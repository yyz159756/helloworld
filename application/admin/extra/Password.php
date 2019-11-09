<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/7/6
 * Time: 17:20
 */

namespace app\admin\extra;


class Password
{
    public function password_encrypt($password)
    {
        /**
         * 获得加密的密码
         * 加密规则：md5(salt+password)
         * @param string password 未加密的密码
         * @return string password_encrypted 加密后的密码
         */
        $password_encrypted = md5(config('setting.SALT').$password);
        return $password_encrypted;
    }
}