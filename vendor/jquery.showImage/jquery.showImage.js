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