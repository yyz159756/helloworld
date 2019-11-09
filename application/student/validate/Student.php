<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/6
 * Time: 23:46
 */

namespace app\student\validate;

use think\Validate;

class Student extends Validate
{
    protected $rule = [
        'number' => 'require|number',
        'password' => 'require|alphaDash',
        'email' => 'require|email',
        'year' => 'require|alphaDash',
        'meeting_id' => 'number'
    ];

    protected $message = [
        'number.require' => 'uid必需',
        'number.number' => 'uid只能为数字',
        'password.require' => '密码必需',
        'password.alphaDash' => '密码只能为字母数字和下划线',
        'email.require' => '邮箱必需',
        'email.email' => '邮箱格式错误',
        'year.require' => '学年必需',
        'year.alphaDash' => '学年值只能为数字和下划线',
        'meeting_id.number' => 'id必须为数字'
    ];

    protected $scene = [
        'login' => ['number', 'password'],
        'email' => ['uid', 'email'],
        'score' => ['year'],
        'check_email' => ['uid'],
        'meeting_id' => ['meeting_id']
    ];
}