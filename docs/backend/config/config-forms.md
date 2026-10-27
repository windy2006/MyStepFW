# 配置表单结构、多语种与字段转换

## 本篇目标与调用场景

将配置从读取到后台编辑再到持久保存拆开，避免把每次读取变成写配置。

### 设置表单和结构

- 缺少 construction 文件时仍可更新普通设置，结构信息在实例中复用。
- 配置结构从当前定义生成，重新生成派生结构；内容相同不重复写入。
- set() 保留表单列表转换为 CSV的约定，按已有明确类型处理数值和开关，文本字段保留前导零及字面 true/false。
- 空密码不覆盖原值，字符串 `0` 可作为新密码；密码变换回调仍遵循结构定义。
- 部分分组提交不会顺带清空其他分组中未提交的开关和复选框。
- 配置控件转义实际值，保留反斜杠，并依据原始布尔值显示开关；选项比较在 HTML 转义之前进行。

## 操作步骤与注意事项

实际值、construction结构、配置说明语言与界面语言各有职责。字段变化要同步默认、表单、读取和保存；客户端need不是服务端规则替代。

## 完整表单结构示例

前提：在隔离目录建立返回配置数组的PHP文件，文件路径传给`myConfig`；以下只生成HTML，不接收HTTP提交，不直接输出整个配置对象。

```php
file_put_contents($file, "<?php return ['web'=>['title'=>'示例','enabled'=>true,'theme'=>'light']];");
$config = new myConfig($file);
$detail = ['web'=>[
    'name'=>'网站设置',
    'list'=>[
        'title'=>['name'=>'网站标题','describe'=>'用于页面标题', 'type'=>['text','', '1-80']],
        'enabled'=>['name'=>'启用','describe'=>'是否启用网站', 'type'=>['switch',1,'开启']],
        'theme'=>['name'=>'配色','describe'=>'当前风格', 'type'=>['radio',['明亮'=>'light','深色'=>'dark']]],
    ],
]];
$rows = $config->build($detail);
assert($rows[0]['idx'] === 'web');
assert(strpos($rows[1]['html'], 'name="setting[web][title]"') !== false);
// 标题行含name/idx；控件行含name/describe/html，由应用模板分别展示。
```

| 结构位置 | 含义与例子 |
| --- | --- |
| `web.name` | 分组显示名称，不是配置值 |
| `web.list.title` | 对应实际值`$config->value('web.title')` |
| `name/describe` | 字段标签与帮助说明，来自受信结构语言文件 |
| `type[0]` | text、textarea、password、checkbox、switch、radio、select；未知类型退回普通input |
| text的`type[1]/[2]` | need规则与len规则；false表示不生成need属性，空字符串是必填文本规则 |
| textarea的`type[2]` | 显示行数，区别于text的len |
| radio/checkbox的`type[1]` | 标签到存储值的映射，如`['明亮'=>'light']`；checkbox形成数组提交并按旧约定保存为CSV |
| select的`type[1]` | 列表时标签和值相同；关联数组时键是标签、值是存储值 |
| switch的`type[1]/[2]` | 提交值及开关旁的文字；缺省未选中项由已提交分组范围内的set规则补齐 |
| password | 还需要name2；生成密码及确认字段，空密码沿用原值。变换回调通过结构元数据登记 |

真实应用把实际值放在`config.php`，可编辑结构放在应用`config/chs.php`、`config/en.php`及`config/default.php`等语言结构文件（框架全局设置使用`config/construction/`）；界面通用提示放在`language/`。配置结构是可执行的受信PHP，不得由普通表单上传替换。新增字段必须同步默认值、各语言结构及读取处的默认值。

保存前先验证后台身份、CSRF、字段白名单及业务范围，再调用`set()`和`save()`；原文件的`save()`默认比较加载时摘要，第三参数`overwrite=false`保留并发保护，详见保存专篇。浏览器合法性检查请阅读[need规则、len与字段示例](../../frontend/javascript/js-need.md)。

## 主要方法：参数、返回和失败约定

### myConfig::makeConstruction

功能说明：编译并缓存表单结构元数据，只有生成内容改变时才原子写入

提取 checkbox/switch/password 等字段的点分隔路径及密码变换回调。
直接传入 construction.php 时加载既有元数据；定义文件与元数据须为可信 PHP。

```text
public function makeConstruction($file);
```

| 参数说明 |
| --- |
| string $file 设置结构定义路径，或可直接 include 返回数组的 construction.php |

**返回**：void 更新实例 schema 及 construction；找不到可加载结构时保持当前状态

**异常**：UnexpectedValueException 定义或元数据不是数组；RuntimeException 派生结构文件无法原子保存

### myConfig::set

功能说明：按表单规则批量更新设置，保留原值的明确类型及未提交分组

只补齐已提交分组内缺失的 checkbox/switch；选择列表转 CSV。空密码不覆盖原值，
字符串 0 可更新密码，已登记的变换回调继续执行；忽略以 _pwd_r 结束的确认字段。

```text
public function set($setting, $idx = '', $check = true);
```

| 参数说明 |
| --- |
| array\|object $setting  setting 表单结构；嵌套映射表示分组，列表表示多选 |
| string $idx 目标分组的点分隔路径；空字符串从根开始，缺失分组自动创建（默认：''） |
| bool $check 是否补齐本次提交范围内缺失的复选框/开关，递归调用时为 false（默认：true） |

**返回**：stdClass 本次更新的目标分组对象，修改尚未持久化

**异常**：InvalidArgumentException 数据不是映射、选择值不是标量、密码或类型转换非法

### myConfig::build

功能说明：按可信结构定义生成设置表单项目及分组标题

实际配置值按 UTF-8 HTML 属性规则转义，保留反斜杠；选项在转义前比较。
名称、说明及结构标记来自可信定义，不把整个结构视为不可信 HTML 的过滤器。

```text
public function build($detail, $idx = '', $ext_setting = array());
```

| 参数说明 |
| --- |
| array\|string $detail 结构数组或返回结构数组的 PHP 文件路径 |
| string\|array $idx 分组路径；文件模式也可传扩展设置数组作为第二参数（默认：''） |
| array $ext_setting 分组到字段的 type 定义覆盖，仅在文件加载模式应用（默认：array()） |

**返回**：array|false 含 name/describe/html 的控件或 name/idx 分组标题列表；缺文件为 false

## 明确调用示例

```php
// $file指向临时配置文件，内容为return ['web'=>['title'=>'原名']];
$config = new myConfig($file);
assert($config->value('web.title') === '原名');
$config->merge(['web' => ['title' => '新名']]);
$config->save('php');
assert((include $file)['web']['title'] === '新名');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
