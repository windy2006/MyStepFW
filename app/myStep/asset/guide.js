/**
 * 文件：app/myStep/asset/guide.js (new)
 * 功能说明：新旧后台共用的指南局部阅读、目录状态保留、全文搜索和浏览历史。
 * 方法目录：init、target、sidebarSave/sidebarRestore、saveReading、status、request、
 *           mark/clearMarks、position、select、navigate、cancelSearch、search、clearSearch。
 * 使用说明：依赖框架已有jQuery、queryURL和checkForm；在guide模板范围绑定，不改全站路由。
 *           HTML正文仅来自同源已鉴权指南接口，搜索标题及摘要以textContent生成。
 */
(function (window, document, $) {
    'use strict';
    if (!$) return;

    /** 初始化当前指南容器；没有所需浏览器能力时保留普通目录链接。 */
    function init() {
        const root = document.querySelector('[data-ms-guide]');
        if (!root || root.dataset.guideReady || !window.fetch || !window.URL || !window.history.pushState || typeof window.queryURL !== 'function') return;
        root.dataset.guideReady = 'true';
        const home = new URL(root.dataset.guideHome, window.location.href);
        const nav = root.querySelector('.guide-directory nav');
        const directory = root.querySelector('.guide-directory');
        const groups = Array.from(root.querySelectorAll('.guide-group'));
        const article = root.querySelector('.guide-article');
        const form = root.querySelector('.guide-search');
        const input = form.querySelector('[name="q"]');
        const results = root.querySelector('.guide-results');
        const list = results.querySelector('ul');
        const count = results.querySelector('.guide-result-count');
        const message = root.querySelector('.guide-status');
        const storageKey = 'mystep-guide:' + home.pathname + home.search;
        let current = root.dataset.guideDoc;
        let pageNumber = 0, searchNumber = 0, pageAbort, searchAbort, searchTimer, sidebarTimer, scrollTimer;

        /**
         * 识别当前后台的指南链接；查询字符串模式另外比较index.php后的路由首段。
         * @param {string} href 待识别链接。
         * @returns {URL|null} 同源同一指南入口，或null。
         */
        function target(href) {
            let url;
            try { url = new URL(href, window.location.href); } catch (error) { return null; }
            if (url.origin !== home.origin || url.pathname.replace(/\/+$/, '') !== home.pathname.replace(/\/+$/, '')) return null;
            if (/\/index\.php$/.test(home.pathname)) {
                const route = home.search.slice(1).split('&')[0];
                if (route && route.indexOf('=') === -1) {
                    let other = url.search.slice(1).split('&')[0];
                    try { other = decodeURIComponent(other); } catch (error) { return null; }
                    if (other.replace(/^\/+|\/+$/g, '') !== route.replace(/^\/+|\/+$/g, '')) return null;
                }
            }
            return url;
        }

        /** 保存左栏位置、分类及整体目录开合；存储不可用时不影响正常阅读。 */
        function sidebarSave() {
            try {
                const open = {};
                groups.forEach(function (group) { open[group.querySelector('summary').textContent] = group.open; });
                window.sessionStorage.setItem(storageKey, JSON.stringify({top: nav.scrollTop, directory: directory.open, open: open}));
            } catch (error) { /* 私密窗口或配额限制不阻断导航。 */ }
        }

        /** 恢复同一后台本标签页的左栏状态，不改变当前文档或读取其他应用状态。 */
        function sidebarRestore() {
            try {
                const saved = JSON.parse(window.sessionStorage.getItem(storageKey));
                if (!saved || typeof saved.open !== 'object') return;
                groups.forEach(function (group) {
                    const open = saved.open[group.querySelector('summary').textContent];
                    if (typeof open === 'boolean') group.open = open;
                });
                if (typeof saved.directory === 'boolean') directory.open = saved.directory;
                if (Number.isFinite(saved.top) && saved.top >= 0) nav.scrollTop = saved.top;
            } catch (error) { /* 旧数据或禁用存储时使用模板的初始状态。 */ }
        }

        /** 将右侧滚动位置记入当前历史项，合并而不覆盖其他模块的history.state字段。 */
        function saveReading() {
            const state = Object.assign({}, window.history.state || {});
            state.msGuide = {home: home.href, id: current, top: article.scrollTop};
            window.history.replaceState(state, '', window.location.href);
        }

        /**
         * 显示加载或错误反馈；整页打开链接由同源指南URL生成。
         * @param {string} text 纯文本状态；空字符串隐藏。
         * @param {URL|null} url 可选的整页重试入口。
         */
        function status(text, url) {
            message.textContent = text;
            if (url) {
                const link = document.createElement('a');
                link.textContent = '整页打开'; link.href = url.href;
                link.dataset.guideReload = 'true'; message.append(' ', link);
            }
            message.hidden = !text;
        }

        /**
         * 读取JSON；拒绝登录页、重定向后的HTML和失败响应，避免当正文插入。
         * @param {string} url queryURL生成的当前指南接口地址。
         * @param {AbortController|undefined} controller 本次请求的取消句柄。
         * @returns {Promise<Object>} 成功JSON；错误时抛出可展示消息。
         */
        function request(url, controller) {
            return window.fetch(url, {
                method: 'GET', credentials: 'same-origin', cache: 'no-store',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                signal: controller ? controller.signal : undefined
            }).then(function (response) {
                if (!/application\/json/i.test(response.headers.get('Content-Type') || '')) throw new Error('登录可能已失效，请刷新页面后重试。');
                return response.json().then(function (data) {
                    if (!response.ok || !data || data.ok !== true) throw new Error(data && data.message ? data.message : '文档请求失败，请稍后重试。');
                    return data;
                });
            });
        }

        /** 移除本脚本添加的关键词标记，不删除正文原有元素和事件。 */
        function clearMarks() {
            article.querySelectorAll('mark.guide-hit').forEach(function (hit) {
                const parent = hit.parentNode;
                parent.replaceChild(document.createTextNode(hit.textContent), hit); parent.normalize();
            });
        }

        /**
         * 在正文文本节点内标记关键词，保留代码与标签结构；不将用户查询当HTML。
         * @param {string} query 当前搜索词，最多标记300处。
         */
        function mark(query) {
            clearMarks();
            const content = article.querySelector('.guide-document');
            const terms = Array.from(new Set(query.trim().split(/\s+/).filter(Boolean))).sort(function (a, b) { return b.length - a.length; });
            if (!content || !terms.length) return;
            const expression = new RegExp(terms.map(function (term) { return term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|'), 'gi');
            const walker = document.createTreeWalker(content, window.NodeFilter.SHOW_TEXT);
            const nodes = []; let node, total = 0;
            while ((node = walker.nextNode())) if (!node.parentElement.closest('script,style,textarea,mark')) nodes.push(node);
            nodes.forEach(function (textNode) {
                if (total >= 300) return;
                const value = textNode.nodeValue, fragment = document.createDocumentFragment();
                let match, offset = 0; expression.lastIndex = 0;
                while (total < 300 && (match = expression.exec(value))) {
                    fragment.append(document.createTextNode(value.slice(offset, match.index)));
                    const hit = document.createElement('mark'); hit.className = 'guide-hit'; hit.textContent = match[0]; fragment.append(hit);
                    offset = match.index + match[0].length; total++;
                }
                if (offset) { fragment.append(document.createTextNode(value.slice(offset))); textNode.parentNode.replaceChild(fragment, textNode); }
            });
        }

        /**
         * 只滚动右侧阅读区域，不调用会牵动左栏和整页的scrollIntoView。
         * @param {string} hash 目标章节锚点。
         * @param {number|undefined} top 浏览历史保存的右侧位置。
         * @param {boolean} useMatch 是否优先定位搜索首个命中。
         */
        function position(hash, top, useMatch) {
            let element;
            if (Number.isFinite(top)) { article.scrollTop = top; return; }
            if (hash) {
                let id; try { id = decodeURIComponent(hash.slice(1)); } catch (error) { id = hash.slice(1); }
                const candidate = Array.from(article.querySelectorAll('[id]')).find(function (item) { return item.id === id; }); if (candidate) element = candidate;
            }
            if (!element && useMatch) element = article.querySelector('mark.guide-hit');
            article.scrollTop = element ? article.scrollTop + element.getBoundingClientRect().top - article.getBoundingClientRect().top - 18 : 0;
        }

        /** 更新当前条目标记，保持原左栏DOM、滚动位置和所有分类开合。 */
        function select() {
            const top = nav.scrollTop;
            nav.querySelectorAll('a[href]').forEach(function (link) {
                const url = target(link.href);
                link.setAttribute('aria-current', url && (url.searchParams.get('doc') || 'index') === current ? 'page' : 'false');
            });
            nav.scrollTop = top; sidebarSave();
        }

        /**
         * 局部加载文档并维护浏览历史；请求序号与取消句柄共同防止旧响应覆盖新内容。
         * @param {URL} url 已经target校验的指南地址。
         * @param {boolean} push 是否新增历史项；popstate为false。
         * @param {number|undefined} top 恢复的右侧位置。
         * @param {boolean} useMatch 从搜索结果进入时定位首个正文命中。
         */
        function navigate(url, push, top, useMatch) {
            const number = ++pageNumber;
            window.clearTimeout(scrollTimer);
            if (pageAbort) pageAbort.abort();
            const id = url.searchParams.get('doc') || 'index';
            if (push) saveReading();
            if (id === current && url.hash) {
                if (push) window.history.pushState(Object.assign({}, window.history.state || {}), '', url.href);
                position(url.hash, top, false); saveReading(); status(''); article.setAttribute('aria-busy', 'false'); return;
            }
            pageAbort = window.AbortController ? new window.AbortController() : undefined;
            article.setAttribute('aria-busy', 'true'); status('正在加载文档…');
            request(window.queryURL(home.href, {_guide: 'page', doc: id}), pageAbort).then(function (data) {
                if (number !== pageNumber) return;
                const canonical = target(data.url);
                if (data.type !== 'document' || !canonical || typeof data.article !== 'string' || typeof data.id !== 'string' || typeof data.title !== 'string') throw new Error('文档响应格式错误，请重新打开。');
                canonical.hash = url.hash;
                if (push) saveReading();
                article.innerHTML = data.article; current = data.id; root.dataset.guideDoc = current;
                mark(input.value); select(); position(canonical.hash, top, Boolean(useMatch));
                document.title = data.title + ' · 框架指南';
                if (push) window.history.pushState(Object.assign({}, window.history.state || {}), '', canonical.href);
                saveReading(); status(''); article.setAttribute('aria-busy', 'false');
                if (push) article.focus({preventScroll: true});
            }).catch(function (error) {
                if (number !== pageNumber || error.name === 'AbortError') return;
                article.setAttribute('aria-busy', 'false'); status(error.message || '文档加载失败，请重试。', url);
            });
        }

        /** 使旧搜索响应失效并取消定时器和传输；不影响当前正文加载。 */
        function cancelSearch() {
            searchNumber++; window.clearTimeout(searchTimer);
            if (searchAbort) searchAbort.abort();
        }

        /** 执行标题与正文搜索，以纯文本构造摘要列表，不改左侧目录或当前正文。 */
        function search() {
            cancelSearch(); const query = input.value.trim(), number = searchNumber;
            if (!query) { clearSearch(); return; }
            if (typeof window.checkForm === 'function' && !window.checkForm(form)) {
                results.hidden = false; results.setAttribute('aria-busy', 'false'); count.textContent = '请检查搜索词的长度或格式。'; list.textContent = ''; return;
            }
            results.hidden = false; results.setAttribute('aria-busy', 'true'); count.textContent = '正在搜索…'; list.textContent = '';
            searchAbort = window.AbortController ? new window.AbortController() : undefined;
            request(window.queryURL(home.href, {_guide: 'search', q: query}), searchAbort).then(function (data) {
                if (number !== searchNumber || input.value.trim() !== query) return;
                if (data.type !== 'search' || !Array.isArray(data.items) || !Number.isInteger(data.total)) throw new Error('搜索响应格式错误，请重试。');
                count.textContent = data.total ? '找到 ' + data.total + ' 篇文档' + (data.total > data.items.length ? '，显示前 ' + data.items.length + ' 篇；可增加关键词缩小范围。' : '。') : '没有找到匹配文档，可换用功能名、类名或方法名。';
                data.items.forEach(function (item) {
                    const url = target(item.url); if (!url) return;
                    const row = document.createElement('li'), link = document.createElement('a'), group = document.createElement('span'), snippet = document.createElement('p');
                    link.href = url.href; link.textContent = item.title; group.textContent = item.group; snippet.textContent = item.snippet;
                    row.append(link, group, snippet); list.append(row);
                });
                results.setAttribute('aria-busy', 'false'); mark(query);
            }).catch(function (error) {
                if (number !== searchNumber || error.name === 'AbortError') return;
                results.setAttribute('aria-busy', 'false'); count.textContent = error.message || '搜索失败，请稍后重试。';
            });
        }

        /** 清空查询、搜索结果和正文标记，保留当前文档及左右滚动位置。 */
        function clearSearch() {
            cancelSearch(); input.value = ''; results.hidden = true; results.setAttribute('aria-busy', 'false'); list.textContent = ''; count.textContent = ''; clearMarks();
        }

        sidebarRestore();
        nav.addEventListener('scroll', function () { window.clearTimeout(sidebarTimer); sidebarTimer = window.setTimeout(sidebarSave, 100); }, {passive: true});
        groups.concat([directory]).forEach(function (item) { item.addEventListener('toggle', sidebarSave); });
        article.addEventListener('scroll', function () { window.clearTimeout(scrollTimer); scrollTimer = window.setTimeout(function () { if (article.getAttribute('aria-busy') !== 'true') saveReading(); }, 100); }, {passive: true});
        root.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');
            if (!link || link.dataset.guideReload || link.hasAttribute('download') || link.target === '_blank' || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const url = target(link.href); if (!url) return;
            event.preventDefault(); navigate(url, true, undefined, results.contains(link));
        });
        form.addEventListener('submit', function (event) { event.preventDefault(); search(); });
        form.querySelector('.guide-clear').addEventListener('click', function () { clearSearch(); input.focus(); });
        input.addEventListener('input', function () {
            cancelSearch();
            if (!input.value.trim()) { clearSearch(); return; }
            searchTimer = window.setTimeout(search, 350);
        });
        input.addEventListener('keydown', function (event) { if (event.key === 'Escape') { event.preventDefault(); clearSearch(); } });
        window.addEventListener('popstate', function (event) {
            const url = target(window.location.href); if (!url) return;
            const saved = event.state && event.state.msGuide;
            navigate(url, false, saved && saved.home === home.href && Number.isFinite(saved.top) ? saved.top : undefined);
        });
        window.addEventListener('pagehide', sidebarSave);
        form.querySelectorAll('input,button').forEach(function (control) { control.disabled = false; });
        position(window.location.hash, undefined, false); saveReading(); if (input.value.trim()) search();
    }
    $(init);
})(window, document, window.jQuery);
