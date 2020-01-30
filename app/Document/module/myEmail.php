<?PHP
myException::init(array(
    'log_type' => E_ALL ^ E_NOTICE, 
    'callback_type' => E_ERROR|E_CORE_ERROR|E_CORE_WARNING|E_USER_ERROR, 
    'exit_on_error' => true
));

$mail = new myEmail();

$email = 'sunkai@cccfna.org.cn';

$server_1 = [
    'mode'=>'smtp', 
    'host'=>'smtpx.sina.net', 
    'port'=>25, 
    'user'=>$email, 
    'password'=>'Mygod1978'
];

// https://www.google.com/settings/security/lesssecureapps
$server_2 = [
    'mode'=>'ssl', 
    'host'=>'smtp.gmail.com', 
    'port'=>465, 
    'user'=>'windy2006@gmail.com', 
    'password'=>'mygod2000'
];

$mail->init($email, 'utf-8');
$mail->from($email, 'test');
$mail->subject('邮件测试');
$mail->content('<b>邮件内容</b><img src="logo.png">', true);
$mail->to([
    'cccfna@cccfna.org.cn', 
    '孙凯' => 'sunkai@cccfna.org.cn', 
]);
$mail->cc('windy_sk@126.com', '孙凯');
$mail->bcc('flyhorses@sina.com', '孙凯');
$mail->file(PATH.'data/file/utf8.txt', 'xxx.txt');
$mail->file(PATH.'data/image/logo.png', null, true);
$mail->header('Disposition-Notification-To', $email);
$result = $mail->send($server_1, false, 1);

echo '<pre>';
var_dump($result);
echo '</pre>';
