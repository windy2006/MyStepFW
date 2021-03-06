<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 错误日志</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <h4 class="text-center py-2"><!--err_msg--></h4>
        <table class="table table-striped table-hover m-0 font-sm border-bottom">
            <!--loop:start key="err"-->
            <tr>
                <td><!--err_content--></td>
            </tr>
            <!--loop:end-->
            <tfoot id="tfoot">
            <tr class="float-right">
                <td class="p-3 border-0">
                    <a class="btn btn-primary btn-sm <!--err_output-->" href="<!--path_admin-->/error?m=clean" /> 清空数据 </a> &nbsp;
                    <a class="btn btn-primary btn-sm <!--err_output-->" href="<!--path_admin-->/error?m=download" /> 保存数据 </a>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
    $.vendor('highlight',{callback:()=>highlight(2, 'Eclipse')});
</script>