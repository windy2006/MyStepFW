# 页面滚动

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

`gotoAnchor(theAnchor='')`按元素id或a.name滚动，允许带`#`；空字符串回到页面顶部。函数始终返回false，便于在链接处理时使用；目标不存在或已有动画时不重复启动。

## 明确示例

```html
<a href="#chapter-two" id="chapter-link">跳到第二节</a>
<section id="chapter-two">第二节正文</section>
```

```javascript
const link = $id('chapter-link');
if (link) link.addEventListener('click', function (event) {
    event.preventDefault();
    gotoAnchor('chapter-two');
});
// 回顶部：gotoAnchor('');
```

gotoAnchor负责滚动，不请求服务端或切换功能页。需要改变地址历史时使用[链接处理](js-urls.md)中的setLocation，并由页面自己的逻辑加载正文。

[前端目录](../README.md) · [函数目录](global-reference.md)
