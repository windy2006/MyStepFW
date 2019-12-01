/**
 * 获得base64
 * @param {Object} opt
 * @param {Number} [opt.mode] 图片需要压缩的模式：1-按宽度，2-按比例，3-按大小
 * @param {Number} [opt.val] 对mode的参数：1-像素，2-分数，3-目标KB数
 * @param {String} [opt.type="image/jpeg"] 生成图片的类型
 * @param {Number} [opt.quality=0.8] JPG压缩质量，不压缩为1
 * @param {Function} [opt.before(this, blob, file)] 处理前函数,this指向的是input:file
 * @param {Function} [opt.callback(obj)] 回调函数
 */
$.getScript("mobileBUGFix.mini.js");
$.fn.resizeImg = function(options) {
    var defaults = {
        mode: 0,
        val: 400,
        type: "image/jpeg",
        quality: 0.8,
        before: new Function(),
        callback: new Function()
    };
    if(!$.isFunction(options)) {
        var opt = defaults;
    } else {
        var opt = $.extend({}, defaults, options || {});
    }

    var getOpt = function(width, height, size) {
        var result = {};
        console.log(opt.mode);
        switch(opt.mode) {
            case 0:
                result.rate = opt.val/width;
                result.width = opt.val;
                result.height = height * result.rate;
                break;
            case 1:
                result.rate = opt.val;
                result.width = width * opt.val;
                result.height = height * opt.val;
                break;
            case 2:
                opt.val *= 1024;
                result.rate = Math.sqrt(size / opt.val);
                result.width = Math.ceil(width / result.rate);
                result.height = Math.ceil(height / result.rate);
                console.log(result);
                break;
        }
        opt = $.extend(opt, result);
        return result;
    };

    this.on('change', function () {
        if(this.value=="") return;
        var file = this.files[0];
        var URL = URL || webkitURL;
        var blob = URL.createObjectURL(file);
        if($.isFunction(options)) opt = options();
        if($.isFunction(opt.before)) opt.before(this, blob, file);

        var img = new Image();
        img.onload = function () {
            getOpt(this.width, this.height, file.size);
            var w = opt.width;
            var h = opt.height;
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            $(canvas).attr({width : w, height : h});
            ctx.drawImage(this, 0, 0, w, h);

            var result = '';
            if( navigator.userAgent.match(/iphone/i) ) {
                var mpImg = new MegaPixImage(img);
                mpImg.render(canvas, { maxWidth: w, maxHeight: h, quality: opt.quality || 0.8});
                result = canvas.toDataURL(opt.type, opt.quality);
            } else if( navigator.userAgent.match(/Android/i) ) {
                opt.type = "image/jpeg";
                var encoder = new JPEGEncoder();
                result = encoder.encode(ctx.getImageData(0,0,w,h), opt.quality * 100);
            } else {
                result = canvas.toDataURL(opt.type, opt.quality);
            }
            opt.callback(result);
        };
        img.src = blob;
    });
};