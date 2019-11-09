<?php /** @noinspection ALL */

/**
 * Created by PhpStorm.
 * Student: yyz
 * Date: 2019/8/4
 * Time: 16:53
 */

namespace app\admin\model;

use app\admin\exception\BaseException;
use app\student\exception\WxException;
use think\Db;
use think\exception\ErrorException;
use think\exception;
class AdminToken extends Token
{
    protected $secret;
    protected $uid;
    protected $code;
    protected $wxAppId;
    protected $wxAppSecret;
    protected $wxUrl;

    /**
     * 用从前端拿到的code在wechatapi查到openid并返回
     * @param string $code 小程序传过来的code（临时登录凭证）
     * @return string $openid
     * @throws Exception
     * @throws WxException
     */
    public function getOpenId($code)
    {
        $this->code = $code;
        $this->wxAppId = config('wechat.app_id');
        $this->wxAppSecret = config('wechat.app_secret');
        //使用格式化字符串构造URL
        $this->wxUrl = sprintf(config('wechat.url'),
            $this->wxAppId, $this->wxAppSecret, $this->code);
        //执行GET请求
        // TODO 此处封装的curl来自ppms2.0，未经验证
        $res = curl_get($this->wxUrl);
        // TODO 此处token机制未完成
        $wxResult = json_decode($res, true);
        if (empty($wxResult)) {
            //没有接收到返回信息
            throw new BaseException(['msg' => '微信内部错误']);
        } else {
            //若请求失败会返回errcode
            $fail = array_key_exists('errcode', $wxResult);
            if ($fail) {
                return $this->LoginError($wxResult);
            }
            return $wxResult['openid'];
        }
    }

    public function checkBinding($openid, $number)
    {
        $user = Db::table('admin')
            ->where(['number' => $number])
            ->field('openid')
            ->find();
        $user_openid = $user['openid'];
        //检测openid是否绑定
        $check_exist = Db::table('admin')
            ->where(['openid' => $openid])
            ->find();
        if ($check_exist) {
            if ($check_exist['number'] != $number) {
                throw new BaseException(['msg' => '你的微信号已经绑定过一个学号了，不可重复关联！']);
            }
        }
        if ($user_openid == NULL) {
            //未绑定直接更新
            $res = Db::table('admin')
                ->where(['number' => $number])
                ->update(['openid' => $openid]);
            if ($res) {
                return 1;
            }
        } else {
            if ($user_openid != $openid) {
                throw new BaseException(['msg' => '微信号与学号不匹配']);
            }
        }
    }

    /**
     * 保存登录态
     * @param $uid
     * @return string
     * @throws Exception
     */
    public function grantToken($uid)
    {
        //获得随机字符串作为key
        $key = (new Token())->getToken_();
        $ori_value = [
            'uid' => $uid,
            'auth' => 1 //0为学生,1为老师
        ];
        $value = json_encode($ori_value);
        //生存时间 20周
        $expire_time = config('setting.expire_time');
        //初始化缓存
        $option = ['type' => 'File']; //指定用File方式缓存
        cache($option);
        //存进缓存
        $res = cache($key, $value, $expire_time);
        if (!$res) {
            throw new Exception(['缓存错误']);
        }
        return $key;
    }

    /**
     * 从小程序获得code然后去wechatAPI请求openid和session_key
     * @param string code 小程序传过来的code（临时登录凭证）
     * @param string uid 唯一用户id
     * @return string token
     * @throws Exception
     */
    public function getToken_($code, $uid)
    {

        //构造要GET请求的url
        $this->code = $code;
        $this->wxAppId = config('wechat.app_id');
        $this->wxAppSecret = config('wechat.app_secret');
        $this->wxUrl = sprintf(config('wechat.url'),
            $this->wxAppId, $this->wxAppSecret);
        //执行GET请求
        // TODO 此处封装的curl来自ppms2.0，未经验证
        $res = curl_get($this->wxUrl);
        // TODO 此处token机制未完成
        $wxres = json_decode($res, true);
        if (empty($wxres)) {
            throw new Exception('微信内部错误');
        } else {
            //如果请求失败会返回一个errcode 详见微信开发文档
            $fail = array_key_exists('errcode', $wxres);
            if ($fail) {
                return $this->LoginError($wxres);
            } else {
                //无报错，取token去咯
                return $this->grantToken($wxres, $uid);
            }
        }

    }



    /**
     * 返回错误信息
     * @param array wxres
     * @throws \app\student\exception\WxException
     */
    private function LoginError($wxres)
    {
        throw new BaseException([
            'code' => 400,
            'msg' => $wxres['errmsg']
        ]);
    }


}