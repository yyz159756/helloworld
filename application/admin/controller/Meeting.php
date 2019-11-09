<?php

namespace app\admin\controller;

use app\admin\controller\MyController;
use app\admin\exception;
use app\admin\model;
use think\Cache;
use app\admin\extra;

/**
 * Class Meeting
 * @package app\admin\controller
 * 活动相关控制器
 */
//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class Meeting extends MyController
{
    public function Meeting()
    {
        $post = input('post.');

        //获取type
        $type = $post['type'];
        //获取data
        $data = $post['data'];


        /***
         * 申请活动
         */
        if ($type == 'A011') {
            //活动申请
            $meeting_id = (new model\MeetingPendingAssess())->applyMeeting($post);
            // TODO 发送邮件 提醒审核
            //邮箱是多少？
            return $this->renderSuccessData('申请成功！', ['meeting_id' => $meeting_id]);
        } /***
         * 会议批准通过审核
         */
        else if ($type == 'A012') {
            //修改审核表中的状态（改为1）并将信息存入meeting表中
            (new model\MeetingPendingAssess())->approve($post['data']['meeting_id']);
            //返回json
            return $this->renderSuccess('已成功批准该会议通过审核！');
        } /***
         * 会议不通过审核
         */
        else if ($type == 'A013') {
            //修改审核表中的状态（改为 -1）并将信息存入meeting表中
            (new model\MeetingPendingAssess())->disapprove($post);
            //返回json
            return $this->renderSuccess('已成功不批准该会议通过审核！');
        } /***
         * 搜索会议并返回会议信息
         * 讲座查看也用这个吧 带meeting_id post过来 其他置为空
         */
        else if ($type == 'A014') {
            //获取会议信息
            $Info = (new model\MeetingPendingAssess())->getInfo($post);
            //返回JSON
            return $this->renderSuccessData('success', $Info);

        } /***
         * 开始会议
         */
        else if ($type == 'A015') {
            //开始会议
            (new model\Meeting())->Start($post);
            //返回JSON
            return $this->renderSuccess('成功开始会议');
        } /***
         * 结束会议
         */
        else if ($type == 'A016') {
            //获取会议信息
            (new model\Meeting())->End($post);
            //取消置顶
            (new model\Meeting())->disTop($post);
            //返回JSON
            return $this->renderSuccess('结束会议');
        } /***
         * 修改讲座信息
         */
        else if ($type == 'A017') {
            //获取会议信息
            (new model\MeetingPendingAssess())->MeetingUpdate($post);
            //返回JSON
            return $this->renderSuccess('修改成功');
        } /***
         * 活动置顶
         */
        else if ($type == 'A018') {
            //将活动置顶
            (new model\Meeting())->Top($post);
            //返回JSON
            return $this->renderSuccess('置顶成功');
        } /***
         * 取消置顶
         */
        else if ($type == 'A019') {
            //将取消活动置顶
            (new model\Meeting())->DisTop($post);
            //返回JSON
            return $this->renderSuccess('取消置顶');
        } /***
         * 查看评论
         */
        else if ($type == 'A020') {
            //查看活动评论
            $commentData = (new model\Comment())->ShowComment($post);
            //返回JSON
            return $this->renderSuccessData('success', $commentData);
        } /***
         * 查看报名名单
         */
        else if ($type == 'A021') {
            //获取报名名单
            $signUpList = (new model\MeetingMember())->signUpList($post);
            //返回JSON
            return $this->renderSuccessData('success', $signUpList);
        } /***
         * 新增管理员
         */
        else if ($type == 'A022') {
            //增加管理员
            (new model\MeetingAdmin())->addAdmin($post);
            //返回json
            return $this->renderSuccess('新增管理员成功');
        } /***
         * 查看出勤名单
         * @param $post
         * @param meeting_id 会议id
         * @return jsonData[]
         */
        else if ($type == 'A023') {
            //获取出勤名单
            $AttendList = (new model\MeetingMember())->AttendList($post);
            //返回JSON
            return $this->renderSuccessData('success', $AttendList);
        } /***
         * 修改出勤名单
         * @param $post []
         * @param meeting_id $post ['data']['meeting_id'] 会议id
         * @param update[] $post ['data']['update'] 要修改的对象信息 array
         * @param number update[0]['number'] 要修改的对象的学号
         * @param status update[0]['status'] 要修改成什么状态 ：1 出席，2 缺席，3 迟到，4 早退，5 请假
         */
        else if ($type == 'A024') {
            //修改出勤名单
            (new model\MeetingMember())->UpdateAttendList($post);
            //返回成功JSON
            return $this->renderSuccess('修改出勤名单成功');
        } /***
         * 讲座发布
         */
        else if ($type == 'A025') {
            //先申请
            $meeting_id = (new model\MeetingPendingAssess())->applyMeeting($post);
            //再同意
            (new model\MeetingPendingAssess())->approve($meeting_id);
            //返回json
            return $this->renderSuccessData('讲座发布成功', ['meeting_id' => $meeting_id]);
        } /***
         * 生成签到二维码 cache存的值是1表示签到 有效期60秒
         * @param $post
         * @param token
         * @param meeting_id
         * @return code和msg的json数据 msg是二维码的url
         */
        else if ($type == 'A026') {
            //生成签到二维码
            $json = (new model\Meeting())->createCode($post);
            //返回json数据
            return $json;
        } /***
         * 生成签退二维码 cache存的值是2表示签退 有效期60秒
         * @param $post
         * @param token
         * @param meeting_id
         * @return code和msg的json数据 msg是二维码的url
         */
        else if ($type == 'A027') {
            //生成签退二维码
            $json = (new model\Meeting())->createSignOutCode($post);
            //返回json
            return $json;
        } /***
         * 二级权限仅查看自己所发布的讲座
         */
        else if ($type == 'A028') {
            //查找所发布过的讲座
            $jsondata = (new model\MeetingPendingAssess())->findMeeting($post);
            //返回json
            return $this->renderSuccessData('success', $jsondata);
        } /***
         * 二级权限申请置顶
         */
        else if ($type == 'A029') {
            //申请置顶
            (new model\MeetingPendingAssess())->applyTop($post);
            //返回json
            return $this->renderSuccess('success');
        } /**
         * 新增讲座预告
         */
        else if ($type == 'A030') {
            //新增讲座预告
            (new model\MeetingNotice())->add($post);
            //返回JSON
            return $this->renderSuccess('新增讲座预告成功');
        } /**
         * 删除讲座预告
         */
        else if ($type == 'A031') {
            //删除讲座预告
            $res = (new model\MeetingNotice())->destroy(['id' => $post['data']['id']]);
            //返回json
            if ($res != 0) {
                return $this->renderSuccess('删除讲座预告成功');
            } else {
                return $this->renderError(400, '删除失败!');
            }

        } /**
         * 更新讲座预告
         */
        else if ($type == 'A032') {
            //更新讲座预告
            (new model\MeetingNotice())->myUpdate($post);
            //返回json
            return $this->renderSuccess('更新讲座预告成功');
        } /**
         * 显示讲座预告
         */
        else if ($type == 'A033') {
            //显示讲座预告
            $res = (new model\MeetingNotice())->all(function ($query) {
                $query->order('month', 'asc');
            });
            if (!$res) {
                return $this->renderError(400, '无结果');
            }
            //返回json
            return $this->renderSuccessData('success', $res);
        } /**
         * 保存临时会议
         */
        else if ($type == 'A034') {
            //保存临时会议
            $meeting_cache_id = (new model\MeetingCache())->mySave($post);
            //返回json
            return $this->renderSuccessData('保存成功', ['id' => $meeting_cache_id]);
        } /**
         * 返回临时保存的会议数据
         */
        else if ($type == 'A035') {
            //返回保存数据
            $info = (new model\MeetingCache())->getInfo($post);
            //返回json
            return $this->renderSuccessData('success', $info);
        } /**
         * 删除临时保存会议
         */
        else if ($type == 'A036') {
            $res = (new model\MeetingCache())->destory(['id' => $post['data']['id']]);
            if (!$res) {
                throw new exception\BaseException(['code' => 411, 'msg' => '删除失败']);
            }

            return $this->renderSuccess('删除成功！');
        } /**
         * 上传会议图片
         */
        else if ($type == 'A037') {
            (new model\MeetingPendingAssess())->updatePhoto($post);
            return $this->renderSuccess('上传成功！');
        } /**
         * 一级权限设置别人二级权限，此二级权限的人会能查到一些额外其他学院的会议
         */
        else if ($type == 'A038') {
            $res = (new model\MeetingPendingAssess())->extraMeetingInfo($post);
            return $this->renderSuccessData('success', $res);
        } /**
         * 删除待审核会议
         */
        else if ($type == 'A039') {
            $res = (new model\MeetingPendingAssess())->destroy(['id' => $post['data']['meeting_id']]);
            if (!$res) {
                throw new exception\BaseException(['code' => 411, 'msg' => '删除失败']);
            }

            return $this->renderSuccess('删除成功！');

        } /**
         * 返回某会议管理员工号的接口
         */
        else if ($type == 'A040'){
            $res = (new model\Meeting()) -> getMeetingManager($post);

            return $this->renderSuccessData('success', $res);

        }

        /***
         * 取消申请活动置顶
         */
        else if ($type == 'A041') {
            //将取消活动置顶
            (new model\MeetingPendingAssess())-> distop_pending_meeting($post);
            //返回JSON
            return $this->renderSuccess('取消置顶成功');
        }

    }

    public function testMail()
    {
        $post = input("post.");
        $department = "信息学院";
        $name = "miaomiaomiao";
        $date1 = 2019; $date2 = 22; $date3 = 222; $time1 = 1; $time2 = 33; $re = "ddd";
        $position = "c503"; $period = 3;
        $address  = ['429242349@qq.com','lesily9@gmail.com'];
        $content = [
            'subject' => 'test',
            'body' => '<center>思政学时活动更新提醒</center><br>各位同学：<br><p style="width: 7px;"></p>'.$department.'将举办“'.$name.
                '活动。本次活动详情如下：<br><p style="width: 7px;"></p>时间：'.$date1.'年'.$date2.'月'.$date3.'日 '.$time1.':'.$time2.'-'.$re.
                '<br><p style="width: 7px;"></p>地点：'.$position.
                '<br><p style="width: 7px;"></p>学时：'.$period.
                '个<br>本次活动报名已经开始，各位同学可通过 “广外学生处”微信公众号，点击下方“学生服务”—“思政学时”进入报名页面，或直接登录“广外思政学时”微信小程序进行报名！'.
                '<br><p style="width: 7px;"></p>欢迎同学们的参与，谢谢！'
        ];
        (new model\Mail())->sendMail($address, $content);
    }

    public function testMail1()
    {
        $post = input("post.");
        $meeting_id = $post['meeting_id'];
        (new model\Mail())->newMeetingAssessNotice($meeting_id);
    }

    public function meeting24hNotice($meeting_id)
    {

    }



}
