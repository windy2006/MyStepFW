<div class="card border-bottom-0 bg-transparent pt-3 mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a id="upload" class="btn btn-primary btn-sm float-right py-0 border" href="add&idx=<!--tpl_idx-->">添加模板</a>
    </div>
    <div class="form-inline mt-5 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select name="tpl" onchange="location.href=global.root_fix+'list&idx='+this.value">
                <!--loop:start key="tpl_list"-->
                <option value="<!--tpl_list_idx-->" <!--tpl_list_selected-->><!--tpl_list_idx--></option>
                <!--loop:end-->
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center">
                <th>模板文件</th>
                <th width="80">文件大小</th>
                <th width="80">文件属性</th>
                <th width="160">修改时间</th>
                <th width="92">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="file"-->
            <tr>
                <td><!--file_name--></td>
                <td><!--file_size--></td>
                <td><!--file_attr--></td>
                <td><!--file_time--></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" href="edit&idx=<!--tpl_idx-->&file=<!--file_name-->">修改</a>
                        <a class="btn btn-sm btn-info" href="delete&idx=<!--tpl_idx-->&file=<!--file_name-->" onclick="return confirm('是否确定删除当前模板文件？')">删除</a>
                    </div>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
            <tfoot>
            <tr>
                <td class="text-center p-3 border-0" colspan="5">
                    <button class="btn btn-primary btn-sm mr-3" type="button" onclick="location.href=global.root_fix"> 返 回 </button>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<script type="text/javascript">
$(function(){
    global.root_fix += 'function/template/';
});
</script>