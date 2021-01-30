/**
 * 获得图像压缩后的 base64
 * @param {Object} opt
 * @param {Number} [opt.mode] 图片需要压缩的模式：1-按宽度，2-按比例，3-按大小
 * @param {Number} [opt.val] 对mode的参数：1-像素，2-分数，3-目标KB数
 * @param {String} [opt.type="image/jpeg"] 生成图片的类型
 * @param {Number} [opt.quality=0.8] JPG压缩质量，不压缩为1
 * @param {Function} [opt.callback(obj)] 回调函数
 */
$.fn.resizeImg = function(options) {
    let defaults = {
        mode: 0,
        val: 400,
        type: "image/jpeg",
        quality: 0.8,
        callback: new Function()
    };
    if(!$.isFunction(options)) {
        let opt = defaults;
    } else {
        let opt = $.extend({}, defaults, options || {});
    }
    this.on('change', function() {
        if(this.value==="") return;
        let file = this.files[0];
        if($.isFunction(options)) opt = options();
        getOrientation(file, function(orientation){
            let reader  = new FileReader();
            let img = new Image();
            img.onload = function() {
                let para = getOpt(this.width, this.height, file.size);
                let w = para.width;
                let h = para.height;
                let canvas = document.createElement("canvas");
                let ctx = canvas.getContext('2d');
                if(orientation<6) {
                    canvas.width = w;
                    canvas.height = h;
                } else {
                    canvas.width = h;
                    canvas.height = w;
                }
                switch(orientation) {
                    case 2:
                        // horizontal flip
                        ctx.translate(w, 0);
                        ctx.scale(-1, 1);
                        break;
                    case 3:
                        // 180 rotate left
                        ctx.translate(w, h);
                        ctx.rotate(Math.PI);
                        break;
                    case 4:
                        // vertical flip
                        ctx.translate(0, h);
                        ctx.scale(1, -1);
                        break;
                    case 5:
                        // vertical flip + 90 rotate right
                        ctx.rotate(0.5 * Math.PI);
                        ctx.scale(1, -1);
                        break;
                    case 6:
                        // 90 rotate right
                        ctx.rotate(0.5 * Math.PI);
                        ctx.translate(0, -h);
                        break;
                    case 7:
                        // horizontal flip + 90 rotate right
                        ctx.rotate(0.5 * Math.PI);
                        ctx.translate(w, -h);
                        ctx.scale(-1, 1);
                        break;
                    case 8:
                        // 90 rotate left
                        ctx.rotate(-0.5 * Math.PI);
                        ctx.translate(-w, 0);
                        break;
                    default:
                        console.log(orientation);
                }
                ctx.drawImage(this, 0, 0, w, h);
                this.onload = null;

                let result = canvas.toDataURL(opt.type, opt.quality);
                if($.isFunction(opt.callback)) opt.callback(result);

            }
            reader.addEventListener("load", function () {
                img.src = reader.result;
            }, false);
            reader.readAsDataURL(file);
        });
    });

    let getOpt = function(width, height, size) {
        let result = {};
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
                break;
        }
        opt = $.extend(opt, result);
        return result;
    }

    let getOrientation = function (file, callback) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let view = new DataView(e.target.result);
            if (view.getUint16(0, false) !== 0xFFD8) {
                return callback(-2);
            }
            let length = view.byteLength, offset = 2;
            while (offset < length) {
                if (view.getUint16(offset+2, false) <= 8) return callback(-1);
                let marker = view.getUint16(offset, false);
                offset += 2;
                if (marker === 0xFFE1) {
                    if (view.getUint32(offset += 2, false) !== 0x45786966) {
                        return callback(-1);
                    }
                    let little = view.getUint16(offset += 6, false) === 0x4949;
                    offset += view.getUint32(offset + 4, little);
                    let tags = view.getUint16(offset, little);
                    offset += 2;
                    for (let i = 0; i < tags; i++) {
                        if (view.getUint16(offset + (i * 12), little) === 0x0112) {
                            return callback(view.getUint16(offset + (i * 12) + 8, little));
                        }
                    }
                } else if ((marker & 0xFF00) !== 0xFF00) {
                    break;
                } else {
                    offset += view.getUint16(offset, false);
                }
            }
            return callback(-1);
        };
        reader.readAsArrayBuffer(file);
    }
};