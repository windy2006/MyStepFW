整体描述：
--------
通过H5调用视频、频输入输出设备，包括可以调用摄像头拍照，调用音频输入设备录音，调用音频输出设备播放音乐，同时还可以捕捉音频波形。

本插件可在PC或移动端使用，如果是移动端会判断手机方向是否正确，以保证拍摄正向的视频。

请参考：[演示页面](https://windy2006.github.io/jquery.mediaDevice/)

**注意：** 由于安全机制限制，相关组件只能在SSL（https）模式下使用。浏览器的兼容情况请参考 [官方文档](https://developer.mozilla.org/zh-CN/docs/Web/API/MediaDevices)

调用条件：
--------
下载并复制 jquery.mediaDevice.js 到网站目录，同时也需要事先准备好 jQuery 框架。

```html
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/path/to/jquery.mediaDevice.js"></script>
```

包含组件：
--------
本插件包含三个组件：
- camera 组件 ： 摄像头及音频设备调用
- audio 组件 ： 音频波形分析
- fullscreen 组件 ： 全屏设置组件

设置方式：
--------
### camera 组件
本组件需要作用于"video"标签，使用方法：
```javascript
$(videoObj).camera(opt, callback_function);
```

其中opt可为以下内容（设置参数或指令）
- **start** 或 **对象参数**：启动媒体设备，本方法的回调函数可选，传入参数为事件对象，默认启动参数对象如下：
    ```javascript
    default_constraints = {
        /**
         * 【音频参数调整】
         * volume            音量调整（范围 0-1.0， 0为静音，1为最大声）
         * sampleRate        采样率 （例 8000）
         * sampleSize        采样大小 （例 16位 2字节）
         * echoCancellation  回音消除 （true/false）
         * autoGainControl   自动增益 （在原有录音的基础上是否增加音量， true/false）
         * noiseSuppression  是否开启降噪功能 （true/false）
         */
        audio: {
            volume: 1,
            sampleRate: 22050,
            sampleSize: 16,
            echoCancellation: true,
            autoGainControl: false,
            noiseSuppression: true,
            channelCount: 2
        },
        /**
         * 【视频参数调整】
         * width        视频宽度
         * height       视频高度
         * aspectRatio  比例（一般可不设置，只设置宽高即可，可用来获取宽高比）
         * frameRate    帧率（可通过帧率设置码流，帧率越高，码流越大，视频越平滑）
         * facingMode   摄像头（PC会忽略，手机端可区分）
         *              user           前置摄像头
         *              environment    后置摄像头
         *              left           前置左侧摄像头
         *              right          前置右侧摄像头
         * resizeMode   采集画面是否裁剪
         *              none           不裁剪
         *              crop-and-scale 裁剪
         */
        video: {
            width: { min: 1024, ideal: 1280, max: 1920 },
            height: { min: 576, ideal: 720, max: 1080 },
            aspectRatio: 1.777777778,   //16:9
            frameRate: {min: 15, ideal: 20, max: 30 },
            facingMode: "user",
            resizeMode: "none"
        },
        /**
         * 【其他参数】
         * latency           延迟大小 （ 延迟小，网络不好的情况下，会卡顿花屏等，好处在于可实时通信，建议200ms）
         *                           （ 延迟大，网络不好的情况下，画面相对更平滑流畅，但即时性较差）
         * channelCount      单/双声道
         * deviceID          多个摄像头或音频输入输出设备时，可进行设备切换（例如切换前后置摄像头）
         * groupID           同一组设备
         */
    };
    ```
  
- **close** ：关闭当前已启动的媒体设备并调用回调函数（可选），在更换媒体设备时也需要先关闭现有启动设备。

- **snap** ：截取当前摄像头所拍摄的图片，并将图片base64编码的url传给回调函数。

- **record** ：开始或停止录制视频，开始录制视频时会直接进入全屏状态，按 ESC 键会退出全屏并停止录像。录像停止后会将摄像事件（含录像内容）传递给回调函数
  
- **enum** ：枚举所有可用的媒体设备，并将结果传递给回调函数，本方法使用无需实现启动媒体控件，所返回的设备信息，如下：
    ```json
    {
        "audioinput": [{
            "deviceId": "98071022b7ed80548ef96d0ba9adbeea3a4159af8db6e956d3cc037f0d5b0e2f",
            "kind": "audioinput",
            "label": "Internal Microphone (Built-in)",
            "groupId": "133c9d98f59b60fa9d6a8bda35c679bc6c5364540f88957a425b3073793978bb"
        }],
        "videoinput": [{
            "deviceId": "bf86e6cab55fb24e1e248677d97b29e3d8bcd52f8a419fe38804d9bd0ddc4e28",
            "kind": "videoinput",
            "label": "FaceTime HD Camera",
            "groupId": "b0798bff15f8df35cee8b5a64cfe7ece7c8d1ada764608f9f1bb1bba9adda326"
        }],
        "audiooutput": [{
            "deviceId": "cab55abf75f9003ffcb35628f76ac60745f5fce880622692e2b0b89e2137b572",
            "kind": "audiooutput",
            "label": "Internal Speakers (Built-in)",
            "groupId": "133c9d98f59b60fa9d6a8bda35c679bc6c5364540f88957a425b3073793978bb"
        }]
    }
    ```
  
- **constraints** ：返回当前媒体调用参数，具体情况见启动媒体控件

### audio 组件
本组件可作用于任意标签，如为audio标签，将在audio标签下添加波形图，如非audio标签，则会自动调用audio控件，并在调用标签下插入波形对象，使用方法：
```javascript
$(obj).audio(opt);
```
其中opt为配置参数，设置如下：
```javascript
{
    device_id : 'audio input device id',  //音频输入设备ID，可通过枚举方式获取，如不设置则选用默认设备
    file : 'path/to/music',  //音乐文件地址，如未设置则自动调用麦克风
    func : 'wave_function' //用于绘制波形的函数，默认有effect_1，effect_2 和 effect_3
}
```
**波形绘制函数** ：本函数可由用户自定义，包含四个参数，分别是：data（音频数据）, canvas（画板对象）, width（画板宽）, height（画板高）

### fullscreen 组件
本组件用于设置浏览器或任意支持全屏属性的对象，多对非全屏对象模拟全屏效果，使用方法：
```javascript
$(obj).fullscreen(opt);
```
其中opt可为以下内容
- **run**：启动全屏
- **exit**：退出全屏
- **check**：检测全屏状态
- **留空**：切换全屏状态
- **其他**：返回当前全屏对象