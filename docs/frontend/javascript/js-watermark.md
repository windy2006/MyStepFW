# 页面水印

说明watermark对受信HTML的处理、版权文字与样式依赖。

## 页面水印

| 接口 | 参数与功能 | 返回约定 |
| --- | --- | --- |
| `watermark(obj, rate=5, copyright='WaterMark Maker, Coded by Windy2000', char_c='', jam_tag=false)` | obj 为 HTML 字符串或具有 innerHTML 的元素；rate 越大随机干扰越少，10 不插入随机干扰字；copyright 在换行后作为版权提示转义；char_c 为逗号分隔的额外单元；jam_tag 是否插入成对干扰 span | 生成的 HTML string；obj 是元素时也会改写该元素 |

```javascript
const text = $id('trusted-article');
if (text) watermark(text, 8, '本站内容', '', false);
```

watermark的隐藏干扰依赖global.css；rate=10仍可能附加版权片段。它不是HTML净化器，只处理可信内容。

[文档总览](../../README.md)
