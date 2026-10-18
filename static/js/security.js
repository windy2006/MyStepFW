/* Session CSRF integration for forms, legacy links, jQuery, XHR and fetch.
 * 受控退出表单：POST + data-ms-logout + data-ms-csrf（同源目标会话令牌接口）。
 * 退出前用same-origin/no-store读取当前令牌；刷新失败不提交，防止旧页面继续使用失效令牌。
 */
(function () {
    'use strict';
    var config = window.msSecurity;
    if (!config) return;
    function allowed(value) {
        try { var url = new URL(value || location.href, document.baseURI); return /^https?:$/.test(url.protocol) && config.origins.indexOf(url.origin) >= 0; }
        catch (error) { return false; }
    }
    function mutates(value) {
        var url = new URL(value, document.baseURI), path = url.pathname;
        if (/\/index\.php$/i.test(path)) path += '/' + url.search.slice(1).split('&')[0];
        if (url.searchParams.has('out') || /\/logout\/?$/i.test(path)) return true;
        var pluginPath = path, base = config.root || '/';
        if (pluginPath.indexOf(base) === 0) pluginPath = pluginPath.slice(base.length);
        pluginPath = pluginPath.replace(/^\/?index\.php\/+/i, '').replace(/^\/+/, '');
        if (/^(?:manager\/(?:build|check|empty|upload|download|plugin|app|pack)|pack)(?:\/|$)/i.test(pluginPath)) return true;
        if (/^badReq(?:\/|$)/i.test(pluginPath) && (/^(ban|unban|del|reset)$/i.test(url.searchParams.get('m') || '') || /^badReq\/(ban|unban|del|reset)(\/|$)/i.test(pluginPath))) return true;
        var administrative = config.adminContext || config.admin.some(function (prefix) { return ('/' + path.replace(/^\//, '') + '/').indexOf('/' + prefix.replace(/^\/|\/$/g, '') + '/') >= 0; });
        return administrative && (/\/(delete|remove|active|uninstall|unlock|rebuild|order|set|pack|add_ok|edit_ok)(\/|$)/i.test(path) || (/\/cache\/?$/.test(path) && url.searchParams.has('id')) || (/\/attachment\/?$/.test(path) && url.searchParams.get('m') === 'del'));
    }
    function field(form) {
        if (form.method.toUpperCase() !== 'POST' || !allowed(form.action)) return;
        var input = form.querySelector('input[name="_ms_csrf"]');
        if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = '_ms_csrf'; form.appendChild(input); }
        input.value = config.token;
    }
    document.addEventListener('submit', function (event) {
        var form = event.target;
        field(form);
        // 原后台退出使用显式POST。只为已声明的退出表单刷新目标会话令牌，
        // 避免长时间打开或另一标签页更新会话后继续提交页面中的旧令牌。
        if (form.method.toUpperCase() !== 'POST' || !form.hasAttribute('data-ms-logout') || event.defaultPrevented || typeof window.fetch !== 'function') return;
        event.preventDefault();
        if (form._msLogoutPending) return;
        var endpoint = form.getAttribute('data-ms-csrf');
        if (!endpoint || !allowed(endpoint) || new URL(endpoint, document.baseURI).origin !== new URL(location.href).origin) return;
        form._msLogoutPending = true;
        window.fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' }).then(function (response) {
            if (!response.ok) throw new Error('Unable to refresh session');
            return response.json();
        }).then(function (data) {
            if (!data || typeof data.token !== 'string' || !/^[a-f0-9]{64}$/.test(data.token)) throw new Error('Invalid session response');
            config.token = data.token;
            field(form);
            // 原生提交不再触发submit监听，避免再次刷新和递归提交。
            form.submit();
        }).catch(function () {
            form._msLogoutPending = false;
            window.alert('登录状态已变化，请刷新页面后重试退出。');
        });
    }, true);
    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.forms, field);
        if (window.jQuery) window.jQuery.ajaxPrefilter(function (options) {
            if (!allowed(options.url)) return;
            if (options.type.toUpperCase() === 'GET' && mutates(options.url)) options.type = 'POST';
            if (!/^(GET|HEAD|OPTIONS)$/i.test(options.type)) { options.headers = options.headers || {}; options.headers['X-CSRF-Token'] = config.token; }
        });
    });
    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var link = event.target.closest('a[href]');
        if (!link || !allowed(link.href) || !mutates(link.href)) return;
        event.preventDefault();
        var form = document.createElement('form'); form.method = 'POST'; form.action = link.href;
        if (link.target) form.target = link.target;
        document.body.appendChild(form); field(form); form.submit();
    });
    var open = XMLHttpRequest.prototype.open, send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) { this._msUnsafe = !/^(GET|HEAD|OPTIONS)$/i.test(method) && allowed(url); return open.apply(this, arguments); };
    XMLHttpRequest.prototype.send = function () { if (this._msUnsafe) this.setRequestHeader('X-CSRF-Token', config.token); return send.apply(this, arguments); };
    if (window.fetch) {
        var fetch = window.fetch;
        window.fetch = function (input, options) {
            var url = input instanceof Request ? input.url : input;
            var method = (options && options.method) || (input instanceof Request ? input.method : 'GET');
            if (allowed(url) && !/^(GET|HEAD|OPTIONS)$/i.test(method)) {
                options = Object.assign({}, options); options.headers = new Headers(options.headers || (input instanceof Request ? input.headers : undefined)); options.headers.set('X-CSRF-Token', config.token);
            }
            return fetch.call(this, input, options);
        };
    }
}());
