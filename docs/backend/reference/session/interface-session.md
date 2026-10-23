# interface_session：interface_session 会话处理器

修订日期：2026-10-23。功能详解。

## 职责与调用位置

此文件实现PHP会话保存或查询相关能力，通常由框架按session.mode选择。应用内使用会话门面，不重复启动处理器；底层回调由PHP引擎调用。

源码：`lib/session/interface_session.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 当前应用已由框架启动会话；不是直接调用底层处理器的read/write。
myReq::session('editor_id', 7);
assert(myReq::session('editor_id') === 7);
myReq::removeSession('editor_id');
```

## 声明与类职责

### interface_session

功能说明：声明open/close/read/write/destroy/gc六个基础存储回调。

使用说明：
  1. 由框架Session后端实现，并由myReq登记到PHP会话机制。
  2. 其他validateId/updateTimestamp等可选钩子由具体处理器提供。
  3. read成功返回序列化会话字节串，空串与后端失败不能随意混为同一状态。

补充说明：
框架静态SESSION处理器的兼容协议（PHP 7.4）。
read返回原始字符串（不存在为空串，失败为false），其余回调返回bool。
可选validateId($sid)和updateTimestamp($sid,$data)由myReq自动注册，
不增加必需方法，保留现有第三方六回调处理器兼容性。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_session::open` | 初始化存储。 |
| `interface_session::close` | 释放连接和锁。 |
| `interface_session::read` | 读取并持有同ID请求锁。 |
| `interface_session::write` | 写入完整字节串。 |
| `interface_session::destroy` | 销毁会话。 |
| `interface_session::gc` | 回收过期存储。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_session::open

功能说明：初始化存储。

```text
public static function open($sess_path, $sess_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sess_path` | string；路径 |
| `$sess_name` | string；Cookie名 |

**返回值**：bool 存储初始化成功为true，目录/连接/结构准备失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:49`。

### interface_session::close

功能说明：释放连接和锁。

参数说明：无显式参数。

```text
public static function close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 连接及锁释放后的结果；内置实现允许重复关闭

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:57`。

### interface_session::read

功能说明：读取并持有同ID请求锁。

```text
public static function read($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:64`。

### interface_session::write

功能说明：写入完整字节串。

```text
public static function write($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；原始数据 |

**返回值**：bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:72`。

### interface_session::destroy

功能说明：销毁会话。

```text
public static function destroy($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool 目标会话删除及相关一致性处理成功为true，失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:79`。

### interface_session::gc

功能说明：回收过期存储。

```text
public static function gc($maxlifetime);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$maxlifetime` | int；有效秒数 |

**返回值**：bool 处理器报告的回收结果；文件处理器可能跳过忙文件，true不表示所有过期文件均已删除，也不是删除条数

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/interface_session.class.php:86`。
