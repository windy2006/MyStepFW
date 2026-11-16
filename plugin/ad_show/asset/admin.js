/** 广告编辑：按素材类型显示相关字段，复用框架checkForm，不改动字段值。 */
(function () {
    'use strict';
    function init() {
        var select=document.querySelector('.plugin-ad_show select[name="ad_mode"]');
        if(!select)return;
        var form=select.form;
        var modes={ad_mobile:[1,6],ad_poster:[5],ad_desc:[6],ad_cta:[5,6],video_autoplay:[5],video_loop:[5],ad_file:[1,3,4,5,6]};
        function update(){
            var mode=Number(select.value);
            Object.keys(modes).forEach(function(name){
                var field=form.elements.namedItem(name);if(!field)return;
                var visible=modes[name].indexOf(mode)>=0,group=field.closest('.form-group');
                if(group)group.hidden=!visible;
                field.required=name==='ad_file'&&visible;
                if(field.required)field.setAttribute('need','');else field.removeAttribute('need');
            });
            var title=form.elements.namedItem('ad_text');if(title){title.required=mode===0||mode===6;if(title.required)title.setAttribute('need','');else title.removeAttribute('need');}
        }
        select.addEventListener('change',update);update();
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
}());
