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

/**
 * 功能说明：框架公共浏览器工具、字符串/日期扩展、路由地址、就绪回调和页面交互。
 * 使用说明：先加载 jQuery，root_web 可由框架注入；language/setting 异步就绪后再调用路由工具。
 * 方法目录：
 *   DOM：getPath、$id、$name、$tag、$class、isArray。
 *   扩展：String.blen/printf（旧环境补 trim）、Date.format、Array.append、Number.formatMoney（均不可枚举）。
 *   界面：loadingShow、openDialog、openWindow、copyValue、copy、copyAsync、gotoAnchor。
 *   数据：sleep、rndNum、rndStr、watermark、isMobile、base64、md5。
 *   调试：debug、c、a、info、checkObj、reportError。
 *   生命周期：checkNrun、checkSetting、setLocation、ms_func_reg、ms_func_run。
 *   路由：queryURL、routeURL、setURL；保留三种框架路由及子目录兼容。
 * 约定：sleep 返回 Promise；自动重试请显式传入 checkSetting 的回调；MD5 不用于认证。
 * watermark/info 的原始 HTML 应来自可信内容；它们不是 HTML 安全过滤器。
 */

const global = {};
global.root = typeof root_web === 'string' && root_web ? root_web : '/';
global.root_fix = location.pathname;
// 查询串模式的首段是路由，后续 & 才是业务参数。
if(/\/index\.php$/.test(location.pathname)) {
    const route=(location.search || '').slice(1).split(/[?&]/)[0];
    if(route && route.indexOf('=')===-1) global.root_fix += '?' + route;
}
if(!global.root_fix.endsWith('/')) global.root_fix += '/';
global.root_fix = global.root_fix.replace(/\/+/g, '/');
global.editor_btn = '';
global.alert_leave = false;
global.func = [];
global.mobile = isMobile().any;

/**
 * 获取当前来源及前 N 个目录段。
 * @param {number} [lvl=0] 保留目录层数，负数按 0；0 仅返回带协议及端口的来源。
 * @returns {string} 来源与目录地址。
 */
function getPath(lvl=0) {
    lvl = Math.max(0, Math.floor(Number(lvl) || 0));
    return location.protocol + '//' + location.host + location.pathname.split('/').slice(0, lvl+1).join('/');
}

/**
 * @param {string} id 元素 ID。
 * @returns {Element|null} 对应元素。
 */
function $id(id) {
    return document.getElementById(id);
}

/**
 * @param {string} name 元素 name。
 * @param {number|string} [idx] 下标、first 或 last；不传则返回完整集合。
 * @returns {NodeList|Element|undefined} 集合或选中元素。
 */
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

/**
 * @param {string} name 标签名。
 * @param {Document|Element|null} [theOLE=document] 容器。
 * @returns {HTMLCollection} 匹配集合。
 */
function $tag(name, theOLE) {
    if(!theOLE || typeof theOLE.getElementsByTagName !== "function") theOLE = document;
    return theOLE.getElementsByTagName(name);
}

/**
 * @param {string} name 样式类名。
 * @param {Document|Element|null} [context=document] 容器。
 * @returns {HTMLCollection} 匹配集合。
 */
function $class(name, context) {
    if(!context || typeof context.getElementsByClassName !== "function") context = document;
    return context.getElementsByClassName(name);
}

/**
 * @param {*} para 待判断的值。
 * @returns {boolean} 是否为数组（支持跨窗口数组）。
 */
function isArray(para) {
    return Array.isArray(para);
}

/** 传统双字节长度：U+0100 以上的每个 UTF-16 码元计 2，用于兼容 len 属性；不是 UTF-8 字节数。 */
Object.defineProperty(String.prototype, 'blen', {configurable:true, writable:true, value:function() {
    return this.length + (this.match(/[^\x00-\xff]/g) || []).length;
}});

// 保留原生 trim 的 Unicode 空白处理，仅为缺少原生实现的环境补充。
if(!String.prototype.trim) Object.defineProperty(String.prototype, 'trim', {configurable:true, writable:true, value:function() {
    return String(this).replace(/^\s+|\s+$/g, '');
}});

/** 单次替换 %1、%2 等占位符；替换值中的 $、% 不作为特殊语法继续解释。 */
Object.defineProperty(String.prototype, 'printf', {configurable:true, writable:true, value:function() {
    const args = arguments;
    return String(this).replace(/%([1-9]\d*)/g, function(token, index) {
        return Number(index) <= args.length ? String(args[Number(index)-1]) : token;
    });
}});

/** 格式化本地日期：y/M/d/h/m/s/q/S 可重复；q 为 1—4 季度，S 为毫秒。 */
Object.defineProperty(Date.prototype, 'format', {configurable:true, writable:true, value:function(format='yyyy-MM-dd hh:mm:ss') {
    const date = this;
    if(!Number.isFinite(date.getTime())) return '';
    const values = {y:date.getFullYear(), M:date.getMonth()+1, d:date.getDate(), h:date.getHours(), m:date.getMinutes(), s:date.getSeconds(), q:Math.floor(date.getMonth()/3)+1, S:date.getMilliseconds()};
    return String(format).replace(/y+|M+|d+|h+|m+|s+|q+|S+/g, function(token) {
        let value = String(values[token.charAt(0)]);
        if(token.charAt(0) === 'y' && token.length < 4) return value.slice(-token.length);
        return token.length > 1 ? value.padStart(token.length, '0') : value;
    });
}});

/** 追加单值或数组，返回当前数组；支持自身追加，不扩展原生可枚举属性。 */
Object.defineProperty(Array.prototype, 'append', {configurable:true, writable:true, value:function(items) {
    if(Array.isArray(items)) { const length=items.length; for(let i=0;i<length;i++) this.push(items[i]); }
    else this.push(items);
    return this;
}});

/** 格式化金额；places 向下取整并限制在 0—20，非法金额按 0 显示。 */
Object.defineProperty(Number.prototype, 'formatMoney', {configurable:true, writable:true, value:function(places=2, symbol='$', thousand=',', decimal='.') {
    places = Number.isFinite(Number(places)) ? Math.min(20, Math.floor(Math.abs(Number(places)))) : 2;
    let amount = Number(this); if(!Number.isFinite(amount)) amount = 0;
    const fixed = Math.abs(amount)>=1e21 ? Math.abs(amount).toLocaleString('en-US',{useGrouping:false,minimumFractionDigits:places,maximumFractionDigits:places}) : Math.abs(amount).toFixed(places);
    const parts = fixed.split('.');
    return String(symbol) + (amount < 0 ? '-' : '') + parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, function() { return String(thousand); }) + (places ? String(decimal) + parts[1] : '');
}});

/**
 * 显示/隐藏加载遮罩，保存并恢复原页面 overflow；info 以纯文本显示。
 * @param {string|null} info 提示文字，空值使用语言包或默认文字。
 * @param {boolean} [show] 显式指定显示或隐藏；省略时沿用旧版切换约定。
 * @returns {boolean} 当前是否显示遮罩。
 */
function loadingShow(info, show) {
    let loading = $('#bar_loading'), locker = $('#screenLocker');
    if(!loading.length) loading = $('<div id="bar_loading">').addClass('position-fixed font-weight-bold border p-5 text-center bg-light').css({top:0,left:0,zIndex:9999,color:'#333',opacity:0.9,maxWidth:'calc(100vw - 16px)',boxSizing:'border-box'}).append($('<img>').attr('src',global.root+'static/images/loading.gif').css({width:300,maxWidth:'100%',height:10})).append('<br><span style="font-size:24px;line-height:48px"></span>').appendTo('body').hide();
    if(!locker.length) locker = $('<div id="screenLocker">').addClass('position-fixed bg-dark').css({top:0,left:0,right:0,bottom:0,zIndex:8888,opacity:0.8}).appendTo('body').hide();
    show = typeof show === 'boolean' ? show : !global.loadingVisible;
    if(show) {
        if(!global.loadingVisible) global.loadingOverflow = document.body.style.overflow;
        $('body').css('overflow','hidden');
        if(info == null) info = typeof language !== 'undefined' && language && language.sending ? language.sending : '正在处理，请稍候……';
        loading.find('span').text(String(info));
        locker.stop(true,true).show(); loading.stop(true,true).show();
        loading.css({top:Math.max(0, ($(window).height()-loading.outerHeight())/2),left:Math.max(0, ($(window).width()-loading.outerWidth())/2)});
    } else {
        if(global.loadingVisible) document.body.style.overflow = global.loadingOverflow || '';
        loading.stop(true,true).hide(); locker.stop(true,true).hide();
    }
    global.loadingVisible = show;
    return show;
}

/**
 * 尝试旧版浏览器对话框，不支持时回退普通窗口。
 * @param {string} url 目标地址。
 * @param {number} width 宽度像素。
 * @param {number} height 高度像素。
 * @param {boolean} mode true 为模态，false 为非模态。
 * @returns {Window|*|null} 浏览器返回的窗口/对话框结果，拦截弹窗时可为空。
 */
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

/**
 * @param {string} url 地址。
 * @param {number} width 宽度像素。
 * @param {number} height 高度像素。
 * @returns {Window|null} 新窗口，拦截时为 null。
 */
function openWindow(url,width,height) {
    return window.open(url, "new_window","height="+height+", width="+width+", top=0, left=0, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no, status=no, modal=yes");
}

/**
 * 异步等待，避免忙循环冻结浏览器；旧函数名保留，调用方应 await sleep(ms)。
 * @param {number} ms 等待毫秒数，负数按 0 处理，最大 2147483647。
 * @returns {Promise<void>} 定时器到期后完成，不阻塞页面。
 */
function sleep(ms=0) {
    ms = Number(ms);
    if(!Number.isFinite(ms) || ms > 2147483647) return Promise.reject(new RangeError('Invalid sleep duration'));
    return new Promise(function(resolve) { setTimeout(resolve, Math.max(0, ms)); });
}

/** 将普通字符串或元素（含 jQuery 对象）解析为待复制文字；#id/.class 必须匹配现有元素。 */
function copyValue(obj) {
    if(typeof obj === 'string' && !/^[#.]/.test(obj)) return obj;
    let element;
    try { element = $(obj); } catch(error) { return null; }
    if(!element.length) return null;
    const data = element.attr('data-copy');
    if(data !== undefined) return data;
    const value = element.val();
    return value !== undefined && value !== '' ? String(value) : element.text();
}

/**
 * 同步复制文字，保留 boolean 返回值；临时输入框移除后恢复焦点和选区。
 * @param {string|Element|jQuery} obj 普通文字、#id/.class 选择器或元素。
 * @returns {boolean} execCommand 的结果；不可用/拒绝/目标不存在时为 false。
 */
function copy(obj) {
    const value = copyValue(obj);
    if(value === null || !document.body) return false;
    const active = document.activeElement, selection = window.getSelection ? window.getSelection() : null, ranges = [];
    if(selection) for(let i=0;i<selection.rangeCount;i++) ranges.push(selection.getRangeAt(i).cloneRange());
    const start = active && active.selectionStart, end = active && active.selectionEnd;
    const area = document.createElement('textarea'); area.value=value;
    area.style.cssText='position:fixed;left:-9999px;top:0'; document.body.appendChild(area);
    let copied = false;
    try { area.select(); copied = typeof document.execCommand === 'function' && document.execCommand('copy'); }
    catch(error) { copied = false; }
    finally {
        area.remove();
        if(selection) { selection.removeAllRanges(); ranges.forEach(function(range) { selection.addRange(range); }); }
        if(active && typeof active.focus === 'function') active.focus({preventScroll:true});
        if(active && start !== null && start !== undefined && typeof active.setSelectionRange === 'function') active.setSelectionRange(start,end);
    }
    return !!copied;
}

/** 优先使用安全上下文的 Clipboard API；被拒绝时回退 copy，结果为 Promise<boolean>。 */
function copyAsync(obj) {
    const value = copyValue(obj);
    if(value === null) return Promise.resolve(false);
    if(navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        try { return Promise.resolve(navigator.clipboard.writeText(value)).then(function() { return true; }, function() { return copy(obj); }); }
        catch(error) { return Promise.resolve(copy(obj)); }
    }
    return Promise.resolve(copy(obj));
}

/**
 * 普通随机整数，闭区间内各整数等概率；不用于安全令牌。
 * @param {number} [min] 下限；只传一个参数时作为上限，下限为 0。
 * @param {number} [max] 上限，允许交换上下界，必须存在安全整数区间。
 * @returns {number} 无参数返回 [0,1) 小数；有参数返回区间整数。
 * @throws {RangeError} 参数非有限数或区间超出安全整数范围。
 */
function rndNum(min,max) {
    if(min === undefined) return Math.random();
    if(max === undefined) { max=min; min=0; }
    min=Number(min); max=Number(max);
    if(!Number.isFinite(min) || !Number.isFinite(max)) throw new RangeError('Finite random bounds required');
    if(min>max) { const swap=min; min=max; max=swap; }
    min=Math.ceil(min); max=Math.floor(max);
    if(max<min || !Number.isSafeInteger(min) || !Number.isSafeInteger(max) || !Number.isSafeInteger(max-min+1)) throw new RangeError('Invalid integer random bounds');
    return min + Math.floor(Math.random()*(max-min+1));
}

/**
 * 生成普通随机文本，不能作为认证令牌使用。
 * @param {number} len 选取次数，0—1000000；小数向下取整。
 * @param {string} [t_lst='0110'] 大写/小写/数字/汉字的四位开关，短开关沿用旧版补位。
 * @param {string|Array<string>} [c_lst=[]] 额外的字符串单元，可为多字符文本。
 * @returns {string} 没有任何候选字符时返回空串。
 */
function rndStr(len, t_lst='', c_lst=[]) {
    len=Number(len);
    if(!Number.isFinite(len) || len<0 || len>1000000) throw new RangeError('Invalid random string length');
    len=Math.floor(len); t_lst=(String(t_lst == null ? '' : t_lst)+'0110').slice(0,4);
    let chars='';
    if(t_lst.charAt(0)==='1') chars+='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if(t_lst.charAt(1)==='1') chars+='abcdefghijklmnopqrstuvwxyz';
    if(t_lst.charAt(2)==='1') chars+='1234567890';
    let extra=c_lst == null ? [] : (Array.isArray(c_lst) ? c_lst : [c_lst]);
    extra=extra.map(String).filter(function(value) { return value.length>0; });
    const chinese=t_lst.charAt(3)==='1' ? 40869-19968+1 : 0, total=chars.length+extra.length+chinese, result=[];
    if(!total) return '';
    for(let i=0;i<len;i++) {
        const at=rndNum(0,total-1);
        result.push(at<chars.length ? chars.charAt(at) : (at<chars.length+extra.length ? extra[at-chars.length] : String.fromCharCode(19968+at-chars.length-extra.length)));
    }
    return result.join('');
}

/**
 * 给可信 HTML 的文本片段插入水印，不跨标签拼接干扰 span；不是 HTML 消毒器。
 * @param {string|Element} obj 文本或元素；元素会更新 innerHTML。
 * @param {number} [rate=5] 插入阈值，越大越少；10 不产生随机干扰字。
 * @param {string} [copyright] 每个换行后的版权说明，以文本转义。
 * @param {string|null} [char_c=''] 逗号分隔的额外随机字符串单元。
 * @param {boolean} [jam_tag=false] 是否在文本片段内插入成对 span。
 * @returns {string} 生成的 HTML。
 */
function watermark(obj, rate=5, copyright='WaterMark Maker, Coded by Windy2000', char_c='', jam_tag=false) {
    const element=obj && typeof obj === 'object' && typeof obj.innerHTML === 'string' ? obj : null;
    let str=element ? element.innerHTML : String(obj == null ? '' : obj);
    rate=rate == null ? 5 : Number(rate); if(!Number.isFinite(rate)) rate=5;
    const chars=char_c == null || char_c === '' ? [] : String(char_c).split(',');
    const escapeText=function(value) { return String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); };
    const mark=function(text) { return '<span class="watermark">'+escapeText(text)+'</span>'; };
    str=str.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1\s*>/gi,'');
    const parts=str.split(/(<[^>]*>|&(?:#\d+|#x[\da-f]+|[a-z][\w]*);)/gi);
    const result=parts.map(function(part,index) {
        if(index%2) return part;
        let text='', opened=false;
        for(const char of part) {
            text+=char;
            if(char === '\n') text+=mark(rndStr(8,'1111',chars))+mark('['+(copyright == null ? '' : copyright)+']');
            else {
                if(jam_tag && rndNum(10)>rate) { text+=opened ? '</span>' : '<span>'; opened=!opened; }
                if(rndNum(10)>rate) text+=mark(rndStr(4,'1111',chars));
            }
        }
        return text+(opened ? '</span>' : '');
    }).join('');
    if(element) element.innerHTML=result;
    return result;
}

/**
 * @returns {{Android:boolean,BlackBerry:boolean,iOS:boolean,Windows:boolean,any:boolean}} UA 识别结果，包括桌面 UA 的触控 iPad。
 */
function isMobile() {
    let isMobile = {
        Android: (navigator.userAgent.match(/Android/i) ? true : false),
        BlackBerry: (navigator.userAgent.match(/BlackBerry/i) ? true : false),
        iOS: !!navigator.userAgent.match(/iPhone|iPad|iPod/i) || (navigator.platform==='MacIntel' && navigator.maxTouchPoints>1),
        Windows: (navigator.userAgent.match(/IEMobile/i) ? true : false)
    };
    isMobile.any = (isMobile.Android || isMobile.BlackBerry || isMobile.iOS || isMobile.Windows);
    return isMobile;
}

/**
 * UTF-8 文本与 Base64 转换；自动判断仅为兼容，含糊的纯字母文本请明确指定 mode。
 * @param {string} code 文本或标准 Base64 字符串，支持 Unicode 文本。
 * @param {boolean} [mode] true 解码、false 编码；省略时尝试解码合法的 UTF-8 Base64，失败则编码。
 * @returns {string} 转换结果；显式解码非法输入时抛出异常。
 */
function base64(code, mode) {
    code=String(code);
    const automatic=mode === undefined;
    const decode=function(value) {
        const binary=window.atob(value);
        return decodeURIComponent(Array.from(binary,function(char) { return '%'+char.charCodeAt(0).toString(16).padStart(2,'0'); }).join(''));
    };
    if(mode === true) return decode(code);
    if(automatic && /^[A-Za-z0-9+/]*={0,2}$/.test(code) && code.length%4!==1) {
        try { return decode(code); } catch(error) { /* 自动模式下按原始文字编码。 */ }
    }
    const binary=encodeURIComponent(code).replace(/%([0-9A-F]{2})/g,function(token,hex) { return String.fromCharCode(parseInt(hex,16)); });
    return window.btoa(binary);
}

/**
 * @param {string} str UTF-8 文本，ASCII 行为兼容旧版。
 * @returns {string} 32 位小写 MD5，仅用于非认证用途的兼容标识。
 */
function md5(str) {
    // 先按 UTF-8 转字节；ASCII 结果保持一致，Unicode 结果与 PHP/标准 MD5 一致。
    str=encodeURIComponent(String(str)).replace(/%([0-9A-F]{2})/g,function(token,hex) { return String.fromCharCode(parseInt(hex,16)); });
    let rhex = function(num) {
        let hex_chr = "0123456789abcdef";
        let result = "";
        for(let i=0; i<=3; i++) result += hex_chr.charAt((num >> (i * 8 + 4)) & 0x0F) + hex_chr.charAt((num >> (i * 8)) & 0x0F);
        return result;
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
        const olda = a, oldb = b, oldc = c, oldd = d;
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

/**
 * @param {*} para 调试值。
 * @param {boolean} [mode=false] true 追加，否则覆盖。
 * @returns {void} 更新页面调试区。
 */
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
/**
 * @param {...*} values 依次写入控制台的值。
 * @returns {void}
 */
function c(){
    for(let x in arguments) console.log(arguments[x]);
}
/**
 * @param {...*} values 依次弹出提示的值。
 * @returns {void}
 */
function a(){
    for(let x in arguments) alert(arguments[x]);
}
/**
 * @param {string|null} msg 可信提示 HTML；空值隐藏。
 * @returns {void} 更新页面提示区。
 */
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

/**
 * @param {Object} obj 对象。
 * @param {Function} [func_show=alert] 每项的输出函数。
 * @returns {void}
 */
function checkObj(obj, func_show) {
    if(typeof(func_show)!=="function") func_show = alert;
    for(let x in obj) func_show(x + " : " + obj[x]);

}

/** 报告错误并保留浏览器控制台默认错误；仅 debug 模式弹出/调用自定义报告器。 */
function reportError(msg, url, line, column, error) {
    const text='You have found an error as below:\n\nErr: '+msg+'\n\n'+(url || '')+':'+(line || 0)+':'+(column || 0);
    if(typeof setting === 'undefined' || (setting && setting.debug)) {
        const handler=typeof global.err_report_func === 'function' ? global.err_report_func : alert;
        handler(text,true,error);
    }
    return false;
}

/**
 * 等待 language/setting 后调用函数，各次注册相互独立，返回取消函数。
 * @param {Function|string} func 回调或 window 上的函数名。
 * @param {Array} [params=[]] 回调参数。
 * @param {number} [timeout=15000] 最长等待毫秒数；超时写控制台并释放定时器。
 * @param {Object} [context=window] 回调 this。
 * @param {number} [interval=100] 轮询间隔，至少 10 毫秒。
 * @returns {Function} 取消当前注册；重复取消无副作用。
 */
function checkNrun(func, params=[], timeout=15000, context=window, interval=100) {
    if(typeof func !== 'function' && typeof func !== 'string') throw new TypeError('Callback required');
    if(!Array.isArray(params)) throw new TypeError('Callback parameters must be an array');
    timeout=Number(timeout); interval=Number(interval);
    if(!Number.isFinite(timeout) || timeout<0 || !Number.isFinite(interval)) throw new RangeError('Invalid readiness timeout');
    interval=Math.max(10,interval);
    let timer=null, cancelled=false; const deadline=Date.now()+timeout;
    function poll() {
        if(cancelled) return;
        if(typeof language === 'object' && language && typeof setting === 'object' && setting) {
            cancelled=true;
            const callback=typeof func === 'function' ? func : window[func];
            if(typeof callback !== 'function') { console.error('Ready callback not found: '+func); return; }
            callback.apply(context,params);
        } else if(Date.now()>=deadline) {
            cancelled=true; console.warn('language/setting loading timed out');
        } else timer=setTimeout(poll,Math.min(interval,Math.max(0,deadline-Date.now())));
    }
    poll();
    return function() { cancelled=true; if(timer !== null) clearTimeout(timer); };
}

/**
 * 检查配置是否就绪；需要自动重试时显式传入 callback 和参数，兼容严格模式。
 * @param {number} [timer=500] 重试间隔毫秒数。
 * @param {Function|string|null} [callback=null] 未就绪时安排的回调。
 * @param {Array} [params=[]] 回调参数。
 * @returns {boolean} 两个配置对象均已定义时为 true；不再读取 caller/arguments。
 */
function checkSetting(timer=500, callback=null, params=[]) {
    if(typeof language === 'object' && language && typeof setting === 'object' && setting) return true;
    if(callback !== null) checkNrun(callback,params,15000,window,timer);
    return false;
}



/**
 * 更新 URL 查询参数，保留查询串路由及锚点，不解码或改写路径。
 * @param {string} url 原始 URL，可含 index.php?/route/&key=value。
 * @param {Object} params 参数字典；null 删除，数组生成重复参数。
 * @returns {string} 参数按 URL 标准编码后的 URL。
 */
function queryURL(url, params={}) {
    let hash = '', at = url.indexOf('#');
    if(at !== -1) { hash = url.slice(at); url = url.slice(0, at); }
    at = url.indexOf('?');
    let path = at === -1 ? url : url.slice(0, at);
    let query = at === -1 ? '' : url.slice(at + 1), route = '';
    if(/\/index\.php$/.test(path) && query && query.split('&')[0].indexOf('=') === -1) {
        let parts = query.split('&'); route = parts.shift(); query = parts.join('&');
    }
    let values = new URLSearchParams(query);
    Object.keys(params).forEach(function(key) {
        values.delete(key);
        if(params[key] !== null && params[key] !== undefined) {
            (Array.isArray(params[key]) ? params[key] : [params[key]]).forEach(function(value) { values.append(key, value); });
        }
    });
    query = values.toString();
    return path + (route ? '?' + route + (query ? '&' + query : '') : (query ? '?' + query : '')) + hash;
}

/**
 * 将应用相对链接转换为当前路由模式的地址，查询值和锚点保持原样。
 * @param {string} url 相对路径或查询串；完整 URL、根路径及锚点直接保留。
 * @param {string} prefix 当前模块路径，默认 global.root_fix；可为已转换的路由地址。
 * @returns {string} 可直接用于 href、action 或 location 的地址；可重复转换。
 */
function routeURL(url, prefix=global.root_fix) {
    if(/^(?:[a-z][a-z0-9+.-]*:|\/|#)/i.test(url)) return url;
    const base = setting.url_prefix;
    if(prefix === undefined) prefix = setting.url_prefix_app || (setting.app + '/');
    const entry=(setting.path_root || global.root || '/').replace(/\/?$/, '/')+'index.php';
    if(prefix.indexOf(entry+'?')===0) prefix=prefix.slice(entry.length+1).split('&')[0];
    else if(prefix.indexOf(entry+'/')===0) prefix=prefix.slice(entry.length+1);
    else if(base && prefix.indexOf(base) === 0) prefix = prefix.slice(base.length);
    let target = prefix + url;
    const at = target.search(/[?#]/);
    let path = at === -1 ? target : target.slice(0, at);
    let suffix = at === -1 ? '' : target.slice(at);
    const fix = (setting.url_fix || '').replace(/^\/+|\/+$/g, '');
    path = path.replace(/^\/+/, '');
    if(fix && (path === fix || path.indexOf(fix + '/') === 0)) path = path.slice(fix.length).replace(/^\/+/, '');
    path = path.replace(/\/+/g, '/');
    // 只有 index.php? 后面的第一段属于路由，业务查询才使用 & 分隔。
    if(base.endsWith('?') && suffix.charAt(0) === '?') suffix = '&' + suffix.slice(1);
    return base + path + suffix;
}

/**
 * 批量转换容器内的链接、表单和图片；keep-url 标记保留原地址。
 * @param {string} prefix 应用或模块路由前缀，默认 global.root_fix。
 * @param {Element|jQuery} context 扫描容器，默认页面 body。
 * @returns {boolean|undefined} 已处理返回 true；配置未就绪时等待后重试。
 */
function setURL(prefix=global.root_fix, context=window.document.body) {
    if(!checkSetting(100, setURL, [prefix,context])) return;
    const list = {'a':'href', 'form':'action', 'img':'src'};
    for(let tag in list) {
        $(tag+'['+list[tag]+']', context).each(function(){
            if($(this).attr('keep-url') !== undefined) return;
            $(this).attr(list[tag], routeURL($(this).attr(list[tag]), prefix));
            // 浏览器原生 GET 提交会覆盖 action 的查询串，查询串路由需要保留首段。
            if(tag === 'form' && (this.method || 'get').toLowerCase() === 'get') {
                $(this).off('submit.msRoute').on('submit.msRoute', function(event) {
                    const submitter=event.originalEvent && event.originalEvent.submitter;
                    if(submitter && submitter.hasAttribute('formmethod') && submitter.formMethod.toLowerCase()!=='get') return;
                    const action=submitter && submitter.hasAttribute('formaction') ? submitter.formAction : $(this).attr('action');
                    if(!/\/index\.php\?[^=&]+/.test(action)) return;
                    if(event.isDefaultPrevented()) return;
                    event.preventDefault();
                    const params = Object.create(null);
                    const fields=$(this).serializeArray();
                    if(submitter && submitter.name && !$(submitter).is(':disabled')) fields.push({name:submitter.name,value:submitter.value});
                    fields.forEach(function(item) {
                        if(Object.prototype.hasOwnProperty.call(params, item.name)) {
                            if(!Array.isArray(params[item.name])) params[item.name] = [params[item.name]];
                            params[item.name].push(item.value);
                        } else params[item.name] = item.value;
                    });
                    const target=submitter && submitter.hasAttribute('formtarget') ? submitter.formTarget : this.target;
                    const url=queryURL(action,params);
                    if(target && target.toLowerCase()!=='_self') window.open(url,target);
                    else { global.alert_leave=false; location.href=url; }
                });
            }
        });
    }
    return true;
}

/** 定位页面内锚点（空串回顶部），不将锚点插入 CSS 选择器；动画与就绪定时器独立。 */
function gotoAnchor(theAnchor='') {
    if(global.anchorAnimating) return false;
    let name=String(theAnchor).replace(/^#/,'');
    try { name=decodeURIComponent(name); } catch(error) { /* 保留不完整的百分号文本。 */ }
    const target=document.getElementById(name) || Array.from(document.getElementsByName(name)).find(function(node) { return node.tagName==='A'; });
    if(name && !target) return false;
    const top=target ? Math.max(0,$(target).offset().top-70) : 0;
    const root=$(document.scrollingElement || document.documentElement);
    const duration=Math.min(1000,Math.abs(root.scrollTop()-top));
    global.anchorAnimating=true;
    root.stop(true).animate({scrollTop:top},duration,function() { global.anchorAnimating=false; });
    return false;
}

/** 更新历史地址和标题数据，不重复注册无实际行为的 popstate 监听器。 */
function setLocation(path, name='') {
    window.history.pushState({title:name,url:path},name,path);
}

/**
 * @param {Function} func 页面初始化回调；非函数忽略。
 * @returns {void} 注册到 global.func。
 */
function ms_func_reg(func) {
    if(typeof(func)!=='function') return;
    global.func.push(func);
}

/**
 * @returns {void} 绑定本脚本事件并执行当前回调快照；重复调用不叠加事件监听器。
 */
function ms_func_run() {
    // 只管理本脚本的离页标记，不清除其他模块的 beforeunload 监听器。
    $(document).off('submit.msLeave','form').on('submit.msLeave','form',function(event) {
        if(event.isDefaultPrevented()) return;
        const previous=global.alert_leave;
        global.alert_leave=false;
        Promise.resolve().then(function() { if(event.isDefaultPrevented()) global.alert_leave=previous; });
    });
    $(document).off('click.msAnchor','a[href^="#"]').on('click.msAnchor','a[href^="#"]',function(event) {
        if(event.isDefaultPrevented() || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || (event.button !== undefined && event.button !== 0)) return;
        if($(this).attr('data-toggle') == null && $(this).attr('data-bs-toggle') == null) {
            event.preventDefault(); gotoAnchor($(this).attr('href'));
        }
    });
    global.func.slice().forEach(function(func) { if(typeof func === 'function') func(); });
}



// 由 jQuery 判断是否跨域，只对当前来源及框架明确信任的来源自动携带凭据。
$.ajaxPrefilter(function(options) {
    if(options.xhrFields && options.xhrFields.withCredentials !== undefined) return;
    try {
        const target=new URL(options.url || location.href,document.baseURI);
        const trusted=target.origin===location.origin || (window.msSecurity && window.msSecurity.origins.indexOf(target.origin)>=0);
        if(trusted && /^https?:$/.test(target.protocol)) options.xhrFields=Object.assign({},options.xhrFields,{withCredentials:true});
    } catch(error) { /* 非 HTTP 或非法 URL 留给 jQuery 正常处理。 */ }
});

$(window).off('beforeunload.msLeave').on('beforeunload.msLeave',function(event) {
    if(global.alert_leave===false) return;
    const msg=typeof global.alert_leave === 'string' ? global.alert_leave : (typeof language !== 'undefined' && language && language.alert_leave ? language.alert_leave : 'Some changes have not been submitted. Leave this page?');
    const nativeEvent=event.originalEvent || event;
    nativeEvent.returnValue=msg;
    return msg;
});



$(function(){
    $('input[type=password]').each(function(){
        if(this.getAttribute('data-ms-password') === 'true') return;
        this.setAttribute('data-ms-password','true');
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
