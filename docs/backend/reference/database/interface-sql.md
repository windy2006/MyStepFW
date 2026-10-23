# interface_sql：interface_sql 数据库驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/interface_sql.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 在应用已取得当前$db的上下文中，先检测能力再调用。
if ($db->supports(interface_db_prepared::class)) {
    $row = $db->fetchOne('SELECT id,title FROM items WHERE id=?', [$id]);
}
// items为业务示意表，$id必须先验证；连接参数由当前应用配置提供。
```

## 声明与类职责

### interface_sql

功能说明：保留历史 SQL 结果读取、数据库管理及脚本维护协议。

本文件从 interface_db.class.php 拆出，(new) 标记表示新增文件，接口本身原已存在。
协议与 interface_db 独立；SQLite 未完整提供这些管理入口，不声明实现。
不能据此推断事务、原生扩展安装状态或连接健康。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_sql::getRS` | 从当前结果读取下一行；调用方负责消费完成后按驱动约定释放结果。 |
| `interface_sql::getDBs` | 列出当前账号可见的数据库，筛选规则和权限以驱动及服务器为准。 |
| `interface_sql::getTbls` | 列出数据表；可选库名、过滤和详细信息参数由具体驱动扩展。 |
| `interface_sql::getPri` | 获取历史单字段键标识；部分驱动无主键时会回退唯一键/自增列。 |
| `interface_sql::getInsertId` | 读取最近插入的自增标识，取值范围与连接/触发器语义依驱动而定。 |
| `interface_sql::getStat` | 获取服务器状态信息；不能作为通用健康检查或事务能力证明。 |
| `interface_sql::getCreateScript` | 导出建表脚本；不同方言输出不可直接跨引擎回放。 |
| `interface_sql::getDataScript` | 将表数据导出为完整SQL字符串，不提供跨表一致性快照或流式备份保证。 |
| `interface_sql::getIdxScript` | 导出独立索引定义；MySQL为建表片段，MSSQL为CREATE INDEX语句。 |
| `interface_sql::batchExe` | 顺序执行一条或多条可信SQL；不自动创建事务，失败策略由驱动控制。 |
| `interface_sql::handleSQL` | 按驱动方言切分可信脚本；不执行、不做安全过滤或完整语法校验。 |
| `interface_sql::file` | 读取并执行可信本地SQL文件；内置驱动可附加find/replace替换参数。 |
| `interface_sql::check` | 检查驱动内部连接/结果属性；仅检查对象或资源形态，不发起远程探活。 |
| `interface_sql::setCache` | 挂接共享查询缓存；事务隔离、SQL资格及失效传播仍由驱动负责。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_sql::getRS

功能说明：从当前结果读取下一行；调用方负责消费完成后按驱动约定释放结果。

内置驱动额外支持mode：1数字下标、2字段名、3两者，默认2。

参数说明：无显式参数。

```text
public function getRS();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|false|null 当前记录或结果结束/失败标记，依驱动约定

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:63`。

### interface_sql::getDBs

功能说明：列出当前账号可见的数据库，筛选规则和权限以驱动及服务器为准。

参数说明：无显式参数。

```text
public function getDBs();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 数据库名称列表

**异常与失败**：Throwable 查询异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:73`。

### interface_sql::getTbls

功能说明：列出数据表；可选库名、过滤和详细信息参数由具体驱动扩展。

参数说明：无显式参数。

```text
public function getTbls();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 表名列表或详细信息列表

**异常与失败**：Throwable 查询异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:83`。

### interface_sql::getPri

功能说明：获取历史单字段键标识；部分驱动无主键时会回退唯一键/自增列。

```text
public function getPri($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名；不保证完整表达复合主键 |

**返回值**：string|false 键字段名或未找到标记，依驱动约定

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:91`。

### interface_sql::getInsertId

功能说明：读取最近插入的自增标识，取值范围与连接/触发器语义依驱动而定。

参数说明：无显式参数。

```text
public function getInsertId();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|string|false 标识值，0或false是否表示未插入须按驱动区分

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:100`。

### interface_sql::getStat

功能说明：获取服务器状态信息；不能作为通用健康检查或事务能力证明。

参数说明：无显式参数。

```text
public function getStat();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|string 状态映射，未连接时内置驱动返回空字符串

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:109`。

### interface_sql::getCreateScript

功能说明：导出建表脚本；不同方言输出不可直接跨引擎回放。

```text
public function getCreateScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名，附加数据库参数由具体驱动提供 |

**返回值**：string 建表SQL；是否含独立索引/约束依驱动说明

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:117`。

### interface_sql::getDataScript

功能说明：将表数据导出为完整SQL字符串，不提供跨表一致性快照或流式备份保证。

```text
public function getDataScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名 |

**返回值**：string INSERT脚本，空表可为空字符串

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:125`。

### interface_sql::getIdxScript

功能说明：导出独立索引定义；MySQL为建表片段，MSSQL为CREATE INDEX语句。

```text
public function getIdxScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名 |

**返回值**：array 脚本/片段列表，不能假设每项都能单独执行

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:133`。

### interface_sql::batchExe

功能说明：顺序执行一条或多条可信SQL；不自动创建事务，失败策略由驱动控制。

```text
public function batchExe($SQLs);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$SQLs` | string\|array；SQL文本或按顺序执行的SQL列表 |

**返回值**：array|false 每项为[原SQL,执行结果]；空批次为[]，未连接为false

**异常与失败**：Throwable 执行异常可中断后续语句，已完成部分不自动回滚

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:142`。

### interface_sql::handleSQL

功能说明：按驱动方言切分可信脚本；不执行、不做安全过滤或完整语法校验。

```text
public function handleSQL($strSQL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$strSQL` | string；脚本内容；DELIMITER等支持范围见驱动说明 |

**返回值**：array 可执行语句列表，空脚本为[]

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:150`。

### interface_sql::file

功能说明：读取并执行可信本地SQL文件；内置驱动可附加find/replace替换参数。

```text
public function file($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；SQL文件路径，不应直接采用外部请求提供的路径 |

**返回值**：array|false 逐句执行结果，文件缺失/未连接时返回false

**异常与失败**：Throwable 文件或SQL执行失败依具体驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:159`。

### interface_sql::check

功能说明：检查驱动内部连接/结果属性；仅检查对象或资源形态，不发起远程探活。

```text
public function check($obj);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$obj` | string；内置驱动通常为connect或result，类型检测可选参数依驱动决定 |

**返回值**：bool 是否具有所需内部对象/资源

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:167`。

### interface_sql::setCache

功能说明：挂接共享查询缓存；事务隔离、SQL资格及失效传播仍由驱动负责。

```text
public function setCache($cache, $ttl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cache` | interface_cache\|myCache\|null；提供get/set的缓存后端，null关闭挂接 |
| `$ttl` | int；缓存有效秒数，内置驱动可省略并使用600 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_sql.class.php:176`。
