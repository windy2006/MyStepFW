/*
 * jQuery JSON Plugin
 * version: 2.1 (2009-08-14)
 *
 * This document is licensed as free software under the terms of the
 * MIT License: http://www.opensource.org/licenses/mit-license.php
 *
 * Brantley Harris wrote this plugin. It is based somewhat on the JSON.org 
 * website's http://www.json.org/json2.js, which proclaims:
 * "NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.", a sentiment that
 * I uphold.
 *
 * It is also influenced heavily by MochiKit's serializeJSON, which is 
 * copyrighted 2005 by Bob Ippolito.
 */
 
(function($) {
    /** jQuery.toJSON( json-serializble )
        Converts the given argument into a JSON respresentation.

        If an object has a "toJSON" function, that will be used to get the representation.
        Non-integer/string keys are skipped in the object, as are keys that point to a function.

        json-serializble:
            The *thing* to be converted.
     **/
    $.toJSON = function(o)
    {
        if (typeof(JSON) == 'object' && JSON.stringify)
            return JSON.stringify(o);
        
        var type = typeof(o);
    
        if (o === null)
            return "null";
    
        if (type == "undefined")
            return undefined;
        
        if (type == "number" || type == "boolean")
            return o + "";
    
        if (type == "string")
            return $.quoteString(o);
    
        if (type == 'object')
        {
            if (typeof o.toJSON == "function") 
                return $.toJSON( o.toJSON() );
            
            if (o.constructor === Date)
            {
                var month = o.getUTCMonth() + 1;
                if (month < 10) month = '0' + month;

                var day = o.getUTCDate();
                if (day < 10) day = '0' + day;

                var year = o.getUTCFullYear();
                
                var hours = o.getUTCHours();
                if (hours < 10) hours = '0' + hours;
                
                var minutes = o.getUTCMinutes();
                if (minutes < 10) minutes = '0' + minutes;
                
                var seconds = o.getUTCSeconds();
                if (seconds < 10) seconds = '0' + seconds;
                
                var milli = o.getUTCMilliseconds();
                if (milli < 100) milli = '0' + milli;
                if (milli < 10) milli = '0' + milli;

                return '"' + year + '-' + month + '-' + day + 'T' +
                             hours + ':' + minutes + ':' + seconds + 
                             '.' + milli + 'Z"'; 
            }

            if (o.constructor === Array) 
            {
                var ret = [];
                for (var i = 0; i < o.length; i++)
                    ret.push( $.toJSON(o[i]) || "null" );

                return "[" + ret.join(",") + "]";
            }
        
            var pairs = [];
            for (var k in o) {
                var name;
                var type = typeof k;

                if (type == "number")
                    name = '"' + k + '"';
                else if (type == "string")
                    name = $.quoteString(k);
                else
                    continue;  //skip non-string or number keys
            
                if (typeof o[k] == "function") 
                    continue;  //skip pairs where the value is a function.
            
                var val = $.toJSON(o[k]);
            
                pairs.push(name + ":" + val);
            }

            return "{" + pairs.join(", ") + "}";
        }
    };

    /** jQuery.evalJSON(src)
        Evaluates a given piece of json source.
     **/
    $.evalJSON = function(src)
    {
        if (typeof(JSON) == 'object' && JSON.parse)
            return JSON.parse(src);
        return eval("(" + src + ")");
    };
    
    /** jQuery.secureEvalJSON(src)
        Evals JSON in a way that is *more* secure.
    **/
    $.secureEvalJSON = function(src)
    {
        if (typeof(JSON) == 'object' && JSON.parse)
            return JSON.parse(src);
        
        var filtered = src;
        filtered = filtered.replace(/\\["\\\/bfnrtu]/g, '@');
        filtered = filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
        filtered = filtered.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
        
        if (/^[\],:{}\s]*$/.test(filtered))
            return eval("(" + src + ")");
        else
            throw new SyntaxError("Error parsing JSON, source is not valid.");
    };

    /** jQuery.quoteString(string)
        Returns a string-repr of a string, escaping quotes intelligently.  
        Mostly a support function for toJSON.
    
        Examples:
            >>> jQuery.quoteString("apple")
            "apple"
        
            >>> jQuery.quoteString('"Where are we going?", she asked.')
            "\"Where are we going?\", she asked."
     **/
    $.quoteString = function(string)
    {
        if (string.match(_escapeable))
        {
            return '"' + string.replace(_escapeable, function (a) 
            {
                var c = _meta[a];
                if (typeof c === 'string') return c;
                c = a.charCodeAt();
                return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
            }) + '"';
        }
        return '"' + string + '"';
    };
    
    var _escapeable = /["\\\x00-\x1f\x7f-\x9f]/g;
    
    var _meta = {
        '\b': '\\b',
        '\t': '\\t',
        '\n': '\\n',
        '\f': '\\f',
        '\r': '\\r',
        '"' : '\\"',
        '\\': '\\\\'
    };
})(jQuery);

/*
example
$.cookie('name', 'value');
$.cookie('name', 'value', {expires: 7, path: '/', domain: 'jquery.com', secure: true});
$.cookie('name', null);
*/

jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

/**
 * 
 * credits for this plugin go to brandonaaron.net
 *
 * unfortunately his site is down
 * 
 * @param {Object} up
 * @param {Object} down
 * @param {Object} preventDefault
 */
jQuery.fn.extend({
    mousewheel: function(up, down, preventDefault) {
        return this.hover(

        function() {
            jQuery.event.mousewheel.giveFocus(this, up, down, preventDefault);
        }, function() {
            jQuery.event.mousewheel.removeFocus(this);
        });
    },
    mousewheeldown: function(fn, preventDefault) {
        return this.mousewheel(function() {}, fn, preventDefault);
    },
    mousewheelup: function(fn, preventDefault) {
        return this.mousewheel(fn, function() {}, preventDefault);
    },
    unmousewheel: function() {
        return this.each(function() {
            jQuery(this).unmouseover().unmouseout();
            jQuery.event.mousewheel.removeFocus(this);
        });
    },
    unmousewheeldown: jQuery.fn.unmousewheel,
    unmousewheelup: jQuery.fn.unmousewheel
});


jQuery.event.mousewheel = {
    giveFocus: function(el, up, down, preventDefault) {
        if (el._handleMousewheel) jQuery(el).unmousewheel();

        if (preventDefault == window.undefined&& down&& down.constructor != Function) {
            preventDefault = down;
            down = null;
        }

        el._handleMousewheel = function(event) {
            if (!event) event = window.event;
            if (preventDefault) if (event.preventDefault) event.preventDefault();
            else event.returnValue = false;
            var delta = 0;
            if (event.wheelDelta) {
                delta = event.wheelDelta / 120;
                if (window.opera) delta = -delta;
            } else if (event.detail) {
                delta = -event.detail / 3;
            }
            if (up&&
            (delta >0 || !down)) up.apply(el, [event, delta]);
            else if (down&& delta <0) down.apply(el, [event, delta]);
        };

        if (window.addEventListener) window.addEventListener('DOMMouseScroll', el._handleMousewheel, false);
        window.onmousewheel = document.onmousewheel = el._handleMousewheel;
    },

    removeFocus: function(el) {
        if (!el._handleMousewheel) return;

        if (window.removeEventListener) window.removeEventListener('DOMMouseScroll', el._handleMousewheel, false);
        window.onmousewheel = document.onmousewheel = null;
        el._handleMousewheel = null;
    }
};

jQuery.fn.outerHTML = function(s) {
    return (s) ? this.before(s).remove() : $('<p>').append(this.eq(0).clone()).html();
};

jQuery.fn.cssText = function(css) {
    var css_list = css.trim().split(";");
    var cur_style = null;
    for(var i=0,m=css_list.length;i<m;i++) {
        css_list[i] = css_list[i].trim();
        if(css_list[i].length<3) continue;
        cur_style = css_list[i].split(":");
        if(cur_style.length==2) $(this).css(cur_style[0].trim(), cur_style[1].trim());
    }
    return this;
};

/**
 * 框架调用：powerImage(options|"destroy")
 * 功能说明：返回原选择集；selector 是图片地址属性名，load/error 事件携带地址；destroy/dispose 取消观察和加载任务。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/** jquery.powerImage.js — Windy2000. Preserve data-src lazy images and ALT-wheel zoom. */
(function($){
    $.fn.powerImage=function(options){
        return this.each(function(){
            const root=this,$root=$(root),old=$root.data('powerImage');
            if(typeof options==='string'){if(!['destroy','dispose'].includes(options))throw new Error('Unknown powerImage method: '+options);if(old)old.dispose();return;}
            if(old)old.dispose();
            const opt=$.extend({selector:'data-src',image:'/static/images/loading_img.gif',width:600,zoom:true},options);
            const id=$.data(document,'powerImageSerial')||0;$.data(document,'powerImageSerial',id+1);
            const ns='.powerImage'+id,items=[],view=root.ownerDocument.defaultView||window;
            let observer,frame=0,timer,disposed=false;
            $root.find('img['+opt.selector+']').filter(function(){return !opt._skipTitleImage||!this.classList.contains('title_img');}).each(function(){
                const el=this,$el=$(el),url=$el.attr(opt.selector),style=el.getAttribute('style'),title=el.title;
                const item={el,$el,url,style,title,state:'waiting',preload:null};items.push(item);
                if(el.title===''&&el.alt)el.title=el.alt;
                el.src=opt.image;$el.css({width:32,height:32,marginBottom:'20px'});
                if(opt.zoom){
                    if(el.title)el.title+='\n';el.title+='Hold the ALT button and wheel the mouse to zoom in or zoom out the image.';
                    const event=$.event.special.mousewheel?'mousewheel':'wheel';
                    $el.on(event+ns,function(e,delta){if(!e.altKey)return;const native=e.originalEvent||e,step=delta||-Math.sign(native.deltaY||0);const zoom=(parseFloat(el.style.zoom)||1)/(String(el.style.zoom).endsWith('%')?100:1);el.style.zoom=Math.max(.1,zoom+step*.1);e.preventDefault();});
                }
            });
            function restore(item){
                if(item.style===null)item.el.removeAttribute('style');else item.el.setAttribute('style',item.style);
                const width=Number(opt.width);if(Number.isFinite(width)&&width>0&&item.$el.width()>width)item.$el.css({width,height:'auto'});
                else if(typeof opt.width==='string'&&opt.width.endsWith('%'))item.$el.css({maxWidth:opt.width,height:'auto'});
            }
            function load(item){
                if(disposed||item.state!=='waiting')return;item.state='loading';if(observer)observer.unobserve(item.el);
                const image=new view.Image();item.preload=image;
                image.onload=()=>{if(disposed)return;item.state='loaded';item.el.src=item.url;restore(item);item.$el.removeAttr('data-load-error').hide().fadeIn('slow').trigger('powerImage:load',[item.url]);image.onload=image.onerror=null;item.preload=null;};
                image.onerror=()=>{if(disposed)return;item.state='error';restore(item);item.el.src=item.url;item.$el.attr('data-load-error','true').trigger('powerImage:error',[item.url]);image.onload=image.onerror=null;item.preload=null;};
                image.src=item.url;
            }
            function scan(){frame=0;if(disposed)return;const top=$(view).scrollTop(),bottom=top+$(view).height();items.forEach(item=>{if(item.state!=='waiting')return;const position=item.$el.offset();if(position&&position.top+item.$el.outerHeight()>=top&&position.top<=bottom)load(item);});}
            function schedule(){if(!frame)frame=(view.requestAnimationFrame||view.setTimeout).call(view,scan);}
            if('IntersectionObserver' in view){observer=new view.IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){const item=items.find(item=>item.el===entry.target);if(item)load(item);}}));items.forEach(item=>observer.observe(item.el));}
            else {$(view).on('resize'+ns+' scroll'+ns,schedule);timer=view.setTimeout(scan,500);}
            $root.data('powerImage',{dispose(){disposed=true;if(observer)observer.disconnect();view.clearTimeout(timer);(view.cancelAnimationFrame||view.clearTimeout).call(view,frame);$(view).off(ns);items.forEach(item=>{item.$el.off(ns).stop(true,true);if(item.preload){item.preload.onload=item.preload.onerror=null;item.preload.src='';}if(item.state==='waiting'||item.state==='loading')item.el.src=item.url;if(item.style===null)item.el.removeAttribute('style');else item.el.setAttribute('style',item.style);item.el.title=item.title;});$root.removeData('powerImage');}});
        });
    };
})(jQuery);

(function($){const enhance=$.fn.powerImage;$.fn.powerImage=function(options){return enhance.call(this,typeof options==='string'?options:$.extend({selector:'src',image:'/images/loading_img.gif',_skipTitleImage:true},options));};})(jQuery);
/**
 * 框架调用：showImage(options|"destroy") / imageWall(options|"destroy")
 * 功能说明：返回原选择集；showImage 每个源图集使用独立 container_id，destroy/dispose 取消轮播并恢复容器。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/**
 * jquery.showImage.js
 * Image Marquee show by windy2000
 */
jQuery.fn.showImage = function(options) {
    if(typeof options==='string'){if(!['destroy','dispose'].includes(options))throw new Error('Unknown showImage method: '+options);return this.each(function(){const old=$(this).data('showImage');if(old)old.dispose();});}
    const source=$(this),previous=source.data('showImage');if(previous)previous.dispose();
    const serial=$.data(document,'showImageSerial')||0;$.data(document,'showImageSerial',serial+1);
    const ns='.showImage'+serial;let resizeTimer;
    let defaults = {
        obj: null,
        container_width: 640,
        img_height: 420,
        thumb_width: 120,
        thumb_height: 80,
        thumb_margin: 12,
        thumb_width_offset: 132,
        container_id: "showImage",
        adjust_pos: 0,
        adjust_left: 0,
        interval: 2
    };
    let params = $.extend({}, defaults, options || {});
    params.obj = this;
    params.thumb_width_offset = params.thumb_width + params.thumb_margin;
    let container = $("#"+params.container_id);
    let container_show = null;
    let container_list = null;
    let container_wrapper = null;
    let img_list = $(this).find("img");
    let img_cnt = img_list.length;
    let the_width = 0;
    let repeat_times = 4;
    let act_img = null;
    let marquee_interval = 0;
    let running = false;
    let rate;

    if(!img_cnt||!container.length)return this;
    const original=container.html(),originalStyle=container.attr('style'),hadClass=container.hasClass('showImage');
    container.addClass("showImage");
    container.css("width",params.container_width);
    rate=container.width()/params.thumb_width_offset;
    if(isNaN(params.interval) || params.interval<=0) params.interval = 2;

    params.adjust_pos = Math.round(rate/2)-1;
    params.adjust_left = params.thumb_width_offset*(rate - Math.floor(rate))/2;
    if(Math.floor(rate)%2) {
        params.adjust_left -= params.thumb_width_offset;
    } else {
        params.adjust_left -= params.thumb_width_offset/2;
    }

    container.on('contextmenu'+ns,function(){return false;}).on('selectstart'+ns,function(){return false;});
    container.show();
    // 透明占位图不发起相对路径请求，兼容多级路由和子目录部署。
    $("<div/>").addClass("container_show").html('<span class="jump back">&nbsp;</span><div class="main"><a href="###" target="_blank" title="Click to show the image in a new window."><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" /></a></div><span class="jump forward">&nbsp;</span>').appendTo(container);
    $("<div/>").addClass("container_list").html('<a class="arrow back">&nbsp;</a><div class="wrapper"></div><a class="arrow forward">&nbsp;</a>').appendTo(container);
    container_show = container.find(".container_show");
    container_show.css("width", '100%');
    container_show.css("height", 'auto');
    container_show.find(".jump").css({"opacity":"0","height":'100%'}).click(function(){
        switch_img($(this).hasClass("back")?-1:1);
        this.blur();
        return false;
    });
    container_show.find("img").css({'height':params.img_height,'width':'auto',"max-width":Math.max(0,container.width()-20)});
    container_list = container.find(".container_list");
    container_wrapper = container_list.find(".wrapper");
    container_wrapper.css("left",params.adjust_left);
    container_list.find("a").css("z-index",999);
    container_list.find("a").first().click(function(){switch_img(-1)});
    container_list.find("a").last().click(function(){switch_img(1)});
    let cur_img = "";
    let cur_title = "";
    for(let i=0;i<img_cnt;i++) {
        cur_img = img_list.get(i).src;
        cur_title = img_list.get(i).alt;
        if(cur_title.length===0) cur_title = img_list.get(i).title;
        $("<img/>").attr("src", cur_img).attr("title", cur_title).css({"width":params.thumb_width,"height":params.thumb_height}).appendTo(container_wrapper);
    }
    the_width = params.thumb_width_offset * img_cnt;
    repeat_times = Math.ceil(container.width()*2/the_width);
    if(repeat_times<2) repeat_times = 2;
    container_wrapper.html((new Array(repeat_times+1)).join(container_wrapper.html()));
    container_wrapper.width(the_width*repeat_times);
    container_wrapper.find("img").hover(
        function () {
            if($(this).attr("active")!=="y") $(this).css("opacity", "0.8");
        },
        function () {
            if($(this).attr("active")!=="y") $(this).css("opacity", "0.3");
        }
    ).click(function(){
        if($(this).attr("active")==="y") return;
        doIt(false);
        let the_step = Math.ceil(($(act_img).position().left-$(this).position().left)/params.thumb_width_offset);
        if(the_step<=0) the_step -= 1;
        switch_img(the_step);
        doIt(true);
    });

    let doIt = function(mode=true) {
        if(mode) {
            if(running) return;
            if(img_cnt>1)marquee_interval = setInterval(switch_img, params.interval*1000);
        } else {
            if(running) clearInterval(marquee_interval);
        }
        running = !!mode;
    }

    let switch_img = function(step) {
        if(step==null) step = -1;
        let the_left = container_wrapper.position().left;
        let the_step = the_left + step * params.thumb_width_offset;
        container_wrapper.find("img").css("opacity", "0.3");
        container_wrapper.find("img").attr("active", "n");
        if(the_step>0) {
            container_wrapper.css("left", the_step-the_width-params.thumb_width_offset);
            the_step -= the_width;
        } else if(-the_step>the_width) {
            container_wrapper.css("left", the_width+the_step+params.thumb_width_offset);
            the_step += the_width;
        }
        container_show.find("img").stop(true,false).animate({"opacity": 0.1}, 800);
        container_wrapper.stop(true,false).animate({"left":the_step},1000,function(){
            let the_idx = Math.ceil(-the_step/params.thumb_width_offset);
            const images=container_wrapper.find("img");act_img=images.get(((the_idx+params.adjust_pos)%images.length+images.length)%images.length);
            if(!act_img)return;
            $(act_img).css("opacity", "1");
            $(act_img).attr("active", "y");
            container_show.find("a").attr("href", act_img.src).attr("title", act_img.title);
            container_show.find("img").attr("src", act_img.src);
            container_show.find("img").animate({"opacity": 1}, 500);
        });
    }

    let reset = function() {
        doIt(false);

        $(params.obj).showImage(params);
    };

    switch_img(1 - img_cnt + params.adjust_pos);
    doIt(true);

    container.on('mouseenter'+ns,function(){doIt(false);}).on('mouseleave'+ns,function(){doIt(true);});

    $(window).on('resize'+ns,function(){
        if(!isNaN(params.container_width))return;
        clearTimeout(resizeTimer);resizeTimer=setTimeout(reset,200);
    });
    source.data('showImage',{dispose(){
        clearTimeout(resizeTimer);clearInterval(marquee_interval);running=false;$(window).off(ns);container.off(ns);
        container.find('div,span,a,img').stop(true,false);container.html(original);if(originalStyle===undefined)container.removeAttr('style');else container.attr('style',originalStyle);if(!hadClass)container.removeClass('showImage');source.removeData('showImage');
    }});
    return this;
};

/**
 * jquery.imageWall.js
 * Image Marquee show by windy2000
 */
jQuery.fn.imageWall = function(options) {
    if(typeof options==='string'){if(!['destroy','dispose'].includes(options))throw new Error('Unknown imageWall method: '+options);return this.each(function(){const old=$(this).data('imageWall');if(old)old.dispose();});}
    if(this.length>1)return this.each(function(){$(this).imageWall(options);});
    const old=$(this).data('imageWall');if(old)old.dispose();
    let timer,disposed=false;const pending=new Set();
    let defaults = {
        size_h: 3,
        size_v: 2,
        img_width: 150,
        img_height: 60,
        interval: 5,
        speed: 500,
        tag: "img"
    };
    let params = $.extend({}, defaults, options || {});
    let container = $(this);
    const original=container.html(),originalStyle=container.attr('style');
    if(!container.find(params.tag).length)return this;
    if(![params.size_h,params.size_v,params.img_width,params.img_height,params.speed,params.interval].every(value=>Number.isFinite(Number(value))&&Number(value)>0))return this;
    params.size_h=Math.floor(Number(params.size_h));params.size_v=Math.floor(Number(params.size_v));if(!params.size_h||!params.size_v)return this;
    let img_cnt = params.size_h*params.size_v;
    let img_content = container.html().replace(/\s*[\r\n]+\s*/g, "");
    let repeat_times = Math.ceil(img_cnt/container.find(params.tag).length)+1;
    let show_done = true;
    var rebuild_list = function(thecontainer) {
        for(let i=0;i<img_cnt;i++) {
            thecontainer.find(params.tag).first().appendTo(thecontainer);
        }
    };
    var setBack = function() {
        container.children("div").last().prependTo(container);
    }
    params.interval *= 1000;
    if(params.interval<params.speed*(img_cnt+1)) params.interval = params.speed*(img_cnt+2);
    img_content = (new Array(repeat_times+1)).join(img_content);
    container.css({"width":(params.size_h*params.img_width),"height":(params.size_v*params.img_height),"overflow":"hidden","position":"relative"});
    container.html('<div></div><div></div>');
    container.children("div").css({"position":"absolute","top":0,"left":0,"z-index":0}).html(img_content);
    container.find(params.tag).css({"display":"block","float":"left","width":params.img_width,"height":params.img_height,"overflow":"hidden"});
    container.find("img").css({"display":"block","float":"left","width":params.img_width,"height":params.img_height,"border":0});
    rebuild_list(container.children("div").eq(0));
    timer=setInterval(function(){
        if(disposed||show_done === false) return;
        let cur_obj = container.children("div").last();
        let cur_idx = 0;
        show_done = false;
        let i;
        for(i=0;i<img_cnt;i++) {
            schedule(function(){cur_obj.find(params.tag+":eq("+cur_idx+")").animate({"opacity":"0"},params.speed*3);cur_idx++;}, params.speed*i);
        }
        schedule(function(){
            setBack();
            rebuild_list(container.children("div").first());
            container.children("div").first().find(params.tag).css("opacity", "1");
            show_done = true;
        }, params.speed*(i+2));
    }, params.interval);
    function schedule(fn,delay){const id=setTimeout(()=>{pending.delete(id);if(!disposed)fn();},delay);pending.add(id);}
    container.data('imageWall',{dispose(){disposed=true;clearInterval(timer);pending.forEach(clearTimeout);container.find('*').stop(true,false);container.html(original);if(originalStyle===undefined)container.removeAttr('style');else container.attr('style',originalStyle);container.removeData('imageWall');}});
    return this;
};
(function($){const show=$.fn.showImage;$.fn.showImage=function(options){if(typeof options==='string')return show.call(this,options);const opt=$.extend({},options);if(opt.ole_id!==undefined&&opt.container_id===undefined)opt.container_id=opt.ole_id;return show.call(this,opt);};})(jQuery);
