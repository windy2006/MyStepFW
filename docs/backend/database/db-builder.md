# SQL构造

使用build选择表，依次设置field、where、order和limit，再执行读取或写入。参数值优先使用绑定接口，字段和表达式按构建器规则填写。

```php
$db = new myDb('MySQL', $host, $user, $password, 'utf8mb4');
$db->connect(false, $database);
$db->build('items')->field('id,title')->where('active', 'n=', 1)->order('id')->limit(10);
$rows = $db->records();
```

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
| mixed ...$args 入口的附加字段参数，按field内部规则组合（位置参数，由func_get_args读取） |
| mixed $fields 位置参数说明：多位置参数形式的补充列，存在时与第一个参数一起作为列列表 |

**返回**：$this|string 设置/重置返回当前构建器，无参返回列清单或赋值片段

写入映射沿用 null 文本、+N/-N、括号表达式等特殊语义；外部值应优先使用参数绑定。

### SQLBuilder::values

功能说明：直接添加insert的插入值

```text
public function values();
```

| 参数说明 |
| --- |
| mixed ...$args 一行的位置值列表，或若干行数组；与field列顺序对应（位置参数，由func_get_args读取） |

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
| mixed ...$args 第二位置参数：HAVING条件（位置参数，由func_get_args读取） |

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
