# 安装卸载

## 本篇目标与调用场景

以hello为教学标识，区分生命周期、普通宿主初始化、独立路由/API与优先脚本。

## 3. 实战：安装与卸载

```php
class plugin_hello implements interface_plugin
{
    public static function check(&$result)
    {
        $result = '此示例无额外扩展依赖。';
        return true;
    }
    public static function install()
    {
        // 按需登记路由、菜单、优先脚本；检查每一步结果。
    }
    public static function uninstall()
    {
        // 对应移除本插件登记；按明确策略处理保留数据。
    }
}
```

`check()` 应可在管理页安全重复执行，不能承担安装写入。`install()`／`uninstall()` 是生命周期接口，不是自动执行同名根脚本。安装失败应抛异常／反馈失败，并允许检查与重试，不能只根据“没有输出错误”判定成功。

| 辅助入口 | 用途 |
| --- | --- |
| `regPluginRoute()`／`removePluginRoute()` | 登记／清理受管路由映射，源声明仍在本插件 |
| `addPluginLink()`／`removePluginLink()` | 管理相关后台菜单登记 |
| `setPluginApps()`／`setPluginOrder()` | 维护绑定及运行顺序，保留规定的必需绑定 |
| `setPriority()`／`removePriority()` | 安装／移除前置脚本 |
| `savePluginFile()` | 在相应受信流程原子发布文件，不代替鉴权 |

安装要写的目录、替换目标和删除所需父目录权限均需检查；当前框架的安装与更新流程已有判断和反馈。多文件更新不等于完整事务，插件仍需安排部分失败和清理逻辑。卸载时只处理本插件资源，不误删其他应用／插件配置。

## 操作步骤与注意事项

index.php中的$this是当前宿主控制器；class.php声明plugin_标识。设置、语言和资源均显式加载，独立入口不自动获得管理员权限。JS初始化代码放入前端脚本，后端负责数据和授权。

## 主要方法：参数、返回和失败约定

### interface_plugin::check

功能说明：检查运行环境、扩展及依赖，将检查结果写入引用参数。

```text
public static function check(&$result);
```

| 参数说明 |
| --- |
| string &$result 供后台展示的检查说明；实现方可设置默认空串 |

**返回**：bool 满足安装条件为 true，否则为 false

### interface_plugin::install

功能说明：初始化插件资源，登记路由、菜单和所需的优先脚本。

参数说明：无显式参数。

```text
public static function install();
```

**返回**：bool|null 支持无返回值实现；管理器不以返回值判定成功，失败须抛异常

**异常**：Exception 安装失败时由实现方或辅助函数抛出

### interface_plugin::uninstall

功能说明：清理插件注册信息及安装资源，数据保留策略由插件实现决定。

参数说明：无显式参数。

```text
public static function uninstall();
```

**返回**：bool|null 支持无返回值实现；管理器不以返回值判定成功，失败须抛异常

**异常**：Exception 卸载失败时由实现方或辅助函数抛出

## 本环节的文件与调用实例

### 4. 实现生命周期和业务方法

#### plugin/hellonote/class.php

```php
<?php
class plugin_hellonote implements interface_plugin
{
    public static function check(&$result = '')
    {
        $ready = is_writable(CONFIG) && is_writable(CONFIG.'priority');
        $result = $ready ? '配置目录和 priority 目录可写。' : '请检查配置目录和 priority 目录权限。';
        return $ready;
    }

    public static function install()
    {
        if (!setPriority('hellonote')) throw new RuntimeException('Missing plugin priority script.');
        if (!regPluginRoute('hellonote')) throw new RuntimeException('Missing plugin routes.');
    }

    public static function uninstall()
    {
        removePluginRoute('hellonote');
        removePriority('hellonote');
        // 保留配置，避免卸载时误删用户填写的数据。
    }

    public static function active()
    {
        $plugins = myStep::activePlugins();
        return isset($plugins['hellonote']);
    }

    public static function message()
    {
        $config = include __DIR__.'/config.php';
        $message = $config['message'] ?? '';
        return is_string($message) ? mb_substr($message, 0, 200, 'UTF-8') : '';
    }

    public static function decorate($html)
    {
        if (!self::active()) {
            return $html;
        }
        $message = htmlspecialchars(self::message(), ENT_QUOTES, 'UTF-8');
        $note = '<aside class="hellonote" role="note">'.$message.'</aside>';
        $position = strripos($html, '</body>');
        return $position === false
            ? $html.$note
            : substr_replace($html, $note, $position, 0);
    }

    public static function page($path = '')
    {
        header('Content-Type: text/plain; charset=utf-8');
        if (!self::active()) {
            http_response_code(404);
            echo '插件未启用';
            return;
        }
        echo self::message();
    }

    public static function status(...$unused)
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!self::active()) {
            http_response_code(404);
            return ['error' => 'Plugin is disabled'];
        }
        return ['ok' => true, 'plugin' => 'hellonote', 'message' => self::message()];
    }
}
```

`check()` 供后台展示安装前条件检查，成功返回 true，说明通过引用参数返回。安装前应存在可写的 `config/priority/` 目录；缺失时创建目录并赋予 PHP 所需权限。

`install()`、`uninstall()` 是显式管理动作，不在每个页面请求时执行。本例只登记和移除自己的路由、优先级文件，不删除全站缓存或其他插件数据。真正需要建表的插件应在安装/迁移中处理，并为重复执行设计明确行为。

`decorate()` 返回 HTML，而不是 echo；这样页面钩子链才能继续传递处理后的正文。纯文本页面不按 HTML 输出，API 返回的是数据，不在方法中手工拼 JSON。

### 8. 安装与启用

1. 先确认 Hello 应用可访问。
2. 将以上文件保存到 `plugin/hellonote/`，用 PHP 7.4 对 PHP 文件做语法检查。
3. 使用管理员身份打开框架后台，进入插件管理。其逻辑地址是 `/{gen.path_admin}/function/plugin/`；如后台有独立域名，使用对应地址。
4. 在未安装列表找到“页面问候提示”，查看检查结果。
5. 保留 Hello 应用关联，填写提示文字并提交安装。
6. 确认插件出现在已安装列表且处于启用状态。
7. 确认 `app/Hello/plugin.php` 数组包含 `hellonote`。

应用关联文件可能已经包含其他插件，维护时合并数组，不要覆盖成只有本插件。后台通常会代为保存关联。

安装完成后，应看到注册库记录、应用关联、`config/priority/plugin_hellonote.php` 及显式路由映射。它们是框架生成的运行状态，不随插件源码一起手工伪造。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
