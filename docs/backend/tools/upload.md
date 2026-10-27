# 真实上传、容量和结果检查

## 本篇目标与调用场景

真实上传需浏览器multipart请求；结果数组逐项报告成功或失败。允许后缀、容量、鉴权及附件登记分别处理。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myUploader::setLimits

功能说明：设置单文件上传大小及允许的扩展名

```text
public function setLimits($bytes = 33554432, $allowed = []);
```

| 参数说明 |
| --- |
| int $bytes 最大允许处理的字节数，必须为正整数（默认：33554432） |
| array\|string $allowed 允许上传的扩展名列表或逗号分隔文本（默认：[]） |

**返回**：$this 返回当前上传器，便于连续设置

**异常**：InvalidArgumentException 大小上限非法或扩展名列表类型不正确

### myUploader::init

功能说明：初始化上传目录、改名策略和禁止扩展名列表

```text
public function init($path = '', $rename = false, $banlst=[]);
```

| 参数说明 |
| --- |
| string $path 目标目录或路径（默认：''） |
| bool $rename 是否为上传文件生成新的唯一名称（默认：false） |
| array $banlst 附加禁止上传的扩展名列表（默认：[]） |

**返回**：void 无返回值。

### myUploader::setBan

功能说明：追加禁止扩展名，合并到内置隔离规则

```text
public function setBan($list);
```

| 参数说明 |
| --- |
| array\|string $list 追加禁止扩展名数组或逗号分隔文本 |

**返回**：void 无返回值。

### myUploader::do

功能说明：遍历单个或嵌套上传字段，逐项上传并收集结果

```text
public function do($getsize = true);
```

| 参数说明 |
| --- |
| bool $getsize 结果中的size是否格式化为可读容量（默认：true） |

**返回**：void 无返回值。

### myUploader::result

功能说明：取得结构化上传结果或HTML结果表

调用示例：
    $uploader->do();
    foreach ($uploader->result() as $item) {
        // 按error/message/new_name处理各项
    }

```text
public function result($html = false);
```

| 参数说明 |
| --- |
| bool $html true返回HTML结果表，false返回结构化上传结果数组（默认：false） |

**返回**：array|string html为false返回结果数组，为true返回HTML表格

## 明确调用示例

```php
// 已鉴权、通过CSRF的上传处理器中，$directory为本业务目标目录。
$uploader = new myUploader($directory, true);
$uploader->setLimits(8 * 1024 * 1024, ['jpg', 'png']);
$uploader->do();
$results = $uploader->result();
// 检查每一项结果后，再按业务需要登记附件；不要只检查数组非空。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
