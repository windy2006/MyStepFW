# 设备采集

加载 `jquery.mediaDevice` 后使用 video、audio 或 canvas 元素。设备采集需要 HTTPS 或 localhost，并需用户允许摄像头／麦克风权限。错误通过目标元素旁的提示及 `media:error` 事件报告。

```javascript
$('#camera').on('media:error',function (event,info) {console.log(info.message);});
$('#camera').camera({audio:false,video:{width:{ideal:1280},height:{ideal:720}}});
$('#camera').camera('snap',function (dataURL) { $('#photo').attr('src',dataURL); });
$('#camera').camera('close');
```

| camera 指令 | 含义 |
| --- | --- |
| `start` 或配置对象 | 开启采集；回调接收元数据就绪事件 |
| `enum`／`e` | 按设备类型分组列举设备，结果传给回调 |
| `constraints`／`setting` | 返回独立的默认约束对象 |
| `snap`／`n` | 截图回调得到 PNG data URL |
| `record`／`r` | 开始／停止录像；回调接收 MediaRecorder dataavailable 事件 |
| `close`／`stop`／`c` | 停止录制及所有采集轨道 |
| `destroy` | 关闭采集并清理实例、提示和组件样式 |

```javascript
$('#wave').audio({deviceId:'选定的设备 ID',echoCancellation:true});
$('#wave').audio('record',function (blob) { console.log(blob.type,blob.size); },'wav');
// 再次执行 record 停止并产生结果。
$('#wave').audio('record');
$('#wave').audio('destroy');
```

`audio` 配置还支持 `file`（播放音频并显示波形）、`func(data,ctx,width,height)`（自定义绘制函数）、默认音频采集约束，以及 `device_id` 别名。`audio('enum',callback)` 列举音频输入；`audio('stop')` 释放采集流和绘制任务。暂停文件播放会暂停波形，不申请麦克风。

录音不传格式时返回浏览器原生 Blob；`wav` 使用内置 PCM 编码；`mp3` 需额外显式加载本地 lamejs，支持单声道和双声道。不是仅修改扩展名。`fullscreen('run')`／`fullscreen('exit')` 进入或退出全屏，`fullscreen('check')` 返回当前元素是否全屏。录制返回值仅表示已发起请求，设备权限及结果仍异步返回。
