/* Browser-only: actual jQuery, DOM validity, focus, events and real password toggles; fixture data only. */
$(async function() {
    'use strict';
    const rows=[],messages=[],fixture=document.getElementById('fixture'),full=typeof copy==='function';
    const originalAlert=window.alert;window.alert=message=>messages.push(String(message));
    function equal(actual,expected) {if(actual!==expected)throw Error('Expected '+JSON.stringify(expected)+'; got '+JSON.stringify(actual));}
    async function test(name,run) {try{await run();rows.push({name,ok:true});}catch(error){rows.push({name,ok:false,error:error.message});}}
    function form(fields, attrs={}) {
        const f=document.createElement('form');Object.entries(attrs).forEach(([k,v])=>f.setAttribute(k,v));
        for(const field of fields) {
            const el=document.createElement(field.tag || 'input');
            Object.entries(field).forEach(([key,value])=>{if(key==='tag')return;if(key==='value')el.value=value;else if(key==='html')el.innerHTML=value;else if(value!==false)el.setAttribute(key,value===true?'':value);});
            f.appendChild(el);
        }
        fixture.appendChild(f);return f;
    }
    async function validate(name,fields,expected,attrs) {await test(name,()=>equal(checkForm(form(fields,attrs)),expected));}
    await validate('exact length succeeds',[{value:'abcd',len:'4!'}],true);
    await validate('exact length rejects mismatch',[{value:'abc',len:'4!'}],false);
    await validate('legacy Chinese double-byte length',[{value:'中文',len:'4!'}],true);
    await validate('maximum length',[{value:'abcde',len:'4'}],false);
    await validate('bounded length',[{value:'abc',len:'2-4'}],true);
    await validate('invalid length configuration is blocked',[{value:'abc',len:'4-2'}],false);
    await validate('optional empty skips length',[{value:'',need:'email_',len:'3-50'}],true);
    await validate('badReq default redirect is legacy free text',[{name:'setting[forward]',value:'https://www.baidu.com/s',need:'_',len:'100'}],true);
    await validate('legacy free text permits empty',[{value:'',need:'_',len:'100'}],true);
    await validate('legacy free text permits local redirect',[{value:'/local/path?x=1',need:'_',len:'100'}],true);
    await validate('legacy free text permits ordinary text',[{value:'文字 abc',need:'_',len:'100'}],true);
    await validate('legacy free text retains maximum length',[{value:'https://www.baidu.com/s',need:'_',len:'10'}],false);
    await validate('legacy free text retains nonempty minimum length',[{value:'ab',need:'_',len:'3-100'}],false);
    await validate('legacy free text retains native required',[{value:'',need:'_',required:true}],false);
    await validate('explicit underscore regex is not free text',[{value:'text',need:'^_$'}],false);
    await validate('required still wins over optional rule',[{value:'',need:'email_',required:true}],false);
    await validate('disabled input is skipped',[{value:'',need:'',disabled:true}],true);
    await test('disabled fieldset is skipped',()=>{const f=form([]);f.innerHTML='<fieldset disabled><input need="" value=""></fieldset>';equal(checkForm(f),true);});
    await validate('native required without need',[{value:'',required:true}],false);
    await validate('native min/max in programmatic checker',[{type:'number',value:'9',max:'5'}],false);
    await validate('novalidate affects native rules only',[{value:'',required:true}],true,{novalidate:true});
    await validate('need still works with novalidate',[{value:'',need:''}],false,{novalidate:true});
    await validate('missing checkbox',[{type:'checkbox',name:'agree',need:''}],false);
    await validate('checked checkbox',[{type:'checkbox',name:'agree',checked:true,need:''}],true);
    await validate('required checkbox is independent',[{type:'checkbox',name:'agree',required:true},{type:'checkbox',name:'agree',checked:true}],false);
    await validate('radio group accepts a checked peer',[{type:'radio',name:'option',need:''},{type:'radio',name:'option',checked:true}],true);
    await validate('native required radio accepts a checked peer',[{type:'radio',name:'option',required:true},{type:'radio',name:'option',checked:true}],true);
    await validate('multiple select validates every selected value',[{tag:'select',multiple:true,need:'digital',html:'<option selected value="1">one</option><option selected value="bad">bad</option>'}],false);
    await validate('multiple select cannot bypass a nonoptional rule',[{tag:'select',multiple:true,need:'email',html:'<option value="a">a</option>'}],false);
    await validate('custom regex keeps uppercase escapes',[{value:'abc',need:'^\\D+$'}],true);
    await validate('custom regex keeps underscore classes',[{value:'ABC_DEF',need:'^[A-Z_]+$'}],true);
    await validate('custom regex invalid config blocked',[{value:'abc',need:'['}],false);
    await validate('custom literal regex flags',[{value:'abc',need:'/^[A-Z]+$/'}],false);
    await validate('long TLD and plus email',[{value:'user+tag@example.technology',need:'email'}],true);
    await validate('IPv6 URL',[{value:'http://[::1]:8080/a',need:'url'}],true);
    await validate('bad URL port',[{value:'http://example.com:99999',need:'url'}],false);
    await validate('bad calendar date',[{value:'2024-04-31',need:'date'}],false);
    await validate('bad date time component',[{value:'2024-02-29 12:70:00',need:'date'}],false);
    await validate('leap day',[{value:'2024-02-29',need:'date'}],true);
    await validate('time seconds range',[{value:'12:59:60',need:'time'}],false);
    await validate('blank edit password can keep the existing password',[{type:'password',value:'',len:'4-16'}],true);
    await validate('password spaces are not trimmed',[{type:'password',value:' a ',id:'raw_pass',len:'3!'},{type:'password',value:' a ',id:'raw_pass_r',len:'3!'}],true);
    await validate('confirmation mismatch',[{type:'password',value:'a',id:'mismatch'},{type:'password',value:'b',id:'mismatch_r'}],false);
    await validate('orphan confirmation is blocked without throwing',[{type:'password',value:'a',id:'orphan_r'}],false);
    await test('password fields survive successful validation and retries',()=>{const f=form([{type:'password',value:'abc',id:'retry'},{type:'password',value:'abc',id:'retry_r'}]);equal(checkForm(f),true);equal(checkForm(f),true);equal(f.elements.length,2);equal(new FormData(f).get('unused'),null);});
    await test('confirmation is scoped to its own form',()=>{form([{type:'password',id:'same_id',value:'other'}]);const f=form([{type:'password',id:'same_id',value:'correct'},{type:'password',id:'same_id_r',value:'correct'}]);equal(checkForm(f),true);});
    await test('external controls linked with form attribute are checked',()=>{const f=form([],{id:'linked'});const el=document.createElement('input');el.setAttribute('form','linked');el.setAttribute('need','');fixture.appendChild(el);equal(checkForm(f),false);});
    await test('callbacks receive form context and can cancel',()=>{const f=form([]);equal(checkForm(f,function(arg){equal(this,f);equal(arg,f);return false;}),false);});
    await test('async callbacks cannot silently pass synchronous validation',()=>equal(checkForm(form([]),()=>Promise.resolve(false)),false));
    await test('invalid number input can be focused without select() exceptions',()=>{const f=form([{type:'number',value:'1',need:'',len:'2!'}]);equal(checkForm(f),false);equal(f.elements[0].classList.contains('is-invalid'),true);});
    await test('invalid marker clears on input',()=>{const f=form([{value:'',need:''}]);checkForm(f);f.elements[0].dispatchEvent(new Event('input',{bubbles:true}));equal(f.elements[0].classList.contains('is-invalid'),false);});
    if(full) {
        await test('visible password still participates in confirmation',()=>{const f=document.getElementById('visible-password');$(f).find('.password').trigger('click');equal(f.elements[0].type,'text');f.elements[1].value='different';equal(checkForm(f),false);f.elements[1].value='same';equal(checkForm(f),true);});
        await test('literal copy text cannot turn into a selector or HTML',()=>{equal(copyValue('hello'),'hello');equal(copyValue('<b>text</b>'),'<b>text</b>');});
        await test('synchronous copy restores focus and selection',()=>{const f=form([{value:'abcdef'}]);f.elements[0].focus();f.elements[0].setSelectionRange(2,4);const old=document.execCommand;document.execCommand=()=>true;try{equal(copy('word'),true);equal(document.activeElement,f.elements[0]);equal(f.elements[0].selectionStart,2);equal(f.elements[0].selectionEnd,4);}finally{document.execCommand=old;}});
        await test('loading overlay explicit state restores original overflow',()=>{const old=document.body.style.overflow;document.body.style.overflow='scroll';loadingShow('<b>literal</b>',true);loadingShow('updated',true);equal(document.querySelector('#bar_loading span').textContent,'updated');loadingShow(null,false);equal(document.body.style.overflow,'scroll');document.body.style.overflow=old;});
        await test('anchor IDs with selector punctuation do not throw',()=>{const el=document.createElement('a');el.id='quoted"[]';fixture.appendChild(el);const old=$.fx.off;$.fx.off=true;try{gotoAnchor('#quoted%22%5B%5D');equal(global.anchorAnimating,false);}finally{$.fx.off=old;}});
        await test('init does not duplicate handlers or remove unrelated unload listeners',()=>{let calls=0;$(window).on('beforeunload.external',()=>{calls++;});ms_func_run();ms_func_run();global.alert_leave=false;$(window).triggerHandler('beforeunload');equal(calls,1);$(window).off('.external');const handlers=$._data(document,'events').click.filter(x=>x.namespace==='msAnchor');equal(handlers.length,1);});
        await test('cancelled submission retains unsaved marker',async()=>{const f=form([]);global.alert_leave='unsaved';$(f).on('submit.reject',event=>event.preventDefault());f.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));await Promise.resolve();equal(global.alert_leave,'unsaved');});
        await test('later document cancellation restores unsaved marker',async()=>{const f=form([]);global.alert_leave='unsaved';$(document).on('submit.late','form',event=>event.preventDefault());f.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));await Promise.resolve();equal(global.alert_leave,'unsaved');$(document).off('.late');});
        await test('successful submission clears only own unsaved marker',async()=>{global.alert_leave=true;form([]).dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));await Promise.resolve();equal(global.alert_leave,false);});
        await test('query GET preserves target, submitter and special field names',()=>{
            const wrap=document.createElement('div');fixture.appendChild(wrap);
            const f=form([{name:'k',value:'A+B &中文?#'},{name:'__proto__',value:'own'},{name:'constructor',value:'ctor'}],{action:'/index.php?/search/',target:'fixture-target'});wrap.appendChild(f);
            const button=document.createElement('button');button.type='submit';button.name='button';button.value='selected';f.appendChild(button);
            const before=setting.url_prefix,open=window.open;let sent;
            setting.url_prefix='/index.php?';window.open=(url,target)=>{sent={url,target};return null;};
            try{setURL('/index.php?/search/',wrap);f.dispatchEvent(new SubmitEvent('submit',{submitter:button,bubbles:true,cancelable:true}));equal(sent.target,'fixture-target');const url=new URL(sent.url,document.baseURI);equal(url.search.split('&')[0],'?/search/');equal(url.searchParams.get('k'),'A+B &中文?#');equal(url.searchParams.get('__proto__'),'own');equal(url.searchParams.get('constructor'),'ctor');equal(url.searchParams.get('button'),'selected');}
            finally{setting.url_prefix=before;window.open=open;}
        });
        await test('same-origin Ajax preserves jQuery request headers',async()=>{const data=await $.getJSON('/echo');equal(data.requestedWith,'XMLHttpRequest');});
    }
    window.alert=originalAlert;
    if(full) global.alert_leave=false;
    const result={mode:full?'integrated':'standalone',passed:rows.filter(x=>x.ok).length,failed:rows.filter(x=>!x.ok).length,rows};
    document.getElementById('results').textContent=JSON.stringify(result,null,2);
    await fetch('/report',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(result)});
});
