<div class="guide-heading"><h2>框架指南</h2><p>点击目录局部阅读正文，目录位置与展开状态保持不变；可搜索标题和正文，快速定位功能说明。</p></div>
<!--if:start key="guide_error"-->
<section class="guide-article"><h2><!--guide_title|html--></h2><p><!--guide_error|html--></p><a href="<!--guide_home|html-->">返回文档目录</a></section>
<!--else-->
<div class="guide-layout" data-ms-guide data-guide-home="<!--guide_home|html-->" data-guide-doc="<!--guide_id|html-->">
    <aside class="guide-nav">
        <form class="guide-search" role="search" action="<!--guide_home|html-->" method="get">
            <label for="guide-query">搜索文档</label>
            <div class="guide-search-input"><input id="guide-query" type="search" name="q" value="<!--guide_query|html-->" need="_" len="160" maxlength="80" placeholder="标题、正文或方法名" autocomplete="off" disabled><button type="submit" disabled>搜索</button></div>
            <div class="guide-search-help"><span>多个关键词用空格分隔</span><button type="button" class="guide-clear" disabled>清除</button></div>
            <noscript>启用JavaScript后可以搜索；目录链接仍可正常阅读。</noscript>
        </form>
        <details class="guide-directory" open><summary>文档目录</summary><nav aria-label="框架文档">
            <!--loop:start key="groups"--><!--groups_content--><!--loop:end-->
        </nav></details>
    </aside>
    <div class="guide-main">
        <p class="guide-status" role="status" aria-live="polite" hidden></p>
        <section class="guide-results" aria-label="文档搜索结果" hidden><h3>搜索结果</h3><p class="guide-result-count" role="status" aria-live="polite"></p><ul></ul></section>
        <article class="guide-article" tabindex="-1" aria-label="文档正文"><!--guide_article--></article>
    </div>
</div>
<!--if:end-->
