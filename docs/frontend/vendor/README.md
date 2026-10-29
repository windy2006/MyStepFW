# 组件选用

本栏目介绍浏览器中的 jQuery 扩展。框架已提供本地 jQuery 3.6.1 和 Bootstrap 4.6.2；使用框架资源加载流程即可，无需再引入 CDN 或第二份 jQuery。

## 按需加载

```javascript
$.vendor('jquery.treeSelection', {
    add_css: true,
    callback: function () { $('#category').treeSelection(); }
});
```

`$.vendor` 需要框架 `jquery.addon.js`、`global` 等公共资源就绪。初始化放到 DOM 就绪及加载回调内。`add_css:true` 只用于存在同名 CSS 的组件：autocomplete、date_input、jmpopups、parallax、range、showImage、treeSelection；其余只加载脚本。独立页面可直接通过本地 `<script>`、`<link>` 引入这些文件。

| 组件 | 适用功能 | 主要入口 |
| --- | --- | --- |
| [自动补全](autocomplete.md) | 本地列表、远程查询、中文输入 | `.autocomplete(options)` |
| [日期输入](date-input.md) | 日期选择、月年切换 | `.date_input(options)` |
| [弹出窗口](popups.md) | 消息、确认、输入、图片和页面 | `showPop`、`confirm`、`prompt` |
| [设备采集](media.md) | 摄像头、录音、波形和全屏 | `.camera()`、`.audio()` |
| [滚动视差](parallax.md) | 普通滚动、全屏分节和入场效果 | `.parallax(options)` |
| [延迟图片](power-image.md) | 可见时加载、ALT 滚轮缩放 | `.powerImage(options)` |
| [文件上传](upload.md) | 选取、拖放、粘贴和批量上传 | `.powerUpload(options)` |
| [数值滑块](range.md) | 单值、区间和小数步长 | `.jRange(options)` |
| [图片缩放](resize.md) | 上传前在浏览器压缩图片 | `.resizeImg(options)` |
| [图集展示](gallery.md) | 缩略图轮播和图片墙 | `.showImage()`、`.imageWall()` |
| [树形选择](tree.md) | 层级选择、原生表单提交 | `.treeSelection(options)` |

## 局部页面清理

在移除或替换组件的宿主元素前调用 `destroy`，释放其监听、请求、观察器或定时器。例如：

```javascript
$('#upload-button').powerUpload('destroy');
$('#category').treeSelection('destroy');
$('#calendar').date_input('destroy');
```

选项初始化通常返回原 jQuery 选择集；autocomplete 为已有接口约定，返回第一个补全实例。jRange 的 getter 返回数值字符串；摄像头和录音的 record 返回是否已发起录制。不要把这些同步返回值当作异步任务完成标志。
