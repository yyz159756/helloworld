<?php

namespace app\admin\controller;

use app\admin\validate\Admin;
use app\admin\model;
use app\admin\exception\BaseException;

//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class Index extends MyController
{
    public function index()
    {
        $post = input('post.');
        $type = $post['type'];
        $data = $post['data'];

        //检查输入
        $val = new Admin();
        $var = [
            'number' => $data['username'],
            'password' => $data['password']
        ];
        $res = $val->check($var);
        if (!$res) {
            return $this->renderError(401, $val->getError());
        }

        $tokenModel = new model\Token();
        $adminModel = new model\Admin();
        /***
         * 登陆
         */
        //判断类型
        if ($type == 'A001') {
            //登陆
            $code = $data['code'];
            $username = $data['username'];
            $password = $data['password'];
            //检验username和password并获取admin_id
            $res = $adminModel->login($post);

            //本地测试时可注释掉微信绑定的部分
            //获取openid
            $openid = (new model\AdminToken())->getOpenId($code);
            //检查绑定
            $res1 = (new model\AdminToken())->checkBinding($openid, $username);
            //END

            //获得token
            $token = (new model\Token())->getToken();
            //获得用户权限
            $level = $res[0]['level'];
            //获取用户id
            $id = $res[0]['id'];
            //获取用户number
            $number = $res[0]['number'];
            //获取用户major
            $major = $res[0]['major'];
            //存入缓存
            cache($token, compact('id', 'level', 'number', 'major'), 60 * 60 * 24 * 7 * 12);
            //返回json
            return $this->renderSuccessData("success", compact('token', 'level', 'id', 'major'));
        } /***
         * 网页登陆接口
         */
        else if ($type == 'A002') {

            //登陆
            $username = $data['username'];
            $password = $data['password'];
            //检验username和password并获取admin_id
            $res = $adminModel->login($post);
            //获得token
            $token = $tokenModel->getToken();
            //获得用户权限
            $level = $res[0]['level'];
            //获取用户id
            $id = $res[0]['id'];
            //获取用户number
            $number = $res[0]['number'];
            //获取用户major
            $major = $number = $res[0]['major'];
            //获取用户name
            $name = $number = $res[0]['name'];


            //存入缓存
            cache($token, compact('id', 'level', 'number', 'major'), 60 * 60 * 24 * 7 * 12);
            //返回json
            return $this->renderSuccessData("success", compact('token', 'level', 'id', 'major', 'name'));
        } /***
         * 最高权限注册接口
         */
        else if ($type == 'A003') {

            //进行注册
            $adminModel->reg($post);
            //返回json
            return $this->renderSuccess('注册成功！');

        } /***
         * 修改密码
         */
        else if ($type == 'A004') {
            (new model\Admin())->updatePassword($post);
            return $this->renderSuccess('修改成功！');
        } /***
         * 发送绑定邮箱激活码
         */

        else if ($type == 'A005') {
            $bind_email_token = (new model\BindEmail())->sendActivationCode($post);
            return $this->renderSuccessData('发送成功', ['bind_email_token' => $bind_email_token]);

        } /***
         * 验证并绑定邮箱
         */
        else if ($type == 'A006') {
            (new model\BindEmail())->bindEmail($post);
            return $this->renderSuccess('绑定成功！');

        } /***
         * 退出登陆
         */
        else if ($type == 'A007') {
            $token = $post['token'];
            cache($token, NULL);
            return json_encode([
                'code' => 200,
                'msg' => '退出成功！'
            ]);
        }

        return $this->renderError('400', 'type错误！');

    }


}
