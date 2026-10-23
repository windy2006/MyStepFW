# 建立应用

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

## 1. 建立应用

目录名、`info.php` 中的 app、核心类名和核心类文件名使用同一个名称，并保持大小写一致。

```text
app/Hello/
├── Hello.class.php
├── info.php
├── config.php
├── plugin.php
├── index.php
├── module/
│   └── index.php
└── template/
    ├── main.tpl
    └── index.tpl
```

```php
// app/Hello/Hello.class.php
class Hello extends myStep
{
}
```

`info.php` 返回名称、`app => Hello`、版本及简介等元信息。浏览器访问框架生成的 Hello 入口，由根 `index.php` 调度；不直接访问 `app/Hello/index.php`。业务类、函数、模板及前端资源均在自己的应用目录，根 `lib/` 只存通用框架能力。


## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 本环节的文件与完整写法

### 2. 建立目录

先创建以下文件。`lib.php` 和 `route.php` 在后面的进阶步骤添加，其余文件构成可运行的最小页面。

```text
app/Hello/
├── Hello.class.php
├── info.php
├── config.php
├── plugin.php
├── index.php
├── module/
│   └── index.php
├── template/
│   ├── main.tpl
│   └── index.tpl
├── lib.php                 # 第 8 节添加
└── route.php               # 第 8 节添加
```

不要创建名为 `myStep` 的业务应用，也不要覆盖已有同名目录。



### 3. 描述应用并设置局部配置

#### app/Hello/info.php

```php
<?php
return [
    'name' => '我的第一个应用',
    'app' => 'Hello',
    'ver' => '1.0.0',
    'intro' => '从页面、模板到路由和 API 的入门示例',
    'copyright' => 'Hello application',
];
```

`info.php` 是框架识别应用的重要条件。只创建目录、没有这个文件，不能可靠地通过路径首段选择应用。

#### app/Hello/config.php

```php
<?php
return [
    'gen' => [
        'cache_page' => false,
    ],
    'web' => [
        'title' => 'Hello · 我的第一个应用',
        'minify' => false,
    ],
    'db' => [
        'auto' => false,
    ],
    'template' => [
        'name' => 'main',
        'path' => 'app/Hello/template/',
        'style' => '',
    ],
];
```

应用配置叠加在全局配置上，只写本应用需要覆盖的项目。`db.auto=false` 让本示例不自动连接数据库；框架会使用文件会话模式。关闭页面 HTML 缓存方便验证参数变化，但不代表关闭模板编译缓存。

#### app/Hello/plugin.php

```php
<?php
return [];
```

先不引用插件，确保先把应用自身跑通。后续可以通过后台管理或维护这个数组接入已安装、已启用的插件。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
