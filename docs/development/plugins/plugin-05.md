# API与模板模块

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

以hello为教学标识，区分生命周期、普通宿主初始化、独立路由/API与优先脚本。

## 5. 实战：接口、模块与模板

`route.php` 使用命名回调声明独立功能及 API，API 范围是 `plugin_hello`。路由汇总时检查活动状态；执行时按需加载标准 `class.php`，不要依靠声明文件的 include 顺便完成每次请求初始化。

插件 API 不必先执行宿主的普通 `index.php`。需要共用方法直接放在插件主类／辅助类；不要让公开接口偷偷初始化整个后台或重新连接宿主数据库。

后台页面复用 `showPluginPage()`：进行框架管理员检查，加载插件模板及可选模块，再与宿主布局组合。`setPluginTemplate()` 等辅助函数负责模板装配，不表示调用者天然有写入权限。功能模块可通过 `$tpl_sub` 赋值，或按约定提供 `$content`。

API 在“API 管理”中只有启用、允许方法、允许跨域、仅限后台四项额外设置；参数统一沿用兼容位置模式，没有逐接口格式限制。插件停用／移除后保留的历史设置只能清理，不能在这里重新注册可执行函数。详见[API 专题](../../backend/runtime/execution.md)。


## 操作步骤与注意事项

index.php中的$this是当前宿主控制器；class.php声明plugin_标识。设置、语言和资源均显式加载，独立入口不自动获得管理员权限。JS初始化代码放入前端脚本，后端负责数据和授权。

## 主要方法：参数、返回和失败约定

### regPluginRoute

功能说明：将插件 route.php 同步到编译表，并写入显式路由源映射。

使用已初始化的全局 $router；编译表分组名为 plugin_插件标识，不修改其他应用分组。

```text
function regPluginRoute($idx);
```

| 参数说明 |
| --- |
| string $idx 已存在的插件目录名 |

**返回**：bool 同步成功为 true；插件没有 route.php 时为 false，不新增无效映射

**异常**：InvalidArgumentException 插件标识/目录非法，或路由包含不可持久化对象；UnexpectedValueException 原路由源映射不是数组；LogicException 全局路由对象尚未初始化；RuntimeException 加锁或保存失败

### removePluginRoute

功能说明：移除插件编译规则及显式源映射，目录已删除或重复清理时仍可执行。

```text
function removePluginRoute($idx);
```

| 参数说明 |
| --- |
| string $idx 插件目录名；只用于定位注册键，不读取插件目录 |

**返回**：bool 清理完成为 true，包括目标原本不存在的情况

**异常**：InvalidArgumentException 插件标识非法；UnexpectedValueException 原路由源映射不是数组；LogicException 全局路由对象尚未初始化；RuntimeException 加锁或保存失败

## 本环节的文件与调用实例

### 6. 为独立页面和 API 准备类加载

#### plugin/hellonote/priority.php

```php
<?php
require_once PLUGIN.'hellonote/class.php';
```

安装时 `setPriority()` 将这个文件复制到 `config/priority/plugin_hellonote.php`。插件处于活动状态时，请求前置阶段按注册表顺序加载它，使独立路由处理器在没有运行页面插件 `index.php` 时仍然可调用。标识含下划线时保持原样。

此处必须用 `PLUGIN` 定位源文件，不能用 `__DIR__.'/class.php'`：复制后 `__DIR__` 已经变为配置目录。这里只加载类定义，不输出内容、不执行页面钩子、不连接数据库。

禁用插件不会自动删除这个优先级文件，所以独立方法还需检查活动状态。卸载时本例主动移除它。

#### plugin/hellonote/route.php

```php
<?php
$rule = [
    ['/hellonote/[any]', 'plugin_hellonote::page'],
];
$api = [
    'status' => 'plugin_hellonote::status',
];
```

路由源只声明规则，不应依赖它每次请求都加载类文件。源文件会编译为注册表，后续请求可能只加载编译结果。

`regPluginRoute()` 既更新编译表，也维护显式映射；`removePluginRoute()` 移除两处。使用这些框架辅助方法，不直接拼写编译表内容。

路由和优先脚本辅助方法的返回及异常约定：

| 方法 | 返回值及用途 |
| --- | --- |
| `regPluginRoute($idx)` | 同步成功返回 `true`；没有 `route.php` 返回 `false`，不登记失效路径。 |
| `removePluginRoute($idx)` | 清理完成返回 `true`，重复调用或插件目录已删除仍可清理。 |
| `setPriority($idx)` | 完整发布脚本返回 `true`；没有 `priority.php` 返回 `false`。 |
| `removePriority($idx)` | 删除成功或原文件不存在均返回 `true`，不递归删除意外同名目录。 |

路由操作需要框架已初始化全局 `$router`。源映射使用旁路锁合并，避免同时安装插件时覆盖彼此；菜单、源映射和优先脚本均先写临时文件再原子替换。非法标识、损坏的映射、加锁和写入失败会抛出异常。接口 `install()`/`uninstall()` 保留旧式无返回值写法，管理器不检查它们的返回值；必需资源失败时应抛出异常。各文件原子发布不代表整个插件安装具备事务回滚。

后台插件页使用 `showPluginPage($idx, $page)`，先验证登录，再加载 `template/$page.tpl` 及可选的 `module/$page.php`；省略页名时使用插件目录名。模块可通过 `$tpl_sub` 赋值，或设置 `$content` 直接提供主体内容。

若需将插件内容嵌入当前应用，可调用：

```php
list($outer, $inner) = setPluginTemplate('hellonote', 'hellonote', false);
$inner->assign('message', 'Hello');
$outer->assign('main', $mystep->render($inner));
$mystep->show($outer);
```

第三个参数 `false` 复用应用的全局 `$tpl_setting`/`$tpl_cache` 创建外层模板，调用后不修改这两个全局变量；默认 `true` 使用框架后台 `main.tpl`。内容子模板始终关闭页面缓存，防止覆盖外层的整页缓存；外层启用缓存时仍需自行按身份、语言等设置隔离维度。


插件 API 分组会变为 `plugin_hellonote`，访问路径为 `/api/plugin_hellonote/status/json`。它不会自动继承应用的权限策略。示例的只读公开信息可以匿名查看；敏感 API 需要在自身方法中验证身份、权限和请求方法。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
