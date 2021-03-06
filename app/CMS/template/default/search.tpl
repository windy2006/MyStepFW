<div class="container-fluid px-4 px-md-5 mt-3 mb-5">
    <div class="row">
        <div class="col-12 col-lg-8 p-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb p-0 d-flex">
                    <li class="breadcrumb-item p-2"><a href="<!--url_prefix_app-->"><span class="glyphicon glyphicon-home"></span> 首页</a></li>
                    <li class="breadcrumb-item p-2">检索</li>
                    <li class="breadcrumb-item p-2 flex-grow-1"><!--keyword--></li>
                </ol>
            </nav>
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/630x80.png" alt="" />
            </div>
            <div class="news_list mb-3 border">
                <!--news keyword="$k" limit="$limit" class="item" template="simple"-->
            </div>
            <nav>
                <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
            </nav>
        </div>
        <div class="col-12 col-lg-4 p-0 pl-lg-3">
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
    $('input[name=k]').val('<!--keyword-->');
    if($('.news_list').text().replace(/\s+/g, '').length<3) {
        $('.news_list').html("未检索到相关内容！").css({
            'text-align': 'center',
            'font-size': '24px',
            'padding': '50px'
        });
    }
});
</script>