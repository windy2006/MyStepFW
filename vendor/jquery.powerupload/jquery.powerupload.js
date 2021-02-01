/**
 * jquery.powerUpload.js - File upload through browse, drop or paste function
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
jQuery.event.addProp("dataTransfer");
(function($){
    let opts = {},
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
            uploadFinished: dummy,      //在每个文件上传完成时执行的代码，包含 index, file, info, duration 四个参数
            progressUpdated: dummy,     //在当前进度发生变化时执行的代码，包含 index, file, currentProgress 三个参数
            speedUpdated: dummy         //在上传速度发生变化时执行的代码，包含 index, file, speeds 三个参数
        },
        errors = ["Browser Not Supported", "Too Many Files", "File Too Large", "No Upload Script Set", "Choose a file at least", "Error occurred when send data"],
        doc_leave_timer,
        stop_loop = false,
        files_count = 0,
        files_done = 0,
        files_rejected = 0,
        files_loaded = 0,
        reject_list = [],
        files = [],
        result = [];
    let template = {
        'main' :    '<div id="uploader" class="modal fade" data-backdrop="static" tabindex="-1" role="dialog">\n' +
                    '    <div class="modal-dialog modal-dialog-scrollable" role="document">\n' +
                    '        <div class="modal-content">\n' +
                    '            <div class="modal-header py-0 bg-light">\n' +
                    '                <h4 class="modal-title"><span class="glyphicon glyphicon-upload"></span> <b>Power Uploader</b></h4>\n' +
                    '                <button type="button" class="close" data-dismiss="modal" aria-label="Close">\n' +
                    '                    <span aria-hidden="true">&times;</span>\n' +
                    '                </button>\n' +
                    '            </div>\n' +
                    '            <div class="modal-body"></div>\n' +
                    '            <div class="modal-footer py-2"></div>\n' +
                    '        </div>\n' +
                    '    </div>\n' +
                    '</div>',
        'file' :    '<div class="input-group mb-3">\n' +
                    '    <div class="input-group-prepend">\n' +
                    '        <span class="input-group-text">文件</span>\n' +
                    '    </div>\n' +
                    '    <div class="custom-file">\n' +
                    '        <label><input type="file" multiple class="custom-file-input" />\n' +
                    '        <span class="custom-file-label nowrap" data-browse="浏览">点击选取文件</span></label>\n' +
                    '    </div>\n' +
                    '</div>\n' +
                    '<div class="input-group mb-3">\n' +
                    '    <div class="list-group w-100"></div>' +
                    '</div>\n',
        'waiting' : '<div class="input-group p-3">\n' +
                    '    <h6>正在读取文件准备上传，文件如果较大，可能持续较长时间，请耐心等待！</h6>\n' +
                    '</div>\n',
        'process' : '<div data-idx="" class="progress mb-3">\n' +
                    '  <div class="progress-bar progress-bar-striped progress-bar-animated text-left p-2" role="progressbar" style="width:0"></div>\n' +
                    '</div>\n',
        'btn_close' :'<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> 关闭 </button>',
        'btn_upload':'<button type="button" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-open"></span> 上传 </button>',
    };

    $.fn.powerUpload = function(options) {
        if(typeof(this.data('upload')) !== 'undefined') {
            $.extend(opts, this.data('upload'), options );
            return;
        }
        opts = $.extend( {}, default_opts, options );
        switch(opts.mode) {
            case 'paste':
                opts.max_files = 1;
                this.bind('paste', paste);
                break;
            case 'drop':
                this.bind('drop', drop)
                    .bind('dragenter', dragEnter)
                    .bind('dragover', dragOver)
                    .bind('dragleave', dragLeave)
                    .bind('paste', paste);
                $(document).bind('drop', docDrop)
                    .bind('dragenter', docEnter)
                    .bind('dragover', docOver)
                    .bind('dragleave', docLeave);
                break;
            case 'browse':
            default:
                if(opts.mode instanceof Function) {
                    $(this).click(function(e){
                        reset(this);
                        files = opts.mode();
                        files_count = files.length;
                        beforeUpload();
                        setTimeout(upload, 1000);
                        e.preventDefault();
                    });
                } else {
                    $(this).click(function(){
                        reset(this);
                        getTpl('browse');
                    });
                    template.file = $(template.file);
                    template.file.find('input[type=file]').change(function(){
                        $(this).next().text(this.value.replace(/^.+[\/\\]([^\/\\]+)$/, '$1'));
                    });
                }
                break;
        }
        this.addClass('upload');
        this.data('upload', opts);
    };

    function reset(obj) {
        stop_loop = false;
        files_count = 0;
        files_done = 0;
        files_rejected = 0;
        files_loaded = 0,
        files = [];
        result = [];
        reject_list = [];
        if(typeof obj!="undefined") {
            obj = $(obj);
            if(!obj.hasClass('upload')) obj = obj.parentsUntil('.upload').parent();
            if(obj.length>0 && obj.data('upload')!=null) opts = obj.data('upload');
        }
    }

    function getTpl(mode) {
        if(files_count>0 && files_count===files_rejected) {
            reset();
            return;
        }
        let tpl, obj;
        tpl = $('#uploader');
        if(tpl.length>0) {
            tpl.find(".modal-body").empty();
            tpl.find(".modal-footer").empty();
        } else {
            tpl = $(template.main);
        }
        tpl.find(".modal-title > b").html(opts.title);

        switch(mode) {
            case 'waiting':
                tpl.find(".modal-body").append($(template.waiting));
                break;
            case 'drop':
                tpl.find(".modal-body").html('Drop the file(s) you need to upload here.(WIP)');
                tpl.find(".modal-footer").append($(template.btn_close));
                break;
            case 'process':
                for (let i=0; i<files_count; i++) {
                    obj = $(template.process).attr('data-idx',i);
                    if(typeof reject_list[i]==='undefined') {
                        obj.find('div').text(files[i].name);
                    } else {
                        obj.find('div')
                            .addClass('bg-danger').css('width','100%')
                            .text(files[i].name + ' - '+reject_list[i]);
                    }
                    tpl.find(".modal-body").append(obj);
                }
                tpl.find(".modal-footer").append($(template.btn_close));
                break;
            case 'browse':
            default:
                let btn = $(template.btn_upload).bind('click', file);
                tpl.find(".modal-body").append(template.file.clone(true));
                tpl.find(".modal-footer").append(btn).append($(template.btn_close));
                if(typeof opts.obj_attr === 'object') {
                    tpl.find("input[type=file]").attr(opts.obj_attr);
                }
                tpl.find("input[type=file]").change(function(){
                    let files = this.files, m = files.length;
                    if($('#uploader .list-group').find('.list-group-item').length + m > opts.max_files) {
                        error(1);
                        return false;
                    }
                    let btn = $('<a href="###" class="link-secondary">&times;</a>');
                    btn.css('float', 'right').click(function(){
                        $(this).parentsUntil('.list-group').remove();
                    });
                    for(let i=0; i<m; i++) {
                        $('<a>').addClass('list-group-item list-group-item-action')
                            .html(files[i].name).data('file', files[i])
                            .appendTo('#uploader .list-group')
                            .append(btn.clone(true));
                    }
                    if(typeof tpl.find("input[type=file]").attr('capture')==='undefined')
                        tpl.find('.custom-file-label').text('点击选取文件');
                });
        }
        $('body').append(tpl);
        if(!tpl.is(":visible")) tpl.modal('show');
    }

    function file(e) {
        if(opts.file(e)===false) return;
        let objs = $(e.target).parentsUntil('#uploader').parent().find(".list-group-item");
        let size = 0, m = objs.length;
        for(let i=0;i<m;i++) {
            files_count++;
            if($(objs.get(i)).data('file').size<size) {
                files.push($(objs.get(i)).data('file'));
            } else {
                files.unshift($(objs.get(i)).data('file'));
            }
            size = $(objs.get(i)).data('file').size;
        }
        if(files_count===0) {
            error(4);
        } else {
            beforeUpload();
            setTimeout(upload, 1000);
            e.preventDefault();
        }
        return false;
    }

    function paste(e) {
        reset(e.target);
        if(opts.paste(e)===false) return;
        if((/webkit/i).test(navigator.userAgent)) {
            e = e.originalEvent || e;
            files = e.clipboardData.files;
            let items = e.clipboardData.items;
            if(items.length > 0) {
                if((items[0]['kind'] === 'file') && (items[0]['type'].search('^image/') !== -1)) {
                    let reader = new FileReader();
                    files_count = 1;
                    reader.onloadend = send;
                    reader.readAsBinaryString(items[0].getAsFile());
                    e.preventDefault();
                    getTpl('process');
                }
            } else if(files.length>0) {
                files_count = files.length;
                beforeUpload();
                setTimeout(upload, 1000);
                e.preventDefault();
            }
        }
        return true;
    }

    function drop(e) {
        reset(e.target);
        if(opts.drop(e)===false) return;
        files = e.dataTransfer.files;
        if (files === null || files === undefined) {
            error(0);
            return false;
        }
        files_count = files.length;
        beforeUpload();
        setTimeout(upload, 1000);
        e.preventDefault();
        return false;
    }

    function getBuilder(filename, filetype, filedata, boundary) {
        let the_dash = '--', crlf = '\r\n', builder = '';
        $.each(opts.data, function(i, val) {
            if (typeof val === 'function') val = val();
            if(builder==='') {
                builder = the_dash;
            } else {
                builder += the_dash;
            }
            builder += boundary;
            builder += crlf;
            builder += 'Content-Disposition: form-data; name="'+i+'"';
            builder += crlf;
            builder += crlf;
            builder += val;
            builder += crlf;
        });

        builder += the_dash;
        builder += boundary;
        builder += crlf;
        builder += 'Content-Disposition: form-data; name="'+opts.param_name+'"';
        builder += '; filename="' + filename + '"';
        builder += crlf;

        builder += 'Content-Type: ' + (filetype.length<5?"application/octet-stream":filetype);
        builder += crlf;
        builder += crlf;

        builder += filedata;
        builder += crlf;

        builder += the_dash;
        builder += boundary;
        builder += the_dash;
        builder += crlf;
        return builder;
    }

    function progress(e) {
        if (e.lengthComputable) {
            let percentage = Math.round((e.loaded * 100) / e.total);
            let obj = $(".progress[data-idx="+this.index+"] > div");
            if (this.currentProgress !== percentage) {
                this.currentProgress = percentage;
                obj.css('width', this.currentProgress+'%');
                opts.progressUpdated(this.index, this.file, this.currentProgress);
                let elapsed = new Date().getTime();
                let diffTime = elapsed - this.currentStart;
                if (diffTime >= opts.refresh) {
                    let diffData = e.loaded - this.startData;
                    let speed = diffData / diffTime; // KB per second
                    opts.speedUpdated(this.index, this.file, speed);
                    obj.html(files[this.index].name+" ("+Math.round(speed) + "KB/S)");
                    this.startData = e.loaded;
                    this.currentStart = elapsed;
                }
            }
        }
    }

    function upload() {
        stop_loop = false;
        if (!files) {
            error(0);
            return false;
        }
        if (files_count > opts.max_files) {
            error(1);
            return false;
        }
        files_loaded = 0;
        for (let i=0; i<files_count; i++) {
            if (stop_loop) return false;
            try {
                if (beforeEach(files[i]) !== false) {
                    if (i === files_count) return;
                    let reader = new FileReader(), max_file_size = 1048576 * opts.max_file_size;
                    reader.index = i;
                    if (files[i].size > max_file_size) {
                        reject_list[i] = errors[2];
                        files_rejected++;
                        error(2, files[i], i);
                        continue;
                    }
                    reader.onloadend = send;
                    reader.readAsBinaryString(files[i]);
                } else {
                    reject_list[i] = 'Reject by user';
                    files_rejected++;
                }
            } catch(err) {
                error(0);
                return false;
            }
        }
    }

    function send(e) {
        if(opts.url==='') {
            error(3);
            return;
        }
        files_loaded++;
        if(files_loaded===files_count) getTpl('process');
        let xhr = new XMLHttpRequest(),
            upload = xhr.upload,
            file = null,
            index = 0,
            start_time = new Date().getTime(),
            boundary = '------multipartformboundary' + (new Date).getTime(),
            builder;

        if(e.target.index === undefined) {
            file = new File([], "img_" + start_time + ".png");
            file.name = "img_" + start_time + ".png";
            file.size = e.total;
            file.type = "image/png";
            files = [file];
        } else {
            file = files[e.target.index];
            index = e.target.index;
        }
        builder = getBuilder(encodeURIComponent(file.name), file.type, e.target.result, boundary);

        upload.index = index;
        upload.file = file;
        upload.downloadStartTime = start_time;
        upload.currentStart = start_time;
        upload.currentProgress = 0;
        upload.startData = 0;
        upload.addEventListener("progress", progress, false);

        xhr.open("POST", opts.url, true);
        xhr.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + boundary);
        xhr.overrideMimeType(file.type);
        xhr.sendAsBinary(builder);
        opts.uploadStarted(index, file, files_count);

        xhr.onerror = function(e) {
            setTimeout(function(){
                $('#uploader').modal('hide');
            },2000);
            error(5);
        };

        xhr.onload = function() {
            if (xhr.responseText) {
                let flag = false;
                let obj = $(".progress[data-idx="+index+"] > div");
                let info = {};
                try{
                    info = jQuery.parseJSON(xhr.responseText);
                    result.push("<"+file.name+"> uploaded!");
                } catch(e) {
                    info = {error: e};
                    result.push("<"+file.name+"> error!");
                }
                obj.addClass('bg-success').css('width','100%');
                flag = opts.uploadFinished(index, file, info, (new Date().getTime() - start_time));
                files_done++;
                if (files_done === files_count - files_rejected) allDone();
                if (flag === false) stop_loop = true;
            }
            xhr.abort();
        };
    }

    function beforeUpload() {
        getTpl('waiting');
        return opts.beforeUpload();
    }

    function beforeEach(file) {
        return opts.beforeEach(file);
    }

    function allDone() {
        return opts.allDone();
    }

    function dragEnter(e) {
        clearTimeout(doc_leave_timer);
        e.preventDefault();
        opts.dragEnter(e);
    }

    function dragOver(e) {
        clearTimeout(doc_leave_timer);
        e.preventDefault();
        opts.docOver(e);
        opts.dragOver(e);
    }

    function dragLeave(e) {
        clearTimeout(doc_leave_timer);
        opts.dragLeave(e);
        e.stopPropagation();
    }

    function docDrop(e) {
        e.preventDefault();
        opts.docLeave(e);
        return false;
    }

    function docEnter(e) {
        clearTimeout(doc_leave_timer);
        e.preventDefault();
        opts.docEnter(e);
        return false;
    }

    function docOver(e) {
        clearTimeout(doc_leave_timer);
        e.preventDefault();
        opts.docOver(e);
        return false;
    }

    function docLeave(e) {
        doc_leave_timer = setTimeout(function(){
            opts.docLeave(e);
        }, 200);
    }

    function error(idx,file,i) {
        //errors = ["Browser Not Supported", "Too Many Files", "File Too Large", "No Upload Script Set", "Choose a file at least"],
        let err;
        if(typeof opts.errors[idx]!=='undefined') {
            err = opts.errors[idx];
        } else if(typeof errors[idx]!=='undefined') {
            err = errors[idx];
        } else {
            err = 'Error Occurs!';
        }
        if(typeof(file)!=='undefined') {
            err += ' - '+file.name+'('+Math.round(file.size/1048576)+'MB)';
        }
        if(typeof opts.error == 'function') {
            opts.error(err,file,i);
        } else {
            alert(err);
        }
    }

    function dummy(){
        return true;
    }

    try {
        if (XMLHttpRequest.prototype.sendAsBinary) return;
        XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
            function byteValue(x) {
                return x.charCodeAt(0) & 0xff;
            }
            let ords = Array.prototype.map.call(datastr, byteValue);
            let ui8a = new Uint8Array(ords);
            this.send(ui8a.buffer);
        }
    } catch(e) {}
})(jQuery);