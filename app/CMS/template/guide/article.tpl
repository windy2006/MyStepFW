<main id="main" class="border-left">
    <div class="mb-3 h-100">
        <h1 class="title"><!--record_subject|html--></h1>
        <nav aria-label="breadcrumb" class="w-100">
            <ol class="breadcrumb">
                <!--loop:start key="cat_list"-->
                <li class="breadcrumb-item" aria-current="page"><!--cat_list_name--></li>
                <!--loop:end-->
            </ol>
        </nav>
        <div class="guide-columns">
            <div id="content" class="py-3 h-100">
                <div class="mb-3 font-weight-bold font-md <!--multi_page-->">
                    文章分页：
                    <select onchange="location.href=this.value">
                        <!--loop:start key="sub_title"-->
                        <option value="<!--sub_title_link-->" <!--sub_title_selected-->><!--sub_title_name--></option>
                        <!--loop:end-->
                    </select>
                </div>
                <div id="guide-body">
                    <!--record_content-->
                </div>
                <div class="row">
                    <div class="col">
                        上一篇：<a id="article_prev" href="<!--guide_previous_url|html-->"><!--guide_previous_title|html--></a>
                    </div>
                    <div class="col text-right">
                        下一篇：<a id="article_next" href="<!--guide_next_url|html-->"><!--guide_next_title|html--></a>
                    </div>
                </div>
                <nav class="w-100 mb-3 mx-auto <!--multi_page-->">
                    <ul class="pagination pagination-sm justify-content-center">
                        <!--loop:start key="page"-->
                        <li class="page-item <!--page_active-->"><a class="page-link" href="<!--page_link-->"><!--page_no--></a></li>
                        <!--loop:end-->
                    </ul>
                </nav>
            </div>
            <aside id="side_cat" class="pt-4" aria-label="本页章节"><nav id="cat_list" class="position-sticky"></nav></aside>
        </div>
    </div>
</main>
<script type="application/javascript">
let cat_id = '<!--cat_id-->';
</script>
