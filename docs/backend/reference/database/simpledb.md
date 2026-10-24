# simpleDB：本地小型数据库

修订日期：2026-10-24。

## 职责与调用位置

管理固定记录格式的本地小表。删除使用destroy；不能把文件级锁视为多表事务。

源码：`lib/database/simpleDB.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$db = new myDb('simpleDB', 'demo', $directory);
if (!$db->check()) $db->create([['title', 100], ['count', 8]]);
$db->insert(['title'=>'示例', 'count'=>1]);
$rows = $db->select();
$db->destroy(); // 仅删除本例独占的demo.db，生产环境须先完成权限检查。
```

## 声明与类职责

### simpleDB

功能说明：提供固定格式文件存储、条件匹配、CRUD、格式校验及原子并发写入。

使用说明：
  1. 适合少量本地结构化记录；通过create定义字段后读写。
  2. 参数采用文件/字段/条件/行号语义，不能直接套用SQL驱动参数。
  3. 使用destroy删除当前表文件。

补充说明：
兼容旧固定长度 .db 格式的本地小表，读写在同一个文件锁内完成。
字段按字节宽度保存，尾部空格作为填充；不支持 NUL/换行及事务 SQL。
超长值明确报错，避免覆盖后续记录。默认文件预算 32 MiB，可在 init 第四参调整。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```php
private $name='', $path='', $file='', $tlen=500, $fp=null, $maxlen=10, $separator="\0", $max_bytes=33554432;
```

```php
public $pos=0, $row=0;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `simpleDB::__construct` | 按可选位置参数初始化，不立即创建文件 |
| `simpleDB::init` | 关闭旧句柄并设置本地数据文件 |
| `simpleDB::connect` | 打开现有数据文件；write/w 不再隐式截断，清空数据使用 empty |
| `simpleDB::selectDB` | 切换本地表并打开只读句柄 |
| `simpleDB::check` | 检查文件是否存在，不隐式创建或修复 |
| `simpleDB::close` | 安全关闭公开 connect 打开的句柄 |
| `simpleDB::__destruct` | 析构只释放当前对象句柄 |
| `simpleDB::decode` | 校验表头及固定记录边界，读取旧格式；损坏文件明确失败 |
| `simpleDB::encode` | 在任何写入之前验证所有记录并编码固定长度内容 |
| `simpleDB::access` | 在共享/排他锁内取得快照并执行操作，写前完成全部编码 |
| `simpleDB::mutate` | 在单个排他锁内读取、变更及保存整个记录集，避免empty/insert多次操作的空表窗口。 |
| `simpleDB::create` | 原子独占创建空表，已存在时不截断 |
| `simpleDB::destroy` | 关闭当前表并删除文件，不与其他进程的文件替换组成事务。 |
| `simpleDB::empty` | 在排他锁内清空记录，保留原字段设置 |
| `simpleDB::matcher` | 将字符串比较或字段映射转换为 AND 过滤器，值中的等号保持原样 |
| `simpleDB::query` | 查询记录，包含空条件时也按指定模式和条数返回 |
| `simpleDB::select` | 读取纯记录，尊重传入条数 |
| `simpleDB::insert` | 在单次排他锁内追加一条或多条记录，按最新表头计算偏移 |
| `simpleDB::update` | 在同一排他锁内筛选并更新记录，空条件明确更新全部 |
| `simpleDB::delete` | 在同一排他锁内删除并压实记录，不使用空白正则改写数据 |
| `simpleDB::record` | 按 1 起始行号读取记录，0/非数字取首行，负数取随机行，越界正数取末行 |
| `simpleDB::records` | 读取全部纯记录 |
| `simpleDB::random` | 无重复随机抽取记录，不通过反复碰撞重试 |
| `simpleDB::getData` | 读取某行或指定字段 |
| `simpleDB::result` | 读取首个匹配项的字段，未指定字段时返回第一列 |
| `simpleDB::getFields` | 取得旧格式字段设置及最新长度元数据 |
| `simpleDB::setOrder` | 排序结果，随机模式使用 shuffle，不使用不满足传递性的随机比较器 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### simpleDB::__construct

功能说明：按可选位置参数初始化，不立即创建文件

```php
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；按init声明顺序传入的数据文件等设置（历史位置参数，由func_get_args读取） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:85`。

### simpleDB::init

功能说明：关闭旧句柄并设置本地数据文件

```php
public function init($name,$path='./',$tlen=500,$max_bytes=33554432);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；单个表名，不含路径分隔符、上级目录或 NUL |
| `$path` | string；文件目录，默认当前目录（默认：'./'） |
| `$tlen` | int；兼容旧表头字节数，范围 32..1048576，已有表须使用原值（默认：500） |
| `$max_bytes` | int；文件内存预算，默认 32 MiB（默认：33554432） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 参数非法

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->init($name, $path, $tlen, $max_bytes);
```

定位：`lib/database/simpleDB.class.php:97`。

### simpleDB::connect

功能说明：打开现有数据文件；write/w 不再隐式截断，清空数据使用 empty

```php
public function connect($mode='r');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | string；read/r/rb 只读，其余历史 write/append/r+ 形式读写（默认：'r'） |

**返回值**：bool 成功 true，文件不存在或不能打开 false

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->connect($mode);
```

定位：`lib/database/simpleDB.class.php:110`。

### simpleDB::selectDB

功能说明：切换本地表并打开只读句柄

```php
public function selectDB($name,$path='',$tlen=500);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；新表名，空值沿用原名 |
| `$path` | string；新目录，空值沿用原目录（默认：''） |
| `$tlen` | int；表头长度，默认 500（默认：500） |

**返回值**：bool 文件可读为 true

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->selectDB($name, $path, $tlen);
```

定位：`lib/database/simpleDB.class.php:120`。

### simpleDB::check

功能说明：检查文件是否存在，不隐式创建或修复

参数说明：无显式参数。

```php
public function check();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 目标数据文件存在为true；不会创建、打开或验证文件格式

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->check();
```

定位：`lib/database/simpleDB.class.php:129`。

### simpleDB::close

功能说明：安全关闭公开 connect 打开的句柄

参数说明：无显式参数。

```php
public function close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->close();
```

定位：`lib/database/simpleDB.class.php:138`。

### simpleDB::__destruct

功能说明：析构只释放当前对象句柄

参数说明：无显式参数。

```php
public function __destruct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:147`。

### simpleDB::decode

功能说明：校验表头及固定记录边界，读取旧格式；损坏文件明确失败

```php
private function decode($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；完整文件快照 |

**返回值**：array [字段宽度映射, 记录数组]

**异常与失败**：UnexpectedValueException 文件长度、字段或分隔符不符合格式

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:157`。

### simpleDB::encode

功能说明：在任何写入之前验证所有记录并编码固定长度内容

```php
private function encode(array $schema,array $rows);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$schema` | array；字段宽度 |
| `$rows` | array；记录 |

**返回值**：string 完整文件内容

**异常与失败**：LengthException 值超过字段宽度或文件预算；InvalidArgumentException 值含 NUL/换行或不是标量/null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:178`。

### simpleDB::access

功能说明：在共享/排他锁内取得快照并执行操作，写前完成全部编码

```php
private function access($write,callable $callback);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$write` | bool；true 写操作，false 只读 |
| `$callback` | callable；接收记录引用和字段映射，返回业务结果 |

**返回值**：mixed 文件不存在/无法打开时 false，否则回调结果

**异常与失败**：RuntimeException 锁或读写失败；不提供断电原子性，预算适用于小型本地表

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:198`。

### simpleDB::mutate

功能说明：在单个排他锁内读取、变更及保存整个记录集，避免empty/insert多次操作的空表窗口。

回调接收引用记录数组及只读结构数组；抛异常时保留原文件，返回值原样传回调用方。
返回false本身不会取消保存；需要取消时应抛异常。受信代码负责具体业务约束。

```php
public function mutate(callable $callback);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$callback` | callable；function(array &$rows, array $schema): mixed |

**返回值**：mixed 回调结果；表不存在/打不开为false，编码、容量和I/O异常继续传递

**异常与失败**：InvalidArgumentException 回调产生无法编码的记录；RuntimeException 无法锁定、读取或完整写入

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->mutate($callback);
```

定位：`lib/database/simpleDB.class.php:221`。

### simpleDB::create

功能说明：原子独占创建空表，已存在时不截断

```php
public function create($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；[[字段名,字节宽度],...]，字段名不能与内部元数据重名 |

**返回值**：bool 创建成功 true，文件已存在 false

**异常与失败**：InvalidArgumentException 数据文件/字段结构不合法或配置预算超限；RuntimeException 无法创建、写入或发布数据文件

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->create($setting);
```

定位：`lib/database/simpleDB.class.php:235`。

### simpleDB::destroy

功能说明：关闭当前表并删除文件，不与其他进程的文件替换组成事务。

参数说明：无显式参数。

```php
public function destroy();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 当前表文件存在且成功删除时为true，否则false

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->destroy();
```

定位：`lib/database/simpleDB.class.php:251`。

### simpleDB::empty

功能说明：在排他锁内清空记录，保留原字段设置

参数说明：无显式参数。

```php
public function empty();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 文件存在并写入成功 true

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->empty();
```

定位：`lib/database/simpleDB.class.php:260`。

### simpleDB::matcher

功能说明：将字符串比较或字段映射转换为 AND 过滤器，值中的等号保持原样

```php
private function matcher($condition);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；field>=value && field%part，或字段到等值的映射 |

**返回值**：callable 接收记录返回 bool；未知字段视为不匹配

**异常与失败**：InvalidArgumentException 条件格式非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/simpleDB.class.php:270`。

### simpleDB::query

功能说明：查询记录，包含空条件时也按指定模式和条数返回

```php
public function query($condition=[],$mode=2,$limit=1);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；AND 条件或等值映射（默认：[]） |
| `$mode` | int；0 纯记录，1 行号/位置，2 记录加元数据，3 仅行号（默认：2） |
| `$limit` | int\|string；最多条数，all 不限制；无参数 query 沿用返回所有纯记录（默认：1） |

**返回值**：array|false 匹配记录；无匹配 false

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->query($condition, $mode, $limit);
```

定位：`lib/database/simpleDB.class.php:285`。

### simpleDB::select

功能说明：读取纯记录，尊重传入条数

```php
public function select($condition=null,$limit=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；过滤条件（默认：null） |
| `$limit` | int\|string\|null；null/all 返回全部（默认：null） |

**返回值**：array|false 匹配记录列表；没有匹配或存储失败为false，应结合驱动错误区分

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->select($condition, $limit);
```

定位：`lib/database/simpleDB.class.php:297`。

### simpleDB::insert

功能说明：在单次排他锁内追加一条或多条记录，按最新表头计算偏移

```php
public function insert($data=[],$mode=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；单记录或记录列表（默认：[]） |
| `$mode` | bool；true 使用字段名，false 按位置（默认：false） |

**返回值**：bool 全部记录编码并保存成功为true；校验异常向外传递，I/O失败为false

**异常与失败**：InvalidArgumentException 待插入值不是要求的记录/记录列表

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->insert($data, $mode);
```

定位：`lib/database/simpleDB.class.php:307`。

### simpleDB::update

功能说明：在同一排他锁内筛选并更新记录，空条件明确更新全部

```php
public function update($condition=null,$data=[],$mode=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；过滤条件（默认：null） |
| `$data` | array；字段映射或按位置的数据（默认：[]） |
| `$mode` | bool；true 合并字段，false 按位置替换整行（默认：true） |

**返回值**：bool 至少匹配一行 true，无匹配 false

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->update($condition, $data, $mode);
```

定位：`lib/database/simpleDB.class.php:320`。

### simpleDB::delete

功能说明：在同一排他锁内删除并压实记录，不使用空白正则改写数据

```php
public function delete($condition=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；空值删除全部记录（默认：null） |

**返回值**：bool 至少匹配一行 true，无匹配 false

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->delete($condition);
```

定位：`lib/database/simpleDB.class.php:331`。

### simpleDB::record

功能说明：按 1 起始行号读取记录，0/非数字取首行，负数取随机行，越界正数取末行

```php
public function record($row=0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$row` | int\|string；行号（默认：0） |

**返回值**：array|false 一行字段映射；空表或读取失败为false，具体行号选择遵守参数约定

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->record($row);
```

定位：`lib/database/simpleDB.class.php:339`。

### simpleDB::records

功能说明：读取全部纯记录

```php
public function records($tmp='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tmp` | string；接口兼容占位参数，不使用（默认：''） |

**返回值**：array|false 文件不存在 false，空表 []

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->records($tmp);
```

定位：`lib/database/simpleDB.class.php:347`。

### simpleDB::random

功能说明：无重复随机抽取记录，不通过反复碰撞重试

```php
public function random($rows=1);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rows` | int；最多条数（默认：1） |

**返回值**：array|false 空表 false，非正条数 []

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->random($rows);
```

定位：`lib/database/simpleDB.class.php:355`。

### simpleDB::getData

功能说明：读取某行或指定字段

```php
public function getData($line=0,$field='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$line` | int；行号，0 为首行（默认：0） |
| `$field` | string；空串返回整行（默认：''） |

**返回值**：mixed 缺字段为空串

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->getData($line, $field);
```

定位：`lib/database/simpleDB.class.php:364`。

### simpleDB::result

功能说明：读取首个匹配项的字段，未指定字段时返回第一列

```php
public function result($condition,$field='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | string\|array\|null；查询条件 |
| `$field` | string；字段名（默认：''） |

**返回值**：mixed 未匹配为空串

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->result($condition, $field);
```

定位：`lib/database/simpleDB.class.php:373`。

### simpleDB::getFields

功能说明：取得旧格式字段设置及最新长度元数据

```php
public function getFields($tmp='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tmp` | string；接口占位参数（默认：''） |

**返回值**：array|false 字段宽度加 mydb_max_length/mydb_rec_length/max_row

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->getFields($tmp);
```

定位：`lib/database/simpleDB.class.php:381`。

### simpleDB::setOrder

功能说明：排序结果，随机模式使用 shuffle，不使用不满足传递性的随机比较器

```php
public function setOrder($result,$col='rand',$mode='asc');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$result` | array；查询结果 |
| `$col` | string；排序列，缺失时随机（默认：'rand'） |
| `$mode` | string；asc/desc，其余随机（默认：'asc'） |

**返回值**：array 排序后的记录副本

调用形式：

```php
// $instance为已初始化的simpleDB实例。
$result = $instance->setOrder($result, $col, $mode);
```

定位：`lib/database/simpleDB.class.php:391`。
