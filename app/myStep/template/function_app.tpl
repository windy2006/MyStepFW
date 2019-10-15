<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 应用设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
<!--loop:start key="app"-->
            <table class="table table-sm table-striped table-hover mb-4 border-bottom">
                <tr>
                    <td width="100"><b>应用名称：</b></td>
                    <td>
                        <!--app_name--> <!--app_app--> （V<!--app_ver-->）
                        <a href="manager/setting/<!--app_app-->">【设置】</a>
                        <a href="manager/function/language?app=<!--app_app-->">【语言】</a>
                        <a href="#" data-toggle="modal" data-target="#plugin" data-app="<!--app_app-->">【插件】</a>
                        <input type="hidden" name="plugin[]" id="<!--app_app-->" value="<!--app_plugin-->" />
                        <input type="hidden" name="name[]" value="<!--app_app-->" />
                    </td>
                </tr>
                <tr>
                    <td><b>应用简介：</b></td>
                    <td>
                        <!--app_intro-->
                    </td>
                </tr>
                <tr>
                    <td><b>应用路由：</b></td>
                    <td>
                        <textarea name="route[]" class="form-control" rows="5"><!--app_route--></textarea>
                    </td>
                </tr>
            </table>
<!--loop:end-->
            <div id="footer" class="position-fixed bg-white border-top text-right py-3" style="z-index: 5;">
                <button class="btn btn-primary btn-sm mr-3" type="submit"> 重新应用所有路由信息 </button>
                <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="plugin" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">请选择本应用可调用的插件</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!--loop:start key="plugin"-->
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="plugin_<!--plugin_idx-->" value="<!--plugin_idx-->" name="plugin" />
                    <label class="custom-control-label" for="plugin_<!--plugin_idx-->"><!--plugin_name--> （<!--plugin_intro-->）</label>
                </div>
                <!--loop:end-->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"> 关闭 </button>
                <button type="submit" class="btn btn-primary"> 确认 </button>
            </div>
        </div>
    </div>
</div>
<script language="JavaScript">
$('#plugin').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var app = button.data('app');
    var modal = $(this);
    var plugin_list = $('#'+app).val().split(',');
    modal.find('input[type="checkbox"]').prop('checked', false);
    for(var x in plugin_list) {
        modal.find('input[value="'+plugin_list[x]+'"]').prop('checked', true);
    }
    modal.find('button[type="submit"]').unbind().click(function(){
        var checked = modal.find('input[type="checkbox"]:checked');
        var result = [];
        for(var i=0,m=checked.length;i<m;i++) {
            result.push(checked[i].value);
        }
        $('#'+app).val(result);
        modal.modal('hide');
    });
})


$(function() {
    $.setCSS('vendor/codemirror/lib/codemirror.css');
    $.setJS([
        "vendor/codemirror/lib/codemirror.js",
        "vendor/codemirror/mode/php/php.js",
        "vendor/codemirror/addon/edit/matchbrackets.js",
        "vendor/codemirror/addon/selection/active-line.js",
        "vendor/codemirror/mode/xml/xml.js",
        "vendor/codemirror/mode/htmlmixed/htmlmixed.js",
        "vendor/codemirror/mode/clike/clike.js"
    ],function(){
        $('textarea').each(function() {
            var editor = CodeMirror.fromTextArea(this, {
                mode: "application/x-httpd-php",
                indentUnit: 4,
                indentWithTabs: true,
                matchBrackets: true,    //括号匹配
                styleActiveLine: true,  //高亮当前行
                lineNumbers: true,
                lineWrapping: true
            });
            editor.setSize('auto', 'auto');
        });
    });
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
</script>