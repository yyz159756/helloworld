<?php

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Model;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Db;

class Student extends Model
{
    protected $name = 'student';

    /**
     * 通过uid获取用户信息 uid可以是主键也可以是学号
     * @param $uid
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($uid)
    {

        $res = db('student')
            ->where('uid', '=', $uid)
            ->select();
        //如果不是uid，用学号去找
        if (!$res) {
            $res = db('student')
                ->where('number', '=', $uid)
                ->select();
            if (!$res) {
                //如果用学号也找不到就return 0
                return 0;
            }
        }
        if ($res) {
            $data['uid'] = $res[0]['uid'];
            $data['username'] = $res[0]['username'];
            $data['major'] = $res[0]['major'];
            $data['number'] = $res[0]['number'];
            $data['email'] = $res[0]['email'];

        }
        return $data;
    }

    public function getInfo_bynumber($number)
    {

        //如果不是uid，用学号去找

        $res = db('student')
            ->where('number', '=', $number)
            ->find();
        if (!$res) {
            //如果用学号也找不到就return 0
            return 0;
        }
        $data['uid'] = $res['uid'];
        $data['username'] = $res['username'];
        $data['major'] = $res['major'];
        $data['number'] = $res['number'];
        $data['email'] = $res['email'];


        return $data;
    }
}
