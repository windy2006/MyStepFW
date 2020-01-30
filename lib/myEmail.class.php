<?php
/********************************************
*                                           *
* Name    : Email Sender                    *
* Modifier: Windy2000                       *
* Time    : 2011-12-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
电子邮件发送：
    $mail = new myEmail();
    $mail->init($from, $charset, $log_file);
    $mail->setFrom('from@mailserver.com', 'name');
    $mail->setSubject('mail subject');
    $mail->setContent('mail content', true);
    $mail->addEmail('anymail@server.com', 'recipient name', 'to');
    $mail->addEmail('anymail1@server.com', 'recipient name', 'cc');
    $mail->addFile($file_at_server, $filename, $filetype, $embed);
    $mail->addHeader('Disposition-Notification-To', 'anymail@server.com');
    $mail->send(array('mode'=>'smtp', 'host'=>'mailserver', 'port'=>25, 'user'=>'username', 'password'=>'psw'), true);
      or
    $mail->send(array('mode'=>'ssl', 'host'=>'smtp.gmail.com', 'port'=>465, 'user'=>'username@gmail.com', 'password'=>'password'));
*/
Class myEmail extends myBase {
    use myTrait;

    protected
        $isHtml = true,
        $charset = 'UTF-8',
        $from = '',
        $to = array(),
        $reply = array(),
        $cc = array(),
        $bcc = array(),
        $headers = array(),
        $subject = '',
        $body = '',
        $content = '',
        $files = array(),
        $file_count = 0,
        $boundary = array(),
        $result = array(),
        $func_alias = array(
                        'from' => 'setFrom',
                        'subject' => 'setSubject',
                        'content' => 'setContent',
                        'mail' => 'addEmail',
                        'file' => 'addFile',
                        'header' => 'addHeader',
                );

    /**
     * 参数初始化
     * @param string $from
     * @param string $charset
     */
    public function init($from='', $charset='UTF-8') {
        if(!empty($from)) $this->setFrom($from);
        if(!empty($charset)) $this->charset = $charset;
        $boundary_str = 'mystep_'.Chop('b'.md5(uniqid(time()))).'_mystep';
        $this->boundary['body'] = '===Body_'.$boundary_str.'===';
        $this->boundary['attach'] = '===Attach_'.$boundary_str.'===';
        $this->boundary['embed'] = '===Embed_'.$boundary_str.'===';
    }

    /**
     * 设置邮件主题
     * @param $subject
     */
    public function setSubject($subject) {
        $subject = str_replace(chr(13), '', $subject);
        $subject = str_replace(chr(10), '', $subject);
        $subject = trim($subject);
        $subject = myString::setCharset($subject, $this->charset);
        $this->subject = '=?'.$this->charset.'?B?'.base64_encode($subject).'?=';
        return;
    }

    /**
     * 设置邮件发送人
     * @param $email
     * @param string $name
     * @param bool $auto
     * @return bool
     */
    public function setFrom($email, $name='', $auto=true) {
        $email = trim($email);
        if(!preg_match('/^[\w\-\.]+@(([\w\-]+)[.])+[a-z] {2, 4}$/i', $email)) return false;
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        $name = myString::setCharset($name, $this->charset);
        if(empty($name)) {
            $this->from = strstr($email, '@', true);
        } else {
            $name = '=?'.$this->charset.'?B?'.base64_encode($name).'?=';
            $this->from = $name . ' <' . $email . '>';
        }
        ini_set('sendmail_from', $email);
        if($auto) {
            $this->addEmail($email, $name, 'reply');
        }
        return true;
    }

    /**
     * 设置邮件内容
     * @param $content
     * @param bool $isHtml
     * @param string $body_alt
     */
    public function setContent($content, $isHtml=true, $body_alt='') {
        $this->body = '';
        $boundary = $this->boundary['body'];
        $this->isHtml = $isHtml;
        $content = myString::setCharset($content, $this->charset);
        if($isHtml) {
            $this->body = '
--'.$boundary.'
Content-Type: text/plain; charset="'.$this->charset.'"
Content-Transfer-Encoding: base64

';
            if(empty($body_alt)) $body_alt = chunk_split(base64_encode(trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', str_replace('&#160;', '  ', str_replace('&nbsp;', ' ', $content)))))));
            if(trim($body_alt)=='') $body_alt = chunk_split(base64_encode('This mail is created by MyEmail(windy2006@gmail.com). '.chr(10).chr(10).'To view this email message, open it in a program that understands HTML!'));
            $this->body .= $body_alt.chr(10);
            $this->content = $content;
            $this->body .= '
--'.$boundary.'
Content-Type: text/html; charset="'.$this->charset.'"
Content-Transfer-Encoding: base64

<!--content-->
';
        } else {
            $content = chunk_split(base64_encode($content));
            $this->body = '
--'.$boundary.'
Content-Type: text/plain; charset="'.$this->charset.'"
Content-Transfer-Encoding: base64

'.$content.'
';
        }
        $this->body .= chr(10).'--'.$boundary.'--'.chr(10).chr(10);
        $this->body = str_replace(chr(13), '', $this->body);
        return;
    }

    /**
     * 添加邮件
     * @param $email
     * @param string $name
     * @param string $type
     * @return false|int
     */
    public function addEmail($email, $name='', $type='to') {
        $type = strtolower($type);
        if(array_search($type, array('to', 'cc', 'bcc', 'reply'))===false) $type = 'to';
        $email = trim($email);
        if(empty($name)) $name = strstr($email, '@', true);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        $name = myString::setCharset($name, $this->charset);
        if($flag = preg_match('/^[\w\-\.]+@(([\w\-]+)[.])+[a-z] {2, 4}$/i', $email)) {
            array_push($this->$type, array($email, $name));
        }
        return $flag;
    }

    /**
     * 添加收件人
     * @param $email
     * @param string $name
     */
    public function to($email, $name = '') {
        if(is_array($email)) {
            foreach($email as $k => $v) {
                if(is_numeric($k)) $k = '';
                $this->addEmail($v, $k, 'to');
            }
        } else {
            $this->addEmail($email, $name, 'to');
        }
    }

    /**
     * 添加抄送人
     * @param $email
     * @param string $name
     */
    public function cc($email, $name = '') {
        if(is_array($email)) {
            foreach($email as $k => $v) {
                if(is_numeric($k)) $k = '';
                $this->addEmail($v, $k, 'cc');
            }
        } else {
            $this->addEmail($email, $name, 'cc');
        }
    }

    /**
     * 添加暗送人
     * @param $email
     * @param string $name
     */
    public function bcc($email, $name = '') {
        if(is_array($email)) {
            foreach($email as $k => $v) {
                if(is_numeric($k)) $k = '';
                $this->addEmail($v, $k, 'bcc');
            }
        } else {
            $this->addEmail($email, $name, 'bcc');
        }
    }

    /**
     * 添加回复人
     * @param $email
     * @param string $name
     */
    public function reply($email, $name = '') {
        if(is_array($email)) {
            foreach($email as $k => $v) {
                if(is_numeric($k)) $k = '';
                $this->addEmail($v, $k, 'reply');
            }
        } else {
            $this->addEmail($email, $name, 'reply');
        }
    }

    /**
     * 格式化邮件地址
     * @param $email_list
     * @return array|string
     */
    public function formatEmail($email_list) {
        if(!is_array($email_list[0])) {
            if(empty($email_list[1])) {
                $result = $email_list[0];
            } else {
                $result = '"' . $email_list[1] . '" <'. $email_list[0] . '>';
            }
        } else {
            $result = array();
            foreach($email_list as $email) {
                if(empty($email[1])) {
                    $result[] = $email[0];
                } else {
                    $result[] = '"' . $email[1] . '" <' . $email[0] . '>';
                }
            }
        }
        return $result;
    }

    /**
     * 添加邮件头
     * @param $name
     * @param $value
     * @return bool
     */
    public function addHeader($name, $value) {
        $name = str_replace(chr(13), '', $name);
        $name = str_replace(chr(10), '', $name);
        $name = trim($name);
        $value = str_replace(chr(13), '', $value);
        $value = str_replace(chr(10), '', $value);
        $value = trim($value);
        $this->headers[] = $name . ': ' . $value;
        return true;
    }

    /**
     * 添加附件
     * @param $filecontent
     * @param string $filename
     * @param bool $embed
     * @param string $filetype
     */
    public function addAttachment($filecontent, $filename = '', $embed=false, $filetype = 'application/octet-stream') {
        $this->file_count++;
        if($filename=='') {
            $filename = 'attachment_'.$this->file_count;
        }
        if(!$embed) {
            $this->files['attach'][] = array(
                                        'type' => $filetype,
                                        'content' => $filecontent,
                                        'name' => $filename,
                                    );
        }else {
            $this->files['embed'][] = array(
                                        'type' => $filetype,
                                        'content' => $filecontent,
                                        'name' => $filename,
                                    );
        }
        return;
    }

    /**
     * 添加附件文件
     * @param $file
     * @param string $filename
     * @param bool $embed
     * @param string $filetype
     */
    public function addFile($file, $filename='', $embed=false, $filetype = 'application/octet-stream') {
        $filecontent = file_get_contents($file);
        if(empty($filename)) $filename = basename($file);
        $this->addAttachment($filecontent, $filename, $filetype, $embed);
    }

    /**
     * 附件编码
     * @param $file
     * @param bool $embed
     * @return string
     */
    public function setFile($file, $embed = false) {
        $content = $file['content'];
        $content = chunk_split(base64_encode($content));
        $return = 'Content-Type: '.$file['type'].';'.chr(10);
        $return .= '        name = "'.$file['name'].'"'.chr(10);
        $return .= 'Content-Transfer-Encoding: base64'.chr(10);
        if($embed) {
            $cid='__'.$file['name'].'@mystep__';
            $return .= 'Content-ID: <'.$cid.'>'.chr(10).chr(10);
            $this->content=str_replace($file['name'], 'cid:'.$cid, $this->content);
        }else {
            $return .= 'Content-Disposition: attachment;'.chr(10).' filename="'.$file['name'].'"'.chr(10).chr(10);
        }
        $return .= $content.chr(10);
        return $return;
    }

    /**
     * 邮件编码
     * @return string
     */
    public function buildMail() {
        $multipart = 'Content-Type: multipart/mixed;
  boundary = "'.$this->boundary['attach'].'"

  This is a multi-part message in MIME format created by Windy2000(windy2006@gmail.com).

--'.$this->boundary['attach'].'
Content-Type: multipart/related;
          boundary="'.$this->boundary['embed'].'";
          type=\'multipart/alternative\'

--'.$this->boundary['embed'].'
Content-Type: multipart/alternative;
          boundary="'.$this->boundary['body'].'"


        ';
        if(!$this->isHtml && isset($this->files['embed'])) {
            $this->files['attach'] += $this->files['embed'];
            $this->files['embed'] = array();
        }
        $attachmentCount = isset($this->files['attach'])?count($this->files['attach']):0;
        $attachmentContent = '';
        $embedCount = isset($this->files['embed'])?count($this->files['embed']):0;
        $embedContent = '';

        if($attachmentCount!=0) {
            for($i = $attachmentCount-1;$i>=0;$i--) {
                $attachmentContent .= chr(10).'--'.$this->boundary['attach'].chr(10).$this->setFile($this->files['attach'][$i], false);
            }
            $attachmentContent .= chr(10).'--'.$this->boundary['attach'].'--'.chr(10);
        }

        if($embedCount!=0) {
            for($i = $embedCount-1;$i>=0;$i--) {
                $embedContent .= chr(10).'--'.$this->boundary['embed'].chr(10).$this->setFile($this->files['embed'][$i], true);
            }
            $embedContent .= chr(10).'--'.$this->boundary['embed'].'--'.chr(10);
        }
        $this->body = str_replace('<!--content-->', chunk_split(base64_encode($this->content)), $this->body);
        $multipart.= $this->body;
        $multipart.= $embedContent;
        $multipart.= $attachmentContent;
        $multipart = rtrim($multipart);
        return $multipart;
    }

    /**
     * 邮件发送
     * @param array $para
     * @param bool $single
     * @param int $priority
     * @return array|bool
     */
    public function send($para=array(), $single=false, $priority=3) {
        if($this->from=='') $this->from = ini_get('sendmail_from');
        $this->addHeader('Return-Path', $this->from);
        $mail_list = array_merge($this->to, $this->cc, $this->bcc);
        if($single==false) {
            if(count($this->to)>0) $this->addHeader('To', implode(', ', $this->formatEmail($this->to)));
            if(count($this->cc)>0) $this->addHeader('Cc', implode(', ', $this->formatEmail($this->cc)));
            if(count($this->bcc)>0) $this->addHeader('Bcc', implode(', ', $this->formatEmail($this->bcc)));
        }
        $this->addHeader('From', $this->from);
        if(count($this->reply)>0) $this->addHeader('Reply-To', implode(', ', $this->formatEmail($this->reply)));
        $this->addHeader('Subject', $this->subject);
        $this->addHeader('Message-ID',  sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['HTTP_HOST']));
        if(!preg_match('/[1-5]/', $priority)) $priority = 3;
        $this->addHeader('X-Priority', $priority);
        $this->addHeader('X-Mailer', 'MyStepFramework');
        $this->addHeader('MIME-Version', '1.0');

        $mail_content = implode(chr(13).chr(10), $this->headers).chr(13).chr(10);
        $mail_content .= $this->buildMail();

        if(empty($para['mode'])) $para['mode'] = 'smtp';
        $smtp = new SMTP();
        if(!$smtp->Connect((($para['mode']=='ssl' || $para['mode']=='ssl/tls')?'ssl://':'').$para['host'], $para['port'], 10)) {
            $this->error('Cannot connect to the mail server!');
            return false;
        }
        if(!$smtp->Hello($_SERVER['HTTP_HOST'])) {
            $this->error('Cannot send messege to the mail server!');
            return false;
        }
        if($para['mode']=='tls' || $para['mode']=='ssl/tls') {
            if(!$smtp->StartTLS()) {
                $this->error('TLS error!');
                return false;
            }
            $smtp->Hello($_SERVER['HTTP_HOST']);
        }
        if(isset($para['user'])) {
            if(!$smtp->Authenticate($para['user'], $para['password'])) {
                $this->error('Authenticate Failed!');
                return false;
            }
        }
        if(!$smtp->Mail(ini_get('sendmail_from'))) {
            $this->error('Bad sender email');
            return false;
        }
        for($i=0, $m=count($mail_list); $i<$m; $i++) {
            if($smtp->Recipient($mail_list[$i][0])) {
                $info = ' sent successfully!';
            } else {
                $info = ' sent error!';
            }
            $this->result[] = $mail_list[$i][0].$info;
        }
        if(!$smtp->Data($mail_content)) {
            $this->error('Mail send Failed!');
            return false;
        }
        $smtp->Reset();
        if($smtp->Connected()) {
            $smtp->Quit();
            $smtp->Close();
        }
        return $this->result;
    }
}

/**
 * PHPMailer - PHP SMTP email transport class
 * NOTE: Designed for use with PHP version 5 and up
 * @package PHPMailer
 * @author Andy Prevost
 * @author Marcus Bointon
 * @copyright 2004 - 2008 Andy Prevost
 * @author Jim Jagielski
 * @copyright 2010 - 2012 Jim Jagielski
 * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
 * @version $Id: class.smtp.php 450 2010-06-23 16:46:33Z coolbru $
 */

/**
 * SMTP is rfc 821 compliant and implements all the rfc 821 SMTP
 * commands except TURN which will always return a not implemented
 * error. SMTP also provides some utility methods for sending mail
 * to an SMTP server.
 * original author: Chris Ryan
 */

class SMTP {
    /**
     *  SMTP server port
     *  @var int
     */
        public $SMTP_PORT = 25;

    /**
     *  SMTP reply line ending
     *  @var string
     */
        public $CRLF = "\r\n";

    /**
     *  Sets whether debugging is turned on
     *  @var bool
     */
        public $do_debug;           // the level of debug to perform

    /**
     *  Sets VERP use on/off (default is off)
     *  @var bool
     */
        public $do_verp = false;

    /**
     * Sets the SMTP PHPMailer Version number
     * @var string
     */
        public $Version                 = '5.2.1';

        /////////////////////////////////////////////////
        // PROPERTIES, PRIVATE AND PROTECTED
        /////////////////////////////////////////////////

        private $smtp_conn; // the socket to the server
        private $error;         // error if any on the last call
        private $helo_rply; // the reply the server sent to us for HELO

    /**
     * Initialize the class so that the data is in a known state.
     * @access public
     * @return void
     */
        public function __construct() {
                $this->smtp_conn = 0;
                $this->error = null;
                $this->helo_rply = null;

                $this->do_debug = 0;
        }

        /////////////////////////////////////////////////
        // CONNECTION FUNCTIONS
        /////////////////////////////////////////////////

    /**
     * Connect to the server specified on the port specified.
     * If the port is not specified use the default SMTP_PORT.
     * If tval is specified then a connection will try and be
     * established with the server for that number of seconds.
     * If tval is not specified the default is 30 seconds to
     * try on the connection.
     *
     * SMTP CODE SUCCESS: 220
     * SMTP CODE FAILURE: 421
     * @access public
     * @return bool
     */
        public function Connect($host, $port = 0, $tval = 30) {
                // set the error val to null so there is no confusion
                $this->error = null;

                // make sure we are __not__ connected
                if($this->connected()) {
                        // already connected, generate error
                        $this->error = array("error" => "Already connected to a server");
                        return false;
                }

                if(empty($port)) {
                        $port = $this->SMTP_PORT;
                }

                // connect to the smtp server
                $this->smtp_conn = @fsockopen($host,        // the host of the server
                        $port,        // the port to use
                        $errno,   // error number if any
                        $errstr,  // error message if any
                        $tval);   // give up after ? secs
                // verify we connected properly
                if(empty($this->smtp_conn)) {
                        $this->error = array("error" => "Failed to connect to server",
                                "errno" => $errno,
                                "errstr" => $errstr);
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": $errstr ($errno)" . $this->CRLF . '<br />';
                        }
                        return false;
                }

                // SMTP server can take longer to respond, give longer timeout for first read
                // Windows does not have support for this timeout function
                if(substr(PHP_OS, 0, 3) != "WIN")
                        socket_set_timeout($this->smtp_conn, $tval, 0);

                // get any announcement
                $announce = $this->get_lines();

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $announce . $this->CRLF . '<br />';
                }

                return true;
        }

    /**
     * Initiate a TLS communication with the server.
     *
     * SMTP CODE 220 Ready to start TLS
     * SMTP CODE 501 Syntax error (no parameters allowed)
     * SMTP CODE 454 TLS not available due to temporary reason
     * @access public
     * @return bool success
     */
        public function StartTLS() {
                $this->error = null; # to avoid confusion

                if(!$this->connected()) {
                        $this->error = array("error" => "Called StartTLS() without being connected");
                        return false;
                }

                fputs($this->smtp_conn, "STARTTLS" . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 220) {
                        $this->error =
                                array("error"         => "STARTTLS not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg"  => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                // Begin encrypted connection
                if(!stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        return false;
                }

                return true;
        }

    /**
     * Performs SMTP authentication.  Must be run after running the
     * Hello() method.  Returns true if successfully authenticated.
     * @access public
     * @return bool
     */
        public function Authenticate($username, $password) {
                // Start authentication
                fputs($this->smtp_conn, "AUTH LOGIN" . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($code != 334) {
                        $this->error =
                                array("error" => "AUTH not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                // Send encoded username
                fputs($this->smtp_conn, base64_encode($username) . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($code != 334) {
                        $this->error =
                                array("error" => "Username not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                // Send encoded password
                fputs($this->smtp_conn, base64_encode($password) . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($code != 235) {
                        $this->error =
                                array("error" => "Password not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                return true;
        }

    /**
     * Returns true if connected to a server otherwise false
     * @access public
     * @return bool
     */
        public function Connected() {
                if(!empty($this->smtp_conn)) {
                        $sock_status = socket_get_status($this->smtp_conn);
                        if($sock_status["eof"]) {
                                // the socket is valid but we are not connected
                                if($this->do_debug >= 1) {
                                        echo "SMTP -> NOTICE:" . $this->CRLF . "EOF caught while checking if connected";
                                }
                                $this->Close();
                                return false;
                        }
                        return true; // everything looks good
                }
                return false;
        }

    /**
     * Closes the socket and cleans up the state of the class.
     * It is not considered good to use this function without
     * first trying to use QUIT.
     * @access public
     * @return void
     */
        public function Close() {
                $this->error = null; // so there is no confusion
                $this->helo_rply = null;
                if(!empty($this->smtp_conn)) {
                        // close the connection and cleanup
                        fclose($this->smtp_conn);
                        $this->smtp_conn = 0;
                }
        }

        /////////////////////////////////////////////////
        // SMTP COMMANDS
        /////////////////////////////////////////////////

    /**
     * Issues a data command and sends the msg_data to the server
     * finializing the mail transaction. $msg_data is the message
     * that is to be send with the headers. Each header needs to be
     * on a single line followed by a <CRLF> with the message headers
     * and the message body being seperated by and additional <CRLF>.
     *
     * Implements rfc 821: DATA <CRLF>
     *
     * SMTP CODE INTERMEDIATE: 354
     *         [data]
     *         <CRLF>.<CRLF>
     *         SMTP CODE SUCCESS: 250
     *         SMTP CODE FAILURE: 552, 554, 451, 452
     * SMTP CODE FAILURE: 451, 554
     * SMTP CODE ERROR  : 500, 501, 503, 421
     * @access public
     * @return bool
     */
        public function Data($msg_data) {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Data() without being connected");
                        return false;
                }

                fputs($this->smtp_conn, "DATA" . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 354) {
                        $this->error =
                                array("error" => "DATA command not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                /* the server is ready to accept data!
             * according to rfc 821 we should not send more than 1000
             * including the CRLF
             * characters on a single line so we will break the data up
             * into lines by \r and/or \n then if needed we will break
             * each of those into smaller lines to fit within the limit.
             * in addition we will be looking for lines that start with
             * a period '.' and append and additional period '.' to that
             * line. NOTE: this does not count towards limit.
             */

                // normalize the line breaks so we know the explode works
                $msg_data = str_replace(chr(13).chr(10), "\n", $msg_data);
                $msg_data = str_replace("\r", "\n", $msg_data);
                $lines = explode("\n", $msg_data);

                /* we need to find a good way to determine is headers are
             * in the msg_data or if it is a straight msg body
             * currently I am assuming rfc 822 definitions of msg headers
             * and if the first field of the first line (':' sperated)
             * does not contain a space then it _should_ be a header
             * and we can process all lines before a blank "" line as
             * headers.
             */

                $field = substr($lines[0], 0, strpos($lines[0], ":"));
                $in_headers = false;
                if(!empty($field) && !strstr($field, " ")) {
                        $in_headers = true;
                }

                $max_line_length = 998; // used below; set here for ease in change

                while(list(, $line) = @each($lines)) {
                        $lines_out = null;
                        if($line == "" && $in_headers) {
                                $in_headers = false;
                        }
                        // ok we need to break this line up into several smaller lines
                        while(strlen($line) > $max_line_length) {
                                $pos = strrpos(substr($line, 0, $max_line_length), " ");

                                // Patch to fix DOS attack
                                if(!$pos) {
                                        $pos = $max_line_length - 1;
                                        $lines_out[] = substr($line, 0, $pos);
                                        $line = substr($line, $pos);
                                } else {
                                        $lines_out[] = substr($line, 0, $pos);
                                        $line = substr($line, $pos + 1);
                                }

                                /* if processing headers add a LWSP-char to the front of new line
                             * rfc 822 on long msg headers
                             */
                                if($in_headers) {
                                        $line = "\t" . $line;
                                }
                        }
                        $lines_out[] = $line;

                        // send the lines to the server
                        while(list(, $line_out) = @each($lines_out)) {
                                if(strlen($line_out) > 0)
                                {
                                        if(substr($line_out, 0, 1) == ".") {
                                                $line_out = "." . $line_out;
                                        }
                                }
                                fputs($this->smtp_conn, $line_out . $this->CRLF);
                        }
                }

                // message data has been sent
                fputs($this->smtp_conn, $this->CRLF . "." . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 250) {
                        $this->error =
                                array("error" => "DATA not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }
                return true;
        }

    /**
     * Sends the HELO command to the smtp server.
     * This makes sure that we and the server are in
     * the same known state.
     *
     * Implements from rfc 821: HELO <SP> <domain> <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE ERROR  : 500, 501, 504, 421
     * @access public
     * @return bool
     */
        public function Hello($host = '') {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Hello() without being connected");
                        return false;
                }

                // if hostname for HELO was not specified send default
                if(empty($host)) {
                        // determine appropriate default to send to server
                        $host = "localhost";
                }

                // Send extended hello first (RFC 2821)
                if(!$this->SendHello("EHLO", $host)) {
                        if(!$this->SendHello("HELO", $host)) {
                                return false;
                        }
                }

                return true;
        }

    /**
     * Sends a HELO/EHLO command.
     * @access private
     * @return bool
     */
        private function SendHello($hello, $host) {
                fputs($this->smtp_conn, $hello . " " . $host . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER: " . $rply . $this->CRLF . '<br />';
                }

                if($code != 250) {
                        $this->error =
                                array("error" => $hello . " not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                $this->helo_rply = $rply;

                return true;
        }

    /**
     * Starts a mail transaction from the email address specified in
     * $from. Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more Recipient
     * commands may be called followed by a Data command.
     *
     * Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE SUCCESS: 552, 451, 452
     * SMTP CODE SUCCESS: 500, 501, 421
     * @access public
     * @return bool
     */
        public function Mail($from) {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Mail() without being connected");
                        return false;
                }

                $useVerp = ($this->do_verp ? "XVERP" : "");
                fputs($this->smtp_conn, "MAIL FROM:<" . $from . ">" . $useVerp . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 250) {
                        $this->error =
                                array("error" => "MAIL not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }
                return true;
        }

    /**
     * Sends the quit command to the server and then closes the socket
     * if there is no error or the $close_on_error argument is true.
     *
     * Implements from rfc 821: QUIT <CRLF>
     *
     * SMTP CODE SUCCESS: 221
     * SMTP CODE ERROR  : 500
     * @access public
     * @return bool
     */
        public function Quit($close_on_error = true) {
                $this->error = null; // so there is no confusion

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Quit() without being connected");
                        return false;
                }

                // send the quit command to the server
                fputs($this->smtp_conn, "quit" . $this->CRLF);

                // get any good-bye messages
                $byemsg = $this->get_lines();

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $byemsg . $this->CRLF . '<br />';
                }

                $rval = true;
                $e = null;

                $code = substr($byemsg, 0, 3);
                if($code != 221) {
                        // use e as a tmp var cause Close will overwrite $this->error
                        $e = array("error" => "SMTP server rejected quit command",
                                "smtp_code" => $code,
                                "smtp_rply" => substr($byemsg, 4));
                        $rval = false;
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $e["error"] . ": " . $byemsg . $this->CRLF . '<br />';
                        }
                }

                if(empty($e) || $close_on_error) {
                        $this->Close();
                }

                return $rval;
        }

    /**
     * Sends the command RCPT to the SMTP server with the TO: argument of $to.
     * Returns true if the recipient was accepted false if it was rejected.
     *
     * Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>
     *
     * SMTP CODE SUCCESS: 250, 251
     * SMTP CODE FAILURE: 550, 551, 552, 553, 450, 451, 452
     * SMTP CODE ERROR  : 500, 501, 503, 421
     * @access public
     * @return bool
     */
        public function Recipient($to) {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Recipient() without being connected");
                        return false;
                }

                fputs($this->smtp_conn, "RCPT TO:<" . $to . ">" . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 250 && $code != 251) {
                        $this->error =
                                array("error" => "RCPT not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }
                return true;
        }

    /**
     * Sends the RSET command to abort and transaction that is
     * currently in progress. Returns true if successful false
     * otherwise.
     *
     * Implements rfc 821: RSET <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE ERROR  : 500, 501, 504, 421
     * @access public
     * @return bool
     */
        public function Reset() {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called Reset() without being connected");
                        return false;
                }

                fputs($this->smtp_conn, "RSET" . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 250) {
                        $this->error =
                                array("error" => "RSET failed",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }

                return true;
        }

    /**
     * Starts a mail transaction from the email address specified in
     * $from. Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more Recipient
     * commands may be called followed by a Data command. This command
     * will send the message to the users terminal if they are logged
     * in and send them an email.
     *
     * Implements rfc 821: SAML <SP> FROM:<reverse-path> <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE SUCCESS: 552, 451, 452
     * SMTP CODE SUCCESS: 500, 501, 502, 421
     * @access public
     * @return bool
     */
        public function SendAndMail($from) {
                $this->error = null; // so no confusion is caused

                if(!$this->connected()) {
                        $this->error = array(
                                "error" => "Called SendAndMail() without being connected");
                        return false;
                }

                fputs($this->smtp_conn, "SAML FROM:" . $from . $this->CRLF);

                $rply = $this->get_lines();
                $code = substr($rply, 0, 3);

                if($this->do_debug >= 2) {
                        echo "SMTP -> FROM SERVER:" . $rply . $this->CRLF . '<br />';
                }

                if($code != 250) {
                        $this->error =
                                array("error" => "SAML not accepted from server",
                                        "smtp_code" => $code,
                                        "smtp_msg" => substr($rply, 4));
                        if($this->do_debug >= 1) {
                                echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->CRLF . '<br />';
                        }
                        return false;
                }
                return true;
        }

    /**
     * This is an optional command for SMTP that this class does not
     * support. This method is here to make the RFC821 Definition
     * complete for this class and __may__ be implimented in the future
     *
     * Implements from rfc 821: TURN <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE FAILURE: 502
     * SMTP CODE ERROR  : 500, 503
     * @access public
     * @return bool
     */
        public function Turn() {
                $this->error = array("error" => "This method, TURN, of the SMTP ".
                        "is not implemented");
                if($this->do_debug >= 1) {
                        echo "SMTP -> NOTICE: " . $this->error["error"] . $this->CRLF . '<br />';
                }
                return false;
        }

    /**
     * Get the current error
     * @access public
     * @return array
     */
        public function getError() {
                return $this->error;
        }

        /////////////////////////////////////////////////
        // INTERNAL FUNCTIONS
        /////////////////////////////////////////////////

    /**
     * Read in as many lines as possible
     * either before eof or socket timeout occurs on the operation.
     * With SMTP we can tell if we have more lines to read if the
     * 4th character is '-' symbol. If it is a space then we don't
     * need to read anything else.
     * @access private
     * @return string
     */
        private function get_lines() {
                $data = "";
                while(!feof($this->smtp_conn)) {
                        $str = @fgets($this->smtp_conn, 515);
                        if($this->do_debug >= 4) {
                                echo "SMTP -> get_lines(): \$data was \"$data\"" . $this->CRLF . '<br />';
                                echo "SMTP -> get_lines(): \$str is \"$str\"" . $this->CRLF . '<br />';
                        }
                        $data .= $str;
                        if($this->do_debug >= 4) {
                                echo "SMTP -> get_lines(): \$data is \"$data\"" . $this->CRLF . '<br />';
                        }
                        // if 4th character is a space, we are done reading, break the loop
                        if(substr($str, 3, 1) == " ") { break; }
                }
                return $data;
        }

}