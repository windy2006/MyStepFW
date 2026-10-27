# myAttachment：附件索引和定位

## 职责与调用位置

以业务文件根及存储标识定位，显示名称与物理文件名分离；记录存在不自动授予下载权限。

源码：`lib/myAttachment.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// $directory为当前分区目录，stored.bin已由上传流程写入。
$ok = myAttachment::append($directory, 'stored.bin', '用户原始名称.bin');
// 从HTTP参数读取id后先校验业务授权，再在指定root内find/remove。
$entry = myAttachment::find($uploadRoot, $id, 'Ym/');
```

## 声明与类职责

### myAttachment

功能说明：通过附件索引集中处理上传登记、唯一查找和删除。

仅操作受信根目录中的合法附件，查找失败或存在歧义时不返回附件记录。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myAttachment::validName` | 验证附件标识的字符范围、长度及连续点限制 |
| `myAttachment::record` | 解析附件索引行并验证标识匹配及文件路径边界 |
| `myAttachment::find` | 按日期目录或有限递归扫描附件索引，仅返回唯一匹配 |
| `myAttachment::append` | 清理显示文件名并加锁追加一条附件索引 |
| `myAttachment::remove` | 加锁复核索引中的唯一匹配，删除附件并重写剩余索引 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myAttachment::validName

功能说明：验证附件标识的字符范围、长度及连续点限制

```text
protected static function validName($name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；附件存储名称，限字母数字及._-且不含连续点 |

**返回值**：bool 名称满足存储规则为true

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myAttachment.class.php:52`。

### myAttachment::record

功能说明：解析附件索引行并验证标识匹配及文件路径边界

```text
protected static function record($directory, $line, $id);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$directory` | string；当前附件索引所在目录 |
| `$line` | string；log.txt中的一条name::display::记录 |
| `$id` | string；待匹配的完整存储名或不含扩展名的唯一标识 |

**返回值**：array|false 含file/name/stored/log的附件记录；无匹配或不合法为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myAttachment.class.php:65`。

### myAttachment::find

功能说明：按日期目录或有限递归扫描附件索引，仅返回唯一匹配

```text
public static function find($root, $id, $mode = 'Ym/');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$root` | string；操作限制在其中的受信根目录 |
| `$id` | string；操作对象的标识 |
| `$mode` | string；由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'） |

**返回值**：array|false 唯一附件记录；未找到、歧义或路径不合法为false

调用形式：

```php
$result = myAttachment::find($root, $id, $mode);
```

定位：`lib/myAttachment.class.php:85`。

### myAttachment::append

功能说明：清理显示文件名并加锁追加一条附件索引

```text
public static function append($directory, $stored, $display);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$directory` | string；存放附件文件及log.txt的目录 |
| `$stored` | string；实际落盘的附件文件名 |
| `$display` | string；用于展示和下载的原始文件名，会移除日志分隔符/换行 |

**返回值**：bool 索引记录是否完整追加

调用形式：

```php
$result = myAttachment::append($directory, $stored, $display);
```

定位：`lib/myAttachment.class.php:127`。

### myAttachment::remove

功能说明：加锁复核索引中的唯一匹配，删除附件并重写剩余索引

```text
public static function remove($root, $id, $mode = 'Ym/');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$root` | string；操作限制在其中的受信根目录 |
| `$id` | string；操作对象的标识 |
| `$mode` | string；由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'） |

**返回值**：bool 附件删除及索引改写是否成功

调用形式：

```php
$result = myAttachment::remove($root, $id, $mode);
```

定位：`lib/myAttachment.class.php:143`。
