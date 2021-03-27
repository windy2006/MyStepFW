<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="form-inline mt-5 pt-3 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <div class="btn-group">
                <a class="btn btn-sm btn-info" href="clean" onclick="return confirm('是否确认清除所有日志信息？')">清空日志</a>
                <a class="btn btn-sm btn-info" href="download">保存日志</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th><a class="text-dark" href="?order_type=<!--order_type-->">编号</a></th>
                <th><a class="text-dark" href="?order=user&order_type=<!--order_type-->">操作人</a></th>
                <th><a class="text-dark" href="?order=group&order_type=<!--order_type-->">级别</a></th>
                <th><a class="text-dark" href="?order=link&order_type=<!--order_type-->">页面</a></th>
                <th><a class="text-dark" href="?order=comment&order_type=<!--order_type-->">维护说明</a></th>
                <th><a class="text-dark" href="?order=time&order_type=<!--order_type-->">时间</a></th>
            </tr>
            </thead>
            <!--if:start key="empty"-->
            <tr class="text-center">
                <td colspan="6" class="p-3"><h3>尚无任何相关管理记录，或者记录已被清空！</h3></td>
            </tr>
            <tbody class="d-none">
            <!--else-->
            <tbody>
            <!--if:end-->
            <!--loop:start key="record" time="20"-->
            <tr class="text-center no-wrap">
                <td><!--record_id--></td>
                <td><!--record_user--></td>
                <td><!--record_group--></a></td>
                <td class="text-left"><a href="<!--record_link-->" target="_blank"><!--record_link_short--></a></td>
                <td><!--record_comment--></a></td>
                <td><!--record_time--></td>
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
    global.root_fix += 'info/log/';
});
</script>
