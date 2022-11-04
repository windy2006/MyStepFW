<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-list-alt"></span> <!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive">
        <form method="post" class="form-inline">
        <table class="table table-striped table-hover m-0 text-center">
            <tr>
                <th>应用</th>
                <th>jQuery</th>
                <th>BootStrap</th>
                <th>操作</th>
            </tr>
            <tr id="selector">
                <td>
                    <select id="app" class="form-control w-100">
                        <option value="">请选择</option>
<!--loop:start key="app"-->
                        <option value="<!--app_app-->"><!--app_name--></option>
<!--loop:end-->
                    </select>
                </td>
                <td>
                    <select id="jQuery" class="form-control w-100">
<!--loop:start key="jq"-->
                        <option value="<!--jq_ver-->"><!--jq_ver--></option>
<!--loop:end-->
                    </select>
                </td>
                <td>
                    <select id="BootStrap" class="form-control w-100">
<!--loop:start key="bs"-->
                        <option value="<!--bs_ver-->"><!--bs_ver--></option>
<!--loop:end-->
                    </select>
                </td>
                <td width="80">
                    <button id="btn_add" class="btn btn-primary" type="button">添加</button>
                </td>
            </tr>
            <tr class="d-none">
                <td colspan="4">
                    <textarea id="detail" name="detail" style="width:100%; height:auto;"><!--detail--></textarea>
                </td>
            </tr>
            <tr>
                <td align="center" colspan="4">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="button" onclick="history.go(0)"> 复 位 </button>
                </td>
            </tr>
        </table>
        </form>
    </div>
</div>
<script type="text/javascript">
$("#btn_add").click(function (){
    let app = $("#app").val();
    if(app==='') {
        alert("未选择应用！");
        $("#app").focus();
        return;
    }
    let jq = $("#jQuery").val();
    let bs = $("#BootStrap").val();
    setNew(app, jq, bs);
    $("#app option:selected").prop('selected', false).hide();
    toDetail();
});
function setNew(app, jq, bs) {
    let obj = $("<tr>").append('<td>'+app+'</td>').append('<td>'+jq+'</td>').append('<td>'+bs+'</td>');
    let btn = $("<button>").attr("type", "button").attr("idx", app).addClass("btn btn-primary").text("删除").click(function (){
        $("#app option[value="+this.getAttribute("idx")+"]").show();
        $(this).parentsUntil("tr").parent().remove();
        toDetail();
    });
    let input = $("<input>").attr("name","rule").attr("type","hidden").val(app+":"+jq+":"+bs);
    $("<td>").append(btn).append(input).appendTo(obj);
    obj.insertAfter("#selector");
}
function toDetail() {
    let result = {};
    $("input[name=rule]").each(function(){
        let list = this.value.split(":");
        result[list[0]] = {jq:list[1],bs:list[2]}
    });
    $("#detail").val(JSON.stringify(result));
    c(JSON.stringify(result));
}
$(function(){
    $("#jQuery option:last").prop("selected", true);
    $("#BootStrap option:last").prev().prop("selected", true);
    let detail = JSON.parse('<!--detail-->');
    for(let x in detail) {
        $("#app option[value="+x+"]").hide();
        setNew(x, detail[x].jq, detail[x].bs);
    }
});
</script>