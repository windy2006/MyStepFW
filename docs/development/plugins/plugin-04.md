# 前台交互和资源

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

以hello为教学标识，区分生命周期、普通宿主初始化、独立路由/API与优先脚本。

## 4. 实战：前台交互与资源

```php
// plugin/hello/index.php：在宿主控制器作用域执行。
require_once __DIR__.'/class.php';
$this->setFunction('page', ['plugin_hello', 'decorate']);
```

上例要求主类真实提供 `decorate($html)` 并返回完整 HTML。初始化只登记功能，不能每次访问都安装路由、建表或重写配置。前端 HTML 必须按上下文编码；公开 API 只返回明确白名单，不因设置表单字段是 password 就假定它会自动从响应删除。

插件资源由自己明确登记，地址兼容部署前缀，不能写固定 `/console/`、`/vendor/` 或本机域名。配置、语言异步就绪后再调用依赖它们的 JavaScript。写操作使用 POST／相应方法、CSRF 和业务授权；页面按钮不是服务端权限控制。


## 操作步骤与注意事项

index.php中的$this是当前宿主控制器；class.php声明plugin_标识。设置、语言和资源均显式加载，独立入口不自动获得管理员权限。JS初始化代码放入前端脚本，后端负责数据和授权。

## 本环节的文件与调用实例

### 5. 注册页面钩子

#### plugin/hellonote/index.php

```php
<?php
require_once __DIR__.'/class.php';
$this->setFunction('page', 'plugin_hellonote::decorate');
```

插件按注册表 order 降序初始化，应用绑定只控制范围且重复项不重复加载；同值保持注册表原顺序。初始化时登记钩子，真正的插入发生在模板渲染完成之后。`page` 钩子可拿到整页 HTML，因此不需要修改宿主模板。

| 钩子或接口 | 常见用途 | 约定 |
| --- | --- | --- |
| `setFunction('start', ...)` | 请求初始化后准备数据 | 默认无额外参数时收到当前控制器；不要随意输出 |
| `setFunction('page', ...)` | 修改整页内容 | 接收正文并返回处理后的正文 |
| `setFunction('end', ...)` | 普通页面的收尾动作 | 按逆登记顺序执行；直接退出的 API 不保证运行 |
| `setAddedContent('start'/'end', ...)` | 注入资源或页面片段 | 宿主模板需有对应 page_start/page_end 占位符 |
| `regTag()` | 自定义模板标签 | 适合局部渲染；注意模板编译与动态值的关系 |
| `regModule()` | 注册模块文件或可调用方法 | 模块名使用插件前缀，避免覆盖其他扩展 |

一个插件通常不需要用完所有能力。本例只用 page 钩子，便于先理解插件生命周期。

## 页面、资源与宿主的关系

普通index.php在宿主控制器上下文执行，`$this`是宿主，不是plugin_类。独立route.php只声明路由，实际业务函数放入lib.php或class.php明确加载；不能依赖普通index已经运行。

```php
// plugin/hello/index.php：资源位于自己的插件目录。
$this->addCSS(PLUGIN.'hello/asset/style.css');
$this->addJS(PLUGIN.'hello/asset/function.js');
```

后端只登记资源及受信数据。浏览器事件、AJAX和checkForm的具体JS放入自己的脚本，参见[浏览器前端](../../frontend/README.md)。设置管理页面优先调用showPluginPage，按宿主上下文选择适配模板；Referer仅可辅助选择外观，不能决定管理员权限。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
