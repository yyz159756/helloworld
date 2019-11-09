<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/8
 * Time: 10:28
 */

namespace app\student\model;


use app\student\exception\BaseException;
use think\Db;

class Score extends BaseModel
{
    //表名
    protected $name = 'score';
    //指定主键
    protected $pk = 'id';

    public function getPresenceInfo($data)
    {
        // TODO 这里获取机制需要修改
        //一次直接返回一个人的全部信息
        $res = Score::all(['uid' => $data['uid']]);
        if (!$res) {
            throw new BaseException(['msg' => '获取失败']);
        }
        return $res;
    }

    public function getPresenceInfoN($data)
    {
        $score = 0;
        $ask_leave = 0;
        $presence = 0;
        $absence = 0;
        $late = 0;
        $early_leave = 0;

        $uid = $data['uid'];
        $year = $data['year'];
        $res = db('meeting_member')
            ->join('meeting', 'meeting.id = meeting_member.meeting_id')
            ->where('uid', '=', $uid)
            ->where('term', 'like', "$year%")
            ->select();
        $sql = db("meeting_member")->getLastSql();
        foreach ($res as $k => $v) {
            $meeting = Db::table('meeting')
                ->where('id', '=', $v['meeting_id'])
                ->find();
            //统计出勤情况
            if ($v['ask_leave'] == 1) {
                //请假数+1
                $ask_leave += 1;
//                $Data['meeting'][$k]['status'] = '请假';
            }
            if ($meeting['status'] != -1) continue; //会议未开始，不进行统计
            //如果未签到，未签退，并且未请假 缺勤数+1
            else if ($v['checkin'] == 0 && $v['checkout'] == 0 && $v['ask_leave'] == 0) {
                //缺勤数+1
                $absence += 1;
//                $Data['meeting'][$k]['status'] = '缺勤';
            } else if ($v['checkin'] == 1 && $v['checkout'] == 1) {
                //出席数+1
                $presence += 1;
//                $Data['meeting'][$k]['status'] = '出席';
            } else if ($v['checkin'] == 0 && $v['checkout'] == 1) {
                //迟到数+1
                $late +=1;
//                $Data['meeting'][$k]['status'] = '迟到';
            } else if ($v['checkin'] == 1 && $v['checkout'] == 0) {
                //早退数+1
                $early_leave += 1;
//                $Data['meeting'][$k]['status'] = 'leave';

            }
            //统计学时 把该学年每场会议都加起来
            $score += $v['score'];
        }

        $Data = [
            'score' => $score,
            'absence' => $absence,
            'presence' => $presence,
            'late' => $late,
            'early_leave' => $early_leave,
            'ask_leave' => $ask_leave,
            'year' => $year,
            'uid' => $uid
        ];



        //验证score表
        $sync = Db::table('score')
            ->where($Data)
            ->find();
        if (!$sync) {
            $res1 = Db::table('score')
                ->where(['uid' => $uid, 'year' => $year])
                ->update($Data);
        }
        $Data['res'] = $sql;
        return $Data;
    }
}