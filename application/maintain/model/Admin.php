<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/14
 * Time: 10:40
 */

namespace app\maintain\model;


use app\maintain\exception\BaseException;
use think\Model;

class Admin extends Model
{
    protected $name = 'admin';
    protected $pk = 'id';

    public function validate_password($data)
    {
        $password = password_encrypt($data['password']);
        $res = Admin::get(['id' => $data['id'], 'password' => $password]);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => 'id或密码错误']);
        }
        session('id', $data['id']);
        return 1;
    }

    public function validate_password_s($data)
    {
        $username = 'ppms3.0';
        $password = '9c58d7dec088526c26affacc2dd3da1c';
        $data['password'] = password_encrypt($data['password']);
        if ($data['password'] == $password and $data['username'] == $username) {
            return 1;
        }
        return 0;
    }

}