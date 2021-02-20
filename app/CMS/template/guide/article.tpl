<div id="main" class="border-left">
    <div class="mb-3 h-75">
        <div class="title"><!--record_subject--></div>
        <nav aria-label="breadcrumb" class="w-100">
            <ol class="breadcrumb">
                <!--loop:start key="cat_list"-->
                <li class="breadcrumb-item" aria-current="page"><!--cat_list_name--></li>
                <!--loop:end-->
            </ol>
        </nav>
        <div class="w-100 h-100">
            <div id="content" class="py-3">
                <div class="mb-3 font-weight-bold font-md <!--multi_page-->">
                    文章分页：
                    <select onchange="location.href=this.value">
                        <!--loop:start key="sub_title"-->
                        <option value="<!--sub_title_link-->" <!--sub_title_selected-->><!--sub_title_name--></option>
                        <!--loop:end-->
                    </select>
                </div>
                <div>
                    <!--record_content-->
                </div>
                <div class="row">
                    <div class="col">
                        上一篇：<!--news_next id='$id' cat_id='$cat_id' mode='prev'-->
                    </div>
                    <div class="col text-right">
                        下一篇：<!--news_next id='$id' cat_id='$cat_id'-->
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
            <div id="side_cat" class="pt-4">
                <div id="cat_list" class="position-sticky">
                    <div class="side-bar">
                        <span class="fa fa-circle start"></span>
                        <span class="fa fa-circle end"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript">
let cat_id = '<!--cat_id-->';
</script>