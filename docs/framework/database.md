# 数据库接口、调用与驱动

整理日期：2026-10-18。目标运行环境：PHP 7.4。接口能力以最新独立接口契约为准；MSSQL 的替身验证不能视为实库集成。

## 现在的接口结构

| 接口 | 范围 | 内置实现 |
| --- | --- | --- |
| interface_db | 历史连接、CRUD、单条/全部/单值读取及字段信息，共 12 个方法 | MySQL、MSSQL、SQLite、simpleDB |
| interface_sql | 历史结果遍历、库表列表、脚本维护、缓存挂接等，共 14 个方法 | MySQL、MSSQL |
| interface_db_prepared | execute、fetchAll、fetchOne、fetchValue，共 4 个方法 | MySQL、MSSQL、SQLite |
| interface_db_transaction | begin、commit、rollback、transaction，共 4 个方法 | MySQL、SQLite |

四个接口均有与名称对应的独立 `.class.php` 文件。旧的 `require_once 'interface_db.class.php'` 会继续载入原来两个接口，并载入两个可选能力接口；先加载独立文件、再加载旧入口也不会重复声明。

新能力接口彼此独立，也不继承混合了文件数据库或服务器管理方法的旧接口。SQLite 不会为了提供参数绑定就被迫实现数据库列表、状态和脚本文件导入；simpleDB 不会被要求实现 SQL 或事务。

所有接口均补充类说明、方法功能、参数、返回及必要的错误/行为边界，覆盖 34 个声明方法。myDb 新增 supports 方法也补充功能说明和参数备注。

## 保留旧签名的原因

两个旧接口的 26 个方法均保留原来的名称、参数名称、必需参数个数、引用规则及无原生类型声明。没有在旧接口中直接追加 execute/事务，也没有添加形式上“可选”的新参数。

接口增加可选参数仍可能使原先不接收该参数的第三方实现无法通过 PHP 的兼容性检查；把 record 的参数改成可选，也可能使原本保留必需参数的实现不再兼容。强制统一返回类型则会与现有连接返回 bool/驱动对象、关闭返回计数/无返回值等差异冲突。因此用 PHPDoc 说明历史行为，用可选接口表达新增能力。

专项用两个保留原始最小签名的第三方替身分别实现旧接口，并核对所有旧签名；只有方法存在、没有声明新接口的旧驱动仍能调用原方法，但不会被自动认定为已满足新协议。需要能力声明的第三方驱动可自愿实现新接口，并按 PHP 检查的签名完成实现。

## 能力检查与调用

```php
$db = new myDb('MySQL', $host, $user, $password, 'utf8mb4');
$db->connect(false, $database);

if ($db->supports(interface_db_prepared::class)) {
    $row = $db->fetchOne('SELECT * FROM items WHERE id=?', [$id]);
}

if ($db->supports(interface_db_transaction::class)) {
    $db->transaction(function ($tx) use ($id, $value) {
        $tx->execute('UPDATE items SET value=? WHERE id=?', [$value, $id]);
    });
}
```

supports 检查内部代理目标是否明确声明指定接口；未初始化、非法/未知名称、普通类名返回 false，也可用于可自动加载的自定义接口。不运行 SQL，不证明扩展已安装、连接健康、数据库权限充分或当前表是事务引擎。自定义自动加载器的异常继续传给调用方。

myDb 自身继续作为通用代理，不伪装成所有目标都具有参数绑定/事务能力的接口对象。因此 `$db->supports(interface_db_prepared::class)` 为 true，不代表 `$db instanceof interface_db_prepared` 为 true。需要接口类型约束时直接接收驱动对象：

```php
function readValue(interface_db_prepared $driver, $id) {
    return $driver->fetchValue('SELECT value FROM items WHERE id=?', [$id]);
}

$driver = new SQLite(':memory:');
// 创建/准备表后，可将 $driver 交给 readValue。
```

驱动 transaction 回调接收驱动自身；myDb 的 transaction 回调接收当前代理，保持原有调用方式。

## 返回值与方言边界

- execute/query 的成功计数不能统一按真假判断。SQLite 读查询成功返回 0，MSSQL 无法确定行数时可返回 -1；应以 `=== false` 判断显式执行失败，并按驱动错误策略处理异常。
- 新 fetchAll 空结果为 `[]`；fetchOne 无记录为 false；fetchValue 保留 SQL NULL、0、空字符串，无记录/显式失败为 false。布尔字段 false 与无记录需要区分时，使用 fetchOne 后检查记录是否存在。
- 绑定只替代数据值，不替代表名、字段、排序方向或 SQL 片段。MySQL/MSSQL 使用位置占位符，SQLite 支持其原生命名形式；流参数和 SQLSRV 描述数组仍遵循驱动各自约定。
- 受管事务正常返回，包括返回 false，仍会提交；需要回滚须抛异常或使用明确的事务控制。已有外部事务、回调边界与隐式提交 DDL 的限制继续生效。
- interface_db 仍兼容 simpleDB 的文件/条件/行号语义，不能将其参数与 SQL 驱动互换。
- interface_sql 是历史管理能力集，不能等同所有 SQL 引擎。旧 getPri 不完整表达复合主键；getIdxScript 在 MySQL 中返回建表片段、MSSQL 返回完整语句，不能假设每项都是独立 SQL。
- 新接口不会让 SQL 方言、数据库异常、备份一致性和权限规则自动跨引擎统一。相关运行限制见 本文“数据库驱动改进记录”相关章节。

## 自动加载兼容

myDb 注册的数据库自动加载器为内置四个驱动和四个接口使用发布包中的规范文件名。PHP 名称不区分大小写，文件系统可能区分大小写；`MySQL`/`MYSQL` 会映射到 `mysql.class.php`，`simpleDB` 映射到 `simpleDB.class.php`，避免依赖 macOS 的宽松大小写匹配。第三方驱动仍按原类名查找文件，不批量改名，也不改变其他自动加载器。

## 参数化执行与共享缓存

参数只能代替值，标识符和 SQL 片段使用固定值、白名单及 quoteIdentifier。MySQL 参数化读取需要 mysqlnd，SQLite 支持自身命名形式；fetchOne 的大结果查询应显式 LIMIT。fetch* 不使用共享结果缓存，保留原生标量类型。

受管事务正常返回（包括 false）时提交；抛异常回滚。MySQL／SQLite 支持保存点嵌套；回调不自行提交或执行隐式提交 DDL。事务内不复用或发布共享查询缓存，提交后推进共享代次，回滚不发布未提交结果。

已知会话变量、临时表、锁定读取及非确定函数采用保守缓存策略。原生连接绕过驱动的 SQL 无法自动跟踪，调用者关闭缓存并自行失效。myCache::getData 只在数据库已 setCache 挂接失效后端时缓存，遵循读写位；未挂接则直接查询。跨进程可见性仍受后端故障和 TTL 限制，不承诺强一致。

MySQL 活动连接字符集决定文本转义，不再每次连接设置 3 秒 wait_timeout；安全且无会话依赖的读取可断线后重连一次，写入和事务不自动重放。

## 驱动与构建：验证范围

| 模块 | 本轮验证 |
| --- | --- |
| myDb / SQLBuilder / SQLStatement | 构建、绑定入口、SQL 扫描、计数、事务所有权、缓存命名空间 |
| MySQL | 本机真实 MySQL；创建随机独立测试库，在 finally 中关闭连接并删除，不操作正式业务表 |
| SQLite | 独立内存库和临时文件库、双连接缓存可见性、导出回放 |
| simpleDB | 临时文件、手工生成的旧格式文件、8 进程同时追加 96 条记录 |
| MSSQL | 没有本机 SQL Server 服务及 sqlsrv 扩展；完成纯 SQL 转换检查、缺失扩展检查，并用隔离的驱动替身验证 8 项原生调用约定；不能据此声称已完成真实 SQL Server 集成 |

原有 `interface_db` / `interface_sql` 的必需方法保持不变。参数化执行仍是具体 SQL 驱动能力，不把 SQL 接口强加给 simpleDB 或外部驱动。

## 驱动与构建：已修复的问题

### SQL 构建和扫描

- `limit(0)` 不再因 empty(0) 被丢弃；SELECT 保留 LIMIT 0，无条件 DELETE 加 LIMIT 0 不会生成 TRUNCATE。
- 无条件、无分页的旧 DELETE 在事务外仍沿用 TRUNCATE；在跟踪到的事务内生成 DELETE FROM，以便回滚。
- INSERT 的位置行使用局部副本，预览不会把已转义 SQL 写回待写数组。后续预览/执行可使用当前转义上下文；实际 null 保持 SQL NULL。
- 支持 `field('id,value')->values(...)` 的插入列清单，不再把它当作无列名的全表 VALUES。
- 投影按顶层逗号分割，保留 COALESCE 等函数参数中的逗号、引号及嵌套内容；单字母列别名保持有效。
- ORDER BY 的函数不再错误添加表别名前缀；构建 SELECT/UPDATE/DELETE 出现异常时恢复原别名。
- 旧日期元组 `where('created', 'd>', ['NOW()', 'y-1'])` 不再被拆成两个比较；字符串日期也能正常处理。
- 复合条件最多 64 层，防止循环/过深数组耗尽资源。
- `record()` 和 `count()` 正确处理末尾分号及行注释；关闭计数子查询前补换行。
- SQL 扫描器按方言区分 `--` 注释：MySQL 中没有后续空白的 `3--2` 保持算术语义。

### 事务与缓存

- SQLite 原生 SAVEPOINT 在未 BEGIN 时也会开始事务，现在跟踪根保存点、重名嵌套、ROLLBACK TO 和 RELEASE。
- 根保存点未提交时不复用或发布共享结果；释放根保存点提交后更新共享代次。
- 回调事务拒绝手写事务控制及直接调用 begin/commit/rollback 改变边界，框架自身的嵌套 transaction 继续通过保存点运行。
- 已知会隐式提交的 MySQL DDL、事务控制及 AUTOCOMMIT 修改，在受管事务执行前报 LogicException，避免先提交再发现范围改变。
- 这些检查不分析存储过程内部，也不能阻止绕过驱动的原生连接操作；仍要求事务表，文件/邮件等副作用不受数据库回滚保障。
- MySQL 重新 connect 且未选库时清空旧库身份，不再复用旧数据库上下文的缓存。SQL mode 策略也进入命名空间，隔离相同 SQL 在不同模式下的语义。

### MySQL

- `free()` 释放 CALL 等语句的后续结果，避免下一条查询出现 commands out of sync；预处理 CALL 也完成实际验证。
- 切换用户后恢复配置的字符集与 SQL mode 策略。
- BLOB 流已经到 EOF 时明确绑定空二进制，避免误成 SQL NULL；无进展的流读取明确报错，避免空转。
- 可选第五参数配置 SQL mode；默认空串兼容旧应用，null 保留服务端默认，字符串明确指定严格模式等策略。
- 建表导出直接使用 SHOW CREATE TABLE，减少一次 SHOW TABLE STATUS 查询，完整保留服务端表定义。
- 数据导出保持 NULL、十进制精度、BIT、字符串和二进制；数字按元数据输出数值，文本/二进制使用十六进制字面量，BIT 先显式转换为无符号值。
- 导出带列名并跳过生成列，避免恢复时报“不能写入生成列”。

### SQLite

- 拒绝混合位置/命名参数，以及 `a` 和 `:a` 这种重复归一化名称，避免一项被覆盖、另一项静默绑定为 NULL。
- 保留重复命名参数及 :/@/$ 前缀。PHP 7.4 原生 bindValue 不直接识别 $ 前缀，驱动只改写代码区中对应参数为不冲突的内部冒号名称，字符串/注释保持原样。
- 建表导出读取 sqlite_master 原始 SQL，保留默认值、复合主键、CHECK 和生成列；新增 `getIdxScript()` 返回独立索引定义。
- 数据导出保持数字、NULL、BLOB、含 NUL 的文本，跳过生成列；带空格/引号的表名可以正确引用。
- 关闭后调用错误检查或 getInsertId 不再对 null 对象调用方法。
- 写入语句的 RETURNING 在执行前明确拒绝。本机 PHP 7.4 的原生 SQLite3Stmt::execute 加 fetchArray 会重复执行该类写入，已独立复现；请使用普通 INSERT + getInsertId 或事务中的单独 SELECT。没有把该限制伪装成返回空结果的成功写入。

### simpleDB

保持原来的固定字节宽度 .db 格式，并完成以下调整：

- 路径不再要求目录末尾必须带斜杠；record(0) 取首行。
- 完整的读取、条件筛选、更新和压实在同一个共享/排他文件锁内完成；追加在锁内读取最新长度，避免覆盖其他进程的新行。
- 超过字段字节宽度、含 NUL/换行或超出文件预算的数据在写入前拒绝，不再覆盖下一字段或下一记录。
- 空条件更新/删除、值中的等号、显式查询条数和元数据偏移得到修正。
- write/w 连接只打开读写，不再隐式截断；清空使用 empty。旧 destory 拼写保留，增加 destroy 别名。
- 默认最大文件 32 MiB，可用 init 第四参调整；损坏表头/记录边界明确报错，不自动“修复”存量文件。
- 随机抽样使用 shuffle，避免不断碰撞重试和随机比较器不满足排序规则的问题。

simpleDB 面向小型本地表。操作会读取快照，写操作验证并重写完整文件，换取锁范围内的一致性；不提供 SQL 事务、断电原子性或大文件数据库的性能。已有大表建议迁移到 SQLite/MySQL。本轮没有批量重写业务 .db 文件。

### MSSQL

- 新增原生 `execute()` 参数绑定，允许 SQLSRV 参数描述数组；不对原生 T-SQL 做 MySQL 转换。fetchAll/One/Value 可通过此入口使用。
- 返回结果按原生字段数判断，避免把 WITH/EXEC 等结果误认作写入。
- 缺少 sqlsrv 时提供明确异常；切库引用完整标识符并更新库身份。
- safeValue 使用 T-SQL 的单引号加倍，保留反斜杠；Unicode 值优先参数绑定。
- 有限兼容转换保留字符串/注释内容；TOP 的位置支持 DISTINCT；偏移分页使用 SQL Server 2012+ OFFSET/FETCH，无排序时不承诺稳定分页。
- 修复文件导入 ALTER 分类条件和导出 INSERT 拼写，基本导出保留 NULL、Unicode、流二进制。

SQL Server 的旧 CREATE/REPLACE 方言转换、模式导出、IDENTITY、计算列、日期精度及触发器仍须真实服务专项验证。原生功能建议使用 execute(T-SQL)，完整备份使用数据库厂商工具；本轮不承诺任意 MySQL SQL 可以转换为 T-SQL。

## 驱动与构建：使用示例

```php
$db = new myDb('MySQL', $host, $user, $password, 'utf8mb4', [
    'sql_mode' => null, // 保留服务端默认；省略此选项仍兼容旧空模式
]);
$db->connect(false, $database);

$rows = $db->fetchAll('SELECT id,name FROM users WHERE status=?', [1]);
$value = $db->transaction(function ($tx) use ($name, $id) {
    $tx->execute('UPDATE users SET name=? WHERE id=?', [$name, $id]);
    return $tx->fetchOne('SELECT id,name FROM users WHERE id=?', [$id]);
});

// 参数不能代替标识符。数组表示分段名称，不将点号误认为当前名称的一部分。
$table = $db->quoteIdentifier([$database, 'order details']);
$row = $db->fetchOne('SELECT * FROM '.$table.' WHERE id=?', [$id]);
```

quoteIdentifier 只处理标识符语法，不代替应用的表/字段访问权限校验。旧 SQLBuilder 仍支持函数/原始表达式、增量值、字符串 null 等特殊含义；外部数据优先参数绑定。

```php
$sqlite = new myDb('SQLite', '/local/data/example.sqlite');
$sqlite->execute('INSERT INTO items(name) VALUES(:name)', ['name'=>'示例']);
$id = $sqlite->getInsertId();
$schema = $sqlite->getCreateScript('items');
$indexes = $sqlite->getIdxScript('items');
$data = $sqlite->getDataScript('items');
```

导出返回完整字符串，适用于小中型维护操作；不是流式备份，也没有自动取得跨表一致性快照。完整、大型或在线一致性备份使用对应数据库工具。SQLite 独立触发器不在 getCreateScript/getIdxScript 的返回范围内。

## 驱动与构建：导入脚本

MySQL 的 handleSQL 保留字符串中的分号、换行、连续空格及注释样式文本，也保留 `/*!...*/` 可执行注释；支持独立行 DELIMITER：

```sql
DELIMITER $$
CREATE PROCEDURE example_proc()
BEGIN
  SELECT 'a;b';
  SELECT 2;
END$$
DELIMITER ;
```

普通分割器不是完整方言解析器。过程/触发器体必须提供适当 DELIMITER；SQL Server GO 批次和 SQLite 触发器复合体不在普通分号分割的自动识别范围内。导入执行失败按驱动错误策略中断，不自动重试写入。

## 驱动与构建：兼容性及验证

新接口与改动方法已补充功能、参数、返回及限制说明。特别注意：零 LIMIT 现在有效；SQLite 非法/重复参数和 RETURNING 会在写入前报错；事务回调不允许自行提交；simpleDB 超长值和损坏格式明确失败，write 不截断。旧 SQL mode 默认仍保留，开启严格模式需由应用明确配置。

```sh
php tests/database/database_regression.php
php tests/database/database_regression.php --database
```

默认测试使用临时文件和独立内存库。--database 从当前配置读取连接参数，创建随机的 `mystep_db_review_*` 测试库，最后删除；不改写正式业务表。SQLSRV 替身只验证调用约定，不进行任何网络连接。

参考：[SQLite 保存点语义](https://www.sqlite.org/lang_savepoint.html)、[MySQL 隐式提交语句](https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html)、[PHP SQLite RETURNING 重复写入报告](https://bugs.php.net/bug.php?id=81134)、[SQLSRV 参数查询](https://learn.microsoft.com/en-us/sql/connect/php/sqlsrv-query?view=sql-server-ver17)。

## 后续补充：simpleDB 批量修改

`simpleDB::mutate($callback)` 在单个排他锁内取得最新记录并修改整批数据；回调抛异常时保留原文件。当前 Admin 插件排序使用此入口，避免分次更新覆盖其他记录。它不改变固定宽度存储预算，也不提供跨文件事务。具体签名和返回约定见 [simpleDB 源码](../../lib/database/simpleDB.class.php)。

## 接口专项验证

```sh
php tests/database/interface_regression.php
php tests/database/interface_regression.php --database
```

默认使用独立 PHP 加载进程、私有临时目录和真实 SQLite，验证独立加载、加载顺序、规范文件路径、旧签名与第三方实现、能力声明、返回值、嵌套事务和回调身份。数据库模式使用随机命名 `mystep_interface_review_*` MySQL 库验证真实 InnoDB 绑定、提交和回滚，finally 关闭连接并删除测试库；不以正式业务表作为夹具。

MSSQL 本轮验证类加载、协议声明、受管事务拒绝和空批次约定；已有数据库专项还含隔离的 SQLSRV 调用替身。本机没有真实 SQL Server 服务或 SQLSRV 扩展，不能将这些检查视为实库集成测试。
