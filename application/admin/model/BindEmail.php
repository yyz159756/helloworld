<?php /** @noinspection ALL */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Cache;
use think\Model;

/***
 * 绑定邮箱model
 * Class BindEmail
 * @package app\admin\model
 */
class BindEmail extends Model
{

    /***
     * 发送邮件 发送绑定邮箱的code
     * @param $username
     * @param $code
     * @param $email
     * @return array|bool
     * @throws \phpmailerException
     */
    public function send_email($username,$code,$email){
        vendor('PHPMailer.class#smtp');
        vendor('PHPMailer.class#phpmailer');
        $mail = new \PHPMailer(); //实例化
        // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = 'smtp.163.com';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->FromName = '后台管理';
        $mail->Username = 'soojustphp@163.com';
        $mail->Password = 'as123456';
        $mail->From = 'soojustphp@163.com';
        $mail->isHTML(true);
        $mail->addAddress($email);
        $mail->Subject = '绑定邮箱';
        $mail->Body = "尊敬的<strong>{$username}</strong>您好:
       您的激活码为<font color='red'>{$code}</font>,请将激活码输入进行验证! 激活码有效期为6分钟^_^";

        if(!$mail->Send())
        {
            return array('status'=>2,'info'=>$mail->ErrorInfo);
        } else {
            return array('status'=>1,'info'=>'发送成功');
        }

    }

    /**
     * 发送邮箱激活码, 生成验证码保存到cache中，返回bind_email_token
     * @param $post
     * @return null|string
     * @throws BaseException
     * @throws \phpmailerException
     */
    public function sendActivationCode($post)
    {
        $token = $post['token'];
        $email = $post['data']['email'];
        try{
            //获取number
            $admin_info = (new Token())->getContent($token);
            $number = $admin_info['number'];
        }
        catch (\Exception $e){
            echo $e;
        }
        //生成bind_email_token
        $bind_email_token = (new Token())->getRandChars(32);
        //生成激活code
        $activation_code = rand(1000, 9999);
        //发送邮件
        $res = $this->send_email($number,$activation_code,$email);
        if($res['status'] == 1) {
            //存入cache
            cache($bind_email_token, compact('activation_time', 'activation_code'), 60 * 6);
        }
        else{
            throw new BaseException(['code' => 411, 'msg' => '发送失败,原因:'.$res['info']]);
        }
        return $bind_email_token;

    }

    /**
     * 验证并绑定邮箱
     * @param $post
     * @param $bind_email_token
     * @param $email
     * @param $code
     * @throws BaseException
     */
    public function bindEmail($post) {
        $token = $post['token'];
//        $bind_email_token = $post['data']['bind_email_token'];
        $email = $post['data']['email'];
//        $code = $post['data']['code'];
        try{
            //获取number
            $admin_info = (new Token())->getContent($token);
            $admin_id = $admin_info['id'];
        }
        catch (\Exception $e){
            echo $e;
        }
//        //验证是否过期
//
//        $vars = Cache::get($bind_email_token);
//        if(!$vars){
//            throw new BaseException(['code' => 411, 'msg' => '绑定code已失效！']);
//        }
//        //验证code是否一致
//        if($code != $vars['activation_code']){
//            throw new BaseException(['code' => 412, 'msg' => '绑定激活码错误！']);
//        }
        //绑定邮箱
        $res = db('admin')->where('id','=',$admin_id)
            ->update(['email' => $email]);

        if(!$res){
            throw new BaseException(['code' => 413, 'msg' => '绑定失败！']);
        }
//        //销毁缓存
//        Cache::rm($bind_email_token);



    }
}
