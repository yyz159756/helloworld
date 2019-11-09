<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/14
 * Time: 11:50
 */

namespace app\maintain\model;

use app\maintain\exception\BaseException;
use app\student\model\Student;
use think\Db;
use think\Model;

class BaseModel extends Model
{
    /**
     * 读取excel中的参会人员信息
     * @param $fileName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function noRecordMeeting($fileName)
    {
        //打开excel
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $inputFileName = $fileName;
        $spreadsheet = $reader->load($inputFileName);
        //提取会议信息
        $meeting_name = $spreadsheet->getActiveSheet()->getCell('B2')->getValue();
        $date = $spreadsheet->getActiveSheet()->getCell('D2')->getValue();
        $type = $spreadsheet->getActiveSheet()->getCell('F2')->getValue();
        $score = $spreadsheet->getActiveSheet()->getCell('H2')->getValue();
        //提取参会人员信息
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $vars = [];
        //这里只提取学号就可以了 务必保证学号正确
        for ($row = 4; $row <= $highestRow; ++$row) {
            $institude = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $name = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $number = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $var = ['institude' => $institude, 'name' => $name, 'number' => $number,
                'meeting_name' => $meeting_name, 'date' => $date, 'score' => $score];
            array_push($vars, $var);
        }
        return $vars;

    }

    /**
     * 确定录入excel中的信息，并写入数据库
     * @param $fileName
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmNoRecordMeeting($fileName)
    {
        //打开excel
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $inputFileName = $fileName;
        $spreadsheet = $reader->load($inputFileName);
        //提取会议信息
        $meeting_name = $spreadsheet->getActiveSheet()->getCell('B2')->getValue();
        $date = $spreadsheet->getActiveSheet()->getCell('D2')->getValue();
        $type = $spreadsheet->getActiveSheet()->getCell('F2')->getValue();
        $score = $spreadsheet->getActiveSheet()->getCell('H2')->getValue();
        //提取参会人员信息
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();//meeting表写入会议信息
        Db::table('meeting')
            ->insert(['name' => $meeting_name, 'date' => $date, 'type' => $type, 'score' => $score, 'status' => -1, 'photo' => config('setting.DEFAULT_PHOTO'),
                'begin_time' => $date, 'end_time' => $date, 'term' => '201920201', 'position' => '-']);
        $res = Db::table('meeting')->where(['name' => $meeting_name])->find();
        $meeting_id = $res['id'];
        $numbers = []; //只用查学号
        for ($row = 4; $row <= $highestRow; ++$row) {
            $number = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            array_push($numbers, $number);
        }
        Db::startTrans();
        try {
            //meeting_member表插入参会人员
            $vars = []; //插入列表
            foreach ($numbers as $n) {
                //需要从学号反查uid
                $uid = (new Student())->numberToUid($n);
                $var = ['meeting_id' => $meeting_id, 'uid' => $uid, 'checkin' => 1,
                    'checkout' => 1, 'ask_leave' => 0, 'score' => $score];
                array_push($vars, $var);
            }
            Db::table('meeting_member')
                ->insertAll($vars);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
        return 1;
    }


    public function errorCorrect($fileName)
    {

    }

    public function getScoreBySid($Sid)
    {
        $uid = (new Student())->numberToUid($Sid);
        $res = Db::table('score')
            ->where(['uid' => $uid])
            ->select();
        if (!$res) {
            throw new BaseException(['code' => 400, 'msg' => '姓名或学号不存在']);
        }
        return $res;
    }

    public function getScoreByName($name)
    {
        $res = Db::table('student')
            ->where('username','like',$name)
            ->field('uid')
            ->select();
        if (!$res) {
            throw new BaseException(['code' => 400, 'msg' => '姓名或学号不存在']);
        }
        $uids = [];
        foreach ($res as $r) {
            $uid = $r['uid'];
            array_push($uids, $uid);
        }
        var_dump($res);
        $vars = []; //结果数组
        foreach ($uids as $u) {
            $res = Db::table('score')
                ->where(['uid' => $u])
                ->select();
            if ($res) {
                array_push($vars, $res);
            }
        }
        return $vars;
    }

    public function updateStudentScore($uid)
    {
        $score = 0;
        $ask_leave = 0;
        $presence = 0;
        $absence = 0;
        $late = 0;
        $early_leave = 0;

        $year = "20192020";
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

    public function clearAdminOpenid($number)
    {
        Db::table('admin')
            ->where('number', '=', $number)
            ->update(['openid' => null]);
    }

    public function clearStudentOpenid($number)
    {
        Db::table('student')
            ->where('number', '=', $number)
            ->update(['openid' => null]);
    }

}