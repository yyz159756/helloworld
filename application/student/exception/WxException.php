<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/25
 * Time: 17:06
 */

namespace app\student\exception;


class WxException extends BaseException
{
    public $code = 405;
    public $msg = '微信服务器接口调用失败';
}