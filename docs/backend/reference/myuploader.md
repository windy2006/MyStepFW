# myUploader：HTTP上传

修订日期：2026-10-23。功能详解。

## 职责与调用位置

真实上传需浏览器multipart请求；结果数组逐项报告成功或失败。允许后缀、容量、鉴权及附件登记分别处理。

源码：`lib/myUploader.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 已鉴权、通过CSRF的上传处理器中，$directory为本业务目标目录。
$uploader = new myUploader($directory, true);
$uploader->setLimits(8 * 1024 * 1024, ['jpg', 'png']);
$uploader->do();
$results = $uploader->result();
// 检查每一项结果后，再按业务需要登记附件；不要只检查数组非空。
```

## 声明与类职责

### myUploader

功能说明：遍历单个/嵌套上传字段，校验真实上传、大小/扩展名并生成落盘结果。

使用说明：
  1. 配置目标目录、改名策略和扩展名规则后执行do，再读取result。
  2. 危险后缀会被隔离为upload形式；具体业务仍应设置允许扩展名。
  3. 本类完成上传落盘，附件可下载索引由myAttachment/框架上传入口登记。

使用示例：
    $upload = new myUploader($directory, true);
    $upload->setLimits(8388608, ['jpg', 'png']);
    $upload->do(); $result = $upload->result();

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public
        $result = array();
```

```text
protected
        $path = '',
        $counter = 0,
        $rename = false,
        $banlst = ['php','exe','com','bat','pif'];
```

```text
protected $max_bytes = 33554432, $allowed = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myUploader::setLimits` | 设置单文件上传大小及允许的扩展名 |
| `myUploader::init` | 初始化上传目录、改名策略和禁止扩展名列表 |
| `myUploader::setBan` | 追加禁止扩展名，合并到内置隔离规则 |
| `myUploader::do` | 遍历单个或嵌套上传字段，逐项上传并收集结果 |
| `myUploader::uploadFile` | 校验当前上传项并落盘，写入本项成功或失败结果 |
| `myUploader::result` | 取得结构化上传结果或HTML结果表 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myUploader::setLimits

功能说明：设置单文件上传大小及允许的扩展名

```text
public function setLimits($bytes = 33554432, $allowed = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$bytes` | int；最大允许处理的字节数，必须为正整数（默认：33554432） |
| `$allowed` | array\|string；允许上传的扩展名列表或逗号分隔文本（默认：[]） |

**返回值**：$this 返回当前上传器，便于连续设置

**异常与失败**：InvalidArgumentException 大小上限非法或扩展名列表类型不正确

调用形式：

```php
// $instance为已初始化的myUploader实例。
$result = $instance->setLimits($bytes, $allowed);
```

定位：`lib/myUploader.class.php:73`。

### myUploader::init

功能说明：初始化上传目录、改名策略和禁止扩展名列表

```text
public function init($path = '', $rename = false, $banlst=[]);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；目标目录或路径（默认：''） |
| `$rename` | bool；是否为上传文件生成新的唯一名称（默认：false） |
| `$banlst` | array；附加禁止上传的扩展名列表（默认：[]） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myUploader实例。
$result = $instance->init($path, $rename, $banlst);
```

定位：`lib/myUploader.class.php:89`。

### myUploader::setBan

功能说明：追加禁止扩展名，合并到内置隔离规则

```text
public function setBan($list);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$list` | array\|string；追加禁止扩展名数组或逗号分隔文本 |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myUploader实例。
$result = $instance->setBan($list);
```

定位：`lib/myUploader.class.php:109`。

### myUploader::do

功能说明：遍历单个或嵌套上传字段，逐项上传并收集结果

```text
public function do($getsize = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$getsize` | bool；结果中的size是否格式化为可读容量（默认：true） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myUploader实例。
$result = $instance->do($getsize);
```

定位：`lib/myUploader.class.php:125`。

### myUploader::uploadFile

功能说明：校验当前上传项并落盘，写入本项成功或失败结果

参数说明：无显式参数。

```text
private function uploadFile();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myUploader.class.php:158`。

### myUploader::result

功能说明：取得结构化上传结果或HTML结果表


调用示例：
    $uploader->do();
    foreach ($uploader->result() as $item) {
        // 按error/message/new_name处理各项
    }

```text
public function result($html = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$html` | bool；true返回HTML结果表，false返回结构化上传结果数组（默认：false） |

**返回值**：array|string html为false返回结果数组，为true返回HTML表格

调用形式：

```php
// $instance为已初始化的myUploader实例。
$result = $instance->result($html);
```

定位：`lib/myUploader.class.php:258`。

