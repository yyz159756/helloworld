<?php /** @noinspection ALL */

namespace app\admin\model;
use app\admin\exception\BaseException;
use think\Model;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Db;

/***
 * 会议成员model
 * Class MeetingMember
 * @package app\admin\model
 */
class MeetingMember extends Model
{
    protected $name = 'meeting_member';


    public function userInfo(){
        return $this->hasMany('student','uid','uid');
    }

    /**
     * 获取报名名单
     * @param $post
     * @param meeting_id
     * @param type 类型 1：全部 ，2：只看请假学生， 3：只看不请假学生
     */
    public function signUpList($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        $type = $post['data']['type'];
        //如果type类型为1的话返回全部报名的学生
        if($type == '1')
        {
            $res = self::field('uid, ask_leave')
                ->with(['userInfo' => function($query){
                    $query->field('uid, username, number, major');
                }])
                ->where('meeting_id','=',$meeting_id)
                ->select();
            if(!$res){
                throw new BaseException(['code' => 411, 'msg' => '未找到结果']);
            }//if
            $res = collection($res)->toArray();

            //请假人数
            $ask_leave_num = self::where('meeting_id','=', $meeting_id)
                ->where('ask_leave','=', 1)
                ->count();
            //报名人数
            $total_num = self::where('meeting_id','=', $meeting_id)->count();
            //签到人数
            $checkin_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkin','=', 1)
                ->count();
            //签退人数
            $checkout_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkout','=', 1)
                ->count();
            $Data['info'] = $res;
            $num_info['checkout_num'] = $checkout_num;
            $num_info['ask_leave_num'] = $ask_leave_num;
            $num_info['total_num'] = $total_num;
            $num_info['checkin_num'] = $checkin_num;
            $Data['num_info'] = $num_info;



            return $Data;
        }
        else if($type == '2')
        {
            //type为2 查看请假学生
            $res = self::field('uid, ask_leave')
                ->with(['userInfo' => function($query){
                    $query->field('uid, username, number, major');
                }])
                ->where('meeting_id','=',$meeting_id)
                ->where('ask_leave','=',1)
                ->select();

            if(!$res){
                throw new BaseException(['code' => 411, 'msg' => '未找到结果']);
            }
            $res = collection($res)->toArray();
            //请假人数
            $ask_leave_num = self::where('meeting_id','=', $meeting_id)
                ->where('ask_leave','=', 1)
                ->count();
            //报名人数
            $total_num = self::where('meeting_id','=', $meeting_id)->count();
            //签到人数
            $checkin_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkin','=', 1)
                ->count();
            //签退人数
            $checkout_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkout','=', 1)
                ->count();
            $Data['info'] = $res;
            $num_info['checkout_num'] = $checkout_num;
            $num_info['ask_leave_num'] = $ask_leave_num;
            $num_info['total_num'] = $total_num;
            $num_info['checkin_num'] = $checkin_num;
            $Data['num_info'] = $num_info;

            return $Data;
        }
        else if($type == '3')
        {
            //type为3 查看不请假学生
            $res = self::field('uid, ask_leave')
                ->with(['userInfo' => function($query){
                    $query->field('uid, username, number, major');
                }])
                ->where('meeting_id','=',$meeting_id)
                ->where('ask_leave','=',0)
                ->select();
            if(!$res){
                throw new BaseException(['code' => 411, 'msg' => '未找到结果']);
            }
            $res = collection($res)->toArray();
            //请假人数
            $ask_leave_num = self::where('meeting_id','=', $meeting_id)
                ->where('ask_leave','=', 1)
                ->count();
            //报名人数
            $total_num = self::where('meeting_id','=', $meeting_id)->count();
            //签到人数
            $checkin_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkin','=', 1)
                ->count();
            //签退人数
            $checkout_num = self::where('meeting_id','=', $meeting_id)
                ->where('checkout','=', 1)
                ->count();
            $Data['info'] = $res;
            $num_info['checkout_num'] = $checkout_num;
            $num_info['ask_leave_num'] = $ask_leave_num;
            $num_info['total_num'] = $total_num;
            $num_info['checkin_num'] = $checkin_num;
            $Data['num_info'] = $num_info;

            return $Data;
        }

    }

    /**
     * 获取出勤名单
     * @param $post
     * @param meeting_id 会议id
     * @return jsonData[]
     */
    public function AttendList($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        //获取会议成员列表
        $res = self::field('uid, ask_leave, checkin, checkout ')
            ->with(['userInfo' => function($query){
                $query->field('uid, username, number, major');
            }])
            ->where('meeting_id','=',$meeting_id)
            ->select();


        if(!$res){
            throw new BaseException(['code' => 411, 'msg' => '会议id有误']);
        }

        //出席人数
        $attend_num = 0;
        //请假人数
        $ask_leave_num = 0;
        //缺席人数
        $absence_num = 0;
        //早退人数
        $leave_num = 0;
        //迟到人数
        $late_num = 0;
        foreach ($res as $k => $v)
        {
            //请假 status = 0
            if($v['ask_leave'] == '1')
            {
                $res[$k]['status'] = 0;
                $ask_leave_num = $ask_leave_num + 1;
            }
            //出席 status = 1
            else if($v['checkin'] == '1' && $v['checkout'] == '1')
            {
                $res[$k]['status'] = 1;
                $attend_num = $attend_num + 1;
            }
            //缺席 status = -1
            else if($v['checkin'] == '0' && $v['checkout'] == '0' )
            {
                $res[$k]['status'] = -1;
                $absence_num = $absence_num + 1;
            }
            //迟到 status = 2
            else if($v['checkin'] == '0' && $v['checkout'] == '1')
            {
                $res[$k]['status'] = 2;
                $late_num = $late_num + 1;
            }
            //早退 status = 3
            else if($v['checkin'] == '1' && $v['checkout'] == '0')
            {
                $res[$k]['status'] = 3;
                $leave_num = $leave_num + 1;
            }

        }
        $Data['info'] = $res;
        $num_info['attend_num'] = $attend_num;
        $num_info['ask_leave_num'] = $ask_leave_num;
        $num_info['absence_num'] = $absence_num;
        $num_info['late_num'] = $late_num;
        $num_info['leave_num'] = $leave_num;
        $Data['num_info'] = $num_info;
        return $Data;

    }

    /**
     * 修改出勤名单
     * @param $post[]
     * @param meeting_id $post['data']['meeting_id'] 会议id
     * @param update[] $post['data']['update'] 要修改的对象信息 array
     * @param number update[0]['number'] 要修改的对象的学号
     * @param status update[0]['status'] 要修改成什么状态 ：1 出席，2 缺席，3 迟到，4 早退，5 请假
     *
     */
    public function UpdateAttendList($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        $update = $post['data']['update'];
        foreach ($update as $k => $v)
        {
            $number = $v['number'];
            $status = $v['status'];
            //修改成出席
            if($status == '1')
            {
                //获取uid
                $uid = db('student')
                    ->where('number','=',$number)
                    ->value('uid');
                //获取该会议的学时
                $score = db('meeting_pending_assess')
                    ->where('id','=',$meeting_id)
                    ->value('score');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->where('uid','=',$uid)
                    ->update([
                        'checkin' => 1,
                        'checkout' => 1,
                        'ask_leave' => 0,
                        'score' => $score
                    ]);
            }
            //修改成缺席
            else if($status == '2')
            {
                //获取uid
                $uid = db('student')
                    ->where('number','=',$number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->where('uid','=',$uid)
                    ->update([
                        'checkin' => 0,
                        'checkout' => 0,
                        'ask_leave' => 0,
                        'score' => -2,
                    ]);
            }
            //修改成迟到
            else if($status == '3')
            {
                //获取uid
                $uid = db('student')
                    ->where('number','=',$number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->where('uid','=',$uid)
                    ->update([
                        'checkin' => 0,
                        'checkout' => 1,
                        'ask_leave' => 0,
                        'score' => 0,
                    ]);
            }
            //修改成早退
            else if($status == '4')
            {
                //获取uid
                $uid = db('student')
                    ->where('number','=',$number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->where('uid','=',$uid)
                    ->update([
                        'checkin' => 1,
                        'checkout' => 0,
                        'ask_leave' => 0,
                        'score' => 0,
                    ]);
            }
            //修改成请假
            else if($status == '5')
            {
                //获取uid
                $uid = db('student')
                    ->where('number','=',$number)
                    ->value('uid');
                //修改meeting_member表
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->where('uid','=',$uid)
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
