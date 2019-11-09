<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/8/9
 * Time: 11:00
 */

namespace app\student\model;

use app\student\exception\BaseException;

class Comment extends BaseModel
{
    protected $name = 'comment';
    protected $pk = 'id';

    public function star_meeting($data)
    {
        $res = $this->insert(['meeting_id' => $data['meeting_id'], 'uid' => $data['uid'],
            'content' => $data['content'], 'holder' => $data['holder'], 'text' => $data['text'], 'time' => date('Y-m-d H:i:s', time())]);
        if (!$res) {
            throw new BaseException('评价失败');
        }
        return 1;
    }
}