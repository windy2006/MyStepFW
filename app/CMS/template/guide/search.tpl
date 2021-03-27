<div id="main" class="border-left">
    <div class="mb-3 h-75">
        <nav aria-label="breadcrumb" class="w-100">
            <ol class="breadcrumb">
                <li class="breadcrumb-item" aria-current="page">首页</li>
                <li class="breadcrumb-item" aria-current="page">检索</li>
                <li class="breadcrumb-item" aria-current="page"><!--keyword--></li>
            </ol>
        </nav>
        <div class="w-100">
            <div class="news_list mb-3">
                <!--news keyword="$k" limit="$limit" class="item" template="search"-->
            </div>
            <nav>
                <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
            </nav>
        </div>
    </div>
</div>
<script type="application/javascript">
$(function(){
    $('#search_input').val('<!--keyword-->');
});
</script>