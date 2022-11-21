<div class="container-fluid px-4 px-md-5 mt-3 mb-5">
    <div class="row">
        <div class="col-12 col-lg-8 p-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb p-0 d-flex">
                    <li class="breadcrumb-item p-2"><a href="<!--url_prefix_app-->"><span class="glyphicon glyphicon-home"></span> 首页</a></li>
                    <!--loop:start key="cat_list"-->
                    <li class="breadcrumb-item p-2"><a href="<!--cat_list_link-->"><!--cat_list_name--></a></li>
                    <!--loop:end-->
                    <li class="breadcrumb-item p-2 flex-grow-1">文章目录</li>
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
                <!--news catalog="$catalog" class="row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4" prefix="$prefix" limit="$limit" template="gallery"-->
            </div>
            <nav>
                <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
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