/**
 * 框架调用：powerUpload(options|command[,files])
 * 功能说明：返回原选择集；命令为 browse/start/cancel/destroy；上传回调保持四参数并追加所属弹窗，回调异常触发 upload:callbackerror。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/** jquery.powerUpload.js — Windy2000, Apache-2.0. https://github.com/windy2006/jquery.powerUpload */
(function ($) {
  "use strict";
  let serial=$.data(document,'jquerypowerUploadSerial')||0;
  const noop = () => true;
  const errors = [
    "Browser Not Supported",
    "Too Many Files",
    "File Too Large",
    "No Upload Script Set",
    "Choose a file at least",
    "Error occurred when send data",
  ];
  const defaults = {
    title: "Power Uploader",
    url: "",
    refresh: 1000,
    param_name: "userfile",
    max_files: 1,
    max_file_size: 10,
    data: {},
    mode: "drop",
    errors: [],
    drop: noop,
    file: noop,
    paste: noop,
    dragEnter: noop,
    dragOver: noop,
    dragLeave: noop,
    docEnter: noop,
    docOver: noop,
    docLeave: noop,
    beforeUpload: noop,
    beforeEach: noop,
    allDone: noop,
    uploadStarted: noop,
    uploadFinished: noop,
    progressUpdated: noop,
    speedUpdated: noop,
    timeout: 0,
  };
  $.fn.powerUpload = function (options) {
    const commandArgument=arguments[1];
    return this.each(function () {
      if(typeof options==='string'){const instance=$(this).data('powerUpload');if(!['destroy','cancel','browse','start'].includes(options))throw new Error('Unknown powerUpload method: '+options);if(instance)instance[options](commandArgument);return;}
      const target = this,
        $target = $(target),
        old = $target.data("powerUpload");
      if(options&&typeof options==='object'){
        options=$.extend({},options);
        if(options.max_files===undefined&&options.maxfiles!==undefined)options.max_files=options.maxfiles;
        if(options.max_file_size===undefined&&options.maxfilesize!==undefined)options.max_file_size=options.maxfilesize;
      }
      const previousOptions = $target.data("upload");
      if (old) old.destroy();
      const opt = $.extend({}, defaults, previousOptions, options),
        id = ($.data(document,'jquerypowerUploadSerial',++serial),serial),
        ns = ".powerUpload" + id;
      let modalPhase="new";
      let modal = null,
        batch = null,
        disposed = false,
        selected = [],
        leaveTimer;
      const requests = new Set();
      function isBlob(file){return !!file&&typeof file.size==='number'&&typeof file.slice==='function'&&typeof file.type==='string';}
      function callback(name, ...args) {
        if (typeof opt[name] === "function"){
          try{return opt[name].apply(opt,args);}catch(error){$target.trigger('upload:callbackerror',[error,name]);return false;}
        }
        return true;
      }
      function error(index, file, position, message) {
        const text = message || (opt.errors||[])[index] || errors[index];
        if (typeof opt.error === "function")
          callback("error",text,file,position);
        else window.alert(text);
        $target.trigger("upload:error", [
          { message: text, file, index: position },
        ]);
      }
      function getModal() {
        if (modal) return modal;
        modal = $(
          '<div class="modal fade" data-bs-backdrop="static" data-backdrop="static" tabindex="-1" role="dialog">',
        ).attr(
          "id",
          document.getElementById("uploader") ? "uploader-" + id : "uploader",
        );
        modal
          .append(
            '<div class="modal-dialog modal-dialog-scrollable" role="document"><div class="modal-content"><div class="modal-header py-0 bg-light"><h4 class="modal-title"><span class="glyphicon glyphicon-upload"></span> <b></b></h4><button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"></div><div class="modal-footer py-2"></div></div></div>',
          )
          .appendTo(document.body);
        modal.attr('data-power-upload-id',id);
        // Without Bootstrap, dismiss controls still close their own dialog.
        modal.on('click'+ns,'[data-dismiss=modal],[data-bs-dismiss=modal]',function(event){if(!$.fn.modal){event.preventDefault();modal.hide().trigger('hidden.bs.modal');}});
        modal.on('show.bs.modal'+ns,()=>{modalPhase='showing';})
          .on('shown.bs.modal'+ns,()=>{modalPhase='shown';})
          .on('hide.bs.modal'+ns,()=>{modalPhase='hiding';})
          .on('hidden.bs.modal'+ns,()=>{modalPhase='hidden';if($('.modal.show').length)$(document.body).addClass('modal-open');});
        modal.find(".modal-title b").text(opt.title);
        return modal;
      }
      function show() {
        const m = getModal(),
          other = $("#uploader");
        // Keep the historical ID for the foreground dialog, while retaining
        // private DOM references for every background request and progress bar.
        if (other.length && other[0] !== m[0]) {
          other.attr("id", "uploader-" + (other.attr("data-power-upload-id")||id));
          if ($.fn.modal) other.modal("hide");
        }
        m.attr("id", "uploader");
        if ($.fn.modal) m.modal("show");
        else m.show();
        return m;
      }
      function finish(state) {
        if (state !== batch || state.finished || state.done !== state.total)
          return;
        state.finished = true;
        if (disposed) return;
        callback("allDone", modal);
        $target.trigger("upload:complete", [state]);
      }
      function cancel() {
        const state = batch;
        if (!state || state.finished) return;
        state.cancelled = true;
        // Aborts finish their individual items exactly once before the batch settles.
        Array.from(requests).forEach((xhr) => xhr.abort());
        finish(state);
      }
      function start(list) {
        if (disposed) return;
        if (batch && !batch.finished) {
          error(5, null, null, "当前批次尚未结束，请等待完成或取消后重试。");
          return;
        }
        const files = Array.from(list || []).map((file, i) =>
          isBlob(file) && typeof file.name === "string"
            ? file
            : isBlob(file)
              ? new File(
                  [file],
                  "blob_" +
                    i +
                    "." +
                    (file.type === "image/png" ? "png" : "bin"),
                  { type: file.type },
                )
              : file,
        );
        if (!files.length) {
          error(4);
          return;
        }
        if (files.length > Number(opt.max_files)) {
          error(1);
          return;
        }
        if (!opt.url) {
          error(3);
          return;
        }
        if (callback("beforeUpload") === false) return;
        const m = show();
        m.find(".modal-body,.modal-footer").empty();
        const state = {
          total: files.length,
          done: 0,
          finished: false,
          cancelled: false,
        };
        batch = state;
        m.find(".modal-footer").append(
          '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">关闭</button>',
        );
        files.forEach((file, index) => {
          if (disposed) return;
          if (state.cancelled) {
            state.done++;
            finish(state);
            return;
          }
          if (!(isBlob(file))) {
            state.done++;
            error(0, file, index);
            finish(state);
            return;
          }
          if (file.size > Number(opt.max_file_size) * 1048576) {
            state.done++;
            error(2, file, index);
            finish(state);
            return;
          }
          let accepted;
          try {
            accepted = callback("beforeEach", file) !== false;
          } catch (e) {
            error(5, file, index, e.message);
            accepted = false;
          }
          if (!accepted) {
            state.done++;
            finish(state);
            return;
          }
          const row = $('<div class="progress mb-3">')
            .attr("data-idx", index)
            .appendTo(m.find(".modal-body"));
          const bar = $(
            '<div class="progress-bar progress-bar-striped progress-bar-animated text-left p-2" role="progressbar">',
          )
            .css("width", "0%")
            .text(file.name)
            .appendTo(row);
          const xhr = new XMLHttpRequest(),
            started = Date.now();
          let settled = false,
            lastTime = started,
            lastLoaded = 0,
            lastProgress = -1;
          function settle(info, success) {
            if (settled) return;
            settled = true;
            requests.delete(xhr);
            state.done++;
            bar
              .removeClass("progress-bar-animated")
              .toggleClass("bg-success", success)
              .toggleClass("bg-danger", !success)
              .css("width", "100%")
              .text(
                file.name +
                  (success ? "" : " - " + (info.message || "上传失败")),
              );
            if (disposed) return;
            try {
              if (
                callback(
                  "uploadFinished",
                  index,
                  file,
                  info,
                  Date.now() - started,
                  m,
                ) === false
              ) {
                state.cancelled = true;
                Array.from(requests).forEach((request) => request.abort());
              }
            } finally {
              finish(state);
            }
          }
          xhr.upload.addEventListener("progress", (event) => {
            if (disposed || settled || !event.lengthComputable) return;
            const percent = Math.round((event.loaded / event.total) * 100);
            bar.css("width", percent + "%");
            if (percent !== lastProgress) {
              lastProgress = percent;
              callback("progressUpdated", index, file, percent);
            }
            const now = Date.now(),
              elapsed = now - lastTime;
            if (elapsed >= Math.max(1, Number(opt.refresh) || 1000)) {
              const speed = (event.loaded - lastLoaded) / elapsed;
              bar.text(file.name + " (" + Math.round(speed) + "KB/S)");
              callback("speedUpdated", index, file, speed);
              lastTime = now;
              lastLoaded = event.loaded;
            }
          });
          xhr.onload = () => {
            let info;
            try {
              info = JSON.parse(xhr.responseText);
            } catch (e) {
              info = { error: 1, message: "服务器返回了无效的 JSON 响应。" };
            }
            if (!info || typeof info !== "object")
              info = { error: 1, message: "服务器返回了无效的上传结果。" };
            const success =
              xhr.status >= 200 && xhr.status < 300 &&
              (!Object.prototype.hasOwnProperty.call(info,'error')||info.error===0||info.error==='0'||info.error===false);
            if (xhr.status < 200 || xhr.status >= 300)
              info = $.extend({}, info, {
                error: info.error || 1,
                message: info.message || "上传失败：HTTP " + xhr.status,
                status: xhr.status,
              });
            settle(info, success);
          };
          xhr.onerror = () =>
            settle({ error: 1, message: "网络连接失败，请重试。" }, false);
          xhr.ontimeout = () =>
            settle({ error: 1, message: "上传超时，请重试。" }, false);
          xhr.onabort = () =>
            settle(
              { error: 1, cancelled: true, message: "上传已取消。" },
              false,
            );
          try {
            const data = new FormData();
            Object.entries(opt.data || {}).forEach(([key, value]) => {
              data.append(key, typeof value === "function" ? value() : value);
            });
            data.append(opt.param_name, file, file.name);
            xhr.open("POST", opt.url, true);
            xhr.timeout = Math.max(0, Number(opt.timeout) || 0);
            requests.add(xhr);
            callback("uploadStarted", index, file, files.length);
            if (disposed || state.cancelled) {
              requests.delete(xhr);
              settle(
                { error: 1, cancelled: true, message: "上传已取消。" },
                false,
              );
              return;
            }
            xhr.send(data);
          } catch (e) {
            settle({ error: 1, message: e.message }, false);
          }
        });
        finish(state);
      }
      function browse() {
        if (batch && !batch.finished) {
          show();
          return;
        }
        const m = show();
        selected = [];
        m.find(".modal-body,.modal-footer").empty();
        const field = $(
          '<div class="input-group mb-3"><div class="input-group-prepend"><span class="input-group-text">文件</span></div><div class="custom-file"><label><span class="custom-file-label nowrap" data-browse="浏览">点击选取文件</span></label></div></div>',
        ).appendTo(m.find(".modal-body"));
        const input = $(
          '<input type="file" multiple class="custom-file-input">',
        )
          .attr(opt.obj_attr || {})
          .prependTo(field.find("label"));
        const list = $('<div class="list-group w-100 mb-3">').appendTo(
          m.find(".modal-body"),
        );
        input.on("change" + ns, function () {
          if (
            callback("file", {
              target: this,
              originalEvent: { target: this },
            }) === false
          )
            return;
          const next = Array.from(this.files || []);
          if (selected.length + next.length > Number(opt.max_files)) {
            error(1);
            this.value = "";
            return;
          }
          next.forEach((file) => {
            selected.push(file);
            const item = $('<div class="list-group-item list-group-item-action">')
              .text(file.name)
              .appendTo(list);
            $('<a href="###" class="link-secondary">&times;</a>')
              .css("float", "right")
              .appendTo(item)
              .on("click" + ns, (event) => {
                event.preventDefault();
                selected.splice(selected.indexOf(file), 1);
                item.remove();
              });
          });
          this.value = "";
        });
        $('<button type="button" class="btn btn-primary btn-sm">上传</button>')
          .appendTo(m.find(".modal-footer"))
          .on("click" + ns, () => start(selected));
        m.find(".modal-footer").append(
          '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">关闭</button>',
        );
      }
      function paste(event) {
        const items = (
          event.originalEvent?.clipboardData || event.clipboardData
        )?.items;
        const files = Array.from(items || [])
          .filter((item) => item.kind === "file")
          .map((item) => item.getAsFile())
          .filter(Boolean);
        if (files.length && callback("paste", event) !== false) {
          event.preventDefault();
          start(files);
        }
      }
      if (opt.mode === "drop") {
        $target
          .on("drop" + ns, (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (callback("drop", event) !== false)
              start(
                (event.originalEvent?.dataTransfer || event.dataTransfer)
                  ?.files,
              );
          })
          .on("dragenter" + ns, (event) => {
            event.preventDefault();
            callback("dragEnter", event);
          })
          .on("dragover" + ns, (event) => {
            event.preventDefault();
            callback("dragOver", event);
          })
          .on("dragleave" + ns, (event) => callback("dragLeave", event))
          .on("paste" + ns, paste);
        $(document)
          .on("drop" + ns, (event) => event.preventDefault())
          .on("dragover" + ns, (event) => {
            event.preventDefault();
            callback("docOver", event);
          })
          .on("dragenter" + ns, (event) => {
            event.preventDefault();
            clearTimeout(leaveTimer);
            callback("docEnter", event);
          })
          .on("dragleave" + ns, (event) => {
            clearTimeout(leaveTimer);
            leaveTimer = setTimeout(() => {
              if (!disposed) callback("docLeave", event);
            }, 100);
          });
      } else if (opt.mode === "paste") $target.on("paste" + ns, paste);
      else
        $target.on("click" + ns, (event) => {
          event.preventDefault();
          if (typeof opt.mode === "function") start(opt.mode.call(opt));
          else if (Array.isArray(opt.mode) || isBlob(opt.mode))
            start(isBlob(opt.mode) ? [opt.mode] : opt.mode);
          else browse();
        });
      $target
        .addClass("upload")
        .data("upload", opt)
        .data("powerUpload", {
          cancel,browse,start,
          destroy() {
            disposed = true;
            cancel();
            clearTimeout(leaveTimer);
            $target
              .off(ns)
              .removeData("upload powerUpload")
              .removeClass("upload");
            $(document).off(ns);
            if (modal) {
              const element = modal,
                instance = typeof window.bootstrap?.Modal?.getInstance==='function'
                  ? window.bootstrap.Modal.getInstance(element[0]) : element.data('bs.modal');
              if(instance){
                let cleaned=false;
                const dispose=()=>{if(cleaned)return;cleaned=true;element.off(ns);instance.dispose();element.remove();};
                if(modalPhase==='new'||modalPhase==='hidden')dispose();
                else {
                    element.one('hidden.bs.modal'+ns,dispose);
                    if(modalPhase==='showing')element.one('shown.bs.modal'+ns,()=>instance.hide());
                    else if(modalPhase==='shown')instance.hide();
                    // Hiding instances must finish their backdrop transition before disposal.
                }
              } else element.remove();
            }
          },
        });
    });
  };
})(jQuery);
