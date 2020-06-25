<div id="main" class="border-left">
    <div class="mb-3 h-75">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
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
                <!--news catalog="$catalog" show_cat="y" limit="$limit" class="item" loop="$loop" prefix="$prefix" template="simple"-->
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