<?php

namespace app\admin\model;

use think\Model;
use app\admin\exception\BaseException;

/***
 * 评论model
 * Class Comment
 * @package app\admin\model
 */
class Comment extends Model
{
    protected $name = 'comment';

    public function userInfo()
    {
        return $this->hasMany('student', 'uid', 'uid');
    }

    /**
     * 查看评论
     * @param $post
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ShowComment($post)
    {

        $data = $post['data'];
        $meeting_id = $data['meeting_id'];

        //type为1时查看打星评价
        $res = self::field('uid, content, holder, text')
            ->with(['userInfo' => function ($query) {
                $query->field('uid, username, major');//注意field要包含主键
            }])
            ->where('meeting_id', '=', $meeting_id)
            ->select();

        if (!$res) {
            throw new BaseException(['code' => 411, 'msg' => '找不到会议评论！']);
        }//if

        return $res;
    }
}
