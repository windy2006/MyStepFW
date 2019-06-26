/**
 * jquery.powerImage.js
 * Mixture image enhance function by windy2000
 */
jQuery.fn.powerImage = function(options) {
    var defaults = {
        selector: 'data-src',
        image: "static/images/loading_img.gif",
        width: 600,
        zoom: true
    };
    var params = $.extend({}, defaults, options || {});
    params.imgs = [];
    $(this).find('img['+params.selector+']').each(function(i) {
        var url = $(this).attr(params.selector);
        this.src = params.image;
        $(this).css({"width":32,"height":32,"margin-bottom":"20px"});
        if(this.title=="" && this.alt!="") this.title = this.alt;
        if(params.zoom) {
            if(this.title!="") this.title += "\n";
            this.title += "Hold the ALT button and wheel the mouse to zoom in or zoom out the image.";
            $(this).mousewheel(function(objEvent, intDelta){
                if(objEvent.altKey) {
                    var zoom = parseInt(this.style.zoom, 10) || 100;
                    zoom += intDelta * 10;
                    if(zoom > 0) {
                        this.style.zoom = zoom + '%';
                    }
                    //objEvent.preventDefault();
                    return false;
                } else {
                    return true;
                }
            });
        }
        var data = {
            obj: $(this),
            url: url
        };
        params.imgs.push(data);
    });
    var showIt = function() {
        if(params.imgs.length==0) return;
        var win_top_1 = $(window).scrollTop(), win_top_2 = win_top_1 + $(window).height();
        var data = params.imgs.shift();
        if(data.obj==null) return;
        var obj = data.obj, url = data.url;
        var img_top_1 = obj.offset().top+100; img_top_2 = img_top_1 + obj.height();
        if((img_top_1 > win_top_1 && img_top_1 < win_top_2) || (img_top_2 > win_top_1 && img_top_2 < win_top_2)) {
            var cur_img = $("<img>");
            cur_img.on('load', function() {
                obj.hide();
                obj.attr("src", url);
                var the_width = obj.attr("width");
                var the_height = obj.attr("height");
                if(typeof(the_width)=="undefined") the_width = "auto";
                if(typeof(the_height)=="undefined") the_height = "auto";
                obj.css({"width":the_width,"height":the_height,"margin-bottom":"10px"});
                obj.fadeIn("slow");
                if(obj.width()>params.width) obj.css({"width":params.width,"height":"auto"});
                $(this).remove();
            });
            cur_img.on('error', function(){
                $(this).remove();
                obj.remove();
            });
            cur_img.attr("src", url);
            data.obj = null;
        } else {
            params.imgs.unshift(data);
        }
        return false;
    };
    setTimeout(showIt, 500);
    $(window).bind("resize", showIt);
    $(window).bind("scroll", showIt);
};