<?php /** @noinspection ALL */

namespace app\admin\controller;

use think\Controller;
use app\admin\controller\MyController;
use app\admin\exception;
use app\admin\model;
use think\Cache;
use app\admin\extra;

/**
 * Class Studentarrange
 * @package app\admin\controller
 * 学生管理控制器
 */
//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class Student extends MyController
{
    public function StudentArrange()
    {

        $post = input('post.');
        //获取type
        $type = $post['type'];
        //获取data
        $data = $post['data'];
        $tokenModel = new model\Token();
        $StudentArrangeModel = new model\StudentArrange();
        $meetingModel = new model\Meeting();

        /***
         * 通过搜索学生姓名或学号来查找学生，
         * 可通过筛选学院、年级（2019、2018、2017、2016）来查看学生状态；
         * 通过选择学年（包含2018-2019学年、2019-2020学年）查看学时和缺勤数
         * 注意：当通过搜索学生姓名和学号来查找时筛选功能失效，只有当学生姓名和学号两项填空''时，筛选功能有效。
         * @param $post
         * @param name 姓名
         * @param number 学号
         * @param major 学院 major
         * @param year 年级 如果是2018级 填2018
         * @param term 学年 ：2019-2020 则填 20192020
         * @return data[] 返回姓名，学号，学院，学时和缺勤数
         */
        if ($type == 'A101') {
            //获取学生信息，学时和缺勤数
            $StudentInfo = $StudentArrangeModel->getScoreAndAbsence($post);
            //返回JSON
            return $this->renderSuccessData('success', $StudentInfo);
        } /***
         * 获取该学生的学生信息：学时和出席数 缺席数 迟到数 早退数 请假数 和该学年的每场会议信息与出勤情况
         * 点击某学生行，跳转到某学生详情页
         * 可筛选学年（包含2018-2019学年、2019-2020学年）查看此学生活动出勤状况
         * 返回信息：姓名 学院 学号 出席数 缺席数 迟到数 早退数 请假数 学期
         * 返回各会议信息[]： 会议id（前端不显示），讲座名称， 地点，日期，出勤状况
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
         * @return data['meeting'][0]['meeting_id'] 会议id
         * @return data['meeting'][0]['meeting_name'] 会议名
         * @return data['meeting'][0]['position'] 会议地点
         * @return data['meeting'][0]['date'] 会议日期
         * @return data['meeting'][0]['status'] 出席状态
         */
        else if ($type == 'A102') {
            $data = $post['data'];
            //如果term填了全部 就返回全部学年（从今到2018年）的统计和会议信息
            if ($data['term'] == '全部') {
                $i = date('Y');
                $k = 0;
                //获取全部学年（从今到2018年）的统计和会议信息
                while ($i != 2017) {
                    $info = $StudentArrangeModel->MeetingInfo($data['number'], $i . $i + 1);
                    $MeetingInfo[$k] = $info;
                    $i = $i - 1;
                    $k = $k + 1;
                }
            }//if
            else {
                $MeetingInfo = $StudentArrangeModel->MeetingInfo($data['number'], $data['term']);
            }
            //返回JSON
            return $this->renderSuccessData('success', $MeetingInfo);
        } /***
         * 进行单个学生多场活动批量编辑，点击“编辑”按钮跳转编辑页面，同出勤名单编辑交互
         * 修改出勤名单
         * @param $post []
         * @param number 学号
         * @param update[] $post ['data']['update'] 要修改的对象信息 array
         * @param meeting_id update[0]['number'] 要修改的会议id
         * @param status update[0]['status'] 要修改成什么状态 ：1 出席，2 缺席，3 迟到，4 早退，5 请假
         */
        else if ($type == 'A103') {
            //修改出勤名单
            (new model\StudentArrange())->updateAttend($post);
            //返回成功JSON
            return $this->renderSuccess('修改成功');
        } /***
         * 重置学生密码
         * @param $post
         * @param number 学号
         */
        else if ($type == 'A104') {
            //重置学生密码
            $StudentArrangeModel->ResetPassword($post);
            //返回JSON
            return $this->renderSuccess('success');
        }
    }
}
