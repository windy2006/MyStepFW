# 滚动视差

对父容器的一级子元素应用背景视差，对各节内部的一级子元素应用入场动画。加载组件 JS 和 CSS。

```html
<div id="story"><section data-bg="/images/background.jpg" data-effect="slide-up">
    <h2>标题</h2><p>内容</p>
</section></div>
```
```javascript
$('#story').parallax({mode:0,rate:0.3,effect:'slide-up',repeat:false});
$('#story').parallax('destroy');
```

| 选项 | 含义 |
| --- | --- |
| `mode` | 0 为页面正常滚动；1 为容器内全屏分节滚动 |
| `rate` | 背景移动比例 |
| `effect` | slide-up、slide-down、slide-left、slide-right、flip-x、flip-y、zoom 或 random/rand |
| `repeat` | 离开再进入时是否重复动画 |
| `offset` | 全屏模式从窗口高度扣除的像素数 |

节上的 `data-bg` 指定背景；`data-rate`、`data-effect` 覆盖对应设置，元素自身也可设置 `data-effect`。`data-func="show_2"` 顺序显示元素；其他全局函数名可自定义显示，函数收到 `($(section), effect)`。`parallax:enter` 事件携带进入元素。用户启用减少动态效果时仍显示内容。重复初始化先释放旧实例。
