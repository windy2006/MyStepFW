<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 语言设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
            <div class="my-3 font-weight-bold pl-3">
                语种选择：
                <select name="type" onchange="goto(this.value)">
<!--loop:start key="type"-->
                    <option value="<!--type_name-->" <!--type_selected-->><!--type_name--></option>
<!--loop:end-->
                </select> &nbsp; | &nbsp;
                <button class="btn btn-info btn-sm mr-3" type="button" data-toggle="modal" data-target="#item"> 添加语言项 </button>
                <span class="font-weight-bold nowrap">
                （设定为新的语言包：
                <input name="lng_new_idx" class="small" type="text" size="10" maxlength="20" value="" />
                生成所填写索引的语言包，如仅修改，请留空！）
                </span>
                &nbsp; &nbsp;
            </div>
            <table id="item_list" class="table table-sm table-striped table-hover mb-4 border-bottom">
                <thead>
                <tr class="font-weight-bold bg-secondary text-white">
                    <td width="40">序号</td>
                    <td width="180">语言索引</td>
                    <td>显示文字</td>
                    <td width="60">操作</td>
                </tr>
                </thead>
<!--loop:start key="item"-->
                <tr>
                    <td><!--item_idx--></td>
                    <td><!--item_key--></td>
                    <td>
                        <input class="w-100" name="language[<!--item_key-->]" type="text" value="<!--item_value-->" />
                    </td>
                    <td class="text-center">
                        <input class="btn-default" type="button" onclick="del(this)" style="width:50px;" value="删除" />
                    </td>
                </tr>
<!--loop:end-->
            </table>
            <div id="footer" class="position-fixed bg-white border-top text-right py-3" style="z-index: 5;">
                <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                <button class="btn btn-primary btn-sm mr-3" type="button" onclick="history.go(-1)"> 返 回 </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="item" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">语言项添加</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">项目索引</span>
                    </div>
                    <input type="text" class="form-control" name="idx" value="" />
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">对应表述</span>
                    </div>
                    <input type="text" class="form-control" name="lng" value="" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"> 关闭 </button>
                <button type="submit" class="btn btn-primary"> 确认 </button>
            </div>
        </div>
    </div>
</div>

<script language="JavaScript">
$(function() {
    setPosition();
    $(window).resize(setPosition);
    $('form').on('reset', function(){
       location.reload();
    });
});
function setPosition() {
    $("#main").css('padding-bottom', 70);
    $("#footer").css({'right':0, 'bottom':10});
    $("#footer").width($("#main").width()+20);
    $("#footer").height($(window).width()>530?60:80);
}
function goto(type) {
    var url = location.href.replace(/&type\=\w+/, '');
    location.href = url + '&type=' + type;
}
function del(obj) {
    $(obj).parent().parent().remove();
}
$('#item').on('show.bs.modal', function (event) {
    var modal = $(this);
    modal.find('input').val('');
    modal.find('button[type="submit"]').unbind().click(function(){
        var idx = modal.find('input[name="idx"]').val();
        var lng = modal.find('input[name="lng"]').val();
        var obj = $('input[name="language['+idx+']"]');
        if(obj.length>0) {
            if(confirm("索引 " + idx + " 已存在，表述内容为：" + obj.val() + "\n\n是否需要替换为：" + lng)) {
                obj.val(lng);
            }
        } else {
            obj = $('#item_list').find('tr:last');
            var no = parseInt(obj.find('td:first').text()) + 1;
            $('<tr>\n' +
              '    <td>'+no+'</td>\n' +
              '    <td>'+idx+'</td>\n' +
              '    <td>\n' +
              '        <input class="w-100" name="language['+idx+']" type="text" value="'+lng+'" />\n' +
              '    </td>\n' +
              '    <td class="text-center">\n' +
              '        <input class="btn-default" type="button" onclick="del(this)" style="width:50px;" value="删除" />\n' +
              '    </td>\n' +
              '</tr>').insertAfter(obj);
        }
        modal.modal('hide');
    });
})
</script>