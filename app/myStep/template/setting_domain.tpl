<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-wrench"></span> 域名绑定设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
            <table class="table table-sm table-striped table-hover m-0">
                <thead>
                <tr class="font-weight-bold bg-secondary text-white">
                    <th width="200">域名</th>
                    <th>规则</th>
                    <th width="60">操作</th>
                </tr>
                </thead>
                <tbody id="rule_list">
                <tr id="tpl" class="d-none">
                    <td style="vertical-align: middle;font-size:14px;">
                        <input type="text" class="form-control" name="domain[]" value="" placeholder="域名" />
                    </td>
                    <td style="vertical-align: middle">
                        <select class="form-control" name="rule[]">
                            <option value="">请选择对应规则</option>
                        </select>
                    </td>
                    <td style="vertical-align: middle">
                        <button class="btn btn-primary" type="button">删除</button>
                    </td>
                </tr>
<!--loop:start key="setting"-->
<!--setting_content-->
<!--loop:end-->
                </tbody>
                <tfoot id="tfoot">
                <tr class="float-right">
                    <td colspan="2" class="p-3 border-0">
                        <button class="btn btn-primary btn-sm mr-3" type="button" func="add"> 添 加 </button>
                        <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                        <button class="btn btn-primary btn-sm" type="reset"> 复 位 </button>
                    </td>
                </tr>
                </tfoot>
            </table>
        </form>
    </div>
</div>

<script type="application/javascript">
let list = <!--list-->;
let rule_list = <!--rule_list-->;
$(function(){
    let obj = null;
    for(let i=0,m=list.length;i<m;i++) {
        $('<option>').val(list[i]['rule']).text(list[i]['idx']+" - "+list[i]['rule']).appendTo($('#tpl').find('select'));
    }
    for(let x in rule_list) {
        if(typeof rule_list[x]!=='string') continue;
        obj = $('#tpl').clone().attr('id', null).removeClass('d-none').appendTo('#rule_list');
        obj.find('input').val(x);
        obj.find('select').val(rule_list[x]);
        obj.find('button').click(function(){
            $(this).parentsUntil('tr').parent().remove();
        });
    }
    $('#rule_list').find('button').click(function(){
        let obj = $(this).parentsUntil('tr').parent();
        $(this).parentsUntil('tr').parent().remove();
        global.alert_leave = true;
    });
    $('button[func=add]').click(function(){
        $('#tpl').clone().attr('id', null).removeClass('d-none').appendTo('#rule_list')
            .find('button').click(function(){
                $(this).parentsUntil('tr').parent().remove();
            });
        global.alert_leave = true;
    });
})
</script>