# lib 功能索引与 PHPDoc 规范

整理日期：2026-10-18。目标运行环境：PHP 7.4。当前覆盖数来自本次只读 CLI 检查：52 个 PHP 文件、56 个类／接口／trait、860 个具名方法／函数、1289 个显式参数；问题为 0。

## 文件头统一格式

文件头保留作者及许可信息，随后按以下顺序说明：

1. **文件**：相对于网站根目录的位置。
2. **功能说明**：该文件负责的功能及包含的辅助类、trait或函数。
3. **方法目录**：逐个列出直接声明的方法/函数、可见性、静态标记、真实参数及默认值、功能摘要；按源码顺序排列。
4. **使用说明**：加载/初始化方式、主要调用顺序、返回/输出约定及适用边界。

类、接口、trait均有独立功能说明。方法采用PHPDoc：

```php
/**
 * 功能说明：读取有效的缓存条目，并通过引用参数报告命中状态。
 *
 * @param string|int $key 缓存键
 * @param bool &$hit 引用输出：有效记录为true，未命中或失败为false
 * @return mixed 缓存值；未命中返回false，应以hit判断
 */
```

每个显式参数都有类型及用途说明，有默认值时注明默认值。引用输入/输出写明数据流向；无参数入口注明“无显式参数”。通过 `func_get_args()` 接收的历史位置参数使用 `@param ...$args` 说明，不修改原生签名；已有详细位置参数说明继续保留。返回值保留/补充 `@return`，已核实的关键异常用 `@throws` 记录。

PHPDoc中的 `string|false`、`$this`、GD `resource` 等是说明类型，不是新增运行时约束。本轮维持PHP 7.4兼容，没有增加原生类型声明或 `strict_types`。

## 类型写法

普通`@param`/`@return`保留常用PHPDoc类型和中文描述；结构化细节写在`@phpstan-param`、`@phpstan-return`、`@phpstan-type`中。不认识扩展标签的工具仍可阅读普通类型和说明；这些标签不会引入运行时依赖，也不需要给PHP 7.4增加原生联合类型。

```php
/**
 * @param array $options 缓存编解码选项
 * @phpstan-param array{max_bytes?:int,max_depth?:int,allowed_classes?:bool|list<string>,namespace?:string} $options
 */
```

`?`表示可选字段，`list<T>`表示从0开始的连续整数键列表，`array<array-key,T>`允许整数或字符串键。只对结构稳定的数据细化；SQL字段、配置值或业务回调的原值仍可能需要mixed。这些扩展语法依据[PHPStan类型文档](https://phpstan.org/writing-php-code/phpdoc-types)，并未加入PHPStan生产依赖。

回调泛型用于关联输入回调与输出结果：

```php
/**
 * @param callable $producer 无参数计算回调
 * @return mixed 原始业务结果
 * @template TResult
 * @phpstan-param callable(): TResult $producer
 * @phpstan-return TResult
 */
```

缓存入口的TResult以同一业务键的存储类型和producer结果一致为前提。变更数据形状时更新业务键版本。事务回调正常返回false仍是正常完成，只有抛异常才按事务规则回滚；不要把它与execute返回false的失败约定混淆。

## 异常、返回值与资源

- `@throws`说明异常类型及触发条件；回调异常继续传播时明确注明。内部try/catch把故障转为false的入口写“异常处理”说明，不把已捕获异常当成调用者必须捕获的异常。
- PHP错误处理器可能终止请求或抛异常；文档描述预期入口行为，不枚举所有可能的PHP编程错误。
- 读取缓存以hit判断命中；保存文件以`===false`判断失败；执行SQL时0可能表示成功但没有影响行。
- `myImg::load()`直接返回的句柄由调用方释放；rotate/resize/crop显式传入图像时产生的新句柄也归调用方。不传图像时由实例管理新画布。
- MySQL BLOB参数从流的当前位置读取，不替调用者rewind或fclose；statement由驱动关闭，结果集按读取/释放入口管理。
- `myController::end()`通常返回调用方；`myStep::end()`最终exit。show/render及缓存命中的具体输出约定见方法说明。

## 代理提示的边界

`myDb`上的@method提供已知转发入口的IDE提示。文件驱动与SQL驱动的参数/返回语义不同；build适用于SQL后端；execute/fetch*先检查`supports(interface_db_prepared::class)`，受管事务先检查`supports(interface_db_transaction::class)`。有提示不等于当前代理对象拥有该能力。

`myCache`只提示基础协议的四个转发方法；lookup和计算锁仍由supports判断。`myReflection`虚拟属性随目标种类而变化，不适用或未初始化时可能为null。应用专用配置字段不写入通用框架的@property。

## 示例的使用

方法旁“调用示例”展示调用顺序；其中的$cache、$db、路径和业务变量由应用先初始化。数据库items表只是示例业务结构。类级`@example`指向已有Document演示文件，相对路径从注释所在源码文件目录解析，数据库子目录因此使用`../../app/...`。

检查器只检查引用文件是否存在，不include或执行演示。phpDocumentor对相对示例路径会尝试源码目录等位置，具体展示能力取决于版本，见[官方@example说明](https://docs.phpdoc.org/guide/references/phpdoc/tags/example.html)。网站Document页继续把注释作为HTML转义后的文本显示。

## 可重复运行的检查

在网站根目录执行：

```sh
php maintenance/check-phpdoc.php
php maintenance/check-phpdoc.php --json
php maintenance/check-phpdoc.php --self-test
```

检查器仅限CLI，使用PHP词法器读取源码，不加载业务类、不连接数据库、不执行示例，也不修改文件。检查失败返回退出码1；目录/参数错误返回2，便于接入现有脚本。

默认检查文件头、类/方法功能说明、全部直接声明的目录顺序及真实签名、参数覆盖/默认值/引用标记、返回类型与描述、重复联合类型、扩展类型括号、异常说明、代理提示格式及@example文件存在性。新增非公开方法和关键字名称的方法也会纳入检查，继承及trait别名不重复计数。

仅改注释时，可先在网站目录外保留lib快照，再比较可执行词元：

```sh
php maintenance/check-phpdoc.php --compare=/私有快照/lib --json
```

--compare按词元ID、原始字节及长度构建SHA-256指纹，排除注释和空白，检查两边PHP文件集合一致；大字典也不构造多份词元偏移数组进行比较。该检查确认未混入执行代码变化，不证明注释的每项业务解释都正确。

本检查器不是完整类型推断器：它不能证明每条运行路径、第三方驱动和回调返回值都符合文档，也不会验证所有工具版本对扩展标签的解析能力。修改功能时仍须结合对应回归及[类功能变更与调用同步](maintenance.md)。

## 本轮新增文件（new）

对照 SVN BASE 的文件清单，本轮维护新增以下 12 个文件。各文件沿用原有星号边框和 Name、Modifier、Time、Email、HomePage、Notice 元信息格式，并在 Name 行标注 `(new)`。已有头部的日期保留；此前没有元信息的文件以 `Time: 2026-10-18 (PHPDoc)` 标明本次注释整理日期，不作为创建日期。

| 位置 | 文件 | 作用 |
| --- | --- | --- |
| `lib/` | [myArchive.class.php (new)](../../lib/myArchive.class.php) | 归档解包暂存与回滚工具 |
| `lib/` | [myAttachment.class.php (new)](../../lib/myAttachment.class.php) | 上传附件索引与安全查找工具 |
| `lib/` | [myAuth.class.php (new)](../../lib/myAuth.class.php) | 密码与签名登录凭据工具 |
| `lib/` | [mySecurity.class.php (new)](../../lib/mySecurity.class.php) | 请求边界与输出安全辅助 |
| `lib/` | [myClassLoader.class.php (new)](../../lib/myClassLoader.class.php) | 类加载规则校验与配置管理工具 |
| `lib/` | [myPath.class.php (new)](../../lib/myPath.class.php) | 网站根相对路径规范化工具 |
| `lib/cache/` | [interface_cache_lock.class.php](../../lib/cache/interface_cache_lock.class.php) | 缓存计算互斥可选协议 |
| `lib/cache/` | [interface_cache_lookup.class.php](../../lib/cache/interface_cache_lookup.class.php) | 缓存精确命中可选协议 |
| `lib/cache/` | [myCacheCodec.class.php](../../lib/cache/myCacheCodec.class.php) | 缓存键、有效期与序列化公共工具 |
| `lib/database/` | [interface_db_prepared.class.php](../../lib/database/interface_db_prepared.class.php) | 数据库参数绑定可选协议 |
| `lib/database/` | [interface_db_transaction.class.php](../../lib/database/interface_db_transaction.class.php) | 数据库受管事务可选协议 |
| `lib/database/` | [interface_sql.class.php](../../lib/database/interface_sql.class.php) | SQL 数据库管理兼容协议 |

`database/interface_sql.class.php` 是将原 `interface_db.class.php` 内已有的接口拆为独立文件；此处 `(new)` 指新增文件，并非新创设的接口。其余已有文件即使本轮修改过，也不标为新增。

## 当前逐文件索引

| 文件 | 功能 | 直接声明数 |
| --- | --- | ---: |
| [cache/interface_cache.class.php](../../lib/cache/interface_cache.class.php) | 缓存驱动的基础兼容协议 | 4 |
| [cache/interface_cache_lock.class.php (new)](../../lib/cache/interface_cache_lock.class.php) | 缓存计算互斥可选协议 | 1 |
| [cache/interface_cache_lookup.class.php (new)](../../lib/cache/interface_cache_lookup.class.php) | 缓存精确命中可选协议 | 1 |
| [cache/myCacheCodec.class.php (new)](../../lib/cache/myCacheCodec.class.php) | 缓存键、有效期与序列化公共工具 | 10 |
| [cache/myCache_File.class.php](../../lib/cache/myCache_File.class.php) | 基于文件的缓存驱动 | 16 |
| [cache/myCache_MSSQL.class.php](../../lib/cache/myCache_MSSQL.class.php) | SQL Server缓存驱动 | 14 |
| [cache/myCache_MySQL.class.php](../../lib/cache/myCache_MySQL.class.php) | MySQL缓存驱动 | 14 |
| [chs2cht.dic.php](../../lib/chs2cht.dic.php) | 简繁与地区用语转换字典 | 2 |
| [database/interface_db.class.php](../../lib/database/interface_db.class.php) | 数据库历史基础兼容协议 | 12 |
| [database/interface_db_prepared.class.php (new)](../../lib/database/interface_db_prepared.class.php) | 数据库参数绑定可选协议 | 4 |
| [database/interface_db_transaction.class.php (new)](../../lib/database/interface_db_transaction.class.php) | 数据库受管事务可选协议 | 4 |
| [database/interface_sql.class.php (new)](../../lib/database/interface_sql.class.php) | SQL数据库管理兼容协议 | 14 |
| [database/mssql.class.php](../../lib/database/mssql.class.php) | SQL Server数据库适配器 | 31 |
| [database/mysql.class.php](../../lib/database/mysql.class.php) | MySQL数据库适配器 | 33 |
| [database/simpleDB.class.php](../../lib/database/simpleDB.class.php) | 文件型简单数据库 | 28 |
| [database/sqlite.class.php](../../lib/database/sqlite.class.php) | SQLite数据库适配器 | 22 |
| [function.php](../../lib/function.php) | 框架公共函数集合 | 17 |
| [myApi.class.php](../../lib/myApi.class.php) | 可注册的应用接口调用器 | 9 |
| [myArchive.class.php (new)](../../lib/myArchive.class.php) | 归档解包暂存与回滚工具 | 6 |
| [myAttachment.class.php (new)](../../lib/myAttachment.class.php) | 上传附件索引与安全查找工具 | 5 |
| [myAuth.class.php (new)](../../lib/myAuth.class.php) | 密码与签名登录凭据工具 | 9 |
| [myBase.class.php](../../lib/myBase.class.php) | 框架类的初始化与错误处理基类 | 3 |
| [myCache.class.php](../../lib/myCache.class.php) | 缓存代理与结果缓存入口 | 13 |
| [myClassLoader.class.php (new)](../../lib/myClassLoader.class.php) | 类规则、相对路径与原子保存 | 4 |
| [myConfig.class.php](../../lib/myConfig.class.php) | 配置读取、合并与原子保存管理器 | 21 |
| [myController.class.php](../../lib/myController.class.php) | 控制器公共能力与输出生命周期 | 42 |
| [myDb.class.php](../../lib/myDb.class.php) | 数据库代理与通用SQL实现 | 64 |
| [myEmail.class.php](../../lib/myEmail.class.php) | MIME邮件构建及SMTP传输工具 | 33 |
| [myEncrypt.class.php](../../lib/myEncrypt.class.php) | 字符串与文件加解密工具 | 8 |
| [myExcel.class.php](../../lib/myExcel.class.php) | SpreadsheetML格式Excel文档生成器 | 16 |
| [myException.class.php](../../lib/myException.class.php) | 框架错误、异常及日志处理器 | 10 |
| [myFile.class.php](../../lib/myFile.class.php) | 文件目录、路径与远程资源工具 | 42 |
| [myImg.class.php](../../lib/myImg.class.php) | GD图像绘制、变换及图表工具 | 49 |
| [myMinify.class.php](../../lib/myMinify.class.php) | JS/CSS压缩与文件缓存工具 | 10 |
| [myPacker.class.php](../../lib/myPacker.class.php) | 框架自定义压缩包管理器 | 12 |
| [myPath.class.php (new)](../../lib/myPath.class.php) | 网站根相对路径规范化和配置转换 | 4 |
| [myProxy.class.php](../../lib/myProxy.class.php) | 对象代理与兼容方法转发基类 | 9 |
| [myReflection.class.php](../../lib/myReflection.class.php) | PHP反射与注释读取封装 | 11 |
| [myReq.class.php](../../lib/myReq.class.php) | HTTP请求、Cookie与Session访问工具 | 33 |
| [myRouter.class.php](../../lib/myRouter.class.php) | 请求URL解析、匹配与路由调度器 | 15 |
| [mySecurity.class.php (new)](../../lib/mySecurity.class.php) | 请求边界与输出安全辅助 | 11 |
| [myStep.class.php](../../lib/myStep.class.php) | 框架主入口及应用执行协调器 | 45 |
| [myString.class.php](../../lib/myString.class.php) | 字符串、结构序列化与字符编码工具 | 31 |
| [myTemplate.class.php](../../lib/myTemplate.class.php) | 模板读取、编译、渲染与缓存引擎 | 39 |
| [myTrait.class.php](../../lib/myTrait.class.php) | 动态属性、方法扩展与别名公共trait | 17 |
| [myUploader.class.php](../../lib/myUploader.class.php) | HTTP文件上传与附件落盘工具 | 6 |
| [myZip.class.php](../../lib/myZip.class.php) | ZIP打包与受限解包工具 | 6 |
| [pinyin.php](../../lib/pinyin.php) | GBK字典驱动的中文拼音转换 | 2 |
| [session/interface_session.class.php](../../lib/session/interface_session.class.php) | PHP会话处理器兼容协议 | 6 |
| [session/sess_file.class.php](../../lib/session/sess_file.class.php) | 基于文件的Session处理器 | 14 |
| [session/sess_mysql.class.php](../../lib/session/sess_mysql.class.php) | 基于MySQL的Session处理器 | 16 |
| [session/sess_mystep.class.php](../../lib/session/sess_mystep.class.php) | CMS认证会话与在线统计处理器 | 12 |

## 本次只读核验

已执行 `php maintenance/check-phpdoc.php --json`：扩展类型 79 项，代理提示 24 项，示例引用 43 项，问题 0 项。该结果检查当前声明及注释覆盖，不代表重新运行所有业务回归或证明每条回调的运行类型。
