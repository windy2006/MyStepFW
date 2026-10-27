# 核心模块：从一个请求到页面和接口

沿原手册的四个主题展开：路由选择处理器、API提供数据、模板组织显示、控制核心协调时序。先读完整流程，再逐段核对变量和提前出口。

## 执行流程

先阅读[执行流程](runtime/execution.md)，按公共启动时序、默认页面、显式规则、API、模板输出及提前结束分支追踪请求，再查阅以下模块。

## 章节目录

| 主题 | 详细章节 | 实例 |
| --- | --- | --- |
| 路由模块 | [声明与优先级](routing/routing.md)、[格式与捕获](routing/route-formats.md)、[模块分发](routing/route-modules.md) | [Hello命名路由](../development/app/app-06.md) |
| 应用接口 | [登记](api/api.md)、[参数](api/api-arguments.md)、[策略](api/api-settings.md)、[正文](api/api-body.md)、[专属域名](api/api-domains.md) | [注册、invoke与run](api/api-example.md) |
| 页面模板 | [实例](templates/templates.md)、[变量](templates/template-values.md)、[流程块](templates/template-blocks.md)、[扩展标签](templates/template-tags.md)、[渲染](templates/template-render.md) | [主/子模板](templates/template-example.md)、[运行期标签](templates/tag-example.md) |
| 控制核心 | [完整流程](runtime/execution.md)、[初始化](runtime/bootstrap.md)、[go分流](runtime/dispatch.md)、[应用启动](runtime/application-start.md)、[输出](runtime/response-lifecycle.md) | [钩子顺序](runtime/hooks-example.md) |

## 按同一个页面追踪

```text
请求 /Hello/?name=开发者
→ index.php：版本、目录常量、myStep::init
→ go：全局设置、域名、显式路由；未命中时选择Hello默认入口
→ setPara：Hello.class.php → lib.php → preload → 资源和模板参数
→ start：服务 → 语言 → Session → CSRF → 身份恢复 → 插件 → start钩子
→ global.php → Hello/index.php → module/index.php
→ 子模板render → 主模板main → show → page钩子 → 普通end
```

这条链只表示普通页面。API预检、文件、拒绝请求、重定向或缓存命中会走自己的出口。需要身份的代码不能提前放到preload，也不能依靠global.php保护所有API。

## 最容易混淆的四组接口

- myTemplate::render返回或显示单个模板；当前控制器render补充框架上下文，供组合子页面；show用于最终页面输出。
- setFunction注册程序回调，setAddedContent追加HTML插槽；相同的start/end名称不代表同一个阶段。
- myApi::invoke返回原始数据，run编码数据；HTTP方法、CSRF和资源授权属于外层调度及业务处理器。
- 模板编译缓存保存生成的PHP，页面缓存保存生成的HTML；命中页面缓存不代表重跑页面全部查询或钩子。

各方法完整签名见[myController](reference/mycontroller.md)、[myStep](reference/mystep.md)、[myRouter](reference/myrouter.md)、[myApi](reference/myapi.md)、[myTemplate](reference/mytemplate.md)。
