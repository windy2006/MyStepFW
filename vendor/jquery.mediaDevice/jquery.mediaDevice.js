/**
 * jquery.mediaDevice.js - H5 Audio and Video device call
 *
 * Written by
 * ----------
 * Windy2000 (windy2006@gmail.com)
 *
 * Licensed under the Apache License Version 2.0
 *
 * Dependencies
 * ------------
 * jQuery (http://jquery.com)
 *
 **/

(function($){
    let constraints = {},
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

    !function(){
        if(document.location.protocol==='http:') {
            alert("媒体设备调用只能在SSL安全模式下使用，正在尝试跳转！");
            location.href = 'https://' + document.location.hostname + document.location.pathname;
        }
        window.requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;
        window.URL = window.URL || window.webkitURL || window.mozURL || window.msURL;
        // 老的浏览器可能根本没有实现 mediaDevices，所以我们可以先设置一个空的对象
        if (navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
        }
        // 一些浏览器部分支持 mediaDevices。我们不能直接给对象设置 getUserMedia
        // 因为这样可能会覆盖已有的属性。这里我们只会在没有getUserMedia属性的时候添加它。
        if (navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = function(constraints) {
                // 首先，如果有getUserMedia的话，就获得它
                let getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
                // 一些浏览器根本没实现它 - 那么就返回一个error到promise的reject来保持一个统一的接口
                if (!getUserMedia) {
                    return Promise.reject(new Error('当前浏览器不支持调用媒体设备！'));
                }
                // 否则，为老的navigator.getUserMedia方法包裹一个Promise
                return new Promise(function(resolve, reject) {
                    getUserMedia.call(navigator, constraints, resolve, reject);
                });
            }
        }
    }();

    $.fn.camera = function(opt) {
        let videoObj = this.get(0);
        if(typeof(opt)==='object') {
            $.extend(constraints, default_constraints, opt);
            $(videoObj).data('stream', null);
            opt = 'start';
        }
        if(typeof arguments[1] === 'undefined') {
            arguments[1] = function(){return true;};
        }
        switch(opt) {
            case 'e':
            case 'enum':
                enumerate(arguments[1]);
                break;
            case 'constraints':
            case 'setting':
                return JSON.parse(JSON.stringify(default_constraints));
            case 'n':
            case 'snap':
                snap(arguments[1]);
                break;
            case 'r':
            case 'record':
                return record(arguments[1]);
            case 'c':
            case 'close':
                close(arguments[1]);
                break;
            case 's':
            case 'start':
            default:
                videoObj.volume = 0;
                this.css('transform', 'rotateY(180deg)');
                if(typeof constraints.audio === 'undefined') {
                    constraints = JSON.parse(JSON.stringify(default_constraints));
                }
                start(arguments[1]);
        }
        function start(func) {
            close();
            let supportedConstraints = navigator.mediaDevices.getSupportedConstraints();
            if (!supportedConstraints.width || !supportedConstraints.height || !supportedConstraints.frameRate || !supportedConstraints.facingMode) {
                new Error('当前浏览器不支持某些摄像头属性设置！')
            }
            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(stream) {
                    // 旧的浏览器可能没有srcObject
                    if ("srcObject" in videoObj) {
                        videoObj.srcObject = stream;
                    } else {
                        // 防止在新的浏览器里使用它，应为它已经不再支持了
                        videoObj.src = window.URL.createObjectURL(stream);
                    }
                    $(videoObj).data('stream', stream);
                    stream.getVideoTracks().forEach(function(track){
                        if(track.readyState === 'live') {
                            let cap = track.getCapabilities(),
                                rate = constraints.video.rate || constraints.video.aspectRatio;
                            constraints.video.width = cap.width.max;
                            constraints.video.height = cap.height.max;
                            if(rate) {
                                let the_height = constraints.video.width / rate;
                                if(the_height > constraints.video.height) {
                                    constraints.video.width = constraints.video.height * rate;
                                } else {
                                    constraints.video.height = the_height;
                                }
                                constraints.video.resizeMode = 'crop-and-scale';
                            }
                            track.applyConstraints(constraints.video);
                        }
                    })
                    $(videoObj).data('recorder', null);
                    videoObj.onloadedmetadata = function(e) {
                        if(typeof func === 'function') func(e);
                        videoObj.play();
                    };
                })
                .catch(function(err) {
                    alert("未检测到媒体设备或浏览器不支持媒体设备调用！");
                    console.log(err.name + ": " + err.message);
                });
        }
        function close(func) {
            if($(videoObj).data('stream')!=null) {
                $(videoObj).data('stream').getTracks().forEach(function(track){
                    track.stop();
                });
                $(videoObj).data('stream', null);
                videoObj.pause();
                videoObj.src = '';
                videoObj.load();
            }
            if(typeof func === 'function') func();
        }
        function enumerate(func) {
            if (typeof navigator.mediaDevices.enumerateDevices === 'function') {
                navigator.mediaDevices.enumerateDevices()
                    .then(function(devices){
                        let result = {};
                        devices.forEach(function(device) {
                            let kind = device.kind.toLowerCase();
                            if(typeof result[kind] === 'undefined') result[kind] = [];
                            result[kind].push(device);
                        });
                        console.log(JSON.stringify(result));
                        func(result);
                    })
                    .catch(function(err) {
                        new Error(err.name + ": " + err.message)
                    });
            }
        }
        function record(func) {
            if("onorientationchange" in window) {
                window.onorientationchange = function(e) {
                    if(window.orientation!==0 && window.orientation!==90) {
                        alert('请反转您的移动设备，以保证所拍摄视频可以正常查看！');
                        return false;
                    }
                }
            }
            let flag = false;
            let recorder = $(videoObj).data('recorder');
            if(recorder===null) {
                recorder = new MediaRecorder($(videoObj).data('stream'));
                recorder.ondataavailable = func;
                recorder.start();
                $(videoObj).data('recorder', recorder);
                $('#video').fullscreen('r');
                setTimeout(function(){
                    $(document).one('fullscreenchange', function(){
                        recorder.stop();
                        $(videoObj).data('recorder', null);
                        videoObj.play();
                    })
                }, 150);
                flag = true;
            } else {
                recorder.stop();
                $(videoObj).data('recorder', null);
            }
            return flag;
        }
        function snap(func) {
            let canvas = document.createElement('canvas');
            canvas.width = $(videoObj).width();
            canvas.height = $(videoObj).height();
            let context = canvas.getContext('2d');
            context.drawImage(videoObj, 0, 0, canvas.width, canvas.height);
            func(canvas.toDataURL("image/png"));
        }
        return this;
    };

    $.fn.audio = function(opt) {
        let canvasObj = this.get(0);
        let audioObj = this.get(0);
        let mainObj = this;
        if(this.data('canvas')==null) {
            let tagName = canvasObj.tagName.toLowerCase();
            if(tagName === 'audio') {
                canvasObj = document.createElement('canvas');
                $(canvasObj).insertAfter(audioObj).css({width:'100%',height:100});
            } else {
                if(tagName !== 'canvas') {
                    canvasObj = document.createElement('canvas');
                    $(canvasObj).insertAfter(audioObj).css({width:'100%',height:100});
                    this.append(canvasObj)
                }
                audioObj = new Audio();
            }
            this.data('canvas', canvasObj);
            this.data('audio', audioObj);
        } else {
            canvasObj = this.data('canvas');
            audioObj = this.data('audio');
        }
        let canvasCtx = canvasObj.getContext("2d");
        let WIDTH = canvasObj.offsetWidth;
        let HEIGHT = canvasObj.offsetHeight;
        let gradient = canvasCtx.createLinearGradient(0, 0, WIDTH/3, HEIGHT);
        gradient.addColorStop(0, "#f500d8");
        gradient.addColorStop(1, "#ceaf11");
        if(!this.data('audioContext')) {
            $(window).resize(function(){
                gradient = canvasCtx.createLinearGradient(0, 0, WIDTH/3, HEIGHT);
                gradient.addColorStop(0, "#f500d8");
                gradient.addColorStop(1, "#ceaf11");
                WIDTH = canvasObj.offsetWidth;
                HEIGHT = canvasObj.offsetHeight;
            });
        }
        
        // 创建音频对象
        let audioContext = this.data('audioContext') || new (window.AudioContext || window.webkitAudioContext || window.mozAudioContext)();
        this.data('audioContext', audioContext);
        // 获取声音源
        let audioSource = audioContext.createBufferSource();
        // 获取分析对象
        let audioAnalyser = this.data('audioAnalyser');
            if(!audioAnalyser) {
                audioAnalyser = audioContext.createAnalyser();
                audioAnalyser.minDecibels = -90;
                audioAnalyser.maxDecibels = -10;
                audioAnalyser.smoothingTimeConstant = 0.85;
                audioAnalyser.fftSize = 1024;
                this.data('audioAnalyser', audioAnalyser);
            }
        let processor = this.data('processor');
            if(!processor) {
                processor = audioContext.createScriptProcessor(1024);
                processor.connect(audioContext.destination);
                audioAnalyser.connect(processor);
                this.data('processor', processor);
            }
        let waveShaper = audioContext.createWaveShaper();
        let biquadFilter = audioContext.createBiquadFilter();
        let gainNode = audioContext.createGain();
        let convolver = audioContext.createConvolver();

        let bufferLength = audioAnalyser.frequencyBinCount;
        let dataArray = new Uint8Array(bufferLength);
        let sound = this.data('sound');
        let func = new Function();

        let constraints = {audio:true,video:false};
        opt = $.extend(opt, {});
        if(typeof opt.device_id !== 'undefined') {
            constraints.audio = {deviceId: device_id};
        }
        if(typeof opt.func !== 'undefined') {
            func = opt.func;
        }
        if(typeof opt.file !== 'undefined') {
            musicWave();
        } else {
            soundWave();
        }
        mainObj.data('stop', 'n');

        function musicWave() {
            let loadAudioElement = function(url) {
                return new Promise(function(resolve, reject) {
                    audioObj.addEventListener('canplay', function() {
                        resolve(audioObj);
                    });
                    audioObj.addEventListener('play', function() {
                        mainObj.data('stop', 'y');
                        setTimeout(function(){
                            func = Math.random()>0.5 ? effect_1 : effect_2;
                            mainObj.data('stop', 'n');
                            show();
                        }, 100);
                    });
                    audioObj.addEventListener('error', reject);
                    audioObj.autoplay = true;
                    audioObj.controls = true;
                    audioObj.src = url;
                    audioObj.onpause = function(){
                        mainObj.audio();
                    }

                });
            }
            loadAudioElement(opt.file).then(function(obj) {
                sound = sound || audioContext.createMediaElementSource(obj);
                obj.onended = function() {
                    sound.disconnect(audioAnalyser);
                    sound.disconnect(audioContext.destination);
                    sound = null;
                    processor.onaudioprocess = function() {};
                    processor.disconnect();
                    canvasCtx.clearRect(0, 0, WIDTH, HEIGHT);
                    mainObj.data('stop', 'y');
                };
                sound.connect(audioAnalyser);
                sound.connect(audioContext.destination);
                mainObj.data('sound', sound);
                processor.onaudioprocess = function(e) {
                    audioAnalyser.getByteTimeDomainData(dataArray);
                };
                obj.play().then(function(){
                    mainObj.data('stop', 'y');
                    setTimeout(function(){
                        func = Math.random()>0.5 ? effect_1 : effect_2;
                        mainObj.data('stop', 'n');
                        show();
                    }, 100);
                }).catch(function(e){
                    console.log(e);
                });
            }).catch(function(e) {
                console.log(e);
            });
        }

        function soundWave() {
            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(stream) {
                    audioContext.resume().then(() => {
                        let source = audioContext.createMediaStreamSource(stream);
                        source.connect(waveShaper);
                        waveShaper.connect(biquadFilter);
                        biquadFilter.connect(gainNode);
                        convolver.connect(gainNode);
                        gainNode.connect(audioAnalyser);
                        audioAnalyser.connect(audioContext.destination);

                        waveShaper.oversample = '4x';
                        biquadFilter.gain.setTargetAtTime(0, audioContext.currentTime, 0)

                        mainObj.data('stop', 'y');
                        setTimeout(function(){
                            func = effect_3;
                            mainObj.data('stop', 'n');
                            show();
                        }, 100);
                    });
                }).catch(function(err) {
                    alert("未检测到媒体设备或浏览器不支持媒体设备调用！");
                    console.log(err.name + ": " + err.message);
                });
        }

        function show() {
            if(mainObj.data('stop')==='y') return;
            requestAnimationFrame(show);
            audioAnalyser.getByteFrequencyData(dataArray);
            canvasCtx.clearRect(0, 0, WIDTH, HEIGHT);
            canvasCtx.fillStyle = 'rgb(240,240,240)';
            canvasCtx.fillRect(0, 0, WIDTH, HEIGHT);
            func(dataArray, canvasCtx, WIDTH, HEIGHT);
        }

        function effect_1(data, canvas, width, height) {
            let length = data.length;
            let barWidth = 10;
            let barHeight;
            let x = 0;
            for(let i = 0; i < length; i++) {
                barHeight = data[i]/256 * height * 1.3;
                canvas.fillStyle = 'rgb(' + (barHeight+150) + ',50,50)';
                canvas.fillRect(x,height-barHeight,barWidth,barHeight);
                x += barWidth + 1;
            }
        }

        function effect_2(data, canvas, width, height) {
            let length = data.length;
            let step = width / length;
            let x = 0;
            canvas.beginPath();
            canvas.moveTo(0, height);
            for (let i = 0; i < length; i++) {
                canvas.lineTo(x, height - data[i]/256 * height);
                x += step;
            }
            canvas.fillStyle = gradient;
            canvas.fill();
            canvas.closePath();

            canvas.beginPath();
            canvas.moveTo(0, height);
            x = 0;
            for (let i = 0; i < length; i++) {
                canvas.lineTo(x, height - data[i]/256 * height - Math.random() * 30)
                x += step;
            }
            canvas.strokeStyle = gradient;
            canvas.stroke();
            canvas.closePath();
        }

        function effect_3(data, canvas, width, height) {
            let length = data.length;
            let barLength = 0;
            let tmp = 0;
            for(let i = 0; i < length; i++) {
                if(data[i]<tmp) break;
                tmp = data[i]
            }
            barLength = width * Math.pow((tmp / 256), 2) * 0.7;
            canvas.fillStyle = 'rgb(' + (barLength+50) + ',50,50)';
            canvas.fillRect(0,0,barLength,height);
        }

        return this;
    }

    $.fn.fullscreen = function(opt) {
        let isFullscreen = document.fullscreenEnabled || document.webkitFullscreenEnabled || document.msFullscreenEnabled || document.mozFullScreenEnabled;
        if(!isFullscreen) return false;
        let theObj = this.get(0);
        if(typeof opt === 'undefined') {
            opt = check() ? 'exit' : 'run';
        }
        switch(opt) {
            case 'r':
            case 'run':
                run();
                break;
            case 'e':
            case 'exit':
                exit();
                break;
            case 'c':
            case 'check':
                return check();
            default:
                return getElement();
        }

        function run() {
            if(check()) exit();
            if(theObj.requestFullscreen) {
                theObj.requestFullscreen();
            } else if(theObj.mozRequestFullScreen) {
                theObj.mozRequestFullScreen();
            } else if(theObj.msRequestFullscreen) {
                theObj.msRequestFullscreen();
            } else if(theObj.oRequestFullscreen) {
                theObj.oRequestFullscreen();
            } else if(theObj.webkitRequestFullscreen) {
                theObj.webkitRequestFullScreen();
            }else{
                let  css = {
                    width:'100%',
                    height:'100%',
                    overflow:'hidden'
                };
                $(document.documentElement).css(css);
                $(document.body).css(css);
                $(theObj).css(css).addClass('m-0 p-0');
            }
            theObj.data('fullscreen', 'y');
        }
        function exit() {
            if(!check()) return;
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if(document.oRequestFullscreen) {
                document.oCancelFullScreen();
            }else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }else{
                let  css = {
                    width:'auto',
                    height:'auto',
                    overflow:'auto',
                };
                $(document.documentElement).css(css);
                $(document.body).css(css);
                $(theObj).css(css).addClass('m-0 p-0');
                document.fullscreenElement = theObj;
            }
            theObj.data('fullscreen', null);
        }
        function check(){
            let list = [document.isFullScreen, document.webkitIsFullScreen, document.msFullscreenEnabled, document.mozIsFullScreen, document.oIsFullScreen, window.fullScreen];
            for(let x in list) {
                if(typeof list[x] === 'boolean') return list[x];
            }
            return !!theObj.data('fullscreen');
        }
        function getElement(){
            return document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement;
        }
        return this;
    }
})(jQuery);
