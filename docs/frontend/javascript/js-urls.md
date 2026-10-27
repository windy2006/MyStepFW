# 路由地址、参数和批量链接

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 完整框架页面在脚本前预置公开设置和语言；独立页面用checkNrun等待 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 4. 路由与查询参数

| 函数 | 功能及参数 | 返回值 |
| --- | --- | --- |
| `routeURL(url, prefix=global.root_fix)` | 把应用／模块相对路径与 prefix 合成当前路由地址；结合 setting.url_prefix、url_fix 等归一化 | string；配置缺失时保留原url，不猜测路由。需要转换时先等待配置就绪。完整协议地址、以 `/` 开头的地址和 `#` 锚点原样保留 |
| `queryURL(url, params={})` | 只合并查询参数并保留锚点、index.php 的首段路由；null／undefined 删除参数；数组生成同名重复参数 | string；使用 URLSearchParams 编码，空格通常为 + |
| `setURL(prefix=global.root_fix, context=document.body)` | 批量调整容器内 a.href、form.action、img.src；带 keep-url 的元素跳过；为查询串路由 GET 表单登记适配 | 已完成返回 true；配置未就绪时安排重试并返回 undefined |

示例假设当前应用为 Hello，未绑定固定应用域名前缀，网站安装在 `/sub/`：

```js
checkNrun(function () {
    const target = routeURL('article/list/', 'Hello/');
    const url = queryURL(target, {cat_id: 3, keyword: 'A+B 中文', page: 1});
    const link = $id('article-list');
    if (link) link.href = url;
});
```

同一个逻辑路由在三种模式下的形态：

| 模式 | routeURL 的路径部分 | 增加参数后的示意 |
| --- | --- | --- |
| rewrite | `/sub/Hello/article/list/` | `/sub/Hello/article/list/?cat_id=3` |
| path_info | `/sub/index.php/Hello/article/list/` | `/sub/index.php/Hello/article/list/?cat_id=3` |
| query_string | `/sub/index.php?Hello/article/list/` | `/sub/index.php?Hello/article/list/&cat_id=3` |

```js
queryURL('/sub/index.php?Hello/article/list/&page=1#list', {
    page: 2, tags: ['php', 'js'], obsolete: null
});
// /sub/index.php?Hello/article/list/&page=2&tags=php&tags=js#list
```

不要手工写 `article/add&cat_id=3`，因为 rewrite／path_info 需要 `?`，query_string 的首段路由之后才用 `&`。重复数组参数在 PHP 后端是否应使用 `tags[]`，取决于接收接口的字段约定；queryURL 不自动增加方括号。

```html
<div id="article-tools">
    <a href="article/add/">添加文章</a>
    <a href="https://example.test/help" keep-url>独立帮助地址</a>
</div>
```

```js
checkNrun(function () {
    setURL('Hello/', $id('article-tools'));
});
```

prefix 应包含需要的尾部分隔符，函数按 prefix + url 组合，不会替任意业务前缀补斜杠。显式 `''` 表示不给 url 添加模块路径；使用时需确认当前路由含义。根路径 `/article/` 会原样保留，不会自动补应用。

setURL 会修改整个指定容器的链接、表单和图片；动态插入的内容需在插入后再次调用，建议限定容器。GET 表单适配尊重取消事件和提交按钮的 formmethod／formaction／formtarget，非查询串路由不额外接管导航。

## 明确调用示例

```javascript
const url = queryURL('/sub/index.php?/Hello/detail&id=1#top', {id: 7, keyword: 'A&B'});
console.log(url); // 路由首段保留，业务值被正确编码。
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
