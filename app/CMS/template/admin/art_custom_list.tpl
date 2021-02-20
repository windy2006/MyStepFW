<div class="card border-bottom-0 bg-transparent mb-5" style="padding-top:50px;">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add/?web_id=<!--web_id-->">添加内容</a>
    </div>
    <div class="form-inline mx-auto">
        <select class="form-control" name="web_id">
            <option value="0">全部</option>
            <!--loop:start key="website"-->
            <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
            <!--loop:end-->
        </select>
    </div>
    <div class="card-body p-0 table-responsive col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2 p-0">
        <table class="table table-sm table-striped table-bordered table-hover font-sm my-md-3 bg-white">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th width="40">编号</th>
                <th width="250">所属网站</th>
                <th>索引</th>
                <th>内容</th>
                <th width="92">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="15"-->
            <tr>
                <td class="align-middle text-center"><!--record_id--></td>
                <td class="align-middle"><!--record_web_id--></td>
                <td class="align-middle"><!--record_idx--></td>
                <td><!--record_content--></td>
                <td class="align-middle">
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" href="edit/<!--record_id-->">修改</a>
                        <a class="btn btn-sm btn-info" href="delete/<!--record_id-->" onclick="return confirm('是否确认删除？')">删除</a>
                    </div>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
</div>
<script type="application/javascript">
$(function() {
    let web_id = '<!--web_id_site-->';
    if(web_id !== '1') {
        $('select[name=web_id]').val(web_id).parent().hide();
    }
    $('select[name=web_id]').change(function () {
        location.href = global.root_fix+'?web_id=' + this.value;
    });
    global.root_fix += 'article/custom/';
});
</script>