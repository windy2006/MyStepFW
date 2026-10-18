<div class="guide-heading"><h2>框架指南</h2><p>按主题阅读现有文档；内容直接来自网站文档目录，应用与站点资料单独分类。</p></div>
<!--if:start key="guide_error"-->
<section class="guide-article"><h2><!--guide_title|html--></h2><p><!--guide_error|html--></p><a href="<!--guide_home|html-->">返回文档目录</a></section>
<!--else-->
<div class="guide-layout">
    <aside class="guide-nav"><details class="guide-directory" open><summary>文档目录</summary><nav aria-label="框架文档">
        <!--loop:start key="groups"--><!--groups_content--><!--loop:end-->
    </nav></details></aside>
    <article class="guide-article">
        <header class="guide-meta"><h2><!--guide_title|html--></h2><span>文档来源：<!--guide_path|html--></span></header>
        <details class="guide-outline"><summary>本篇章节</summary><ul>
            <!--loop:start key="outline"--><li data-level="<!--outline_level|html-->"><a href="<!--outline_url|html-->"><!--outline_title|html--></a></li><!--loop:end-->
        </ul></details>
        <div class="guide-document"><!--guide_html--></div>
    </article>
</div>
<!--if:end-->
