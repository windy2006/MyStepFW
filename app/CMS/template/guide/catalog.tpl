<div id="main" class="border-left">
    <div class="mb-3 h-75">
        <nav aria-label="breadcrumb" class="w-100">
            <ol class="breadcrumb">
                <li class="breadcrumb-item" aria-current="page">首页</li>
                <!--loop:start key="cat_list"-->
                <li class="breadcrumb-item" aria-current="page"><!--cat_list_name--></li>
                <!--loop:end-->
            </ol>
        </nav>
        <div class="w-100">
            <ul class="nav justify-content-center pre_list mb-3">
                <!--loop:start key="prefix"-->
                <li class="nav-item mx-3">
                    <a class="btn btn-<!--prefix_class-->" href="?pre=<!--prefix_name-->"><!--prefix_name--></a>
                </li>
                <!--loop:end-->
            </ul>
            <div class="news_list mb-3 border">
                <!--news catalog="$catalog" show_cat="y" limit="$limit" class="item" prefix="$prefix" template="search"-->
            </div>
            <nav>
                <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
            </nav>
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