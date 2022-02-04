/**************************************************
 *                                                *
 * Author  : Windy_sk                             *
 * Create  : 2003-05-03                           *
 * Modified: 2020-6-21                            *
 * Email   : windy2006@gmail.com                  *
 * HomePage: www.mysteps.cn                       *
 * Notice  : U Can Use & Modify it freely,        *
 *           BUT PLEASE HOLD THIS ITEM.           *
 *                                                *
 **************************************************/

const global = {};
global.root = root_web;
if(global.root==null) global.root = '/';
global.root_fix = location.pathname.replace(/&.+$/,'')+'/';
global.root_fix = global.root_fix.replace(/\/+/g, '/');
global.editor_btn = '';
global.alert_leave = false;
global.func = [];
global.mobile = isMobile().any;

//获取当前路径（可自定义目录层级）
function getPath(lvl) {
    let hostname = location.hostname;
    let pathname = location.pathname;
    let contextPath = pathname.split("/");
    let port = location.port;
    let protocol = location.protocol;
    if(port!=='') port = ':' + port;
    if(isNaN(lvl)) lvl = 0;
    return protocol + "//" + hostname + port + contextPath.slice(0,lvl+1).join('/');
}

//获取某ID元素
function $id(id) {
    return document.getElementById(id);
}

//获取某name元素集合
function $name(name, idx) {
    let objs = document.getElementsByName(name);
    if(idx==="first") {
        return objs[0];
    } else if(idx==="last") {
        return objs[objs.length-1];
    } else if(!isNaN(idx)) {
        return objs[idx];
    } else {
        return objs;
    }
}

//获取某标签元素集合
function $tag(name, theOLE) {
    if(typeof(theOLE)!=="object") theOLE = document;
    return theOLE.getElementsByTagName(name);
}

//获取某样式元素集合
function $class(name, context) {
    if(typeof(context)!=="object") context = document;
    return context.getElementsByClassName(name);
}

//判断变量是否为数组
function isArray(para) {
    return Object.prototype.toString.apply(para) === '[object Array]';
}

//返回某字符串的二进制长度
String.prototype.blen = function() {
    let arr=this.match(/[^\x00-\xff]/ig);
    return this.length+(arr==null?0:arr.length);
};

//去除字符串首尾空字符
String.prototype.trim= function(){
    return this.replace(/^\s+|\s+$/g, "");
};

//字符串赋值
String.prototype.printf = function() {
    let num = arguments.length;
    let str = this;
    for (let i = 0; i < num; i++) {
        let pattern = "%" + (i+1);
        let re = new RegExp(pattern, "g");
        str = str.replace(re, arguments[i]);
    }
    return str;
};

//格式化日期
Date.prototype.format = function(format){  //eg:format="YYYY-MM-dd hh:mm:ss";
    let o = {
        "M+" :  this.getMonth()+1,  //month
        "d+" :  this.getDate(),     //day
        "h+" :  this.getHours(),    //hour
        "m+" :  this.getMinutes(),  //minute
        "s+" :  this.getSeconds(), //second
        "q+" :  Math.floor((this.getMonth()+3)/3),  //quarter
        "S"  :  this.getMilliseconds() //millisecond
    };
    if(/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
    }
    for(let k in o) {
        if(new RegExp("("+ k +")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length===1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));
        }
    }
    return format;
};

//扩展数组
Array.prototype.append = function(newArray) {
    if (isArray(newArray)) {
        for (let i = 0; i < newArray.length; i++) {
            this[this.length] = newArray[i];
        }
    } else {
        this[this.length] = newArray;
    }

};

// 格式化金额
// 用法: number.formatMoney(保留小数, 金额单位, 千位间隔符, 小数点符号)
// 默认: (2, "$", ",", ".")
Number.prototype.formatMoney = function (places, symbol, thousand, decimal) {
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    let number = this,
        negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
};

//显示锁屏信息
function loadingShow(info) {
    let obj_loading = $("#bar_loading");
    let obj_locker = $("#screenLocker");
    if (obj_loading.length === 0) {
        obj_loading = $("<div>")
            .attr("id", "bar_loading")
            .addClass("position-absolute font-weight-bold border p-5 text-center bg-light")
            .cssText("top:0;left:0;z-index:9999;color:#333333;opacity:0.9")
            .append($('<img src="static/images/loading.gif" style="width:90%;min-width:300px;height:10px">'))
            .append("<br />").append($("<span style='font-size:24px;line-height:48px;'>"));
        obj_loading.appendTo("body").hide();
    }
    if (obj_locker.length === 0) {
        obj_locker = $("<div>")
            .attr("id", "screenLocker")
            .addClass("position-absolute bg-dark")
            .cssText("top:0;left:0;z-index:8888;opacity:0.8");
        obj_locker.appendTo("body").hide();
    }
    if (obj_locker.is(':visible')) {
        $('body').css('overflow', 'auto');
        obj_loading.hide(500);
        obj_locker.hide(500);
    } else {
        $('body').css('overflow', 'hidden');
        obj_locker.height($(window).height() + $(window).scrollTop());
        obj_locker.width($(document.body).outerWidth(true) + $(window).scrollLeft());
        obj_locker.fadeIn(500);
        if (info === null) info = language.sending;
        obj_loading.find('span').html(info);
        let theTop = ($(document.body).height() - obj_loading.height()) / 2 + $(document.body).scrollTop()-100;
        let theLeft = ($(document.body).width() - obj_loading.width()) / 2 + $(document.body).scrollLeft();
        obj_loading.css({"top": theTop, "left": theLeft});
        obj_loading.fadeIn(500);
    }
}

//开启模态窗口
function openDialog(url, width, height, mode) {
    let sOrnaments = "dialogWidth:"+width+"px;dialogHeight:"+height+"px;center:1;dialogLeft:200;dialogTop:100;dialogHide:0;edge:raised;help:0;resizable:0;scroll:0;status:0;unadorned:0;center:1;";
    let win = null;
    try {
        if(mode){
            win = window.showModalDialog(url, window, sOrnaments);
        }else{
            win = window.showModelessDialog(url, window, sOrnaments);
        }
    } catch(e) {
        win = openWindow(url, width, height);
    }
    return win;
}

//新开窗口
function openWindow(url,width,height) {
    return window.open(url, "new_window","height="+height+", width="+width+", top=0, left=0, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no, status=no, modal=yes");
}

//程序中断指定时间
function sleep(the_time) {
    let over_time = new Date(new Date().getTime() + the_time);
    while(over_time > new Date()) {}
}

//复制某一页面元素内容（value或innerText）或者一个字符串
function copy(obj) {
    let value = '';
    if(typeof obj === 'string' && /^(#|.)\w+$/.test(obj) === false) {
        value = obj;
    } else {
        obj = $(obj)
        if(obj.length>0) {
            value = obj.attr('data-copy');
            if(typeof value === 'undefined') {
                value = obj.val();
            }
            if(typeof value === 'undefined' || value === '') {
                value = obj.text();
            }
        }
    }
    obj = $('<textarea>').val(value).appendTo('body').select();
    let flag = document.execCommand('copy');
    obj.remove();
    return flag;
}


//随机数字
function rndNum(min,max){
    if(typeof(min)==="undefined") return Math.random();
    if(typeof(max)==="undefined") {
        max = min;
        min = 0;
    }
    let Range = max - min;
    let Rand = Math.random();
    return(min + Math.round(Rand * Range));
}

//随机字符串
function rndStr(len, t_lst, c_lst) {
    let str = "";
    let upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    let lower = "abcdefghijklmnopqrstuvwxyz";
    let number = "1234567890";
    let char_lst = [];
    let rnd_num = 0;
    if(typeof(t_lst)==="undefined") t_lst = "";
    t_lst += "0110";
    if(t_lst.charAt(0)==="1") char_lst = char_lst.concat(upper.split(/\B/));
    if(t_lst.charAt(1)==="1") char_lst = char_lst.concat(lower.split(/\B/));
    if(t_lst.charAt(2)==="1") char_lst = char_lst.concat(number.split(/\B/));
    let cn = (t_lst.charAt(3)==="1");
    if(typeof(c_lst)==="undefined") {
        c_lst = [];
    } else if(typeof(c_lst)!=="object") {
        c_lst = [c_lst];
    }
    for(let i=0; i<len; i++) {
        rnd_num = rndNum(10);
        if(c_lst.length>0 && rnd_num>7) {
            str += c_lst[rndNum(c_lst.length-1)];
        } else if(cn && rnd_num>3) {
            str += String.fromCharCode(rndNum(19968, 40869));
        } else if(char_lst.length>0){
            str += char_lst[rndNum(char_lst.length-1)];
        }
    }
    return str;
}

//字符串水印
function watermark(obj, rate, copyright, char_c, jam_tag) {
    let c_cur = "", result = "", str="";
    let c_lst = [];
    let jam_flag = true;

    if(typeof(obj)==="object") {
        str = obj.innerHTML;
    } else {
        str = obj.toString();
    }
    if(rate===null) rate = 5;
    if(copyright===null) copyright = "WaterMark Maker, Coded by Windy2000";
    if(char_c!==null) c_lst = char_c.split(",");
    if(jam_tag===null) jam_tag = false;

    str = str.replace(/<(script|style)[^>]*?>([\w\W]*?)<\/\1>/ig,"");
    let u_lst = str.match(/(<(.+?)>)|(&[\w#]+;)/g);
    str = str.replace(/(<(.+?)>)|(&[\w#]+;)/g,String.fromCharCode(0));
    let m_start = "<span class='watermark'>";
    let m_end = "</span>";

    for(let i=0;i<str.length;i++) {
        c_cur = str.charCodeAt(i);
        if(c_cur===0) {
            result += u_lst.shift();
        } else if(c_cur===10) {
            result += m_start + rndStr(8, "1111", c_lst) + m_end;
            result += m_start + "[" + copyright + "]" + m_end + "\n";
        } else {
            result += String.fromCharCode(c_cur);
            if(jam_tag && rndNum(10)>rate) {
                result += jam_flag?"<span>":"</span>";
                jam_flag = !jam_flag;
            }
            if(rndNum(10)>rate) result += m_start + rndStr(4, "1111", c_lst) + m_end;
        }
    }
    if(!jam_flag) result += "</span>";

    if(typeof(obj)==="object") {
        obj.innerHTML = result;
    }
    return result;
}

//移动设备判断
function isMobile() {
    let isMobile = {
        Android: (navigator.userAgent.match(/Android/i) ? true : false),
        BlackBerry: (navigator.userAgent.match(/BlackBerry/i) ? true : false),
        iOS: (navigator.userAgent.match(/iPhone|iPad|iPod/i) ? true : false),
        Windows: (navigator.userAgent.match(/IEMobile/i) ? true : false)
    };
    isMobile.any = (isMobile.Android || isMobile.BlackBerry || isMobile.iOS || isMobile.Windows);
    return isMobile;
}

//MD5编码
function md5(str) {
    let rhex = function(num) {
        let hex_chr = "0123456789abcdef";
        str = "";
        for(let i=0; i<=3; i++) str += hex_chr.charAt((num >> (i * 8 + 4)) & 0x0F) + hex_chr.charAt((num >> (i * 8)) & 0x0F);
        return str;
    };
    let str2blks_MD5 = function(str) {
        let i=0,m=0;
        let nblk = ((str.length + 8) >> 6) + 1;
        let blks = new Array(nblk * 16);
        for (i=0; i<nblk*16; i++) blks[i] = 0;
        for (i=0,m=str.length; i<m; i++) blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
        blks[i >> 2] |= 0x80 << ((i % 4) * 8);
        blks[nblk * 16 - 2] = str.length * 8;
        return blks;
    };
    let add = function(x, y) {
        let lsw = (x & 0xFFFF) + (y & 0xFFFF);
        let msw = (x >> 16) + (y >> 16) + (lsw >> 16);
        return (msw << 16) | (lsw & 0xFFFF);
    };
    let rol = function(num, cnt) {
        return (num << cnt) | (num >>> (32 - cnt));
    };
    let cmn = function(q, a, b, x, s, t) {
        return add(rol(add(add(a, q), add(x, t)), s), b);
    };
    let ff = function(a, b, c, d, x, s, t) {
        return cmn((b & c) | ((~b) & d), a, b, x, s, t);
    };
    let gg = function(a, b, c, d, x, s, t) {
        return cmn((b & d) | (c & (~d)), a, b, x, s, t);
    };
    let hh = function(a, b, c, d, x, s, t) {
        return cmn(b ^ c ^ d, a, b, x, s, t);
    };
    let ii = function(a, b, c, d, x, s, t) {
        return cmn(c ^ (b | (~d)), a, b, x, s, t);
    };
    let x = str2blks_MD5(str);
    let a = 1732584193;
    let b = -271733879;
    let c = -1732584194;
    let d = 271733878;
    for (let i=0, m=x.length; i<m; i+=16) {
        olda = a;
        oldb = b;
        oldc = c;
        oldd = d;
        a = ff(a, b, c, d, x[i + 0], 7, -680876936);
        d = ff(d, a, b, c, x[i + 1], 12, -389564586);
        c = ff(c, d, a, b, x[i + 2], 17, 606105819);
        b = ff(b, c, d, a, x[i + 3], 22, -1044525330);
        a = ff(a, b, c, d, x[i + 4], 7, -176418897);
        d = ff(d, a, b, c, x[i + 5], 12, 1200080426);
        c = ff(c, d, a, b, x[i + 6], 17, -1473231341);
        b = ff(b, c, d, a, x[i + 7], 22, -45705983);
        a = ff(a, b, c, d, x[i + 8], 7, 1770035416);
        d = ff(d, a, b, c, x[i + 9], 12, -1958414417);
        c = ff(c, d, a, b, x[i + 10], 17, -42063);
        b = ff(b, c, d, a, x[i + 11], 22, -1990404162);
        a = ff(a, b, c, d, x[i + 12], 7, 1804603682);
        d = ff(d, a, b, c, x[i + 13], 12, -40341101);
        c = ff(c, d, a, b, x[i + 14], 17, -1502002290);
        b = ff(b, c, d, a, x[i + 15], 22, 1236535329);
        a = gg(a, b, c, d, x[i + 1], 5, -165796510);
        d = gg(d, a, b, c, x[i + 6], 9, -1069501632);
        c = gg(c, d, a, b, x[i + 11], 14, 643717713);
        b = gg(b, c, d, a, x[i + 0], 20, -373897302);
        a = gg(a, b, c, d, x[i + 5], 5, -701558691);
        d = gg(d, a, b, c, x[i + 10], 9, 38016083);
        c = gg(c, d, a, b, x[i + 15], 14, -660478335);
        b = gg(b, c, d, a, x[i + 4], 20, -405537848);
        a = gg(a, b, c, d, x[i + 9], 5, 568446438);
        d = gg(d, a, b, c, x[i + 14], 9, -1019803690);
        c = gg(c, d, a, b, x[i + 3], 14, -187363961);
        b = gg(b, c, d, a, x[i + 8], 20, 1163531501);
        a = gg(a, b, c, d, x[i + 13], 5, -1444681467);
        d = gg(d, a, b, c, x[i + 2], 9, -51403784);
        c = gg(c, d, a, b, x[i + 7], 14, 1735328473);
        b = gg(b, c, d, a, x[i + 12], 20, -1926607734);
        a = hh(a, b, c, d, x[i + 5], 4, -378558);
        d = hh(d, a, b, c, x[i + 8], 11, -2022574463);
        c = hh(c, d, a, b, x[i + 11], 16, 1839030562);
        b = hh(b, c, d, a, x[i + 14], 23, -35309556);
        a = hh(a, b, c, d, x[i + 1], 4, -1530992060);
        d = hh(d, a, b, c, x[i + 4], 11, 1272893353);
        c = hh(c, d, a, b, x[i + 7], 16, -155497632);
        b = hh(b, c, d, a, x[i + 10], 23, -1094730640);
        a = hh(a, b, c, d, x[i + 13], 4, 681279174);
        d = hh(d, a, b, c, x[i + 0], 11, -358537222);
        c = hh(c, d, a, b, x[i + 3], 16, -722521979);
        b = hh(b, c, d, a, x[i + 6], 23, 76029189);
        a = hh(a, b, c, d, x[i + 9], 4, -640364487);
        d = hh(d, a, b, c, x[i + 12], 11, -421815835);
        c = hh(c, d, a, b, x[i + 15], 16, 530742520);
        b = hh(b, c, d, a, x[i + 2], 23, -995338651);
        a = ii(a, b, c, d, x[i + 0], 6, -198630844);
        d = ii(d, a, b, c, x[i + 7], 10, 1126891415);
        c = ii(c, d, a, b, x[i + 14], 15, -1416354905);
        b = ii(b, c, d, a, x[i + 5], 21, -57434055);
        a = ii(a, b, c, d, x[i + 12], 6, 1700485571);
        d = ii(d, a, b, c, x[i + 3], 10, -1894986606);
        c = ii(c, d, a, b, x[i + 10], 15, -1051523);
        b = ii(b, c, d, a, x[i + 1], 21, -2054922799);
        a = ii(a, b, c, d, x[i + 8], 6, 1873313359);
        d = ii(d, a, b, c, x[i + 15], 10, -30611744);
        c = ii(c, d, a, b, x[i + 6], 15, -1560198380);
        b = ii(b, c, d, a, x[i + 13], 21, 1309151649);
        a = ii(a, b, c, d, x[i + 4], 6, -145523070);
        d = ii(d, a, b, c, x[i + 11], 10, -1120210379);
        c = ii(c, d, a, b, x[i + 2], 15, 718787259);
        b = ii(b, c, d, a, x[i + 9], 21, -343485551);
        a = add(a, olda);
        b = add(b, oldb);
        c = add(c, oldc);
        d = add(d, oldd)
    }
    return rhex(a) + rhex(b) + rhex(c) + rhex(d);
}

//查看变量
function debug(para, mode) {
    if($("#debug").length===0) {
        $('\
<div style="margin:10px auto;width:600px;">\
    <b>Debug:</b><br/>\
    <textarea id="debug" cols="100" rows="10"></textarea>\
</div>\
        ').appendTo("body");
    }
    let str = "";
    if(typeof(para)!=="string" && typeof(para)!=="number") {
        for(let x in para) {
            str += x + " : " + para[x] + "\n";
        }
    } else {
        str = para;
    }

    if(mode===true) {
        $id("debug").value = str + "\n\n======================================\n\n" + $id("debug").value;
    } else {
        $id("debug").value = str;
    }
}
function c(){
    for(let x in arguments) console.log(arguments[x]);
}
function a(){
    for(let x in arguments) alert(arguments[x]);
}
function info(msg) {
    if(typeof window.myInfo === 'undefined') {
        window.myInfo = $("<div>")
            .css({position:'sticky',top:0,left:0,display:'inline',border:'1px gray solid',background:'#eee',padding:'10px','z-index':99999})
            .prependTo('body');
    }
    if(msg == null) {
        window.myInfo.hide();
    } else {
        window.myInfo.show().html(msg);
    }
}

//查看对象属性
function checkObj(obj, func_show) {
    if(typeof(func_show)!=="function") func_show = alert;
    for(let x in obj) func_show(x + " : " + obj[x]);

}

//错误信息处理
function reportError(msg, url, line) {
    let str = "You have found an error as below: \n\n";
    str += "Err: " + msg + "\n\non line: " + line;
    if(typeof global.err_report_func!=="function") global.err_report_func = alert;
    if(typeof(setting)!=="undefined" && !setting.debug) global.err_report_func(str, true);
    return true;
}

//检测language, setting可被调用后运行指定函数，func为需要运行的函数，params为对应函数数组形式的变量，此函数可通过checkSetting()替代
function checkNrun(func, params) {
    let idx = md5(func.toString());
    if(typeof global.timer === 'undefined') global.timer = {};
    global.timer[idx] = setInterval(function(){
        if(typeof(language)!=='undefined' && typeof(setting)!=='undefined') {
            if(typeof func === 'function') {
                func.apply(func, params);
            } else if(typeof func === 'string') {
                window[func].apply(func, params);
            }
            clearInterval(global.timer[idx]);
        }
    }, 100);
}

//检测language, setting可被调用后，回调函数
function checkSetting(timer = 500){
    let flag = true;
    if(typeof(language)=='undefined' || typeof(setting)=='undefined') {
        //let caller = arguments.callee.caller; //Cannot work at strict mode
        let caller = checkSetting.caller;
        let args = caller.arguments;
        setTimeout(function(){
            caller.apply(caller, args);
        }, timer);
        flag = false;
    }
    return flag;
}

//处理页面URL为对应的链接模式
function setURL(prefix=global.root_fix, context=window.document.body) {
    if(!checkSetting()) return;
    if(typeof(prefix) === 'undefined') {
        prefix = setting.app+'/';
    } else {
        if(setting.url_prefix.length>0 && prefix.indexOf(setting.url_prefix)===0) {
            prefix = prefix.replace(setting.url_prefix,'');
        }
    }
    let re = new RegExp("^\/?"+setting.url_fix+"\/?");
    let list = {
        'a':'href',
        'form':'action',
        'img':'src'
    };
    for(let x in list) {
        $(x+'['+list[x]+']', context).each(function(){
            if($(this).attr('keep-url')!==undefined) return;
            let url = $(this).attr(list[x]);
            if(url.indexOf('//')===0 || url.indexOf(':')!==-1 || url.indexOf("#")===0) return;
            if(url.indexOf(setting.url_prefix)!==0) {
                url = prefix + url.replace('?', '&');
                url = setting.url_prefix + url.replace(re, '');
                $(this).attr(list[x], url.replace(/\/+/g, '/'));
            }
        });
    }
    return true;
}

//滚动至锚点
function gotoAnchor(theAnchor = '') {
    if(global.timer) return;
    theAnchor = theAnchor.replace('#', '');
    let obj = $('a[name="'+theAnchor+'"],a[id="'+theAnchor+'"]');
    let top = 0;
    if(obj.length>0) {
        top = $(obj.get(0)).offset().top - 70;
    }
    let timer = Math.abs($('html').scrollTop() - top);
    if(timer>1000) timer = 1000;
    global.timer = true;
    $('html').animate({scrollTop: top}, timer, function(){
        //location.href = location.pathname + '#' + theAnchor;
        global.timer = false;
    });
    return false;
}

//无需跳转改变地址栏链接
function setLocation(path, name = '') {
    let state = {
        title: name,
        url: path
    };
    window.history.pushState(state, name, path);
    window.addEventListener('popstate', function(e){
        if (history.state){
            let state = e.state;
            //do something(state.url, state.title);
        }
    }, false);
}

//注册需要页面载入后运行的函数
function ms_func_reg(func) {
    if(typeof(func)!=='function') return;
    global.func.push(func);
}

//运行于所有页面载入之后的函数
function ms_func_run(){
    //如页面发生改动，在离开时提示
    $('form').submit(function(){
        $(window).unbind('beforeunload');
    });
    //修正页面设置base-url造成的锚点定位
    $('a[href^="#"]').on('click', function(){
        let link = $(this).attr('href');
        if($(this).attr('data-toggle')==null) {
            gotoAnchor(link);
            //location.href = location.pathname + link;
            return false;
        }
    });
    for(let i in global.func) {
        global.func[i]();
    }
}

$(window).bind('beforeunload',function(e){
    if(global.alert_leave!==false) {
        let msg = 'Some changes have not been submit, are you sure to leave?';
        if(typeof(global.alert_leave)==='string') {
            msg = global.alert_leave;
        } else if(typeof(language.alert_leave)==='undefined') {
            msg = language.alert_leave;
        }
        let e = window.event || e;
        e.returnValue = msg;
        return msg;
    }
});

$(function(){
    $('input[type=password]').each(function(){
        let obj = $('<div>').addClass('password').data('mode', 'off');
        $(this).after(obj).parent().css('position', 'relative');
        obj.click(function(){
            let obj_s = $(this);
            if(obj_s.data('mode')==='off') {
                obj_s.prev().attr('type', 'text');
                obj_s.css('background-position-x', '-32px');
                obj_s.data('mode', 'on');
            } else {
                obj_s.prev().attr('type', 'password');
                obj_s.css('background-position-x', '0');
                obj_s.data('mode', 'off');
            }
        });
    });
});
