/*!
 * jquery.powerUpload.js
 * File upload through browse, drop or paste function by windy2000
*/
jQuery.event.addProp("dataTransfer");
(function($){
    let opts = {},
        default_opts = {
            title: 'Power Uploader',
            url: '',
            refresh: 1000,
            param_name: 'userfile',
            max_files: 1,
            max_file_size: 10, // MBs
            data: {},
            mode: 'drop', // drop or browse
            errors: [],
            drop: dummy,
            file: dummy,
            paste: dummy,
            dragEnter: dummy,
            dragOver: dummy,
            dragLeave: dummy,
            docEnter: dummy,
            docOver: dummy,
            docLeave: dummy,
            beforeEach: dummy,
            allDone: dummy,
            uploadStarted: dummy,
            uploadFinished: dummy,
            progressUpdated: dummy,
            speedUpdated: dummy
        },
        errors = ["Browser Not Supported", "Too Many Files", "File Too Large", "No Upload Script Set", "Choose a file at least"],
        doc_leave_timer,
        stop_loop = false,
        files_count = 0,
        files_done = 0,
        files_rejected = 0,
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
        'file' :    '<div data-idx="" class="input-group mb-3">\n' +
                    '    <div class="input-group-prepend">\n' +
                    '        <span class="input-group-text">文件</span>\n' +
                    '    </div>\n' +
                    '    <div class="custom-file">\n' +
                    '        <label><input type="file" class="custom-file-input" name="files[]" />\n' +
                    '        <span class="custom-file-label nowrap" data-browse="浏览">点击选取需上传的文件</span></label>\n' +
                    '    </div>\n' +
                    '</div>\n',
        'process' : '<div data-idx="" class="progress mb-3">\n' +
                    '  <div class="progress-bar progress-bar-striped progress-bar-animated text-left p-2" role="progressbar" style="width:0"></div>\n' +
                    '</div>\n',
        'btn_close' :'<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> 关闭 </button>',
        'btn_upload':'<button type="button" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-open"></span> 上传 </button>',
        'btn_add'   :'<button type="button" class="btn btn-info btn-sm"><span class="glyphicon glyphicon-plus-sign"></span> 增加 </button>',
    };

    $.fn.powerUpload = function(options) {
        opts = $.extend( {}, default_opts, options );
        this.addClass('upload');
        this.data('upload', opts);
        if(opts.mode==='drop') {
            this.bind('drop', drop)
                .bind('dragenter', dragEnter)
                .bind('dragover', dragOver)
                .bind('dragleave', dragLeave)
                .bind('paste', paste);
            $(document).bind('drop', docDrop)
                .bind('dragenter', docEnter)
                .bind('dragover', docOver)
                .bind('dragleave', docLeave);
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
    };

    function reset(obj) {
        stop_loop = false;
        files_count = 0;
        files_done = 0;
        files_rejected = 0;
        files = [];
        result = [];
        reject_list = [];
        if(typeof obj!="undefined") {
            obj = $(obj);
            while(!obj.hasClass('upload')) obj = obj.parent();
            opts = obj.data('upload');
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
                obj = template.file.clone(true);
                let btn = $(template.btn_upload).bind('click', file);
                tpl.find(".modal-body").append(obj.attr('data-idx', 0));
                tpl.find(".modal-footer").append(btn).append($(template.btn_close));
                if(opts.max_files>1) {
                    $(template.btn_add).click(function(){
                        let cnt = $("#uploader input[name='files[]']").length;
                        if(cnt<opts.max_files) {
                            let obj = template.file.clone(true);
                            obj.attr('data-idx', cnt);
                            $("#uploader").find(".modal-body").append(obj);
                            if(cnt===opts.max_files-1) $(this).hide();
                        } else {
                            $(this).hide();
                        }
                    }).prependTo(tpl.find(".modal-footer"));
                }
        }
        $('body').append(tpl);
        if(!tpl.is(":visible")) tpl.modal('show');
    }

    function file(e) {
        opts.file(e);
        let objs = $(e.target).parentsUntil('#uploader').parent().find("input[name='files[]']");
        let file = null;
        for(let i=0,m=objs.length;i<m;i++) {
            if(objs.get(i).files.length===0) continue;
            file = objs.get(i).files[0];
            files_count++;
            files.push(file);
        }
        if(files_count===0) {
            error(4);
        } else {
            upload();
            e.preventDefault();
        }
        return false;
    }

    function paste(e) {
        reset(e.target);
        opts.paste(e);
        if((/webkit/i).test(navigator.userAgent)) {
            e = e.originalEvent;
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
                upload();
                e.preventDefault();
            }
        }
        return true;
    }

    function drop(e) {
        reset(e.target);
        opts.drop(e);
        files = e.dataTransfer.files;
        if (files === null || files === undefined) {
            error(0);
            return false;
        }
        files_count = files.length;
        upload();
        e.preventDefault();
        return false;
    }

    function getBuilder(filename, filetype, filedata, boundary) {
        let the_dash = '--', crlf = '\r\n', builder = '';
        $.each(opts.data, function(i, val) {
            c(i, val);
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
                obj.width(this.currentProgress);
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
        getTpl('process');
    }

    function send(e) {
        if(opts.url==='') {
            error(3);
            return;
        }
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
        xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
        xhr.overrideMimeType(file.type);
        xhr.sendAsBinary(builder);
        opts.uploadStarted(index, file, files_count);

        xhr.onload = function() {
            if (xhr.responseText) {
                let flag = false;
                let obj = $(".progress[data-idx="+index+"] > div");
                try{
                    /*
                    * xhr.responseText
                    * error - error no. ('0' means no error)
                    * message - error detail
                    * eg: {"error":"-1","message":"Upload Denied!"}
                    */
                    let info = jQuery.parseJSON(xhr.responseText);
                    if(typeof result.error!=='undefined' && result.error!==0) {
                        obj.addClass('bg-danger').css('width','100%');
                        flag = opts.uploadFinished(index, file, info, (new Date().getTime() - start_time));
                        result.push("<"+file.name+"> failed!");
                    } else {
                        obj.addClass('bg-success').css('width','100%');
                        flag = opts.uploadFinished(index, file, info, (new Date().getTime() - start_time));
                        result.push("<"+file.name+"> uploaded!");
                    }
                } catch(e) {
                    obj.addClass('bg-danger').css('width','100%');
                    result.push("<"+file.name+"> failed!");
                }
                files_done++;
                if (files_done === files_count - files_rejected) allDone();
                if (flag === false) stop_loop = true;
            }
            //xhr.abort();
        };
    }

    function beforeEach(file) {
        return opts.beforeEach(file);
    }

    function allDone() {
        return opts.allDone(result);
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