/**
 * 文件：app/CMS/asset/guide/function.js
 * 功能说明：手册正文目录、高亮与快捷键；兼容原a[id^=p]章节及带id的新标题。
 * 方法目录：cmsGuideOutline、cmsGuideActiveHeading、cmsGuideKeyboard。
 * 使用说明：由框架在公开setting/language及jQuery后收集加载，仅操作当前guide页面。
 */
(function () {
    'use strict';
    let headings = [];
    let scheduled = false;
    let outline = [];
    let activeIndex = -1;

    /**
     * 功能说明：从真实标题建立本页目录，使用DOM文本节点，避免将标题解释为HTML。
     * 参数说明：无显式参数。
     * @returns {void} 没有标题时隐藏侧栏；现有标题id保持不变。
     */
    function cmsGuideOutline() {
        const content = document.getElementById('guide-body') || document.getElementById('content');
        const toc = document.getElementById('cat_list');
        if (!content || !toc) return;
        // 旧正文使用空锚点，新正文直接给标题id；两种形式只保留一个导航入口。
        headings = Array.from(content.querySelectorAll('h2,h3,h4')).filter(node => node.textContent.trim());
        if (!headings.length) headings = Array.from(content.querySelectorAll('a[id^="p"]')).map(node => node.parentElement);
        headings = Array.from(new Set(headings));
        toc.replaceChildren();
        if (!headings.length) {
            document.getElementById('side_cat').hidden = true;
            content.classList.add('guide-full-width');
            return;
        }
        const title = document.createElement('strong');
        title.textContent = '☷ 本页目录';
        toc.appendChild(title);
        const list = document.createElement('ul');
        outline = []; activeIndex = -1;
        const parents = [];
        headings.forEach((heading, index) => {
            const old = heading.querySelector('a[id^="p"]');
            if (!heading.id) {
                let candidate = old ? old.id : 'guide-section-' + (index + 1);
                if (old) old.removeAttribute('id');
                while (document.getElementById(candidate)) candidate += '-section';
                heading.id = candidate;
            }
            const item = document.createElement('li');
            const link = document.createElement('a');
            // 模板有base标签；单独#会指向base目录，必须保留当前路径和查询串。
            link.href = location.pathname + location.search + '#' + encodeURIComponent(heading.id);
            const icon = document.createElement('span');
            icon.className = 'guide-toc-icon'; icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '•';
            const label = document.createElement('span');
            label.className = 'guide-toc-label'; label.textContent = heading.textContent.trim();
            link.className = 'guide-toc-link'; link.append(icon, label);
            link.dataset.headingId = heading.id;
            link.dataset.level = /^H[2-4]$/.test(heading.tagName) ? heading.tagName.substring(1) : '2';
            const level = Number(link.dataset.level);
            while (parents.length && outline[parents[parents.length - 1]].level >= level) parents.pop();
            const parent = parents.length ? parents[parents.length - 1] : -1;
            if (parent >= 0) { outline[parent].link.classList.add('guide-toc-branch'); outline[parent].icon.textContent = '▸'; }
            outline.push({heading: heading, link: link, icon: icon, level: level, parent: parent});
            parents.push(index);
            item.appendChild(link);
            list.appendChild(item);
        });
        toc.appendChild(list);
        cmsGuideActiveHeading();
        $(window).off('scroll.cmsGuide resize.cmsGuide hashchange.cmsGuide').on('scroll.cmsGuide resize.cmsGuide hashchange.cmsGuide', function () {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(function () { scheduled = false; cmsGuideActiveHeading(); });
        });
    }

    /**
     * 功能说明：按可见标题同时突出当前目录及祖先链，当前位置改变后仅滚动目录容器以保持当前项可见。
     * 参数说明：无显式参数。
     * @returns {void} 更新当前项、父项及装饰图标，不改变正文滚动位置。
     */
    function cmsGuideActiveHeading() {
        if (!outline.length) return;
        const header = document.querySelector('header');
        // 标题scroll-margin-top为100px；阈值须覆盖它，否则点击锚点后仍会标记上一篇。
        const offset = Math.max((header ? header.getBoundingClientRect().height : 64) + 40, 101);
        let current = 0;
        outline.forEach((item, index) => { if (item.heading.getBoundingClientRect().top <= offset) current = index; });
        if (current === activeIndex) return;
        activeIndex = current;
        const ancestors = new Set();
        for (let parent = outline[current].parent; parent >= 0; parent = outline[parent].parent) ancestors.add(parent);
        outline.forEach((item, index) => {
            const active = index === current;
            item.link.classList.toggle('active', active);
            item.link.classList.toggle('guide-toc-parent', ancestors.has(index));
            if (active) item.link.setAttribute('aria-current', 'location');
            else item.link.removeAttribute('aria-current');
            item.icon.textContent = active ? '➤' : (item.link.classList.contains('guide-toc-branch') ? '▸' : '•');
        });
        const toc = document.getElementById('cat_list');
        const box = toc.getBoundingClientRect();
        const selected = outline[current].link.getBoundingClientRect();
        if (selected.top < box.top + 30) toc.scrollTop -= box.top + 30 - selected.top;
        else if (selected.bottom > box.bottom - 8) toc.scrollTop += selected.bottom - box.bottom + 8;
    }

    /**
     * 功能说明：Alt加方向键切换章节或定位页首/页尾；输入控件和浏览器组合键保持原行为。
     * @param {KeyboardEvent} event 当前键盘事件。
     * @returns {void} 只在存在合法阅读目标时阻止默认行为，不劫持普通上下键或触摸滚动。
     */
    function cmsGuideKeyboard(event) {
        if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.isComposing) return;
        if (event.target.closest && event.target.closest('input,textarea,select,[contenteditable]')) return;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            const link = document.getElementById(event.key === 'ArrowLeft' ? 'article_prev' : 'article_next');
            if (!link || !link.getAttribute('href')) return;
            const url = new URL(link.href, location.href);
            if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
            event.preventDefault();
            location.assign(url.href);
        } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            window.scrollTo({top: event.key === 'ArrowUp' ? 0 : document.documentElement.scrollHeight, behavior: 'smooth'});
        }
    }

    $(function () {
        cmsGuideOutline();
        $(document).off('keydown.cmsGuide').on('keydown.cmsGuide', cmsGuideKeyboard);
        $.vendor('highlight', {callback: function () { highlight(1, 'vs2015'); }});
    });
}());
