/* 功能说明：复用框架 checkForm 统一表单校验，并处理操作确认、验证码刷新、行编辑、列表过滤与窄屏导航。 */
(function () {
    'use strict';

    /** 增删行后重排字段索引，避免同名字段被PHP覆盖。 */
    function reindex(body) {
        Array.from(body.rows).forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
            });
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('.adm-shell form')) return;
        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
            return;
        }
        if (typeof window.checkForm !== 'function') {
            event.preventDefault();
            window.alert('表单校验脚本未加载，请刷新页面后重试。');
            return;
        }
        // need、len、密码确认及原生约束统一由框架 checkForm 处理。
        if (!window.checkForm(form)) {
            event.preventDefault();
            return;
        }
        form.querySelectorAll('button[type=submit]').forEach(function (button) {
            button.disabled = true;
        });
    });

    /** 下拉导航使用同一套菜单DOM，桌面保留侧栏，窄屏由按钮控制。 */
    function setNavigation(open, restoreFocus) {
        var panel = document.getElementById('adm-navigation');
        var toggle = document.querySelector('.adm-nav-toggle');
        if (!panel || !toggle) return;
        panel.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? '收起导航菜单' : '展开导航菜单');
        if (restoreFocus) toggle.focus();
    }

    document.addEventListener('keydown', function (event) {
        var panel = document.getElementById('adm-navigation');
        if (event.key === 'Escape' && panel && panel.classList.contains('is-open')) setNavigation(false, true);
    });

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.adm-nav-toggle');
        if (toggle) {
            setNavigation(toggle.getAttribute('aria-expanded') !== 'true', false);
            return;
        }
        var navigation = document.getElementById('adm-navigation');
        if (navigation && navigation.classList.contains('is-open') && (!navigation.contains(event.target) || event.target.closest('a[href]'))) setNavigation(false, false);
        var image = event.target.closest('[data-captcha]');
        if (image) {
            var url = new URL(image.src, location.href);
            // 不用URLSearchParams重新编码query_string模式的首个路由段。
            var parts = url.search.slice(1).split('&').filter(function (part) {
                return part && part.split('=')[0] !== '_';
            });
            parts.push('_=' + Date.now());
            url.search = '?' + parts.join('&');
            image.src = url.href;
            return;
        }
        var add = event.target.closest('[data-add-row]');
        if (add) {
            var table = document.getElementById(add.dataset.addRow);
            var body = table && table.tBodies[0];
            if (!body) return;
            // 空表也可从隐藏模板新增；其他编辑器继续使用已有行。
            var template = add.dataset.rowTemplate && document.getElementById(add.dataset.rowTemplate);
            var source = template && template.content ? template.content.firstElementChild : body.rows[body.rows.length - 1];
            if (!source) return;
            var row = source.cloneNode(true);
            row.querySelectorAll('input,textarea,select').forEach(function (input) {
                input.value = input.dataset && input.dataset.defaultValue || '';
            });
            row.querySelectorAll('[data-remove-row]').forEach(function (button) { button.disabled = false; });
            row.querySelectorAll('[data-row-existing]').forEach(function (link) { link.remove(); });
            body.appendChild(row);
            reindex(body);
        }
        var remove = event.target.closest('[data-remove-row]');
        if (remove) {
            var row = remove.closest('tr');
            if (row && (row.parentNode.rows.length > 1 || row.parentNode.dataset && row.parentNode.dataset.allowEmpty === 'true')) {
                var body = row.parentNode;
                row.remove();
                reindex(body);
            }
        }
    });

    document.addEventListener('input', function (event) {
        if (!event.target.matches('[data-table-filter]')) return;
        var panel = event.target.closest('.adm-panel');
        var query = event.target.value.toLowerCase();
        panel.querySelectorAll('tbody tr').forEach(function (row) {
            row.hidden = row.textContent.toLowerCase().indexOf(query) < 0;
        });
    });

    /** 忽略筛选参数，同时保留query_string模式中作为页面身份的路由段。 */
    function pageURL(value) {
        var url = new URL(value, location.href);
        return url.origin + url.pathname + (url.search.indexOf('?/') === 0 ? url.search.split('&')[0] : '');
    }

    function markCurrent() {
        document.querySelectorAll('.adm-sidebar a').forEach(function (a) {
            if (pageURL(a.href) === pageURL(location.href)) a.setAttribute('aria-current', 'page');
        });
    }
    function ready() {
        markCurrent();
        if (typeof window.matchMedia === 'function') {
            var desktop = window.matchMedia('(min-width: 851px)');
            var resize = function () { if (desktop.matches) setNavigation(false, false); };
            if (desktop.addEventListener) desktop.addEventListener('change', resize);
            else if (desktop.addListener) desktop.addListener(resize);
            resize();
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
})();
