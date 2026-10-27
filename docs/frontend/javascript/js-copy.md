# 剪贴复制

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法与返回值

| 调用 | 行为 | 返回值 |
| --- | --- | --- |
| `copyValue(obj)` | 从文本、选择器、元素或jQuery对象中取复制内容，按data-copy、非空val、text取值 | string或null |
| `copy(obj)` | 使用execCommand同步复制，恢复焦点及选区 | boolean |
| `copyAsync(obj)` | 优先Clipboard API，不可用或被拒绝时调用copy | Promise<boolean> |

## 明确示例

```html
<span id="share-address" data-copy="https://example.test/">分享地址</span>
<button type="button" id="copy-address">复制</button>
```

```javascript
const copyButton = $id('copy-address');
if (copyButton) copyButton.addEventListener('click', async function () {
    const ok = await copyAsync('#share-address');
    if (!ok) alert('未能复制，请手动选择文字。');
});
```

以`#`或`.`开头的字符串解释为选择器；需要复制此类字面文本时放入元素data-copy属性，再传该元素。浏览器权限、HTTPS及用户手势影响结果；调用端检查布尔值，不将Promise当作复制成功。

[前端目录](../README.md) · [函数目录](global-reference.md)
