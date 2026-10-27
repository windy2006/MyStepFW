# myDb：数据库代理与SQL构建

## 职责与调用位置

prepared和transaction由真实驱动声明；supports不建立连接或证明服务器健康。表名仍须白名单。

源码：`lib/myDb.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$db = new myDb('SQLite'); // 独立内存库，不使用业务数据库。
$db->execute('CREATE TABLE items(id INTEGER PRIMARY KEY,title TEXT)');
$db->execute('INSERT INTO items(id,title) VALUES(?,?)', [1,'Hello']);
assert($db->fetchOne('SELECT * FROM items WHERE id=?', [1])['title'] === 'Hello');
$db->transaction(function ($tx) { $tx->execute('UPDATE items SET title=? WHERE id=?', ['World',1]); });
assert($db->fetchValue('SELECT title FROM items WHERE id=?',[1]) === 'World');
```

## 声明与类职责

### myDb

功能说明：代理具体数据库驱动，并提供能力检查及以代理为参数的事务回调

使用说明：
  1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
  2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
  3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
  4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。

补充说明：
数据库代理，转发驱动方法并提供参数化查询及事务入口（MySQL/SQLite）

    $db = new myDb('MySQL', $host, $user, $pwd, 'utf8mb4'); // 构造驱动，尚需 connect()
    $db->connect(false, $database);                       // 连接并选择数据库
    $db->execute($sql, $params);                          // 参数化执行，不用共享结果缓存
    $rows = $db->fetchAll($sql, $params);                  // 参数化读取全部记录
    $row = $db->fetchOne($sql, $params);                   // 参数化读取第一条记录
    $value = $db->fetchValue($sql, $params);               // 参数化读取第一行第一列
    $db->transaction(function ($tx) { ... });              // 正常返回提交，异常回滚

使用说明：
1. 初始化遵循 myProxy：驱动存在构造函数时不重复调用 init()；支持直接代理现有驱动。
2. 参数占位符只代替数据值，不代替表名、字段、排序方向或 SQL 片段。
3. query()/build()/record()/records()/result() 用于SQL构造和结果读取；参数化读取及受管事务
   由 MySQL/SQLite 实现；MySQL 参数化读取需要 mysqlnd，事务表须支持事务。
4. 代理事务回调接收当前 myDb；直接驱动入口接收驱动自身。异常交给调用方处理。
5. supports(接口名)检查驱动声明的API能力；代理本身不充当具体驱动的接口实例。

### SQLStatement

功能说明：扫描SQL词法结构、注释及最外层分页片段的公共辅助类

使用说明：
  1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
  2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
  3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
  4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。

补充说明：
SQL 结构扫描辅助类，记录关键字位置及括号层级

    SQLStatement::tokens($sql, true);   // 忽略引号和注释中的内容，取得词及层级
    SQLStatement::unpaged($sql, true);  // 去掉最外层排序、分页及锁定子句

供计数查询、只读缓存判定及 SQLite 格式转换使用；它不是完整 SQL 解析器，
不负责数据库语法验证或处理任意方言，也不能把不可信 SQL 变成安全语句。

### base_db

功能说明：数据库标识符、共享缓存状态与受管事务公共trait

使用说明：
  1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
  2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
  3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
  4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。

补充说明：
数据库基础能力：标识符辅助、共享缓存代次及受管事务

由驱动混入使用。缓存命名空间隔离连接用户、库、字符集及 SQLite 文件/内存实例；
事务、会话状态、临时表、锁定及已知非确定查询绕过共享缓存。写入更新共享代次，
事务内推迟到提交；回滚不发布未提交结果。绕过驱动的原生 SQL 不能自动跟踪，
外部缓存故障和未识别函数仍受后端可用性及 TTL 约束，不提供跨进程强一致承诺。
受管 begin/commit/rollback/transaction 限定 MySQL/SQLite，并支持嵌套保存点。

### base_sql

功能说明：SQL构建、普通查询与参数化读取公共trait

使用说明：
  1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
  2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
  3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
  4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。

补充说明：
SQL 驱动共享的构建、结果读取及参数化读取辅助方法

build() 创建的 SQLBuilder 继承当前驱动的标识符定界符及值转义器。
record()/records()/result() 可按策略缓存普通查询，空值保留其语义；count() 包装
移除外层分页后的原查询，保留分组及去重。fetchAll/One/Value 委托驱动 execute()
绑定参数，不使用共享查询缓存，结束或异常时释放结果及构建状态。
旧构建器仍接受可信原始表达式，不能将所有参数当作不可信数据的安全字面量。

### SQLBuilder

功能说明：可链式组合字段、条件、值、排序及分页的SQL构建器

使用说明：
  1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
  2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
  3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
  4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。

补充说明：
链式 SQL 片段构建器，支持字段、条件、排序、分页及分组

    $builder->where(['status' => 1, 'id' => [1, 2]]); // 关联映射：相等或 IN
    $builder->where('deleted_at', '=', null);        // 输出 IS NULL
    $builder->where([['a', '=', 1], ['b', '=', 2], 'or']); // 嵌套条件组
    $builder->order(['id', true])->limit(0, 20);     // 降序及分页

使用说明：
1. 由驱动 build() 创建时继承其值转义器；独立构造且未提供转义器时沿用旧 MySQL 规则。
2. 空数组过滤转换为明确布尔条件，不直接丢失过滤；空 IN 为假，空 NOT IN 为真。
3. 旧字符串表达式、字段增量及函数形式仍有特殊含义，调用方须限制输入；
   不可信数据和二进制值优先使用驱动 execute()/fetchAll/One/Value 参数绑定。
4. field()/where()/order()/limit() 无参数读取当前片段，group() 无参数返回分组设置；
   不会执行 SQL，实际执行由数据库驱动完成。

## PHP8.5空值条件

`where('expire', 'is', null)`及`where('expire', 'is not', null)`分别生成IS NULL与IS NOT NULL，直接接受PHP的null值；无需先转成字符串。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected static $database_autoload_registered = false;
```

```text
protected
        $func_alias = array(
            'c' => 'connect',
            'i' => 'insert',
            's' => 'select',
            'u' => 'update',
            'd' => 'delete',
            'q' => 'query',
            'r' => 'record',
            'g' => 'result'
        );
```

```text
protected
        $cache_mode = 255,
        $cache = null,
        $cache_ttl = 600,
        $cache_keys = [];
```

```text
protected $transaction_control=false, $managed_callbacks=0, $managed_boundary=false, $cache_savepoints = [], $cache_savepoint_root = false, $cache_transaction = false, $cache_manual = false, $cache_dirty = false, $cache_session = false, $cache_temporary = [], $managed_depth = 0, $cache_memory_identity = '';
```

```text
protected
        $host = '',
        $user = '',
        $pwd = '',
        $charset = 'utf8',
        $connect = NULL,
        $result = NULL,
        $sql = '',
        $db = '',
        $builder = array(),
        $delimiter = '';
```

```text
protected $escaper = null;
```

```text
public
        $join = array(
            'mode' => 'left',
            'field' => '',
            'field_join' => ''
        ),
        $dl = '',
        $dr = '',
        $tbl = '',
        $sel_prefix = '',
        $idx = '';
```

```text
protected
        $condition = array(),
        $fields = array(),
        $order = array(),
        $values = array(),
        $group = array(),
        $limit = '';
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myDb::__construct` | 注册数据库目录自动加载器，并按参数初始化代理驱动 |
| `myDb::supports` | 检查代理驱动是否声明实现指定能力接口，不执行SQL或检测服务器健康。 |
| `myDb::transaction` | 委托驱动执行事务，使回调接收当前代理而不是内部驱动 |
| `SQLStatement::tokens` | 扫描 SQL 单词，跳过引号内容及行/块注释，记录括号嵌套深度 |
| `SQLStatement::segments` | 按 SQL 引号和注释边界划分文本，保留每段原始字节 |
| `SQLStatement::mapCode` | 仅转换 SQL 代码片段，不修改字符串、定界标识符和注释 |
| `SQLStatement::split` | 分割可信导入脚本，保留字符串内分号/空白/注释及 MySQL 可执行注释 |
| `SQLStatement::trimTerminator` | 去掉最后一个代码分号，保留其后的注释与空白 |
| `SQLStatement::leadingSQL` | 去除普通前导注释，仅供导入结果说明与旧方言转换使用 |
| `SQLStatement::unpaged` | 移除最外层 ORDER BY、LIMIT/OFFSET/FETCH 及锁定子句 |
| `base_db::quoteIdentifier` | 引用完整标识符，不像 safeName 那样删改合法名称中的空格或标点 |
| `base_db::safeName` | 获取安全的名称（数据库、表及字段） |
| `base_db::safeValue` | 获取安全的字段值 |
| `base_db::cache` | 读取或设置共享查询缓存策略的读写位 |
| `base_db::setCache` | 挂接共享缓存后端及查询条目的 TTL，后续写操作通过同后端传播失效 |
| `base_db::hasCache` | 检查是否已挂接共享缓存后端，供外层查询缓存决定是否启用 |
| `base_db::cacheNamespace` | 生成当前数据库上下文的稳定隔离摘要 |
| `base_db::cacheKey` | 为逻辑查询键添加数据库命名空间和当前失效代次 |
| `base_db::invalidateCache` | 更新共享缓存代次，并清空当前请求记录的读写键关联 |
| `base_db::getCache` | 按读策略读取查询缓存，并在查询前捕获代次用于后续写入 |
| `base_db::writeCache` | 按写策略保存查询结果，优先沿用查询前捕获的代次 |
| `base_db::isCacheable` | 保守判断 SQL 是否可复用共享结果，同时作为 MySQL 安全重试的筛选条件 |
| `base_db::noteStatement` | 在语句成功后跟踪事务、会话上下文和写入失效，由驱动内部调用 |
| `base_db::savepointName` | 提取成功执行的保存点控制语句名称，忽略注释并解开标识符引号 |
| `base_db::noteSavepoint` | 跟踪原生保存点，防止 SQLite 外层 SAVEPOINT 内的未提交结果进入共享缓存 |
| `base_db::guardTransaction` | 在执行前拒绝会破坏受管事务边界的已知控制/隐式提交语句 |
| `base_db::transactionSQL` | 标记框架内部发起的事务 SQL，避免与回调手写控制语句混淆 |
| `base_db::transactionBoundary` | 在回调事务的进入/退出边界调用事务方法，保持嵌套回调的所有权 |
| `base_db::begin` | 开始一层受管事务，嵌套时创建 ms_scope_ 层级保存点 |
| `base_db::commit` | 提交当前受管层：内层释放保存点，最外层 COMMIT 后更新脏查询缓存代次 |
| `base_db::rollback` | 回滚当前受管层：内层回滚并释放保存点，最外层执行 ROLLBACK |
| `base_db::transaction` | 在受管事务中执行回调，正常返回即提交，抛异常时回滚到进入前层级 |
| `base_sql::build` | 获取或创建指定表的查询构建器，沿用驱动转义器及定界符 |
| `base_sql::create` | 按对象类别构建创建库/表/索引SQL，可选择预览或执行 |
| `base_sql::drop` | 按对象类别构建删除库/表/索引SQL，可选择预览或执行 |
| `base_sql::insert` | 将当前表的字段和值构建为INSERT并预览或执行 |
| `base_sql::select` | 依据当前一张或多张表的构建状态生成 SELECT，或立即执行 |
| `base_sql::update` | 将单表或关联表的字段及条件构建为UPDATE并预览或执行 |
| `base_sql::delete` | 依据当前构建条件生成DELETE并预览或执行 |
| `base_sql::record` | 读取查询第一条记录，支持可空记录缓存并清理结果和构建状态 |
| `base_sql::records` | 读取全部记录，按数据库缓存策略复用查询结果，保留空数组 |
| `base_sql::result` | 通过 record() 的数字下标模式读取第一行第一列 |
| `base_sql::count` | 统计原查询的结果行数，保留 DISTINCT、GROUP/HAVING 和 UNION 语义 |
| `base_sql::fetchAll` | 绑定参数读取全部记录，不参与共享结果缓存 |
| `base_sql::fetchOne` | 绑定参数读取第一条记录，不参与共享结果缓存 |
| `base_sql::fetchValue` | 绑定参数读取第一行第一列，保留 SQL NULL、0 及空字符串 |
| `base_sql::correctDelimiter` | 转换引号和注释之外的 MySQL 简单标识符定界符，保留用户文本 |
| `SQLBuilder::__construct` | 初始化表名、关联设置、别名、定界符及驱动值转义器 |
| `SQLBuilder::safeValue` | 使用驱动提供的值转义回调，未提供时使用 MySQL 转义 |
| `SQLBuilder::reset` | 清空字段、值、条件、排序、分组、分页及 SELECT 前缀 |
| `SQLBuilder::exists` | 追加 EXISTS/NOT EXISTS 子查询条件，沿用当前驱动转义器 |
| `SQLBuilder::condition` | 按操作符规则原地规范化一个条件的字段、操作符和值 |
| `SQLBuilder::condition_multi` | 递归生成带括号的复合条件，保留每层逻辑连接方式 |
| `SQLBuilder::where` | 追加条件、读取条件 SQL，或清空条件 |
| `SQLBuilder::field` | 设置查询列或写入字段，读取时使用局部副本避免重复预览改写状态 |
| `SQLBuilder::fieldList` | 分割投影列表，函数参数、字符串和子查询中的逗号保持原样 |
| `SQLBuilder::values` | 直接添加insert的插入值 |
| `SQLBuilder::order` | 追加排序字段，支持数组形式的排序方向，读取时不修改内部状态 |
| `SQLBuilder::limit` | 设置或读取分页片段，数值转为非负整数 |
| `SQLBuilder::group` | 设置 GROUP BY 及可选 HAVING，或读取当前分组结构 |
| `SQLBuilder::select` | 生成 SELECT SQL，保留去重前缀、条件、排序、分组/HAVING 和分页 |
| `SQLBuilder::insert` | 生成 INSERT/REPLACE，列列表支持 field(列名)，位置行使用局部副本，预览不消耗待写数据 |
| `SQLBuilder::update` | 生成更新查询语句 |
| `SQLBuilder::delete` | 生成删除查询语句 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myDb::__construct

功能说明：注册数据库目录自动加载器，并按参数初始化代理驱动

```text
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；myProxy::init的驱动类名/对象及其初始化参数（位置参数，由func_get_args读取） |
| `$module` | string\|object；位置参数说明：第一个可选位置参数：驱动类名或现有驱动实例，省略时不初始化 |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：InvalidArgumentException 代理目标不可用或参数不适用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:171`。

### myDb::supports

功能说明：检查代理驱动是否声明实现指定能力接口，不执行SQL或检测服务器健康。

例如supports(interface_db_prepared::class)判断参数绑定API；
supports(interface_db_transaction::class)判断受管事务API。MSSQL即使由trait
带有begin方法，也不会误报支持受管事务。第三方驱动可自愿实现对应协议。

```text
public function supports($interface);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$interface` | string；接口名称；接受已加载或可自动加载的自定义接口 |

**返回值**：bool 目标声明实现该接口时为true；未初始化、未知名称或类名为false

**异常与失败**：Throwable 自定义自动加载器产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myDb实例。
$result = $instance->supports($interface);
```

定位：`lib/myDb.class.php:198`。

### myDb::transaction

功能说明：委托驱动执行事务，使回调接收当前代理而不是内部驱动

正常返回（包括 false）提交；异常回滚并继续抛出，嵌套事务由驱动使用保存点。
回调不可自行提交或执行隐式提交 DDL；文件、邮件等外部副作用不受回滚保障。

调用示例：
    if ($db->supports(interface_db_transaction::class)) {
        $id = $db->transaction(function ($tx) {
            $tx->execute('INSERT INTO items(name) VALUES(?)', ['示例']);
            return $tx->getInsertId();
        });
    }

```text
public function transaction(callable $callback);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$callback` | callable；接收当前 myDb 对象的业务回调，返回值作为本方法结果 |

**返回值**：mixed 回调正常返回的值

**异常与失败**：BadMethodCallException 目标没有可调用的事务入口或驱动不支持受管事务；Throwable 驱动启动/提交/回滚或业务回调异常原样向外传递

调用形式：

```php
// $instance为已初始化的myDb实例。
$result = $instance->transaction($callback);
```

定位：`lib/myDb.class.php:226`。

### SQLStatement::tokens

功能说明：扫描 SQL 单词，跳过引号内容及行/块注释，记录括号嵌套深度

```text
public static function tokens($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；待分析的可信 SQL 模板，不执行查询 |
| `$backslash` | bool；是否按 MySQL 方式跳过引号内反斜杠转义；SQLite 使用 false（默认：true） |

**返回值**：array 每项含 word（大写单词）、offset（原串字节位置）、depth（括号深度）；
              从零层开始，不包含注释、字符串或标识符引号内的关键字

调用形式：

```php
$result = SQLStatement::tokens($sql, $backslash);
```

定位：`lib/myDb.class.php:261`。

### SQLStatement::segments

功能说明：按 SQL 引号和注释边界划分文本，保留每段原始字节

```text
public static function segments($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SQL 模板 |
| `$backslash` | bool；true 使用 MySQL 反斜杠及 #/--空白注释规则，false 用 SQLite/T-SQL 规则（默认：true） |

**返回值**：array 包含 type/text/offset 的片段；不验证完整 SQL 语法

调用形式：

```php
$result = SQLStatement::segments($sql, $backslash);
```

定位：`lib/myDb.class.php:281`。

### SQLStatement::mapCode

功能说明：仅转换 SQL 代码片段，不修改字符串、定界标识符和注释

```text
public static function mapCode($sql, callable $callback, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL |
| `$callback` | callable；接收一段代码，返回替换文本 |
| `$backslash` | bool；方言引号/注释规则（默认：true） |

**返回值**：string 转换结果

调用形式：

```php
$result = SQLStatement::mapCode($sql, $callback, $backslash);
```

定位：`lib/myDb.class.php:313`。

### SQLStatement::split

功能说明：分割可信导入脚本，保留字符串内分号/空白/注释及 MySQL 可执行注释

```text
public static function split($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；脚本；MySQL 支持独立行 DELIMITER 指令，其他方言不解释该指令 |
| `$backslash` | bool；true 为 MySQL，false 为普通 SQLite/T-SQL 分号批次（默认：true） |

**返回值**：array 不含分隔符的语句；不自动识别无 DELIMITER 的过程/触发器体或 GO 指令

**异常与失败**：InvalidArgumentException 分隔符指令出现在未结束的语句中

调用形式：

```php
$result = SQLStatement::split($sql, $backslash);
```

定位：`lib/myDb.class.php:327`。

### SQLStatement::trimTerminator

功能说明：去掉最后一个代码分号，保留其后的注释与空白

```text
public static function trimTerminator($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；单条 SQL |
| `$backslash` | bool；方言规则（默认：true） |

**返回值**：string 去尾分号后的 SQL，可安全再包装子查询（关闭括号前仍需换行）

调用形式：

```php
$result = SQLStatement::trimTerminator($sql, $backslash);
```

定位：`lib/myDb.class.php:356`。

### SQLStatement::leadingSQL

功能说明：去除普通前导注释，仅供导入结果说明与旧方言转换使用

```text
public static function leadingSQL($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL，不改动内部字符串 |
| `$backslash` | bool；方言规则（默认：true） |

**返回值**：string 以实际语句开始的文本；可执行注释仍保留

调用形式：

```php
$result = SQLStatement::leadingSQL($sql, $backslash);
```

定位：`lib/myDb.class.php:375`。

### SQLStatement::unpaged

功能说明：移除最外层 ORDER BY、LIMIT/OFFSET/FETCH 及锁定子句

保留 DISTINCT、GROUP/HAVING、UNION 及嵌套查询/引号中的同名内容，供 count()
包装子查询使用；不等同于对任意数据库方言进行完整语法重写。

```text
public static function unpaged($sql, $backslash = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；待计数的可信 SELECT/WITH 查询 |
| `$backslash` | bool；引号内是否启用 MySQL 反斜杠转义，SQLite 为 false（默认：true） |

**返回值**：string 截取后的查询，或移除末尾分号及空白后的原查询

调用形式：

```php
$result = SQLStatement::unpaged($sql, $backslash);
```

定位：`lib/myDb.class.php:394`。

### base_db::quoteIdentifier

功能说明：引用完整标识符，不像 safeName 那样删改合法名称中的空格或标点

```text
public function quoteIdentifier($name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string\|array；单个名称，或分段名称数组（如 [数据库, 表]）；不接收 SQL 表达式 |

**返回值**：string 按驱动转义定界符后的标识符

**异常与失败**：InvalidArgumentException 空名称、NUL 或不合法数组

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:438`。

### base_db::safeName

功能说明：获取安全的名称（数据库、表及字段）

```text
public function safeName($name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；待验证/引用的数据库标识符，不能作为任意SQL表达式 |

**返回值**：string 移除不允许字符后的标识符文本；可能为空，不是对任意SQL表达式的引用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:453`。

### base_db::safeValue

功能说明：获取安全的字段值

```text
public function safeValue($val);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$val` | mixed；需要按当前数据库连接规则转义的数据值 |

**返回值**：string|array 标量按规则返回转义文本，传入数组时逐项替换并保留键；不执行SQL

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:463`。

### base_db::cache

功能说明：读取或设置共享查询缓存策略的读写位

```text
public function cache($mode=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int\|null；null 只读取；0 关闭、1 可读、2 可写、3 可读写；默认内部值 255（默认：null） |

**返回值**：int 当前模式；高位不参与现有读写判断，事务及查询资格仍可禁用缓存

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:476`。

### base_db::setCache

功能说明：挂接共享缓存后端及查询条目的 TTL，后续写操作通过同后端传播失效

```text
public function setCache($cache, $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cache` | interface_cache\|myCache\|null；提供 get()/set() 的共享后端，null 关闭挂接 |
| `$ttl` | int；查询缓存有效秒数，默认 600；共享代次存活至少一天且长于该 TTL（默认：600） |

**返回值**：void 不迁移原后端条目；不同连接需要相同后端及一致的命名空间才能传播失效

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:490`。

### base_db::hasCache

功能说明：检查是否已挂接共享缓存后端，供外层查询缓存决定是否启用

参数说明：无显式参数。

```text
public function hasCache();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 后端不为 null 时为 true；不检测后端健康或网络连通性

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:502`。

### base_db::cacheNamespace

功能说明：生成当前数据库上下文的稳定隔离摘要

参数说明：无显式参数。

```text
protected function cacheNamespace();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string SHA-256 摘要，涵盖驱动类、主机、用户、库、字符集及 SQLite 路径；
               每次打开独立内存库时使用不同身份，并隔离 MySQL sql_mode 策略；不把密码作为缓存键的一部分

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:512`。

### base_db::cacheKey

功能说明：为逻辑查询键添加数据库命名空间和当前失效代次

共享代次不存在时分配新的随机代次，避免代次过期/淘汰后旧结果重新出现。

```text
public function cacheKey($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；可序列化的逻辑查询键，通常包含 SQL、结果模式及版本 |

**返回值**：string v4 物理缓存键；挂接后端时本调用可能初始化其代次元数据

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:529`。

### base_db::invalidateCache

功能说明：更新共享缓存代次，并清空当前请求记录的读写键关联

参数说明：无显式参数。

```text
public function invalidateCache();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 不逐条删除旧查询结果；旧代次条目随后按各自 TTL 过期

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:550`。

### base_db::getCache

功能说明：按读策略读取查询缓存，并在查询前捕获代次用于后续写入

```text
public function getCache($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；包含 SQL 与返回模式的逻辑键 |

**返回值**：mixed 后端缓存值；无后端、禁止读取或未命中时为 false；false 本身需由上层封装

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:563`。

### base_db::writeCache

功能说明：按写策略保存查询结果，优先沿用查询前捕获的代次

避免并发写库后把较早读取的结果标为新代次；写入后移除该键的临时关联。

```text
public function writeCache($key, $result);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；与 getCache() 相同的逻辑键 |
| `$result` | mixed；可序列化的查询结果或包含空值的封装 |

**返回值**：void 无后端或禁止写入时不保存，但仍清理临时关联

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:579`。

### base_db::isCacheable

功能说明：保守判断 SQL 是否可复用共享结果，同时作为 MySQL 安全重试的筛选条件

仅允许普通 SELECT；事务/手动提交/会话状态、临时表、锁定读取、用户变量、
可执行注释和已知非确定函数绕过缓存。不能证明任意用户函数的确定性。

```text
public function isCacheable($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL 查询；非字符串直接判为不可缓存 |

**返回值**：bool 通过已实现筛选时为 true；MSSQL 共享结果缓存保守关闭

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:594`。

### base_db::noteStatement

功能说明：在语句成功后跟踪事务、会话上下文和写入失效，由驱动内部调用

识别 SQL 事务控制、AUTOCOMMIT、SET/USE/PRAGMA、临时表及 DDL；提交脏事务
更新代次，回滚不发布结果；MySQL 非临时 DDL 按隐式提交处理。

```text
protected function noteStatement($sql, $rows);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；已执行成功的 SQL 模板，绑定参数不展开进 SQL |
| `$rows` | bool；是否产生结果集/按驱动判为只读，用于识别实际写操作 |

**返回值**：void 仅更新跟踪状态及必要的缓存代次，不执行或重放业务 SQL

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:614`。

### base_db::savepointName

功能说明：提取成功执行的保存点控制语句名称，忽略注释并解开标识符引号

```text
protected function savepointName($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SAVEPOINT、RELEASE 或 ROLLBACK TO 语句 |

**返回值**：string|null 规范小写名称；不是可识别的保存点控制时返回 null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:660`。

### base_db::noteSavepoint

功能说明：跟踪原生保存点，防止 SQLite 外层 SAVEPOINT 内的未提交结果进入共享缓存

```text
protected function noteSavepoint($sql,$word);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；已执行成功的语句 |
| `$word` | string；首关键字 |

**返回值**：bool true 表示已处理保存点语句；SQLite 根保存点 RELEASE 按提交发布失效

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:676`。

### base_db::guardTransaction

功能说明：在执行前拒绝会破坏受管事务边界的已知控制/隐式提交语句

```text
protected function guardTransaction($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；待执行模板，不展开绑定参数 |

**返回值**：void 不检查存储过程内部或绕过驱动的原生调用

**异常与失败**：LogicException 回调内尝试原生事务控制或 MySQL 已知隐式提交操作

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:706`。

### base_db::transactionSQL

功能说明：标记框架内部发起的事务 SQL，避免与回调手写控制语句混淆

```text
protected function transactionSQL($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；框架生成的 BEGIN/保存点/提交/回滚 |

**返回值**：mixed query 的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:719`。

### base_db::transactionBoundary

功能说明：在回调事务的进入/退出边界调用事务方法，保持嵌套回调的所有权

```text
protected function transactionBoundary($method);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string；begin/commit/rollback，由框架固定传入 |

**返回值**：bool 对应事务方法结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:730`。

### base_db::begin

功能说明：开始一层受管事务，嵌套时创建 ms_scope_ 层级保存点

不接管 SQL 方式已开启的外部事务；若前一受管事务被外部控制打断，会重置计数并报错。

参数说明：无显式参数。

```text
public function begin();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 开启事务或保存点成功为 true；驱动以 false 报错时为 false

**异常与失败**：BadMethodCallException 驱动不是 MySQL/SQLite；LogicException 存在受管范围外的事务或受管事务已被打断

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:746`。

### base_db::commit

功能说明：提交当前受管层：内层释放保存点，最外层 COMMIT 后更新脏查询缓存代次

参数说明：无显式参数。

```text
public function commit();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 对应语句成功为 true；驱动以 false 报错时为 false，层级只在成功后减少

**异常与失败**：LogicException 没有活动受管事务或它已被 SQL 控制语句打断

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:764`。

### base_db::rollback

功能说明：回滚当前受管层：内层回滚并释放保存点，最外层执行 ROLLBACK

参数说明：无显式参数。

```text
public function rollback();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 对应语句成功为 true；驱动以 false 报错时为 false；不会发布未提交查询结果

**异常与失败**：LogicException 没有活动受管事务或它已被 SQL 控制语句打断

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:780`。

### base_db::transaction

功能说明：在受管事务中执行回调，正常返回即提交，抛异常时回滚到进入前层级

支持嵌套保存点，正常返回 false 也会提交。回调不得自行提交、回滚或执行隐式提交
DDL；检查到范围改变会抛异常，但已经发生的外部提交不能撤销。仅支持事务表，
文件、邮件等外部副作用也不能回滚；回滚自身失败会记录日志并继续抛原异常。

```text
public function transaction(callable $callback);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$callback` | callable；接收当前驱动的业务回调；代理入口另行包装为接收 myDb |

**返回值**：mixed 回调正常返回值

**异常与失败**：RuntimeException 无法开始/提交事务或回调改变受管范围；LogicException 外部事务冲突或受管事务中断；Throwable 业务回调和驱动异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:806`。

### base_sql::build

功能说明：获取或创建指定表的查询构建器，沿用驱动转义器及定界符

```text
public function build($tbl, $join=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tbl` | string；表名或 数据库.表名，各段按 safeName() 规范化；[reset] 清空全部构建器 |
| `$join` | array\|null；联表设置，含 mode、field 和可选 field_join；重用时可更新联表信息（默认：null） |

**返回值**：SQLBuilder|null 正常表名返回对应构建器；[reset]重置所有构建状态并返回null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:864`。

### base_sql::create

功能说明：按对象类别构建创建库/表/索引SQL，可选择预览或执行

```text
public function create($name, $para=[], $type='db', $show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；待操作的数据库对象名称 |
| `$para` | array\|string；表/索引的col、pri、idx等结构选项；数据库场景为字符集文本（默认：[]） |
| `$type` | string；数据库对象类别（默认：'db'） |
| `$show` | bool；true仅返回生成的SQL，false执行语句（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回驱动query的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:890`。

### base_sql::drop

功能说明：按对象类别构建删除库/表/索引SQL，可选择预览或执行

```text
public function drop($name, $type='db', $show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string\|array；库/表名称；删除索引时为[索引名,表名] |
| `$type` | string；数据库对象类别（默认：'db'） |
| `$show` | bool；true仅返回生成的SQL，false执行语句（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回驱动query的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:976`。

### base_sql::insert

功能说明：将当前表的字段和值构建为INSERT并预览或执行

```text
public function insert($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true仅返回生成的SQL，false执行语句（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回驱动query的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1002`。

### base_sql::select

功能说明：依据当前一张或多张表的构建状态生成 SELECT，或立即执行

```text
public function select($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true 仅返回 SQL，false 调用驱动 query()；多表前缀采用第一表设置（默认：false） |

**返回值**：string|int|false 预览 SQL、驱动执行结果或失败 false

**异常与失败**：LogicException 未先调用 build() 建立任何表

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1017`。

### base_sql::update

功能说明：将单表或关联表的字段及条件构建为UPDATE并预览或执行

```text
public function update($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true仅返回生成的SQL，false执行语句（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回驱动query的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1106`。

### base_sql::delete

功能说明：依据当前构建条件生成DELETE并预览或执行

```text
public function delete($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true仅返回生成的SQL，false执行语句（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回驱动执行结果；无条件且事务外可能使用TRUNCATE

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1161`。

### base_sql::record

功能说明：读取查询第一条记录，支持可空记录缓存并清理结果和构建状态

无最外层 LIMIT 的普通 SELECT 会补 LIMIT 1，且放在锁定子句前；已有 LIMIT 不改写。

```text
public function record($sql='', $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL；空字符串由当前构建器生成（默认：''） |
| `$mode` | int；结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回值**：array|false 首条记录；无记录或驱动执行失败时为 false，SQL NULL 留在字段值中

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1210`。

### base_sql::records

功能说明：读取全部记录，按数据库缓存策略复用查询结果，保留空数组

```text
public function records($sql='', $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL，或简单 表名/数据库.表名；空字符串从当前构建器生成（默认：''） |
| `$mode` | int；结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回值**：array|false 记录数组，无记录为 []，执行失败为 false；结束时释放结果和构建状态

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1243`。

### base_sql::result

功能说明：通过 record() 的数字下标模式读取第一行第一列

```text
public function result($sql='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL；空字符串从当前构建器生成（默认：''） |

**返回值**：mixed 单值，保留 null、0、空字符串；无记录或失败返回 false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1265`。

### base_sql::count

功能说明：统计原查询的结果行数，保留 DISTINCT、GROUP/HAVING 和 UNION 语义

只移除最外层排序、分页及锁定子句，再包装为 count(*) 子查询；不直接替换投影列。

```text
public function count($sql='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SELECT/WITH 查询；空字符串从当前构建器生成（默认：''） |

**返回值**：int|string|false 计数值（类型依驱动），非 SELECT/WITH 返回 0，执行失败为 false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1278`。

### base_sql::fetchAll

功能说明：绑定参数读取全部记录，不参与共享结果缓存

无论成功还是异常都释放结果与构建状态；需要驱动提供 execute()。

```text
public function fetchAll($sql, array $params = [], $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SQL 模板；MySQL 用 ?，SQLite 用 ? 或命名参数 |
| `$params` | array；绑定数据值；不能替代表名、字段名、排序方向及 SQL 片段（默认：[]） |
| `$mode` | int；结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回值**：array|false 所有记录，空结果为 []；驱动执行失败为 false，保留原生标量类型

**异常与失败**：Throwable 驱动参数校验及查询异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1298`。

### base_sql::fetchOne

功能说明：绑定参数读取第一条记录，不参与共享结果缓存

不自动改写 SQL，较大结果集应在模板中显式 LIMIT；结束或异常时释放结果及构建状态。

```text
public function fetchOne($sql, array $params = [], $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SQL 模板，参数形式由具体驱动决定 |
| `$params` | array；按驱动约定绑定的数据值，不代替标识符（默认：[]） |
| `$mode` | int；结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回值**：array|false 首条记录；无记录或执行失败为 false

**异常与失败**：Throwable 驱动参数校验及查询异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1318`。

### base_sql::fetchValue

功能说明：绑定参数读取第一行第一列，保留 SQL NULL、0 及空字符串

调用示例：
    $value = $db->fetchValue('SELECT value FROM items WHERE id=?', [1]);
    if ($value === false) {
        // 无记录或执行失败；不要把0、空串或SQL NULL当作false
    }

```text
public function fetchValue($sql, array $params = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SQL 模板，按驱动约定提供占位符 |
| `$params` | array；数据值参数，不代替表名、字段名或 SQL 片段（默认：[]） |

**返回值**：mixed 第一列值；无记录或执行失败返回 false，SQL NULL 返回 null

**异常与失败**：Throwable 驱动参数校验及查询异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1341`。

### base_sql::correctDelimiter

功能说明：转换引号和注释之外的 MySQL 简单标识符定界符，保留用户文本

```text
public function correctDelimiter($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信 SQL；仅转换反引号包围的单词标识符，不是完整的方言转换器 |

**返回值**：string 使用当前驱动左右定界符的 SQL，并移除两端换行

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1352`。

### SQLBuilder::__construct

功能说明：初始化表名、关联设置、别名、定界符及驱动值转义器

```text
public function __construct($tbl, $join = array(), $idx='', $delimiter='', $escaper = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tbl` | string；表名或 数据库.表名，分段规范化并添加定界符 |
| `$join` | array\|null；联表配置，mode 默认 left，field 为当前表字段，field_join 为目标字段（默认：array()） |
| `$idx` | string；表别名，空字符串不添加别名（默认：''） |
| `$delimiter` | string\|array；左右相同的定界符，或 [左, 右]；空字符串不添加（默认：''） |
| `$escaper` | callable\|null；接收标量并返回已转义文本（不含外层引号）；null 沿用旧规则（默认：null） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1433`。

### SQLBuilder::safeValue

功能说明：使用驱动提供的值转义回调，未提供时使用 MySQL 转义

```text
public function safeValue($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；字段值；布尔先转整数，null 在此辅助入口转空字符串 |

**返回值**：string 已转义但未加外层引号的文本；NULL 条件及参数绑定由其他入口处理

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->safeValue($value);
```

定位：`lib/myDb.class.php:1475`。

### SQLBuilder::reset

功能说明：清空字段、值、条件、排序、分组、分页及 SELECT 前缀

```text
public function reset($complete = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$complete` | bool；true 另外清空表名、别名及关联设置；false 保留这些结构信息（默认：false） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->reset($complete);
```

定位：`lib/myDb.class.php:1487`。

### SQLBuilder::exists

功能说明：追加 EXISTS/NOT EXISTS 子查询条件，沿用当前驱动转义器

```text
public function exists($para, $condition=[], $not = false, $mode='and');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|array；可信完整子查询，或 [子表名, 与外层同名的关联字段] |
| `$condition` | array\|string；数组形式子表的额外 where 条件；完整 SQL 模式不使用（默认：[]） |
| `$not` | bool；true 生成 NOT EXISTS，默认 EXISTS（默认：false） |
| `$mode` | string；与已有条件的连接方式，and 或 or，非 and 值按 or（默认：'and'） |

**返回值**：$this 当前构建器；没有额外条件时不输出多余 AND

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->exists($para, $condition, $not, $mode);
```

定位：`lib/myDb.class.php:1511`。

### SQLBuilder::condition

功能说明：按操作符规则原地规范化一个条件的字段、操作符和值

处理普通比较、数值/日期/原始表达式、LIKE/REGEXP、IN 及 NULL 等。
原始表达式及函数形式须来自可信代码；本辅助方法不替代参数绑定。

```text
protected function condition(&$field, &$condition=null, &$value=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$field` | string；引用参数；输入字段/可信表达式，输出规范字段或恒真/恒假表达式 |
| `&$condition` | string\|null；引用参数；输入比较符，输出实际 SQL 操作符；! 表示原始条件（默认：null） |
| `&$value` | mixed；引用参数；输入数据，输出转义并加引号的 SQL 值、数值或可信表达式（默认：null） |

**返回值**：void null 相等比较转 IS NULL，不等转 IS NOT NULL；空 IN/NOT IN 明确布尔化

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1549`。

### SQLBuilder::condition_multi

功能说明：递归生成带括号的复合条件，保留每层逻辑连接方式

```text
protected function condition_multi($condition, $layer=0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$condition` | array\|string；可信 SQL 片段、[字段, 操作符, 值, 可选连接方式]，<br>                               或子条件列表（末项可指定 and/or）；空列表生成空片段 |
| `$layer` | int；当前递归深度，默认 0；内部子层使用 layer+1（默认：0） |

**返回值**：string 生成的条件片段；子层含连接词，最外层不额外添加连接词

**异常与失败**：InvalidArgumentException 非空输入不是支持的条件结构

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1698`。

### SQLBuilder::where

功能说明：追加条件、读取条件 SQL，或清空条件

支持三元组、嵌套组和字段到值的关联映射（数组值使用 IN）；=null 转 IS NULL。
空数组等值过滤产生假条件，避免条件被忽略后扩大查询或写入范围。

```text
public function where($field=null, $condition=null, $value=null, $mode='and');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$field` | string\|array\|null；字段、可信表达式或条件数组；null 读取 SQL；[reset] 清空（默认：null） |
| `$condition` | string\|null；比较符；数组条件形式可使用 and/or 指定与前文的连接方式（默认：null） |
| `$value` | mixed；比较值；普通数组可展开，IN 使用成员列表，原始表达式须可信（默认：null） |
| `$mode` | string；与前文连接的 and/or，默认 and；条件数组自身也可带连接方式（默认：'and'） |

**返回值**：$this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->where($field, $condition, $value, $mode);
```

定位：`lib/myDb.class.php:1741`。

### SQLBuilder::field

功能说明：设置查询列或写入字段，读取时使用局部副本避免重复预览改写状态

```text
public function field($field = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$field` | string\|array\|null；列字符串/列表、含别名的列、写入字段映射；可传多个位置参数；<br>                               null 读取片段，[reset] 清空，all/distinct/distinctrow 设置前缀（默认：null） |
| `...$args` | mixed；入口的附加字段参数，按field内部规则组合（位置参数，由func_get_args读取） |
| `$fields` | mixed；位置参数说明：多位置参数形式的补充列，存在时与第一个参数一起作为列列表 |

**返回值**：$this|string 设置/重置返回当前构建器，无参返回列清单或赋值片段

写入映射沿用 null 文本、+N/-N、括号表达式等特殊语义；外部值应优先使用参数绑定。

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->field($field);
```

定位：`lib/myDb.class.php:1823`。

### SQLBuilder::fieldList

功能说明：分割投影列表，函数参数、字符串和子查询中的逗号保持原样

```text
protected static function fieldList($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；可信 SELECT 列列表 |

**返回值**：array 去除两端空白后的列片段

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myDb.class.php:1897`。

### SQLBuilder::values

功能说明：直接添加insert的插入值

```text
public function values();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；一行的位置值列表，或若干行数组；与field列顺序对应（位置参数，由func_get_args读取） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->values();
```

定位：`lib/myDb.class.php:1916`。

### SQLBuilder::order

功能说明：追加排序字段，支持数组形式的排序方向，读取时不修改内部状态

```text
public function order($field=null, $desc = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$field` | string\|array\|null；字段或可信函数表达式，亦可传 [字段, 是否降序]；<br>                               null 读取排序 SQL，[reset] 清空排序（默认：null） |
| `$desc` | bool；true 降序、false 升序；数组模式使用其第 1 项，函数模式忽略（默认：false） |

**返回值**：$this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->order($field, $desc);
```

定位：`lib/myDb.class.php:1936`。

### SQLBuilder::limit

功能说明：设置或读取分页片段，数值转为非负整数

```text
public function limit($start=null, $count=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$start` | int\|string\|array\|null；单参数表示条数；与 count 合用表示偏移；<br>                                    可传 [偏移, 条数] 或逗号/空格分隔文本；null 读取，[reset] 清空（默认：null） |
| `$count` | int\|null；第二参数表示条数；仅当 start 为数值时采用（默认：null） |

**返回值**：$this|string|null 设置返回当前构建器，无参返回含 limit 的片段，[reset] 不返回值

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->limit($start, $count);
```

定位：`lib/myDb.class.php:1976`。

### SQLBuilder::group

功能说明：设置 GROUP BY 及可选 HAVING，或读取当前分组结构

```text
public function group($group=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$group` | string\|array\|null；分组字段，或含 field/having 的数组，也可传 [字段, 条件]；<br>                               null 返回当前结构，字段列表可为逗号字符串或数组（默认：null） |
| `...$args` | mixed；第二位置参数：HAVING条件（位置参数，由func_get_args读取） |

**返回值**：$this|array 设置返回当前构建器；读取返回结构数组，SQL 生成时不改写其内容

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->group($group);
```

定位：`lib/myDb.class.php:2005`。

### SQLBuilder::select

功能说明：生成 SELECT SQL，保留去重前缀、条件、排序、分组/HAVING 和分页

参数说明：无显式参数。

```text
public function select();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string SQL 文本，不执行查询；分组及字段预览不消耗已有构建状态

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->select();
```

定位：`lib/myDb.class.php:2033`。

### SQLBuilder::insert

功能说明：生成 INSERT/REPLACE，列列表支持 field(列名)，位置行使用局部副本，预览不消耗待写数据

```text
public function insert($replace=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$replace` | bool；是否生成REPLACE而非INSERT语句（默认：false） |

**返回值**：string 普通值仍沿用旧函数表达式/null 文本约定；不可信值优先参数绑定

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->insert($replace);
```

定位：`lib/myDb.class.php:2076`。

### SQLBuilder::update

功能说明：生成更新查询语句

参数说明：无显式参数。

```text
public function update();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 根据当前字段/条件/排序/分页生成的UPDATE文本，不执行SQL

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->update();
```

定位：`lib/myDb.class.php:2118`。

### SQLBuilder::delete

功能说明：生成删除查询语句

参数说明：无显式参数。

```text
public function delete();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 根据当前条件生成的DELETE/TRUNCATE文本，不执行SQL

调用形式：

```php
// $instance为已初始化的SQLBuilder实例。
$result = $instance->delete();
```

定位：`lib/myDb.class.php:2143`。
