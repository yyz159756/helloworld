<?php

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Db;
use think\Model;

class WechatMessage extends Model
{
    protected $appid;
    protected $appsecret;
    protected $url;
    protected $access_token;

    /**
     * 获取access_token并存入缓存（2小时有效）
     * @throws BaseException
     */
    public function getAccessToken()
    {
        $this->appid = config('wechat.app_id');
        $this->appsecret = config('wechat.app_secret');
        $this->url = sprintf(config('wechat.access_token_url'),
            $this->appid, $this->appsecret);
        //执行GET请求
        $res = curl_get($this->url);
        $wxResult = json_decode($res, true);
        if (empty($wxResult)) {
            throw new BaseException('微信内部错误');
        } else {
            $fail = array_key_exists('errcode', $wxResult);
            if ($fail) {
                return $this->Error($wxResult);
            }
            //不用每次都取，可以cache起來，有效期两个小时
            cache(['type' => 'redis']);
            cache('access_token', $wxResult['access_token'], 60 * 60 * 2);
        }
    }

    /**
     * 发送微信模板消息
     * @param $openid
     * @param $form_id
     * @param $data
     * @return int|void
     * @throws BaseException
     */
    public function sendMessage($openid, $form_id, $data)
    {
        //获取access_token并完成请求的url
        //先尝试从cache取
        $this->access_token = cache('access_token');
        if (!$this->access_token) {
            $this->access_token = $this->getAccessToken();
        }
        $this->url = sprintf(config('wechat.notify_url'), $this->access_token);

        //构造请求信息
        $post_data = [
            'touser' => $openid,
            'template_id' => config('wechat.notify_template_id'),
            'form_id' => $form_id,
            'data' => [
                'keyword1' => $data['name'], //讲座名称
                'keyword2' => $data['time'], //时间
                'keyword3' => $data['type'], //课程类型
                'keyword4' => $data['teacher'], //讲师
                'keyword5' => $data['location'], //上课地点
                'keyword6' => $data['warn'], //温馨提示
                'keyword7' => $data['note'], //备注
                'keyword8' => $data['score'] //学时
            ]
        ];
        $res = curl_post($this->url, $post_data);
        $wxResult = json_decode($res, true);
        if (empty($wxResult)) {
            throw new BaseException('微信内部错误');
        } else {
            $fail = array_key_exists('errcode', $wxResult);
            if ($fail) {
                return $this->Error($wxResult);
            }
        }
        return 1;
    }

    /**
     * 会议24小时前发送微信模板通知
     * @param $meeting_id
     * @param $warn
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function aheadNotice24h($meeting_id, $warn)
    {
//        后台运行
        ignore_user_abort(true);
        set_time_limit(0);
        header('HTTP/1.1 200 OK');
        header('Content-Length:0');
        header('Connection:Close');
        flush();

        //获取讲座信息
        $meeting = Db::table('meeting')
            ->where(['meeting_id' => $meeting_id])
            ->find();
        $data = [
            'name' => $meeting['name'],
            'time' => $meeting['date'],
            'type' => $meeting['type'],
            'teacher' => $meeting['department'],
            'location' => $meeting['position'],
            'note' => $meeting['description'],
            'score' => $meeting['score'],
            'warn' => $warn
        ];
        //取需要发送消息的学生信息
        $students = Db::table('wechat_message')
            ->where(['meeting_id' => $meeting_id])
            ->select();
        //发送模板消息
        foreach ($students as $s) {
            try {
                $this->sendMessage($s['openid'], $s['form_id'], $data);
            } catch (BaseException $e) {
                continue;
            }
        }

    }

    /**
     * 报错
     * @param $wxResult
     * @throws BaseException
     */
    private function Error($wxResult)
    {
        throw new BaseException(['code' => 405, 'msg' => $wxResult['errmsg']]);
    }
}