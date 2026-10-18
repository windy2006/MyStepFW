<?PHP
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
 * 文件：lib/myEmail.class.php
 * 功能说明：MIME邮件构建及SMTP传输工具
 *   提供发件人/收件人、附件、内嵌资源与MIME编码，并包含兼容SMTP传输类。
 *
 * 方法目录：
 *   public myEmail::init($from='', $charset='UTF-8') — 参数初始化
 *   public myEmail::setSubject($subject) — 设置邮件主题
 *   public myEmail::setFrom($email, $name='', $auto=true) — 设置邮件发送人
 *   public myEmail::setContent($content, $isHtml=true, $body_alt='') — 设置邮件内容
 *   public myEmail::addEmail($email, $name='', $type='to') — 校验并登记一个邮箱到to/cc/bcc/reply分组
 *   public myEmail::to($email, $name = '') — 添加收件人
 *   public myEmail::cc($email, $name = '') — 添加抄送人
 *   public myEmail::bcc($email, $name = '') — 添加暗送人
 *   public myEmail::reply($email, $name = '') — 添加回复人
 *   public myEmail::formatEmail($email_list) — 格式化邮件地址
 *   public myEmail::addHeader($name, $value) — 添加邮件头
 *   public myEmail::addAttachment($filecontent, $filename = '', $embed=false, $filetype = 'application/octet-stream') — 添加附件
 *   public myEmail::addFile($file, $filename='', $embed=false, $filetype = 'application/octet-stream') — 添加附件文件
 *   public myEmail::setFile($file, $embed = false) — 附件编码
 *   public myEmail::buildMail() — 邮件编码
 *   public myEmail::send($para=array(), $single=false, $priority=3) — 邮件发送
 *   public SMTP::__construct() — 初始化SMTP端口、连接及错误状态
 *   public SMTP::Connect($host, $port = 0, $tval = 30) — 按主机、端口和超时时间建立SMTP套接字连接
 *   public SMTP::StartTLS() — 发送STARTTLS并将当前SMTP连接升级为TLS
 *   public SMTP::Authenticate($username, $password) — 使用用户名和密码执行SMTP LOGIN认证
 *   public SMTP::Connected() — 检查SMTP套接字是否存在且尚未到达流末尾
 *   public SMTP::Close() — 关闭SMTP套接字并清空连接状态
 *   public SMTP::Data($msg_data) — 发送DATA命令及完整邮件内容，处理行长和点转义
 *   public SMTP::Hello($host = '') — 优先发送EHLO，失败时回退HELO问候
 *   private SMTP::SendHello($hello, $host) — 发送指定的EHLO/HELO问候并检查服务器响应
 *   public SMTP::Mail($from) — 发送MAIL FROM以开始一轮邮件事务
 *   public SMTP::Quit($close_on_error = true) — 发送QUIT并按响应及参数决定是否关闭连接
 *   public SMTP::Recipient($to) — 发送RCPT TO登记一个信封收件人
 *   public SMTP::Reset() — 发送RSET以放弃当前邮件事务
 *   public SMTP::SendAndMail($from) — 发送兼容SMTP的SAML FROM命令
 *   public SMTP::Turn() — 记录TURN未实现错误并返回false
 *   public SMTP::getError() — 取得最近一次SMTP错误结构
 *   private SMTP::get_lines() — 读取SMTP多行响应，直到最后一行或连接结束
 *
 * 使用说明：
 *   1. 先设置发件人、主题、正文和收件人，再传SMTP配置发送。
 *   2. 附件名称/邮件头按实现过滤；地址和传输配置仍需由调用方提供有效值。
 *   3. 底部SMTP类保留上游版权说明及协议细节；测试邮件应发送到受控测试服务。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：构建邮件头、地址、正文及附件，并通过SMTP发送MIME邮件
 *
 * 使用说明：
 *   1. 先设置发件人、主题、正文和收件人，再传SMTP配置发送。
 *   2. 附件名称/邮件头按实现过滤；地址和传输配置仍需由调用方提供有效值。
 *   3. 底部SMTP类保留上游版权说明及协议细节；测试邮件应发送到受控测试服务。
 *
 * 使用示例：
 *     $mail = new myEmail($from, 'UTF-8');
 *     $mail->setSubject('主题'); $mail->setContent('<p>正文</p>', true);
 *     $mail->to('recipient@example.com', '收件人');
 *     $mail->addFile($path, 'report.pdf', false, 'application/pdf');
 *     $result = $mail->send($smtpOptions);
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myEmail.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
Class myEmail extends myBase {
    use myTrait;

    protected
        $isHtml = true,
        $charset = 'UTF-8',
        $from = '',
        $from_email = '',
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
     * 功能说明：参数初始化
     *
     * @param string $from 源文件路径或发送方地址，按对应入口解释（默认：''）
     * @param string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'UTF-8'）
     * @return void 无返回值。
     */
    public function init($from='', $charset='UTF-8') {
        if(!empty($charset)) $this->charset = $charset;
        if(!empty($from)) $this->setFrom($from);
        $boundary_str = 'mystep_'.Chop('b'.md5(uniqid(time()))).'_mystep';
        $this->boundary['body'] = '===Body_'.$boundary_str.'===';
        $this->boundary['attach'] = '===Attach_'.$boundary_str.'===';
        $this->boundary['embed'] = '===Embed_'.$boundary_str.'===';
    }

    /**
     * 功能说明：设置邮件主题
     *
     * @param string $subject 邮件主题文本
     * @return void 无返回值。
     */
    public function setSubject($subject) {
        $subject = str_replace(chr(13), '', $subject);
        $subject = str_replace(chr(10), '', $subject);
        $subject = trim($subject);
        $subject = myString::setCharset($subject, $this->charset);
        $this->subject = '=?'.$this->charset.'?B?'.base64_encode($subject).'?=';
    }

    /**
     * 功能说明：设置邮件发送人
     *
     * @param string $email 合法的发件人邮箱地址
     * @param string $name 发件人显示姓名（默认：''）
     * @param bool $auto 是否同时添加为reply地址（默认：true）
     * @return bool 邮箱有效并登记为true，否则为false；auto为true时还登记回复地址
     */
    public function setFrom($email, $name='', $auto=true) {
        $email = trim($email);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $plain_name = $name;
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        $name = myString::setCharset($name, $this->charset);
        if(empty($name)) {
            $this->from = $email;
        } else {
            $name = '=?'.$this->charset.'?B?'.base64_encode($name).'?=';
            $this->from = $name . ' <' . $email . '>';
        }
        $this->from_email = $email;
        ini_set('sendmail_from', $email);
        if($auto) {
            $this->addEmail($email, $plain_name, 'reply');
        }
        return true;
    }

    /**
     * 功能说明：设置邮件内容
     *
     * @param string $content 待处理或输出的内容字节串
     * @param bool $isHtml 正文是否按HTML格式构建（默认：true）
     * @param string $body_alt HTML邮件对应的纯文本正文，空串不单独设置（默认：''）
     * @return void 无返回值。
     */
    public function setContent($content, $isHtml=true, $body_alt='') {
        if($body_alt !== '') $body_alt = chunk_split(base64_encode(myString::setCharset($body_alt, $this->charset)));
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
    }

    /**
     * 功能说明：校验并登记一个邮箱到to/cc/bcc/reply分组
     *
     * @param string $email 电子邮件地址
     * @param string $name 该邮箱的显示姓名，空串使用地址本身（默认：''）
     * @param string $type 地址分组：to、cc、bcc或reply（默认：'to'）
     * @return int 1表示地址有效并登记，0表示地址无效
     */
    public function addEmail($email, $name='', $type='to') {
        $type = strtolower($type);
        if(array_search($type, array('to', 'cc', 'bcc', 'reply'))===false) $type = 'to';
        $email = trim($email);
        if(empty($name)) $name = strstr($email, '@', true);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        $name = myString::setCharset($name, $this->charset);
        if($flag = (int)(filter_var($email, FILTER_VALIDATE_EMAIL) !== false)) {
            array_push($this->$type, array($email, $name));
        }
        return $flag;
    }

    /**
     * 功能说明：添加收件人
     *
     * @param string|array $email 单个邮箱地址，或邮箱列表/显示名到地址的映射
     * @param string $name 单个邮箱对应的显示姓名（默认：''）
     * @return void 无返回值。
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
     * 功能说明：添加抄送人
     *
     * @param string|array $email 单个邮箱地址，或邮箱列表/显示名到地址的映射
     * @param string $name 单个邮箱对应的显示姓名（默认：''）
     * @return void 无返回值。
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
     * 功能说明：添加暗送人
     *
     * @param string|array $email 单个邮箱地址，或邮箱列表/显示名到地址的映射
     * @param string $name 单个邮箱对应的显示姓名（默认：''）
     * @return void 无返回值。
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
     * 功能说明：添加回复人
     *
     * @param string|array $email 单个邮箱地址，或邮箱列表/显示名到地址的映射
     * @param string $name 单个邮箱对应的显示姓名（默认：''）
     * @return void 无返回值。
     */
    public function reply($email, $name = '') {
        if(is_array($email)) {
            foreach($email as $k => $v) {
                if(is_numeric($k)) $k = '';
                $this->addEmail($v, $k, 'reply');
            }
        } else {
            $this->addEmail($email, $plain_name, 'reply');
        }
    }

    /**
     * 功能说明：格式化邮件地址
     *
     * @param array $email_list 地址/姓名二元组或二元组列表
     * @return string|array 单个地址二元组返回MIME地址文本，地址列表返回文本数组，空列表返回空数组
     * @phpstan-param array{0:string,1?:string}|list<array{0:string,1?:string}> $email_list
     * @phpstan-return string|list<string>
     */
    public function formatEmail($email_list) {
        if(!$email_list) return [];
        $single = !is_array($email_list[0]);
        $list = $single ? [$email_list] : $email_list;
        $result = [];
        foreach($list as $email) {
            $name = $email[1] ?? '';
            $result[] = $name === '' ? $email[0] : '=?'.$this->charset.'?B?'.base64_encode($name).'?= <'.$email[0].'>';
        }
        return $single ? $result[0] : $result;
    }

    /**
     * 功能说明：添加邮件头
     *
     * @param string $name 邮件头字段名，会移除换行
     * @param string $value 邮件头字段值，会移除换行
     * @return true 移除名称/值中的换行并追加后固定返回true，不校验邮件头业务语义
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
     * 功能说明：添加附件
     *
     * @param string $filecontent 附件的原始二进制内容
     * @param string $filename 附件展示名称或文件路径，按方法用途解释（默认：''）
     * @param bool $embed 是否按邮件内嵌资源而非普通附件处理（默认：false）
     * @param string $filetype 附件的MIME类型（默认：'application/octet-stream'）
     * @return void 无返回值。
     * @throws InvalidArgumentException 附件名称为空或包含路径/控制字符等非法内容
     */
    public function addAttachment($filecontent, $filename = '', $embed=false, $filetype = 'application/octet-stream') {
        if(preg_match('/[\r\n\x00]/', $filename.$filetype) || !preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~iD', $filetype)) throw new InvalidArgumentException('Invalid MIME attachment header');
        $filename = str_replace(['\\', '"'], '_', $filename);
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
    }

    /**
     * 功能说明：添加附件文件
     *
     * @param string $file 目标文件路径
     * @param string $filename 附件展示名称或文件路径，按方法用途解释（默认：''）
     * @param bool $embed 是否按邮件内嵌资源而非普通附件处理（默认：false）
     * @param string $filetype 附件的MIME类型（默认：'application/octet-stream'）
     * @return bool 文件可读取并已登记为true；文件不存在或读取失败为false
     */
    public function addFile($file, $filename='', $embed=false, $filetype = 'application/octet-stream') {
        $filecontent = file_get_contents($file);
        if($filecontent === false) return false;
        if(empty($filename)) $filename = basename($file);
        $this->addAttachment($filecontent, $filename, $embed, $filetype);
        return true;
    }

    /**
     * 功能说明：附件编码
     *
     * @param array $file 含content/name/type等字段的内部附件记录
     * @param bool $embed 是否按邮件内嵌资源而非普通附件处理（默认：false）
     * @return string 含MIME类型、Base64正文及附件/CID头的邮件片段
     * @phpstan-param array{content:string,name:string,type:string} $file
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
        }else {
            $return .= 'Content-Disposition: attachment;'.chr(10).' filename="'.$file['name'].'"'.chr(10).chr(10);
        }
        $return .= $content.chr(10);
        return $return;
    }

    /**
     * 功能说明：邮件编码
     *
     * 参数说明：无显式参数。
     *
     * @return string 由当前地址、正文、附件及边界构建的完整邮件内容，不执行发送
     */
    public function buildMail() {
        $content = $this->content;
        $embedded = '';
        foreach($this->files['embed'] ?? [] as $file) {
            if($this->isHtml) {
                $cid = '__'.$file['name'].'@mystep__';
                $content = str_replace($file['name'], 'cid:'.$cid, $content);
                $embedded .= '--'.$this->boundary['embed']."\n".$this->setFile($file, true);
            }
        }
        $body = str_replace('<!--content-->', chunk_split(base64_encode($content)), $this->body);
        $message = 'Content-Type: multipart/mixed; boundary="'.$this->boundary['attach'].'"'."\n\n";
        $message .= '--'.$this->boundary['attach']."\n";
        $message .= 'Content-Type: multipart/related; boundary="'.$this->boundary['embed'].'"'."\n\n";
        $message .= '--'.$this->boundary['embed']."\n";
        $message .= 'Content-Type: multipart/alternative; boundary="'.$this->boundary['body'].'"'."\n".$body;
        $message .= $embedded.'--'.$this->boundary['embed']."--\n";
        $attachments = $this->files['attach'] ?? [];
        if(!$this->isHtml) $attachments = array_merge($attachments, $this->files['embed'] ?? []);
        foreach($attachments as $file) $message .= '--'.$this->boundary['attach']."\n".$this->setFile($file, false);
        $message .= '--'.$this->boundary['attach']."--\n";
        return str_replace("\n", "\r\n", str_replace("\r", '', $message));
    }

    /**
     * 功能说明：邮件发送
     *
     *
     * 副作用：会连接SMTP并提交邮件；返回成功只表示SMTP接受该次提交，最终投递由邮件服务器处理。连接在finally中关闭。
     * SMTP配置：host必填；mode为smtp/ssl/ssl/tls/tls之一，tls表示STARTTLS；port默认按模式选25/465/587，user为空时不认证，password默认空串。
     *
     * @param array $para SMTP连接配置，包含host以及认证/端口/TLS等已支持项（默认：array()）
     * @param bool $single 是否逐个收件人单独发送（默认：false）
     * @param int $priority 邮件优先级，按SMTP邮件头约定使用1至5（默认：3）
     * @return array|false 成功执行流程后为逐收件人的状态文本数组；缺少发件人/收件人/主机或SMTP流程失败为false
     * @phpstan-param array{host:string,port?:int,mode?:string,user?:string,password?:string} $para
     * @phpstan-return list<string>|false
     */
    public function send($para=array(), $single=false, $priority=3) {
        if($this->from_email === '' && !$this->setFrom(ini_get('sendmail_from'))) return false;
        $mail_list = array_merge($this->to, $this->cc, $this->bcc);
        if(!$mail_list || empty($para['host'])) return false;
        $mode = strtolower($para['mode'] ?? 'smtp');
        $smtp = new SMTP(); $this->result = []; $failure = '';
        $hello = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost');
        try {
            $implicit = in_array($mode, ['ssl','ssl/tls'], true);
            if(!$smtp->Connect(($implicit ? 'ssl://' : '').$para['host'], $para['port'] ?? ($implicit ? 465 : ($mode === 'tls' ? 587 : 25)), 10)) $failure = 'Cannot connect to mail server';
            elseif(!$smtp->Hello($hello)) $failure = 'SMTP greeting failed';
            elseif($mode === 'tls' && (!$smtp->StartTLS() || !$smtp->Hello($hello))) $failure = 'TLS negotiation failed';
            elseif(!empty($para['user']) && !$smtp->Authenticate($para['user'], $para['password'] ?? '')) $failure = 'SMTP authentication failed';
            if($failure === '') {
                $groups = $single ? array_map(function($recipient) { return [$recipient]; }, $mail_list) : [$mail_list];
                $mime = $this->buildMail();
                foreach($groups as $group) {
                    if(!$smtp->Mail($this->from_email)) { $failure = 'Bad sender email'; break; }
                    $accepted = [];
                    foreach($group as $recipient) {
                        if($smtp->Recipient($recipient[0])) $accepted[] = $recipient;
                        else $this->result[] = $recipient[0].' sent error!';
                    }
                    if(!$accepted) { $smtp->Reset(); continue; }
                    $headers = $this->headers;
                    $headers[] = 'From: '.$this->from;
                    $headers[] = 'To: '.($single ? implode(',', $this->formatEmail($group)) : ($this->to ? implode(',', $this->formatEmail($this->to)) : 'undisclosed-recipients:;'));
                    if(!$single && $this->cc) $headers[] = 'Cc: '.implode(',', $this->formatEmail($this->cc));
                    if($this->reply) $headers[] = 'Reply-To: '.implode(',', $this->formatEmail($this->reply));
                    $headers[] = 'Subject: '.$this->subject;
                    $headers[] = 'Date: '.date('r');
                    $headers[] = 'Message-ID: <'.bin2hex(random_bytes(16)).'@'.($hello ?: 'localhost').'>';
                    $headers[] = 'MIME-Version: 1.0';
                    $headers[] = 'X-Priority: '.max(1, min(5, (int)$priority));
                    $sent = $smtp->Data(implode("\r\n", $headers)."\r\n".$mime);
                    foreach($accepted as $recipient) $this->result[] = $recipient[0].($sent ? ' sent successfully!' : ' sent error!');
                    if(!$sent) { $failure = 'SMTP DATA failed'; break; }
                    $smtp->Reset();
                }
            }
        } finally {
            if($smtp->Connected()) $smtp->Quit();
            $smtp->Close();
        }
        if($failure !== '') { $this->error($failure); return false; }
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
 * 功能说明：MIME邮件使用的SMTP套接字传输实现，保留原作者版权说明
 *
 * 使用说明：
 *   1. 先设置发件人、主题、正文和收件人，再传SMTP配置发送。
 *   2. 附件名称/邮件头按实现过滤；地址和传输配置仍需由调用方提供有效值。
 *   3. 底部SMTP类保留上游版权说明及协议细节；测试邮件应发送到受控测试服务。
 *
 * 补充说明：
 * SMTP is rfc 821 compliant and implements all the rfc 821 SMTP
 * commands except TURN which will always return a not implemented
 * error. SMTP also provides some utility methods for sending mail
 * to an SMTP server.
 * original author: Chris Ryan
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
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
     * 功能说明：初始化SMTP端口、连接及错误状态
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     * @access public
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
     * 功能说明：按主机、端口和超时时间建立SMTP套接字连接
     *
     * If the port is not specified use the default SMTP_PORT.
     * If tval is specified then a connection will try and be
     * established with the server for that number of seconds.
     * If tval is not specified the default is 30 seconds to
     * try on the connection.
     *
     * SMTP CODE SUCCESS: 220
     * SMTP CODE FAILURE: 421
     *
     * @param string $host 服务器主机名或地址
     * @param int $port 服务器端口，0使用该协议的默认端口（默认：0）
     * @param int $tval 建立SMTP连接时的超时秒数（默认：30）
     * @return bool 建立连接并取得成功问候响应为true，连接/握手失败为false
     * @access public
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
                $peer = preg_replace('#^(?:ssl|tls|tcp)://#i', '', $host);
                $context = stream_context_create(['ssl'=>['verify_peer'=>true, 'verify_peer_name'=>true, 'allow_self_signed'=>false, 'peer_name'=>trim($peer, '[]')]]);
                $target = (strpos($host, '://') === false ? 'tcp://' : '').$host.':'.$port;
                $this->smtp_conn = @stream_socket_client($target, $errno, $errstr, $tval, STREAM_CLIENT_CONNECT, $context);
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

                if(substr($announce, 0, 3) !== '220') { $this->Close(); return false; }
                return true;
        }

    /**
     * 功能说明：发送STARTTLS并将当前SMTP连接升级为TLS
     *
     * SMTP CODE 220 Ready to start TLS
     * SMTP CODE 501 Syntax error (no parameters allowed)
     * SMTP CODE 454 TLS not available due to temporary reason
     *
     * 参数说明：无显式参数。
     *
     * @return bool success
     * @access public
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
     * 功能说明：使用用户名和密码执行SMTP LOGIN认证
     *
     * Hello() method.  Returns true if successfully authenticated.
     *
     * @param string $username 认证用户名
     * @param string $password 认证密码
     * @return bool LOGIN认证各步骤成功为true，服务器拒绝或协议错误为false
     * @access public
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
     * 功能说明：检查SMTP套接字是否存在且尚未到达流末尾
     *
     * 参数说明：无显式参数。
     *
     * @return bool 套接字存在且未结束为true；已断开时清理状态并返回false
     * @access public
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
     * 功能说明：关闭SMTP套接字并清空连接状态
     *
     * It is not considered good to use this function without
     * first trying to use QUIT.
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     * @access public
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
     * 功能说明：发送DATA命令及完整邮件内容，处理行长和点转义
     *
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
     *
     * @param string $msg_data 已构建的完整MIME邮件内容
     * @return bool DATA正文被服务器接受为true，命令或提交响应失败为false
     * @access public
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
     * 功能说明：优先发送EHLO，失败时回退HELO问候
     *
     * This makes sure that we and the server are in
     * the same known state.
     *
     * Implements from rfc 821: HELO <SP> <domain> <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE ERROR  : 500, 501, 504, 421
     *
     * @param string $host 用于EHLO/HELO问候的客户端主机名，空串自动选择（默认：''）
     * @return bool EHLO或回退的HELO成功为true，两者均失败为false
     * @access public
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
     * 功能说明：发送指定的EHLO/HELO问候并检查服务器响应
     *
     * @param string $hello SMTP问候命令，通常为EHLO或HELO
     * @param string $host EHLO/HELO中声明的客户端主机名
     * @return bool 服务器接受指定问候命令为true，否则为false
     * @access private
     * @internal 框架内部实现入口；应用优先调用对应公开方法
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
     * 功能说明：发送MAIL FROM以开始一轮邮件事务
     *
     * $from. Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more Recipient
     * commands may be called followed by a Data command.
     *
     * Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE SUCCESS: 552, 451, 452
     * SMTP CODE SUCCESS: 500, 501, 421
     *
     * @param string $from SMTP MAIL FROM信封发件人地址
     * @return bool 服务器接受MAIL FROM为true，否则为false
     * @access public
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
     * 功能说明：发送QUIT并按响应及参数决定是否关闭连接
     *
     * if there is no error or the $close_on_error argument is true.
     *
     * Implements from rfc 821: QUIT <CRLF>
     *
     * SMTP CODE SUCCESS: 221
     * SMTP CODE ERROR  : 500
     *
     * @param bool $close_on_error QUIT失败时是否仍关闭连接（默认：true）
     * @return bool QUIT响应成功为true；失败为false，是否仍关闭由close_on_error决定
     * @access public
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
     * 功能说明：发送RCPT TO登记一个信封收件人
     *
     * Returns true if the recipient was accepted false if it was rejected.
     *
     * Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>
     *
     * SMTP CODE SUCCESS: 250, 251
     * SMTP CODE FAILURE: 550, 551, 552, 553, 450, 451, 452
     * SMTP CODE ERROR  : 500, 501, 503, 421
     *
     * @param string $to SMTP RCPT TO信封收件人地址
     * @return bool 服务器接受当前收件人为true，否则为false，不代表最终邮件投递成功
     * @access public
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
     * 功能说明：发送RSET以放弃当前邮件事务
     *
     * currently in progress. Returns true if successful false
     * otherwise.
     *
     * Implements rfc 821: RSET <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE ERROR  : 500, 501, 504, 421
     *
     * 参数说明：无显式参数。
     *
     * @return bool 服务器确认RSET为true，否则为false
     * @access public
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
     * 功能说明：发送兼容SMTP的SAML FROM命令
     *
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
     *
     * @param string $from SMTP MAIL FROM信封发件人地址
     * @return bool 服务器接受SAML FROM为true，否则为false
     * @access public
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
     * 功能说明：记录TURN未实现错误并返回false
     *
     * support. This method is here to make the RFC821 Definition
     * complete for this class and __may__ be implimented in the future
     *
     * Implements from rfc 821: TURN <CRLF>
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE FAILURE: 502
     * SMTP CODE ERROR  : 500, 503
     *
     * 参数说明：无显式参数。
     *
     * @return false 固定返回false，同时记录未实现错误
     * @access public
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
     * 功能说明：取得最近一次SMTP错误结构
     *
     * 参数说明：无显式参数。
     *
     * @return array|null 最近错误字段组成的数组；无错误时为null
     * @access public
     */
        public function getError() {
                return $this->error;
        }

        /////////////////////////////////////////////////
        // INTERNAL FUNCTIONS
        /////////////////////////////////////////////////

    /**
     * 功能说明：读取SMTP多行响应，直到最后一行或连接结束
     *
     * either before eof or socket timeout occurs on the operation.
     * With SMTP we can tell if we have more lines to read if the
     * 4th character is '-' symbol. If it is a space then we don't
     * need to read anything else.
     *
     * 参数说明：无显式参数。
     *
     * @return string 读到的SMTP响应原文，可能为空或在超时/断开时只包含部分行
     * @access private
     * @internal 框架内部实现入口；应用优先调用对应公开方法
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
                        if(strlen($data) > 65536) break;
                        if($this->do_debug >= 4) {
                                echo "SMTP -> get_lines(): \$data is \"$data\"" . $this->CRLF . '<br />';
                        }
                        // if 4th character is a space, we are done reading, break the loop
                        if($str === false || $str === '' || (stream_get_meta_data($this->smtp_conn)['timed_out'] ?? false)) break;
                        if(substr($str, 3, 1) == " ") { break; }
                }
                return $data;
        }

}