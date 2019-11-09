<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/6
 * Time: 18:01
 */

namespace app\extra;


class uuid_generator
{
    //没有啥用，已废弃
    public function generate_uuid($namespace = '')
    {
        static $guid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['LOCAL_ADDR'];
        $data .= $_SERVER['LOCAL_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('Quanta', $uid . $guid . md5($data)));
        $guid = '{' .
            substr($hash, 0, 8) .
            '-' .
            substr($hash, 8, 4) .
            '-' .
            substr($hash, 12, 4) .
            '-' .
            substr($hash, 16, 4) .
            '-' .
            substr($hash, 20, 12) .
            '}';
        return $guid;
    }
}