# 核心扩展

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 4. 核心扩展

将本应用公共控制能力放在 `Hello extends myStep` 中；复杂业务可拆为本应用 `HelloService.class.php`，由核心显式 `require_once`。类名自动加载并不等于完整的 PSR-4 命名空间目录发现，不要假定框架会递归扫描所有 app。

应用函数可使用 `namespace app\Hello;`，与核心全局类名区分。生命周期扩展需明确调用时机；没有必要时不要覆写 `start()` 或 `end()`。确需覆写时保持父类支持签名及框架必要行为。

配置、数据库、缓存、语言、资源、钩子和模板由既有机制提供。不要在应用类构造中重跑 `myStep::init()`、另起会话或创建第二套全局配置。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 本环节的文件与完整写法

### 4. 编写核心类和页面入口

#### app/Hello/Hello.class.php

```php
<?php
/**
 * 功能说明：Hello 应用核心，继承框架资源和生命周期功能。
 * 使用说明：由框架按应用名自动加载和实例化。
 */
class Hello extends myStep
{
}
```

目录名、应用标识、核心类名和核心类文件名必须一致。即使当前没有扩展方法，也保留应用核心类；框架会把它实例化为当前请求的 `$mystep`。应用专属辅助类按需从本应用目录明确引入，不为自动加载而放进公用 `lib/`。

#### app/Hello/index.php

```php
<?php
$module = $info_app['path'][0] ?? 'index';
if ($module === '') {
    $module = 'index';
}

// 第一版只开放首页；不把任意 URL 片段直接拼成 include 路径。
$allowed = ['index'];
if (!in_array($module, $allowed, true) || count($info_app['path']) > 1) {
    myStep::header('404');
}

$tpl = new myTemplate($tpl_setting, false);
$subSetting = $tpl_setting;
$subSetting['name'] = $module;
$tpl_sub = new myTemplate($subSetting, false);

require __DIR__.'/module/'.$module.'.php';
$content = $mystep->render($tpl_sub);
```

这里故意不调用 `show()` 和 `end()`：默认应用入口执行完毕后，外层 `go()` 会把 `$content` 赋给主模板的 `main`、显示页面并结束请求。入口重复显示一次，会造成重复输出。

模块白名单和路径段数检查可防止未知页面悄悄落入首页，也避免将用户输入用于任意文件包含。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
