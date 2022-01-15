<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add/?web_id=<!--web_id-->">添加链接</a>
    </div>
    <div class="form-inline mt-5 pt-3 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select class="custom-select" name="web_id">
                <option value="">全部网站</option>
                <!--loop:start key="website"-->
                <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                <!--loop:end-->
            </select>
            <select class="custom-select" name="idx">
                <option value="">全部索引</option>
                <!--loop:start key="idx"-->
                <option value="<!--idx_idx-->" <!--idx_selected-->><!--idx_idx--></option>
                <!--loop:end-->
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered table-hover my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th><a class="text-dark" href="?order_type=<!--order_type-->&idx=<!--idx-->&web_id=<!--web_id-->">编号</a></th>
                <th><a class="text-dark" href="?order_type=<!--order_type-->&order=web_id&idx=<!--idx-->&web_id=<!--web_id-->">所属网站</a></th>
                <th><a class="text-dark" href="?order_type=<!--order_type-->&order=idx&idx=<!--idx-->&web_id=<!--web_id-->">链接索引</a></th>
                <th><a class="text-dark" href="?order_type=<!--order_type-->&order=name&idx=<!--idx-->&web_id=<!--web_id-->">链接名称</a></th>
                <th><a class="text-dark" href="?order_type=<!--order_type-->&order=image&idx=<!--idx-->&web_id=<!--web_id-->">链接图形</a></th>
                <th><a class="text-dark" href="?order_type=<!--order_type-->&order=level&idx=<!--idx-->&web_id=<!--web_id-->">显示级别</a></th>
                <th width="92">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="20"-->
            <tr class="text-center">
                <td><!--record_id--></td>
                <td><!--record_web_id--></td>
                <td><!--record_idx--></td>
                <td><a href="<!--record_url-->" target="_blank"><!--record_name--></a></td>
                <td><!--record_image--></td>
                <td><!--record_level--></td>
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
    <nav>
        <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
    </nav>
</div>
<script type="application/javascript">
$(function(){
    let web_id = '<!--web_id_site-->';
    if(web_id !== '1') {
        $('select[name=web_id]').val(web_id).hide();
    }
    $('select[name=web_id]').change(function(){
        location.href=global.root_fix+'?web_id='+this.value;
    });
    $('select[name=idx]').change(function(){
        location.href=global.root_fix+'?idx='+this.value;
    });
    global.root_fix += 'function/link/';
});
</script>
