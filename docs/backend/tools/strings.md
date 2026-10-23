# 字符集、JSON、XML和文本处理

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

字符集转换、数据编码和HTML转义分别承担不同职责；已知编码时显式给定来源，不反复猜测。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myString::set

功能说明：设置默认字符串及来源，验证通过后才替换原值

```text
public function set($val, $from_charset = null);
```

| 参数说明 |
| --- |
| string\|scalar\|null\|object $val 初值，不接受数组、资源及不可转字符串对象 |
| string\|null $from_charset 明确来源；null 推测，不能确定时保存未知状态（默认：null） |

**返回**：void 不立即改变编码，不移除原始 BOM

**异常**：InvalidArgumentException 类型或编码非法；UnexpectedValueException 显式来源与字节格式不符

### myString::get

功能说明：读取默认字符串，指定目标时严格转换并记住明确编码

```text
public function get($charset='');
```

| 参数说明 |
| --- |
| string $charset 空字符串返回原始字节；非空指定目标编码（默认：''） |

**返回**：string 成功转换后更新实例状态；失败保留旧字符串及来源

**异常**：Throwable 来源未知、不能无损转换或目标非法时明确报错

### myString::normalizeCharset

功能说明：规范字符集名称并校验后端支持，拒绝非字符编码及隐含丢弃选项

```text
public static function normalizeCharset($charset);
```

| 参数说明 |
| --- |
| string $charset 名称或常见别名，例如 utf8、CP936、latin1、Shift-JIS |

**返回**：string 规范名称，例如 UTF-8、GBK、ISO-8859-1；不接受 //IGNORE 等后缀

**异常**：InvalidArgumentException 名称非法、不是字符编码或当前后端不支持；RuntimeException 没有可用的 mbstring/iconv 转换后端

### myString::charset

功能说明：在候选集内按明确顺序推测编码，BOM 优先；不声称能自动识别真实来源

```text
public static function charset($str, $encodings = null);
```

| 参数说明 |
| --- |
| string $str 待检查字节串；空串及普通 ASCII 默认按 UTF-8 兼容 |
| array\|string\|null $encodings 候选列表或逗号串；默认 UTF-8、GBK、BIG5、GB18030（默认：null） |

**返回**：string|false 首个严格合法候选，BOM 编码只有符合候选时使用；均不符为 false

**异常**：InvalidArgumentException 输入/候选非法；单字节西文编码须由调用方明确加入

### myString::convertCharset

功能说明：在两个明确编码之间转换原始字节，严格模式验证往返，不静默丢失数据

```text
public static function convertCharset($text, $from = null, $to = 'UTF-8', array $options = []);
```

| 参数说明 |
| --- |
| string $text 原始字符串，不能直接用于任意压缩/图片等二进制文件 |
| string\|null $from 来源编码；null/auto 使用 charset() 推测，已知来源应显式指定（默认：null） |
| string $to 目标编码，默认 UTF-8；UTF-16/32 泛称统一按 BE 并输出 BOM（默认：'UTF-8'） |
| array $options on_error 默认 throw；replace/ignore 明确允许替换/丢弃；BOM 默认 remove；<br>                      preserve 保留有 BOM 的 Unicode 标记，add 强制添加；后端及边界见 charsetOptions()（默认：[]） |

**返回**：string 转换字节；显式同编码仍校验输入，不用猜测覆盖已知编码

**异常**：InvalidArgumentException 编码/选项非法、来源与 BOM 冲突或目标不支持所需 BOM；UnexpectedValueException 来源未知、字节非法或严格转换不能往返；RuntimeException 后端不可用、不能执行选定容错策略或转换失败

### myString::setCharset

功能说明：兼容批量转码入口，支持明确来源，复制对象而不原地修改

```text
public static function setCharset($str, $charset='UTF-8', $from_charset = null, array $options = []);
```

| 参数说明 |
| --- |
| mixed $str 字符串、数组、对象；非字符串标量保持原类型，仅转换对象公开状态 |
| string $charset 目标编码，默认 UTF-8（默认：'UTF-8'） |
| string\|null $from_charset 来源编码，默认自动；已知来源必须明确传入以消除歧义（默认：null） |
| array $options 严格转换/BOM/容错/键转换及最大深度，详见 charsetOptions()（默认：[]） |

**返回**：mixed 独立转换结果；数组键默认不转换，显式 convert_keys 才转换并检测冲突

**异常**：Throwable 来源未知、损失、对象循环/不可复制或边界非法时明确报错

## 明确调用示例

```php
$gb = iconv('UTF-8', 'GB18030', '中文');
$utf8 = myString::convertCharset($gb, 'GB18030', 'UTF-8');
assert($utf8 === '中文');
$json = myString::toJson(['title' => $utf8]);
assert(myString::fromJson($json)['title'] === '中文');
assert(myString::fromHex(myString::toHex('A&B')) === 'A&B');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
