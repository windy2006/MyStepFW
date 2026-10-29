/**
 * 框架调用：resizeImg(options|"destroy")
 * 功能说明：options 可为每次选图求值的函数；callback(dataURL) 仅接收最新缩图结果，error(Error) 反馈失败；destroy 恢复输入属性。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/** jquery.resizeImg.js — Windy2000, Apache-2.0. https://github.com/windy2006/jquery.resizeImg */
(function ($) {
  "use strict";
  let serial=$.data(document,'jqueryresizeImgSerial')||0;
  const defaults = {
    mode: 0,
    val: 400,
    type: "image/jpeg",
    quality: 0.8,
    capture: true,
    before: () => {},
    callback: () => {},
    error: () => {},
  };
  $.fn.resizeImg = function (options) {
    return this.each(function () {
      if(typeof options==='string'){const instance=$(this).data('resizeImg');if(options!=='destroy')throw new Error('Unknown resizeImg method: '+options);if(instance)instance.destroy();return;}
      const input = this,
        $input = $(input),
        old = $input.data("resizeImg");
      if (old) old.destroy();
      if(input.tagName!=="INPUT"||input.type!=="file")return;
      const ns = ".resizeImg" + ($.data(document,'jqueryresizeImgSerial',++serial),serial),
        attrs = {
          accept: input.getAttribute("accept"),
          capture: input.getAttribute("capture"),
        };
      let generation = 0,
        pending = null,
        disposed = false;
      function config() {
        return $.extend(
          {},
          defaults,
          typeof options === "function" ? options.call(input) : options,
        );
      }
      // A dynamic configuration is evaluated per file, as in the original API.
      if ((typeof options === "function" ? defaults : config()).capture)
        $input.attr({ accept: "image/*", capture: "true" });
      function cancel() {
        generation++;
        if (pending) {
          pending();
          pending = null;
        }
      }
      function fail(error, opt) {
        if (disposed) return;
        try {
          if (typeof opt.error === "function") opt.error.call(input, error);
        } finally {
          $input.trigger("resizeImg:error", [error]);
        }
      }
      $input.on("change" + ns, function () {
        cancel();
        const file = input.files && input.files[0];
        if (!file) return;
        const id = generation;
        let opt;
        try {
          opt = config();
          if (
            typeof opt.before === "function" &&
            opt.before.call(input, file) === false
          )
            return;
          const mode = Number(opt.mode),
            value = Number(opt.val),
            quality = Number(opt.quality);
          if (
            ![0, 1, 2].includes(mode) ||
            !Number.isFinite(value) ||
            value <= 0
          )
            throw new Error("缩图模式或尺寸参数无效。");
          if (!["image/jpeg", "image/png"].includes(opt.type))
            throw new Error("输出格式仅支持 image/jpeg 或 image/png。");
          if (!Number.isFinite(quality) || quality < 0 || quality > 1)
            throw new Error("图片质量必须介于 0 和 1。");
          if (file.type && !file.type.startsWith("image/"))
            throw new Error("请选择图片文件。");
          if (!file.size) throw new Error("图片文件为空。");
          const image = new Image(),
            url = URL.createObjectURL(file);
          let cleaned = false;
          function cleanup() {
            if (cleaned) return;
            cleaned = true;
            image.onload = image.onerror = null;
            image.removeAttribute("src");
            URL.revokeObjectURL(url);
            if (pending === cleanup) pending = null;
          }
          pending = cleanup;
          image.onerror = () => {
            cleanup();
            if (id === generation)
              fail(
                new Error("图片无法解码，文件可能损坏或格式不受支持。"),
                opt,
              );
          };
          image.onload = () => {
            if (id !== generation || disposed) {
              cleanup();
              return;
            }
            try {
              // Native image decoding applies orientation consistently with browser display.
              const width = image.naturalWidth,
                height = image.naturalHeight;
              if (!width || !height) throw new Error("无法读取图片尺寸。");
              const ratio =
                mode === 0
                  ? value / width
                  : mode === 1
                    ? value
                    : Math.sqrt((value * 1024) / file.size);
              const w = Math.max(1, Math.round(width * ratio)),
                h = Math.max(1, Math.round(height * ratio));
              if (
                !Number.isSafeInteger(w) ||
                !Number.isSafeInteger(h) ||
                w * h > 40_000_000 ||
                w > 32767 ||
                h > 32767
              )
                throw new Error("目标图片尺寸过大，请降低尺寸或比例。");
              const canvas = document.createElement("canvas");
              canvas.width = w;
              canvas.height = h;
              const ctx = canvas.getContext("2d");
              if (!ctx) throw new Error("当前浏览器无法创建图片画布。");
              ctx.drawImage(image, 0, 0, w, h);
              const result = canvas.toDataURL(opt.type, quality);
              if (result === "data:,")
                throw new Error("图片编码失败，请降低输出尺寸。");
              cleanup();
              if (id === generation && typeof opt.callback === "function")
                opt.callback.call(input, result);
            } catch (error) {
              cleanup();
              if (id === generation) fail(error, opt);
            }
          };
          image.src = url;
        } catch (error) {
          if (id === generation) fail(error, opt || defaults);
        }
      });
      $input.data("resizeImg", {
        destroy() {
          disposed = true;
          cancel();
          $input.off(ns).removeData("resizeImg");
          Object.entries(attrs).forEach(([key, value]) =>
            value === null
              ? input.removeAttribute(key)
              : input.setAttribute(key, value),
          );
        },
      });
    });
  };
})(jQuery);
