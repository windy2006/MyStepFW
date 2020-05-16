<div class="container-fluid px-4 px-md-5 mt-3 mb-5">
    <div class="row">
        <div class="col-12 col-lg-8 p-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb p-0 d-flex">
                    <li class="breadcrumb-item p-2"><a href="<!--url_prefix_app-->"><span class="glyphicon glyphicon-home"></span> 首页</a></li>
                    <!--loop:start key="cat_list"-->
                    <li class="breadcrumb-item p-2"><a href="<!--cat_list_link-->"><!--cat_list_name--></a></li>
                    <!--loop:end-->
                    <li class="breadcrumb-item p-2 flex-grow-1"><!--cat_name--></li>
                </ol>
            </nav>
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/630x80.png" alt="" />
            </div>
            <ul class="nav justify-content-center pre_list mb-3">
                <!--loop:start key="prefix"-->
                <li class="nav-item mx-3">
                    <a class="btn btn-<!--prefix_class-->" href="?pre=<!--prefix_name-->"><!--prefix_name--></a>
                </li>
                <!--loop:end-->
            </ul>
            <div class="mb-3">
                <!--news catalog="$catalog" class="row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4" limit="$limit" template="gallery"-->
            </div>
            <nav>
                <ul class="pagination pagination-sm justify-content-center">
                    <li class="page-item"><a class="page-link" href="<!--link_first-->">首页</a></li>
                    <li class="page-item"><a class="page-link" href="<!--link_prev-->">上页</a></li>
                    <li class="page-item"><a class="page-link" href="<!--link_next-->">下页</a></li>
                    <li class="page-item"><a class="page-link" href="<!--link_last-->">末页</a></li>
                    <li>
                        <div class="input-group input-group-sm mb-2 mr-sm-2">
                            <input type="text" class="form-control" name="jump" size="2" value="<!--page_current-->" style="text-align:center" />
                            <div class="input-group-append">
                                <button class="btn btn-light btn-outline-secondary" name="jump" type="button">跳页</button>
                            </div>
                        </div>
                    </li>
                    <li class="pl-4 pt-1"> 【 共 <!--page_count--> 页，  <!--record_count--> 条记录 】</li>
                </ul>
            </nav>
        </div>
        <div class="col-12 col-lg-4 p-0 pl-lg-3">
            <!--catalog id="$catalog" show="2" class="cat_list"-->
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/300x80.png" alt="" />
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">最新更新</h5>
                </div>
                <div class="card-body">
                    <!--news limit="10" loop="10" order="news_id desc"-->
                </div>
            </div>
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/300x80.png" alt="" />
            </div>
        </div>
    </div>
</div>

<script type="application/javascript">
$(function(){
    $('input[name=jump]').keypress(function(e){
        if(e.keyCode==13) {
            location.href=global.root_fix+'?pre=<!--prefix-->&page='+this.value;
        }
    });
    $('button[name=jump]').click(function(e){
        location.href=global.root_fix+'?pre=<!--prefix-->&page='+$('input[name=jump]').val();
    });
});
</script>