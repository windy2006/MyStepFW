# 邮件工具

接口说明：`myEmail`。

## 职责与调用位置

buildMail只组装内容；send才涉及传输。地址、头部及附件检查不等于投递成功，SMTP凭据不得公开。

源码：`lib/myEmail.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$mail = new myEmail('sender@example.test');
$mail->setSubject('通知');
$mail->to('reader@example.test', '读者');
$mail->setContent('Hello', false);
$mime = $mail->buildMail();
// 配置了测试SMTP时才执行send；本示例不会向收件人实际发送。
```

## 声明与类职责

### myEmail

功能说明：构建邮件头、地址、正文及附件，并通过SMTP发送MIME邮件

使用说明：
  1. 先设置发件人、主题、正文和收件人，再传SMTP配置发送。
  2. 附件名称/邮件头按实现过滤；地址和传输配置仍需由调用方提供有效值。
  3. 底部SMTP类保留上游版权说明及协议细节；测试邮件应发送到受控测试服务。

使用示例：
    $mail = new myEmail($from, 'UTF-8');
    $mail->setSubject('主题'); $mail->setContent('<p>正文</p>', true);
    $mail->to('recipient@example.com', '收件人');
    $mail->addFile($path, 'report.pdf', false, 'application/pdf');
    $result = $mail->send($smtpOptions);

### SMTP

功能说明：MIME邮件使用的SMTP套接字传输实现，保留原作者版权说明

使用说明：
  1. 先设置发件人、主题、正文和收件人，再传SMTP配置发送。
  2. 附件名称/邮件头按实现过滤；地址和传输配置仍需由调用方提供有效值。
  3. 底部SMTP类保留上游版权说明及协议细节；测试邮件应发送到受控测试服务。

补充说明：
SMTP is rfc 821 compliant and implements all the rfc 821 SMTP
commands except TURN which will always return a not implemented
error. SMTP also provides some utility methods for sending mail
to an SMTP server.
original author: Chris Ryan

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
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
```

```text
public $SMTP_PORT = 25;
```

```text
public $CRLF = "\r\n";
```

```text
public $do_debug;
```

```text
public $do_verp = false;
```

```text
public $Version                 = '5.2.1';
```

```text
private $smtp_conn;
```

```text
private $error;
```

```text
private $helo_rply;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myEmail::init` | 参数初始化 |
| `myEmail::setSubject` | 设置邮件主题 |
| `myEmail::setFrom` | 设置邮件发送人 |
| `myEmail::setContent` | 设置邮件内容 |
| `myEmail::addEmail` | 校验并登记一个邮箱到to/cc/bcc/reply分组 |
| `myEmail::to` | 添加收件人 |
| `myEmail::cc` | 添加抄送人 |
| `myEmail::bcc` | 添加暗送人 |
| `myEmail::reply` | 添加回复人 |
| `myEmail::formatEmail` | 格式化邮件地址 |
| `myEmail::addHeader` | 添加邮件头 |
| `myEmail::addAttachment` | 添加附件 |
| `myEmail::addFile` | 添加附件文件 |
| `myEmail::setFile` | 附件编码 |
| `myEmail::buildMail` | 邮件编码 |
| `myEmail::send` | 邮件发送 |
| `SMTP::__construct` | 初始化SMTP端口、连接及错误状态 |
| `SMTP::Connect` | 按主机、端口和超时时间建立SMTP套接字连接 |
| `SMTP::StartTLS` | 发送STARTTLS并将当前SMTP连接升级为TLS |
| `SMTP::Authenticate` | 使用用户名和密码执行SMTP LOGIN认证 |
| `SMTP::Connected` | 检查SMTP套接字是否存在且尚未到达流末尾 |
| `SMTP::Close` | 关闭SMTP套接字并清空连接状态 |
| `SMTP::Data` | 发送DATA命令及完整邮件内容，处理行长和点转义 |
| `SMTP::Hello` | 优先发送EHLO，失败时回退HELO问候 |
| `SMTP::SendHello` | 发送指定的EHLO/HELO问候并检查服务器响应 |
| `SMTP::Mail` | 发送MAIL FROM以开始一轮邮件事务 |
| `SMTP::Quit` | 发送QUIT并按响应及参数决定是否关闭连接 |
| `SMTP::Recipient` | 发送RCPT TO登记一个信封收件人 |
| `SMTP::Reset` | 发送RSET以放弃当前邮件事务 |
| `SMTP::SendAndMail` | 发送兼容SMTP的SAML FROM命令 |
| `SMTP::Turn` | 记录TURN未实现错误并返回false |
| `SMTP::getError` | 取得最近一次SMTP错误结构 |
| `SMTP::get_lines` | 读取SMTP多行响应，直到最后一行或连接结束 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myEmail::init

功能说明：参数初始化

```text
public function init($from='', $charset='UTF-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$from` | string；源文件路径或发送方地址，按对应入口解释（默认：''） |
| `$charset` | string；字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'UTF-8'） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->init($from, $charset);
```

定位：`lib/myEmail.class.php:119`。

### myEmail::setSubject

功能说明：设置邮件主题

```text
public function setSubject($subject);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$subject` | string；邮件主题文本 |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->setSubject($subject);
```

定位：`lib/myEmail.class.php:134`。

### myEmail::setFrom

功能说明：设置邮件发送人

```text
public function setFrom($email, $name='', $auto=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string；合法的发件人邮箱地址 |
| `$name` | string；发件人显示姓名（默认：''） |
| `$auto` | bool；是否同时添加为reply地址（默认：true） |

**返回值**：bool 邮箱有效并登记为true，否则为false；auto为true时还登记回复地址

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->setFrom($email, $name, $auto);
```

定位：`lib/myEmail.class.php:150`。

### myEmail::setContent

功能说明：设置邮件内容

```text
public function setContent($content, $isHtml=true, $body_alt='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；待处理或输出的内容字节串 |
| `$isHtml` | bool；正文是否按HTML格式构建（默认：true） |
| `$body_alt` | string；HTML邮件对应的纯文本正文，空串不单独设置（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->setContent($content, $isHtml, $body_alt);
```

定位：`lib/myEmail.class.php:178`。

### myEmail::addEmail

功能说明：校验并登记一个邮箱到to/cc/bcc/reply分组

```text
public function addEmail($email, $name='', $type='to');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string；电子邮件地址 |
| `$name` | string；该邮箱的显示姓名，空串使用地址本身（默认：''） |
| `$type` | string；地址分组：to、cc、bcc或reply（默认：'to'） |

**返回值**：int 1表示地址有效并登记，0表示地址无效

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->addEmail($email, $name, $type);
```

定位：`lib/myEmail.class.php:224`。

### myEmail::to

功能说明：添加收件人

```text
public function to($email, $name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string\|array；单个邮箱地址，或邮箱列表/显示名到地址的映射 |
| `$name` | string；单个邮箱对应的显示姓名（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->to($email, $name);
```

定位：`lib/myEmail.class.php:244`。

### myEmail::cc

功能说明：添加抄送人

```text
public function cc($email, $name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string\|array；单个邮箱地址，或邮箱列表/显示名到地址的映射 |
| `$name` | string；单个邮箱对应的显示姓名（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->cc($email, $name);
```

定位：`lib/myEmail.class.php:262`。

### myEmail::bcc

功能说明：添加暗送人

```text
public function bcc($email, $name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string\|array；单个邮箱地址，或邮箱列表/显示名到地址的映射 |
| `$name` | string；单个邮箱对应的显示姓名（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->bcc($email, $name);
```

定位：`lib/myEmail.class.php:280`。

### myEmail::reply

功能说明：添加回复人

```text
public function reply($email, $name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string\|array；单个邮箱地址，或邮箱列表/显示名到地址的映射 |
| `$name` | string；单个邮箱对应的显示姓名（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->reply($email, $name);
```

定位：`lib/myEmail.class.php:298`。

### myEmail::formatEmail

功能说明：格式化邮件地址

```text
public function formatEmail($email_list);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email_list` | array；地址/姓名二元组或二元组列表 |

**返回值**：string|array 单个地址二元组返回MIME地址文本，地址列表返回文本数组，空列表返回空数组

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->formatEmail($email_list);
```

定位：`lib/myEmail.class.php:317`。

### myEmail::addHeader

功能说明：添加邮件头

```text
public function addHeader($name, $value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；邮件头字段名，会移除换行 |
| `$value` | string；邮件头字段值，会移除换行 |

**返回值**：true 移除名称/值中的换行并追加后固定返回true，不校验邮件头业务语义

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->addHeader($name, $value);
```

定位：`lib/myEmail.class.php:336`。

### myEmail::addAttachment

功能说明：添加附件

```text
public function addAttachment($filecontent, $filename = '', $embed=false, $filetype = 'application/octet-stream');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$filecontent` | string；附件的原始二进制内容 |
| `$filename` | string；附件展示名称或文件路径，按方法用途解释（默认：''） |
| `$embed` | bool；是否按邮件内嵌资源而非普通附件处理（默认：false） |
| `$filetype` | string；附件的MIME类型（默认：'application/octet-stream'） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 附件名称为空或包含路径/控制字符等非法内容

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->addAttachment($filecontent, $filename, $embed, $filetype);
```

定位：`lib/myEmail.class.php:357`。

### myEmail::addFile

功能说明：添加附件文件

```text
public function addFile($file, $filename='', $embed=false, $filetype = 'application/octet-stream');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$filename` | string；附件展示名称或文件路径，按方法用途解释（默认：''） |
| `$embed` | bool；是否按邮件内嵌资源而非普通附件处理（默认：false） |
| `$filetype` | string；附件的MIME类型（默认：'application/octet-stream'） |

**返回值**：bool 文件可读取并已登记为true；文件不存在或读取失败为false

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->addFile($file, $filename, $embed, $filetype);
```

定位：`lib/myEmail.class.php:388`。

### myEmail::setFile

功能说明：附件编码

```text
public function setFile($file, $embed = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | array；含content/name/type等字段的内部附件记录 |
| `$embed` | bool；是否按邮件内嵌资源而非普通附件处理（默认：false） |

**返回值**：string 含MIME类型、Base64正文及附件/CID头的邮件片段

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->setFile($file, $embed);
```

定位：`lib/myEmail.class.php:404`。

### myEmail::buildMail

功能说明：邮件编码

参数说明：无显式参数。

```text
public function buildMail();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 由当前地址、正文、附件及边界构建的完整邮件内容，不执行发送

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->buildMail();
```

定位：`lib/myEmail.class.php:427`。

### myEmail::send

功能说明：邮件发送

副作用：会连接SMTP并提交邮件；返回成功只表示SMTP接受该次提交，最终投递由邮件服务器处理。连接在finally中关闭。
SMTP配置：host必填；mode为smtp/ssl/ssl/tls/tls之一，tls表示STARTTLS；port默认按模式选25/465/587，user为空时不认证，password默认空串。

```text
public function send($para=array(), $single=false, $priority=3);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | array；SMTP连接配置，包含host以及认证/端口/TLS等已支持项（默认：array()） |
| `$single` | bool；是否逐个收件人单独发送（默认：false） |
| `$priority` | int；邮件优先级，按SMTP邮件头约定使用1至5（默认：3） |

**返回值**：array|false 成功执行流程后为逐收件人的状态文本数组；缺少发件人/收件人/主机或SMTP流程失败为false

调用形式：

```php
// $instance为已初始化的myEmail实例。
$result = $instance->send($para, $single, $priority);
```

定位：`lib/myEmail.class.php:465`。

### SMTP::__construct

功能说明：初始化SMTP端口、连接及错误状态

参数说明：无显式参数。

```text
public function __construct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEmail.class.php:595`。

### SMTP::Connect

功能说明：按主机、端口和超时时间建立SMTP套接字连接

If the port is not specified use the default SMTP_PORT.
If tval is specified then a connection will try and be
established with the server for that number of seconds.
If tval is not specified the default is 30 seconds to
try on the connection.

SMTP CODE SUCCESS: 220
SMTP CODE FAILURE: 421

```text
public function Connect($host, $port = 0, $tval = 30);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$host` | string；服务器主机名或地址 |
| `$port` | int；服务器端口，0使用该协议的默认端口（默认：0） |
| `$tval` | int；建立SMTP连接时的超时秒数（默认：30） |

**返回值**：bool 建立连接并取得成功问候响应为true，连接/握手失败为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Connect($host, $port, $tval);
```

定位：`lib/myEmail.class.php:625`。

### SMTP::StartTLS

功能说明：发送STARTTLS并将当前SMTP连接升级为TLS

SMTP CODE 220 Ready to start TLS
SMTP CODE 501 Syntax error (no parameters allowed)
SMTP CODE 454 TLS not available due to temporary reason

参数说明：无显式参数。

```text
public function StartTLS();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool success

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->StartTLS();
```

定位：`lib/myEmail.class.php:684`。

### SMTP::Authenticate

功能说明：使用用户名和密码执行SMTP LOGIN认证

Hello() method.  Returns true if successfully authenticated.

```text
public function Authenticate($username, $password);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$username` | string；认证用户名 |
| `$password` | string；认证密码 |

**返回值**：bool LOGIN认证各步骤成功为true，服务器拒绝或协议错误为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Authenticate($username, $password);
```

定位：`lib/myEmail.class.php:730`。

### SMTP::Connected

功能说明：检查SMTP套接字是否存在且尚未到达流末尾

参数说明：无显式参数。

```text
public function Connected();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 套接字存在且未结束为true；已断开时清理状态并返回false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Connected();
```

定位：`lib/myEmail.class.php:793`。

### SMTP::Close

功能说明：关闭SMTP套接字并清空连接状态

It is not considered good to use this function without
first trying to use QUIT.

参数说明：无显式参数。

```text
public function Close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Close();
```

定位：`lib/myEmail.class.php:820`。

### SMTP::Data

功能说明：发送DATA命令及完整邮件内容，处理行长和点转义

finializing the mail transaction. $msg_data is the message
that is to be send with the headers. Each header needs to be
on a single line followed by a <CRLF> with the message headers
and the message body being seperated by and additional <CRLF>.

Implements rfc 821: DATA <CRLF>

SMTP CODE INTERMEDIATE: 354
        [data]
        <CRLF>.<CRLF>
        SMTP CODE SUCCESS: 250
        SMTP CODE FAILURE: 552, 554, 451, 452
SMTP CODE FAILURE: 451, 554
SMTP CODE ERROR  : 500, 501, 503, 421

```text
public function Data($msg_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$msg_data` | string；已构建的完整MIME邮件内容 |

**返回值**：bool DATA正文被服务器接受为true，命令或提交响应失败为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Data($msg_data);
```

定位：`lib/myEmail.class.php:856`。

### SMTP::Hello

功能说明：优先发送EHLO，失败时回退HELO问候

This makes sure that we and the server are in
the same known state.

Implements from rfc 821: HELO <SP> <domain> <CRLF>

SMTP CODE SUCCESS: 250
SMTP CODE ERROR  : 500, 501, 504, 421

```text
public function Hello($host = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$host` | string；用于EHLO/HELO问候的客户端主机名，空串自动选择（默认：''） |

**返回值**：bool EHLO或回退的HELO成功为true，两者均失败为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Hello($host);
```

定位：`lib/myEmail.class.php:996`。

### SMTP::SendHello

功能说明：发送指定的EHLO/HELO问候并检查服务器响应

```text
private function SendHello($hello, $host);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$hello` | string；SMTP问候命令，通常为EHLO或HELO |
| `$host` | string；EHLO/HELO中声明的客户端主机名 |

**返回值**：bool 服务器接受指定问候命令为true，否则为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEmail.class.php:1030`。

### SMTP::Mail

功能说明：发送MAIL FROM以开始一轮邮件事务

$from. Returns true if successful or false otherwise. If True
the mail transaction is started and then one or more Recipient
commands may be called followed by a Data command.

Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>

SMTP CODE SUCCESS: 250
SMTP CODE SUCCESS: 552, 451, 452
SMTP CODE SUCCESS: 500, 501, 421

```text
public function Mail($from);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$from` | string；SMTP MAIL FROM信封发件人地址 |

**返回值**：bool 服务器接受MAIL FROM为true，否则为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Mail($from);
```

定位：`lib/myEmail.class.php:1073`。

### SMTP::Quit

功能说明：发送QUIT并按响应及参数决定是否关闭连接

if there is no error or the $close_on_error argument is true.

Implements from rfc 821: QUIT <CRLF>

SMTP CODE SUCCESS: 221
SMTP CODE ERROR  : 500

```text
public function Quit($close_on_error = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$close_on_error` | bool；QUIT失败时是否仍关闭连接（默认：true） |

**返回值**：bool QUIT响应成功为true；失败为false，是否仍关闭由close_on_error决定

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Quit($close_on_error);
```

定位：`lib/myEmail.class.php:1119`。

### SMTP::Recipient

功能说明：发送RCPT TO登记一个信封收件人

Returns true if the recipient was accepted false if it was rejected.

Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>

SMTP CODE SUCCESS: 250, 251
SMTP CODE FAILURE: 550, 551, 552, 553, 450, 451, 452
SMTP CODE ERROR  : 500, 501, 503, 421

```text
public function Recipient($to);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$to` | string；SMTP RCPT TO信封收件人地址 |

**返回值**：bool 服务器接受当前收件人为true，否则为false，不代表最终邮件投递成功

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Recipient($to);
```

定位：`lib/myEmail.class.php:1175`。

### SMTP::Reset

功能说明：发送RSET以放弃当前邮件事务

currently in progress. Returns true if successful false
otherwise.

Implements rfc 821: RSET <CRLF>

SMTP CODE SUCCESS: 250
SMTP CODE ERROR  : 500, 501, 504, 421

参数说明：无显式参数。

```text
public function Reset();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 服务器确认RSET为true，否则为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Reset();
```

定位：`lib/myEmail.class.php:1222`。

### SMTP::SendAndMail

功能说明：发送兼容SMTP的SAML FROM命令

$from. Returns true if successful or false otherwise. If True
the mail transaction is started and then one or more Recipient
commands may be called followed by a Data command. This command
will send the message to the users terminal if they are logged
in and send them an email.

Implements rfc 821: SAML <SP> FROM:<reverse-path> <CRLF>

SMTP CODE SUCCESS: 250
SMTP CODE SUCCESS: 552, 451, 452
SMTP CODE SUCCESS: 500, 501, 502, 421

```text
public function SendAndMail($from);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$from` | string；SMTP MAIL FROM信封发件人地址 |

**返回值**：bool 服务器接受SAML FROM为true，否则为false

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->SendAndMail($from);
```

定位：`lib/myEmail.class.php:1273`。

### SMTP::Turn

功能说明：记录TURN未实现错误并返回false

support. This method is here to make the RFC821 Definition
complete for this class and __may__ be implimented in the future

Implements from rfc 821: TURN <CRLF>

SMTP CODE SUCCESS: 250
SMTP CODE FAILURE: 502
SMTP CODE ERROR  : 500, 503

参数说明：无显式参数。

```text
public function Turn();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：false 固定返回false，同时记录未实现错误

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->Turn();
```

定位：`lib/myEmail.class.php:1321`。

### SMTP::getError

功能说明：取得最近一次SMTP错误结构

参数说明：无显式参数。

```text
public function getError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|null 最近错误字段组成的数组；无错误时为null

调用形式：

```php
// $instance为已初始化的SMTP实例。
$result = $instance->getError();
```

定位：`lib/myEmail.class.php:1338`。

### SMTP::get_lines

功能说明：读取SMTP多行响应，直到最后一行或连接结束

either before eof or socket timeout occurs on the operation.
With SMTP we can tell if we have more lines to read if the
4th character is '-' symbol. If it is a space then we don't
need to read anything else.

参数说明：无显式参数。

```text
private function get_lines();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 读到的SMTP响应原文，可能为空或在超时/断开时只包含部分行

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEmail.class.php:1360`。
