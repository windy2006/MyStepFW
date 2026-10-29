# 数值滑块

初始化时从 input 读取数值，单值为 `30`，区间为 `20,80`。返回值始终保留在原 input，表单正常提交。

```html
<input id="price" name="price" type="hidden" value="20,80">
```
```javascript
$('#price').jRange({from:0,to:100,step:0.5,isRange:true,width:300,
    scale:[0,25,50,75,100],showLabels:true,showScale:true,
    onstatechange:function (value) {console.log(value);}});
$('#price').jRange('setValue','30,60');
console.log($('#price').jRange('getValue')); // "30,60"
$('#price').jRange('updateRange','0,200','50,100');
$('#price').jRange('disable');
$('#price').jRange('enable');
$('#price').jRange('destroy');
```

from/to 必须有限且 to 大于 from，step 必须大于 0。超范围数值会截断，反向区间会排序；isRange:true 时传入单值会同时设置两个端点。程序一次 setValue 只把最终区间写回并通知 change。小数显示按边界和步长保留所需精度。

`format:'%s'` 或 `format(value,type)` 自定义标签，type 为 low/high，单值时为 undefined；格式返回值按 HTML 显示，应为可信内容。scale 是显示文字数组，`'|'` 只显示刻度。`snap` 控制按步长吸附。另有 getOptions、getRange、toggleDisable 方法；ondragend、onbarclicked 回调接收当前值。
