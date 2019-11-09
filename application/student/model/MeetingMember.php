<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/8
 * Time: 22:01
 */

namespace app\student\model;


use think\Db;

class MeetingMember extends BaseModel
{
    protected $name = 'meeting_member';
    protected $pk = 'id';

    public function getStudentStatus($meeting_id, $uid)
    {
        //判断学生参与会议的状态
        //先判断是否参加
        $res = Db::table('meeting_member')
            ->where(['meeting_id' => $meeting_id, 'uid' => $uid])
            ->find();
        $res2 = Db::table('meeting')
            ->where(['id' => $meeting_id])
            ->find();
        if ($res2['status'] == 1) $is_start = 1;
        elseif ($res2['status'] == -1) $is_start = -1;
        elseif ($res2['status'] == 0) $is_start = 0;
        if (!$res) {
            return -4; //未参加
        }
        if ($res['checkin'] == 1 and $res['checkout'] == 0 and $is_start == 1) {
            //已签到
            return 1;
        } elseif ($res['checkin'] == 1 and $res['checkout'] == 1 and $is_start == -1) {
            //已参与
            return 2;
        } elseif ($res['checkin'] == 0 and $res['checkout'] == 0 and $is_start == -1) {
            return 3;
        }elseif ($res['checkin'] == 0 and $res['checkout'] == 1 and $is_start == -1) {
            //迟到
            return -1;
        } elseif ($res['checkin'] == 1 and $res['checkout'] == 0 and $is_start == -1) {
            //早退
            return -2;
        } elseif ($res['ask_leave'] == 1) {
            //已请假
            return -3;
        } elseif ($res['checkin'] == 0 and $res['checkout'] == 0 and $res['ask_leave'] == 0) {
            return 0; //已报名
        }
    }


}