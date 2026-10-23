# SQLBuilder条件、字段和分页

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

区分代理、驱动和可选能力；检查真实声明及返回值，不能把false、null、0或空集合统一当失败。

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


## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

## 主要方法：参数、返回和失败约定

### base_sql::build

功能说明：获取或创建指定表的查询构建器，沿用驱动转义器及定界符

```text
public function build($tbl, $join=null);
```

| 参数说明 |
| --- |
| string $tbl 表名或 数据库.表名，各段按 safeName() 规范化；[reset] 清空全部构建器 |
| array\|null $join 联表设置，含 mode、field 和可选 field_join；重用时可更新联表信息（默认：null） |

**返回**：SQLBuilder|null 正常表名返回对应构建器；[reset]重置所有构建状态并返回null

### SQLBuilder::where

功能说明：追加条件、读取条件 SQL，或清空条件

支持三元组、嵌套组和字段到值的关联映射（数组值使用 IN）；=null 转 IS NULL。
空数组等值过滤产生假条件，避免条件被忽略后扩大查询或写入范围。

```text
public function where($field=null, $condition=null, $value=null, $mode='and');
```

| 参数说明 |
| --- |
| string\|array\|null $field 字段、可信表达式或条件数组；null 读取 SQL；[reset] 清空（默认：null） |
| string\|null $condition 比较符；数组条件形式可使用 and/or 指定与前文的连接方式（默认：null） |
| mixed $value 比较值；普通数组可展开，IN 使用成员列表，原始表达式须可信（默认：null） |
| string $mode 与前文连接的 and/or，默认 and；条件数组自身也可带连接方式（默认：'and'） |

**返回**：$this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值

### SQLBuilder::field

功能说明：设置查询列或写入字段，读取时使用局部副本避免重复预览改写状态

```text
public function field($field = null);
```

| 参数说明 |
| --- |
| string\|array\|null $field 列字符串/列表、含别名的列、写入字段映射；可传多个位置参数；<br>                               null 读取片段，[reset] 清空，all/distinct/distinctrow 设置前缀（默认：null） |
| mixed ...$args 兼容入口的附加字段参数，按field内部规则组合（历史位置参数，由func_get_args读取） |
| mixed $fields 历史位置参数说明：多位置参数形式的补充列，存在时与第一个参数一起作为列列表 |

**返回**：$this|string 设置/重置返回当前构建器，无参返回列清单或赋值片段

写入映射沿用 null 文本、+N/-N、括号表达式等特殊语义；外部值应优先使用参数绑定。

### SQLBuilder::values

功能说明：直接添加insert的插入值

```text
public function values();
```

| 参数说明 |
| --- |
| mixed ...$args 一行的位置值列表，或若干行数组；与field列顺序对应（历史位置参数，由func_get_args读取） |

**返回**：$this 返回当前实例，支持链式调用。

### SQLBuilder::order

功能说明：追加排序字段，支持数组形式的排序方向，读取时不修改内部状态

```text
public function order($field=null, $desc = false);
```

| 参数说明 |
| --- |
| string\|array\|null $field 字段或可信函数表达式，亦可传 [字段, 是否降序]；<br>                               null 读取排序 SQL，[reset] 清空排序（默认：null） |
| bool $desc true 降序、false 升序；数组模式使用其第 1 项，函数模式忽略（默认：false） |

**返回**：$this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值

### SQLBuilder::limit

功能说明：设置或读取分页片段，数值转为非负整数

```text
public function limit($start=null, $count=null);
```

| 参数说明 |
| --- |
| int\|string\|array\|null $start 单参数表示条数；与 count 合用表示偏移；<br>                                    可传 [偏移, 条数] 或逗号/空格分隔文本；null 读取，[reset] 清空（默认：null） |
| int\|null $count 第二参数表示条数；仅当 start 为数值时采用（默认：null） |

**返回**：$this|string|null 设置返回当前构建器，无参返回含 limit 的片段，[reset] 不返回值

### SQLBuilder::group

功能说明：设置 GROUP BY 及可选 HAVING，或读取当前分组结构

```text
public function group($group=null);
```

| 参数说明 |
| --- |
| string\|array\|null $group 分组字段，或含 field/having 的数组，也可传 [字段, 条件]；<br>                               null 返回当前结构，字段列表可为逗号字符串或数组（默认：null） |
| mixed ...$args 兼容第二位置参数：HAVING条件（历史位置参数，由func_get_args读取） |

**返回**：$this|array 设置返回当前构建器；读取返回结构数组，SQL 生成时不改写其内容

## 明确调用示例

```php
$db = new myDb('SQLite'); // 独立内存库，不使用业务数据库。
$db->execute('CREATE TABLE items(id INTEGER PRIMARY KEY,title TEXT)');
$db->execute('INSERT INTO items(id,title) VALUES(?,?)', [1,'Hello']);
assert($db->fetchOne('SELECT * FROM items WHERE id=?', [1])['title'] === 'Hello');
$db->transaction(function ($tx) { $tx->execute('UPDATE items SET title=? WHERE id=?', ['World',1]); });
assert($db->fetchValue('SELECT title FROM items WHERE id=?',[1]) === 'World');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
