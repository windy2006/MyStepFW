<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加子站</a>
    </div>
    <div class="card-body p-0 table-responsive col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2 p-0 mt-5">
        <table class="table table-sm table-striped table-bordered table-hover my-md-3 bg-white">
            <thead class="thead-light">
            <tr class="text-center">
                <th width="40">编号</th>
                <th width="250">网站名称</th>
                <th>索引</th>
                <th>域名</th>
                <th width="92">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="15"-->
            <tr>
                <td align="center"><!--record_web_id--></td>
                <td><a href="<!--record_link-->" target="_blank"><!--record_name--></a></td>
                <td><!--record_idx--></td>
                <td><!--record_domain--></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" href="edit/<!--record_web_id-->">修改</a>
                        <a class="btn btn-sm btn-info" href="delete/<!--record_web_id-->" onclick="return confirm('是否确认删除？')">删除</a>
                    </div>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
$(function(){
    global.root_fix += 'setting/subweb/';
});
</script>

