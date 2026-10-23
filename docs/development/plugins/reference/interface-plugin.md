# interface_plugin：插件公共生命周期和辅助函数

修订日期：2026-10-23。功能详解。

## 职责与调用位置

接口约束check/install/uninstall；辅助函数用于绑定、路由、模板、优先脚本与包，均不代替入口鉴权。

源码：`plugin/interface_plugin.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
class plugin_hello implements interface_plugin {
    public static function check(&$result) { $result = '无额外依赖'; return true; }
    public static function install() { if (!regPluginRoute('hello')) throw new RuntimeException('路由登记失败'); }
    public static function uninstall() { removePluginRoute('hello'); removePriority('hello'); }
}
// 此处只声明类，不执行安装；实际安装由管理员流程调用。
```

## 声明与类职责

### interface_plugin

功能说明：约定插件检查、安装与卸载生命周期的公共接口。

使用说明：
  1. 插件主类按 plugin_目录名 命名并实现此接口；由后台管理流程显式调用生命周期。
  2. check 用于检查与说明；install/uninstall 失败须抛异常，兼容原有无返回值实现。
  3. 接口只定义生命周期，不自动提供业务授权、活动状态判断或多资源事务。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_plugin::check` | 检查运行环境、扩展及依赖，将检查结果写入引用参数。 |
| `interface_plugin::install` | 初始化插件资源，登记路由、菜单和所需的优先脚本。 |
| `interface_plugin::uninstall` | 清理插件注册信息及安装资源，数据保留策略由插件实现决定。 |
| `addPluginLink` | 幂等增加/更新插件菜单，保留其余菜单内容。 |
| `removePluginLink` | 删除指定插件的全部重复菜单项，空菜单回到框架插件管理入口。 |
| `changePluginMenu` | 持有旁路锁执行菜单读改写，避免同时安装插件时丢失菜单。 |
| `pluginDirectory` | 校验插件标识并返回目录，加载资源时同时检查目录存在且不是符号链接。 |
| `setPluginApps` | 同步插件应用绑定，按应用名顺序持有全部旁路锁后再校验和发布。 |
| `setPluginOrder` | 校验并在同一次注册表排他读改写内保存插件运行顺序。 |
| `buildPluginPackage` | 在隔离副本中导出指定插件，供新旧后台共用且不依赖manager已启用。 |
| `pluginQueryURL` | 合并 URL 查询参数，保留 index.php?路由和片段，值为 null 时删除参数。 |
| `regPluginRoute` | 将插件 route.php 同步到编译表，并写入显式路由源映射。 |
| `removePluginRoute` | 移除插件编译规则及显式源映射，目录已删除或重复清理时仍可执行。 |
| `changePluginRoute` | 持有源映射旁路锁，合并插件映射并调用路由器同步编译规则。 |
| `savePluginFile` | 先完整写入同目录临时文件，再原子替换配置/脚本，并清除对应 OPcache。 |
| `setPluginTemplate` | 创建外层页面模板及插件内容模板，不覆盖应用全局模板配置。 |
| `pluginPageHost` | 检查应用是否提供通用插件后台布局适配器，myStep作为内置缺省后台。 |
| `pluginPageApplication` | 从可信Referer识别插件页面的外层后台，缺省返回myStep。 |
| `pluginPageURL` | 生成保留插件后台展示标记的页面链接，兼容三种路由及原查询参数。 |
| `pluginAdminURL` | 生成当前展示后台中的设置、缓存等通用功能链接。 |
| `renderPluginPage` | 统一装配已启用插件的后台模块及内容模板，返回主体HTML。 |
| `showPluginPage` | 按当前展示后台统一输出插件管理页，保留原插件路由与业务参数。 |
| `setPriority` | 将插件 priority.php 原子复制到框架优先脚本目录，覆盖旧版本。 |
| `removePriority` | 删除插件优先脚本并使 OPcache 失效，支持目录已删除和重复清理。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_plugin::check

功能说明：检查运行环境、扩展及依赖，将检查结果写入引用参数。

```text
public static function check(&$result);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$result` | string；供后台展示的检查说明；实现方可设置默认空串 |

**返回值**：bool 满足安装条件为 true，否则为 false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`plugin/interface_plugin.class.php:63`。

### interface_plugin::install

功能说明：初始化插件资源，登记路由、菜单和所需的优先脚本。

参数说明：无显式参数。

```text
public static function install();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool|null 兼容原有无返回值实现；管理器不以返回值判定成功，失败须抛异常

**异常与失败**：Exception 安装失败时由实现方或辅助函数抛出

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`plugin/interface_plugin.class.php:73`。

### interface_plugin::uninstall

功能说明：清理插件注册信息及安装资源，数据保留策略由插件实现决定。

参数说明：无显式参数。

```text
public static function uninstall();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool|null 兼容原有无返回值实现；管理器不以返回值判定成功，失败须抛异常

**异常与失败**：Exception 卸载失败时由实现方或辅助函数抛出

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`plugin/interface_plugin.class.php:83`。

### addPluginLink

功能说明：幂等增加/更新插件菜单，保留其余菜单内容。

```text
function addPluginLink($name, $path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；显示名称 |
| `$path` | string；插件路由路径 |

**返回值**：bool 保存结果

**异常与失败**：UnexpectedValueException 菜单结构无效；RuntimeException 锁或保存失败

调用形式：

```php
$result = addPluginLink($name, $path);
```

定位：`plugin/interface_plugin.class.php:95`。

### removePluginLink

功能说明：删除指定插件的全部重复菜单项，空菜单回到框架插件管理入口。

```text
function removePluginLink($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；插件路由路径 |

**返回值**：bool 保存结果

**异常与失败**：UnexpectedValueException 菜单结构无效；RuntimeException 锁或保存失败

调用形式：

```php
$result = removePluginLink($path);
```

定位：`plugin/interface_plugin.class.php:122`。

### changePluginMenu

功能说明：持有旁路锁执行菜单读改写，避免同时安装插件时丢失菜单。

```text
function changePluginMenu($change);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$change` | callable；回调接收引用菜单数组及插件组下标 |

**返回值**：bool 保存结果

**异常与失败**：RuntimeException 锁或保存失败；UnexpectedValueException 原菜单无效，不覆盖原文件

调用形式：

```php
$result = changePluginMenu($change);
```

定位：`plugin/interface_plugin.class.php:159`。

### pluginDirectory

功能说明：校验插件标识并返回目录，加载资源时同时检查目录存在且不是符号链接。

```text
function pluginDirectory($idx, $required = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录名，只允许字母开头的字母、数字及下划线 |
| `$required` | bool；true 要求目录存在且非符号链接；false 仅校验标识，用于清理已删除的插件（默认：true） |

**返回值**：string 插件目录，带末尾斜线；required=false 不表示该目录可用

**异常与失败**：InvalidArgumentException 标识非法，或 required=true 时目录不存在/为符号链接

调用形式：

```php
$result = pluginDirectory($idx, $required);
```

定位：`plugin/interface_plugin.class.php:185`。

### setPluginApps

功能说明：同步插件应用绑定，按应用名顺序持有全部旁路锁后再校验和发布。

保留其他插件及原顺序，去除目标插件的重复项；仅数据变化时写入连续索引数组。
全部现有清单通过校验后才开始保存；单文件原子发布，不提供跨文件I/O回滚。

```text
function setPluginApps($idx, array $apps, array $required = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录标识；卸载清理时允许源目录已经不存在 |
| `$apps` | string[]；本次选择的应用标识 |
| `$required` | string[]；安装/设置时必须保留的应用标识，卸载时传空数组（默认：[]） |

**返回值**：int 实际改写的应用清单数量

**异常与失败**：InvalidArgumentException 插件或应用标识无效、指定应用不存在或路径含符号链接；UnexpectedValueException 现有清单不是字符串数组，保留原文件；RuntimeException 清单、锁或原子发布失败；已发布的其他文件不自动回滚

调用形式：

```php
$result = setPluginApps($idx, $apps, $required);
```

定位：`plugin/interface_plugin.class.php:205`。

### setPluginOrder

功能说明：校验并在同一次注册表排他读改写内保存插件运行顺序。

只修改提交的插件，其余记录和状态保留；按order降序排列，同值保持原记录顺序。
无效输入抛异常且不会先清空注册表。调用方负责管理员身份、POST与CSRF验证。

```text
function setPluginOrder(array $orders);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$orders` | array；插件标识到0至99整数或整数字符串的字典，可为空 |

**返回值**：bool 保存成功为true；注册表缺失、读写失败或输入无效时抛异常

**异常与失败**：InvalidArgumentException 插件不存在或顺序不在允许范围；RuntimeException 注册表不存在、无法打开或无法保存；Throwable 注册表损坏或回调异常继续传递

调用形式：

```php
$result = setPluginOrder($orders);
```

定位：`plugin/interface_plugin.class.php:266`。

### buildPluginPackage

功能说明：在隔离副本中导出指定插件，供新旧后台共用且不依赖manager已启用。

在线config.php、运行数据、锁、日志和版本库信息不入包；manager主类存在时按原规则生成脱敏配置副本。
缺少manager时不生成配置副本，仍强制过滤在线配置；临时目录在请求结束时清理，下载需在此之前完成。

```text
function buildPluginPackage($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；已存在且不是符号链接的插件目录标识，不允许空标识或插件根目录 |

**返回值**：array 下载描述，file为临时归档绝对路径，name为plugin_标识.plugin

**异常与失败**：InvalidArgumentException 插件或manager路径不安全；RuntimeException 暂存目录、完整复制或归档生成失败；Throwable 脱敏配置生成或归档异常继续传递；不改写在线插件目录

调用形式：

```php
$result = buildPluginPackage($idx);
```

定位：`plugin/interface_plugin.class.php:317`。

### pluginQueryURL

功能说明：合并 URL 查询参数，保留 index.php?路由和片段，值为 null 时删除参数。

```text
function pluginQueryURL($url,$params=[]);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；原始 URL |
| `$params` | array；需要覆盖的查询参数（默认：[]） |

**返回值**：string RFC3986 编码后的地址；放入 HTML 时再转义

调用形式：

```php
$result = pluginQueryURL($url, $params);
```

定位：`plugin/interface_plugin.class.php:354`。

### regPluginRoute

功能说明：将插件 route.php 同步到编译表，并写入显式路由源映射。

使用已初始化的全局 $router；编译表分组名为 plugin_插件标识，不修改其他应用分组。

```text
function regPluginRoute($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；已存在的插件目录名 |

**返回值**：bool 同步成功为 true；插件没有 route.php 时为 false，不新增无效映射

**异常与失败**：InvalidArgumentException 插件标识/目录非法，或路由包含不可持久化对象；UnexpectedValueException 原路由源映射不是数组；LogicException 全局路由对象尚未初始化；RuntimeException 加锁或保存失败

调用形式：

```php
$result = regPluginRoute($idx);
```

定位：`plugin/interface_plugin.class.php:379`。

### removePluginRoute

功能说明：移除插件编译规则及显式源映射，目录已删除或重复清理时仍可执行。

```text
function removePluginRoute($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录名；只用于定位注册键，不读取插件目录 |

**返回值**：bool 清理完成为 true，包括目标原本不存在的情况

**异常与失败**：InvalidArgumentException 插件标识非法；UnexpectedValueException 原路由源映射不是数组；LogicException 全局路由对象尚未初始化；RuntimeException 加锁或保存失败

调用形式：

```php
$result = removePluginRoute($idx);
```

定位：`plugin/interface_plugin.class.php:393`。

### changePluginRoute

功能说明：持有源映射旁路锁，合并插件映射并调用路由器同步编译规则。

两个文件各自完整发布；不承诺跨文件事务，保存异常须交由调用方中止操作。
框架下一次 refreshRoutes 会按源映射和启用状态重建不一致的编译表。

```text
function changePluginRoute($idx, $remove = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录名 |
| `$remove` | bool；true 清理；false 登记，要求目录及 route.php 存在（默认：false） |

**返回值**：bool 成功为 true；登记时没有路由源为 false

**异常与失败**：InvalidArgumentException 插件标识/目录非法，或路由无法持久化；UnexpectedValueException 路由源映射不是数组；LogicException 全局路由对象尚未初始化；RuntimeException 加锁、读取或保存失败

**上下文变量**：myRouter $router 已初始化的全局路由器

调用形式：

```php
$result = changePluginRoute($idx, $remove);
```

定位：`plugin/interface_plugin.class.php:413`。

### savePluginFile

功能说明：先完整写入同目录临时文件，再原子替换配置/脚本，并清除对应 OPcache。

本函数只发布内容，不提供读改写锁；需要合并原文件的调用方应先持有旁路锁。

```text
function savePluginFile($file, $content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标本地文件路径；父目录不存在时创建 |
| `$content` | string；完整文件字节串，包括空串和字符串 0 |

**返回值**：bool 完整发布后返回 true

**异常与失败**：RuntimeException 无法创建目录、完整写入或原子替换目标文件

调用形式：

```php
$result = savePluginFile($file, $content);
```

定位：`plugin/interface_plugin.class.php:450`。

### setPluginTemplate

功能说明：创建外层页面模板及插件内容模板，不覆盖应用全局模板配置。

mode=true 使用框架后台 main 模板且关闭页面缓存；mode=false 复用全局
tpl_setting/tpl_cache 创建外层模板。子模板始终关闭页面缓存，防止与外层共用缓存文件。
编译目录按插件和应用隔离；未提供应用信息时使用 myStep。

```text
function setPluginTemplate($idx, $page='', $mode = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；已存在的插件目录名 |
| `$page` | string；插件 template 下的模板名（不含扩展名）；空串使用插件目录名（默认：''） |
| `$mode` | bool；true 使用框架后台外层模板；false 使用当前应用外层模板（默认：true） |

**返回值**：myTemplate[] 下标 0 为外层模板，1 为插件内容模板

**异常与失败**：InvalidArgumentException 插件、页名或模板配置非法；LogicException 复用应用模板时缺少配置，或应用标识非法；RuntimeException 外层或内容模板不存在/不可读

调用形式：

```php
$result = setPluginTemplate($idx, $page, $mode);
```

定位：`plugin/interface_plugin.class.php:478`。

### pluginPageHost

功能说明：检查应用是否提供通用插件后台布局适配器，myStep作为内置缺省后台。

仅检查应用标识及固定文件，不由请求参数指定包含路径，也不读取应用业务配置。

```text
function pluginPageHost($app);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$app` | mixed；应用目录名 |

**返回值**：bool 应用标识合法且具备插件布局适配器时为true

调用形式：

```php
$result = pluginPageHost($app);
```

定位：`plugin/interface_plugin.class.php:512`。

### pluginPageApplication

功能说明：从可信Referer识别插件页面的外层后台，缺省返回myStep。

支持三种路由、子目录、域名绑定及来源插件页的_plugin_app标记；不修改GET、REQUEST、
当前路由或会话。来源为空、不可信、应用没有通用布局适配器时使用myStep。
只决定展示，不授予登录、插件启用或业务操作权限。

```text
function pluginPageApplication($referer=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$referer` | string\|null；完整来源URL；null读取HTTP_REFERER，空串明确使用myStep（默认：null） |

**返回值**：string 通过pluginPageHost验证的展示应用名

**上下文变量**：array $domain 当前域名绑定；myConfig $ms_setting 当前配置，提供旧后台路径和缺省应用

调用形式：

```php
$result = pluginPageApplication($referer);
```

定位：`plugin/interface_plugin.class.php:530`。

### pluginPageURL

功能说明：生成保留插件后台展示标记的页面链接，兼容三种路由及原查询参数。

供插件自己的分页、筛选、表单和返回链接使用；app为空时读取当前插件展示上下文。
myStep缺省布局移除标记，不将布局偏好写入会话。只接受站内地址，不改外部链接。

```text
function pluginPageURL($url='', array $params=[], $app=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；站内URL；空串使用当前REQUEST_URI（默认：''） |
| `$params` | array；需要合并的查询参数；null值删除参数（默认：array()） |
| `$app` | string\|null；展示应用；null使用当前上下文，myStep不附加标记（默认：null） |

**返回值**：string 合并参数及有效展示标记后的链接

**异常与失败**：InvalidArgumentException 指定的展示应用未提供适配器

调用形式：

```php
$result = pluginPageURL($url, $params, $app);
```

定位：`plugin/interface_plugin.class.php:571`。

### pluginAdminURL

功能说明：生成当前展示后台中的设置、缓存等通用功能链接。

myStep使用配置的gen.path_admin，其他后台使用其应用名；参数按框架查询规则合并。
应用中另有特殊功能路由时由应用适配器或插件自身生成对应链接。

```text
function pluginAdminURL($path, array $params=[], $app=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；后台内的相对功能路径 |
| `$params` | array；查询参数（默认：array()） |
| `$app` | string\|null；展示应用；null使用当前插件上下文（默认：null） |

**返回值**：string 支持当前路由模式的站内后台URL

**异常与失败**：InvalidArgumentException 展示应用不合法

**上下文变量**：myConfig $ms_setting 当前配置

调用形式：

```php
$result = pluginAdminURL($path, $params, $app);
```

定位：`plugin/interface_plugin.class.php:594`。

### renderPluginPage

功能说明：统一装配已启用插件的后台模块及内容模板，返回主体HTML。

不重新启动插件、会话或宿主控制器。模块仍在独立函数作用域中执行，保留tpl、tpl_sub、
path_plugin、page_app以及历史全局配置别名；模块设置的content包括空串和0均原样采用。
编译按插件及展示后台隔离，内容不缓存；展示上下文在正常返回或异常时恢复。

```text
function renderPluginPage($idx, $page='', $app=null, $tpl=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录名，必须已安装且启用 |
| `$page` | string；模块及模板同名页；空串使用插件名（默认：''） |
| `$app` | string\|null；展示后台；null使用当前上下文或当前应用（默认：null） |
| `$tpl` | myTemplate\|null；供历史模块使用的外层模板；null自行创建当前外层（默认：null） |

**返回值**：string 插件内容HTML或模块显式提供的字符串

**异常与失败**：InvalidArgumentException 插件未启用、页名或展示应用不合法；RuntimeException 框架管理员身份不满足，或模板缺失；Throwable 模块及模板异常继续传递

**上下文变量**：myStep $mystep 当前控制器；myConfig $S 当前配置历史别名；myConfig $ms_setting 当前配置；array $info_app 当前真实请求及路由信息

调用形式：

```php
$result = renderPluginPage($idx, $page, $app, $tpl);
```

定位：`plugin/interface_plugin.class.php:621`。

### showPluginPage

功能说明：按当前展示后台统一输出插件管理页，保留原插件路由与业务参数。

自动模式从显式_plugin_app或可信Referer选择布局；GET首次进入其他后台时仅补展示标记，
再渲染页面，不执行模块两次。POST直接处理，保留原业务路由、会话和框架CSRF检查。
外部后台通过app/应用/plugin_layout.php提供返回myTemplate的适配回调，不为每个插件另建页面。
管理页不缓存，CSS/JS缓存按插件和布局隔离；不更改全局应用、配置或模板设置。

```text
function showPluginPage($idx, $page='', $mode=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；已启用的插件目录名 |
| `$page` | string；模块与模板的同名页；空串使用插件名（默认：''） |
| `$mode` | bool\|null；null自动选择后台；true固定myStep外层；false沿用当前外层（默认：null） |

**返回值**：void 输出管理页或在补充展示标记后重定向；未登录时交给原登录检查

**异常与失败**：InvalidArgumentException 插件、页名或展示应用不合法；LogicException 布局适配器没有返回myTemplate或不是回调；Throwable 模块、布局和模板异常继续传递

**上下文变量**：myStep $mystep 当前宿主控制器；myConfig $S 当前配置历史别名；array $info_app 当前实际请求及路由

调用形式：

```php
$result = showPluginPage($idx, $page, $mode);
```

定位：`plugin/interface_plugin.class.php:684`。

### setPriority

功能说明：将插件 priority.php 原子复制到框架优先脚本目录，覆盖旧版本。

目标为 CONFIG/priority/plugin_插件标识.php；源文件复制后 __DIR__ 会改变，
引用插件资源须使用 PLUGIN 常量。调用本函数不会立即执行优先脚本。

```text
function setPriority($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；已存在的插件目录名 |

**返回值**：bool 安装成功为 true；没有 priority.php 时为 false

**异常与失败**：InvalidArgumentException 插件标识/目录非法，或源脚本为符号链接；RuntimeException 无法读取、创建或完整发布脚本

调用形式：

```php
$result = setPriority($idx);
```

定位：`plugin/interface_plugin.class.php:738`。

### removePriority

功能说明：删除插件优先脚本并使 OPcache 失效，支持目录已删除和重复清理。

```text
function removePriority($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；插件目录名；只用于构造配置文件名，不读取插件目录 |

**返回值**：bool 删除成功或脚本原本不存在时为 true

**异常与失败**：InvalidArgumentException 插件标识非法；RuntimeException 目标异常地成为目录，或无法删除目标文件

调用形式：

```php
$result = removePriority($idx);
```

定位：`plugin/interface_plugin.class.php:755`。

