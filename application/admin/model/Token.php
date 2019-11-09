<?php /** @noinspection ALL */


namespace app\admin\model;
use app\admin\exception\BaseException;
use think\Cache;
use think\Exception;
use think\Model;
class Token extends Model
{

    public function getRandChars($length){
        //根据length生成对应位数的随机字符串
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        //从中间抽出字符串加length次
        for ($i = 0; $i < $length; $i++){
            $str .= $strPol[rand(0, $max)];
        }

        return $str;
    }

    public function getToken(){
        //用三组字符串md5加密
        //32个字符组成一组随机字符串
        $randChars = Token::getRandChars(32);
        //时间戳
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = "Quanta";

        $key = md5($randChars.$timestamp.$salt);

        return $key;
    }


    //获取缓存内容
    public function getContent($token){
        $vars = Cache::get($token);
        if (!$vars){
            throw new BaseException(['code'=>401,'msg'=>'Token已经过期或无效Token！']);
        }
        else{
            return $vars;
        }
    }


}