# 加载提示、窗口、滚动与复制

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 5. 加载提示、窗口与锚点

| 函数 | 参数与功能 | 返回值／说明 |
| --- | --- | --- |
| `loadingShow(info, show)` | info 为纯文本提示，null 用语言包或默认值；show 为 true 显示、false 隐藏，省略则切换 | boolean 当前显示状态；管理遮罩和 body overflow，依赖现有基础样式及本地 loading.gif |
| `openWindow(url, width, height)` | 打开名为 new_window 的浏览器窗口，宽高单位像素 | Window 或 null；受浏览器弹窗策略约束 |
| `openDialog(url, width, height, mode)` | 尝试旧版对话框；mode true 为模态、false 为非模态，不支持时回退 openWindow | 浏览器的窗口／旧对话框结果或 null |
| `gotoAnchor(theAnchor='')` | 按 id 或旧式 a.name 滚动；允许带 #，空串回顶部 | 始终 false，方便链接处理；没有目标或已有动画时不重复启动 |
| `setLocation(path, name='')` | 调用 history.pushState，记录 title／url 状态 | undefined；不导航、不刷新页面，也不更新 document.title |
| `sleep(ms=0)` | 非阻塞等待，毫秒；负数按 0；非有限值或超过 2147483647 被拒绝 | Promise<void>，调用方需 await／then |

```js
async function updatePreview() {
    loadingShow('正在更新预览……', true);
    try {
        await sleep(100);
        // 在此执行自己的异步预览工作。
    } finally {
        loadingShow(null, false);
    }
}

const previewButton = $id('preview-button');
if (previewButton) previewButton.addEventListener('click', function () {
    openWindow('/preview/', 900, 640); // 示例根路径，实际业务地址由框架生成。
});
// gotoAnchor('chapter-2');
// setLocation(queryURL(location.href, {tab: 'preview'}), '预览');
```

openDialog 是兼容入口，不提供现代页面模态组件的行为保证。setLocation 仅改变历史状态，不会执行服务端路由；需要新的页面内容时仍应进行导航或主动加载。


## 6. 复制文本

| 函数 | 参数与功能 | 返回值 |
| --- | --- | --- |
| `copyValue(obj)` | 辅助解析待复制内容：普通文本直接返回；#id／.class／元素／jQuery 对象按 data-copy、非空 val、text 顺序取值 | string 或 null；通常由 copy／copyAsync 内部调用 |
| `copy(obj)` | 同步复制，使用 execCommand，恢复原焦点和选区 | boolean，权限不足或目标不存在时 false |
| `copyAsync(obj)` | 优先 Clipboard API，被拒绝或不可用时回退 copy | Promise<boolean> |

```html
<span id="share-address" data-copy="https://example.test/">复制地址</span>
<button type="button" id="copy-address">复制</button>
```

```js
const copyButton = $id('copy-address');
if (copyButton) copyButton.addEventListener('click', async function () {
    const ok = await copyAsync('#share-address');
    if (!ok) alert('未能复制，请手动选择文字。');
});
// copy('普通文字');
```

以 # 或 . 开头的字符串会被解释为选择器；要复制这种字面文本，可放在元素的 data-copy 中，再传元素。浏览器权限、HTTPS 和用户手势都会影响结果，应检查返回值。


## 明确调用示例

```javascript
loadingShow('正在处理', true);
Promise.resolve().then(function () { loadingShow('', false); });
// 复制应在用户手势中调用，并处理异步失败。
const copyButton = $id('copy-button');
if (copyButton) copyButton.addEventListener('click', function () { copyAsync('Hello').catch(console.error); });
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
