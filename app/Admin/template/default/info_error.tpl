<section class="adm-panel">
<h2>错误日志（最近2 MiB）</h2>
<pre><!--log|html--></pre>
<div class="adm-actions">
    <!--if:start key="has_log"-->
    <a class="adm-button" href="<!--download_url|html-->">下载完整日志</a>
    <!--else-->
    <button class="adm-button" type="button" disabled>下载完整日志</button>
    <!--if:end-->
    <form method="post" action="<!--page_url|html-->" data-confirm="确认清空错误日志？">
        <input type="hidden" name="_action" value="clear">
        <button class="adm-button" type="submit" <!--if:start key="has_log"--><!--else-->disabled<!--if:end-->>清空日志</button>
    </form>
</div>
</section>
