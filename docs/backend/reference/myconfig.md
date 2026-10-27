# 设置管理

接口说明：`myConfig`。

## 职责与调用位置

构造时提供实际配置文件；value无副作用读取，merge按分组覆盖；保存前进行权限、字段及并发检查。

源码：`lib/myConfig.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// $file指向临时配置文件，内容为return ['web'=>['title'=>'原名']];
$config = new myConfig($file);
assert($config->value('web.title') === '原名');
$config->merge(['web' => ['title' => '新名']]);
$config->save('php');
assert((include $file)['web']['title'] === '新名');
```

## 声明与类职责

### myConfig

功能说明：解析PHP/JSON/INI配置，处理配置分组、结构表单、合并和并发保存冲突。

使用说明：
  1. 初始化时传配置文件路径，value只读访问，get(true)导出数组。
  2. set用于兼容设置表单，merge递归覆盖；save默认检查加载版本避免覆盖并发编辑。
  3. PHP配置会被执行，路径和配置内容必须来自受信来源。

补充说明：
配置信息加载、分组合并、设置表单及原子保存

    $config = new myConfig($file);                 // 加载并记录原文件内容版本
    $config->catalog->item = 'somevalue';          // 兼容链式读写，缺失分组自动创建
    $value = $config->value('db.port', 3306);       // 无副作用地读取点分隔路径
    $config->merge($overlay);                     // 递归覆盖分组
    $config->set($_POST['setting']);              // 依据结构处理设置表单
    $config->save();                              // 原子保存，拒绝覆盖并发修改
    $config->save('php', '', true);               // 显式允许覆盖冲突

使用说明：
1. PHP/JSON 保留 null、布尔、数字及文本类型；内部数组/对象统一为分组对象，
   get(true) 再递归导出数组。INI 沿用文本配置约定，不保证复杂类型完整往返。
2. value() 不创建节点；__get() 保留缺失节点自动创建行为。克隆会复制嵌套配置。
3. set() 面向旧设置表单，列表转为 CSV；不是任意 JSON 数据的无损批量赋值接口。
4. save() 保存原文件时检查加载版本，冲突抛异常；导出到不同文件不检查原版本。
   配置/结构 PHP 文件会被执行，只接受受信路径及结构定义。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $file = '',
        $construction = '',
        $type = '',
        $parser = [],
        $setting = array();
```

```text
protected $schema = [], $revision = null;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myConfig::init` | 绑定配置文件、读取内容版本，并寻找相邻的设置结构定义 |
| `myConfig::get` | 获取全部配置，可选择递归数组或当前内部对象 |
| `myConfig::__get` | 读取顶层配置，缺失时自动创建空分组以兼容链式赋值 |
| `myConfig::__set` | 直接设置顶层配置，不套用表单列表及密码转换 |
| `myConfig::__isset` | 检查顶层配置是否存在且值非 null |
| `myConfig::__unset` | 删除顶层配置项，仅修改内存，持久化需另行 save() |
| `myConfig::__clone` | 复制嵌套配置内容，避免克隆实例共享分组对象 |
| `myConfig::value` | 无副作用地读取点分隔配置路径，保留已存在的 null |
| `myConfig::__toString` | 将当前设置递归转换后编码为 JSON，便于展示及与脚本交互 |
| `myConfig::a2o` | 递归将数组及对象的可遍历属性复制到新的分组对象 |
| `myConfig::o2a` | 递归将分组对象及数组复制为数组，不猜测标量内容类型 |
| `myConfig::recover` | 兼容历史表单/INI 的简单类型恢复，不用于 PHP/JSON 类型推断 |
| `myConfig::setParser` | 登记自定义扩展名的加载及保存回调 |
| `myConfig::makeConstruction` | 编译并缓存表单结构元数据，只有生成内容改变时才原子写入 |
| `myConfig::load` | 按文件格式读取配置，并返回本次稳定读取的内容摘要 |
| `myConfig::merge` | 递归合并分组配置，同名标量覆盖，分组可替换原标量 |
| `myConfig::set` | 按表单规则批量更新设置，保留原值的明确类型及未提交分组 |
| `myConfig::formValue` | 依据已有字段的明确类型转换表单文本，不猜测文本字段类型 |
| `myConfig::save` | 编码完整配置并原子保存，默认防止原文件被并发更新覆盖 |
| `myConfig::atomicWrite` | 锁定目标旁的锁文件，再以同目录临时文件原子发布完整内容 |
| `myConfig::build` | 按可信结构定义生成设置表单项目及分组标题 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myConfig::init

功能说明：绑定配置文件、读取内容版本，并寻找相邻的设置结构定义

优先查找 config/default.php，再查找 construction/default.php；实际配置文件
不存在时以空分组初始化，首次保存仍参与版本冲突检查。

```text
public function init($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；配置路径，扩展名决定加载格式；内置 php/ini/json |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 不支持的格式；UnexpectedValueException 配置根节点或结构定义不是数组/对象；RuntimeException 读取期间文件持续变动、消失或结构保存失败

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->init($file);
```

定位：`lib/myConfig.class.php:106`。

### myConfig::get

功能说明：获取全部配置，可选择递归数组或当前内部对象

```text
public function get($arr = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$arr` | bool；true 返回递归转换的数组；false 返回内部配置对象引用（默认：true） |

**返回值**：array|stdClass 全部设置；对象模式下修改嵌套属性会影响当前实例

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->get($arr);
```

定位：`lib/myConfig.class.php:130`。

### myConfig::__get

功能说明：读取顶层配置，缺失时自动创建空分组以兼容链式赋值

```text
public function __get($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；顶层配置名；显式保存的 null 原样返回，不创建空对象 |

**返回值**：mixed 现有值或新建的 stdClass；只读且不希望创建节点时使用 value()

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:142`。

### myConfig::__set

功能说明：直接设置顶层配置，不套用表单列表及密码转换

```text
public function __set($idx, $value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；顶层配置名 |
| `$value` | mixed；新值；直接赋入对象时调用方自行管理其引用 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:154`。

### myConfig::__isset

功能说明：检查顶层配置是否存在且值非 null

```text
public function __isset($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；顶层配置名 |

**返回值**：bool 遵循 PHP isset 语义；区分缺失和 null 时使用 value() 或 get()

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:164`。

### myConfig::__unset

功能说明：删除顶层配置项，仅修改内存，持久化需另行 save()

```text
public function __unset($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；顶层配置名，不解释为点分隔路径 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:172`。

### myConfig::__clone

功能说明：复制嵌套配置内容，避免克隆实例共享分组对象

参数说明：无显式参数。

```text
public function __clone();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 文件路径、原内容版本及结构元数据仍沿用被克隆实例

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:181`。

### myConfig::value

功能说明：无副作用地读取点分隔配置路径，保留已存在的 null

调用示例：
    $port = $config->value('db.port', 3306); // 不创建缺失分组

```text
public function value($path, $default = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；例如 db.port；空字符串返回整个内部配置对象，点号作为分隔符 |
| `$default` | mixed；任一路径段不存在时的返回值，默认 null（默认：null） |

**返回值**：mixed 已存在的配置值或默认值；返回对象仍是内部对象，不是副本

**异常与失败**：InvalidArgumentException 路径不是字符串

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->value($path, $default);
```

定位：`lib/myConfig.class.php:195`。

### myConfig::__toString

功能说明：将当前设置递归转换后编码为 JSON，便于展示及与脚本交互

参数说明：无显式参数。

```text
public function __toString();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string myString::toJson() 生成的文本；文件保存使用 save() 的编码流程

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:214`。

### myConfig::a2o

功能说明：递归将数组及对象的可遍历属性复制到新的分组对象

```text
public static function a2o($arr);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$arr` | mixed；待转换的数组、对象或标量；对象只复制可遍历属性，不保留原类 |

**返回值**：mixed 容器转换为 stdClass；null、数字、布尔及文本按原类型返回

调用形式：

```php
$result = myConfig::a2o($arr);
```

定位：`lib/myConfig.class.php:224`。

### myConfig::o2a

功能说明：递归将分组对象及数组复制为数组，不猜测标量内容类型

```text
public static function o2a($obj);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$obj` | mixed；分组对象、数组或标量 |

**返回值**：mixed 容器转为数组，嵌套列表保留数字键；标量及 null 原样返回

调用形式：

```php
$result = myConfig::o2a($obj);
```

定位：`lib/myConfig.class.php:237`。

### myConfig::recover

功能说明：兼容历史表单/INI 的简单类型恢复，不用于 PHP/JSON 类型推断

```text
public static function recover($v);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$v` | mixed；数组转为逗号分隔文本；大小写不敏感的 true/false 文本转为布尔 |

**返回值**：mixed 转换结果；其他标量不变，不自动把数字文本转为数字

调用形式：

```php
$result = myConfig::recover($v);
```

定位：`lib/myConfig.class.php:250`。

### myConfig::setParser

功能说明：登记自定义扩展名的加载及保存回调

```text
public  function setParser($ext, $func_load, $func_save);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$ext` | string；扩展名，不含点号，建议使用小写以匹配 load()/save() 的格式名 |
| `$func_load` | callable；接收原文件文本，须返回数组或对象 |
| `$func_save` | callable；接收完整配置数组，须返回待写入字符串 |

**返回值**：void 名称为空或任一回调不可调用时不登记

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->setParser($ext, $func_load, $func_save);
```

定位：`lib/myConfig.class.php:270`。

### myConfig::makeConstruction

功能说明：编译并缓存表单结构元数据，只有生成内容改变时才原子写入

提取 checkbox/switch/password 等字段的点分隔路径及密码变换回调。
直接传入 construction.php 时加载既有元数据；定义文件与元数据须为可信 PHP。

```text
public function makeConstruction($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；设置结构定义路径，或可直接 include 返回数组的 construction.php |

**返回值**：void 更新实例 schema 及 construction；找不到可加载结构时保持当前状态

**异常与失败**：UnexpectedValueException 定义或元数据不是数组；RuntimeException 派生结构文件无法原子保存

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->makeConstruction($file);
```

定位：`lib/myConfig.class.php:290`。

### myConfig::load

功能说明：按文件格式读取配置，并返回本次稳定读取的内容摘要

读取前后比较文件摘要，最多重试三次；PHP 在独立作用域 include，JSON 严格解码，
INI 保留兼容类型恢复。首次发现文件不存在返回空配置，不自动保存或替换实例设置。

```text
public function load($file='', $return_object=false, &$snapshot = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；配置路径；空字符串使用实例绑定文件（默认：''） |
| `$return_object` | bool；true 将结果转换为分组对象；false 保留解析器结果（默认：false） |
| `&$snapshot` | string\|null；引用参数；输出稳定读取内容的 SHA-256 摘要；初始文件不存在为 null（默认：null） |

**返回值**：array|object 配置根节点，具体容器取决于格式及 return_object

**异常与失败**：InvalidArgumentException 文件名类型或格式不支持；UnexpectedValueException 解析结果根节点不是数组或对象；JsonException JSON 内容非法；RuntimeException 文件在读取中消失或持续变化；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->load($file, $return_object, $snapshot);
```

定位：`lib/myConfig.class.php:338`。

### myConfig::merge

功能说明：递归合并分组配置，同名标量覆盖，分组可替换原标量

输入数组/对象会复制为分组对象，避免把其嵌套引用直接挂入当前配置。

```text
public function merge($setting, &$org = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array\|object\|string；待覆盖的设置或配置文件路径；不存在的文件跳过 |
| `&$org` | object\|null；合并目标，null 使用实例设置；引用参数供递归及指定目标使用（默认：null） |

**返回值**：void 只更新内存，不保存文件或改变原文件的版本基准

**异常与失败**：InvalidArgumentException 输入不是数组、对象或文件路径

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->merge($setting, $org);
```

定位：`lib/myConfig.class.php:381`。

### myConfig::set

功能说明：按表单规则批量更新设置，保留原值的明确类型及未提交分组

只补齐已提交分组内缺失的 checkbox/switch；选择列表转 CSV。空密码不覆盖原值，
字符串 0 可更新密码，已登记的变换回调继续执行；忽略以 _pwd_r 结束的确认字段。

```text
public function set($setting, $idx = '', $check = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array\|object；旧式 setting 表单结构；嵌套映射表示分组，列表表示多选 |
| `$idx` | string；目标分组的点分隔路径；空字符串从根开始，缺失分组自动创建（默认：''） |
| `$check` | bool；是否补齐本次提交范围内缺失的复选框/开关，递归调用时为 false（默认：true） |

**返回值**：stdClass 本次更新的目标分组对象，修改尚未持久化

**异常与失败**：InvalidArgumentException 数据不是映射、选择值不是标量、密码或类型转换非法

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->set($setting, $idx, $check);
```

定位：`lib/myConfig.class.php:409`。

### myConfig::formValue

功能说明：依据已有字段的明确类型转换表单文本，不猜测文本字段类型

```text
protected function formValue($value, $current);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；新表单值；非字符串原样返回 |
| `$current` | mixed；旧值；布尔使用 FILTER_VALIDATE_BOOLEAN，数字类型在符合规则时转换 |

**返回值**：mixed 转换值；旧值为文本或 null 时保留前导零及 true/false 字面文本

**异常与失败**：InvalidArgumentException 布尔文本不合法或整数超出允许转换范围

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:468`。

### myConfig::save

功能说明：编码完整配置并原子保存，默认防止原文件被并发更新覆盖

先编码后写入，编码失败保留原文件。保存原文件时比较加载摘要，成功后更新本实例
版本；显式导出其他路径不套用原文件版本检查。新文件权限 0600，既有权限保留。

调用示例：
    try {
        $config->save();
    } catch (RuntimeException $error) {
        // 重新加载并处理写入或版本冲突
    }

```text
public function save($type='', $file = '', $overwrite = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$type` | string；php/ini/json 或已登记格式；空字符串沿用原扩展名（默认：''） |
| `$file` | string；目标路径；空字符串保存原路径，切换格式时自动替换原扩展名（默认：''） |
| `$overwrite` | bool；true 明确允许覆盖原文件的并发修改；默认 false 拒绝丢失更新（默认：false） |

**返回值**：int 写入的完整字节数

**异常与失败**：InvalidArgumentException 格式未登记；UnexpectedValueException 编码器没有返回字符串（包括 JSON 编码失败）；RuntimeException 目标不合法、无法加锁/写入/替换或内容版本冲突；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->save($type, $file, $overwrite);
```

定位：`lib/myConfig.class.php:507`。

### myConfig::atomicWrite

功能说明：锁定目标旁的锁文件，再以同目录临时文件原子发布完整内容

版本检查在锁内进行；目标已经等于待保存内容也可成功。拒绝目标和锁文件为
符号链接，保留已有权限，新文件 0600；清理临时文件并使 OPcache 失效。

```text
protected function atomicWrite($file, $content, $expected = null, $check = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径，父目录不存在时尝试创建 |
| `$content` | string；已完整编码的内容 |
| `$expected` | string\|null；预期现有内容的 SHA-256 摘要；null 表示预期尚不存在（默认：null） |
| `$check` | bool；true 比较内容版本；false 不检查（用于导出及派生结构）（默认：false） |

**返回值**：int 完整内容的字节数

**异常与失败**：RuntimeException 目标不合法、锁定/写入/权限设置/替换失败或版本冲突

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myConfig.class.php:540`。

### myConfig::build

功能说明：按可信结构定义生成设置表单项目及分组标题

实际配置值按 UTF-8 HTML 属性规则转义，保留反斜杠；选项在转义前比较。
名称、说明及结构标记来自可信定义，不把整个结构视为不可信 HTML 的过滤器。

```text
public function build($detail, $idx = '', $ext_setting = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$detail` | array\|string；结构数组或返回结构数组的 PHP 文件路径 |
| `$idx` | string\|array；分组路径；文件模式也可传扩展设置数组作为第二参数（默认：''） |
| `$ext_setting` | array；分组到字段的 type 定义覆盖，仅在文件加载模式应用（默认：array()） |

**返回值**：array|false 含 name/describe/html 的控件或 name/idx 分组标题列表；缺文件为 false

调用形式：

```php
// $instance为已初始化的myConfig实例。
$result = $instance->build($detail, $idx, $ext_setting);
```

定位：`lib/myConfig.class.php:572`。
