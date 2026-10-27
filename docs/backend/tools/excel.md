# 表格导出

## 本篇目标与调用场景

是Excel可读取的XML，不是XLSX。getContent返回文本，writeTo写流，make产生下载并结束请求。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myExcel::init

功能说明：初始化工作簿，清空旧数据并创建首张工作表

```text
public function init($file_name='', $sheet_name='');
```

| 参数说明 |
| --- |
| string $file_name 下载文件名，空字符串使用 export；响应时另做路径/头清理（默认：''） |
| string $sheet_name 首张表名，空字符串使用 sheet1；字符串 0 是有效名称（默认：''） |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 文件名或表名类型、编码及格式非法

### myExcel::addSheet

功能说明：添加工作表，未指定名称时自动选取未占用的 sheetN

```text
public function addSheet($sheet_name='', $change_sheet=true);
```

| 参数说明 |
| --- |
| string $sheet_name 新表名，空字符串自动命名；大小写重复时不新增、不切表（默认：''） |
| bool $change_sheet 新增成功后是否切换当前表，默认 true（默认：true） |

**返回**：string 已存在或新建的表名；可供后续 chgSheet() 使用

**异常**：InvalidArgumentException 表名非法

### myExcel::delSheet

功能说明：删除工作表，仅当前表被删除时切换到剩余首表

```text
public function delSheet($sheet_name);
```

| 参数说明 |
| --- |
| string $sheet_name 表名，大小写不敏感 |

**返回**：bool 原表存在并删除为 true；删完后当前表为空，输出仍生成一个空 sheet1

### myExcel::resetSheet

功能说明：清空指定工作表的行及列数记录

```text
public function resetSheet($sheet_name, $change_sheet=true);
```

| 参数说明 |
| --- |
| string $sheet_name 已存在表名，大小写不敏感 |
| bool $change_sheet true 同时切换到该表（默认：true） |

**返回**：bool 表存在并已清空为 true，不存在为 false

### myExcel::chgSheet

功能说明：切换当前工作表，不自动创建不存在的表

```text
public function chgSheet($sheet_name);
```

| 参数说明 |
| --- |
| string $sheet_name 已存在表名，大小写不敏感 |

**返回**：bool 切换成功为 true，不存在为 false

### myExcel::addRow

功能说明：在当前表追加空行，无当前表时自动创建 sheet1

参数说明：无显式参数。

```text
public function addRow();
```

**返回**：int 新行的零起始索引

**异常**：LengthException 超过 XML Spreadsheet 2003 的 65536 行限制

## 明确调用示例

```php
$excel = new myExcel('report', '统计');
$excel->addCells(['编号', '名称']);
$excel->addCells(['001', '中文']);
$xml = $excel->getContent('UTF-8');
assert(strpos($xml, '中文') !== false);
// Web下载分支才使用$excel->make()；不要在其后拼接页面。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
