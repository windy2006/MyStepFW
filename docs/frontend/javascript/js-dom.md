# 元素查找

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 1. DOM 与类型判断

| 函数 | 参数及功能 | 返回值 |
| --- | --- | --- |
| `getPath(lvl=0)` | 取得当前协议、主机、端口和前 lvl 层目录；lvl 向下取整，负数按 0 | string；0 为来源地址，不含查询串 |
| `$id(id)` | 按元素 id 查找 | Element 或 null |
| `$name(name, idx)` | 按 name 查找；idx 可省略、传数字／数字字符串、first、last | 不传 idx 返回 NodeList；传 idx 返回元素或 undefined |
| `$tag(name, theOLE=document)` | 在指定 Document／Element 内按标签名查找 | HTMLCollection |
| `$class(name, context=document)` | 在指定 Document／Element 内按 CSS 类名查找 | HTMLCollection |
| `isArray(para)` | Array.isArray 的兼容入口，支持跨窗口数组 | boolean |

```js
const panel = $id('profile');
if (panel) {
    const firstInput = $tag('input', panel)[0];
    const hints = $class('field-help', panel);
    console.log(firstInput, hints.length);
}
const selected = $name('category', 'first');
console.log(selected, isArray([1, 2]));
console.log(getPath(0)); // 例如 https://example.test:8443
```

$id 返回原生元素；需要 jQuery 方法时再用 `$(element)` 包装。$name 不会自动把带方括号的 PHP 字段名解析成对象。

## 明确调用示例

```javascript
const input = $id('title');
if (input) input.value = 'Hello';
const first = $name('topics[]', 'first');
console.assert(isArray([1, 2]));
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
