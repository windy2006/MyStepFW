/**************************************************
* Functions For Form Check                        *
* Author  : Windy_sk                              *
* Create  : 2003-05-03                            *
* Modified: 2004-1-9                              *
* Email   : windy2006@gmail.com                   *
* HomePage: www.mysteps.cn                        *
* Notice  : U Can Use & Modify it freely,         *
*           BUT PLEASE HOLD THIS ITEM.            *
*                                                 *
**************************************************/
/**
 * 功能说明：表单校验及无效控件定位，支持独立加载，不依赖 global.js 的原型扩展。
 * 方法目录：highlightIt、formCheckMessage、formCheckValue、checkForm。
 * 使用说明：onsubmit="return checkForm(this, optionalChecker)"。
 * need="" 必填；email/url/digital/number/alpha/word/name/date/time/tel/fax 为内置规则；
 * need="_" 表示允许空值的自由文本；内置规则前/后带 _ 表示允许空值。
 * 自由文本仍遵循 len 和原生 required；其他 need 值按不区分大小写的正则处理，亦支持 /pattern/flags。
 * len="N" 最大传统双字节长度，N! 为定长，min-max 为范围；不是 UTF-8 字节数。
 * 确认密码使用同表单内 原密码id_r，显示密码后仍校验；不删除字段、不改变输入值。
 * 客户端校验只用于交互反馈，服务端须独立验证数据。
 */

/**
 * 标记并定位无效控件，兼容不支持 select 的输入类型和被隐藏的编辑器控件。
 * @param {Element|jQuery|null} element 待定位元素；不存在时不执行。
 * @returns {void}
 */
function highlightIt(element) {
    if(element && element.jquery) element=element.get(0);
    if(!element || !element.tagName) return;
    $(element).addClass('is-invalid').off('.msInvalid').one('input.msInvalid change.msInvalid',function() { $(this).removeClass('is-invalid'); });
    try {
        if(typeof element.focus === 'function') element.focus({preventScroll:true});
        if(typeof element.select === 'function' && /^(?:text|search|url|tel|email|password)$/.test(element.type || '')) element.select();
    } catch(error) { /* 某些输入类型或隐藏控件不能选中文本。 */ }
    const offset=$(element).offset();
    if(offset && $(element).is(':visible')) $(window).scrollTop(Math.max(0,offset.top-100));
}

/**
 * 读取语言包并替换 %1 等参数；语言包未加载时使用内置中文提示。
 * @param {string} key 语言条目标识。
 * @param {Array} [params=[]] 字符串替换参数。
 * @returns {string} 可直接显示的纯文本提示。
 */
function formCheckMessage(key, params=[]) {
    const defaults={checkform_lenth_limit1:'请将字符数限制在 %1 个字符以内！',checkform_lenth_limit1_2:'字符数应为 %1 个！',checkform_lenth_limit2:'所填内容必须为 %1 个字符！',checkform_noempty:'当前项目不能为空！',checkform_err:'当前项目格式有误！',checkform_err_password:'两次输入密码请保持一致！',checkform_err_email:'请输入合法的电子邮件地址！',checkform_err_url:'请输入完整的 http、https 或 ftp 网址！',checkform_err_digital:'当前项目只允许数字！',checkform_err_number:'请填写合法的实数！',checkform_err_alpha:'只允许字母、数字和下划线！',checkform_err_word:'当前项目格式有误！',checkform_err_name:'请输入合法的名称！',checkform_err_date:'请输入合法的日期！',checkform_err_date2:'所填日期或时间不合法！',checkform_err_time:'请输入合法的时间！',checkform_err_time2:'所填时间不合法！',checkform_err_tel:'请输入合法的电话号码！'};
    const message=typeof language !== 'undefined' && language && typeof language[key] === 'string' ? language[key] : (defaults[key] || defaults.checkform_err);
    return message.replace(/%([1-9]\d*)/g,function(token,index) { return Number(index)<=params.length ? String(params[Number(index)-1]) : token; });
}

/**
 * 校验单个已归一化的值，不操作 DOM，不修改原值。
 * @param {string} value 文字值；密码由调用方保留原始空白。
 * @param {string} rule 内置规则名、自由文本标记 _，或原样保留的自定义正则。
 * @returns {string|null} 失败时返回语言条目标识，成功返回 null。
 */
function formCheckValue(value, rule) {
    const name=rule.toLowerCase();
    switch(name) {
        case '': return value.length ? null : 'checkform_noempty';
        case '_': return null;
        case 'email': {
            const parts=value.split('@');
            if(parts.length!==2 || parts[0].length>64 || value.length>254 || !/^[a-z\d.!#$%&'*+/=?^_`{|}~-]+$/i.test(parts[0]) || /^\.|\.$|\.\./.test(parts[0]) || /[\s:/\\?#]/.test(parts[1])) return 'checkform_err_email';
            let domain;
            try { domain=new URL('http://'+parts[1]).hostname; } catch(error) { return 'checkform_err_email'; }
            const labels=domain.split('.');
            if(labels.length<2 || !/[a-z]/i.test(labels[labels.length-1]) || domain.length>253 || labels.some(function(label) { return !/^[a-z\d](?:[a-z\d-]{0,61}[a-z\d])?$/i.test(label); })) return 'checkform_err_email';
            return null;
        }
        case 'url': {
            if(!/^(?:https?|ftp):\/\//i.test(value) || /[\s\x00-\x1f\x7f\\]/.test(value)) return 'checkform_err_url';
            try { const url=new URL(value); return url.hostname && /^(?:https?|ftp):$/.test(url.protocol) ? null : 'checkform_err_url'; } catch(error) { return 'checkform_err_url'; }
        }
        case 'digital': return /^\d+$/.test(value) ? null : 'checkform_err_digital';
        case 'number': return /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i.test(value) && Number.isFinite(Number(value)) ? null : 'checkform_err_number';
        case 'alpha': return /^[a-z_\d]+$/i.test(value) ? null : 'checkform_err_alpha';
        case 'word': return /^[\w\s\-().,]+$/.test(value) ? null : 'checkform_err_word';
        case 'name': return /^[\w\-\u3400-\u9fff \uf900-\ufaff()]+$/.test(value) ? null : 'checkform_err_name';
        case 'date': {
            const match=value.match(/^(\d{4})([\/-])(\d{1,2})\2(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);
            if(!match) return 'checkform_err_date';
            const year=Number(match[1]), month=Number(match[3]), day=Number(match[4]), date=new Date(0);
            date.setUTCFullYear(year,month-1,day);
            if(year<1 || date.getUTCFullYear()!==year || date.getUTCMonth()!==month-1 || date.getUTCDate()!==day || Number(match[5] || 0)>23 || Number(match[6] || 0)>59 || Number(match[7] || 0)>59) return 'checkform_err_date2';
            return null;
        }
        case 'time': {
            const match=value.match(/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/);
            if(!match) return 'checkform_err_time';
            return Number(match[1])<24 && Number(match[2])<60 && Number(match[3] || 0)<60 ? null : 'checkform_err_time2';
        }
        case 'tel': case 'fax': {
            const digits=value.replace(/\D/g,'');
            return /^\+?[\d() .-]+$/.test(value) && digits.length>=6 && digits.length<=20 && (value.match(/\(/g)||[]).length===(value.match(/\)/g)||[]).length ? null : 'checkform_err_tel';
        }
        default:
            try {
                const literal=rule.match(/^\/([\s\S]*)\/([a-z]*)$/);
                const expression=literal ? new RegExp(literal[1],literal[2]) : new RegExp(rule,'i');
                return expression.test(value) ? null : 'checkform_err';
            } catch(error) { return 'checkform_err'; }
    }
}

/**
 * 校验表单：自定义前置校验、need/len、原生有效性和密码确认，均通过才允许提交。
 * @param {HTMLFormElement|jQuery} form 目标表单，包含通过 form 属性关联的外部控件。
 * @param {Function} [myChecker] 同步前置校验，接收表单且 this 为表单；返回 false 或 Promise 阻止提交（不自动等待异步校验）。
 * @returns {boolean} 是否通过。禁用控件/非输入按钮不校验；不移除密码确认字段。
 */
function checkForm(form, myChecker) {
    if(form && form.jquery) form=form.get(0);
    if(!form || !form.elements) return false;
    if(typeof myChecker === 'function') {
        const result=myChecker.call(form,form);
        if(result === false || (result && typeof result.then === 'function')) return false;
    }
    const controls=Array.from(form.elements).filter(function(element) { return /^(INPUT|SELECT|TEXTAREA)$/.test(element.tagName) && !$(element).is(':disabled') && !/^(?:submit|button|reset|image)$/i.test(element.type || ''); });
    const groups=new Map(), ids=new Map();
    controls.forEach(function(element) {
        if(element.id && !ids.has(element.id)) ids.set(element.id,element);
        if(/^(?:checkbox|radio)$/.test(element.type) && element.name) {
            const key=element.type+'\0'+element.name;
            groups.set(key,!!element.checked || groups.get(key)===true);
        }
    });
    const fail=function(element,key,params) { alert(formCheckMessage(key,params)); highlightIt(element); return false; };
    const namedRules=['email','url','digital','number','alpha','word','name','date','time','tel','fax'];
    for(const element of controls) {
        const password=element.type==='password' || element.getAttribute('data-ms-password')==='true';
        const raw=String(element.value == null ? '' : element.value);
        const value=password ? raw : raw.trim();
        let rule=element.getAttribute('need'), optional=false;
        if(rule === '_') {
            // myConfig 的历史自由文本标记；不可按匹配下划线的正则处理。
            optional=true;
        } else if(rule !== null) {
            const normalized=rule.replace(/^_|_$/g,'').toLowerCase();
            optional=normalized!==rule.toLowerCase() && namedRules.indexOf(normalized)>=0;
            if(namedRules.indexOf(rule.toLowerCase())>=0 || optional) rule=normalized;
        }
        const choice=/^(?:checkbox|radio)$/.test(element.type);
        if(choice && !element.checked) {
            const checked=element.name ? groups.get(element.type+'\0'+element.name) : element.checked;
            if(rule==='' && !checked) return fail(element,'checkform_noempty');
            if(!form.noValidate && element.willValidate && !element.checkValidity()) return fail(element,'checkform_noempty');
            continue;
        }
        const values=element.multiple && element.tagName==='SELECT' ? Array.from(element.selectedOptions).map(function(option) { return option.value.trim(); }) : [value];
        if(!values.length) values.push('');
        const empty=!values.length || values.every(function(item) { return item===''; });
        if(empty && optional && !element.required) continue;
        const lengthRule=element.getAttribute('len');
        if(lengthRule !== null && !(password && empty && !element.required && rule===null)) {
            const exact=lengthRule.match(/^(\d+)(!)?$/), range=lengthRule.match(/^(\d+)-(\d+)$/);
            if(!exact && (!range || Number(range[1])>Number(range[2]))) return fail(element,'checkform_err');
            for(const item of values) {
                const length=item.length+(item.match(/[^\x00-\xff]/g)||[]).length;
                if(exact && (exact[2] ? length!==Number(exact[1]) : length>Number(exact[1]))) return fail(element,exact[2]?'checkform_lenth_limit1_2':'checkform_lenth_limit1',[exact[1]]);
                if(range && (length<Number(range[1]) || length>Number(range[2]))) return fail(element,'checkform_lenth_limit2',[range[1]===range[2]?range[1]:range[1]+' - '+range[2]]);
            }
        }
        if(rule !== null) {
            if(empty && rule==='') return fail(element,'checkform_noempty');
            for(const item of values) { const error=formCheckValue(item,rule); if(error) return fail(element,error); }
        }
        if(!form.noValidate && element.willValidate && !element.checkValidity()) {
            if(element.validity.valueMissing) return fail(element,'checkform_noempty');
            alert(element.validationMessage || formCheckMessage('checkform_err')); highlightIt(element); return false;
        }
    }
    for(const confirmation of controls) {
        if(!/_r$/.test(confirmation.id) || !(confirmation.type==='password' || confirmation.getAttribute('data-ms-password')==='true')) continue;
        const original=ids.get(confirmation.id.slice(0,-2));
        if(!original || original.value!==confirmation.value) return fail(confirmation,'checkform_err_password');
    }
    return true;
}
