# 窗口工具

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

| 调用 | 参数与用途 | 返回值 |
| --- | --- | --- |
| `openWindow(url, width, height)` | URL及像素宽高，打开名为new_window的窗口 | Window或null |
| `openDialog(url, width, height, mode)` | mode为true请求模态对话框，false请求非模态；浏览器不提供时调用openWindow | 对话框结果、Window或null |

这些接口不创建Bootstrap页面内模态框。应用需要页面内窗口时，使用已经加载的Bootstrap组件及自己的模板。

## 明确示例

```html
<button type="button" id="preview-window" data-url="/preview/">预览</button>
```

```javascript
const button = $id('preview-window');
if (button) button.addEventListener('click', function () {
    const win = openWindow(button.getAttribute('data-url'), 900, 640);
    if (win === null) alert('未能打开预览窗口。');
});
```

实际data-url由服务端地址工具生成并按HTML属性转义。窗口应在点击等用户手势内打开，返回null时给出反馈。

[前端目录](../README.md) · [函数目录](global-reference.md)
