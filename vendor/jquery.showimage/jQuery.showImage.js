/**
 * jquery.showImage.js
 * Image Marquee show by windy2000
 */
jQuery.fn.showImage = function(options) {
    var defaults = {
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
    var params = $.extend({}, defaults, options || {});
    params.obj = this;
    params.thumb_width_offset = params.thumb_width + params.thumb_margin
    var container = $("#"+params.container_id);
    var container_show = null;
    var container_list = null;
    var container_wrapper = null;
    var img_list = $(this).find("img");
    var img_cnt = img_list.length;
    var the_width = 0;
    var repeat_times = 4;
    var act_img = null;
    var marquee_interval = 0;
    var rate = container.width()/params.thumb_width_offset;

    if(img_list.length<2) return;
    container.addClass("showImage");
    container.css("width",params.container_width);
    if(isNaN(params.interval) || params.interval<=0) params.interval = 2;
    
    params.adjust_pos = Math.round(rate/2)-1;
    params.adjust_left = params.thumb_width_offset*(rate - Math.floor(rate))/2;
    if(Math.floor(rate)%2) {
        params.adjust_left -= params.thumb_width_offset;
    } else {
        params.adjust_left -= params.thumb_width_offset/2;
    }

    container.bind("contextmenu",function(){return false;}).bind("selectstart",function(){return false;});
    container.show();
    $("<div/>").addClass("container_show").html('<span class="jump back">&nbsp;</span><div class="main"><a href="###" target="_blank" title="Click to show the image in a new window."><img src="static/images/dummy.png" /></a></div><span class="jump forward">&nbsp;</span>').appendTo(container);
    $("<div/>").addClass("container_list").html('<a class="arrow back">&nbsp;</a><div class="wrapper"></div><a class="arrow forward">&nbsp;</a>').appendTo(container);
    container_show = container.find(".container_show");
    container_show.css("width", '100%');
    container_show.css("height", 'auto');
    container_show.find(".jump").css({"opacity":"0","height":'100%'}).click(function(){
        swich_img($(this).hasClass("back")?-1:1);
        this.blur();
        return false;
    });
    container_show.find("img").css({'height':params.img_height,'width':'auto',"max-width":(params.container_width-20)});
    container_list = container.find(".container_list");
    container_wrapper = container_list.find(".wrapper");
    container_wrapper.css("left",params.adjust_left);
    container_list.find("a").css("z-index",999);
    container_list.find("a").first().click(function(){swich_img(-1)});
    container_list.find("a").last().click(function(){swich_img(1)});
    var cur_img = "";
    var cur_title = "";
    for(var i=0;i<img_cnt;i++) {
        cur_img = img_list.get(i).src;
        cur_title = img_list.get(i).alt;
        if(cur_title.length==0) cur_title = img_list.get(i).title;
        $("<img/>").attr("src", cur_img).attr("title", cur_title).css({"width":params.thumb_width,"height":params.thumb_height}).appendTo(container_wrapper);
    }
    the_width = params.thumb_width_offset * img_cnt;
    repeat_times = Math.ceil(container.width()*2/the_width);
    if(repeat_times<2) repeat_times = 2;
    container_wrapper.html((new Array(repeat_times+1)).join(container_wrapper.html()));
    container_wrapper.width(the_width*repeat_times);
    container_wrapper.find("img").hover(
        function () {
            if($(this).attr("active")!="y") $(this).css("opacity", "0.8");
        },
        function () {
            if($(this).attr("active")!="y") $(this).css("opacity", "0.3");
        }
    ).click(function(){
        if($(this).attr("active")=="y") return;
        clearInterval(marquee_interval);
        var the_step = Math.ceil(($(act_img).position().left-$(this).position().left)/params.thumb_width_offset);
        if(the_step<=0) the_step -= 1;
        swich_img(the_step);
        marquee_interval = setInterval(swich_img, params.interval*1000);
    });

    var swich_img = function(step) {
        if(step==null) step = -1;
        var the_left = container_wrapper.position().left;
        var the_step = the_left + step * params.thumb_width_offset;
        container_wrapper.find("img").css("opacity", "0.3");
        container_wrapper.find("img").attr("active", "n");
        if(the_step>0) {
            container_wrapper.css("left", the_step-the_width-params.thumb_width_offset);
            the_step -= the_width;
        } else if(-the_step>the_width) {
            container_wrapper.css("left", the_width+the_step+params.thumb_width_offset);
            the_step += the_width;
        }
        container_show.find("img").animate({"opacity": 0}, 1000);
        container_wrapper.animate({"left":the_step},1000,function(){
            var the_idx = Math.ceil(-the_step/params.thumb_width_offset);
            act_img = container_wrapper.find("img").get(the_idx+params.adjust_pos);
            $(act_img).css("opacity", "1");
            $(act_img).attr("active", "y");
            container_show.find("a").attr("href", act_img.src).attr("title", act_img.title);
            container_show.find("img").attr("src", act_img.src);
            container_show.find("img").animate({"opacity": 1}, 500);
            return;
        });
        return;
    }

    var reset = function() {
        clearInterval(marquee_interval);
        container.find('div,span,a,img').unbind();
        container.empty();
        $(params.obj).showImage(params);
        return;
    };

    swich_img(1 - img_cnt + params.adjust_pos);
    marquee_interval = setInterval(swich_img, params.interval*1000);

    $(window).resize(function(){
        if(!isNaN(params.container_width)) return;
        global.resize_monitor = true;
        setTimeout(function(){
            global.resize_monitor = false;
        },400);
        if(typeof(global.resize_timer)=='undefined' || global.resize_timer==0) {
            global.resize_timer = setInterval(function(){
                if(global.resize_monitor) return;
                reset();
                clearInterval(global.resize_timer);
                global.resize_timer = 0;
            }, 900);
        }
    });
    return;
};

/**
 * jquery.imageWall.js
 * Image Marquee show by windy2000
 */
jQuery.fn.imageWall = function(options) {
    var defaults = {
        size_h: 3,
        size_v: 2,
        img_width: 150,
        img_height: 60,
        interval: 5,
        speed: 500,
        tag: "img"
    };
    var params = $.extend({}, defaults, options || {});
    var container = $(this);
    var img_cnt = params.size_h*params.size_v;
    var img_content = container.html().replace(/\s*[\r\n]+\s*/g, "");
    var repeat_times = Math.ceil(img_cnt/container.find(params.tag).length)+1;
    var show_done = true;
    var rebuild_list = function(thecontainer) {
        for(var i=0;i<img_cnt;i++) {
            thecontainer.find(params.tag).first().appendTo(thecontainer);
        }
    };
    var setBack = function() {
        container.find("div").last().prependTo(container);
    }
    params.interval *= 1000;
    if(params.interval<params.speed*(img_cnt+1)) params.interval = params.speed*(img_cnt+2);
    img_content = (new Array(repeat_times+1)).join(img_content);
    container.css({"width":(params.size_h*params.img_width),"height":(params.size_v*params.img_height),"overflow":"hidden","position":"relative"});
    container.html('<div></div><div></div>');
    container.find("div").css({"position":"absolute","top":0,"left":0,"z-index":0}).html(img_content);
    container.find(params.tag).css({"display":"block","float":"left","width":params.img_width,"height":params.img_height,"overflow":"hidden"});
    container.find("img").css({"display":"block","float":"left","width":params.img_width,"height":params.thumb_height,"border":0});
    rebuild_list(container.find("div:eq(0)"));
    setInterval(function(){
        if(show_done == false) return;
        var cur_obj = container.find("div").last();
        var cur_idx = 0;
        show_done = false;
        for(var i=0;i<img_cnt;i++) {
            setTimeout(function(){cur_obj.find(params.tag+":eq("+cur_idx+")").animate({"opacity":"0"},params.speed*3);cur_idx++;}, params.speed*i);
        }
        setTimeout(function(){
            setBack();
            rebuild_list(container.find("div").first());
            container.find("div").first().find(params.tag).css("opacity", "1");
            show_done = true;
        }, params.speed*(i+2));
    }, params.interval);
    return;
};