<?php
/**
 * Created by PhpStorm.
 * student: leslie
 * Date: 2019/7/27
 * Time: 10:58
 */

namespace app\student\controller;

use think\Controller;

class BaseController extends Controller
{
    /**
     * API控制器基类
     * Class BaseController
     * @package app\controller
     */

    const JSON_SUCCESS_STATUS = 200;
    const JSON_ERROR_STATUS = 400;

    /**
     * 返回封装后的 API 数据到客户端
     * @param int $code
     * @param string $msg
     */
    protected function renderJson($code = self::JSON_SUCCESS_STATUS, $msg = '')
    {
        exit(json_encode([
            'code' => $code,
            'msg' => $msg
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 返回操作成功json
     * @param string $msg
     * @return array
     */
    protected function renderSuccess($msg)
    {
        return $this->renderJson(self::JSON_SUCCESS_STATUS, $msg);

    }

    /**
     * 返回带有$data的json数据 给客户端
     * @param $msg
     * @param array $data
     */
    protected function renderSuccessData($msg, $data = [])
    {
        exit(json_encode([
            'code' => 200,
            'msg' => $msg,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 返回操作失败json
     * @param string $msg
     */
    protected function renderError($code, $msg = 'error')
    {
        exit(json_encode([
            'code' => $code,
            'msg' => $msg
        ], JSON_UNESCAPED_UNICODE));
    }

}