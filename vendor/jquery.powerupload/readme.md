整体描述：
--------
基于jQuery的文件上传插件，可通过文件选取、鼠标拖拽、图片粘贴以及blob读取等方式实现无刷新上传

调用条件：
--------
下载并复制 jquery.parallax.js 到网站目录，同时也需要事先准备好 jQuery 和 bootstrap 框架。

```html
<link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.3/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.bundle.js"></script>
<script type="/path/to/text/javascript" src="jquery.powerupload.js"></script>
```

调用方式：
--------
可通过如下方式调用：
```javascript
$(targetObj).powerUpload(opt);
```

其中opt为设置项，默认为：
```javascript
default_opts = {
    title: 'Power Uploader',    //浏览模式下载的对话框标题
    url: '',                    //响应上传的服务器地址
    refresh: 1000,              //进程刷新频率，1000为1秒
    param_name: 'userfile',     //上传文件的变量名
    max_files: 1,               //同时上传文件的数量限制
    max_file_size: 10,          //上传文件的最大体积（MB）
    data: {},                   //随同文件同时提交的参数（类似于form表单）
    mode: 'drop',               //上传模式，可为：browse, drop, paste 或直接传入文件对象变量（可为数组）
    errors: [],                 //错误回馈信息，默认为：["Browser Not Supported", "Too Many Files", "File Too Large", "No Upload Script Set", "Choose a file at least", "Error occurred when send data"]
    drop: dummy,                //在执行拖拽上传前所执行的指令，参数为浏览器事件，需返回 true 才会继续
    file: dummy,                //在执行浏览上传前所执行的指令，参数为浏览器事件，需返回 true 才会继续
    paste: dummy,               //在执行粘贴上传前所执行的指令，参数为浏览器事件，需返回 true 才会继续
    dragEnter: dummy,           //当鼠标拖拽文件进入目标元素时所执行的代码，参数为浏览器事件
    dragOver: dummy,            //当鼠标拖拽文件经过目标元素时所执行的代码，参数为浏览器事件
    dragLeave: dummy,           //当鼠标拖拽文件离开目标元素时所执行的代码，参数为浏览器事件
    docEnter: dummy,            //当鼠标拖拽文件进入浏览器时所执行的代码，参数为浏览器事件
    docOver: dummy,             //当鼠标拖拽文件经过浏览器时所执行的代码，参数为浏览器事件
    docLeave: dummy,            //当鼠标拖拽文件离开浏览器时所执行的代码，参数为浏览器事件
    beforeUpload: dummy,        //在执行上传操作前需执行的代码
    beforeEach: dummy,          //在每个文件上传操作前执行的检测代码，参数为当前文件对象，需返回true才能继续
    allDone: dummy,             //所有文件上传结束后执行的代码
    uploadStarted: dummy,       //在每个文件开始上传时执行的代码，包含 index, file, files_count 三个参数
    uploadFinished: dummy,      //在每个文件上传完成时执行的代码，包含 index, file, info, duration 四个参数，其中info为服务器脚本返回的参数
    progressUpdated: dummy,     //在当前进度发生变化时执行的代码，包含 index, file, currentProgress 三个参数
    speedUpdated: dummy         //在上传速度发生变化时执行的代码，包含 index, file, speeds 三个参数
}
```

设置实例：
--------
### 浏览上传
本例中加入了"obj_attr"参数的作用是在浏览器端可以直接调用摄像头拍摄视频（拍照为"image/*"）并上传，如无需此功能可直接去掉
```javascript
$(targetObj).powerUpload({
    url: 'web-server/script',
    title: '文件上传',
    mode: 'browse',
    obj_attr: {accept:'video/*',capture:'user'},
    max_files: 1,
    max_file_size: 100,
    allDone:function(){
        alert("所有文件上传完成！");
    }
});
```

### 拖拽上传
拖拽上传模式也支持图片粘贴的方式，需要注意的是 uploadFinished 的 result 是服务器脚本反馈的，请实际使用时不要以本例为准
```javascript
$(targetObj).powerUpload({
    url: 'web-server/script',
    mode: 'drop',
    max_files: 5,
    max_file_size: 32,
    errors: ["浏览器不支持", "一次只能上传5个文件", "每个文件必须小于32MB", "未设置上传目标", "请至少选择一个文件"],
    uploadFinished:function(i,file,result,timeDiff){
        let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
        if(result.error!==0) {
            obj.html(obj.html()+' - 上传失败 ('+result.message+')');
        }
    },
    dragEnter: function(){
        $(targetObj).text('松开鼠标以上传！');
    },
    dragLeave: function(){
        $(targetObj).html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
    },
    allDone:function(){
        $(targetObj).html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
        $('#uploader').find(".modal-title > b").html("全部文件上传完成，请关闭本对话框！");
    }
});
```

### 图片粘贴
本例的效果为粘贴图片并上传，然后将服务器脚本回馈的url在当前页面显示。
```javascript
$(targetObj).powerUpload({
    url: 'web-server/script',
    mode: 'paste',
    uploadFinished:function(i,file,result,timeDiff){
        if(result.error===0) {
            imgObj.src = result.src;
        }
    }
});
```

### blob上传
本模式可以直接将内存中的文件对象信息进行上传，可用于阿里云OSS之类，需要特殊验证的上传服务，其中"getVideos"应为文件对象数组或返回此数组的函数，"form_data"是服务器需要同步提交表单信息（如验证信息等内容为字符串的对象）
```javascript
$(targetObj).powerUpload({
    url: 'web-server/script',
    mode: getVideos,
    data: form_data,
    param_name: 'file',
    max_files: 3,
    max_file_size: 1000,
    uploadFinished:function(i,file,result,timeDiff){
        let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
        if(result.status==='OK') {
            obj.html(obj.html()+' - 上传完成');
        } else {
            obj.html(obj.html()+' - 上传失败').attr('title', result.error);
        }
    },
    allDone:function(){
        setTimeout(function(){
            alert("上传完成！");
            $('#uploader').modal('hide');
        }, 1000);
    }
});
```

其他问题：
--------
是否支持断点或分片上传？利用h5的文件slice方法是可以的，不过需要服务器端匹配，加上就丧失通用性了，意义也不大，这里仅讨论客户端代码，就不放出了

以续传为例大致说明一下，取文件 hash 及大小并传递给服务器，服务器检测对应 hash 的文件大小是否与客户端一致，如果小于则将服务器端文件大小返回；
客户端在将此文件slice片段读入内存，再通过本插件的blob模式上传，并附带提交 hash 信息；服务器端再根据所提交的数据进行对应处理