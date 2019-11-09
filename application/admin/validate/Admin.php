<?php
/**
 * Created by PhpStorm.
 * Student: leslie
 * Date: 2019/9/3
 * Time: 10:41
 */

namespace app\admin\validate;


use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'number' => 'alphaNum',
        'password' => 'alphaDash',
        'content' => 'chsDash'
    ];

    protected $message = [
        'number.number' => '工号只能为数字或字母',
        'password.alphaDash' => '密码只能为字母数字或下划线',
        'content.chsDash' => '内容含有非法字符'
    ];

    protected $scene = [
        'content' => ['content']
    ];
}