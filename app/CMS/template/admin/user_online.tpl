<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="form-inline mt-5 pt-3 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <input type="text" name="keyword" class="input-group-prepend" value="<!--keyword-->" placeholder="请输入查询关键字" />
            <div class="input-group-append">
                <button class="btn btn-light btn-outline-secondary" name="keyword" type="button">检索</button>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered border-0 table-hover my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th width="120"><a class="text-dark" href="?keyword=<!--keyword-->&order=ip&&order_type=<!--order_type-->">IP</a></th>
                <th width="100">用户名称</th>
                <th width="100">用户组</th>
                <th width="160"><a class="text-dark" href="?keyword=<!--keyword-->&order=refresh&order_type=<!--order_type-->">刷新时间</a></th>
                <th><a class="text-dark" href="?keyword=<!--keyword-->&order=url&order_type=<!--order_type-->">浏览页面</a></th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="20"-->
            <tr class="text-center no-wrap" title="<!--record_sid-->">
                <td><!--record_ip--></td>
                <td><!--record_username--></td>
                <td><!--record_group--></a></td>
                <td><!--record_refresh--></td>
                <td class="text-left"><a href="<!--record_url-->" target="_blank"><!--record_url_simple--></a></td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
    <nav>
        <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
    </nav>
</div>
<script type="application/javascript">
$(function(){
    $('input[name=keyword]').keypress(function(e){
        if(e.keyCode==13) {
            location.href=global.root_fix+'?keyword='+this.value;
        }
    });
    $('button[name=keyword]').click(function(e){
        location.href=global.root_fix+'?keyword='+$('input[name=keyword]').val();
    });
    global.root_fix += 'user/online/';
});
</script>
