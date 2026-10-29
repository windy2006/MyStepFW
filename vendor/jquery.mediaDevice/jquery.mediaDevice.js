/**
 * 框架调用：camera(command,callback) / audio(options|command,callback,format)
 * 功能说明：WAV 内置编码；MP3 单独加载 lamejs。设备权限和录制结果异步通知，destroy 释放设备及监听。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/**
 * jquery.mediaDevice.js - H5 Audio and Video device call
 *
 * Written by
 * ----------
 * Windy2000 (windy2006@gmail.com)
 *
 * Licensed under the Apache License Version 2.0
 *
 * Release at https://github.com/windy2006/jquery.mediaDevice
 *
 * Dependencies
 * ------------
 * jQuery (http://jquery.com)
 *
 **/

(function ($) {
  let mediaSerial=$.data(document,'jquerymediaDeviceSerial')||0;
  const default_constraints = {
    /**
     * 【音频参数调整】
     * volume            音量调整（范围 0-1.0， 0为静音，1为最大声）
     * sampleRate        采样率 （例 8000）
     * sampleSize        采样大小 （例 16位 2字节）
     * echoCancellation  回音消除 （true/false）
     * autoGainControl   自动增益 （在原有录音的基础上是否增加音量， true/false）
     * noiseSuppression  是否开启降噪功能 （true/false）
     * channelCount      声道数量 （例 1/2）
     */
    audio: {
      volume: 1,
      sampleRate: 22050,
      sampleSize: 16,
      echoCancellation: true,
      autoGainControl: false,
      noiseSuppression: true,
      channelCount: 1,
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
      aspectRatio: 1.777777778, //16:9
      frameRate: { min: 15, ideal: 20, max: 30 },
      facingMode: "user",
      resizeMode: "none",
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

  function notify(target, error, kind) {
    const messages = {
      NotFoundError:
        "未检测到可用的" +
        (kind === "audio" ? "麦克风" : "摄像头或麦克风") +
        "，请连接设备后重试。",
      DevicesNotFoundError: "未检测到媒体设备，请连接设备后重试。",
      NotAllowedError:
        "媒体访问被拒绝，请在浏览器的网站权限中允许摄像头或麦克风，然后重试。",
      PermissionDeniedError: "媒体访问被拒绝，请检查浏览器的网站权限。",
      NotReadableError:
        "设备无法读取，可能正被其他程序占用，请关闭占用程序后重试。",
      TrackStartError: "设备无法启动，可能正被其他程序占用。",
      OverconstrainedError:
        "当前设备不支持所选参数，请切换设备或降低分辨率后重试。",
      SecurityError: "浏览器阻止了媒体访问，请使用 HTTPS 并检查网站权限。",
    };
    const message =
      messages[error.name] ||
      error.message ||
      "媒体设备调用失败，请检查设备连接及浏览器权限。";
    const obj = $(target),
      id = kind === "audio" ? "audio" : "camera";
    let notice = obj.data("mediaNotice-" + id);
    if (!notice || !notice[0].isConnected) {
      notice = $(
        '<div role="alert" class="alert alert-warning my-2 media-notice">',
      ).css({ textAlign: "left", fontSize: 14 });
      obj.after(notice);
      obj.data("mediaNotice-" + id, notice);
    }
    notice.text(message).show();
    obj.trigger("media:error", [
      { message, name: error.name || "Error", kind: id, error },
    ]);
  }
  function available(target, kind) {
    if (!window.isSecureContext) {
      notify(
        target,
        new Error("媒体设备需要 HTTPS；本机也可通过 localhost 访问。"),
        kind,
      );
      return false;
    }
    if (
      !navigator.mediaDevices ||
      typeof navigator.mediaDevices.getUserMedia !== "function"
    ) {
      notify(
        target,
        new Error(
          "当前浏览器不支持媒体设备调用，请使用支持摄像头/麦克风的浏览器。",
        ),
        kind,
      );
      return false;
    }
    return true;
  }
  function clearNotice(target, kind) {
    const notice = $(target).data("mediaNotice-" + kind);
    if (notice) notice.hide();
  }
  function safePlay(media) {
    const promise = media.play();
    if (promise) promise.catch(function () {});
  }
  // Video Functions
  $.fn.camera = function (command, callback) {
    if (command === "setting" || command === "constraints")
      return JSON.parse(JSON.stringify(default_constraints));
    if (command === "e" || command === "enum") {
      const target = this[0];
      if (!target) return this;
      if (
        !available(target, "camera") ||
        !navigator.mediaDevices.enumerateDevices
      ) {
        if (typeof callback === "function") callback({});
        return this;
      }
      navigator.mediaDevices
        .enumerateDevices()
        .then((devices) => {
          const result = {};
          devices.forEach((device) =>
            (result[device.kind] || (result[device.kind] = [])).push(device),
          );
          if (!result.videoinput?.length)
            notify(
              target,
              new DOMException("未发现摄像头", "NotFoundError"),
              "camera",
            );
          if (typeof callback === "function") callback(result);
        })
        .catch((error) => {
          notify(target, error, "camera");
          if (typeof callback === "function") callback({});
        });
      return this;
    }
    let recording = false;
    this.each(function () {
      const video = this,
        $video = $(video);
      let state = $video.data("cameraState");
      if (!state) {
        state = {
          ns: ".mediaCamera" + ($.data(document,'jquerymediaDeviceSerial',++mediaSerial),mediaSerial),
          generation: 0,
          constraints: $.extend(true, {}, default_constraints),
          style: video.getAttribute("style"),
          originalSrc: video.getAttribute("src"),
          originalLoop: video.loop,
          timer: null,
        };
        $video.data("cameraState", state);
      }
      function stopRecording() {
        const recorder = $video.data("recorder");
        if (recorder && recorder.state !== "inactive") recorder.stop();
        $video.data("recorder", null);
        $(document).off(state.ns);
        $video.off(state.ns);
      }
      function close(noise) {
        state.generation++;
        $video.removeData("cameraRequest");
        stopRecording();
        const stream = $video.data("stream");
        if (stream) stream.getTracks().forEach((track) => track.stop());
        $video.data("stream", null);
        video.onloadedmetadata = null;
        if (video.srcObject) {
          video.pause();
          video.srcObject = null;
          if (noise) {
            video.loop=state.originalLoop;
            if(state.originalSrc){video.src=state.originalSrc;video.load();}
            else video.removeAttribute("src");
          }
        }
      }
      if (command === "destroy") {
        close(false);
        video.loop=state.originalLoop;
        if(state.originalSrc===null)video.removeAttribute("src");else video.setAttribute("src",state.originalSrc);
        if (state.style === null) video.removeAttribute("style");
        else video.setAttribute("style", state.style);
        $video.removeData("cameraState cameraConstraints");
        const notice = $video.data("mediaNotice-camera");
        if (notice) notice.remove();
        $video.removeData("mediaNotice-camera");
        return;
      }
      if (command === "c" || command === "close" || command === "stop") {
        close(true);
        if (typeof callback === "function") callback();
        return;
      }
      if (command === "n" || command === "snap") {
        if (video.readyState < 2 || !video.videoWidth) {
          notify(
            video,
            new Error("摄像头画面尚未就绪，请先开启摄像头后再截图。"),
            "camera",
          );
          return;
        }
        const canvas = document.createElement("canvas");
        canvas.width = $video.width() || video.videoWidth;
        canvas.height = $video.height() || video.videoHeight;
        canvas
          .getContext("2d")
          .drawImage(video, 0, 0, canvas.width, canvas.height);
        if (typeof callback === "function")
          callback(canvas.toDataURL("image/png"));
        return;
      }
      if (command === "r" || command === "record") {
        const stream = $video.data("stream");
        if (
          !stream ||
          !stream.getVideoTracks().some((track) => track.readyState === "live")
        ) {
          notify(
            video,
            new Error("摄像头尚未开启或已断开，请先开启摄像头。"),
            "camera",
          );
          return;
        }
        try {
          if (!window.MediaRecorder) throw new Error("当前浏览器不支持录像。");
          if ($video.data("recorder")) {
            stopRecording();
            return;
          }
          const type = [
            "video/mp4",
            "video/webm;codecs=vp8,opus",
            "video/webm",
          ].find((type) => MediaRecorder.isTypeSupported(type));
          const recorder = new MediaRecorder(
            stream,
            type ? { mimeType: type } : undefined,
          );
          let wasFullscreen = false;
          recorder.ondataavailable = (event) => {
            if (typeof callback === "function") callback(event);
          };
          recorder.onstop = () => {
            if ($video.data("recorder") === recorder)
              $video.data("recorder", null);
            $(document).off(state.ns);
            $video.off(state.ns);
          };
          $video.data("recorder", recorder);
          recorder.start();
          recording = true;
          $(document).on("fullscreenchange" + state.ns, () => {
            if (document.fullscreenElement === video) wasFullscreen = true;
            else if (wasFullscreen) stopRecording();
          });
          $video.on("pause" + state.ns, () => {
            if (document.fullscreenElement === video) $video.fullscreen("e");
          });
          $video.fullscreen("r");
        } catch (error) {
          notify(video, error, "camera");
        }
        return;
      }
      if (typeof command === "object")
        state.constraints = $.extend(true, {}, default_constraints, command);
      close(false);
      const constraints = $.extend(true, {}, state.constraints),
        id = state.generation;
      if (!available(video, "camera")) return;
      if (constraints.mirror) $video.css("transform", "rotateY(180deg)");
      else if (state.style === null) video.style.removeProperty("transform");
      video.volume = 0;
      clearNotice(video, "camera");
      $video.data("cameraRequest", id);
      navigator.mediaDevices
        .getUserMedia(constraints)
        .then((stream) => {
          if (id !== state.generation) {
            stream.getTracks().forEach((track) => track.stop());
            return;
          }
          if (constraints.video !== false && !stream.getVideoTracks().length) {
            stream.getTracks().forEach((track) => track.stop());
            throw new DOMException("未检测到可用摄像头", "NotFoundError");
          }
          $video.removeData("cameraRequest");
          video.onloadedmetadata = (event) => {
            if (id !== state.generation) return;
            if (typeof callback === "function") callback(event);
            safePlay(video);
            $video.trigger("media:ready");
          };
          video.srcObject = stream;
          video.muted = true;
          video.setAttribute("playsinline", "");
          $video.data("stream", stream).data("recorder", null);
          clearNotice(video, "camera");
          safePlay(video);
        })
        .catch((error) => {
          if (id === state.generation) {
            $video.removeData("cameraRequest");
            notify(video, error, "camera");
          }
        });
    });
    return command === "r" || command === "record" ? recording : this;
  };

  //audio Functions
  $.fn.audio = function (opt) {
    if(opt==null)opt={};
    if(opt&&typeof opt==="object"&&opt.device_id!==undefined)opt=$.extend({},opt,{deviceId:opt.device_id});
    if(opt&&typeof opt.func==="string")opt=$.extend({},opt,{func:window[opt.func]});
    if (this.length > 1) {
      const args = arguments;
      this.each(function () {
        $.fn.audio.apply($(this), args);
      });
      return this;
    }
    if (!this.length) return this;
    if (["stop", "close", "destroy"].includes(opt)) {
      const para = this.data("para");
      if (para) {
        para.disposed = opt === "destroy";
        if (para.cleanup) para.cleanup();
        para.recordGeneration = (para.recordGeneration || 0) + 1;
        this.removeData("recordingPending");
        const recorder = this.data("mediaRecorder");
        if (recorder && recorder.state !== "inactive") recorder.stop();
        this.data("mediaRecorder", null);
        if (opt === "destroy") {
          $(window).off(para.ns);
          $(document).off(para.ns);
          if (para.audioContext) para.audioContext.close().catch(() => {});
          if (para.ownedCanvas) $(this.data("canvas")).remove();
          const notice=this.data("mediaNotice-audio");if(notice)notice.remove();
          this.removeData("audio canvas para stop mediaNotice-audio");
        }
      }
      return this;
    }
    if (opt === "enum") {
      const callback = arguments[1],
        target = this[0];
      if (!available(target, "audio")) {
        if (typeof callback === "function") callback([]);
        return this;
      }
      navigator.mediaDevices
        .enumerateDevices()
        .then((devices) => {
          if (typeof callback === "function")
            callback(devices.filter((device) => device.kind === "audioinput"));
        })
        .catch((error) => {
          notify(target, error, "audio");
          if (typeof callback === "function") callback([]);
        });
      return this;
    }
    let canvasObj, audioObj, func_show, dataArray;
    let para = {},
      mainObj = this,
      constraints = {
        audio: $.extend(true, {}, default_constraints.audio),
        video: false,
      },
      effect_func = [effect_1, effect_2, effect_3];
    if (this.data("audio") === undefined) {
      canvasObj = this.get(0);
      audioObj = this.get(0);
      let tagName = canvasObj.tagName.toLowerCase();
      if (tagName === "audio") {
        canvasObj = document.createElement("canvas");
        $(canvasObj).insertAfter(audioObj).css({ width: "100%", height: 100 });
      } else {
        if (tagName !== "canvas") {
          canvasObj = document.createElement("canvas");
          $(canvasObj)
            .insertAfter(audioObj)
            .css({ width: "100%", height: 100 });
          this.append(canvasObj);
        }
        audioObj = new Audio();
      }
      this.data("canvas", canvasObj);
      this.data("audio", audioObj);
      this.data("stop", "n");
      para.ns = ".mediaAudio" + ($.data(document,'jquerymediaDeviceSerial',++mediaSerial),mediaSerial);
      para.ownedCanvas = canvasObj !== this[0];
      try {
        init_audio();
        init_canvas();
      } catch (error) {
        this.removeData("audio canvas");
        if (para.ownedCanvas) $(canvasObj).remove();
        if (para.audioContext) para.audioContext.close().catch(() => {});
        notify(this[0], error, "audio");
        return this;
      }
      mainObj.data("para", para);
      $(window).on("pagehide" + para.ns, () => mainObj.audio("stop"));
    } else {
      canvasObj = this.data("canvas");
      audioObj = this.data("audio");
      para = this.data("para");
    }
    para.cleanup = () => {
      para.waveGeneration = (para.waveGeneration || 0) + 1;
      mainObj.data("stop", "y");
      cancelAnimationFrame(para.frame);
      clearTimeout(para.waveTimer);
      if (para.waveStream) {
        para.waveStream.getTracks().forEach((track) => track.stop());
        para.waveStream = null;
      }
      if (para.waveSource) {
        para.waveSource.disconnect();
        para.waveSource = null;
      }
      if (para.mediaElementSource) para.mediaElementSource.disconnect();
      if (para.audioHandlers) para.audioHandlers();
      audioObj.onpause = audioObj.onended = null;
      audioObj.pause();
    };
    // 初始化变量
    dataArray = new Uint8Array(para.audioAnalyser.frequencyBinCount);

    if (typeof arguments[1] === "undefined") {
      arguments[1] = function () {
        return true;
      };
    }
    if (typeof opt === "string") {
      switch (opt) {
        case "enum":
          enumerate(arguments[1]);
          break;
        case "record":
          return record(arguments[1], arguments[2]);
      }
    } else if (typeof opt === "object") {
      Object.keys(default_constraints.audio).forEach((key) => {
        if (typeof opt[key] !== "undefined") {
          constraints.audio[key] = opt[key];
        }
      });
      if (typeof opt.deviceId !== "undefined") constraints.audio.deviceId=opt.deviceId;
      para.constraints=$.extend(true,{},constraints);
      if (typeof opt.file !== "undefined") {
        musicWave();
      } else {
        soundWave();
      }
    }

    function init_canvas() {
      para.canvasCtx = canvasObj.getContext("2d");
      para.canvasWidth = canvasObj.offsetWidth;
      para.canvasHeight = canvasObj.offsetHeight;
      canvasObj.width = Math.max(1, para.canvasWidth);
      canvasObj.height = Math.max(1, para.canvasHeight);
      para.gradient = para.canvasCtx.createLinearGradient(
        0,
        0,
        para.canvasWidth / 3,
        para.canvasHeight,
      );
      para.gradient.addColorStop(0, "#f500d8");
      para.gradient.addColorStop(1, "#ceaf11");
      $(window).on("resize" + para.ns, function () {
        para.canvasWidth = canvasObj.offsetWidth;
        para.canvasHeight = canvasObj.offsetHeight;
        canvasObj.width = Math.max(1, para.canvasWidth);
        canvasObj.height = Math.max(1, para.canvasHeight);
        para.gradient = para.canvasCtx.createLinearGradient(
          0,
          0,
          para.canvasWidth / 3,
          para.canvasHeight,
        );
        para.gradient.addColorStop(0, "#f500d8");
        para.gradient.addColorStop(1, "#ceaf11");
      });
    }

    function init_audio() {
      // 创建音频对象
      para.audioContext = new (window.AudioContext ||
        window.webkitAudioContext ||
        window.mozAudioContext)();
      // 获取声音源
      // 获取分析对象
      para.audioAnalyser = para.audioContext.createAnalyser();
      para.audioAnalyser.minDecibels = -90;
      para.audioAnalyser.maxDecibels = -10;
      para.audioAnalyser.smoothingTimeConstant = 0.85;
      para.audioAnalyser.fftSize = 1024;
      // 获取其他对象
    }

    function beginWave() {
      para.cleanup();
      para.disposed = false;
      func_show =
        typeof opt.func === "function"
          ? opt.func
          : effect_func[Math.floor(3 * Math.random())];
      return para.waveGeneration;
    }
    function drawWave(id) {
      if (para.disposed || id !== para.waveGeneration) return;
      mainObj.data("stop", "n");
      show();
    }
    function musicWave() {
      const id = beginWave();
      function broken() {
        if (id === para.waveGeneration)
          notify(
            mainObj[0],
            new Error("音频文件无法播放，请重新选择音乐或检查文件是否存在。"),
            "audio",
          );
      }
      async function ready() {
        if (id !== para.waveGeneration || para.disposed) return;
        try {
          await para.audioContext.resume();
          if (id !== para.waveGeneration || para.disposed) return;
          if (!para.mediaElementSource)
            para.mediaElementSource =
              para.audioContext.createMediaElementSource(audioObj);
          para.mediaElementSource.disconnect();
          para.mediaElementSource.connect(para.audioAnalyser);
          para.mediaElementSource.connect(para.audioContext.destination);
          cancelAnimationFrame(para.frame);
          drawWave(id);
        } catch (error) {
          if (id === para.waveGeneration) broken();
        }
      }
      audioObj.addEventListener("play", ready);
      audioObj.addEventListener("error", broken);
      para.audioHandlers = () => {
        audioObj.removeEventListener("play", ready);
        audioObj.removeEventListener("error", broken);
      };
      audioObj.onpause = () => {
        if(id===para.waveGeneration){cancelAnimationFrame(para.frame);mainObj.data("stop","y");}
      };
      audioObj.onended = () => {
        if (id === para.waveGeneration) para.cleanup();
      };
      audioObj.autoplay = true;
      audioObj.controls = true;
      audioObj.src = opt.file;
      safePlay(audioObj);
    }
    function soundWave() {
      const id = beginWave();
      if (!available(mainObj[0], "audio")) return;
      navigator.mediaDevices
        .getUserMedia(constraints)
        .then(async (stream) => {
          if (para.disposed || id !== para.waveGeneration) {
            stream.getTracks().forEach((track) => track.stop());
            return;
          }
          para.waveStream = stream;
          try {
            await para.audioContext.resume();
            if (para.disposed || id !== para.waveGeneration) {
              stream.getTracks().forEach((track) => track.stop());
              return;
            }
            para.waveSource = para.audioContext.createMediaStreamSource(stream);
            para.waveSource.connect(para.audioAnalyser);
            drawWave(id);
          } catch (error) {
            stream.getTracks().forEach((track) => track.stop());
            if (id === para.waveGeneration) notify(mainObj[0], error, "audio");
          }
        })
        .catch((error) => {
          if (id === para.waveGeneration) notify(mainObj[0], error, "audio");
        });
    }

    function show() {
      if (mainObj.data("stop") === "y") return;
      cancelAnimationFrame(para.frame);
      para.frame = requestAnimationFrame(show);
      if (typeof para.audioAnalyser === "undefined") return;
      para.audioAnalyser.getByteFrequencyData(dataArray);
      para.canvasCtx.clearRect(0, 0, para.canvasWidth, para.canvasHeight);
      para.canvasCtx.fillStyle = "rgb(240,240,240)";
      para.canvasCtx.fillRect(0, 0, para.canvasWidth, para.canvasHeight);
      func_show(dataArray, para.canvasCtx, para.canvasWidth, para.canvasHeight);
    }

    function record(func, format) {
      if(para.constraints)constraints=$.extend(true,{},para.constraints);
      if (!available(mainObj[0], "audio")) return false;
      if (!window.MediaRecorder) {
        notify(mainObj[0], new Error("当前浏览器不支持录音。"), "audio");
        return false;
      }
      if (mainObj.data("recordingPending")) {
        para.recordGeneration = (para.recordGeneration || 0) + 1;
        mainObj.removeData("recordingPending");
        return false;
      }
      const active = mainObj.data("mediaRecorder");
      if (active) {
        if (active.state !== "inactive") active.stop();
        return false;
      }
      const request = (para.recordGeneration =
        (para.recordGeneration || 0) + 1);
      mainObj.data("recordingPending", true);
      navigator.mediaDevices
        .getUserMedia(constraints)
        .then((stream) => {
          if (para.disposed || request !== para.recordGeneration) {
            stream.getTracks().forEach((track) => track.stop());
            return;
          }
          mainObj.removeData("recordingPending");
          clearNotice(mainObj[0], "audio");
          try {
            const type = ["audio/webm", "audio/mp4"].find((type) =>
              MediaRecorder.isTypeSupported(type),
            );
            const recorder = new MediaRecorder(
              stream,
              type ? { mimeType: type } : undefined,
            );
            recorder.ondataavailable = async (event) => {
              if (
                !event.data.size ||
                para.disposed ||
                request !== para.recordGeneration
              )
                return;
              try {
                let result = event.data;
                if (format !== undefined) {
                  const buffer = await event.data.arrayBuffer();
                  const audioBuffer =
                    await para.audioContext.decodeAudioData(buffer);
                  if (para.disposed || request !== para.recordGeneration)
                    return;
                  result = buffer2wav(audioBuffer, format === "mp3");
                }
                if (typeof func === "function") func(result);
              } catch (error) {
                if (!para.disposed && request === para.recordGeneration)
                  notify(mainObj[0], error, "audio");
              }
            };
            recorder.onstop = () => {
              stream.getTracks().forEach((track) => track.stop());
              if (mainObj.data("mediaRecorder") === recorder)
                mainObj.data("mediaRecorder", null);
            };
            recorder.onerror = (event) => {
              stream.getTracks().forEach((track) => track.stop());
              if (mainObj.data("mediaRecorder") === recorder)
                mainObj.data("mediaRecorder", null);
              if (!para.disposed)
                notify(
                  mainObj[0],
                  event.error || new Error("录音失败，请重试。"),
                  "audio",
                );
            };
            mainObj.data("mediaRecorder", recorder);
            recorder.start();
          } catch (error) {
            stream.getTracks().forEach((track) => track.stop());
            mainObj.data("mediaRecorder", null);
            throw error;
          }
        })
        .catch((error) => {
          if (!para.disposed && request === para.recordGeneration) {
            mainObj.removeData("recordingPending");
            notify(mainObj[0], error, "audio");
          }
        });
      return true;
    }

    function effect_1(data, canvas, width, height) {
      //音柱
      let length = data.length;
      let barWidth = 10;
      let barHeight;
      let x = 0;
      for (let i = 0; i < length; i++) {
        barHeight = (data[i] / 256) * height * 1.3;
        canvas.fillStyle = "rgb(" + (barHeight + 150) + ",50,50)";
        canvas.fillRect(x, height - barHeight, barWidth, barHeight);
        x += barWidth + 1;
      }
    }

    function effect_2(data, canvas, width, height) {
      //音波
      let length = data.length;
      let step = width / length;
      let x = 0;
      canvas.beginPath();
      canvas.moveTo(0, height);
      for (let i = 0; i < length; i++) {
        canvas.lineTo(x, height - (data[i] / 256) * height);
        x += step;
      }
      canvas.fillStyle = para.gradient;
      canvas.fill();
      canvas.closePath();

      canvas.beginPath();
      canvas.moveTo(0, height);
      x = 0;
      for (let i = 0; i < length; i++) {
        canvas.lineTo(
          x,
          height - (data[i] / 256) * height - Math.random() * 30,
        );
        x += step;
      }
      canvas.strokeStyle = para.gradient;
      canvas.stroke();
      canvas.closePath();
    }

    function effect_3(data, canvas, width, height) {
      //横条
      let length = data.length;
      let barLength = 0;
      let tmp = 0;
      for (let i = 0; i < length; i++) {
        if (data[i] < tmp) break;
        tmp = data[i];
      }
      barLength = width * Math.pow(tmp / 256, 2) * 0.7;
      canvas.fillStyle = "rgb(" + (barLength + 50) + ",50,50)";
      canvas.fillRect(0, 0, barLength, height);
    }

    // 将 webm 格式的浏览器音频转码为 wav
    function buffer2wav(aBuffer, mp3) {
      let numOfChan = aBuffer.numberOfChannels,
        btwLength = aBuffer.length * numOfChan * 2 + 44,
        btwArrBuff = new ArrayBuffer(btwLength),
        btwView = new DataView(btwArrBuff),
        btwChnls = [],
        btwIndex,
        btwSample,
        btwOffset = 0,
        btwPos = 0;
      setUint32(0x46464952); // "RIFF"
      setUint32(btwLength - 8); // file length - 8
      setUint32(0x45564157); // "WAVE"
      setUint32(0x20746d66); // "fmt " chunk
      setUint32(16); // length = 16
      setUint16(1); // PCM (uncompressed)
      setUint16(numOfChan);
      setUint32(aBuffer.sampleRate);
      setUint32(aBuffer.sampleRate * 2 * numOfChan); // avg. bytes/sec
      setUint16(numOfChan * 2); // block-align
      setUint16(16); // 16-bit
      setUint32(0x61746164); // "data" - chunk
      setUint32(btwLength - btwPos - 4); // chunk length

      function setUint16(data) {
        btwView.setUint16(btwPos, data, true);
        btwPos += 2;
      }

      function setUint32(data) {
        btwView.setUint32(btwPos, data, true);
        btwPos += 4;
      }

      for (btwIndex = 0; btwIndex < aBuffer.numberOfChannels; btwIndex++)
        btwChnls.push(aBuffer.getChannelData(btwIndex));

      while (btwPos < btwLength) {
        for (btwIndex = 0; btwIndex < numOfChan; btwIndex++) {
          // interleave btwChnls
          btwSample = Math.max(-1, Math.min(1, btwChnls[btwIndex][btwOffset])); // clamp
          btwSample =
            (btwSample < 0 ? btwSample * 32768 : btwSample * 32767) | 0; // scale to 16-bit signed int
          btwView.setInt16(btwPos, btwSample, true); // write 16-bit sample
          btwPos += 2;
        }
        btwOffset++; // next source sample
      }

      if(!mp3)return new Blob([btwArrBuff], {type:"audio/wav"});
      if(!window.lamejs||typeof window.lamejs.Mp3Encoder!=="function")throw new Error("导出 MP3 需要先加载 lamejs；也可使用 WAV 格式。");
      return wav2mp3(numOfChan,aBuffer.sampleRate,new Int16Array(btwArrBuff,44));
    }

    // 将 wav 编码转换为 mp3（需要 lamejs 支持）
    function wav2mp3(channels, sampleRate, samples) {
      if(channels!==1&&channels!==2)throw new Error("MP3 仅支持单声道或双声道。");
      const buffer=[],encoder=new window.lamejs.Mp3Encoder(channels,sampleRate,128),frames=samples.length/channels;
      const left=new Int16Array(frames),right=channels===2?new Int16Array(frames):null;
      for(let i=0;i<frames;i++){left[i]=samples[i*channels];if(right)right[i]=samples[i*channels+1];}
      for(let i=0;i<frames;i+=1152){const encoded=encoder.encodeBuffer(left.subarray(i,i+1152),right?right.subarray(i,i+1152):undefined);if(encoded.length)buffer.push(new Int8Array(encoded));}
      const tail=encoder.flush();if(tail.length)buffer.push(new Int8Array(tail));
      return new Blob(buffer,{type:"audio/mp3"});
    }

    return this;
  };

  // Native fullscreen uses the browser's actual element, including Escape exits.
  $.fn.fullscreen = function (command, callback) {
    const el = this[0];
    if (!el) return command === "check" || command === "c" ? false : this;
    const current = () =>
      document.fullscreenElement || document.webkitFullscreenElement;
    if (command === "check" || command === "c") return current() === el;
    if (typeof command === "function") {
      callback = command;
      command = undefined;
    }
    if (command === undefined) command = current() === el ? "exit" : "run";
    if (!["run", "r", "exit", "e"].includes(command)) return current();
    const fn =
      command === "exit" || command === "e"
        ? document.exitFullscreen || document.webkitExitFullscreen
        : el.requestFullscreen || el.webkitRequestFullscreen;
    if (!fn) return this;
    try {
      const result = fn.call(
        command === "exit" || command === "e" ? document : el,
      );
      Promise.resolve(result)
        .then(() => {
          if (typeof callback === "function") callback();
        })
        .catch((error) => notify(el, error, "camera"));
    } catch (error) {
      notify(el, error, "camera");
    }
    return this;
  };
})(jQuery);

// dataURI 转 blob
function dataURItoBlob(dataURI) {
  // convert base64/URLEncoded data component to raw binary data held in a string
  let byteString;
  if (dataURI.split(",")[0].indexOf("base64") >= 0) {
    byteString = atob(dataURI.split(",")[1]);
  } else {
    byteString = unescape(dataURI.split(",")[1]);
  }

  // separate out the mime component
  const mimeString = dataURI.split(",")[0].split(":")[1].split(";")[0];

  // write the bytes of the string to a typed array
  const ia = new Uint8Array(byteString.length);
  for (let i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }

  return new Blob([ia], { type: mimeString });
}

// blob 转 dataURI
function BlobToDataURL(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (_e) => resolve(reader.result);
    reader.onerror = (_e) => reject(reader.error);
    reader.onabort = (_e) => reject(new Error("Read aborted"));
    reader.readAsDataURL(blob);
  });
}
