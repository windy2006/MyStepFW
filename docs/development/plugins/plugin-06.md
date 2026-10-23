# 钩子调用

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

以hello为教学标识，区分生命周期、普通宿主初始化、独立路由/API与优先脚本。

## 6. 实战：钩子调用

```php
// class.php中的类；只声明，不在此执行安装。
class plugin_hello_page {
public static function decorate($html)
{
    $note = '<aside class="hello-note">来自插件的提示</aside>';
    $position = strripos($html, '</body>');
    return $position === false
        ? $html.$note
        : substr_replace($html, $note, $position, 0);
}
}
```

这里展示的是页面钩子的类方法，不是可独立执行的完整 PHP 文件。动态消息需 HTML 转义，不能将插件配置或请求值直接拼入标签、属性或脚本。

普通 start／end 钩子通常接收当前控制器，page 管道接收并返回 HTML；自定义钩子按实际 `run()` 调用签名处理。回调登记顺序与插件 order 是相关但不同的层次；数组参数在管道中按位置展开，不自动绑定命名参数。

`end` 不覆盖所有 API、文件及跳转分支。临时文件、备份连接和事务应由当前操作的明确清理路径负责，不能依赖全站最后一定执行一个钩子。


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

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
