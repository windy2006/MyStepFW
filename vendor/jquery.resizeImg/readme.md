整体描述：
--------
直接在客户端对图片文件进行压缩，并返回压缩图片的 base64 编码给回调函数。

本插件可以根据照片拍摄的方向信息自动纠正为正向。

请参考：[演示页面](https://windy2006.github.io/jquery.resizeImg/)

调用条件：
--------
下载并复制 jquery.resizeImg.js，mobileBUGFix.mini.js 到网站目录，同时也需要事先准备好 jQuery 框架。

```html
<script type="text/javascript" src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript" src="/path/to/jquery.resizeImg.js"></script>
<script type="text/javascript" src="/path/to/mobileBUGFix.mini.js"></script>
```

调用方式：
--------
调用非常简单，只需向 type 为 file 的 input 元素执行 resizeImg 方法即可，如下：
```javascript
$(fileObj).resizeImg(options);
```
其中opt为可选的设置项，默认为：
```javascript
{
    mode: 0,                    // 缩放模式：1 - 按宽度，2 - 按比例，3 - 按大小
    val: 400,                   // 对应模式的变化量
    type: "image/jpeg",         // 生成图片的格式可为 image/jpeg 或 image/png
    quality: 0.8,               // 生成图片的质量（针对jpg格式）
    capture: true,              // 是否为移动端添加调用摄像头拍摄的功能
    before: new Function(),     // 在生成缩略图前的预处理，将传入未处理的图片文件对象
    callback: new Function()    // 处理后经base64编码的缩略图信息将传递给此回调函数
}
```
同时，opt亦可为一个返回此标准对象的函数，以自动响应页面其他元素的设置变更。

本插件调用比较简单，更多可参考 [演示页面](https://windy2006.github.io/jquery.resizeImg/)