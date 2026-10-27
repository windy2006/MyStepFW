# myController：控制器基础资源

## 职责与调用位置

普通应用通过myStep继承；钩子、模块、语言和响应共用此层。run显式第三参是管道，省略时传控制器。

源码：`lib/myController.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 在已启动的应用上下文中登记；不要额外new第二个控制器。
$mystep->setFunction('start', function ($controller) {
    $controller->setAddedContent('head', '<meta name="example" content="yes">');
});
$mystep->setFunction('hello-data', function ($value) { return $value + 1; });
assert($mystep->run('hello-data', false, 0) === 1);
```

## 声明与类职责

### myController

功能说明：管理实例、语言、插件、模块、API、标签、钩子及CSS/JS合并输出。

使用说明：
  1. 由myStep及应用控制器继承；先登记资源和回调，再执行生命周期。
  2. show/end可能输出或结束请求；需要字符串时使用相应非输出入口。
  3. 本文件包含可继承公共接口，注释描述实际支持行为，不改变签名。

## PHP8.5运行说明

OPcache开关、内存和重验周期由服务器php.ini管理，框架启动不再逐请求覆盖。`setOp()`保留为显式维护入口；业务请求无需重复调用。域名和插件绑定等可变PHP配置在加载前主动失效旧字节码，保存后下一请求即可读取新值。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $modules = array(),
        $singleton = false,
        $goto_url = '';
```

```text
protected
        $functions = array(),
        $func_tag = array(),
        $func_api = array(),
        $func_log = array(),
        $plugins = array(),
        $language = array(),
        $page_content = array(),
        $url = array(),
        $css = array(),
        $js = array();
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myController::__clone` | 单实例克隆控制 |
| `myController::setSingleton` | 单实例模式设置 |
| `myController::getInstance` | 按类名取得实例，新实例执行构造初始化，单例复用不隐式重新初始化 |
| `myController::setAddedContent` | 设置指定页面内容 |
| `myController::pushAddedContent` | 将内容添加到指定位置 |
| `myController::setLanguage` | 设置页面语言 |
| `myController::setLanguagePack` | 获取语言包 |
| `myController::getLanguage` | 获取语言项目 |
| `myController::regApi` | 注册 API 处理函数 |
| `myController::runApi` | 获取 API 数据 |
| `myController::regPlugin` | 注册插件 |
| `myController::plugin` | 设置插件 |
| `myController::regModule` | 注册功能模块 |
| `myController::module` | 调用已注册模块 |
| `myController::regTag` | 注册模版标签处理函数 |
| `myController::regUrl` | 注册 URL 生成函数 |
| `myController::url` | 获取 URL |
| `myController::regLog` | 注册登录接口 |
| `myController::login` | 用户登录 |
| `myController::logout` | 用户退出 |
| `myController::chg_psw` | 变更密码 |
| `myController::addCSS` | 添加 CSS |
| `myController::removeCSS` | 移除 CSS |
| `myController::clearCSS` | 清空已添加样式 |
| `myController::CSS` | 获取合成样式表 |
| `myController::addJS` | 添加 JS |
| `myController::removeJS` | 移除 JS |
| `myController::clearJS` | 清空已添加 JS |
| `myController::JS` | 获取合成脚本 |
| `myController::etag` | 检测过期标签 |
| `myController::file` | 在框架默认及内置gzip缓冲下分块输出文件，保留原始字节并终止请求 |
| `myController::guid` | 生成GUID |
| `myController::setFunction` | 添加钩子代码 |
| `myController::run` | 执行钩子代码 |
| `myController::start` | 页面起始 |
| `myController::show` | 显示页面 |
| `myController::end` | 页面结束 |
| `myController::redirect` | 链接跳转 |
| `myController::setOp` | opCache 设置 |
| `myController::regClass` | 设置类载入规则 |
| `myController::setAlias` | 设置类别名 |
| `myController::header` | 发送信息头 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myController::__clone

功能说明：单实例克隆控制

参数说明：无显式参数。

```text
public function __clone();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 单例模式下触发错误并返回false，否则为true；PHP的clone表达式仍返回克隆对象

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myController.class.php:157`。

### myController::setSingleton

功能说明：单实例模式设置

```text
public function setSingleton($singleton = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$singleton` | bool；是否启用按类复用实例的单例模式（默认：false） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->setSingleton($singleton);
```

定位：`lib/myController.class.php:179`。

### myController::getInstance

功能说明：按类名取得实例，新实例执行构造初始化，单例复用不隐式重新初始化

```text
public static function getInstance($calledClass);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$calledClass` | string；待创建或获取实例的类名 |
| `...$args` | mixed；传给目标类init或构造函数的位置参数（位置参数，由func_get_args读取） |

**返回值**：object 新实例优先执行真实构造，所需参数缺省时延迟初始化；已有单例无参复用，显式传参重新初始化

**异常与失败**：ReflectionException 目标反射或底层调用产生的异常继续传递；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
$result = myController::getInstance($calledClass);
```

定位：`lib/myController.class.php:192`。

### myController::setAddedContent

功能说明：设置指定页面内容

```text
public function setAddedContent($position, $content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$position` | string；页面插入位置，例如head/start/end等既有位置 |
| `$content` | string；要追加到该位置的HTML/脚本内容 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->setAddedContent($position, $content);
```

定位：`lib/myController.class.php:230`。

### myController::pushAddedContent

功能说明：将内容添加到指定位置

```text
public function pushAddedContent(myTemplate $tpl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl` | myTemplate；已经初始化的模板实例 |
| `...$args` | string；可选页面位置名称列表；省略时一次赋值全部位置（位置参数，由func_get_args读取） |

**返回值**：void 将内容写入模板变量，不返回拼接结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->pushAddedContent($tpl);
```

定位：`lib/myController.class.php:242`。

### myController::setLanguage

功能说明：设置页面语言

```text
public function setLanguage($language, $charset = 'utf-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$language` | array；语言键到文本的映射；非数组不会合并 |
| `$charset` | string；语言文本需要转换到的目标编码（默认：'utf-8'） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->setLanguage($language, $charset);
```

定位：`lib/myController.class.php:266`。

### myController::setLanguagePack

功能说明：获取语言包

```text
public function setLanguagePack($dir, $lng = 'default', $charset = 'utf-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；目标目录 |
| `$lng` | string；语言包名称，不含文件扩展名（默认：'default'） |
| `$charset` | string；语言包文本转换到的目标编码（默认：'utf-8'） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->setLanguagePack($dir, $lng, $charset);
```

定位：`lib/myController.class.php:281`。

### myController::getLanguage

功能说明：获取语言项目

```text
public function getLanguage($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；语言键名；缺失时按当前语言策略返回原键或默认值 |

**返回值**：mixed 已登记语言键的原值；键不存在时原样返回idx

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->getLanguage($idx);
```

定位：`lib/myController.class.php:297`。

### myController::regApi

功能说明：注册 API 处理函数

```text
public function regApi($name, $method = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；对外登记的API名称 |
| `$method` | callable\|string；接口回调或方法名，空串使用名称约定（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->regApi($name, $method);
```

定位：`lib/myController.class.php:308`。

### myController::runApi

功能说明：获取 API 数据

```text
public function runApi($name, $para = array(), $return = 'json');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；已登记的API名称 |
| `$para` | array；依位置顺序传给接口的参数（默认：array()） |
| `$return` | string；结果输出格式，例如json/xml等已支持格式（默认：'json'） |

**返回值**：string 已登记接口的编码正文；名称未登记时返回空串，不调用接口

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->runApi($name, $para, $return);
```

定位：`lib/myController.class.php:325`。

### myController::regPlugin

功能说明：注册插件

```text
public function regPlugin($plugin, &$info = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$plugin` | string；插件目录名称或路径 |
| `&$info` | array；引用输出的插件元信息（默认：array()） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->regPlugin($plugin, $info);
```

定位：`lib/myController.class.php:342`。

### myController::plugin

功能说明：设置插件

参数说明：无显式参数。

```text
public function plugin();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->plugin();
```

定位：`lib/myController.class.php:358`。

### myController::regModule

功能说明：注册功能模块

```text
public static function regModule($module, $func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$module` | string；登记的模块名称 |
| `$func` | callable\|string；处理该模块的回调或执行入口 |

**返回值**：void 只登记模块，不立即执行

调用形式：

```php
$result = myController::regModule($module, $func);
```

定位：`lib/myController.class.php:371`。

### myController::module

功能说明：调用已注册模块

```text
public static function module($module, $global_vars = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$module` | string；模块名称 |
| `$global_vars` | string\|string[]；模块执行前需要导入的全局变量名列表（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myController::module($module, $global_vars);
```

定位：`lib/myController.class.php:389`。

### myController::regTag

功能说明：注册模版标签处理函数

```text
public function regTag($tag_name, $tag_func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tag_name` | string；模板标签名称 |
| `$tag_func` | callable；处理模板标签的回调 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->regTag($tag_name, $tag_func);
```

定位：`lib/myController.class.php:414`。

### myController::regUrl

功能说明：注册 URL 生成函数

```text
public function regUrl($mode, $func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | string；URL生成策略名称 |
| `$func` | callable；执行该策略的URL构造回调 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->regUrl($mode, $func);
```

定位：`lib/myController.class.php:426`。

### myController::url

功能说明：获取 URL

```text
public function url($mode);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | string；已登记的URL生成策略；附加参数传给该策略回调 |
| `...$args` | mixed；依顺序转交已登记URL回调的位置参数（位置参数，由func_get_args读取） |

**返回值**：mixed 已登记URL回调的原始返回值；未登记模式返回#

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->url($mode);
```

定位：`lib/myController.class.php:441`。

### myController::regLog

功能说明：注册登录接口

回调签名：login($usr,$pwd)、logout()、chg_psw($id,$psw_org,$psw_new)；原始返回值由对应登录入口直接返回。

```text
public function regLog($login, $logout = '', $chg_psw = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$login` | callable；登录处理回调 |
| `$logout` | callable\|string；退出处理回调，空串不登记（默认：''） |
| `$chg_psw` | callable\|string；密码修改回调，空串不登记（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->regLog($login, $logout, $chg_psw);
```

定位：`lib/myController.class.php:462`。

### myController::login

功能说明：用户登录

```text
public function login($usr='', $pwd='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$usr` | string；登录用户名（默认：''） |
| `$pwd` | string；账号密码，空串按调用入口的支持约定处理（默认：''） |

**返回值**：mixed 登录回调的原始返回值；未登记回调时返回false

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->login($usr, $pwd);
```

定位：`lib/myController.class.php:478`。

### myController::logout

功能说明：用户退出

参数说明：无显式参数。

```text
public function logout();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：mixed 退出回调的原始结果；未登记回调时返回false

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->logout();
```

定位：`lib/myController.class.php:494`。

### myController::chg_psw

功能说明：变更密码

```text
public function chg_psw($id, $psw_org, $psw_new);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；操作对象的标识 |
| `$psw_org` | string；原密码 |
| `$psw_new` | string；新密码 |

**返回值**：mixed 密码修改回调的原始结果；未登记时返回false

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->chg_psw($id, $psw_org, $psw_new);
```

定位：`lib/myController.class.php:511`。

### myController::addCSS

功能说明：添加 CSS

```text
public function addCSS($code);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；CSS文件路径或CSS源码；文件路径会立即读取并按内容去重 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->addCSS($code);
```

定位：`lib/myController.class.php:525`。

### myController::removeCSS

功能说明：移除 CSS

```text
public function removeCSS($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；项目、属性或资源的索引名称 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->removeCSS($idx);
```

定位：`lib/myController.class.php:542`。

### myController::clearCSS

功能说明：清空已添加样式

参数说明：无显式参数。

```text
public function clearCSS();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->clearCSS();
```

定位：`lib/myController.class.php:559`。

### myController::CSS

功能说明：获取合成样式表

```text
public function CSS($show = true, $cache_path = '', $expires = 604800);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true输出CSS并结束请求，false返回压缩结果字符串（默认：true） |
| `$cache_path` | string；生成资源缓存文件的目录，空串采用默认位置（默认：''） |
| `$expires` | int；缓存有效秒数（默认：604800） |

**返回值**：string show为false返回压缩CSS文本；show为true发送响应并结束请求

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->CSS($show, $cache_path, $expires);
```

定位：`lib/myController.class.php:572`。

### myController::addJS

功能说明：添加 JS

```text
public function addJS($code);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；JavaScript文件路径或源码；文件路径会立即读取并按内容去重 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->addJS($code);
```

定位：`lib/myController.class.php:592`。

### myController::removeJS

功能说明：移除 JS

```text
public function removeJS($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；项目、属性或资源的索引名称 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->removeJS($idx);
```

定位：`lib/myController.class.php:609`。

### myController::clearJS

功能说明：清空已添加 JS

参数说明：无显式参数。

```text
public function clearJS();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->clearJS();
```

定位：`lib/myController.class.php:626`。

### myController::JS

功能说明：获取合成脚本

```text
public function JS($show = true, $cache_path = '', $expires = 604800);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；true输出JavaScript并结束请求，false返回压缩结果字符串（默认：true） |
| `$cache_path` | string；生成资源缓存文件的目录，空串采用默认位置（默认：''） |
| `$expires` | int；缓存有效秒数（默认：604800） |

**返回值**：string show为false返回压缩JavaScript文本；show为true发送响应并结束请求

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->JS($show, $cache_path, $expires);
```

定位：`lib/myController.class.php:639`。

### myController::etag

功能说明：检测过期标签

输出约定：写入缓存响应头；GET/HEAD的If-None-Match命中时发送304并exit，支持列表、弱标签及星号；其他方法不返回304。空etag直接返回。

```text
public static function etag($etag = '', $expires = 604800);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$etag` | string；用于条件请求比较的资源标识（默认：''） |
| `$expires` | int；缓存有效秒数，限制为非负数（默认：604800） |

**返回值**：void 未命中时返回，命中时终止请求

**异常与失败**：InvalidArgumentException 标签含控制字符

调用形式：

```php
$result = myController::etag($etag, $expires);
```

定位：`lib/myController.class.php:664`。

### myController::file

功能说明：在框架默认及内置gzip缓冲下分块输出文件，保留原始字节并终止请求

```text
public static function file($file, $name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；要发送的本地文件路径 |
| `$name` | string；下载文件名；空串使用文件名，响应头会过滤控制字符并编码非ASCII字符（默认：''） |

**返回值**：void 成功输出、HEAD或文件不存在（404）均终止请求；不执行页面end钩子

**异常与失败**：RuntimeException 无法移除框架默认响应缓冲或文件读取失败

调用形式：

```php
$result = myController::file($file, $name);
```

定位：`lib/myController.class.php:694`。

### myController::guid

功能说明：生成GUID

```text
public static function guid($para = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；参与GUID摘要生成的附加文本（默认：''） |

**返回值**：string 32位十六进制GUID摘要，供标识使用，不作为认证令牌

调用形式：

```php
$result = myController::guid($para);
```

定位：`lib/myController.class.php:755`。

### myController::setFunction

功能说明：添加钩子代码

回调签名取决于run：显式传入第三参数时按管道传递，保留空串、0、false及null；省略第三参数时只传当前控制器。

```text
public function setFunction($position, $func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$position` | string；内容/钩子插入位置名称 |
| `$func` | callable\|callable[]；单个回调（包括可调用数组）或按登记顺序执行的回调列表 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->setFunction($position, $func);
```

定位：`lib/myController.class.php:790`。

### myController::run

功能说明：执行钩子代码

```text
public function run($position, $desc = false, $para = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$position` | string；内容/钩子插入位置名称 |
| `$desc` | bool；true按登记顺序的逆序执行回调（默认：false） |
| `$para` | mixed；显式传入时启动管道；数组展开为位置参数，其他值作为单参数；省略时传当前控制器（默认：''） |

**返回值**：mixed 依次执行回调后的最终值；初始参数及回调返回值按已有管道规则传递

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->run($position, $desc, $para);
```

定位：`lib/myController.class.php:809`。

### myController::start

功能说明：页面起始

```text
public function start($charset = 'UTF-8', $set_plugin = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'UTF-8'） |
| `$set_plugin` | bool；是否加载当前配置中启用的插件（默认：true） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->start($charset, $set_plugin);
```

定位：`lib/myController.class.php:833`。

### myController::show

功能说明：显示页面

输出约定：渲染并echo页面，可能开启缓冲；本方法本身不结束请求，生命周期收尾由调用方负责。

```text
public function show(myTemplate $tpl, $minify = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl` | myTemplate；已经初始化的模板实例 |
| `$minify` | bool；是否压缩最终HTML输出（默认：false） |
| `...$args` | string；支持第三位置参数：传给模板render的子模板名称（位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->show($tpl, $minify);
```

定位：`lib/myController.class.php:877`。

### myController::end

功能说明：页面结束

参数说明：无显式参数。

生命周期：执行end钩子及可调用shutdown；通常返回调用方，只有满足预设goto跳转条件时才结束请求。

```text
public function end();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myController实例。
$result = $instance->end();
```

定位：`lib/myController.class.php:898`。

### myController::redirect

功能说明：链接跳转

```text
public static function redirect($url = '', $code = '302');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；跳转目标；空串回到Referer或默认地址（默认：''） |
| `$code` | string\|int；重定向HTTP状态码，默认302（默认：'302'） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myController::redirect($url, $code);
```

定位：`lib/myController.class.php:914`。

### myController::setOp

功能说明：opCache 设置

```text
public static function setOp($setting='check');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array\|string；opcache配置数组，或check/config/debug/reset命令（默认：'check'） |

**返回值**：array|bool config返回OPcache配置，其他命令返回当前操作/状态结果；扩展不可用为false

调用形式：

```php
$result = myController::setOp($setting);
```

定位：`lib/myController.class.php:930`。

### myController::regClass

功能说明：设置类载入规则

```text
public static function regClass($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；加载规则列表；path相对于ROOT，运行时支持绝对路径；ext逗号分隔，idx文件相对于该path |

**返回值**：void 登记自动加载回调，不立即创建实例

调用形式：

```php
$result = myController::regClass($setting);
```

定位：`lib/myController.class.php:964`。

### myController::setAlias

功能说明：设置类别名

```text
public static function setAlias($list);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$list` | array；原类名与别名的映射 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myController::setAlias($list);
```

定位：`lib/myController.class.php:1001`。

### myController::header

功能说明：发送信息头

```text
public static function header($idx, $para = '', $exit = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string\|int；状态码或已支持的响应头类别 |
| `$para` | string\|array；响应头模板的替换值；数组用于refresh等多值模板（默认：''） |
| `$exit` | bool；是否在发出响应头后结束请求（默认：true） |

**返回值**：void 发送响应头；exit为true时结束请求

调用形式：

```php
$result = myController::header($idx, $para, $exit);
```

定位：`lib/myController.class.php:1015`。
