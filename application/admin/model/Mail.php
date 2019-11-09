<?php


namespace app\admin\model;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use think\Db;

class Mail
{

    public function sendMail($address, $content)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = "UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.126.com';                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = 'gwszxs@126.com';                // SMTP 用户名  即邮箱的用户名
            $mail->Password = 'a36207022';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom('gwszxs@126.com', '广外思政学时');  //发件人
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容

            //处理收件人
            foreach ($address as $a) {
                $mail->addAddress($a);
                echo $a;
            }

            //处理内容
            $mail->Subject = $content['subject'];
            $mail->Body = $content['body'];

//            $mail->Subject = '这里是邮件标题' . time();
//            $mail->Body    = '<h1>这里是邮件内容</h1>' . date('Y-m-d H:i:s');
            $mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';

            $mail->send();
            echo '邮件发送成功';
        } catch (Exception $e) {
            echo '邮件发送失败: ', $mail->ErrorInfo;
        }
    }

    public function newMeetingAssessNotice($meeting_id)
    {
        //获取会议信息
        $meeting = (new MeetingPendingAssess())->getMeetingInfo($meeting_id);
        $department = $meeting['department'];
        $name = $meeting['name'];
        $time = strtotime($meeting['date']);
        $date1 = date('Y', $time);
        $date2 = date('m', $time);
        $date3 = date('d', $time);
        $time1 = date('H', $time);
        $time2 = date('i', $time);
        $position = $meeting['position'];
        $score = $meeting['score'];

        $current_time = date("Y-m-d H:i:s");

        //获取一级管理员邮箱
        $admins = Db::table('admin')
            ->where('level', '=', 1)
            ->field('email')
            ->select();
        //编辑内容
        $content['subject'] = "新活动审核通知";
        $content['body'] = "<body>
        <center>思政学时活动审核提醒</center><br>尊敬的管理员：<br><p style=\"width: 7px;\"></p>" . $department . "将举办“$name
        活动。本次活动详情如下：<br><p style=\"width: 7px;\"></p>时间：$date1 年$date2 月$date3 日$time1:$time2.
        <br><p style=\"width: 7px;\"></p>地点：$position<br><p style=\"width: 7px;\"></p>学时：$score
        个<br>请通过思政学时小程序或网页端进行审核。<br><p style=\"width: 7px;\"></p>
        <p>$current_time</p></body>";

        //发送邮件
        $this->sendMail($admins, $content);
    }

    public function newMeetingNotice($meeting_id)
    {
        //获取会议信息
        $meeting = (new Meeting())->getMeetingInfo($meeting_id);
        $department = $meeting['department'];
        $name = $meeting['name'];
        $time = strtotime($meeting['date']);
        $date1 = date('Y', $time);
        $date2 = date('m', $time);
        $date3 = date('d', $time);
        $time1 = date('H', $time);
        $time2 = date('i', $time);
        $position = $meeting['position'];
        $score = $meeting['score'];
        //获取学生邮箱
        $students = Db::table('student')
            ->where('allow_notice', '=', 1)
            ->field('email')
            ->select();
        var_dump($students);
        $current_time = date("Y-m-d H:i:s");
//        $students = ['20182002872'];
        //编辑内容
        $content['subject'] = "新活动发布通知";
        $content['body'] = "<body>
        <center>思政学时活动更新提醒</center><br>各位同学：<br><p style=\"width: 7px;\"></p>" . $department . "将举办“$name
        活动。本次活动详情如下：<br><p style=\"width: 7px;\"></p>时间：$date1 年$date2 月$date3 日$time1:$time2.
        <br><p style=\"width: 7px;\"></p>地点：$position<br><p style=\"width: 7px;\"></p>学时：$score
        个<br>本次活动报名已经开始，各位同学可通过 “广外学生处”微信公众号，点击下方“学生服务”—“思政学时”进入报名页面，或直接登录“广外思政学时”微信小程序进行报名！<br><p style=\"width: 7px;\"></p>欢迎同学们的参与，谢谢！
        <p>$current_time</p></body>";
        //发送邮件
        $this->sendMail($students, $content);
    }

    public function meeting24Notice($meeting_id)
    {
        //后台发送
        ignore_user_abort(true);
        set_time_limit(0);
        header('HTTP/1.1 200 OK');
        header('Content-Length:0');
        header('Connection:Close');
        flush();

//        $meeting = Db::table('meeting')
//            ->where('id', '=', $meeting_id)
//            ->field('date')
//            ->find();
//        $time = date($meeting);
//        $during_time = $time-time();

        sleep(60*60*2);

        $this->newMeetingNotice($meeting_id);
    }

    public function sendNotice($content, $recipient)
    {
        //后台发送
        ignore_user_abort(true);
        set_time_limit(0);
        header('HTTP/1.1 200 OK');
        header('Content-Length:0');
        header('Connection:Close');
        flush();


    }


}