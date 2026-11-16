/* 功能说明：复用checkForm校验表单与本地CodeMirror编辑源码，处理操作确认、复位、行编辑、列表过滤与窄屏导航。 */
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

    /**
     * 功能说明：将模板标记的源码框转换为本地CodeMirror；普通文本框不处理。
     * @param {Document|Element} context 初始化范围；新增类加载行时只初始化该行。
     * @returns {void}
     */
    function codeEditors(context) {
        if (typeof window.CodeMirror !== 'function') return;
        context.querySelectorAll('textarea[data-admin-code]').forEach(function (input) {
            if (input.adminCodeEditor) return;
            var editor = window.CodeMirror.fromTextArea(input, {
                mode: input.dataset.adminCode === 'php-array' || !/^\s*</.test(input.value)
                    ? 'application/x-httpd-php-open' : 'application/x-httpd-php',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4,
                matchBrackets: true,
                styleActiveLine: true,
                readOnly: input.disabled || input.readOnly,
                leaveSubmitMethodAlone: true,
                extraKeys: {
                    Tab: function (cm) { cm.execCommand(cm.somethingSelected() ? 'indentMore' : 'insertSoftTab'); },
                    'Shift-Tab': 'indentLess',
                    'Ctrl-F11': function (cm) { cm.setOption('fullScreen', !cm.getOption('fullScreen')); },
                    Esc: function (cm) { if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false); },
                    'Ctrl-Enter': function (cm) {
                        var form = input.form;
                        if (input.hasAttribute('data-admin-run') && form && typeof form.requestSubmit === 'function') {
                            cm.save();
                            form.requestSubmit();
                        }
                    }
                }
            });
            input.adminCodeEditor = editor;
            editor.getWrapperElement().classList.add('adm-code-editor');
            editor.setSize('100%', Math.max(130, Math.min(480, (input.rows || 8) * 22 + 16)));
            var field = editor.getInputField();
            var label = input.closest('label');
            var caption = label && label.querySelector('span');
            field.setAttribute('aria-label', input.getAttribute('aria-label') || (caption ? caption.textContent.trim() : '特别指定') + ' 代码编辑器');
            if (input.getAttribute('aria-describedby')) field.setAttribute('aria-describedby', input.getAttribute('aria-describedby'));
            editor.on('change', function () {
                // 即时写回原控件，checkForm、原生约束及FormData都读取同一份源码。
                editor.save();
                var mode = input.dataset.adminCode === 'php-array' || !/^\s*</.test(input.value)
                    ? 'application/x-httpd-php-open' : 'application/x-httpd-php';
                if (editor.getOption('mode') !== mode) editor.setOption('mode', mode);
                editor.getWrapperElement().classList.remove('is-invalid');
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }

    /** 原生required失败时定位可见编辑器，避免浏览器尝试聚焦已隐藏的源码框。 */
    document.addEventListener('invalid', function (event) {
        if (!event.target.adminCodeEditor) return;
        event.preventDefault();
        event.target.adminCodeEditor.focus();
        event.target.adminCodeEditor.getWrapperElement().classList.add('is-invalid');
    }, true);

    /** 表单复位在浏览器恢复默认值之后同步编辑器，保留用户取消复位的行为。 */
    document.addEventListener('reset', function (event) {
        var form = event.target;
        if (!form.matches('.adm-shell form')) return;
        setTimeout(function () {
            if (event.defaultPrevented) return;
            form.querySelectorAll('textarea[data-admin-code]').forEach(function (input) {
                if (input.adminCodeEditor) input.adminCodeEditor.setValue(input.value);
            });
        }, 0);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('.adm-shell form') || form.getAttribute('data-ms-api-form') === 'true') return;
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
            var invalid = form.querySelector && form.querySelector('textarea[data-admin-code].is-invalid');
            if (invalid && invalid.adminCodeEditor) invalid.adminCodeEditor.focus();
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
            // 克隆行只保留原字段，删除复制的编辑器DOM，再为新行创建独立实例。
            row.querySelectorAll('.CodeMirror').forEach(function (node) { node.remove(); });
            row.querySelectorAll('textarea[data-admin-code]').forEach(function (input) { input.style.display = ''; });
            row.querySelectorAll('input,textarea,select').forEach(function (input) {
                input.value = input.dataset && input.dataset.defaultValue || '';
                // 新行的复位基准应是空值／默认值，不能沿用被复制行的源码。
                if ('defaultValue' in input) input.defaultValue = input.value;
            });
            row.querySelectorAll('[data-remove-row]').forEach(function (button) { button.disabled = false; });
            row.querySelectorAll('[data-row-existing]').forEach(function (link) { link.remove(); });
            body.appendChild(row);
            reindex(body);
            codeEditors(row);
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
        codeEditors(document);
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
