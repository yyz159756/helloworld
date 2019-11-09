<?php

namespace app\student\controller;

use app\student\model\Comment;
use app\student\model\Meeting;
use app\student\model\Score;
use app\student\model\Token;
use app\student\model\UserToken;

class Student extends BaseController
{
    /**
     *
     * @throws \app\student\exception\BaseException
     * @throws \app\student\exception\WxException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $post = input('post.');

        //验证传值
        if (!key_exists('type', $post)) {
            $this->renderError(402, 'type参数缺失');
        }
        if (!key_exists('token', $post)) {
            $this->renderError(402, 'token缺失');
        }

        //获取data
        if (key_exists('data', $post)) {
            $data = $post['data'];
        }
        $type = $post['type'];
        //在有token时先在最外层解出uid放入data数组
        if (key_exists('token', $post)) {
            if ($post['token'] != 'login') {
                $data['uid'] = (new Token())->get_uid(); //适应登录时token为login
            }
        }

        //实例化模型
        $StudentModel = new \app\student\model\Student();
        $meeting = new Meeting();
        $validate = new \app\student\validate\Student();


        if ($type == 'S001') {
            /**
             * 学生端登录
             */
            $code = $data['code'];
            $number = $data['number'];
            $password = $data['password'];

            //先验证账号密码
            $var = [
                'number' => $number,
                'password' => $password
            ];
            $res = $validate->scene('login')->check($var);
            if (!$res) {
                return $this->renderError(401, $validate->getError());
            }
            $var = [
                'number' => $number,
                'password' => password_encrypt($password)
            ];
            $res = $StudentModel->validate_password_by_number($var);
            if (!$res) {
                return $this->renderError(401, '用户名或密码错误');
            }

            //这部分在测试不需要微信登录的时候应该注释掉
            //取openid
            $openid = (new UserToken())->getOpenId($code);
            //检查绑定
            $res = (new UserToken())->checkBinding($openid, $number);
            // END

            //查uid
            $uid = $StudentModel->numberToUid($number);
            //保存登录态、发放token
            $token = (new UserToken())->grantToken($uid);
            return $this->renderSuccessData('登陆成功', ['token' => $token]);
        } elseif ($type == 'S002') {
            /**
             * 活动详情
             * @param int meeting_id
             * @return array result
             */
            $var = ['meeting_id' => $data['meeting_id']];
            $val = $validate->scene('id')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $res = $meeting->get_single_meeting($var);
            return $this->renderSuccessData('获取成功', $res);
        } elseif ($type == 'S003') {
            /**
             * 活动报名
             * @param int uid
             * @param int meeting_id
             * @return $result
             */
            $var = [
                'uid' => $data['uid'],
                'meeting_id' => $data['meeting_id'],
                'form_id' => $data['form_id']
            ];
            $val = $validate->scene('meeting_id')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $meeting->registerMeeting($var);
            return $this->renderSuccess('报名成功');
        } elseif ($type == 'S004') {
            /**
             * 活动请假
             * @param int uid
             * @param int meeting_id
             * @return int res
             */
            $var = [
                'uid' => $data['uid'],
                'meeting_id' => $data['meeting_id']
            ];
            $val = $validate->scene('meeting_id')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $meeting->meeting_ask_leave($var);
            return $this->renderSuccess('请假成功');
        } elseif ($type == 'S005') {
            /**
             * 活动签到
             * @param int uid
             * @param int meeting_id
             * @return $result
             */
            $var = [
                'uid' => $data['uid'],
                'meeting_id' => $data['meeting_id'],
                'code_id' => $data['code_id']
            ];
            $val = $validate->scene('meeting_id')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $meeting->meeting_checkin($var);
            return $this->renderSuccess('签到成功');
        } elseif ($type == 'S006') {
            /**
             * 活动签退
             * @param int uid
             * @param int meeting_id
             * @return $result
             */
            $var = [
                'uid' => $data['uid'],
                'meeting_id' => $data['meeting_id'],
                'code_id' => $data['code_id']
            ];
            $val = $validate->scene('meeting_id')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $meeting->meeting_checkout($var);
            return $this->renderSuccess('签退成功');
//        } elseif ($type == 'S007') {
//            //已结束活动查看
        } elseif ($type == 'S008') {
            //已报名活动查看
            $var = ['uid' => $data['uid']];
            $res = $meeting->get_signed_meeting_list($var);
            return $this->renderSuccessData('获取成功', $res);
        } elseif ($type == 'S009') {
            /**
             * 学时统计
             * @param int uid
             * @param string year
             * @return array result
             */
            $var = ['uid' => $data['uid'], 'year' => $data['year']];
            $presence = (new Score())->getPresenceInfoN($var);
            return $this->renderSuccessData('获取成功', $presence);
        } elseif ($type == 'S010') {
            /**
             * 出勤统计
             * TODO 暂时废弃
             */
            $var = [
                'uid' => $data['uid'],
                'year' => $data['year']
            ];
            $res = $validate->scene('score')->check($var);
            if (!$res) {
                return ['code' => 400, 'msg' => $validate->getError()];
            }
            $res = $StudentModel->presence_total($var);
            return $this->renderSuccessData('查询成功', $res);
        } elseif ($type == 'S011') {
            //修改密码
            $password_old = password_encrypt($data['password_old']);
            $password_new = password_encrypt($data['password_new']);
            $var = [
                'uid' => $data['uid'],
                'password_old' => $password_old,
                'password_new' => $password_new
            ];
            $StudentModel->edit_password($var);
            return $this->renderSuccess('修改成功');
        } elseif ($type == 'S012') {
            /**
             * 绑定邮箱（Student）
             * @param int uid $data['uid']
             * @param string email $data['email']
             * @return int res
             */
            $var = [
                'uid' => $data['uid'],
                'email' => $data['email']
            ];
            $val = $validate->scene('email')->check($var);
            if (!$val) {
                return $this->renderError(401, $validate->getError());
            }
            $res = $StudentModel->bind_email($var);
            if (!$res) {
                return $this->renderError(401, '绑定失败');
            }
            return $this->renderSuccess('绑定成功');
        } elseif ($type == 'S013') {
            //查询绑定邮箱
            $var = [
                'uid' => $data['uid']
            ];
            $res = $StudentModel->check_email($var);
            if (!$res) {
                return $this->renderError(401, '查询失败');
            }
            return $this->renderSuccessData('查询成功', $res);
        } elseif ($type == 'S014') {
            /**
             * 活动列表
             * @param int meeting_id
             * @return json res
             */
            $res = (new Meeting())->get_meeting_list_with_info($data['uid']);
            return $this->renderSuccessData('获取成功', $res);
        } elseif ($type == 'S015') {
            //反馈
            //TODO 反馈的格式？
        } elseif ($type == 'S016') {
            //个人信息
            $var = ['uid' => $data['uid']];
            $res = $StudentModel->get_info_by_uid($var);
            //过滤一下敏感信息
            $info = [
                'username' => $res['username'],
                'major' => $res['major'],
                'number' => $res['number']
            ];
            return $this->renderSuccessData('获取成功', $info);
        } elseif ($type == 'S017') {
            //活动评价
            $var = [
                'meeting_id' => $data['meeting_id'],
                'uid' => $data['uid'],
                'content' => $data['content'],
                'holder' => $data['holder'],
                'text' => $data['text']
            ];
            (new Comment())->star_meeting($var);
            return $this->renderSuccess('评价成功');
        } elseif ($type == 'S018') {
            //找回密码

        } elseif ($type == "S099") {
            //退出登录
            //清理登录态
            (new UserToken())->destroyToken($post['token']);
            return $this->renderSuccess('退出成功');
        }


    }
}
