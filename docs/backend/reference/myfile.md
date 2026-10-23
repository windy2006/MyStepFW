# myFile：本地文件、路径和远程读取

修订日期：2026-10-23。功能详解。

## 职责与调用位置

只在受控目录操作；resolveWithin仅返回根内已存在文件；网络方法保持TLS验证，超时及响应大小由业务配套。

源码：`lib/myFile.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// $directory是测试进程独占的临时目录。
$file = $directory.'/note.txt';
myFile::saveFile($file, 'Hello');
assert(myFile::getLocal($file) === 'Hello');
assert(myFile::resolveWithin($directory, 'note.txt') === realpath($file));
assert(myFile::resolveWithin($directory, '../outside.txt') === false);
assert((int)myFile::getByte('8M') === 8388608);
```

## 声明与类职责

### myFile

功能说明：提供本地读写/移动/遍历、路径边界校验、HTTP读取、TLS配置及受限公开图片导入。

使用说明：
  1. 本地操作优先明确传入受信路径；上传/解包路径使用边界检查入口。
  2. 普通远程读取与受限公开资源读取用途不同；后者执行地址和体积约束。
  3. getLocal使用字节长度/偏移，find/getTree的递归和过滤规则见方法说明。

使用示例：
    $text = myFile::getLocal($path);
    $ok = myFile::saveFile($path, $text) !== false;
    $file = myFile::resolveWithin($publicRoot, $relativeName);

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $func_alias = array(
            'rt' => 'rootPath',
            'p' => 'realPath',
            'd' => 'mkdir',
            'mime' => 'getMime',
            'c' => 'copy',
            'r' => 'rename',
            'm' => 'rename',
            's' => 'saveFile',
            'g' => 'getLocal',
            'get' => 'getLocal',
            'url' => 'getRemote',
            'grab' => 'getRemoteFile',
        );
```

```text
public
        $file = '',
        $remote = false,
        $exist = true,
        $headers = array(),
        $info = array(),
        $content = '',
        $context = null,
        $func_str = array('myFile', 'get');
```

```text
protected static $tls_options = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myFile::__construct` | 构造函数 |
| `myFile::__get` | 内部变量读取 |
| `myFile::__toString` | 字符串显示 |
| `myFile::buildContext` | 构建远程连接配置 |
| `myFile::rootPath` | 获取网站根的绝对路径 |
| `myFile::resolveWithin` | 定位受信根目录内已存在的文件，拒绝路径穿越和符号链接越界 |
| `myFile::realPath` | 获取文件真实绝对路径或相对于根的路径 |
| `myFile::mkdir` | 建立目录，带递归和容错 |
| `myFile::getMime` | 获取文件类型 |
| `myFile::getAttrib` | 获取文件属性 |
| `myFile::setAttrib` | 设置文件属性 |
| `myFile::getHeader` | 获取远程连接头信息 |
| `myFile::getSize` | 获取文件大小 |
| `myFile::formatSize` | 容量格式化 |
| `myFile::getByte` | 获取容量字节数 |
| `myFile::copy` | 复制文件，可为多重目录 |
| `myFile::del` | 删除文件，可为多重目录 |
| `myFile::rename` | 重命名文件或目录 |
| `myFile::move` | 移动文件或目录 |
| `myFile::save` | 保存实例文件 |
| `myFile::saveFile` | 保存文件 |
| `myFile::get` | 获取文件（本地或远程） |
| `myFile::getLocal` | 获取本地文件（可为部分） |
| `myFile::setTLSOptions` | 设置可信CA文件/目录，保留证书和主机名验证 |
| `myFile::tlsOptions` | 返回强制启用证书及主机校验的流TLS选项 |
| `myFile::getRemote` | 按可用扩展选择cURL或流方式读取HTTP资源 |
| `myFile::getRemote_stream` | 使用验证TLS的流读取HTTP响应并去除UTF-8 BOM |
| `myFile::remoteRequest` | 规范HTTP方法及表单请求体，GET数据并入查询串 |
| `myFile::getRemote_curl` | 使用cURL读取HTTP正文并输出实际请求头；无cURL时回退流读取 |
| `myFile::show` | 输出内容 |
| `myFile::find` | 文件搜索（支持通配符） |
| `myFile::gzdecode` | 解压数据 |
| `myFile::getRemoteFile` | 获取远程文件 |
| `myFile::judgeChild` | 判断是否存在子目录 |
| `myFile::getTree` | 获取文件树 |
| `myFile::removeBom` | 去除UTF-8文件头 |
| `myFile::rewritable` | 检测某文件或目录是否可写 |
| `myFile::extractionPath` | 验证归档成员并生成根目录内的发布路径，拒绝符号链接 |
| `myFile::getRemotePublic` | 固定到经检查的公网地址读取有限大小内容，不跟随重定向 |
| `myFile::publicAddresses` | 解析主机的IPv4/IPv6地址并去除重复值 |
| `myFile::publicAddress` | 判断单个IP是否可用作受限公网资源读取目标 |
| `myFile::importPublicImage` | 下载受限公网位图，按实际图像格式确定后缀并保存 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myFile::__construct

功能说明：构造函数

```text
public function __construct($file, $headers=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；本地文件路径或远程URL；远程资源按协议识别 |
| `$headers` | array；HTTP头映射/列表；流上下文还支持method、data和append选项（默认：array()） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:133`。

### myFile::__get

功能说明：内部变量读取

```text
public function __get($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；已保存的文件信息或属性名称 |

**返回值**：mixed 文件属性/信息映射中的原值；键不存在时为null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:173`。

### myFile::__toString

功能说明：字符串显示

参数说明：无显式参数。

```text
public function __toString();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前文件内容文本；读取行为与get相同

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:188`。

### myFile::buildContext

功能说明：构建远程连接配置


资源所有权：返回的是流上下文，不是已打开的网络连接；无需把它作为文件流传给fclose。

```text
public static function buildContext($url, $headers=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$headers` | array；HTTP头映射/列表；流上下文还支持method、data和append选项（默认：array()） |

**返回值**：resource 新建的stream-context资源，可交给支持context的流函数

**异常与失败**：InvalidArgumentException 请求头名称或值包含CR/LF

调用形式：

```php
$result = myFile::buildContext($url, $headers);
```

定位：`lib/myFile.class.php:203`。

### myFile::rootPath

功能说明：获取网站根的绝对路径

```text
public static function rootPath($mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | bool；true使用当前操作系统分隔符，false统一使用/（默认：false） |

**返回值**：string 网站根目录的绝对路径，使用所选分隔符

调用形式：

```php
$result = myFile::rootPath($mode);
```

定位：`lib/myFile.class.php:246`。

### myFile::resolveWithin

功能说明：定位受信根目录内已存在的文件，拒绝路径穿越和符号链接越界

```text
public static function resolveWithin($root, $relative);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$root` | string；操作限制在其中的受信根目录 |
| `$relative` | string；相对于受信根目录的待解析路径 |

**返回值**：string|false 根目录内真实文件路径；不存在、越界或非法输入为false

调用形式：

```php
$result = myFile::resolveWithin($root, $relative);
```

定位：`lib/myFile.class.php:260`。

### myFile::realPath

功能说明：获取文件真实绝对路径或相对于根的路径

声明ROOT时相对路径固定从网站根解析，与工作目录和调用文件无关；独立使用类库时保留历史规则。本入口不是访问权限检查。读取公开文件或解包时使用resolveWithin/extractionPath。

```text
public static function realPath($file, $relative = false, $mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$relative` | bool；true返回相对于站点根的路径，false返回完整路径（默认：false） |
| `$mode` | bool；true使用操作系统分隔符，false使用统一/分隔符（默认：false） |

**返回值**：string 规范化后的绝对路径或站点相对路径；不保证文件存在

**相关入口**：myFile::resolveWithin() 已存在公开文件的根目录边界检查；myFile::extractionPath() 归档成员路径校验

调用形式：

```php
$result = myFile::realPath($file, $relative, $mode);
```

定位：`lib/myFile.class.php:285`。

### myFile::mkdir

功能说明：建立目录，带递归和容错

```text
public static function mkdir($dir);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；目标目录 |

**返回值**：bool 目录已存在或递归创建成功为true，创建失败为false

调用形式：

```php
$result = myFile::mkdir($dir);
```

定位：`lib/myFile.class.php:344`。

### myFile::getMime

功能说明：获取文件类型


兼容行为：未知扩展名可能直接作为返回文本，而非application/octet-stream；此入口不能仅靠返回值证明文件真实内容格式。

```text
public static function getMime($file='', $use_finfo = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径（默认：''） |
| `$use_finfo` | bool；是否优先使用fileinfo检测实际MIME类型（默认：false） |

**返回值**：string|false 已知扩展名返回MIME文本；未知扩展名沿用原扩展名；不是普通文件或finfo失败为false

调用形式：

```php
$result = myFile::getMime($file, $use_finfo);
```

定位：`lib/myFile.class.php:378`。

### myFile::getAttrib

功能说明：获取文件属性

```text
public static function getAttrib($file, $show_txt = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$show_txt` | bool；true返回rwx文本权限，false返回三位八进制权限文本（默认：false） |

**返回值**：string|false 三位八进制权限文本或九位rwx文本；文件不存在为false

调用形式：

```php
$result = myFile::getAttrib($file, $show_txt);
```

定位：`lib/myFile.class.php:477`。

### myFile::setAttrib

功能说明：设置文件属性

```text
public static function setAttrib($file, $attrib);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$attrib` | int；chmod使用的权限位，例如0644 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myFile::setAttrib($file, $attrib);
```

定位：`lib/myFile.class.php:502`。

### myFile::getHeader

功能说明：获取远程连接头信息

```text
public static function getHeader($url, $mode = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$mode` | bool；true按响应头名分组，false返回原始头列表（默认：true） |

**返回值**：array|false HEAD响应头；mode为true按小写头名组织，false保留数字行列表；失败为false

调用形式：

```php
$result = myFile::getHeader($url, $mode);
```

定位：`lib/myFile.class.php:517`。

### myFile::getSize

功能说明：获取文件大小

```text
public static function getSize($file, $format = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$format` | bool；true格式化为KB/MB等文本，false保留字节计数（默认：true） |

**返回值**：int|float|string|false format为false返回文件/递归目录字节数，为true返回容量文本；不存在为0或0B，filesize底层失败可为false

调用形式：

```php
$result = myFile::getSize($file, $format);
```

定位：`lib/myFile.class.php:532`。

### myFile::formatSize

功能说明：容量格式化

```text
public static function formatSize($size, $precision=2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$size` | int；字节数量 |
| `$precision` | int；格式化时保留的小数位数（默认：2） |

**返回值**：string 含B/KB/MB等单位的容量文本，按precision保留小数

调用形式：

```php
$result = myFile::formatSize($size, $precision);
```

定位：`lib/myFile.class.php:553`。

### myFile::getByte

功能说明：获取容量字节数

```text
public static function getByte($size);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$size` | int\|float\|string；原始字节数或含K/M/G等容量单位的文本 |

**返回值**：float|int 按1024进位换算的字节数；不符合数值/容量单位格式时返回0

调用形式：

```php
$result = myFile::getByte($size);
```

定位：`lib/myFile.class.php:570`。

### myFile::copy

功能说明：复制文件，可为多重目录

```text
public static function copy($source, $dest, $overwrite = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$source` | string；源文件或源目录路径 |
| `$dest` | string；目标文件或目标目录路径 |
| `$overwrite` | bool；目标已存在时是否允许覆盖（默认：false） |

**返回值**：bool 复制结果；目录递归失败时可能已有部分目标文件，不提供事务回滚

调用形式：

```php
$result = myFile::copy($source, $dest, $overwrite);
```

定位：`lib/myFile.class.php:584`。

### myFile::del

功能说明：删除文件，可为多重目录

```text
public static function del($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |

**返回值**：bool 删除文件/目录的操作结果；递归删除不具备整体回滚能力

调用形式：

```php
$result = myFile::del($file);
```

定位：`lib/myFile.class.php:641`。

### myFile::rename

功能说明：重命名文件或目录

```text
public static function rename($file, $newname);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$newname` | string；新文件名或完整目标路径 |

**返回值**：bool 底层重命名成功为true，目标冲突或失败为false

调用形式：

```php
$result = myFile::rename($file, $newname);
```

定位：`lib/myFile.class.php:670`。

### myFile::move

功能说明：移动文件或目录

```text
public static function move($file, $newname);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$newname` | string；新文件名或完整目标路径 |

**返回值**：bool 与rename相同，表示移动是否成功

调用形式：

```php
$result = myFile::move($file, $newname);
```

定位：`lib/myFile.class.php:695`。

### myFile::save

功能说明：保存实例文件

```text
public function save($content = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；待处理或输出的内容字节串（默认：''） |

**返回值**：int|false 成功写入的字节数，包括0；远程文件或写入失败为false

调用形式：

```php
// $instance为已初始化的myFile实例。
$result = $instance->save($content);
```

定位：`lib/myFile.class.php:705`。

### myFile::saveFile

功能说明：保存文件


调用示例：
    $written = myFile::saveFile($file, '');
    if ($written === false) {
        // 保存失败；0字节也可能是成功
    }

```text
public static function saveFile($file, $content='', $mode='wb');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$content` | string；待处理或输出的内容字节串（默认：''） |
| `$mode` | string；fopen写入模式，例如w/a，按调用方期望覆盖或追加（默认：'wb'） |

**返回值**：int|false 成功写入的字节数，包括0；创建目录、加锁或写入失败为false

调用形式：

```php
$result = myFile::saveFile($file, $content, $mode);
```

定位：`lib/myFile.class.php:726`。

### myFile::get

功能说明：获取文件（本地或远程）

参数说明：无显式参数。

```text
public function get();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string|false 当前文件正文；远程读取失败通常为空串，本地不可用按读取入口约定返回

调用形式：

```php
// $instance为已初始化的myFile实例。
$result = $instance->get();
```

定位：`lib/myFile.class.php:747`。

### myFile::getLocal

功能说明：获取本地文件（可为部分）


length=0且offset=0时读全文件；存在偏移且length=0时读取8192字节。长度/偏移采用字节，不是字符位置。

调用示例：
    $chunk = myFile::getLocal($file, 4096, 1024); // 从1024字节偏移读4096字节

```text
public static function getLocal($file, $length=0, $offset=0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$length` | int；非负读取字节数；length与offset均为0时读全文件；有偏移且length为0时读8192字节（默认：0） |
| `$offset` | int；开始读取的字节偏移（默认：0） |

**返回值**：string 去除UTF-8 BOM后的文件内容；不存在/读取失败时为空串，空正文也为空串

调用形式：

```php
$result = myFile::getLocal($file, $length, $offset);
```

定位：`lib/myFile.class.php:774`。

### myFile::setTLSOptions

功能说明：设置可信CA文件/目录，保留证书和主机名验证

```text
public static function setTLSOptions(array $options);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$options` | array；仅允许cafile/capath，值须为可读路径；证书/主机校验始终开启 |

**返回值**：void 只更新静态TLS配置，不返回业务值

**异常与失败**：InvalidArgumentException 配置键不是cafile/capath，或指定路径不可读

调用形式：

```php
$result = myFile::setTLSOptions($options);
```

定位：`lib/myFile.class.php:798`。

### myFile::tlsOptions

功能说明：返回强制启用证书及主机校验的流TLS选项

参数说明：无显式参数。

```text
protected static function tlsOptions();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 用于流上下文的TLS选项，始终验证证书及主机名

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:816`。

### myFile::getRemote

功能说明：按可用扩展选择cURL或流方式读取HTTP资源

仅HTTP/HTTPS，成功状态要求200；不跟随重定向。失败与合法空正文均为空串。

```text
public static function getRemote($url, $header=array(), $data='', $method='GET', $timeout=10);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$header` | array；附加HTTP请求头列表（默认：array()） |
| `$data` | array\|string；请求体；数组按当前实现编码为表单内容（默认：''） |
| `$method` | string；GET/POST等HTTP请求方法（默认：'GET'） |
| `$timeout` | int\|float；最长等待/执行时间，单位秒（默认：10） |

**返回值**：string 响应正文，失败或空响应为兼容约定的空串

**异常与失败**：InvalidArgumentException 请求方法不支持或请求头非法；传输失败仍按空串返回约定处理

调用形式：

```php
$result = myFile::getRemote($url, $header, $data, $method, $timeout);
```

定位：`lib/myFile.class.php:833`。

### myFile::getRemote_stream

功能说明：使用验证TLS的流读取HTTP响应并去除UTF-8 BOM

状态要求200且不跟随重定向；HTTP方法由流上下文发送。

```text
public static function getRemote_stream($url, $header=array(), $data='', $method='GET', $timeout=10);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$header` | array；HTTP头映射/列表；最终流上下文由buildContext建立（默认：array()） |
| `$data` | array\|string；请求体；数组按当前实现编码为表单内容（默认：''） |
| `$method` | string；GET/POST等HTTP请求方法（默认：'GET'） |
| `$timeout` | int\|float；最长等待/执行时间，单位秒（默认：10） |

**返回值**：string 去除UTF-8 BOM后的正文，失败返回空串

**异常与失败**：InvalidArgumentException 请求方法不支持或请求头非法；传输失败仍按空串返回约定处理

调用形式：

```php
$result = myFile::getRemote_stream($url, $header, $data, $method, $timeout);
```

定位：`lib/myFile.class.php:851`。

### myFile::remoteRequest

功能说明：规范HTTP方法及表单请求体，GET数据并入查询串

```text
protected static function remoteRequest($url, $data, $method);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$data` | array\|string；请求体；数组按当前实现编码为表单内容 |
| `$method` | string；GET/POST等HTTP请求方法 |

**返回值**：array 规范化的[url,body,method]三元组

**异常与失败**：InvalidArgumentException HTTP方法不是GET或POST

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:873`。

### myFile::getRemote_curl

功能说明：使用cURL读取HTTP正文并输出实际请求头；无cURL时回退流读取

return_header是历史参数名，实际输出发出的请求头，不是响应头。仅支持GET/POST，其他方法在规范化阶段抛InvalidArgumentException。

```text
public static function getRemote_curl($url, $header=array(), $data='', &$return_header=array(), $method=null, $timeout=30);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$header` | array；附加HTTP请求头列表（默认：array()） |
| `$data` | array\|string；请求体；数组按当前实现编码为表单内容（默认：''） |
| `&$return_header` | string\|array；引用输出：cURL实际发出的请求头文本；流回退或提前失败时不改写（默认：array()） |
| `$method` | string\|null；仅支持GET/POST；null按请求体是否为空选择，其他方法由remoteRequest拒绝（默认：null） |
| `$timeout` | int\|float；最长等待/执行时间，单位秒（默认：30） |

**返回值**：string 状态200时的正文；失败为空串，return_header输出请求头

**异常与失败**：InvalidArgumentException HTTP方法不支持或请求头含CR/LF

调用形式：

```php
$result = myFile::getRemote_curl($url, $header, $data, $return_header, $method, $timeout);
```

定位：`lib/myFile.class.php:900`。

### myFile::show

功能说明：输出内容

```text
public static function show($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；待处理或输出的内容字节串 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myFile::show($content);
```

定位：`lib/myFile.class.php:944`。

### myFile::find

功能说明：文件搜索（支持通配符）

```text
public static function find($filter='', $dir='./', $recursive=false, $mode = 3);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$filter` | string\|array；文件名glob通配模式或模式数组；纯单词自动包含匹配，空串匹配全部（默认：''） |
| `$dir` | string；目标目录（默认：'./'） |
| `$recursive` | bool；是否递归处理子目录（默认：false） |
| `$mode` | int；类型位：1目录、2文件、3两者；对应DIR/FILE/BOTH常量（默认：3） |

**返回值**：array 匹配的完整路径列表，目录项带末尾斜杠；不存在时为空数组

调用形式：

```php
$result = myFile::find($filter, $dir, $recursive, $mode);
```

定位：`lib/myFile.class.php:960`。

### myFile::gzdecode

功能说明：解压数据

```text
public static function gzdecode($data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | string；gzip封装的压缩字节串 |

**返回值**：string|false 解压后的原始数据；gzip头或解压校验失败为false

调用形式：

```php
$result = myFile::gzdecode($data);
```

定位：`lib/myFile.class.php:997`。

### myFile::getRemoteFile

功能说明：获取远程文件

```text
public static function getRemoteFile($remote_file, $local_file='./');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$remote_file` | string；要下载的远程资源URL |
| `$local_file` | string；下载内容保存到的本地文件路径（默认：'./'） |

**返回值**：bool 取得非空内容并保存成功为true，否则为false

调用形式：

```php
$result = myFile::getRemoteFile($remote_file, $local_file);
```

定位：`lib/myFile.class.php:1010`。

### myFile::judgeChild

功能说明：判断是否存在子目录

```text
public static function judgeChild($dir, $only_dir = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；目标目录 |
| `$only_dir` | bool；是否只检查子目录而忽略普通文件（默认：true） |

**返回值**：bool 存在符合only_dir筛选的子项为true，否则为false

调用形式：

```php
$result = myFile::judgeChild($dir, $only_dir);
```

定位：`lib/myFile.class.php:1024`。

### myFile::getTree

功能说明：获取文件树


记录以文件/目录名为键，每项含name、格式化size、rwx权限attr及Y/m/d H:i:s时间time；目录size为---，递归时仅非链接目录增加sub子树。

```text
public static function getTree($dir='./', $recursion = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；目标目录（默认：'./'） |
| `$recursion` | bool；是否递归构建下级目录树（默认：false） |

**返回值**：array 目录/文件树记录列表；字段及递归结构见方法中的结构说明

调用形式：

```php
$result = myFile::getTree($dir, $recursion);
```

定位：`lib/myFile.class.php:1046`。

### myFile::removeBom

功能说明：去除UTF-8文件头

```text
public static function removeBom($str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |

**返回值**：string 移除开头UTF-8 BOM后的字节串；其余字节原样保留

调用形式：

```php
$result = myFile::removeBom($str);
```

定位：`lib/myFile.class.php:1073`。

### myFile::rewritable

功能说明：检测某文件或目录是否可写

```text
public static function rewritable($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |

**返回值**：bool 目标文件或其目录可写为true，否则为false

调用形式：

```php
$result = myFile::rewritable($file);
```

定位：`lib/myFile.class.php:1084`。

### myFile::extractionPath

功能说明：验证归档成员并生成根目录内的发布路径，拒绝符号链接

```text
public static function extractionPath($root, $name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$root` | string；操作限制在其中的受信根目录 |
| `$name` | string；相对归档成员名；拒绝绝对路径、上级跳转及已有符号链接 |

**返回值**：string|false 合法成员的目标路径；非法路径或符号链接为false

调用形式：

```php
$result = myFile::extractionPath($root, $name);
```

定位：`lib/myFile.class.php:1115`。

### myFile::getRemotePublic

功能说明：固定到经检查的公网地址读取有限大小内容，不跟随重定向

```text
public static function getRemotePublic($url, $max_bytes = 10485760, $timeout = 10);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$max_bytes` | int；最大允许读取的字节数（默认：10485760） |
| `$timeout` | int\|float；最长等待/执行时间，单位秒（默认：10） |

**返回值**：string|false 2xx响应正文；解析、地址、大小、传输或状态校验失败为false

调用形式：

```php
$result = myFile::getRemotePublic($url, $max_bytes, $timeout);
```

定位：`lib/myFile.class.php:1142`。

### myFile::publicAddresses

功能说明：解析主机的IPv4/IPv6地址并去除重复值

```text
protected static function publicAddresses($host);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$host` | string；服务器主机名或地址 |

**返回值**：array 不重复的IP地址列表；解析失败为空数组

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:1175`。

### myFile::publicAddress

功能说明：判断单个IP是否可用作受限公网资源读取目标

```text
protected static function publicAddress($ip);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$ip` | string；待检查的IPv4或IPv6地址 |

**返回值**：bool 符合当前公网目标限制为true

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myFile.class.php:1187`。

### myFile::importPublicImage

功能说明：下载受限公网位图，按实际图像格式确定后缀并保存

```text
public static function importPublicImage($url, $directory);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `$directory` | string；受信的图片保存目录 |

**返回值**：string|false 已保存附件的存储文件名，不含目录；失败为false

调用形式：

```php
$result = myFile::importPublicImage($url, $directory);
```

定位：`lib/myFile.class.php:1212`。

