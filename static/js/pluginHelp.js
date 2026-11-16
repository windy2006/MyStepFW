/**
 * 文件：static/js/pluginHelp.js (new)
 * 功能说明：为插件设置控件追加README对应章节链接，保留原控件及提交数据。
 * 方法目录：section、titleFor、enhance、init；功能与参数见各方法说明。
 * 使用说明：仅处理data-plugin-help容器；URL及章节清单由受信后台生成，
 *           无外部依赖。支持设计器动态字段，缺少文档时不生成空链接。
 */
(function (window, document) {
    'use strict';

    /**
     * 根据标准控件名称解析文档章节，重复密码与原密码使用同一说明。
     * @param {HTMLElement} control 当前input、select或textarea。
     * @returns {string} setting-或field-章节名；未命名控件返回空串。
     */
    function section(control) {
        let name = control.getAttribute('name') || '';
        const named = name !== '';
        if (!named) {
            const ids = {app: 'app', jQuery: 'jquery', BootStrap: 'bootstrap',
                'cf-import-json': 'fields_json', 'cf-template-select': 'template-selector'};
            name = ids[control.id] || '';
        }
        if (!name) return '';
        if (name === 'apps[]') return ''; // 应用绑定是后台通用设置，不生成插件README帮助。
        const prefix = name.indexOf('setting[') === 0 ? 'setting-' : 'field-';
        if (prefix === 'setting-') name = name.slice(8);
        name = name.replace(/\[|\]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        name = name.replace(/_pwd_r$/, '');
        return prefix + name.toLowerCase();
    }

    /**
     * 找到项目名称的容器；隐式label只包裹名称文字，保留原控件、说明和表格结构。
     * @param {HTMLElement} control 当前控件。
     * @param {HTMLElement} root 当前插件设置容器。
     * @returns {HTMLElement} 用于在项目名称下方放置链接的节点。
     */
    function titleFor(control, root) {
        const group = control.closest('.adm-field,.form-group,.input-group');
        if (group && root.contains(group)) {
            const title = group.matches('.adm-field') ? group.querySelector(':scope > span') :
                Array.from(group.children).find(node => node.matches('label') && !node.querySelector('input,select,textarea')) ||
                group.querySelector('.input-group-text');
            if (title) { title.classList.add('plugin-help-title'); return title; }
        }
        let label = control.closest('label');
        if (!label && control.id) label = Array.from(root.querySelectorAll('label[for]')).find(node => node.htmlFor === control.id);
        if (label) {
            if (!label.contains(control)) { label.classList.add('plugin-help-title'); return label; }
            const existing = label.querySelector(':scope > .plugin-help-title');
            if (existing) return existing;
            const title = document.createElement('span');
            title.className = 'plugin-help-title';
            const field = Array.from(label.childNodes).find(node => node === control || (node.nodeType === 1 && node.contains(control)));
            const flag = /^(checkbox|radio)$/i.test(control.type);
            for (const node of Array.from(label.childNodes)) {
                if (node === field) { if (!flag) break; continue; }
                if (node.nodeType === 1 && node.matches('a.config-field-help')) continue;
                title.appendChild(node);
            }
            if (!title.textContent.trim()) title.textContent = control.getAttribute('aria-label') || control.name || control.id;
            if (flag) field.insertAdjacentElement('afterend', title);
            else label.insertBefore(title, field);
            return title;
        }
        const panel = control.closest('[data-cf-template-panel]');
        const heading = panel && panel.querySelector('h4');
        const cell = control.closest('td');
        const table = cell && cell.closest('table');
        const column = table && table.querySelector('tr')?.children[cell.cellIndex];
        const host = heading || (column?.tagName === 'TH' ? column : null);
        if (host) {
            let title = host.querySelector(':scope > .plugin-help-title');
            if (!title) {
                title = document.createElement('span');title.className = 'plugin-help-title';
                while (host.firstChild) title.appendChild(host.firstChild);
                host.appendChild(title);
            }
            return title;
        }
        const title = document.createElement('span');title.className = 'plugin-help-title';
        title.textContent = control.getAttribute('aria-label') || control.name || control.id;
        control.insertAdjacentElement('beforebegin', title);
        return title;
    }

    /**
     * 初始化一个后台容器，按白名单章节插入链接，不改控件值、名称或事件。
     * @param {HTMLElement} root 当前插件设置容器。
     * @returns {void}
     */
    function enhance(root) {
        let anchors, url;
        try {
            anchors = JSON.parse(root.getAttribute('data-plugin-help-map') || '{}');
            const source = root.getAttribute('data-plugin-help-url');
            if (!source) return;
            url = new URL(source, window.location.href);
            if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return;
        } catch (error) { return; }
        if (!anchors || Array.isArray(anchors) || typeof anchors !== 'object') return;
        /** 按当前控件增补说明链接；已生成链接保持不变，不写入表单数据。 */
        function decorate() {
            root.querySelectorAll('input,select,textarea').forEach(function (control) {
                if (control.closest('[data-plugin-help]') !== root || control.closest('.CodeMirror') || /^(hidden|submit|button|reset|image)$/i.test(control.type)) return;
                const key = section(control);
                if (!Object.prototype.hasOwnProperty.call(anchors, key) || anchors[key] !== true) return;
                // 原始控件的相邻链接仍在时不重复添加；克隆或重建控件也可补齐。
                if (control._pluginHelpLink && control._pluginHelpLink.isConnected) return;
                const label = control.closest('label');
                const group = control.closest('.adm-field,.form-group,.input-group');
                const destination = group || label || control.parentElement;
                if (!destination || !root.contains(destination)) return;
                // 同组选择共享标题处的说明；密码确认行仍各自保留对应链接。
                const title = titleFor(control, root);
                let link = Array.from(title.querySelectorAll('a[data-plugin-help-section]')).concat(
                    Array.from(destination.querySelectorAll('a[data-plugin-help-section]'))
                ).find(function (item) { return item.getAttribute('data-plugin-help-section') === key; });
                // 复用结构中已经声明的帮助链接，避免同一字段显示两个填写说明。
                if (!link) link = destination.querySelector('a.config-field-help:not([data-plugin-help-section]):not([data-plugin-help-index])');
                if (!link) link = document.createElement('a');
                const target = new URL(url.href);
                target.hash = key;
                link.href = target.href;
                link.className = 'config-field-help plugin-field-help';
                link.textContent = '填写说明';
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.setAttribute('data-plugin-help-section', key);
                link.setAttribute('aria-label', '查看 ' + key.replace(/^(setting|field)-/, '') + ' 的填写说明');
                if (link.parentElement !== title) title.appendChild(link);
                control._pluginHelpLink = link;
            });
        }
        decorate();
        if (window.MutationObserver) {
            let pending = false;
            new MutationObserver(function (changes) {
                if (pending || !changes.some(function (change) {
                    return Array.from(change.addedNodes).some(function (node) {
                        return node.nodeType === 1 && !node.matches('a[data-plugin-help-section]');
                    });
                })) return;
                pending = true;
                window.setTimeout(function () { pending = false; decorate(); }, 0);
            }).observe(root, {childList: true, subtree: true});
        }
    }

    /** 初始化页面中的插件设置容器；重复载入脚本时不重复注册观察器。 */
    function init() {
        document.querySelectorAll('[data-plugin-help]').forEach(function (root) {
            if (root.dataset.pluginHelpReady) return;
            root.dataset.pluginHelpReady = 'true';
            enhance(root);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})(window, document);
