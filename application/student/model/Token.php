<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/7/28
 * Time: 16:57
 */

namespace app\student\model;


use app\student\exception\BaseException;
use think\Cache;
use think\Exception;

class Token extends BaseModel
{
    /**
     * 获取用于token的key
     * @return string
     */
    public function get_key()
    {
        //用三组字符串md5加密
        //32个字符组成一组随机字符串
        $randChars = getRandChars(32);
        //时间戳
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = config('setting.salt');

        $key = md5($randChars.$timestamp.$salt);

        return $key;
    }

    public function get_uid()
    {
        //从token取uid
        $token = input('post.token');
        $res = Cache::get($token);
        if (!$res) {
            throw new BaseException(['msg' => 'Token已经过期或无效']);
        }
        $res = json_decode($res,true);
        $uid = $res['uid'];
        $auth = $res['auth'];
        return $uid;
    }

}