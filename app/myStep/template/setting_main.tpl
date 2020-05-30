<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-wrench"></span> 框架基本参数设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post" onsubmit="return checkForm(this)">
            <table class="table table-sm table-striped table-hover m-0">
                <thead>
                <tr>
                    <td colspan="2" class="text-center">
                        <div class="row">
                            <div class='col-6'>
                                <b>项目选取：</b>
                                <select class="custom-select custom-select-sm is-valid my-1 w-50" onchange="setSection(this.value)">
                                    <option value="">显示全部设置项目</option>
<!--loop:start key="item"-->
                                    <option value="<!--item_idx-->"><!--item_name--></option>
<!--loop:end-->
                                </select>
                            </div><div class='col-6'>
                                <b>应用选取：</b>
                                <select class="custom-select custom-select-sm is-valid my-1 w-50" onchange="location.href=setting.url_prefix+'manager/setting/'+this.value">
                                    <option value="">主框架设置</option>
<!--loop:start key="app"-->
                                    <option value="<!--app_name-->" <!--app_selected-->><!--app_name--></option>
<!--loop:end-->
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
<!--loop:start key="setting"-->
<!--setting_content-->
<!--loop:end-->
                </tbody>
                <tfoot class="position-fixed bg-white border-top">
                <tr class="float-right">
                    <td colspan="2" class="p-3 border-0">
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
function setSection(sect) {
    if(sect=='') {
        $('tbody[id]').show(1000);
    } else {
        $('tbody[id]').hide();
        $('#'+sect).show(1000);
    }
    resizeMain();
}
function setPosition() {
    $("#main").css('padding-bottom', 70);
    $("tfoot").css({'right':0, 'bottom':40});
    $("tfoot").width($("#main").width()+20);
    $("tfoot").height($(window).width()>530?60:80);
}
$(window).resize(setPosition);
$(setPosition);
</script>