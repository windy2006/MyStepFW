# myTemplate：模板实例和编译

修订日期：2026-10-23。功能详解。

## 职责与调用位置

name/path/style决定模板，编译缓存与页面缓存不同。渲染字符串返回与直接输出显式区分。

源码：`lib/myTemplate.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$template = new myTemplate([
    'name'=>'list', 'path'=>$templateDirectory, 'path_compile'=>$compileDirectory,
], false);
$template->assign('title', 'A&B')->setIf('visible', true)
    ->setLoop('items', [['id'=>1,'title'=>'中文']], true);
$html = $template->render('', false);
// list.tpl使用<!--title|html-->和<!--loop:start key="items"-->等标签。
```

## 声明与类职责

### myTemplate

功能说明：解析变量、循环、条件、switch、子模板及自定义标签，管理依赖和页面缓存。

使用说明：
  1. 传模板路径/名称/编译目录初始化，通过assign/setLoop填充再render。
  2. 模板及标签回调是受信程序资源；不把用户任意文本作为可执行模板。
  3. 继承、编译指纹、页面缓存及旧标签兼容规则见docs/framework/templates.md。

补充说明：
前台模板引擎：独立变量空间、嵌套块、可信标签扩展及按内容校验的编译/页面缓存。
普通占位符兼容原样输出；<!--name|html--> 用于 HTML 文本或引号属性，
<!--name|url--> 用于 URL 组件。模板、标签回调及编译目录必须由可信应用控制。
页面缓存不会自动区分用户，请使用 cache.vary 或按用户/语言分目录。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static $tpl_para = [];
```

```text
public $allow_script = false;
```

```text
protected $delimiter_l = '<!--', $delimiter_r = '-->', $hash = '';
```

```text
protected $setting = [], $tags = [], $dependencies = [], $tag_dependencies = [], $php_parts = [], $sequence = 0;
```

```text
protected $cache = ['use'=>false, 'file'=>'', 'expire'=>300];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myTemplate::init` | 初始化模板、静态页面缓存和标签分隔符 |
| `myTemplate::relativeName` | 校验模板名称或风格子目录，禁止绝对路径、包装器和上级目录 |
| `myTemplate::setTplPara` | 设置模板位置并建立当前对象的数据空间，同一对象切换模板会清空赋值 |
| `myTemplate::__destruct` | 对象释放时删除所属变量空间，避免长驻进程累积模板数据 |
| `myTemplate::__clone` | 克隆时复制变量空间，后续赋值彼此独立 |
| `myTemplate::__serialize` | 序列化时携带当前对象的数据，避免恢复对象借用原实例静态空间 |
| `myTemplate::__unserialize` | 恢复时建立新变量空间，不重新运行 init 或清除原对象的赋值 |
| `myTemplate::setCacheMode` | 启用或关闭页面缓存；登录态/语言等差异必须由调用方通过 vary 或独立路径区分 |
| `myTemplate::getTemplate` | 获取模板或默认风格模板，并登记编译依赖（含不存在的候选路径） |
| `myTemplate::setLoop` | 设置循环记录，支持数组或可遍历对象 |
| `myTemplate::setIf` | 设置条件值，缺省条件为 false |
| `myTemplate::setSwitch` | 设置分支值，未赋值时按 null 处理 |
| `myTemplate::assign` | 赋值普通变量，数组/对象展开一层为 name_key；旧占位符仍原样输出 |
| `myTemplate::assignEscaped` | 将变量按 HTML 文本/引号属性上下文转义后赋值；不要再叠加 \|html |
| `myTemplate::loadSet` | 加载可信 PHP 返回数组、INI 或 JSON 配置，支持重复调用 |
| `myTemplate::attributes` | 解析引号属性，完整保留等号、反斜杠、空串及相反种类的引号 |
| `myTemplate::parseBlock` | 解析旧扩展接口使用的块属性，正文不再 stripslashes |
| `myTemplate::regTag` | 登记可信的编译期标签回调，扩展结果可包含 PHP |
| `myTemplate::identity` | 计算基础编译身份，模板值不写入编译文件 |
| `myTemplate::tagIdentity` | 计算已登记标签的实现身份；闭包和对象回调按模板实例隔离；具名回调的外部状态变化请更新 compile_version |
| `myTemplate::dependenciesValid` | 校验元数据中所有依赖的内容摘要，同时检查原先不存在的模板是否已出现 |
| `myTemplate::metadata` | 读取缓存元数据，损坏文件作为未命中处理 |
| `myTemplate::atomicWrite` | 在目标目录写临时文件后原子替换，避免读到半个 PHP/HTML 文件 |
| `myTemplate::compiledFile` | 取得旧接口使用的编译入口位置，风格目录总是正确连接 |
| `myTemplate::protectPHP` | 用 PHP 词法器隔离脚本，防止模板正文被编译器当成 PHP 字符串插值 |
| `myTemplate::literalCode` | 将正文编译为安全的字面量输出，内部脚本标记恢复为可信代码 |
| `myTemplate::templateTokens` | 将模板分隔标签转为词元，PHP 部分已经隔离 |
| `myTemplate::compileSequence` | 递归编译标准块，嵌套块和循环使用独立局部变量 |
| `myTemplate::displayValue` | 将占位值转为可显示字符串；不存在/不可显示对象返回空串 |
| `myTemplate::templateValue` | 读取最内层匹配的循环字段或普通变量，缺失字段安静输出空串 |
| `myTemplate::compileTemplate` | 按内容和编译选项复用缓存，生成不可变版本文件供当前渲染使用 |
| `myTemplate::removeCache` | 清除当前模板编译版本，可选择同时删除当前页面缓存 |
| `myTemplate::checkCache` | 检测静态页面是否有效，核对引擎身份、依赖、页面摘要及实际当前时间 |
| `myTemplate::getCache` | 读取有效的静态页面 |
| `myTemplate::render` | 获取最终 HTML；独立缓冲区保证异常时不清空调用者已有内容 |
| `myTemplate::minifyHTML` | 保守压缩 HTML，去除普通标签行首缩进；特殊内容整段保护 |
| `myTemplate::tagPlaceholders` | 在自定义标签返回文本中按 PHP 词法上下文输出属性占位符 |
| `myTemplate::parseTag` | 编译自定义标签，保留既有 PHP 代码生成及 split 标记约定 |
| `myTemplate::getBlock` | 提取首个完整块，兼容可信扩展生成 PHP 时使用的历史返回格式 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myTemplate::init

功能说明：初始化模板、静态页面缓存和标签分隔符


调用示例：
    $tpl = new myTemplate(['path' => $views, 'path_compile' => $compiled, 'name' => 'home']);
    $tpl->assign('title', '示例'); $html = $tpl->render('', false);

```text
public function init($setting, $cache = false, $allow_script = false, $delimiter = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；name/style/path/path_compile/ext；compile_version 可显式更新扩展编译版本 |
| `$cache` | bool\|array\|null；false/null关闭，true或数组启用；选项含name/path/ext/expire/vary，expire单位秒（默认：false） |
| `$allow_script` | bool；仅对可信模板开启，注册标签回调始终属于可信 PHP 扩展（默认：false） |
| `$delimiter` | array\|null；两个非空字符串，默认 HTML 注释分隔符（默认：null） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 参数或相对模板名称不合法

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->init($setting, $cache, $allow_script, $delimiter);
```

定位：`lib/myTemplate.class.php:136`。

### myTemplate::relativeName

功能说明：校验模板名称或风格子目录，禁止绝对路径、包装器和上级目录

```text
protected function relativeName($value, $empty = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | string；相对路径，允许 a/b 形式 |
| `$empty` | bool；是否允许空值（默认：false） |

**返回值**：string 去掉末尾目录分隔符后的相对路径

**异常与失败**：InvalidArgumentException 路径非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:156`。

### myTemplate::setTplPara

功能说明：设置模板位置并建立当前对象的数据空间，同一对象切换模板会清空赋值


设置结构：name默认main，style默认空串，path默认./，path_compile默认./complied（沿用历史拼写），ext默认tpl，compile_version默认空串；路径名必须来自受信配置。

```text
public function setTplPara($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；name 默认 main，ext 默认 tpl；path_compile 默认 ./complied |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：InvalidArgumentException 配置非法

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->setTplPara($setting);
```

定位：`lib/myTemplate.class.php:175`。

### myTemplate::__destruct

功能说明：对象释放时删除所属变量空间，避免长驻进程累积模板数据

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:203`。

### myTemplate::__clone

功能说明：克隆时复制变量空间，后续赋值彼此独立

参数说明：无显式参数。

```text
public function __clone();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:215`。

### myTemplate::__serialize

功能说明：序列化时携带当前对象的数据，避免恢复对象借用原实例静态空间

参数说明：无显式参数。

```text
public function __serialize();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 包含子类私有属性的实例状态和模板数据；闭包/资源限制仍遵循 PHP 原生行为

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:227`。

### myTemplate::__unserialize

功能说明：恢复时建立新变量空间，不重新运行 init 或清除原对象的赋值

```text
public function __unserialize(array $data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；__serialize 返回的可信对象状态 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:237`。

### myTemplate::setCacheMode

功能说明：启用或关闭页面缓存；登录态/语言等差异必须由调用方通过 vary 或独立路径区分


缓存结构：name默认当前模板名，path默认模板目录/cache/风格，ext默认html，expire默认300秒；expire非正不复用页面，vary用于区分用户/语言等上下文。

```text
public function setCacheMode($cache);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cache` | bool\|array\|null；false/null关闭页面缓存，true使用默认选项；数组可设置name/path/ext/expire/vary |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：InvalidArgumentException 参数或扩展名非法

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->setCacheMode($cache);
```

定位：`lib/myTemplate.class.php:263`。

### myTemplate::getTemplate

功能说明：获取模板或默认风格模板，并登记编译依赖（含不存在的候选路径）

```text
public function getTemplate($file1, &$file2 = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file1` | string；首选本地文件；只能由可信应用代码构造 |
| `&$file2` | string；引用参数；备用文件，成功后回填实际位置（默认：''） |

**返回值**：string 原始模板内容，不转换换行或反斜杠

**异常与失败**：RuntimeException 模板不存在或不能读取

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->getTemplate($file1, $file2);
```

定位：`lib/myTemplate.class.php:290`。

### myTemplate::setLoop

功能说明：设置循环记录，支持数组或可遍历对象

```text
public function setLoop($key, $value, $fullset = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；循环名称，对应 key_field 占位符 |
| `$value` | array\|object；单条记录；fullset=true 时为完整记录集合 |
| `$fullset` | bool；true 替换集合，false 追加记录；time 仍只作最少行数补齐（默认：false） |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：InvalidArgumentException 集合或记录类型非法

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->setLoop($key, $value, $fullset);
```

定位：`lib/myTemplate.class.php:316`。

### myTemplate::setIf

功能说明：设置条件值，缺省条件为 false

```text
public function setIf($key, $value = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；条件名称 |
| `$value` | mixed；按 PHP 布尔规则判断（默认：true） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->setIf($key, $value);
```

定位：`lib/myTemplate.class.php:336`。

### myTemplate::setSwitch

功能说明：设置分支值，未赋值时按 null 处理

```text
public function setSwitch($key, $value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；分支名称 |
| `$value` | mixed；保留旧 switch 的宽松比较规则 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->setSwitch($key, $value);
```

定位：`lib/myTemplate.class.php:345`。

### myTemplate::assign

功能说明：赋值普通变量，数组/对象展开一层为 name_key；旧占位符仍原样输出

```text
public function assign($name, $value = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string\|array\|object；名称或无前缀批量变量 |
| `$value` | mixed；值；非字符串标量保留 myString::fromAny 的历史表示（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

**相关入口**：myTemplate::assignEscaped() HTML文本/属性的转义赋值

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->assign($name, $value);
```

定位：`lib/myTemplate.class.php:355`。

### myTemplate::assignEscaped

功能说明：将变量按 HTML 文本/引号属性上下文转义后赋值；不要再叠加 |html

```text
public function assignEscaped($name, $value = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string\|array\|object；名称或批量映射 |
| `$value` | mixed；值，批量赋值沿用 assign 的一层展开规则（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->assignEscaped($name, $value);
```

定位：`lib/myTemplate.class.php:370`。

### myTemplate::loadSet

功能说明：加载可信 PHP 返回数组、INI 或 JSON 配置，支持重复调用

```text
public function loadSet($file, $prefix = '', $mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；本地配置位置；PHP 文件会执行，只能来自可信应用 |
| `$prefix` | string；赋值前缀（默认：''） |
| `$mode` | bool；true 用下划线保留嵌套键路径，false 仅保留叶子键（默认：false） |

**返回值**：$this 不存在文件沿用旧行为忽略

**异常与失败**：UnexpectedValueException 配置不是数组或深度超过 64

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->loadSet($file, $prefix, $mode);
```

定位：`lib/myTemplate.class.php:386`。

### myTemplate::attributes

功能说明：解析引号属性，完整保留等号、反斜杠、空串及相反种类的引号

```text
protected function attributes($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；属性列表 |

**返回值**：array 小写属性名到原始字符串值的映射

**异常与失败**：UnexpectedValueException 属性语法错误

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:415`。

### myTemplate::parseBlock

功能说明：解析旧扩展接口使用的块属性，正文不再 stripslashes

```text
protected function parseBlock($attrib, $content, &$block_attrib, &$block_content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$attrib` | string；引号属性串 |
| `$content` | string；正文 |
| `&$block_attrib` | array；引用参数；回填属性 |
| `&$block_content` | string；引用参数；回填原样正文 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:434`。

### myTemplate::regTag

功能说明：登记可信的编译期标签回调，扩展结果可包含 PHP


回调签名：function (myTemplate &$tpl, array &$attrs): string；两项按引用传入，允许回调忽略不需要的形参。attrs以解析值为基础并含unit/unit_blank/class，回调可补充占位参数；在编译期执行，返回的PHP仅在渲染时按所处模板分支执行。

调用示例：
    $tpl->regTag('hello', function ($current, &$attrs) {
        return htmlspecialchars((string)($attrs['name'] ?? '访客'), ENT_QUOTES, 'UTF-8');
    });

```text
public function regTag($tag_name, $tag_func = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tag_name` | string\|array；标签名或名称到回调的映射 |
| `$tag_func` | callable\|string；接收当前模板和属性引用，返回模板/PHP 文本；允许无属性标签（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：InvalidArgumentException 标签名或回调非法

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->regTag($tag_name, $tag_func);
```

定位：`lib/myTemplate.class.php:456`。

### myTemplate::identity

功能说明：计算基础编译身份，模板值不写入编译文件

参数说明：无显式参数。

```text
protected function identity();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 包含引擎、文件路径、脚本开关、分隔符及显式版本的摘要

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:469`。

### myTemplate::tagIdentity

功能说明：计算已登记标签的实现身份；闭包和对象回调按模板实例隔离；具名回调的外部状态变化请更新 compile_version

参数说明：无显式参数。

```text
protected function tagIdentity();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 名称、源码文件摘要及定义位置的摘要；闭包和对象回调包含实例身份

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:480`。

### myTemplate::dependenciesValid

功能说明：校验元数据中所有依赖的内容摘要，同时检查原先不存在的模板是否已出现

```text
protected function dependenciesValid(array $files);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$files` | array；路径到 SHA256/null 的映射 |

**返回值**：bool 全部一致才允许复用缓存

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:500`。

### myTemplate::metadata

功能说明：读取缓存元数据，损坏文件作为未命中处理

```text
protected function metadata($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；元数据位置 |

**返回值**：array 缺失/格式错误时为空数组

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:515`。

### myTemplate::atomicWrite

功能说明：在目标目录写临时文件后原子替换，避免读到半个 PHP/HTML 文件

```text
protected function atomicWrite($file, $content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标位置，目录由可信应用配置 |
| `$content` | string；完整文件内容 |

**返回值**：void 无返回值。

**异常与失败**：RuntimeException 目录或写入/替换失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:529`。

### myTemplate::compiledFile

功能说明：取得旧接口使用的编译入口位置，风格目录总是正确连接

参数说明：无显式参数。

```text
protected function compiledFile();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 编译入口的本地绝对路径

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:547`。

### myTemplate::protectPHP

功能说明：用 PHP 词法器隔离脚本，防止模板正文被编译器当成 PHP 字符串插值

```text
protected function protectPHP($text, $keep);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；模板内容 |
| `$keep` | bool；true 保存可信 PHP 为内部标记；false 去掉脚本 |

**返回值**：string 只含正文和内部标记的文本

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:558`。

### myTemplate::literalCode

功能说明：将正文编译为安全的字面量输出，内部脚本标记恢复为可信代码

```text
protected function literalCode($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；正文 |

**返回值**：string PHP 语句，不改变美元符、反斜杠、换行或 heredoc 结束字样

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:579`。

### myTemplate::templateTokens

功能说明：将模板分隔标签转为词元，PHP 部分已经隔离

```text
protected function templateTokens($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；正文 |

**返回值**：array 交替的正文及标签原文

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:597`。

### myTemplate::compileSequence

功能说明：递归编译标准块，嵌套块和循环使用独立局部变量

```text
protected function compileSequence(array $tokens, &$i, array $stops = [], $depth = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tokens` | array；词元 |
| `&$i` | int；引用参数；当前下标，返回时停在调用方要求的结束/分支标签 |
| `$stops` | array；当前层允许的结束标记；switch:default 为默认分支，(default) 保持普通字符串分支（默认：[]） |
| `$depth` | int；嵌套深度，上限 64（默认：0） |

**返回值**：string PHP 输出语句

**异常与失败**：UnexpectedValueException 块未闭合、交叉闭合或嵌套过深

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:611`。

### myTemplate::displayValue

功能说明：将占位值转为可显示字符串；不存在/不可显示对象返回空串

```text
protected function displayValue($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；原始值 |

**返回值**：string 不隐式打印数组或无 __toString 的对象

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:673`。

### myTemplate::templateValue

功能说明：读取最内层匹配的循环字段或普通变量，缺失字段安静输出空串

```text
protected function templateValue($name, array $data, array $rows, $filter = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；完整占位名称 |
| `$data` | array；当前对象变量空间 |
| `$rows` | array；外到内的循环记录栈 |
| `$filter` | string；空串原样、html 为 UTF-8 HTML 转义、url 为 URL 组件编码（默认：''） |

**返回值**：string 最终输出；HTML 转义不适用于 JS/CSS 或危险 URL 协议

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:686`。

### myTemplate::compileTemplate

功能说明：按内容和编译选项复用缓存，生成不可变版本文件供当前渲染使用

参数说明：无显式参数。

```text
protected function compileTemplate();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前版本的 PHP 文件路径；旧 name.php 保留为最新版本入口

**异常与失败**：RuntimeException 文件操作失败；UnexpectedValueException 模板语法非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:706`。

### myTemplate::removeCache

功能说明：清除当前模板编译版本，可选择同时删除当前页面缓存

```text
public function removeCache($html = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$html` | bool；true 连同当前 vary 对应的静态页面一起删除，默认保持历史行为（默认：false） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->removeCache($html);
```

定位：`lib/myTemplate.class.php:735`。

### myTemplate::checkCache

功能说明：检测静态页面是否有效，核对引擎身份、依赖、页面摘要及实际当前时间

参数说明：无显式参数。

```text
public function checkCache();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 空串及字符串 0 也是有效缓存；无元数据的旧页面会重新生成

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->checkCache();
```

定位：`lib/myTemplate.class.php:750`。

### myTemplate::getCache

功能说明：读取有效的静态页面

参数说明：无显式参数。

```text
public function getCache();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 未命中返回空串；需区分空页面时先调用 checkCache

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->getCache();
```

定位：`lib/myTemplate.class.php:765`。

### myTemplate::render

功能说明：获取最终 HTML；独立缓冲区保证异常时不清空调用者已有内容

```text
public function render($global_vars = '', $tpl_show = true, $minify = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$global_vars` | string\|array；供可信扩展 PHP 使用的全局变量名，逗号分隔或数组（默认：''） |
| `$tpl_show` | bool；true 输出并返回，false 只返回（默认：true） |
| `$minify` | bool；保守去除 HTML 行首缩进，保留 pre/textarea/script/style 内容（默认：false） |

**返回值**：string 渲染后的内容

**异常与失败**：Throwable 编译或扩展异常继续传播，渲染缓冲区始终清理

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->render($global_vars, $tpl_show, $minify);
```

定位：`lib/myTemplate.class.php:776`。

### myTemplate::minifyHTML

功能说明：保守压缩 HTML，去除普通标签行首缩进；特殊内容整段保护

```text
protected function minifyHTML($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；完整 HTML |

**返回值**：string 保留注释、文本空格、脚本与样式的结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:809`。

### myTemplate::tagPlaceholders

功能说明：在自定义标签返回文本中按 PHP 词法上下文输出属性占位符

```text
protected function tagPlaceholders($result, array &$attrs);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$result` | string；可信扩展生成的 HTML/PHP |
| `&$attrs` | array；引用参数；标签属性，回填未提供的占位键为空串 |

**返回值**：string HTML 中使用输出语句，PHP 字符串中使用连接或插值，避免嵌套 PHP 标签

**异常与失败**：UnexpectedValueException nowdoc占位值是动态变量表达式，无法按该上下文展开

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:824`。

### myTemplate::parseTag

功能说明：编译自定义标签，保留既有 PHP 代码生成及 split 标记约定

```text
protected function parseTag($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；已隔离模板内原始 PHP 的内容 |

**返回值**：string 未知标签原样保留，登记标签由可信回调展开

**异常与失败**：UnexpectedValueException 属性格式不合法；Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTemplate.class.php:858`。

### myTemplate::getBlock

功能说明：提取首个完整块，兼容可信扩展生成 PHP 时使用的历史返回格式

```text
public function getBlock($tpl_content, $tag = '', $idx = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl_content` | string；子模板正文 |
| `$tag` | string；loop/if/switch，支持同类嵌套且名称按字面匹配（默认：''） |
| `$idx` | string；loop 记录变量名，替换为旧 heredoc 字段表达式（默认：''） |

**返回值**：array [块原文, 单元或分支映射, 补白/else/switch 默认正文]；(default) 仍为普通分支键，未找到时均为空串

**异常与失败**：InvalidArgumentException 非法 loop PHP 变量名；UnexpectedValueException 找到的块未闭合

调用形式：

```php
// $instance为已初始化的myTemplate实例。
$result = $instance->getBlock($tpl_content, $tag, $idx);
```

定位：`lib/myTemplate.class.php:898`。

