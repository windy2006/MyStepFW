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

/*!
 * jquery.powerImage.js
 * Mixture image enhance function by windy2000
*/
jQuery.fn.powerImage = function(options) {
	var defaults = {
		image: "/images/loading_img.gif",
		width: 600,
		zoom: true
	};
	var params = $.extend({}, defaults, options || {});
	params.imgs = [];
	$(this).find("img").each(function(i) {
		if($(this).hasClass("title_img")) return;
		var url = $(this).attr("src");
		if(!url) return;
		this.src = params.image;
		$(this).css({"width":32,"height":32,"margin-bottom":"20px"});
		if(this.title=="" && this.alt!="") this.title = this.alt;
		if(params.zoom) {
			if(this.title!="") this.title += "\n";
			this.title += "Press ALT button, wheel the mouse to zoom in or zoom out the image.";
			$(this).mousewheel(function(objEvent, intDelta){
				if(objEvent.altKey) {
					var zoom = parseInt(this.style.zoom, 10) || 100;
					zoom += intDelta * 10;
					if(zoom > 0) {
						this.style.zoom = zoom + '%';
					}
					if(objEvent.preventDefault){
						objEvent.preventDefault();
					} else {
						objEvent.returnValue = false;
					}
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
		var win_top_1 = $(window).scrollTop(), win_top_2 = win_top_1 + $(window).height();
		$.each(params.imgs, function(i, data) {
			if(data.obj==null) return;
			var obj = data.obj, url = data.url;
			var img_top_1 = obj.offset().top+100; img_top_2 = img_top_1 + obj.height();
			if((img_top_1 > win_top_1 && img_top_1 < win_top_2) || (img_top_2 > win_top_1 && img_top_2 < win_top_2)) {
				var cur_img = $("<img>");
				cur_img.load(function() {
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
				cur_img.error(function(){
					$(this).remove();
					obj.remove();
				});
				cur_img.attr("src", url);
				data.obj = null;
			}
		});
		return false;
	};
	setTimeout(showIt, 1000);
	$(window).bind("resize", showIt);
	$(window).bind("scroll", showIt);
};

/*!
 * jquery.showImage.js
 * Image Marquee show by windy2000
*/
jQuery.fn.showImage = function(options) {
	var defaults = {
		ole_width: 640,
		img_show_height: 420,
		img_width: 120,
		img_height: 80,
		ole_id: "showImage",
		pos_adjust: 1,
		step_adjust: -10,
		interval: 5,
		remove_org: false
	};
	var params = $.extend({}, defaults, options || {});
	var obj_ole = $("#"+params.ole_id);
	var obj_ole_show = null;
	var obj_ole_list = null;
	var obj_ole_wrapper = null;
	var img_list = $(this).find("img");
	var the_width = 0;
	var repeat_times = 4;
	var act_img = null;
	if(img_list.length<2) return;
	obj_ole.addClass("showImage");
	obj_ole.css("width",params.ole_width);
	var swich_img = function(step) {
		if(step==null) step = -1;
		var the_left = obj_ole_wrapper.position().left;
		var the_step = the_left + step * (params.img_width+12);
		obj_ole_wrapper.find("img").css("opacity", "0.3");
		obj_ole_wrapper.find("img").attr("active", "n");
		if(the_step>0) {
			obj_ole_wrapper.css("left", the_step-the_width-(params.img_width+12));
			the_step -= the_width;
		} else if(-the_step>the_width) {
			obj_ole_wrapper.css("left", the_width+the_step+(params.img_width+12));
			the_step += the_width;
		}
		obj_ole_show.find("img").animate({"opacity": 0}, 1000);
		obj_ole_wrapper.animate({"left":the_step},1000,function(){
			var the_idx = Math.ceil(-the_step/(params.img_width+12));
			act_img = obj_ole_wrapper.find("img").get(the_idx+params.pos_adjust);
			$(act_img).css("opacity", "1");
			$(act_img).attr("active", "y");
			obj_ole_show.find("a").attr("href", act_img.src).attr("title", act_img.title);
			obj_ole_show.find("img").attr("src", act_img.src);
			obj_ole_show.find("img").animate({"opacity": 1}, 500);
			return;
		});
		return;
	}
	obj_ole.bind("contextmenu",function(){return false;}).bind("selectstart",function(){return false;});  
	obj_ole.show();
	$("<div/>").addClass("ole_show").html('<span class="jump back">&nbsp;</span><div class="main"><a href="###" target="_blank" title="Click to show the image in a new window."><img src="/images/dummy.png" /></a></div><span class="jump forward">&nbsp;</span>').appendTo(obj_ole);
	$("<div/>").addClass("ole_list").html('<a class="arrow back">&nbsp;</a><div class="wrapper"></div><a class="arrow forward">&nbsp;</a>').appendTo(obj_ole);
	obj_ole_show = obj_ole.find(".ole_show");
	obj_ole_show.css("height", params.img_show_height+20);
	obj_ole_show.find(".jump").css({"opacity":"0","height":params.img_show_height+20}).click(function(){
		swich_img($(this).hasClass("back")?-1:1);
		this.blur();
		return false;
	});
	obj_ole_show.find("img").css({"height":params.img_show_height,"max-width":(params.ole_width-20)});
	obj_ole_list = obj_ole.find(".ole_list");
	obj_ole_wrapper = obj_ole_list.find(".wrapper");
	obj_ole_wrapper.css("left",params.step_adjust);
	obj_ole_list.find("a").css("z-index",999);
	obj_ole_list.find("a").first().click(function(){swich_img(-1)});
	obj_ole_list.find("a").last().click(function(){swich_img(1)});
	var cur_img = "";
	var cur_title = "";
	for(var i=0,m=img_list.length;i<m;i++) {
			cur_img = img_list.get(i).src;
			cur_title = img_list.get(i).alt;
			if(cur_title.length==0) cur_title = img_list.get(i).title;
			$("<img/>").attr("src", cur_img).attr("title", cur_title).css({"width":params.img_width,"height":params.img_height}).appendTo(obj_ole_wrapper);
			if(params.remove_org) $(img_list.get(i)).remove();
	}
	the_width = (params.img_width+12) * m;
	repeat_times = Math.ceil(obj_ole_wrapper.width()*2/the_width);
	if(repeat_times<2) repeat_times = 2;
	obj_ole_wrapper.html((new Array(repeat_times+1)).join(obj_ole_wrapper.html()));
	obj_ole_wrapper.width(the_width*repeat_times);
	obj_ole_wrapper.find("img").hover(
		function () {
			if($(this).attr("active")!="y") $(this).css("opacity", "0.8");
		},
		function () {
			if($(this).attr("active")!="y") $(this).css("opacity", "0.3");
		}
	).click(function(){
		if($(this).attr("active")=="y") return;
		var the_step = Math.ceil(($(act_img).position().left-$(this).position().left)/(params.img_width+12));
		swich_img(the_step);
	});
	swich_img(1 - m + params.pos_adjust);
	if(params.interval>0) {
		var marquee_interval = setInterval(swich_img, params.interval*1000);
		obj_ole.hover(
			function () {
				clearInterval(marquee_interval);
			},
			function () {
				marquee_interval = setInterval(swich_img, params.interval*1000);
			}
		);
	}
	return;
};

/*!
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
	var obj_ole = $(this);
	var img_cnt = params.size_h*params.size_v;
	var img_content = obj_ole.html().replace(/\s*[\r\n]+\s*/g, "");
	var repeat_times = Math.ceil(img_cnt/obj_ole.find(params.tag).length)+1;
	var show_done = true;
	var rebuild_list = function(theOle) {
		for(var i=0;i<img_cnt;i++) {
			theOle.find(params.tag).first().appendTo(theOle);
		}
	};
	var setBack = function() {
		obj_ole.find("div").last().prependTo(obj_ole);
	}
	params.interval *= 1000;
	if(params.interval<params.speed*(img_cnt+1)) params.interval = params.speed*(img_cnt+2);
	img_content = (new Array(repeat_times+1)).join(img_content);
	obj_ole.css({"width":(params.size_h*params.img_width),"height":(params.size_v*params.img_height),"overflow":"hidden","position":"relative"});
	obj_ole.html('<div></div><div></div>');
	obj_ole.find("div").css({"position":"absolute","top":0,"left":0,"z-index":0}).html(img_content);
	obj_ole.find(params.tag).css({"display":"block","float":"left","width":params.img_width,"height":params.img_height,"overflow":"hidden"});
	obj_ole.find("img").css({"display":"block","float":"left","width":params.img_width,"height":params.img_height,"border":0});
	rebuild_list(obj_ole.find("div:eq(0)"));
	setInterval(function(){
		if(show_done == false) return;
		var cur_obj = obj_ole.find("div").last();
		var cur_idx = 0;
		show_done = false;
		for(var i=0;i<img_cnt;i++) {
			setTimeout(function(){cur_obj.find(params.tag+":eq("+cur_idx+")").animate({"opacity":"0"},params.speed*3);cur_idx++;}, params.speed*i);
		}
		setTimeout(function(){
			setBack();
			rebuild_list(obj_ole.find("div").first());
			obj_ole.find("div").first().find(params.tag).css("opacity", "1");
			show_done = true;
		}, params.speed*(i+2));
	}, params.interval);
	return;
};