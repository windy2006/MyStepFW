# 邮件发送

## 本篇目标与调用场景

buildMail只组装内容；send才涉及传输。地址、头部及附件检查不等于投递成功，SMTP凭据不得公开。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myEmail::init

功能说明：参数初始化

```text
public function init($from='', $charset='UTF-8');
```

| 参数说明 |
| --- |
| string $from 源文件路径或发送方地址，按对应入口解释（默认：''） |
| string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'UTF-8'） |

**返回**：void 无返回值。

### myEmail::setSubject

功能说明：设置邮件主题

```text
public function setSubject($subject);
```

| 参数说明 |
| --- |
| string $subject 邮件主题文本 |

**返回**：void 无返回值。

### myEmail::setFrom

功能说明：设置邮件发送人

```text
public function setFrom($email, $name='', $auto=true);
```

| 参数说明 |
| --- |
| string $email 合法的发件人邮箱地址 |
| string $name 发件人显示姓名（默认：''） |
| bool $auto 是否同时添加为reply地址（默认：true） |

**返回**：bool 邮箱有效并登记为true，否则为false；auto为true时还登记回复地址

### myEmail::setContent

功能说明：设置邮件内容

```text
public function setContent($content, $isHtml=true, $body_alt='');
```

| 参数说明 |
| --- |
| string $content 待处理或输出的内容字节串 |
| bool $isHtml 正文是否按HTML格式构建（默认：true） |
| string $body_alt HTML邮件对应的纯文本正文，空串不单独设置（默认：''） |

**返回**：void 无返回值。

### myEmail::addEmail

功能说明：校验并登记一个邮箱到to/cc/bcc/reply分组

```text
public function addEmail($email, $name='', $type='to');
```

| 参数说明 |
| --- |
| string $email 电子邮件地址 |
| string $name 该邮箱的显示姓名，空串使用地址本身（默认：''） |
| string $type 地址分组：to、cc、bcc或reply（默认：'to'） |

**返回**：int 1表示地址有效并登记，0表示地址无效

### myEmail::to

功能说明：添加收件人

```text
public function to($email, $name = '');
```

| 参数说明 |
| --- |
| string\|array $email 单个邮箱地址，或邮箱列表/显示名到地址的映射 |
| string $name 单个邮箱对应的显示姓名（默认：''） |

**返回**：void 无返回值。

## 明确调用示例

```php
$mail = new myEmail('sender@example.test');
$mail->setSubject('通知');
$mail->to('reader@example.test', '读者');
$mail->setContent('Hello', false);
$mime = $mail->buildMail();
// 配置了测试SMTP时才执行send；本示例不会向收件人实际发送。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
