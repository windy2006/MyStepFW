/**
 * jquery.parallax.js - build a parallax page with awesome effect
 *
 * Written by
 * ----------
 * Windy2000 (windy2006@gmail.com)
 *
 * Licensed under the Apache License Version 2.0
 *
 * Dependencies
 * ------------
 * jQuery (http://jquery.com)
 *
 **/
(function($){
    $('body').css('overflow-x', 'hidden');
    $.fn.parallax = function(opt) {
        let effect_list = ['slide-up','slide-down','slide-right','slide-left','flip-x','flip-y','zoom'];
        opt = $.extend(opt || {}, {rate:0.3,mode:0,effect:effect_list[0]});
        this.children().each(function(){
            let obj = $(this);
            obj.addClass('parallax-list');
            if(obj.attr('data-bg')) {
                obj.css('background-image','url('+obj.attr('data-bg')+')').addClass('parallax');
            }
            obj.children().each(function(){
                let item = $(this);
                item.css('opacity', 0)
                    .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd onanimationend animationend', function() {
                        item.css('opacity', 1);
                    });
            });
            let func = show_1;
            if(obj.attr('data-func')) {
                if(typeof(window[obj.attr('data-func')])==='function') {
                    func = window[obj.attr('data-func')]
                } else {
                    try{
                        func = eval(obj.attr('data-func'));
                        if(typeof(func)==='function') {
                            func = eval(obj.attr('data-func'));
                        }
                    } catch (e) {
                        func = show_2;
                    }
                }
            }
            func(obj, obj.attr('data-effect') || opt.effect || effect_list[0]);

            if(opt.mode===1) {
                obj.css('overflow', 'hidden');
                $('body').css('overflow', 'hidden');
                $(window).resize(function(){
                    obj.css({
                        width:$(window).width(),
                        height:$(window).height(),
                    })
                });
                $(window).trigger('resize');

                obj.get(0).onmousewheel = function(e) {
                    if(window.scrolling) return;
                    window.scrolling = true;
                    let item = e.deltaY>0 ? obj.next() : obj.prev();
                    if(item.length>0) {
                        $('html, body').animate({
                            scrollTop:item.position().top
                        }, 1000, function(){
                            window.scrolling = false;
                        });
                    } else {
                        window.scrolling = false;
                    }
                }
            } else {
                if(obj.attr('data-bg')) {
                    $(window).scroll(function(){
                        if($(window).width()<768) {
                            obj.css({
                                'background-position': 'bottom center'
                            });
                        } else {
                            let rate = obj.attr('data-rate') || opt.rate;
                            if(
                                obj.position().top < $(window).scrollTop() + $(window).height()
                                &&
                                obj.position().top + obj.height() > $(window).scrollTop()
                            ) {
                                obj.css('background-position-y', (obj.position().top - $(window).scrollTop()) * rate);
                            }
                        }
                    });
                }
            }
        });
        $(window).trigger('scroll');
        function show_1(obj, effect) {
            if(effect==='rand' || effect==='random') {
                effect = effect_list[Math.floor(Math.random() * effect_list.length)];
            }
            obj.children().each(function(){
                let item = $(this);
                let cur_effect = item.attr('data-effect') || effect;
                if(cur_effect==='rand' || cur_effect==='random') {
                    cur_effect = effect_list[Math.floor(Math.random() * effect_list.length)];
                }
                $(window).scroll(function(){
                    if(item.attr('data-shown')) return;
                    if(
                        item.position().top < $(window).scrollTop() + $(window).height()/1.5
                        &&
                        item.position().top + item.height() > $(window).scrollTop() + $(window).height()/10
                    ) {
                        item.addClass('animation animation_'+cur_effect)
                            .attr('data-shown', 'y')
                            .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd onanimationend animationend', function() {
                                $(this).removeClass('animation animation_'+cur_effect);
                            });
                    }
                });
            });
        }
        function show_2(obj, effect) {
            let items = obj.children();
            let cnt = items.length;
            let idx = 0;
            if(cnt===0) return;
            let show = function(item) {
                item = $(item);
                if($(window).width()>768) {
                    effect = idx%2 ? 'slide-up' : 'slide-down';
                } else {
                    effect = idx%2 ? 'slide-left' : 'slide-right';
                }
                if(idx===0) effect = 'zoom';
                item.addClass('animation animation_'+effect)
                    .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd onanimationend animationend', function() {
                        $(this).removeClass('animation animation_'+effect);
                        if(idx++<cnt) show(items[idx]);
                    });
            }
            $(window).scroll(function(){
                if(
                    obj.attr('data-shown')
                    ||
                    obj.position().top > $(window).scrollTop() + $(window).height()/1.5
                    ||
                    obj.position().top + obj.height() < $(window).scrollTop() + $(window).height()/10
                ) return;
                show(items[idx]);
                obj.attr('data-shown', 'y')
            });
        }
    };
})(jQuery);
