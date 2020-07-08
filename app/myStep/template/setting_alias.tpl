<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-wrench"></span> 类别名设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
            <table class="table table-sm table-striped table-hover mb-2 border-bottom" data-toggle="tooltip" data-placement="top" title="可在代码中直接通过别名调用对应类">
                <tr class="bg-info text-center font-weight-bold">
                    <td width="100">类名</td>
                    <td>调用别名</td>
                    <td width="100">类名</td>
                    <td>调用别名</td>
                </tr>
                <tr>
<!--loop:start key="class"-->
                    <td class="text-right align-middle"><a href="<!--path_admin-->/setting/detail/<!--class_name-->"><!--class_name--></a></td>
                    <td><input type="text" class="form-control" name="<!--class_name-->" value="<!--class_alias-->" /></td>
                    <!--class_tr-->
<!--loop:end-->
                    <!--dummy-->
                </tr>
            </table>
            <div class="p-3 border-0 text-right">
                <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                <button class="btn btn-primary btn-sm" type="reset" onclick="history.go(-1)"> 返 回 </button>
            </div>
        </form>
    </div>
</div>