# 加载提示

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

`loadingShow(info, show)`管理遮罩和body滚动状态，返回当前是否显示的布尔值。info是纯文本；null使用语言包或默认提示。show为true显示、false隐藏，省略时切换。

依赖现有基础样式及本地loading.gif。应用完成当前异步工作后显式隐藏，避免把遮罩一直留在页面上。

## 明确示例

```javascript
async function previewArticle() {
    loadingShow('正在生成预览……', true);
    try {
        await sleep(100); // 非阻塞等待；实际业务在此等待自己的请求。
    } finally {
        loadingShow(null, false);
    }
}
const previewButton = $id('preview-button');
if (previewButton) previewButton.addEventListener('click', previewArticle);
```

sleep返回Promise，单位毫秒，不会阻塞浏览器。多个并行任务应由应用协调遮罩的显示状态；本接口不替业务自动统计请求数。

[前端目录](../README.md) · [函数目录](global-reference.md)
