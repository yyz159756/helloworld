<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/25
 * Time: 17:05
 */

namespace app\student\exception;

use think\Exception;
class BaseException extends Exception
{
    public $code = 400;
    public $msg = '参数错误';

    public function __construct($params = []){
        if (!is_array($params)){
            return;
        }

        if (array_key_exists('code',$params)){
            $this->code = $params['code'];
        }

        if (array_key_exists('msg',$params)){
            $this->msg = $params['msg'];
        }
    }
}