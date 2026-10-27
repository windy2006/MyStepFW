# 组成结构和命名

## 本篇目标与调用场景

以hello为教学标识，区分生命周期、普通宿主初始化、独立路由/API与优先脚本。

## 2. 组成结构和命名对应

```text
plugin/hello/
├── info.php                 # idx与目录一致，显示名称可为中文
├── class.php                # plugin_hello implements interface_plugin
├── index.php                # 普通宿主初始化和钩子登记
├── config.php               # 可选，实际插件设置
├── route.php                # 可选，独立路由与API声明
├── priority.php             # 可选，安装时复制的前置脚本源
├── config/                  # 可选，设置编辑结构及说明语言
├── language/                # 可选，界面语言
├── module/                  # 按功能处理
└── template/                # 对应功能模板
```

应用核心文件为同名 `.class.php`，插件主类文件固定为 `class.php`；类名是 `plugin_标识`。`info.php` 的 idx、目录、类名后缀、绑定值、API 分组及打包标识保持一致。支持已有注册表时检查表字段长度，不仅检查文件系统允许的名称长度。

配置文件存在不代表自动合并进 `$ms_setting`；语言包存在也不代表自动加载。模块和模板应按同一功能配对，注册处理器、后台展示和宿主布局分别明确。

## 操作步骤与注意事项

index.php中的$this是当前宿主控制器；class.php声明plugin_标识。设置、语言和资源均显式加载，独立入口不自动获得管理员权限。JS初始化代码放入前端脚本，后端负责数据和授权。

## 本环节的文件与调用实例

### 2. 目录与命名

```text
plugin/hellonote/
├── info.php
├── class.php
├── index.php
├── config.php
├── priority.php
├── route.php
└── config/
    ├── construction.php
    ├── default.php
    └── chs.php
```

插件目录及 idx 为 `hellonote`，生命周期类名固定为 `plugin_hellonote`。插件标识须符合注册表idx列的长度限制；本例使用不超过10个ASCII字符的标识。结构和现状说明见 [插件目录说明](../../../plugin/README.md)。

`class.php` 不是继承 `myStep` 的第二个控制器，而是实现 `interface_plugin` 的插件类。运行期 `index.php` 被包含到控制器方法作用域中，里面的 `$this` 才是当前框架控制器。

### 3. 插件信息与运行配置

#### plugin/hellonote/info.php

```php
<?php
return [
    'name' => '页面问候提示',
    'idx' => 'hellonote',
    'ver' => '1.0.0',
    'intro' => '展示插件配置、页面钩子与独立接口',
    'copyright' => 'HelloNote plugin',
    'description' => '给 Hello 应用加入一条提示信息。',
    'app' => ['Hello'],
];
```

`app` 是后台安装/设置页的默认应用关联声明，不是接口访问权限白名单。不要在 `info.php` 中写入文件、建表或输出正文，后台扫描列表也会加载它。

#### plugin/hellonote/config.php

```php
<?php
return [
    'message' => '这是我的第一个 MyStepFW 插件。',
];
```

运行配置由插件自己读取，不会自动合并到 `$ms_setting`。配置值仍需校验及输出转义。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
