# 插件前置

说明priority脚本的登记、复制位置、早期执行条件与清理方式。

## 前置脚本登记

插件安装时显式调用 `setPriority('hello')`，将自身 `priority.php` 安装到 `config/priority/plugin_hello.php`。运行时 `go()` 在常规应用、会话及认证准备前，检查来源、元信息、注册状态后执行。badReq 的早期检查使用这条机制，不能改成普通 page／start 钩子而声称优先级相同。

复制后脚本的 `__DIR__` 指向配置目录，访问原插件应使用 `PLUGIN.'hello/'`。此阶段不假定 `$this`、当前应用 `PATH` 或登录信息已就绪。前置脚本来源是受信代码，不作为执行用户提交 PHP 的入口。

### 主要方法：参数、返回和失败约定

#### setPriority

功能说明：将插件 priority.php 原子复制到框架优先脚本目录，替换同名目标脚本。

目标为 CONFIG/priority/plugin_插件标识.php；源文件复制后 `__DIR__` 会改变，
引用插件资源须使用 PLUGIN 常量。调用本函数不会立即执行优先脚本。

```php
function setPriority($idx);
```

| 参数说明 |
| --- |
| string $idx 已存在的插件目录名 |

**返回**：bool 安装成功为 true；没有 priority.php 时为 false

**异常**：InvalidArgumentException 插件标识/目录非法，或源脚本为符号链接；RuntimeException 无法读取、创建或完整发布脚本

#### removePriority

功能说明：删除插件优先脚本并使 OPcache 失效，支持目录已删除和重复清理。

```php
function removePriority($idx);
```

| 参数说明 |
| --- |
| string $idx 插件目录名；只用于构造配置文件名，不读取插件目录 |

**返回**：bool 删除成功或脚本原本不存在时为 true

**异常**：InvalidArgumentException 插件标识非法；RuntimeException 目标异常地成为目录，或无法删除目标文件

### 两种顺序机制和执行前提

| 机制 | 运行阶段 | 上下文 | 适合工作 |
| --- | --- | --- | --- |
| plugin.db的order | 普通应用插件初始化 | 当前宿主已经选定 | 登记钩子、模块和页面资源 |
| config/priority中的优先脚本 | go较早阶段 | 尚无普通应用会话、模板或数据库保证 | 轻量请求预检／拦截 |

```php
// 管理员安装流程中的辅助调用；登记的源文件必须真实存在。
if (!setPriority('hello')) {
    throw new RuntimeException('优先脚本登记失败');
}
// 卸载时清理对应登记，而不是删除其他插件的优先项。
removePriority('hello');
```

实际setPriority的参数及返回契约见插件共享接口；安装文件应检查它是否成功，不把注册普通插件当成优先脚本已安装。不要在优先脚本连接业务数据库、读取管理员Session或构造页面。

[插件规范](README.md)
