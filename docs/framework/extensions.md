# 应用接口、通用扩展、基础类与 XML Excel

整理日期：2026-10-18。目标运行环境：PHP 7.4。下文中的检查及验证记录保留原日期和适用边界。

更新：2026-10-16。目标环境：ServBay PHP 7.4.33。对应文件为 `lib/myApi.class.php`、`lib/myTrait.class.php`、`lib/myBase.class.php` 和 `lib/myExcel.class.php`。本轮只处理这四个类及相关回归、说明，不提交 SVN。

## 应用接口 myApi

- `regMethod()` 明确校验接口名及回调；`regMethods()` 支持数组/Traversable，整批验证后合并，失败不留下半批登记。名称区分大小写，数字键保留兼容。
- 新增 `hasMethod()`、`removeMethod()`、`invoke()`，原始调用不编码或发送头，保留 null/false/0/空数组。原始调用只执行注册表中的回调，未知接口抛 `BadMethodCallException`。
- `run()` 继续保留未知接口编码空字符串、旧格式别名及未知格式回退 JSON 的习惯。参数按数组遍历顺序传为位置参数，不依赖 PHP 8 命名参数。
- JSON 总是 UTF-8，使用异常式编码，明确报告非法 UTF-8、递归及不支持的值。不再为猜编码而递归改写回调返回的对象；非 UTF-8 业务数据应由调用方先明确转换。
- XML 使用 XMLWriter 转义，回退非法元素名为 item，移除 XML 1.0 禁止的控制符；null 表示空内容、布尔表示 true/false。对象循环和超过 64 层嵌套明确报错，数组自引用也有深度边界。
- JSON/XML/纯文本输出在编码成功且响应头尚未发送时设置相应 Content-Type；字符集标签拒绝控制符。XML 显式非 UTF-8 编码使用 iconv 转换，无法无损转换时报错。

```php
$api = new myApi();
$api->regMethod('sum', function ($a, $b) { return $a + $b; });
$value = $api->invoke('sum', [2, 3]); // 整数 5，不发送头
$json = $api->run('sum', [2, 3]);    // 文本 5，JSON 内容类型
$exists = $api->hasMethod('sum');
$api->removeMethod('sum');
```

JSON 的 charset 参数为兼容保留，不把 JSON 输出改成 GBK。XML 输入文本必须是 UTF-8，charset 指输出编码；string/code 模式仍将原内容按旧辅助函数转为文本，声明字符集由调用方与实际数据保持一致。`run()` 不 echo、不结束请求、不自动把异常改成成功响应，HTTP 状态及异常展示交给框架/应用。格式编码不是授权边界，原有 CSRF、认证、HTTP 方法限制继续由调用入口负责。

## 通用扩展 myTrait

- `addMethod()` 支持函数名（含命名空间）、方法数组、闭包和可调用对象；不再把字符串/数组强行交给 `Closure::bind()`。未绑定普通闭包在允许时绑定当前实例，已绑定/静态/内部回调保持原身份；闭包与可调用对象须显式命名。
- 新登记扩展方法按大小写不敏感匹配，优先于历史全局函数回退；别名保持最高优先级。没有匹配的魔术调用仍返回 null。
- `regAlias()` 支持未声明静态别名表的类，原有静态表保持同步；多级别名先校验循环再发布，失败保留原表。静态别名不能偷偷调用实例方法。
- `__isset()`、`__unset()` 只操作辅助属性，不暴露或删除受保护状态。析构只释放扩展与辅助引用，不再枚举并 unset 静态属性。
- `__sleep()` 保存实际实例属性（含父类私有值），排除静态表；`__wakeup()` 不重跑构造函数。`__set_state()` 按实际子类恢复属性，不触发构造及初始化副作用。
- 调试快照不再访问静态属性；含闭包时文本转换可安全回退类名，而正常 serialize() 仍明确拒绝闭包，不能据此宣称任意资源可持久化。
- 补充正确拼写的 `instance`，保留旧 `instatnce`。数值函数式调用不再重设全局 mt_rand；其确定性算法有变化。文本摘要扰动也不消耗 mt_rand 状态，不作为认证令牌。

```php
$object->addMethod(function ($value) { return $this->prefix.$value; }, 'join');
$object->addMethod('strtoupper', 'upper');
$object->join('hello');
$object->upper('hello');
SomeClass::regAlias(['short' => 'fullName']);
```

兼容边界：旧全局函数回退仍保留，不能把 `$object->$untrustedName()` 当作安全白名单。函数式映射赋值仍能修改当前作用域可写成员，只接收受信字段。需要从序列化状态恢复外部连接的类应自行覆盖 `__wakeup()`；普通恢复不再执行构造，是为了避免状态重置及重复副作用。直接序列化仍只用于受信数据。

## 基础类 myBase

- 构造只调用实际声明且当前作用域可调用的 init，不误触发 `__call('init')`。
- 无参数且 init 有必需参数时，保留延迟初始化；可选参数和变长参数入口正常初始化，显式错误参数继续由 PHP 报错。
- 按实际类缓存必需参数数目，避免重复反射；缓存不保留实例引用。
- `setErrHandler()` 接收 callable/null，非法参数明确报错且保留原回调。
- `error()` 临时修改的 `myException::callback_type` 在正常返回或错误处理器抛异常时通过 finally 恢复，避免一个对象改变后续请求内其他对象的回调策略。

默认 `exit_on_error` 与显式终止约定保留；直接 exit 会结束进程，不承诺执行 finally。没有安装错误处理器时，E_USER_ERROR 仍遵循 PHP 原生行为。

## XML Excel myExcel

这是 XML Spreadsheet 2003 导出，不是 ZIP 格式的 XLSX，也不是二进制 BIFF XLS。旧浏览器下载继续使用 .xls 扩展名。

- 修复删除当前表后把“表数据数组”误设为当前表名；删除非当前表不改变当前表。字符串 0 可作表名。
- 修复行号 0 被 empty() 当作缺省；无指定行时追加末行，无行时自动创建。显式非法/不存在行号报错，不静默改写末行。
- 工作表名称验证长度、非法字符和首尾单引号，按大小写不敏感去重，并在 XML 属性位置转义。保留空表；全删后输出有效的默认空 sheet1。
- 单元格保留文本类型和前导零，不自动把公式样式文本变成公式；修复多余尾空格，正确保留空白、CR/LF/TAB。拒绝无效 UTF-8、非有限数及不支持的复杂值；移除 XML 禁止控制字符。
- 整批单元格先验证后追加，错误不会留下半行；限制每表 65536 行/256 列及每格 32767 个 UTF-16 单元，超限抛异常。
- 最大列数在追加时记录，输出不用再扫描全部单元格。XML 时间戳使用真正 UTC。
- 新增 `writeTo($stream, $charset='UTF-8')`，按头、表、行分块写入，支持部分写入并报告失败，不关闭调用方的流。工作簿行数据仍在内存中，不承诺无限数据量。
- `getContent()` 和 `make()` 默认 UTF-8，编码声明与内容一致。显式 GBK 等 ASCII 兼容编码将非 ASCII 内容写成 Unicode 数值实体，避免中文、Emoji 丢失，不依赖猜编码。
- 下载先完成临时流再发头，普通输出时按实际字节设置 Content-Length；gzip 或自定义输出转换启用时不预设未转换长度；清理路径/控制字符，使用 ASCII 文件名回退及 RFC 5987 filename*，拒绝头已发送或缓冲已有内容的损坏下载。

```php
$xls = new myExcel('统计', '数据');
$xls->addCells(['编号', '文本']); // 自动建首行
$xls->addRow();
$xls->addCells(['0012', '中文😀']);
$xls->addCells('追加到首行', 0);

$stream = fopen('/私有目录/export.xml', 'wb');
try { $bytes = $xls->writeTo($stream); }
finally { fclose($stream); }
// 浏览器入口使用 $xls->make();，会输出并 exit
```

兼容变化：无效表名、超限内容、无效行号和无效 UTF-8 现在明确报错；构造不再接受任意非字符串名字。默认下载改为 UTF-8，显式 `make('GBK')` 仍支持。旧版输出每格额外附带的尾空格被移除，读取方不应依赖该错误。

## 验证

```sh
php tests/extensions/extension_regression.php
python3 tests/extensions/extension_http_fixture.py
```

专项使用临时文件、独立 PHP 子进程和回环 HTTP 服务，不触碰业务记录或对外投递。覆盖真实序列化/var_export、回调类型、全局错误标志和随机状态、JSON/XML 失败、命名及编码、下载响应头，并在独立 16 MiB PHP 进程中写出大于 10 MB 的 10000 行工作簿，用 XMLReader 逐行验证。

结构检查使用 DOM/XMLReader，以及独立 HTTP 客户端的 XML 解析；不据此宣称已在所有 Excel 客户端做人工打开验证。原有核心、数据库/缓存、功能类、安全与 HTTPS 回归继续运行，结果见测试记录。

## 参考

- [PHP 闭包与绑定](https://www.php.net/manual/en/closure.bindto.php)、[魔术方法及对象状态](https://www.php.net/manual/en/language.oop5.magic.php)：实现按本机 PHP 7.4 验证，不照搬新版 Reflection API。
- [Microsoft 工作表名称约束](https://support.microsoft.com/zh-cn/excel/rename-a-worksheet)：长度、非法字符及首尾单引号。
- [Microsoft 旧格式容量说明](https://learn.microsoft.com/en-us/office/vba/excel/concepts/excel-performance/excel-tips-for-optimizing-performance-obstructions)：旧格式为 65536 行、256 列。
