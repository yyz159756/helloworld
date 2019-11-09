<?php

namespace app\admin\model;

use app\admin\exception\BaseException;
use app\admin\extra\Password;
use think\Model;

/**
 * Class Admin
 * @package app\admin\model
 * 管理者model
 */
class Admin extends Model
{
    protected $name = 'admin';

    //注册
    public function reg($post)
    {
        $username = $post['data']['username'];
        $password = $post['data']['password'];

        //新增验证器
        $var = [
            'number' => $username,
            'password' => $password
        ];
        $admin_val = new \app\admin\validate\Admin();
        $res = $admin_val->check($var);
        if (!$res) {
            throw new BaseException(['msg' => $admin_val->getError()]);
        }

        $id = $this->save([
            'number' => "$username",
            'password' => password_encrypt($password),
            'level' => 1
        ]);

        if (!$id) {
            throw new BaseException(['msg' => '未注册成功！']);
        }

        return $id;
    }

    public function login($post)
    {
        $username = $post['data']['username'];
        $password = $post['data']['password'];


        //验证器
        $admin_val = new \app\admin\validate\Admin();
        $var = ['number' => $username, 'password' => $password];
        $res = $admin_val->check($var);
        if (!$res) {
            throw new BaseException(['msg' => $admin_val->getError()]);
        }
        //检查账号名是否在表中
        $result = db('admin')
            ->where(['number' => $username])
            ->select();

        if (!$result) {
            throw new BaseException(['code' => '401', 'msg' => '用户名不存在！']);
        }
        //检测密码是否正确


        $res = $this->where([
            'number' => $username,
            'password' => md5(config('setting.SALT') . $password)
        ])->field('id')->find();

        if (!$res) {
            throw new BaseException(['code' => '402', 'msg' => '密码错误！']);
        }


        //返回res
        return $result;
    }

    /**
     * 修改密码
     *
     * @param $post
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updatePassword($post)
    {
        $token = $post['token'];
        $password = $post['data']['password'];
        $new_password = $post['data']['new_password'];
        $new_password2 = $post['data']['new_password2'];
        try {
            //获取admin_id
            $admin_info = (new Token())->getContent($token);
            $admin_id = $admin_info['id'];
        } catch (\Exception $e) {
            echo $e;
            throw new BaseException(['code' => 500, 'msg' => 'error']);
        }
        //确认旧密码
        $res = self::where('id', '=', $admin_id)
            ->where('password', '=', (new Password())->password_encrypt($password))
            ->select();

        if (!$res) {
            throw new BaseException(['code' => 411, 'msg' => '原密码不正确！']);
        }
        //确认新密码一致
        if ($new_password != $new_password2) {
            throw new BaseException(['code' => 412, 'msg' => '新密码应该一致！']);
        }
        //更改密码
        $res = self::where('id', '=', $admin_id)
            ->update(['password' => (new Password())->password_encrypt($new_password)]);

        if (!$res) {
            throw new BaseException(['code' => 413, 'msg' => '更改失败！']);
        }


    }
}
