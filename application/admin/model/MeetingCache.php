<?php /** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Model;

/***
 * 申请会议时用的缓存model
 * Class MeetingCache
 * @package app\admin\model
 */
class MeetingCache extends Model
{
    protected $name = 'meeting_pending_cache';

    /**
     * 申请会议时临时保存
     * @param $post
     * @return array
     * @throws BaseException
     */
    public function mySave($post)
    {
        $data = $post['data'];
        $token = $post['token'];
        //获取发布者的id
        try {
            $value = (new Token())->getContent($token);
            $admin_id = $value['id'];
        } catch (BaseException $e) {
            throw new BaseException(['code' => 411, 'msg' => '无法获取admin_id']);
        }

        //将$data转化为json 存入数据库
        $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $meetingCache = new MeetingCache();
        $meetingCache->admin_id = $admin_id;
        $meetingCache->json_info = $json_data;
        $res = $meetingCache->save();
        if(!$res){
            throw new BaseException(['code' => 412, 'msg' => '保存失败！']);
        }

        $id = $meetingCache->id;
        return $id;

    }


    /**
     * 获取临时储存的会议信息
     * @param $post
     * @param id
     * @return mixed
     * @throws BaseException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function getInfo($post)
    {
        $id = $post['data']['id'];
        $res = self::get($id);
        if(!$res){
            throw new BaseException(['code' => 411, 'msg' => '无法获取保存内容!']);
        }

        //处理str转化为数组
        $res = stripslashes($res);
        $i = strripos($res, 'json_info');
        $new_str = substr($res,0,$i+11) . substr($res, $i+12, strlen($res)-$i-12);
        $new_str2 = substr($new_str, 0, strlen($new_str)-2) . '}' ;
        //转化为数组
        $info_array = json_decode($new_str2, true);

        //返回数组
        return $info_array;

    }
}
