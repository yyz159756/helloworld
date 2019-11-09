<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/15
 * Time: 14:18
 */

namespace app\student\model;

use app\admin\model\MeetingPendingAssess;
use app\student\exception\BaseException;
use think\cache;
use think\db;
use think\exception\DbException;

class Meeting extends BaseModel
{
    //指定表名
    protected $name = 'meeting';
    //指定主键
    protected $pk = 'id';


    /**
     * 会议签到
     * @param $data
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function meeting_checkin($data)
    {
        //校验二维码是否有效
        $code_valid = Cache::get($data['code_id']);
        if (empty($code_valid) or $code_valid != 1) {
            throw new BaseException(['msg' => '二维码无效']);
        }
        $res = Db::table('meeting_member')
            ->where(['meeting_id' => $data['meeting_id'], 'uid' => $data['uid']])
            ->update(['checkin' => 1]);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => '签到失败']);
        }
    }

    /**
     * 会议签退
     * @param int uid $data['uid']
     * @throws DbException
     * @throws BaseException
     * @throws \think\Exception
     */
    public function meeting_checkout($data)
    {
        //校验二维码是否有效
        $code_valid = Cache::get($data['code_id']);
        if (empty($code_valid) or $code_valid != 2) {
            throw new BaseException(['msg' => '二维码无效']);
        }
        Db::startTrans();
        try {
            Db::table('meeting_member')
                ->where(['meeting_id' => $data['meeting_id'], 'uid' => $data['uid']])
                ->update(['checkout' => 1]);//签退
            $res = Db::table('meeting_member')
                ->where(['meeting_id' => $data['meeting_id'], 'uid' => $data['uid']])
                ->find();//迟到 即是否有签到
            $is_late = !$res['checkin'];
            $year = getYear();
            if ($is_late) {
                //迟到
                Db::table('score')
                    ->where(['uid' => $data['uid'], 'year' => $year])
                    ->setInc('late');//迟到次数+1
            }
            Db::table('score')
                ->where(['uid' => $data['uid'], 'year' => $year])
                ->inc('presence')//出席次数+1
                ->inc('score', $res['score'])//加分
                ->update();
        } catch (DbException $e) {
            Db::rollback();
            throw new BaseException(['code' => 401, 'msg' => '签退失败']);
        }
        Db::commit();


    }

    /**
     * 活动报名
     * 此处io压力较小不用redis了
     * @param int uid
     * @return void res
     * @throws BaseException
     * @throws \think\Exception
     * @throws DbException
     * @throws \think\exception\PDOException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function registerMeeting($data)
    {
        //先查是否满
        $meeting = Db::table('meeting')
            ->where(['id' => $data['meeting_id']])
            ->find();
        if ($meeting['capacity'] <= $meeting['signed']) {
            //报名已满
            throw new BaseException(['code' => 401, 'msg' => '报名已满']);
        }
        //检查是否已过报名时间
        $start_time = strtotime($meeting['signup_start_time']);
        $end_time = strtotime($meeting['signup_end_time']);
        $current_time = time();
        if ($current_time < $start_time or $current_time > $end_time) {
            throw new BaseException(['code' => 401, 'msg' => '当前不在可报名时间内']);
        }
        $res = Db::table('meeting_member')
            ->where(['uid' => $data['uid'], 'meeting_id' => $data['meeting_id']])
            ->find();
        if ($res) {
            //已经报名
            throw new BaseException(['code' => 401, 'msg' => '已经报名']);
        }
        Db::startTrans();
        try {
            $score = Db::table('meeting')
                ->where(['id' => $data['meeting_id']])
                ->field('score')
                ->find();
            $score = $score['score'];
            Db::table('meeting_member')
                ->insert(['uid' => $data['uid'], 'meeting_id' => $data['meeting_id'], 'score' => $score]);
            //meeting表signed+1
            Db::table('meeting')
                ->where('id', '=', $data['meeting_id'])
                ->inc('signed')
                ->update();
//            // TODO 报名时获取formid存进数据库
//            $openid = (new Student())->getOpenidByUid($data['uid']);
//            Db::table('wechat_message')
//                ->insert(['openid' => $openid, 'form_id' => $data['form_id'], 'meeting_id' => $data['meeting_id'], 'create_time' => time()]);
        } catch (DbException $e) {
            Db::rollback();
            throw new BaseException(['code' => 401, 'msg' => '报名失败']);
        }
        Db::commit();
    }

    /**
     * 活动请假
     * @param int uid
     * @throws BaseException
     * @throws DbException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @throws db\exception\DataNotFoundException
     */
    public function meeting_ask_leave($data)
    {
        Db::startTrans();
        try {
            $meeting = Db::table('meeting')
                ->where(['id' => $data['meeting_id']])
                ->find(); //会议信息
            $is_ask_leave = Db::table('meeting_member')
                ->where(['uid' => $data['uid'], 'meeting_id' => $data['meeting_id']])
                ->field('ask_leave')
                ->find(); //是否请假
            if ($is_ask_leave['ask_leave'] != 0) {
                throw new BaseException(['code' => 401, 'msg' => '已经请假']); //已经请假
            }
            Db::table('meeting_member')
                ->where(['uid' => $data['uid'], 'meeting_id' => $data['meeting_id']])
                ->update(['ask_leave' => 1]); //修改请假情况
            $signed = $meeting['signed'] - 1; //已报名数-1
            Db::table('meeting')
                ->where(['id' => $data['meeting_id']])
                ->update(['signed' => $signed]); //更新已报名情况
            $res = Db::table('score')
                ->where(['uid' => $data['uid']])
                ->find(); //查现有分数
            $ask_leave = $res['ask_leave'] + 1; //请假数+1
            $year = getYear();
            //检查是否开始前24小时内请假
            $start_time = strtotime($meeting['begin_time']);
            if ($start_time - time() <= 86400) {
                //已经24小时内了要扣分
                $score = $res['score'] - 0.5; //扣分
                Db::table('score')
                    ->where(['uid' => $data['uid'], 'year' => $year])
                    ->update(['score' => $score, 'ask_leave' => $ask_leave]); //修改分数和请假数
            }
            Db::table('score')
                ->where(['uid' => $data['uid'], 'year' => $year])
                ->update(['ask_leave' => $ask_leave]);
        } catch (DbException $e) {
            Db::rollback();
            throw new BaseException(['code' => 401, 'msg' => '请假失败']);
        }
        Db::commit();
    }

    /**
     * 活动查看
     * @param int meeting_id
     * @return array|int
     * @throws BaseException
     * @throws DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function get_single_meeting($data)
    {
        //先查redis
        $res = cache::get($data['meeting_id']);
        if (!$res) {
            $res = Db::table('meeting')
                ->where(['id' => $data['meeting_id']])
                ->find();
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '获取错误']);
            }
        }
        return $res;
    }

    public function get_meeting_list()
    {
        /**
         * 查看活动列表
         * @param
         */
        // TODO 此处的逻辑？
        $res = Meeting::all();
        return $res;

    }

    /**
     * 已报名活动查看
     * @param int uid
     * @return array res
     * @throws BaseException
     * @throws DbException
     */
    public function get_signed_meeting_list($data)
    {
        $res = MeetingMember::all(['uid' => $data['uid']]);
        if (!$res) {
            throw new BaseException(['code' => 400, 'msg' => '没有报名任何活动']); //没有报名任何活动
        }
        return $res;
    }

    public function get_finished_meeting_list($data)
    {
        /**
         * 已结束活动查看
         * @param int uid
         * @return array res
         */
        //活动结束逻辑？
        $res = Meeting::all(['uid' => $data['uid']]);
    }

    public function getMeetingInfo($meeting_id)
    {
        $res = self::get(['id' => $meeting_id]);
        if (!$res) return 0;
        return $res;
    }

    public function get_meeting_list_with_info($uid)
    {
        //获取学生年级和专业
        $student = (new Student())->get_info_by_uid(['uid' => $uid]);
        $major = $student['major'];
        $year = substr($student['number'], 0, 4);

        $meeting_ids = Db::table('meeting_major')
            ->where('major', 'like', $major)
            ->where('year', '=', $year)
            ->field('meeting_id')
            ->select();
        if (!$meeting_ids) {
            throw new BaseException(['code' => 401, 'msg' => '没有可参加的会议']);
        }
        $meetings = [];
        foreach ($meeting_ids as $meeting_id) {
            $meeting = $this->getMeetingInfo($meeting_id['meeting_id']);
            if (!$meeting) continue;
            //逐个查询状态
            $state = (new MeetingMember())->getStudentStatus($meeting['id'], $uid);
            //修改数组中的state（学生参加的状态）
            $meeting['state'] = $state;
            array_push($meetings, $meeting);
        }
        return $meetings;
    }

}