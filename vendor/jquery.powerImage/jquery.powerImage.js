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
