<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 插件安装 - “<!--name-->”</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <form method="post" class="form-inline" onsubmit="return checkForm(this)">
        <table class="table table-striped table-hover m-0">
            <tr>
                <td class="text-center"><!--info--></td>
            </tr>
            <tr>
                <td style="line-height:22px;">
                    <b>插件名称：</b><!--name--><br />
                    <b>插件索引：</b><!--idx--><br />
                    <b>插件板本：</b><!--ver--><br />
                    <b>功能描述：</b><!--intro--><br />
                    <b>版权信息：</b><!--copyright--><br />
                    <b>检测信息：</b>
                    <div style="margin-top:-22px;margin-left:70px;"><!--check--></div>
                    <b>插件简介：</b>
                    <div style="margin-top:-22px;margin-left:70px;"><!--description--></div>
                </td>
            </tr>
            <tr>
                <td style="line-height:22px;">
                    <!--setting-->
                </td>
            </tr>
            <tr>
                <td style="line-height:22px;">
                    <div class="form-group mb-2" data-toggle="tooltip" data-placement="top" title="选择应用插件的APP">
                        <label class="mr-3" style="min-width:100px;">生效应用：</label>
                        <!--loop:start key="app"-->
                        <label class="mr-3">
                            <input class="mr-1" type="checkbox" class="form-control" name="apps[]" value="<!--app_app-->" <!--app_checked--> /> <!--app_name-->
                        </label>
                        <!--loop:end-->
                    </div>
                </td>
            </tr>
            <tr>
                <td align="center">
                    <input type="hidden" value="<!--idx-->" name="idx" />
                    <button class="btn btn-primary btn-sm mr-3 <!--btn_install-->" type="submit"> 安 装 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="button" onclick="location.reload()"> 复 查 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="button" onClick="history.go(-1)" > 返 回 </button>
                </td>
            </tr>
        </table>
        </form>
    </div>
</div>
<script type="text/javascript">
$('form').submit(function() {
    let theObjs = $(this).find("input:password[id$=_r]");
    for(let i=0; i<theObjs.length; i++) {
        if(document.getElementById(theObjs[i].id.replace(/_r$/, "")).value!=theObjs[i].value) {
            alert("两次输入密码请保持一致！");
            document.getElementById(theObjs[i].id.replace(/_r$/, "")).focus();
            return false;
        }
    }
    theObjs.remove();
    return true;
});
function setSize() {
    if($(window).width()>576) {
        $('textarea,select,input[type=text],input[type=password]').each(function(){
            $(this).width($(this).parent().width()-140);
        });
    } else {
        $('textarea,select,input[type=text],input[type=password]').css('width','100%');
    }
}
$(function(){
    $('textarea').each(function(){
        $(this).width(this.scrollWidth);
        $(this).height(this.scrollHeight);
    });
    $(window).resize(setSize);
    setTimeout(setSize, 500);
});
</script>