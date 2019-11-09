<?php

namespace app\admin\model;
use app\admin\exception\BaseException;
use think\Model;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Db;


class MeetingMajor extends Model
{
    protected $name = 'meeting_major';
    protected $hidden = ['id', 'meeting_id'];

    public function insert(array $data = [], $replace = false, $getLastInsID = false, $sequence = null)
    {
        return parent::insert($data, $replace, $getLastInsID, $sequence); // TODO: Change the autogenerated stub
    }
}
