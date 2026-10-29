# 延迟图片

图片进入可见区域后才读取其目标地址；支持 IntersectionObserver 和滚动扫描后备实现。

```html
<div id="pictures"><img data-src="/images/picture.jpg" alt="图片说明"></div>
```
```javascript
$('#pictures').powerImage({selector:'data-src',width:'100%',zoom:true});
$('#pictures img').on('powerImage:error',function (event,url) {console.log('加载失败',url);});
$('#pictures').powerImage('destroy');
```

`selector` 是存放图片地址的属性名，默认 data-src；`image` 为占位图地址；`width` 为最大宽度，默认 600，可填百分比；`zoom` 默认 true，按 ALT 并滚轮缩放。`powerImage:load` 与 `powerImage:error` 事件在对应 img 上触发，携带目标地址。

动态加入新图片后可再次初始化；旧任务与监听会先清理。destroy（也可用 dispose）恢复图片目标地址、原样式和标题。Document 的独立页面适配默认使用 src 并跳过 title_img，原调用入口继续有效。
