# 框架路由与API

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 6. 框架路由与 API

默认页面、显式规则、注册模块和 HTTP API 是不同入口。页面应用可以先只开放首页，再在 `route.php` 添加命名处理器；路由声明保持无输出、无写入，处理器代码在当前应用准备阶段加载。

API 按 `$api = ['status' => 'app\Hello\status'];` 登记，只能调用明确注册目标。API 的启动和参数规则见[核心模块](../../backend/runtime/execution.md)与[API 专题](../../backend/runtime/execution.md)。不要把一个会输出 HTML 的页面处理函数直接当 JSON API，也不要把完整配置对象作为接口返回值。

接口需用户身份时在处理器检查；需要框架管理员时可叠加“仅限后台”。后台开关不替代资源归属和字段验证，普通应用的账号也不能自动等同于框架管理员。


## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 本环节的文件与完整写法

### 8. 添加自定义页面和 API

现在增加两个文件；最小页面中的其他文件保持原样。

#### app/Hello/route.php

```php
<?php
$rule = [
    ['/Hello/hi/[str]', 'app\\Hello\\greet'],
];

$api = [
    'ping' => 'app\\Hello\\ping',
];
```

#### app/Hello/lib.php

```php
<?php
namespace app\Hello;

function greet($name = 'World')
{
    global $mystep, $tpl_setting;

    $name = trim($name, '/');
    $tpl = new \myTemplate($tpl_setting, false);
    $subSetting = $tpl_setting;
    $subSetting['name'] = 'index';
    $sub = new \myTemplate($subSetting, false);
    $sub->assign('greeting', htmlspecialchars('你好，'.$name.'！', ENT_QUOTES, 'UTF-8'));
    $sub->assign('description', '这个页面来自自定义路由。');
    $tpl->assign('main', $mystep->render($sub));
    $mystep->show($tpl);
    // 路由处理器正常返回，由外层 go() 调用 end()。
}

function ping(...$unused)
{
    // 当前 API 层的默认头不一定是 JSON，明确设置。
    header('Content-Type: application/json; charset=utf-8');
    return ['ok' => true, 'app' => 'Hello', 'message' => 'pong'];
}
```

`lib.php` 在完整初始化阶段加载，供路由回调和页面复用。不要在这里直接 `echo` 调试信息，它也会影响 API 输出。

| 请求 | 预期 |
| --- | --- |
| `/Hello/hi/Alice` | 自定义页面显示“你好，Alice！” |
| `/api/Hello/ping/json` | JSON 对象，包含 `ok: true` 和 `message: pong` |
| `/api/Hello/ping/json?probe=1` | 本示例忽略额外参数，仍返回 pong |

`[str]` 默认匹配 `\w+`，不是任意名称；这个 URL 示例使用 ASCII 名称。中文名称使用前面首页的查询参数示例即可。

自定义规则优先于应用默认入口，所以 `/Hello/hi/Alice` 不会被首页模块白名单拒绝。API 规则由框架分发，`$api` 的键是方法名，分组名自动来自应用目录。

当前 API 实现会把 GET/POST 与路径值按位置传给方法，部分形式还保留格式参数；这里使用可变参数且不消费外部数据，避免初学阶段混淆。业务 API 应明确校验参数类型、数量、方法和授权，返回所需字段，不要返回整个配置对象。

路由源会被编译到 `config/route.php`。只改自己的 `route.php`，不要把生成文件作为开发入口。路由源不应执行有副作用的初始化代码，因为它不保证每次请求都被包含。



### 9. 为敏感页面加权限中间件

这一步是可选扩展。在 `route.php` 的 `$rule` 数组追加：

```php
$rule[] = ['/Hello/private/[str]', ['app\\Hello\\requireAdmin', 'app\\Hello\\privatePage']];
```

在 `lib.php` 的同一命名空间中追加：

```php
function requireAdmin($previous = true)
{
    if (\myReq::session('sysop') !== 'y') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo '需要管理员身份';
        return false;
    }
    return true;
}

function privatePage($path, array $results)
{
    header('Content-Type: text/plain; charset=utf-8');
    echo '管理员页面：'.trim($path, '/');
}
```

匿名访问 `/Hello/private/demo` 应得到 403。这只是框架管理员身份示例，不是完整角色权限系统。业务权限要按自己的用户、角色和资源规则处理。

流水线最后一项接收的是“捕获值拼成的路径”和“中间件结果数组”，与单处理器逐个接收捕获值不同。返回严格 `false` 会阻止最终处理器，但不会自动补正文或状态，因此中间件需自行输出拒绝响应。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
