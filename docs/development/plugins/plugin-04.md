# 前台交互

说明插件如何登记并执行页面处理，不在初始化脚本中重复安装或改写配置。

## 前台交互和资源

### 4. 实战：前台交互与资源

```php
// plugin/hello/index.php：在宿主控制器作用域执行。
require_once __DIR__.'/class.php';
$this->setFunction('page', ['plugin_hello', 'decorate']);
```

上例要求主类真实提供 `decorate($html)` 并返回完整 HTML。初始化只登记功能，不能每次访问都安装路由、建表或重写配置。前端 HTML 必须按上下文编码；公开 API 只返回明确白名单，不因设置表单字段是 password 就假定它会自动从响应删除。

### 本环节的文件与调用实例

#### 5. 注册页面钩子

##### plugin/hellonote/index.php

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

[插件规范](README.md)
