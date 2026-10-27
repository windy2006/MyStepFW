# myException：错误处理与日志

## 职责与调用位置

启动期及完整应用使用不同初始化设置；异常显示由debug和回调控制，不能在API正文中输出调试堆栈。

源码：`lib/myException.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
try {
    throw new InvalidArgumentException('编号无效');
} catch (InvalidArgumentException $error) {
    $message = $error->getMessage(); // 对用户展示稳定的业务提示。
}
// 内部错误由框架既有异常处理器记录，不在每个模块重跑init。
```

## 声明与类职责

### myException

功能说明：登记PHP错误/异常/关闭回调，控制详情显示、日志输出及开发调试页面。

使用说明：
  1. 由框架启动时配置init，生产环境关闭详细输出。
  2. 日志与页面包含的错误上下文按配置筛选；不要通过公开页面暴露敏感调试信息。
  3. 错误页依赖vendor/highlight高亮资源；show/日志清理等入口遵循当前静态状态。

补充说明：
程序错误处理

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $err_all = array(),
        $err_last = array(),
        $callback = array('myException', 'show'),
        $callback_type = E_ERROR|E_CORE_ERROR|E_CORE_WARNING|E_USER_ERROR,
        $log_type = E_ALL ^ E_NOTICE,
        $log_file = 'error.log',
        $log_mode = 0,
        $exit_on_error = true;
```

```text
public static $display_details = false;
```

```text
protected static $shutdown_registered = false;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myException::__construct` | 构造函数 |
| `myException::init` | 合并错误处理选项并登记错误、异常及关闭阶段回调 |
| `myException::errorHandle` | 错误信息处理 |
| `myException::exceptionHandle` | 抛出异常处理 |
| `myException::shutdownCheck` | 程序终了错误检测 |
| `myException::log` | 错误记录 |
| `myException::clear` | 错误清除 |
| `myException::check` | 错误检测 |
| `myException::show` | 错误显示 |
| `myException::getLines` | 获取某段程序代码 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myException::__construct

功能说明：构造函数

```text
public function __construct($err_msg, $code=0, $err_no=E_USER_ERROR, $err_file='', $err_line='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$err_msg` | string；错误或异常消息 |
| `$code` | int；传递给父异常对象的错误代码（默认：0） |
| `$err_no` | int；PHP错误级别或错误编号（默认：E_USER_ERROR） |
| `$err_file` | string；错误发生的源文件路径（默认：''） |
| `$err_line` | int；错误源文件行号（默认：''） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myException.class.php:88`。

### myException::init

功能说明：合并错误处理选项并登记错误、异常及关闭阶段回调

```text
public static function init($options=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$options` | array；功能配置选项（默认：array()） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myException::init($options);
```

定位：`lib/myException.class.php:119`。

### myException::errorHandle

功能说明：错误信息处理

```text
public static function errorHandle($err_no, $err_msg, $err_file, $err_line);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$err_no` | int；PHP错误级别或错误编号 |
| `$err_msg` | string；错误或异常消息 |
| `$err_file` | string；错误发生的源文件路径 |
| `$err_line` | int；错误源文件行号 |

**返回值**：false|null 被忽略的错误返回false，已处理分支无返回值；配置可要求终止请求

调用形式：

```php
$result = myException::errorHandle($err_no, $err_msg, $err_file, $err_line);
```

定位：`lib/myException.class.php:156`。

### myException::exceptionHandle

功能说明：抛出异常处理

```text
public static function exceptionHandle($e);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$e` | Throwable；未捕获的异常对象 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myException::exceptionHandle($e);
```

定位：`lib/myException.class.php:234`。

### myException::shutdownCheck

功能说明：程序终了错误检测

参数说明：无显式参数。

```text
public static function shutdownCheck();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = myException::shutdownCheck();
```

定位：`lib/myException.class.php:247`。

### myException::log

功能说明：错误记录

参数说明：无显式参数。

```text
public static function log();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = myException::log();
```

定位：`lib/myException.class.php:268`。

### myException::clear

功能说明：错误清除

参数说明：无显式参数。

```text
public static function clear();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = myException::clear();
```

定位：`lib/myException.class.php:305`。

### myException::check

功能说明：错误检测

参数说明：无显式参数。

```text
public static function check();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 当前是否记录了框架错误，具体基于静态错误状态

调用形式：

```php
$result = myException::check();
```

定位：`lib/myException.class.php:318`。

### myException::show

功能说明：错误显示

参数说明：无显式参数。

```text
public static function show();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 无待显示错误为false，已输出通用错误/详情为true；本方法本身不执行exit

调用形式：

```php
$result = myException::show();
```

定位：`lib/myException.class.php:329`。

### myException::getLines

功能说明：获取某段程序代码

```text
public static function getLines($file, $line, $scope = 3);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$line` | int；定位的源文件行号，从1开始 |
| `$scope` | int；中心行前后读取的上下文范围（默认：3） |

**返回值**：array 以源码行号为键的上下文行列表；文件不可用时返回空数组

调用形式：

```php
$result = myException::getLines($file, $line, $scope);
```

定位：`lib/myException.class.php:406`。
