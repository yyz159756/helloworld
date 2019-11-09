<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/6
 * Time: 17:20
 */

namespace app\extra;


class Password
{
    public function password_encrypt($var)
    {
        /**
         * 获得加密的密码
         * 加密规则：md5(salt+password)
         * @param string password 未加密的密码
         * @return string password_encrypted 加密后的密码
         */
        //salt
        $salt = config('setting.salt');
        //password
        $password = $var['password'];

        $password_encrypted = md5($salt+$password);
        return $password_encrypted;
    }
}