/** 计划编辑：切换格式时只替换未改动的示例值，保留用户自行填写的计划。 */
(function(){
    'use strict';
    function init(){
        var select=document.querySelector('.plugin-crontab select[name="mode"]');if(!select)return;
        var field=select.form.elements.namedItem('schedule');if(!field)return;
        var examples={'0':'0,0,0,10,0','1':'0,0,9,0,0','2':'*/10 * * * *'},previous=select.value;
        function change(){
            if(field.value.trim()===''||field.value===examples[previous])field.value=examples[select.value];
            field.placeholder=examples[select.value];field.title=select.value==='2'?'Cron：分 时 日 月 星期':'五段逗号分隔数字';
            field.required=true;field.setAttribute('need','');previous=select.value;
        }
        select.addEventListener('change',change);field.placeholder=examples[previous];field.required=true;field.setAttribute('need','');
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
}());
