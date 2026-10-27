# 基础抽象

接口说明：`myBase`。

## 职责与调用位置

业务类不要直接实例化myBase；构造函数把位置参数交给init，统一错误处理及子类初始化约定。

源码：`lib/myBase.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
class HelloValue extends myBase {
    public $value;
    public function init($value = '') { $this->value = $value; }
}
$object = new HelloValue('示例');
assert($object->value === '示例');
```

## 声明与类职责

### myBase

功能说明：把构造参数按需转交init，并提供可恢复的错误回调策略。

使用说明：
  1. 具体类继承myBase并按需实现init；必需参数缺失时保留延迟初始化约定。
  2. 构造和init签名保持兼容，不在此处统一强制参数类型。
  3. 错误回调及终止行为受myException配置影响，不能假设所有错误都会返回。

补充说明：
框架基础类，提供按需初始化及可恢复的错误处理

    new ChildClass($arg1, $arg2); // 只向实际声明的 init() 转交参数
    $this->setErrHandler($callable); // 子类内部设置错误回调，null 恢复默认
    $this->error($message, false); // 按 myException 策略记录/回调

无参数构造且 init() 有必需参数时保留延迟初始化约定；不把 __call() 伪装的
init 当作初始化入口。错误临时修改的全局回调标志在正常返回或异常时恢复。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $err_handler = null;
```

```text
protected static $init_required = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myBase::__construct` | 将构造参数转交给实际声明且当前作用域可调用的 init() |
| `myBase::setErrHandler` | 设置或清除实例错误处理回调，参数校验失败时保留原回调 |
| `myBase::error` | 执行实例回调或框架默认错误策略，并恢复临时回调位 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myBase::__construct

功能说明：将构造参数转交给实际声明且当前作用域可调用的 init()

```text
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；按具体子类init的位置顺序传入；无参数时遵守延迟初始化规则（位置参数，由func_get_args读取） |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：Throwable 显式参数不符或 init() 内部异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myBase.class.php:55`。

### myBase::setErrHandler

功能说明：设置或清除实例错误处理回调，参数校验失败时保留原回调

```text
protected function setErrHandler($func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | callable\|null；接收错误消息的回调；null 清除实例回调 |

**返回值**：void 回调的返回值不改变 error() 的返回及终止策略

**异常与失败**：InvalidArgumentException 非 null 参数不可调用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myBase.class.php:74`。

### myBase::error

功能说明：执行实例回调或框架默认错误策略，并恢复临时回调位

实例回调先接收消息；随后通过 E_USER_ERROR 交给已有错误处理器记录，
此时临时关闭框架回调，避免同一错误重复展示。未安装错误处理器时仍遵循
PHP 对 E_USER_ERROR 的默认行为。进程 exit 不保证执行 finally。

```text
protected function error($msg, $exit = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$msg` | string；错误消息 |
| `$exit` | bool；true 明确终止；false 仍遵循 myException::exit_on_error（默认：false） |

**返回值**：bool 未终止且未抛异常时为 true

**异常与失败**：Throwable 实例或 PHP 错误处理器抛出的异常继续传递，临时位会恢复

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myBase.class.php:91`。
