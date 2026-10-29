# 图集展示

showImage 从一组 img 读取地址、alt/title，输出主图、缩略图和轮播。每组图集使用独立目标容器。

```html
<div id="source" hidden><img src="/images/a.jpg" alt="A"><img src="/images/b.jpg" alt="B"></div>
<div id="gallery"></div>
```
```javascript
$('#source').showImage({container_id:'gallery',container_width:'100%',img_height:420,
    thumb_width:120,thumb_height:80,thumb_margin:12,interval:3});
$('#source').showImage('destroy');
```

默认目标 ID 为 showImage；container_width 默认 640；interval 单位秒，鼠标进入时暂停。单图不启动轮播；没有图片时不建立面板。方法返回原选择集。destroy/dispose 释放计时器和监听，恢复目标容器原内容及样式。Document 页面还接受 ole_id 作为 container_id 别名。

## 图片墙

```javascript
$('#logos').imageWall({size_h:4,size_v:2,img_width:150,img_height:60,
    interval:5,speed:500,tag:'img'});
$('#logos').imageWall('destroy');
```

size_h/size_v 为列数和行数，img_width/img_height 为单元尺寸；interval 单位秒，speed 单位毫秒。tag 指定轮换元素，通常为 img 或链接。销毁会取消尚未执行的切换并恢复原内容；多个容器分别保留自己的实例。
