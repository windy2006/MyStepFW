# 表格工具

接口说明：`myExcel`。

## 职责与调用位置

是Excel可读取的XML，不是XLSX。getContent返回文本，writeTo写流，make产生下载并结束请求。

源码：`lib/myExcel.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$excel = new myExcel('report', '统计');
$excel->addCells(['编号', '名称']);
$excel->addCells(['001', '中文']);
$xml = $excel->getContent('UTF-8');
assert(strpos($xml, '中文') !== false);
// Web下载分支才使用$excel->make()；不要在其后拼接页面。
```

## 声明与类职责

### myExcel

功能说明：生成基于XML的工作簿、工作表、样式、行列及单元格，并支持输出/流式写入。

使用说明：
  1. 生成的是SpreadsheetML XML，不是XLSX ZIP格式；扩展名和响应头按使用场景选择。
  2. 按工作表、行、单元格顺序添加内容，使用getContent/writeTo/make输出。
  3. 大量数据可使用流式方式，避免一次性在内存组装完整工作簿。

补充说明：
XML Spreadsheet 2003 工作簿生成器，保留旧 .xls 下载入口（不是二进制 XLS/XLSX）

    $xls = new myExcel('export', 'sheet1');
    $xls->addRow(); $xls->addCells(['标题', '0012']);
    $xls->addCells('补充', 0);          // 显式第 0 行不再被当作缺省
    $xls->writeTo($stream);            // 分块写入调用方提供的流，不关闭它
    $xml = $xls->getContent();          // 取完整文本，默认 UTF-8
    $xls->make();                      // 下载并结束请求，文件名保留 .xls 兼容

单元格保留文本类型，不把以 =/+/-/@ 开头的文本自动转换为公式；不添加尾空格。
工作表名称按 Excel 规则验证、大小写不敏感地去重。输入文本要求有效 UTF-8，移除
XML 1.0 禁用控制字符；行/列上限 65536/256，单元格上限 32767 个 UTF-16 单元。
writeTo() 避免同时建立完整输出副本，但工作簿行数据仍在内存中，不是无限量导出接口。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $workSheets = [], $cur_sheet = '', $file = '';
```

```text
protected $sheet_columns = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myExcel::init` | 初始化工作簿，清空旧数据并创建首张工作表 |
| `myExcel::validateSheet` | 验证 Excel 表名，不截断或静默替换开发者指定的名称 |
| `myExcel::findSheet` | 按大小写不敏感的 Unicode 比较查找工作表 |
| `myExcel::addSheet` | 添加工作表，未指定名称时自动选取未占用的 sheetN |
| `myExcel::delSheet` | 删除工作表，仅当前表被删除时切换到剩余首表 |
| `myExcel::resetSheet` | 清空指定工作表的行及列数记录 |
| `myExcel::chgSheet` | 切换当前工作表，不自动创建不存在的表 |
| `myExcel::addRow` | 在当前表追加空行，无当前表时自动创建 sheet1 |
| `myExcel::cellText` | 转为有效 XML 文本，保留文本内容及前导零，拒绝无效 UTF-8 |
| `myExcel::addCells` | 向指定行末尾追加单元格，整批验证成功后才更新行内容 |
| `myExcel::outputCharset` | 规范导出字符集，限定为无需 BOM 的 ASCII 兼容 XML 编码 |
| `myExcel::xmlText` | 转义 XML 文本/属性并按旧编码需要生成 Unicode 数值实体 |
| `myExcel::contentChunks` | 按头部、工作表及行生成 XML 块，避免循环拼接完整文档 |
| `myExcel::writeTo` | 将 XML 分块写入可写流，处理部分写入，不关闭调用方流 |
| `myExcel::getContent` | 获取完整 XML 字符串，与 writeTo() 共用编码及生成逻辑 |
| `myExcel::make` | 生成下载响应并结束请求，先完成临时流后才发送头部 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myExcel::init

功能说明：初始化工作簿，清空旧数据并创建首张工作表

```text
public function init($file_name='', $sheet_name='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file_name` | string；下载文件名，空字符串使用 export；响应时另做路径/头清理（默认：''） |
| `$sheet_name` | string；首张表名，空字符串使用 sheet1；字符串 0 是有效名称（默认：''） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 文件名或表名类型、编码及格式非法

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->init($file_name, $sheet_name);
```

定位：`lib/myExcel.class.php:75`。

### myExcel::validateSheet

功能说明：验证 Excel 表名，不截断或静默替换开发者指定的名称

```text
protected function validateSheet($name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；有效 UTF-8 名称，1 到 31 个 Unicode 字符 |

**返回值**：void 名称不得含控制符、[]:*?/反斜杠或以单引号开头/结尾

**异常与失败**：InvalidArgumentException 名称不符合约束

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:92`。

### myExcel::findSheet

功能说明：按大小写不敏感的 Unicode 比较查找工作表

```text
protected function findSheet($name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；表名 |

**返回值**：string|int|false 原始存储键，不存在为 false；数字名称可能为整数键

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:102`。

### myExcel::addSheet

功能说明：添加工作表，未指定名称时自动选取未占用的 sheetN

```text
public function addSheet($sheet_name='', $change_sheet=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sheet_name` | string；新表名，空字符串自动命名；大小写重复时不新增、不切表（默认：''） |
| `$change_sheet` | bool；新增成功后是否切换当前表，默认 true（默认：true） |

**返回值**：string 已存在或新建的表名；可供后续 chgSheet() 使用

**异常与失败**：InvalidArgumentException 表名非法

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->addSheet($sheet_name, $change_sheet);
```

定位：`lib/myExcel.class.php:117`。

### myExcel::delSheet

功能说明：删除工作表，仅当前表被删除时切换到剩余首表

```text
public function delSheet($sheet_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sheet_name` | string；表名，大小写不敏感 |

**返回值**：bool 原表存在并删除为 true；删完后当前表为空，输出仍生成一个空 sheet1

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->delSheet($sheet_name);
```

定位：`lib/myExcel.class.php:132`。

### myExcel::resetSheet

功能说明：清空指定工作表的行及列数记录

```text
public function resetSheet($sheet_name, $change_sheet=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sheet_name` | string；已存在表名，大小写不敏感 |
| `$change_sheet` | bool；true 同时切换到该表（默认：true） |

**返回值**：bool 表存在并已清空为 true，不存在为 false

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->resetSheet($sheet_name, $change_sheet);
```

定位：`lib/myExcel.class.php:146`。

### myExcel::chgSheet

功能说明：切换当前工作表，不自动创建不存在的表

```text
public function chgSheet($sheet_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sheet_name` | string；已存在表名，大小写不敏感 |

**返回值**：bool 切换成功为 true，不存在为 false

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->chgSheet($sheet_name);
```

定位：`lib/myExcel.class.php:159`。

### myExcel::addRow

功能说明：在当前表追加空行，无当前表时自动创建 sheet1

参数说明：无显式参数。

```text
public function addRow();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int 新行的零起始索引

**异常与失败**：LengthException 超过 XML Spreadsheet 2003 的 65536 行限制

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->addRow();
```

定位：`lib/myExcel.class.php:172`。

### myExcel::cellText

功能说明：转为有效 XML 文本，保留文本内容及前导零，拒绝无效 UTF-8

```text
protected function cellText($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | scalar\|null\|object；标量、null 或实现 __toString() 的对象 |

**返回值**：string null/false 为空字符串，true 为 1；禁用 XML 控制符移除

**异常与失败**：InvalidArgumentException 值类型、浮点数或编码非法；LengthException 单元格超过 32767 个 UTF-16 单元

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:187`。

### myExcel::addCells

功能说明：向指定行末尾追加单元格，整批验证成功后才更新行内容

```text
public function addCells($cells, $idx='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cells` | scalar\|null\|object\|array；单个单元格或一行值，数组按值顺序追加，不保留键 |
| `$idx` | int\|string\|null；零起始行号；空字符串/null 使用末行，无行时自动创建；0 有效（默认：''） |

**返回值**：int 本次写入的行号；显式不存在的行号不会回退覆盖末行

**异常与失败**：InvalidArgumentException 索引或单元格类型/编码非法；OutOfBoundsException 显式行号不存在；LengthException 超过 256 列或单元格长度上限

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->addCells($cells, $idx);
```

定位：`lib/myExcel.class.php:207`。

### myExcel::outputCharset

功能说明：规范导出字符集，限定为无需 BOM 的 ASCII 兼容 XML 编码

```text
protected function outputCharset($charset);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；UTF-8/UTF8、GBK、GB2312、GB18030、BIG5、ASCII/US-ASCII、ISO-8859-1、Windows-1252 |

**返回值**：string 规范编码标签；旧编码将非 ASCII 字符表示为数值实体，避免丢失 Unicode

**异常与失败**：InvalidArgumentException 不是支持的编码，不允许控制符及任意头部内容

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:232`。

### myExcel::xmlText

功能说明：转义 XML 文本/属性并按旧编码需要生成 Unicode 数值实体

```text
protected function xmlText($text, $charset);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；已验证的 UTF-8 文本 |
| `$charset` | string；outputCharset() 返回的编码标签 |

**返回值**：string 可直接放入 XML 的内容，CR/LF/TAB 用实体保留原值

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:248`。

### myExcel::contentChunks

功能说明：按头部、工作表及行生成 XML 块，避免循环拼接完整文档

```text
protected function contentChunks($charset);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；已规范的输出字符集 |

**返回值**：Generator 每次产生一个文本块；空工作簿生成默认空 sheet1

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myExcel.class.php:266`。

### myExcel::writeTo

功能说明：将 XML 分块写入可写流，处理部分写入，不关闭调用方流

```text
public function writeTo($stream, $charset='UTF-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$stream` | resource；已打开的可写流，从当前位置开始写，不回卷 |
| `$charset` | string；输出编码，默认 UTF-8；其他支持编码用数值实体保留 Unicode（默认：'UTF-8'） |

**返回值**：int 实际写入的字节数

**异常与失败**：InvalidArgumentException 流不是资源或字符集不支持；RuntimeException 写入失败或连续零字节写入

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->writeTo($stream, $charset);
```

定位：`lib/myExcel.class.php:296`。

### myExcel::getContent

功能说明：获取完整 XML 字符串，与 writeTo() 共用编码及生成逻辑

```text
public function getContent($charset='UTF-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；输出编码，默认 UTF-8，保留旧无参数调用（默认：'UTF-8'） |

**返回值**：string 完整 XML；大导出优先 writeTo()，避免同时保留完整结果副本

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->getContent($charset);
```

定位：`lib/myExcel.class.php:316`。

### myExcel::make

功能说明：生成下载响应并结束请求，先完成临时流后才发送头部

```text
public function make($charset='UTF-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；输出编码，默认 UTF-8；显式 GBK 等通过数值实体无损兼容（默认：'UTF-8'） |

**返回值**：void 输出 XML 内容并 exit；兼容 .xls 扩展名，不生成二进制 XLS/XLSX

**异常与失败**：RuntimeException 已发送头部、输出缓冲非空、无法创建/写入临时流；InvalidArgumentException 字符集不受支持

调用形式：

```php
// $instance为已初始化的myExcel实例。
$result = $instance->make($charset);
```

定位：`lib/myExcel.class.php:330`。
