# 图片缩放

为 file 输入框绑定图片解码和 Canvas 缩放，回调返回 data URL，不会自动上传或改写 input.files。

```html
<input id="photo" type="file" accept="image/*">
```
```javascript
$('#photo').resizeImg({mode:0,val:800,type:'image/jpeg',quality:0.85,capture:false,
    before:function (file) { return file.size>0; },
    callback:function (dataURL) { $('#preview').attr('src',dataURL); },
    error:function (error) { console.log(error.message); }
});
$('#photo').resizeImg('destroy');
```

mode 0：按 val 指定目标宽度；mode 1：按 val 比例缩放；mode 2：以 val KiB 相对原文件大小估算缩放比例，不保证最终编码字节数。val 大于 0；quality 为 0 到 1；type 为 image/jpeg 或 image/png。默认 capture:true 会设置 accept 和 capture；不需要优先拍摄时设 false。

options 也可为每次选图时返回设置对象的函数。before(file) 返回 false 取消；损坏图片、非法尺寸或不支持的画布会走 error 和 resizeImg:error。连续选图只交付最新任务；destroy 取消解码并恢复输入属性。图片方向使用浏览器原生解码处理。
