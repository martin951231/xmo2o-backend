<?php

class Community_mailModel extends Model{
    public function __construct(){
        parent::__construct();

        $smtp_host = C('config.mail_config_smtp_host');
        if(!$smtp_host){
            return;
        }

    }

    public function send($to, $subject = '', $body = '', $attachment = null){
        $config = array(
            'SMTP_HOST' => C('config.mail_config_smtp_host'),
            'SMTP_PORT' => 25,
            'SMTP_USER' => C('config.mail_config_smtp_username'),
            'SMTP_PASS' => C('config.mail_config_smtp_password'),
        );

        require_once APP_PATH . 'Lib/ORG/PHPMailer/class.phpmailer.php';

        $mail = new PHPMailer(); //实例化  
//        $mail->IsSMTP(); // 启用SMTP
        $mail->Mailer = 'SMTP'; // 启用SMTP
        $mail->Host = $config['SMTP_HOST']; //smtp服务器的名称（这里以QQ邮箱为例）  
        $mail->SMTPAuth = true; //启用smtp认证  
        $mail->Username = $config['SMTP_USER']; //你的邮箱名  
        $mail->Password = $config['SMTP_PASS'] ; //邮箱密码  
        $mail->From = $config['SMTP_USER']; //发件人地址（也就是你的邮箱地址）  
        $mail->FromName = $config['SMTP_USER']; //发件人姓名  
        $mail->AddAddress($to);
        $mail->WordWrap = 50; //设置每行字符长度  
        $mail->IsHTML(true); // 是否HTML格式邮件  
        $mail->CharSet= 'utf-8'; //设置邮件编码  
        $mail->Subject = $subject; //邮件主题  
        $mail->Body = $body; //邮件内容
        if(is_array($attachment)){ // 添加附件
            foreach ($attachment as $file){
                is_file($file) && $mail->AddAttachment($file);
            }
        }
        // $mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端"; //邮件正文不支持HTML的备用显示  
        return $mail->Send() ? true : $mail->ErrorInfo;
    }
}
?>