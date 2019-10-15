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
                </select>
            </div>
            <table class="table table-sm table-striped table-hover mb-4 border-bottom">
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
                <span class="font-weight-bold">
                添加语言包：
                <input name="lng_new_idx" type="text" size="10" maxlength="200" value="" />
                （会依据现有设置生成新的语言包，仅仅修改时，请留空！）
                </span>
                <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
            </div>
        </form>
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
</script>