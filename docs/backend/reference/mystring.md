# myString：字符串与字符集

修订日期：2026-10-23。功能详解。

## 职责与调用位置

字符集转换、数据编码和HTML转义分别承担不同职责；已知编码时显式给定来源，不反复猜测。

源码：`lib/myString.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$gb = iconv('UTF-8', 'GB18030', '中文');
$utf8 = myString::convertCharset($gb, 'GB18030', 'UTF-8');
assert($utf8 === '中文');
$json = myString::toJson(['title' => $utf8]);
assert(myString::fromJson($json)['title'] === '中文');
assert(myString::fromHex(myString::toHex('A&B')) === 'A&B');
```

## 声明与类职责

### myString

功能说明：提供HEX/INI/JSON/XML/PHP脚本转换、HTML处理、字符集和文本操作。

使用说明：
  1. 编码转换明确来源/目标，严格或容错策略按方法参数选择。
  2. 数组/对象转换、BOM和文件原子转换见相关入口；不可表示字符按所选策略处理。
  3. 字符串转义与HTML转义各有用途，不能替代SQL值绑定或完整HTML过滤。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $func_alias = array(
            'c' => 'charset',
            'sc' => 'setCharset',
            'hex' => 'toHex',
            's16' => 'fromHex',
            'json' => 'toJson',
            'obj' => 'fromJson',
            'str' => 'fromAny',
            'xml' => 'toXML',
            'html' => 'htmlTrans',
            'rnd' => 'rndKey',
            'txt' => 'txt2html',
        );
```

```text
protected
        $str = '',
        $charset = '',
        $func_str = array('myString', 'get');
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myString::__construct` | 初始化字符串及已知/推测编码，显式来源不会被后续 get() 再猜测覆盖 |
| `myString::__toString` | 作为字符串显示时的操作 |
| `myString::set` | 设置默认字符串及来源，验证通过后才替换原值 |
| `myString::get` | 读取默认字符串，指定目标时严格转换并记住明确编码 |
| `myString::normalizeCharset` | 规范字符集名称并校验后端支持，拒绝非字符编码及隐含丢弃选项 |
| `myString::charsetBom` | 识别 Unicode BOM，UTF-32 先于共享前缀的 UTF-16 检查 |
| `myString::iconvCharset` | 将规范名称映射到 iconv 后端使用的别名 |
| `myString::validCharset` | 严格检查字节串能否按给定编码解释，不把“合法字节”当作来源证明 |
| `myString::charset` | 在候选集内按明确顺序推测编码，BOM 优先；不声称能自动识别真实来源 |
| `myString::charsetOptions` | 校验转换选项，避免拼错策略后悄悄退回有损默认值 |
| `myString::convertCharset` | 在两个明确编码之间转换原始字节，严格模式验证往返，不静默丢失数据 |
| `myString::charsetTree` | 递归复制并转换值，拒绝循环及过深结构；JSON 模式只取可序列化的数据形状 |
| `myString::setCharset` | 兼容批量转码入口，支持明确来源，复制对象而不原地修改 |
| `myString::toHex` | 将字符串转换为16进制 |
| `myString::fromHex` | 将16进制转换为字符串 |
| `myString::fromIni` | 以相同的原始扫描规则解析INI文件或INI文本 |
| `myString::toIni` | 递归把配置数组转换为带分组的INI文本 |
| `myString::toJson` | 编码为 UTF-8 JSON，复制转换值及非 UTF-8 键，不修改输入对象 |
| `myString::fromJson` | 解析JSON文本，按assoc选择关联数组或对象 |
| `myString::fromAny` | 将任意值转换为文本，复杂类型使用PHP序列化 |
| `myString::toXML` | 将数组转换为XML |
| `myString::toScript` | 转换变量为php变量脚本 |
| `myString::fileCharset` | 在边界内完整转换本地文本文件，以同目录临时文件原子替换，失败保留旧文件 |
| `myString::htmlTrans` | 将字符串转换为超文本格式 |
| `myString::txt2html` | 将纯文本转换为超文本格式 |
| `myString::rndKey` | 生成随机字符串或可在目标编码表示的随机汉字 |
| `myString::addSlash` | 批量添加转移斜杠 |
| `myString::stripSlash` | 批量去除转移斜杠 |
| `myString::breakStr` | 将字符串打碎为单个的字符或汉字 |
| `myString::watermark` | 为超文本添加水印 |
| `myString::substr` | 截取任意字符串，可为任意字符集的中文 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myString::__construct

功能说明：初始化字符串及已知/推测编码，显式来源不会被后续 get() 再猜测覆盖

```text
public function __construct($str='', $from_charset = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string\|scalar\|null\|object；初值，允许可转字符串标量和 __toString 对象（默认：''） |
| `$from_charset` | string\|null；可选明确来源编码；省略时在候选集中推测（默认：null） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:104`。

### myString::__toString

功能说明：作为字符串显示时的操作

参数说明：无显式参数。

```text
public function __toString();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前字符串按get约定取得的文本

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:113`。

### myString::set

功能说明：设置默认字符串及来源，验证通过后才替换原值

```text
public function set($val, $from_charset = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$val` | string\|scalar\|null\|object；初值，不接受数组、资源及不可转字符串对象 |
| `$from_charset` | string\|null；明确来源；null 推测，不能确定时保存未知状态（默认：null） |

**返回值**：void 不立即改变编码，不移除原始 BOM

**异常与失败**：InvalidArgumentException 类型或编码非法；UnexpectedValueException 显式来源与字节格式不符

调用形式：

```php
// $instance为已初始化的myString实例。
$result = $instance->set($val, $from_charset);
```

定位：`lib/myString.class.php:126`。

### myString::get

功能说明：读取默认字符串，指定目标时严格转换并记住明确编码

```text
public function get($charset='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；空字符串返回原始字节；非空指定目标编码（默认：''） |

**返回值**：string 成功转换后更新实例状态；失败保留旧字符串及来源

**异常与失败**：Throwable 来源未知、不能无损转换或目标非法时明确报错

调用形式：

```php
// $instance为已初始化的myString实例。
$result = $instance->get($charset);
```

定位：`lib/myString.class.php:141`。

### myString::normalizeCharset

功能说明：规范字符集名称并校验后端支持，拒绝非字符编码及隐含丢弃选项

```text
public static function normalizeCharset($charset);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；名称或常见别名，例如 utf8、CP936、latin1、Shift-JIS |

**返回值**：string 规范名称，例如 UTF-8、GBK、ISO-8859-1；不接受 //IGNORE 等后缀

**异常与失败**：InvalidArgumentException 名称非法、不是字符编码或当前后端不支持；RuntimeException 没有可用的 mbstring/iconv 转换后端

调用形式：

```php
$result = myString::normalizeCharset($charset);
```

定位：`lib/myString.class.php:163`。

### myString::charsetBom

功能说明：识别 Unicode BOM，UTF-32 先于共享前缀的 UTF-16 检查

```text
protected static function charsetBom($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；原始字节串 |

**返回值**：array|null [明确字节序编码, BOM字节数]，没有已知 BOM 为 null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:189`。

### myString::iconvCharset

功能说明：将规范名称映射到 iconv 后端使用的别名

```text
protected static function iconvCharset($encoding);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$encoding` | string；已规范名称 |

**返回值**：string iconv 标签；例如 mbstring 的 SJIS-WIN 对应 CP932

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:200`。

### myString::validCharset

功能说明：严格检查字节串能否按给定编码解释，不把“合法字节”当作来源证明

```text
protected static function validCharset($text, $encoding);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；原始字节串 |
| `$encoding` | string；已规范的字符集 |

**返回值**：bool 格式合法为 true；UTF-8/ASCII 使用快速检查，其他优先 mbstring

**异常与失败**：RuntimeException 非UTF-8/ASCII校验缺少mbstring及iconv后端

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:210`。

### myString::charset

功能说明：在候选集内按明确顺序推测编码，BOM 优先；不声称能自动识别真实来源

```text
public static function charset($str, $encodings = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待检查字节串；空串及普通 ASCII 默认按 UTF-8 兼容 |
| `$encodings` | array\|string\|null；候选列表或逗号串；默认 UTF-8、GBK、BIG5、GB18030（默认：null） |

**返回值**：string|false 首个严格合法候选，BOM 编码只有符合候选时使用；均不符为 false

**异常与失败**：InvalidArgumentException 输入/候选非法；单字节西文编码须由调用方明确加入

调用形式：

```php
$result = myString::charset($str, $encodings);
```

定位：`lib/myString.class.php:226`。

### myString::charsetOptions

功能说明：校验转换选项，避免拼错策略后悄悄退回有损默认值

```text
protected static function charsetOptions(array $options);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$options` | array；on_error=throw/replace/ignore，bom=remove/preserve/add，backend=auto/mbstring/iconv，<br>                      convert_keys 默认 false，max_depth 默认 64；文件另支持 max_bytes 默认 32 MiB |

**返回值**：array 补齐默认值的选项；严格策略默认拒绝损失，容错必须显式选择

**异常与失败**：InvalidArgumentException 未知选项、枚举值或边界类型非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:251`。

### myString::convertCharset

功能说明：在两个明确编码之间转换原始字节，严格模式验证往返，不静默丢失数据

```text
public static function convertCharset($text, $from = null, $to = 'UTF-8', array $options = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；原始字符串，不能直接用于任意压缩/图片等二进制文件 |
| `$from` | string\|null；来源编码；null/auto 使用 charset() 推测，已知来源应显式指定（默认：null） |
| `$to` | string；目标编码，默认 UTF-8；UTF-16/32 泛称统一按 BE 并输出 BOM（默认：'UTF-8'） |
| `$options` | array；on_error 默认 throw；replace/ignore 明确允许替换/丢弃；BOM 默认 remove；<br>                      preserve 保留有 BOM 的 Unicode 标记，add 强制添加；后端及边界见 charsetOptions()（默认：[]） |

**返回值**：string 转换字节；显式同编码仍校验输入，不用猜测覆盖已知编码

**异常与失败**：InvalidArgumentException 编码/选项非法、来源与 BOM 冲突或目标不支持所需 BOM；UnexpectedValueException 来源未知、字节非法或严格转换不能往返；RuntimeException 后端不可用、不能执行选定容错策略或转换失败

调用形式：

```php
$result = myString::convertCharset($text, $from, $to, $options);
```

定位：`lib/myString.class.php:273`。

### myString::charsetTree

功能说明：递归复制并转换值，拒绝循环及过深结构；JSON 模式只取可序列化的数据形状

```text
protected static function charsetTree($value, $to, $from, array $options, SplObjectStorage $copies, SplObjectStorage $active, $depth = 0, $json = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；输入数据 |
| `$to` | string；已规范目标编码 |
| `$from` | string\|null；明确来源或 null 自动推测 |
| `$options` | array；已补齐策略 |
| `$copies` | SplObjectStorage；已复制对象到结果的映射，共享引用仅转换一次 |
| `$active` | SplObjectStorage；当前递归栈，用于识别对象循环 |
| `$depth` | int；当前深度，根为 0，数组自引用同样受上限保护（默认：0） |
| `$json` | object\|false；JSON 内部错误上下文；false 为普通复制转换（默认：false） |

**返回值**：mixed 独立转换结果；非字符串标量和资源保持类型，资源仍不能 JSON 编码

**异常与失败**：RuntimeException|JsonException 循环或深度超限，JSON 模式带对应错误码

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myString.class.php:334`。

### myString::setCharset

功能说明：兼容批量转码入口，支持明确来源，复制对象而不原地修改

```text
public static function setCharset($str, $charset='UTF-8', $from_charset = null, array $options = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | mixed；字符串、数组、对象；非字符串标量保持原类型，仅转换对象公开状态 |
| `$charset` | string；目标编码，默认 UTF-8（默认：'UTF-8'） |
| `$from_charset` | string\|null；来源编码，默认自动；已知来源必须明确传入以消除歧义（默认：null） |
| `$options` | array；严格转换/BOM/容错/键转换及最大深度，详见 charsetOptions()（默认：[]） |

**返回值**：mixed 独立转换结果；数组键默认不转换，显式 convert_keys 才转换并检测冲突

**异常与失败**：Throwable 来源未知、损失、对象循环/不可复制或边界非法时明确报错

调用形式：

```php
$result = myString::setCharset($str, $charset, $from_charset, $options);
```

定位：`lib/myString.class.php:400`。

### myString::toHex

功能说明：将字符串转换为16进制

```text
public static function toHex($str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |

**返回值**：string 小写十六进制编码文本，每个输入字节对应两个字符

调用形式：

```php
$result = myString::toHex($str);
```

定位：`lib/myString.class.php:413`。

### myString::fromHex

功能说明：将16进制转换为字符串

```text
public static function fromHex($hex);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$hex` | string；十六进制编码字符串 |

**返回值**：string|false 解码后的原始字节；非字符串、奇数长度或非HEX字符在解码前返回false，空串合法

调用形式：

```php
$result = myString::fromHex($hex);
```

定位：`lib/myString.class.php:423`。

### myString::fromIni

功能说明：以相同的原始扫描规则解析INI文件或INI文本

```text
public static function fromIni($ini, $mode = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$ini` | string；INI文件路径或直接INI文本 |
| `$mode` | bool；是否保留INI节分组；true生成分组数组，false合并为平面数组（默认：true） |

**返回值**：array|false 按mode组织的配置数组；解析失败为false

调用形式：

```php
$result = myString::fromIni($ini, $mode);
```

定位：`lib/myString.class.php:435`。

### myString::toIni

功能说明：递归把配置数组转换为带分组的INI文本

```text
public static function toIni($array, $level = 1);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$array` | array；需要编码或转换的数组 |
| `$level` | int；当前递归层级，由顶层调用使用默认值（默认：1） |

**返回值**：string 完整INI文本

调用形式：

```php
$result = myString::toIni($array, $level);
```

定位：`lib/myString.class.php:452`。

### myString::toJson

功能说明：编码为 UTF-8 JSON，复制转换值及非 UTF-8 键，不修改输入对象

JSON 模式尊重 JsonSerializable，ArrayObject 取存储内容；共享对象只转换一次。
原生 JSON 不支持的类型、递归及深度错误继续返回 false 并保留 json_last_error()；
转码本身按严格策略明确报错，不把损坏数据猜成单字节编码后继续保存。

```text
public static function toJson($var, $charset='UTF-8', $from_charset = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$var` | mixed；待编码数据 |
| `$charset` | string；兼容旧参数，非空时统一转 UTF-8；空字符串完全跳过预处理（默认：'UTF-8'） |
| `$from_charset` | string\|null；新增明确源编码；null 自动推测字符串值，键单独推测（默认：null） |

**返回值**：string|false JSON 文本或原生编码错误 false

**异常与失败**：Throwable 来源/字节/转码非法或业务 jsonSerialize() 抛出的异常继续传递

调用形式：

```php
$result = myString::toJson($var, $charset, $from_charset);
```

定位：`lib/myString.class.php:485`。

### myString::fromJson

功能说明：解析JSON文本，按assoc选择关联数组或对象

```text
public static function fromJson($json, $assoc=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$json` | string；JSON格式内容 |
| `$assoc` | bool；true将JSON对象转换为关联数组（默认：true） |

**返回值**：mixed JSON对象/数组/标量；assoc控制对象形态，合法null与失败均可返回null，应检查json_last_error

调用形式：

```php
$result = myString::fromJson($json, $assoc);
```

定位：`lib/myString.class.php:508`。

### myString::fromAny

功能说明：将任意值转换为文本，复杂类型使用PHP序列化

```text
public static function fromAny($var);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$var` | mixed；字符串原样返回，标量转文本，数组及其他值序列化 |

**返回值**：string 标量/可字符串化对象的文本，其他值使用PHP序列化表示

调用形式：

```php
$result = myString::fromAny($var);
```

定位：`lib/myString.class.php:518`。

### myString::toXML

功能说明：将数组转换为XML

```text
public static function toXML($var, $item_tag='item', $main_tag='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$var` | mixed；需要编码为XML的数据，数组/对象按当前转换规则展开 |
| `$item_tag` | string；XML数组成员使用的元素名（默认：'item'） |
| `$main_tag` | string；XML根元素名称（默认：''） |

**返回值**：string XML文本；根元素/数组成员名按main_tag/item_tag规则生成

**异常与失败**：InvalidArgumentException item_tag或main_tag不是支持的XML元素名称

调用形式：

```php
$result = myString::toXML($var, $item_tag, $main_tag);
```

定位：`lib/myString.class.php:547`。

### myString::toScript

功能说明：转换变量为php变量脚本

```text
public static function toScript($var, $name='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$var` | mixed；通过var_export输出为PHP变量脚本的值 |
| `$name` | string；生成PHP赋值脚本所使用的变量名（默认：''） |

**返回值**：string 可供受信程序使用的PHP变量表示/赋值脚本文本，不自动执行

**异常与失败**：InvalidArgumentException 目标变量名或展开的键不是合法PHP变量名

调用形式：

```php
$result = myString::toScript($var, $name);
```

定位：`lib/myString.class.php:571`。

### myString::fileCharset

功能说明：在边界内完整转换本地文本文件，以同目录临时文件原子替换，失败保留旧文件

```text
public static function fileCharset($file_src, $file_dst='', $charset='UTF-8', $from_charset = null, array $options = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file_src` | string；源文件，不接受流包装器或符号链接；不存在时返回 false |
| `$file_dst` | string；目标文件，空字符串覆盖源；父目录须存在，拒绝符号链接（默认：''） |
| `$charset` | string；目标编码，默认 UTF-8（默认：'UTF-8'） |
| `$from_charset` | string\|null；明确来源，null 自动推测（默认：null） |
| `$options` | array；转码策略；max_bytes 默认 32 MiB，另检查当前 PHP 剩余内存（默认：[]） |

**返回值**：int|false 写入字节数（空文件为 0）；源不存在为 false

**异常与失败**：LengthException 文件超过字节预算；RuntimeException 内存不足、读取/锁定/发布失败或读写期间版本变化；Throwable 转码失败等异常向外传递，目标不写入部分结果

调用形式：

```php
$result = myString::fileCharset($file_src, $file_dst, $charset, $from_charset, $options);
```

定位：`lib/myString.class.php:598`。

### myString::htmlTrans

功能说明：将字符串转换为超文本格式

```text
public static function htmlTrans($str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | mixed；字符串或嵌套数组；转换引号/尖括号/空白，其他类型原样保留 |

**返回值**：mixed 递归转换后的值，保持数组键和其他类型

调用形式：

```php
$result = myString::htmlTrans($str);
```

定位：`lib/myString.class.php:644`。

### myString::txt2html

功能说明：将纯文本转换为超文本格式

```text
public static function txt2html($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；待处理或输出的内容字节串 |

**返回值**：string 经过文本替换规则转换的HTML片段，不是完整HTML过滤器

调用形式：

```php
$result = myString::txt2html($content);
```

定位：`lib/myString.class.php:663`。

### myString::rndKey

功能说明：生成随机字符串或可在目标编码表示的随机汉字

```text
public static function rndKey($length, $scope=1, $charset='UTF-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$length` | int；生成字符数量，沿用原有循环约定 |
| `$scope` | int；1 数字，2 加小写，3 加大写，4 加特殊符；其他值生成汉字（默认：1） |
| `$charset` | string；汉字的目标编码，默认 UTF-8；英文/数字分支不做字符转码（默认：'UTF-8'） |

**返回值**：string 汉字候选从明确 GBK 字节生成，过滤未定义/不可表示候选，最后整体转码

**异常与失败**：RuntimeException 尝试 128 次仍无法为某字符找到可表示的汉字；Throwable 目标非法或后端不可用等异常继续传递；不静默输出替代字符

调用形式：

```php
$result = myString::rndKey($length, $scope, $charset);
```

定位：`lib/myString.class.php:681`。

### myString::addSlash

功能说明：批量添加转移斜杠

```text
public static function addSlash(&$str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$str` | mixed；引用传入的字符串或嵌套数组，递归原地处理；其他类型原样保留 |

**返回值**：mixed 转换后的原值，同时更新引用参数

调用形式：

```php
$result = myString::addSlash($str);
```

定位：`lib/myString.class.php:719`。

### myString::stripSlash

功能说明：批量去除转移斜杠

```text
public static function stripSlash(&$str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$str` | mixed；引用传入的字符串或嵌套数组，递归原地处理；其他类型原样保留 |

**返回值**：mixed 转换后的原值，同时更新引用参数

调用形式：

```php
$result = myString::stripSlash($str);
```

定位：`lib/myString.class.php:736`。

### myString::breakStr

功能说明：将字符串打碎为单个的字符或汉字

```text
public static function breakStr($str);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |

**返回值**：array 逐字符字节串列表；优先按UTF-8，否则按历史双字节规则拆分

调用形式：

```php
$result = myString::breakStr($str);
```

定位：`lib/myString.class.php:753`。

### myString::watermark

功能说明：为超文本添加水印

需要为class_name配置隐藏样式；本方法是展示水印/干扰文本，不承担内容访问控制。

```text
public static function watermark($html, $rate=2, $scope=4, $str_append='', $charset='utf-8', $class_name='watermark', $tag_name='span', $jam_tag=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$html` | string；待处理的HTML内容 |
| `$rate` | int；出现几率（默认：2） |
| `$scope` | int；水印内容对应 rndkey（默认：4） |
| `$str_append` | string；自定义水印内容（默认：''） |
| `$charset` | string；生成水印随机字串所使用的字符编码，默认utf-8（默认：'utf-8'） |
| `$class_name` | string；水印节点使用的CSS类名（默认：'watermark'） |
| `$tag_name` | string；包裹水印字符使用的HTML标签名（默认：'span'） |
| `$jam_tag` | bool；干扰标签（默认：false） |

**返回值**：string 插入随机隐藏水印后的HTML；输入超过50000字节时原样返回

调用形式：

```php
$result = myString::watermark($html, $rate, $scope, $str_append, $charset, $class_name, $tag_name, $jam_tag);
```

定位：`lib/myString.class.php:774`。

### myString::substr

功能说明：截取任意字符串，可为任意字符集的中文

```text
public static function substr($string, $start, $length = 0, $mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$string` | string；待截取的文本 |
| `$start` | int；起始位置，计数单位由截取模式决定 |
| `$length` | int；截取长度；0延伸到末尾，负值从可用长度中扣除（默认：0） |
| `$mode` | bool；true按字符计数；false按字节范围截取并保留完整字符（默认：false） |

**返回值**：string 落在指定字符/字节范围内的完整字符文本；范围外或长度不足返回空串

调用形式：

```php
$result = myString::substr($string, $start, $length, $mode);
```

定位：`lib/myString.class.php:817`。

