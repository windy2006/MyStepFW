<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-wrench"></span> 类加载设置</b>
    </div>
    <div class="card-body p-0">
        <form id="theForm" method="post" onsubmit="return checkForm(this)">
            <!--loop:start key="class"-->
            <table class="table table-sm table-striped table-hover mb-2 border-bottom">
                <tr>
                    <td width="80">所在目录</td>
                    <td data-toggle="tooltip" data-placement="bottom" title="框架会自动读取此目录下面的类脚本"><input type="text" class="form-control" name="path[]" value="<!--class_path-->" need="" /></td>
                    <td rowspan="3" class="align-middle text-center" width="80">
                        <a class="btn btn-info" href="manager/setting/alias/<!--class_id-->">查看</a><br /><br />
                        <a class="btn btn-info" name="btn_del" href="#">删除</a>
                    </td>
                </tr>
                <tr data-toggle="tooltip" data-placement="bottom" title="自动载入的文件扩展名类型">
                    <td>载入类型</td>
                    <td><input type="text" class="form-control" name="ext[]" value="<!--class_ext-->" need="" /></td>
                </tr>
                <tr data-toggle="tooltip" data-placement="bottom" title="如果类名与文件名不一致，需要设置下索引补充，格式为：array('类名称'=>'所在文件')">
                    <td>特别指定</td>
                    <td>
                        <textarea class="form-control" name="idx[]" rows="3"><!--class_idx--></textarea>
                    </td>
                </tr>
            </table>
            <!--loop:end-->
            <div class="p-3 border-0 text-center">
                <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                <button id="btn" class="btn btn-primary btn-sm" type="button"> 添 加 </button>
            </div>
        </form>
    </div>
</div>

<table name="tpl" class="table table-sm table-striped table-hover mb-2 border-bottom" style="display:none;">
    <tr>
        <td width="80">所在目录</td>
        <td data-toggle="tooltip" data-placement="bottom" title="框架会自动加载此目录下面的类脚本"><input type="text" class="form-control" name="path[]" value="" need="" /></td>
        <td rowspan="3" class="align-middle text-center" width="80">
            <a class="btn btn-info" name="btn_del" href="">删除</a>
        </td>
    </tr>
    <tr data-toggle="tooltip" data-placement="bottom" title="自动载入的文件扩展名类型">
        <td>载入类型</td>
        <td><input type="text" class="form-control" name="ext[]" value=".php,.class.php" need="" /></td>
    </tr>
    <tr data-toggle="tooltip" data-placement="bottom" title="如果类名与文件名不一致，需要设置下索引补充">
        <td>特别指定</td>
        <td>
            <textarea class="form-control" name="idx[]" rows="3"></textarea>
        </td>
    </tr>
</table>
<script type="application/javascript">
$('#btn').click(function(){
    let item = $('table[name=tpl]').clone().attr('name', 'new').hide();
    item.insertAfter('#theForm > table:last').show(500);
    $('[data-toggle="tooltip"]').tooltip();
    setBtn();
});
$(function(){
    $('a[name=btn_del]:first').remove();
    setBtn();
});
function setBtn(){
    $('a[name=btn_del]').unbind('click').click(function(){
        $(this).parentsUntil('table').last().parent().hide(500, function(){
            $(this).remove();
        });
        return false;
    });
}
</script>
