<?php

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Model;
use app\admin\extra\Password;
use app\admin\extra;
use app\admin\model\Student;

class StudentArrange extends Model
{

    /**
     * 根据学号和学期
     * 返回该学生的姓名，学号，学院与该学期的学时和 缺勤数
     * @param $number
     * @param $term
     */
    public function findScore($number, $term)
    {
        //获取学生信息
        $info = (new Student())->getInfo_bynumber($number);
        if ($info == 0) {
            throw new BaseException(['code' => 411, 'msg' => '无法获取学生信息！']);
        }
        try {
            $Data['username'] = $info['username'];
            $Data['number'] = $number;
            $Data['major'] = $info['major'];
            $uid = $info['uid'];
        } catch (\Exception $e) {
            throw new BaseException(['code' => 411, 'msg' => '无法获取学生信息！']);
        }
        //统计学时和缺勤数 去meeting_member表里面去找
        $res = db('meeting_member')
            ->join('meeting', 'meeting.id = meeting_member.meeting_id', 'LEFT')
            ->where('uid', '=', $uid)
            ->where('term', 'like', "$term%")
            ->select();

        //学时
        $score = 0;
        //缺席数
        $absence = 0;
        //出席数
        $attend = 0;
        //迟到数
        $late = 0;
        //早退数
        $leave = 0;
        //请假数
        $ask_leave = 0;

        if (!$res) {
            //如果没找到的 学时和缺勤数都为0
            $Data['score'] = 0;
            $Data['absence'] = 0;
            $Data['attend'] = 0;
            $Data['late'] = 0;
            $Data['leave'] = 0;
            $Data['ask_leave'] = 0;
        } else {
            //如果找到了 统计学时和 缺勤数 出席数 缺席数 迟到数 早退数 请假数
            foreach ($res as $k => $v) {
                if($v['status'] != -1){//如果会议未开始，不统计
                    continue;
                }
                if ($v['ask_leave'] == 1) {
                    //请假数+1
                    $ask_leave = $ask_leave + 1;
                } //如果未签到，未签退，并且未请假 缺勤数+1
                else if ($v['checkin'] == 0 && $v['checkout'] == 0 && $v['ask_leave'] == 0) {
                    //缺勤数+1
                    $absence = $absence + 1;
                    $score = $score - 2;
                } else if ($v['checkin'] == 1 && $v['checkout'] == 1) {
                    //出席数+1
                    $attend = $attend + 1;
                    $score = $score + $v['score'];
                } else if ($v['checkin'] == 0 && $v['checkout'] == 1) {
                    //迟到数+1
                    $late = $late + 1;
                } else if ($v['checkin'] == 1 && $v['checkout'] == 0) {
                    //早退数+1
                    $leave = $leave + 1;
                }
                //统计学时 把该学年每场会议都加起来
            }
            $Data['score'] = $score;
            $Data['absence'] = $absence;
            //下面也可以用，不过json不需要返回这些信息
//            $Data['attend'] = $attend;
//            $Data['late'] = $late;
//            $Data['leave'] = $leave;
//            $Data['ask_leave'] = $ask_leave;
            $Data['term'] = $term;
        }//else
        return $Data;
    }

    /**
     * 根据学号和学期 返回该学生在该学年里每场会议信息与出勤情况
     * 会议信息：讲座名称 地点 日期 出席状态
     * @param $number 学号
     * @param $term 学期
     * @return data['username'] 姓名
     * @return data['number'] 学号
     * @return data['major'] 学院
     * @return data['score'] 本学期学时之和
     * @return data['absence'] 缺勤数
     * @return data['attend'] 出席数
     * @return data['late'] 迟到数
     * @return data['leave'] 早退数
     * @return data['ask_leave'] 请假数
     * @return data['term'] 学期
     * @return data['meeting'][0]['meeting_name'] 会议名
     * @return data['meeting'][0]['position'] 会议地点
     * @return data['meeting'][0]['date'] 会议日期
     * @return data['meeting'][0]['status'] 出席状态
     *
     *
     */
    public function MeetingInfo($number, $term)
    {
        //获取学生信息
        $studentInfo = (new Student())->getInfo($number);
        try {
            $Data['username'] = $studentInfo['username'];
            $Data['number'] = $number;
            $Data['major'] = $studentInfo['major'];
            //获取该学生的uid
            $uid = $studentInfo['uid'];
        } catch (\Exception $e) {
            throw new BaseException(['code' => 411, 'msg' => '无法获取该学生的信息!']);
        }
        //获取该学生参加了哪些会议
        $res = db('meeting_member')
            ->join('meeting', 'meeting.id = meeting_member.meeting_id')
            ->where('uid', '=', $uid)
            ->where('term', 'like', "$term%")
            ->select();
        //学时
        $score = 0;
        //缺席数
        $absence = 0;
        //出席数
        $attend = 0;
        //迟到数
        $late = 0;
        //早退数
        $leave = 0;
        //请假数
        $ask_leave = 0;
        if (!$res) {
            //如果没找到的 学时和缺勤数都为0
            $Data['score'] = 0;
            $Data['absence'] = 0;
            $Data['attend'] = 0;
            $Data['late'] = 0;
            $Data['leave'] = 0;
            $Data['ask_leave'] = 0;
            $Data['term'] = $term;
        } else {
            //如果找到了 统计学时和 缺勤数 出席数 缺席数 迟到数 早退数 请假数
            //并且整理会议信息
            foreach ($res as $k => $v) {

                //会议信息打包
                $Data['meeting'][$k]['meeting_name'] = $v['name'];
                $Data['meeting'][$k]['position'] = $v['position'];
                $Data['meeting'][$k]['date'] = $v['date'];
                $Data['meeting'][$k]['meeting_id'] = $v['meeting_id'];
                //统计出勤情况
                if ($v['ask_leave'] == 1) {
                    //请假数+1
                    $ask_leave = $ask_leave + 1;
                    $Data['meeting'][$k]['status'] = '请假';
                } //如果未签到，未签退，并且未请假 缺勤数+1
                else if ($v['checkin'] == 0 && $v['checkout'] == 0 && $v['ask_leave'] == 0) {
                    //缺勤数+1
                    $absence = $absence + 1;
                    $Data['meeting'][$k]['status'] = '缺勤';
                } else if ($v['checkin'] == 1 && $v['checkout'] == 1) {
                    //出席数+1
                    $attend = $attend + 1;
                    $Data['meeting'][$k]['status'] = '出席';
                } else if ($v['checkin'] == 0 && $v['checkout'] == 1) {
                    //迟到数+1
                    $late = $late + 1;
                    $Data['meeting'][$k]['status'] = '迟到';
                } else if ($v['checkin'] == 1 && $v['checkout'] == 0) {
                    //早退数+1
                    $leave = $leave + 1;
                    $Data['meeting'][$k]['status'] = 'leave';

                }
                //统计学时 把该学年每场会议都加起来
                $score = $score + $v['score'];

            }
            $Data['score'] = $score;
            $Data['absence'] = $absence;
            $Data['score'] = $score;
            $Data['absence'] = $absence;
            $Data['attend'] = $attend;
            $Data['late'] = $late;
            $Data['leave'] = $leave;
            $Data['ask_leave'] = $ask_leave;
            $Data['term'] = $term;
        }//else
        return $Data;


    }

    /**
     * 通过搜索学生姓名或学号查找学生，
     * 可筛选学院、年级（2019、2018、2017、2016）来查看学生状态；
     * 通过选择学年（包含2018-2019学年、2019-2020学年）查看学时
     * @param $post
     * @param name 姓名
     * @param number 学号
     * @param major 学院 major
     * @param year 年级 如果是2018级 填2018
     * @param term 学年 ：2019-2020 则填 20192020
     * @return data[] 返回姓名，学号，学院，学时和缺勤数
     * @throws BaseException
     */
    public function getScoreAndAbsence($post)
    {
        $data = $post['data'];
        $name = $data['name'];
        $number = $data['number'];
        $major = $data['major'];
        $year = $data['year'];
        $term = $data['term'];

        //判断管理员权限
        $token = $post['token'];
        $admin = (new Token())->getContent($token);
        if ($admin['level'] != 1) {
            $admin_major = $admin['major'];
        }

        //如果填了学号，姓名为''空
        if ($number != '' && $name == '') {
            //获取学生信息 学时和缺勤数
            $info[0] = StudentArrange::findScore($number, $term, $admin_major);
            return $info;
        }//if
        //如果填了姓名 没填学号
        else if ($number == '' && $name != '') {
            //获取学生信息
            $res = db('student')
                ->where('username', '=', $name)
                ->select();
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '查无此人！']);
            }
            foreach ($res as $k => $v) {
                //获取学生信息 学时和缺勤数
                $info[$k] = StudentArrange::findScore($v['number'], $term);
            }
            return $info;

        }//else if
        //如果学号和姓名都没有填 就根据筛选条件major，year，term来返回数据
        else if ($number == '' && $name == '') {
            //获取学生信息
            if ($major == '全部学院') {
                $res = db('student')
                    ->where('number', 'like', "$year%")
                    ->select();
            } else {
                $res = db('student')
                    ->where('number', 'like', "$year%")
                    ->where('major', '=', $major)
                    ->select();
            }
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '查无此人！']);
            }
            foreach ($res as $k => $v) {
                //获取学生信息 学时和缺勤数
                $info[$k] = StudentArrange::findScore($v['number'], $term);
            }
            return $info;
        }
    }

    /**
     * 重置学生的密码
     * post学号过来，重置密码
     * 加密方式 MD5(SALT + DEFAULT_PASSWORD)
     * @param $post
     * @param number 学号
     */
    public function ResetPassword($post)
    {
        $data = $post['data'];
        $number = $data['number'];

        $update = db('student')
            ->where('number', '=', $number)
            ->update([
                'password' => password_encrypt(config('setting.DEFAULT_PASSWORD')),
            ]);
    }

    /***
     * 进行单个学生多场活动批量编辑，点击“编辑”按钮跳转编辑页面，同出勤名单编辑交互
     * 修改出勤名单
     * @param $post []
     * @param number 学号
     * @param update[] $post ['data']['update'] 要修改的对象信息 array
     * @param meeting_id update[0]['number'] 要修改的会议id
     * @param status update[0]['status'] 要修改成什么状态 ：1 出席，2 缺席，3 迟到，4 早退，5 请假
     */
    public function updateAttend($post)
    {
        $number = $post['data']['number'];
        $update = $post['data']['update'];

        foreach ($update as $k => $v) {
            $meeting_id = $v['meeting_id'];
            $status = $v['status'];
            if ($status == '1') {
                //获取uid
                $uid = db('student')
                    ->where('number', '=', $number)
                    ->value('uid');
                //获取该会议的学时
                $score = db('meeting')
                    ->where('id', '=', $meeting_id)
                    ->value('score');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('uid', '=', $uid)
                    ->update([
                        'checkin' => 1,
                        'checkout' => 1,
                        'ask_leave' => 0,
                        'score' => $score
                    ]);
            } //修改成缺席
            else if ($status == '2') {
                //获取uid
                $uid = db('student')
                    ->where('number', '=', $number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('uid', '=', $uid)
                    ->update([
                        'checkin' => 0,
                        'checkout' => 0,
                        'ask_leave' => 0,
                        'score' => -2,
                    ]);
            } //修改成迟到
            else if ($status == '3') {
                //获取uid
                $uid = db('student')
                    ->where('number', '=', $number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('uid', '=', $uid)
                    ->update([
                        'checkin' => 0,
                        'checkout' => 1,
                        'ask_leave' => 0,
                        'score' => 0,
                    ]);
            } //修改成早退
            else if ($status == '4') {
                //获取uid
                $uid = db('student')
                    ->where('number', '=', $number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('uid', '=', $uid)
                    ->update([
                        'checkin' => 1,
                        'checkout' => 0,
                        'ask_leave' => 0,
                        'score' => 0,
                    ]);
            } //修改成请假
            else if ($status == '5') {
                //获取uid
                $uid = db('student')
                    ->where('number', '=', $number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('uid', '=', $uid)
                    ->update([
                        'checkin' => 0,
                        'checkout' => 0,
                        'ask_leave' => 1,
                        'score' => 0,
                    ]);
            }

        }
    }
}
