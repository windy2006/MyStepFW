/* 文件：app/myStep/asset/api.js (new)
 * 功能说明：新旧后台共用的API行内设置展开、checkForm校验和AJAX应用。
 * 方法目录：message、apply、submit；页面事件委托支持动态状态更新。
 * 使用说明：与function_api/api_endpoint模板配套；仅向同源后台POST，保留CSRF与配置版本。
 */
(function () {
    'use strict';
    var busy = false;
    /**
     * 功能说明：显示本行操作结果，只写入文本内容。
     * @param {HTMLFormElement} form 当前接口表单。
     * @param {string} text 提示文本。
     * @param {boolean} error 是否为错误状态。
     * @returns {void} 更新本行提示。
     */
    function message(form, text, error) {
        var node = form.querySelector('.ms-api-message');
        if (!node) return;
        node.textContent = text;
        node.classList.toggle('is-error', !!error);
    }
    /**
     * 功能说明：回填当前接口状态并同步全部表单版本，保留其他接口未保存的修改。
     * @param {HTMLFormElement} form 当前接口表单。
     * @param {Object} data 服务端返回的version和entry设置。
     * @returns {void} 更新界面；历史项清理后移除对应两行。
     */
    function apply(form, data) {
        if (typeof data.version !== 'string') throw new Error('服务器未返回配置版本，请刷新后重试。');
        document.querySelectorAll('.ms-api-form input[name="version"]').forEach(function (input) { input.value = data.version; });
        var detail = form.closest('.ms-api-detail');
        if (!detail) return;
        var row = detail.previousElementSibling;
        if (!data.entry) { if (row) row.remove(); detail.remove(); return; }
        var status = row && row.querySelector('[data-api-status]');
        if (status) status.textContent = data.entry.status;
        var policy = data.entry.policy;
        if (policy) {
            ['enabled', 'cors', 'admin'].forEach(function (key) {
                var input = form.querySelector('input[type="checkbox"][name="policy[' + key + ']"]');
                if (input) input.checked = policy[key] === true;
            });
            var methods = form.querySelector('input[name="policy[methods]"]');
            if (methods) methods.value = policy.methods.join(',');
        }
    }
    /**
     * 功能说明：通过checkForm后串行提交AJAX请求，处理成功、冲突和登录失效反馈。
     * @param {HTMLFormElement} form 当前接口表单。
     * @param {string} action save保存或reset恢复默认。
     * @returns {void} 异步更新界面，不触发页面跳转。
     */
    function submit(form, action) {
        if (busy) return;
        if (typeof window.checkForm !== 'function') { message(form, '表单校验脚本未加载，请刷新后重试。', true); return; }
        if (!window.checkForm(form)) return;
        if (action === 'reset' && !window.confirm('恢复当前接口的默认设置？原有业务权限仍然生效。')) return;
        var url = new URL(form.action, document.baseURI);
        if (url.origin !== location.origin) { message(form, '设置只能提交到当前后台。', true); return; }
        if (typeof window.fetch !== 'function') { message(form, '当前浏览器不支持无刷新保存，请升级浏览器。', true); return; }
        var body = new URLSearchParams(new FormData(form));
        body.set('_action', action);
        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
        var token = form.querySelector('input[name="_ms_csrf"]');
        if (token) headers['X-CSRF-Token'] = token.value;
        var buttons = Array.from(document.querySelectorAll('.ms-api-form button')).map(function (node) { return { node: node, disabled: node.disabled }; });
        busy = true;
        buttons.forEach(function (item) { item.node.disabled = true; });
        message(form, '正在应用…', false);
        window.fetch(url.href, { method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: headers, body: body }).then(function (response) {
            if (!(response.headers.get('Content-Type') || '').includes('application/json')) throw new Error('登录状态可能已失效，请刷新页面后重新登录。');
            return response.json().then(function (data) {
                if (!response.ok || !data || data.ok !== true) throw new Error(data && (data.message || data.error) || '设置未保存，请刷新后重试。');
                apply(form, data);
                message(form, data.message || 'API设置已应用。', false);
            });
        }).catch(function (error) {
            message(form, error.message || '请求失败，请检查网络后重试。', true);
        }).then(function () {
            busy = false;
            buttons.forEach(function (item) { item.node.disabled = item.disabled; });
        });
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-api-toggle]');
        if (toggle) {
            event.preventDefault();
            var detail = document.getElementById(toggle.getAttribute('aria-controls'));
            if (detail) { detail.hidden = !detail.hidden; toggle.setAttribute('aria-expanded', detail.hidden ? 'false' : 'true'); }
            return;
        }
        var reset = event.target.closest('[data-api-reset]');
        if (reset) { event.preventDefault(); var form = reset.closest('.ms-api-form'); if (form) submit(form, 'reset'); }
    });
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('.ms-api-form')) return;
        event.preventDefault();
        submit(form, 'save');
    });
}());
