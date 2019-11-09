<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/14
 * Time: 10:22
 */

namespace app\maintain\controller;


use app\maintain\exception\BaseException;
use app\maintain\model\Admin;
use app\maintain\model\BaseModel;
use think\Db;

//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');


class Maintain extends BaseController
{
//    public function index()
//    {
//        $post = input('post.');
//        $type = $post['type'];
//        if (key_exists('data',$post)) {
//            $data = $post['data'];
//        }
////        if (key_exists('token', $post)) {
////            $data = $post['token'];
////        }
//        if ($type == 'M01') {
//            //维护端登录
//            $res = (new Admin())->validate_password($data);
//            return $this->renderSuccess('登录成功');
//        } elseif($type == 'M02') {
//
//        }
//    }

    public function login()
    {
        //M01维护端登录

        $var = [
            'username' => input('post.username'),
            'password' => input('post.password')
        ];
        $res = (new Admin())->validate_password_s($var);
        if (!$res) {
            return $this->renderError(401, '用户名或密码错误');
        }
        return $this->renderSuccess('登录成功');

    }

    public function noRecordMeeting()
    {
        //M02无记录会议（返回excel处理结果）
        //处理文件上传
        $file = request()->file('file');
        if ($file) {
            //过滤文件格式
            $info = $file->validate(['ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'excel');
            if ($info) {
                $fileName = 'excel' . DS . $info->getSaveName();
                cache('filename', $fileName);
            } else {
                throw new BaseException(['code' => 400, 'msg' => $file->getError()]);
            }
        }
        $res = (new BaseModel())->noRecordMeeting($fileName);
        return $this->renderSuccessData('读取成功', $res);
    }

    public function confirmNoRecordMeeting()
    {
        //M03确认录入无记录会议
        $fileName = cache('filename');
        $res = (new BaseModel())->confirmNoRecordMeeting($fileName);
        if (!$res) {
            return $this->renderError(400, '录入失败');
        }
        return $this->renderSuccess('录入成功');
    }

    public function getScore()
    {
        //M04查询学时接口

        $input = input('post.input');
        // TODO 验证器
        if (is_numeric($input)) {
            //输入的是学号
            $res = (new BaseModel())->getScoreBySid($input);
        } else {
            //输入的是姓名
            $res = (new BaseModel())->getScoreByName($input);
        }
        return $this->renderSuccessData('获取成功', $res);
    }


    public function editScore()
    {
        if (request()->isAjax()) {

        }
    }

    public function updateStudentScore()
    {
        //先获取全部学生
        $uids = Db::table('student')
            ->select();
        foreach ($uids as $u) {
            (new BaseModel())->updateStudentScore($u['uid']);
        }
        return $this->renderSuccess("更新成功");
    }

    public function clearAdminOpenid()
    {
        $number = input('post.number');
        (new BaseModel())->clearAdminOpenid($number);
        return $this->renderSuccess('清除'.$number.'openid成功');
    }

    public function clearStudentOpenid()
    {
        $number = input('post.number');
        (new BaseModel())->clearStudentOpenid($number);
        return $this->renderSuccess('清除'.$number.'openid成功');
    }


}