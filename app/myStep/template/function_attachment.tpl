<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 附件管理</b>
    </div>
    <!--if:start key='empty'-->
    <h1 id="info" class="text-center p-5">暂无附件上传</h1>
    <!--if:end-->
    <div type="show" class="form-inline mt-2 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <div class="input-group-prepend">
                <span class="input-group-text item-name">上传日期</span>
            </div>
            <select date class="custom-select" name="year" style="width:100px;">
                <!--loop:start key="years"-->
                <option value="<!--years_year-->" <!--years_selected-->><!--years_year-->年 </option>
                <!--loop:end-->
            </select>
            <select date class="custom-select" name="month">
                <!--loop:start key="months"-->
                <option value="<!--months_month-->" <!--months_selected-->><!--months_month-->月 </option>
                <!--loop:end-->
            </select>
        </div>
    </div>
    <div type="show" class="card-body p-0 mt-3">
        <table class="table table-sm table-striped table-bordered table-hover font-sm bg-white m-0">
            <thead class="thead-light">
            <tr>
                <th>文件名称</th>
                <th>文件大小</th>
                <th>上传日期</th>
                <th width="50">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="files"-->
            <tr>
                <td><a href="api/myStep/download/<!--files_idx-->"><!--files_name--></a></td>
                <td><!--files_size--></td>
                <td><!--files_date--></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" file_idx="<!--files_idx-->" href="javascript:">删除</a>
                    </div>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
</div>
<script type="application/javascript">
$(function(){
    if($('#info').length>0) $('[type=show]').hide();
    $('select[date]').change(function(){
        location.href = location.href.replace(location.search, '')
            +'?y='+$('select[name=year]').val()
            +'&m='+$('select[name=month]').val();
    });
    $('a[file_idx]').click(function(){
        if(!confirm('是否确认删除该附件？')) return;
        let idx = $(this).attr('file_idx');
        if(idx.indexOf('/')>0) {
            location.href = location.href = location.href.replace(location.search, '')+'?m=del&idx='+idx;
        } else {
            $.get('<!--url_prefix-->api/myStep/remove/'+idx, function(data, status){
                if(data.error==='0' && status==='success') {
                    alert('附件已删除！');
                    location.reload();
                } else {
                    alert(data.message);
                }
            }, 'json');
        }
    });
});
</script>