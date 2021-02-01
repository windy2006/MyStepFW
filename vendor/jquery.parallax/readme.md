整体描述：
--------
本插件对某一父元素下的所有一级子元素设置背景视差效果，并对该子元素下一级孙元素设定出现效果。

本插件可在PC或移动端使用，如果是移动端会判断手机方向是否正确，以保证拍摄正向的视频。

请参考：[演示页面](https://windy2006.github.io/jquery.parallax/)

调用条件：
--------
下载并复制 jquery.parallax.js 到网站目录，同时也需要事先准备好 jQuery 框架。

```html
<link href="/path/to/jquery.parallax.css" rel="stylesheet">
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="/path/to/text/javascript" src="jquery.parallax.js"></script>
```

调用方式：
--------
调用非常简单，只需向父元素执行parallax方法即可，如下：
```javascript
$(obj).parallax(opt);
```
其中opt为可选的设置项，默认为：
```javascript
{
    mode : 0,             //滚动响应模式：0 为正常模式，1 为全屏模式即每次滚动一个屏幕
    rate : 0.3,           //背景相对移动比例，1为与滚动条同步，0为完全静止
    effect : 'slide-up'   //孙元素呈现效果
}
```

个性设置：
--------
除了统一设置外，还需要在每个子元素中设置对应参数，如下：
- **data-bg** ： 子元素背景图，非必选项，但是为达到预期效果，长宽比尽可能小于1
- **data-rate** ： 非必选项，功能与统一设置中的 rate 参数相同
- **data-effect** ： 非必选项，功能与统一设置中的 effect 参数相同，该选项也可用于孙元素
- **data-func** ： 非必选项，可自定义孙元素的显示规则，插件默认有 show_1 和 show_2 两种方式，
                  用户也可以自定义自己的方法，该方法将传入两个参数，即：子元素 obj 和 显示效果 mode

显示效果：
--------
孙元素可用如下7种显示效果：
```
slide-up
slide-down
slide-right
slide-left,
flip-x
flip-y
zoom
```
如果定义为 random 或 rand 的话，会从以上效果中任意选取一种。