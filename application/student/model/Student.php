<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/2
 * Time: 0:18
 */

namespace app\student\model;

use app\student\exception\BaseException;
use think\Cache;
use think\Db;
use think\exception\DbException;
use think\Paginator;

class Student extends BaseModel
{
    //表名
    protected $name = 'student';
    //指定主键
    protected $pk = 'id';

    /**
     * 通过uid获取学生信息
     * @param int uid
     * @return mixed res
     * @throws DbException
     * @throws BaseException
     */
    public function get_info_by_uid($data)
    {
        $res = Student::get(['uid' => $data['uid']]);
        if ($res) {
            return $res;
        }
        throw new BaseException(['code' => 401, 'msg' => '获取失败']); //uid不存在
    }

    /**
     * 通过学号获取学生信息
     * @param int stuid
     * @return mixed res
     * @throws DbException
     */
    public function get_info_by_stuid($data)
    {
        $res = Student::get(['number' => $data['stuid']]);
        if ($res) {
            return $res;
        }
        return 0; //学号不存在
    }


    public function score_total($data)
    {
        /**
         * 查询学时
         * @param int uid $data['uid']
         * @return $score
         */
        $res = Student::all(['uid' => $data['uid']]);
        if ($res) {
            return $res['score'];
        }
        return 0;
//        $res = Db::table('score')
//            ->where(['uid' => $data['uid'], 'year' => $data['year']])
//            ->field('score')
//            ->find();
//        if (!$res) {
//            return 0;
//        }
//        return $res;
    }

    /**
     * 查询出勤
     * @param int uid $data['uid']
     * @return array $presence
     * $presence: ask_leave, checkin, late_checkin, early_leave, absence
     * @throws BaseException
     * @throws DbException
     */
    public function presence_total($data)
    {

        $res = Student::get(['uid' => $data['uid'], 'year' => $data['year']]);
        if ($res) {
            $presence = [
                'ask_leave' => $res['ask_leave'],
                'checkin' => $res['checkin'],
                'late_checkin' => $res['late_checkin'],
                'early_leave' => $res['early_leave'],
                'absence' => $res['absence']
            ];
            return $presence;
        }
        throw new BaseException(['code' => 401, 'msg' => '查询失败']);
    }

    public function validate_password($data)
    {
        /**
         * 验证密码（user）
         * @param int uid $data['uid']
         * @param string password $data['password'] 密码在传入model前先md5
         * @return int res
         */
        $res = Student::get(['uid' => $data['uid'], 'password' => $data['password']]);
        if ($res) {
            return 1;
        }
        return 0;
//        $res = Db::table('user')
//            ->where(['uid' => $data['uid'], 'password' => $data['password']])
//            ->find();
//        if (!$res) {
//            return 0; //用户不存在或密码错误
//        }
//        return 1;
    }

    public function validate_password_by_number($data)
    {
        $res = Student::get(['number' => $data['number'], 'password' => $data['password']]);
        if (!$res) return 0;
        return 1;
    }

    /**
     * 修改密码（user）
     * @param int uid $data['uid']
     * @param string password_old $data['password_old']
     * @param string password_new $data['password_new']
     * @throws BaseException
     */
    public function edit_password($data)
    {
        $var = [
            'uid' => $data['uid'],
            'password' => $data['password_old']
        ];
        $res = $this->validate_password($var);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => '密码错误']); //密码错误
        }
        $res = $this->where(['uid' => $data['uid']])
            ->update(['password' => $data['password_new']]);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => '修改失败']); //修改失败
        }
    }

    /**
     * 绑定邮箱（user）
     * 用model类重写了逻辑
     * @param int uid $data['uid']
     * @return int res
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function bind_email($data)
    {

//        $res = $this->where(['uid' => $data['uid']])
//            ->update(['email' => $data['email']]);
        $res = Db::table('student')
            ->where(['uid' => $data['uid']])
            ->update(['email' => $data['email']]);
        return $res;
    }

    public function check_email($data)
    {
        /**
         * 查询绑定邮箱（user）
         * 新用户可以直接调用bind_email，而老用户先调用这个查看原本绑定的邮箱
         * @param int uid $data['uid']
         * @return array res
         */
        $res = Student::get(['uid' => $data['uid']]);
        if ($res) {
            return $res['email'];
        }
        return 0;
//        $res = Db::table('user')
//            ->where(['uid' => $data['uid']])
//            ->field('email')
//            ->find();
//        if (!$res) {
//            return 0; //获取失败
//        }
//        return $res;
    }

    public function feedback($data)
    {
        $res = Db::table('feedback')
            ->insert($data);
        if (!$res) {
            return 0;
        }
        return 1;
    }

    public function numberToUid($number)
    {
        $res = Db::table('student')
            ->where(['number' => $number])
            ->find();
        if (!$res) {
            return 0;
        }
        return $res['uid'];
    }

    public function getOpenidByUid($uid)
    {
        $res = Db::table('student')
            ->where(['uid' => $uid])
            ->find();
        if (!$res) {
            return 0;
        }
        return $res['openid'];
    }

}