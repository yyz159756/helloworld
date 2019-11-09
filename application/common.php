<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function getRandChars($length){
    /**
     * 获取指定位数的随机字符串
     * @return string str
     */
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    //从中间抽出字符串加length次
    for ($i = 0; $i < $length; $i++){
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

// TODO curl部分来自2.0代码，未验证
function curl_get($url, $httpCode = 0) {
//    初始化
    $ch = curl_init();
//    爬取url地址
    curl_setopt($ch, CURLOPT_URL, $url);
//    不将爬取内容直接输出而保存到变量中
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //部署在Linux环境下改为true
//    模拟一个浏览器访问https网站
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//    设定连接时间
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    //执行获取内容
    $file_contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}

function curl_post($url, $param) {
////    初始化
//    $ch = curl_init();
////    爬取url地址
//    curl_setopt($ch, CURLOPT_URL, $url);
////    不将爬取内容直接输出而保存到变量中
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    //设定为post
//    curl_setopt($ch, CURLOPT_PORT,1);
//
//    //post的数据
//    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//
//    //部署在Linux环境下改为true
////    模拟一个浏览器访问https网站
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
////    设定连接时间
//    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
//
//    //执行获取内容
//    $file_contents = curl_exec($ch);
//    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//    curl_close($ch);
//    return $file_contents;
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch); //运行curl
    curl_close($ch);
    return $data;
}

function password_encrypt($password)
{
    /**
     * 获得加密的密码
     * 加密规则：md5(salt+password)
     * @param string password 未加密的密码
     * @return string password_encrypted 加密后的密码
     */
    $password_encrypted = md5(config('setting.salt').$password);
    return $password_encrypted;
}

function get_key()
{
    //用三组字符串md5加密
    //32个字符组成一组随机字符串
    $randChars = getRandChars(32);
    //时间戳
    $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
    //salt 盐
    $salt = config('setting.salt');

    $key = md5($randChars.$timestamp.$salt);

    return $key;
}

function getYear()
{
    $current_year = date('Y');
    $year = ($current_year) . '-' . ($current_year - 1);
    return $year;
}