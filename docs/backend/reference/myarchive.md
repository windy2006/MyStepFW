# 解包工具

接口说明：`myArchive`。

## 职责与调用位置

在受信根下暂存，检查完整后commit；不允许归档路径越界。此类是资源安装辅助，不是整站事务。

源码：`lib/myArchive.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$archive = new myArchive($targetDirectory);
$temporary = $archive->stage('assets/note.txt');
file_put_contents($temporary, 'Hello');
$archive->commit();
assert(file_get_contents($targetDirectory.'/assets/note.txt') === 'Hello');
```

## 声明与类职责

### myArchive

功能说明：管理解包暂存文件、覆盖备份及发布回滚的资源生命周期。

普通发布失败会尝试回滚；进程崩溃或外部并发写入下不保证完整回滚。
回滚失败时保留备份并记录错误，调用方应据此安排恢复。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $root, $stage, $backup, $lock, $entries = [], $created = [], $journal = [], $preserve = false;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myArchive::__construct` | 锁定解包根目录并建立暂存及覆盖备份目录 |
| `myArchive::stage` | 验证并登记唯一归档成员，返回可供写入的暂存路径 |
| `myArchive::move` | 重命名文件，用作发布和回滚时可覆盖的移动入口 |
| `myArchive::directory` | 递归建立发布目录并登记本次创建的目录 |
| `myArchive::commit` | 发布已暂存成员；失败时尝试恢复被覆盖文件并撤销新目录 |
| `myArchive::__destruct` | 清理暂存资源并释放目录锁，回滚失败时保留恢复备份 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myArchive::__construct

功能说明：锁定解包根目录并建立暂存及覆盖备份目录

```text
public function __construct($root);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$root` | string；操作限制在其中的受信根目录 |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：RuntimeException 根目录、锁或暂存目录准备失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myArchive.class.php:54`。

### myArchive::stage

功能说明：验证并登记唯一归档成员，返回可供写入的暂存路径

```text
public function stage($name, $directory = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；相对解包根目录的合法成员名，不能重复或穿越目录 |
| `$directory` | bool；true登记目录成员，false登记待写入文件（默认：false） |

**返回值**：string|false 已登记成员的暂存路径；名称/冲突校验失败返回false

调用形式：

```php
// $instance为已初始化的myArchive实例。
$result = $instance->stage($name, $directory);
```

定位：`lib/myArchive.class.php:75`。

### myArchive::move

功能说明：重命名文件，用作发布和回滚时可覆盖的移动入口

```text
protected function move($from, $to);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$from` | string；已暂存文件或备份文件的源路径 |
| `$to` | string；发布或恢复到的目标路径 |

**返回值**：bool 文件重命名是否成功

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myArchive.class.php:94`。

### myArchive::directory

功能说明：递归建立发布目录并登记本次创建的目录

```text
protected function directory($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；需要创建的目标目录，已存在时保留 |

**返回值**：bool 目录已存在或成功创建为true，创建失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myArchive.class.php:102`。

### myArchive::commit

功能说明：发布已暂存成员；失败时尝试恢复被覆盖文件并撤销新目录

普通文件回滚失败时备份目录保留并记录错误，不能保证进程崩溃或外部并发写入下的完整回滚。

参数说明：无显式参数。

异常处理：发布阶段异常在内部触发回滚并返回false；回滚失败保留备份并记录错误，不把已捕获的发布异常重新抛给调用方。

```text
public function commit();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 全部发布成功为true；失败并尝试回滚后为false

调用形式：

```php
// $instance为已初始化的myArchive实例。
$result = $instance->commit();
```

定位：`lib/myArchive.class.php:122`。

### myArchive::__destruct

功能说明：清理暂存资源并释放目录锁，回滚失败时保留恢复备份

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myArchive.class.php:158`。
