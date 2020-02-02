<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 框架更新</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover m-0 font-sm">
            <tr>
                <td style="width:120px;">当前框架版本</td>
                <td>
                    <!--version-->
                    <span id="info">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <td>更新地址</td>
                <td><!--link--></td>
            </tr>
            <tr>
                <td>文件校验</td>
                <td>
                    <a href="javascript:" id="link_1">更新本地校验</a>
                    |
                    <a href="javascript:" id="link_2">检查文件改动</a>
                </td>
            </tr>
            <tr>
                <td>上传更新</td>
                <td>
                    <a href="javascript:" data-toggle="modal" data-target="#upload"> 点击上传 </a>
                </td>
            </tr>
            <tr>
                <td>其他功能</td>
                <td>
                    <a href="javascript:" id="link_3">清空升级信息</a>
                    |
                    <a href="javascript:" id="link_4">导出升级信息</a>
                </td>
            </tr>
            <tr>
                <td>框架打包</td>
                <td>
                    <a href="javascript:" id="link_5">一键打包当前框架所有文件</a>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="modal fade" id="upload" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form_upload" name="upload" method="post" ACTION="update/upload" ENCTYPE="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="glyphicon glyphicon-upload"></span> 请选择需要上传的升级文件</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">更新</span>
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="plugin" id="customFile" required />
                            <label class="custom-file-label" for="customFile">文件选取</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<!--max_size-->" />
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>  关闭 </button>
                    <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-open"></span>  上传 </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script language="JavaScript" type="text/javascript">
jQuery.vendor('jquery.jmpopups', {add_css:true});
$('#link_1').click(function(){
    confirm(
        '更新校验信息会造成自动升级时将已改动文件错误覆盖！\n\n是否继续？',
        'buildVerify',
        ['确 定','取 消'],
        '更新本地校验'
    );
});
function buildVerify(mode) {
    if(mode==1) return;
    loadingShow("正在更新系统文件校验信息，请等待！");
    $.get("update/build?"+(new Date()).getTime(), function(info){
        loadingShow();
        if(info.length==0) {
            alert("已成功更新当前系统文件校验信息！");
        } else {
            alert("校验信息更新失败！");
        }
    });
}
$('#link_2').click(function(){
    confirm(
        '请选择校验方式：\n\n本机校验：通过本地生成的校验信息校验网站文件\n\n网络校验：通过更新服务器上的校验文件校验',
        'checkModify',
        ['本机校验','网络校验'],
        '文件校验'
    );
});
$('#form_upload').submit(function(){
    loadingShow('文件上传并更新中……');
});
function checkModify(mode) {
    loadingShow("正在检测系统文件的变更情况，请等待！");
    let url = "update/check_" + (mode==1?'server':'local');
    $.get(url, function(info){
        if(info==false || info.code!='0') {
            if(mode==0) {
                alert("校验失败，请确认校验信息是否已成功建立！");
            } else {
                alert("服务器连接失败，或不存在本网站对应字符集的校验信息");
            }
            return;
        }
        loadingShow('框架文件校验中……');
        let result = "";
        if(info['new']!=null) {
            result += "<b>发现 " + info['new'].length + " 个新增文件：</b>\n";
            result += info['new'].join("\n");
            result += "\n&nbsp;\n";
        }
        if(info['mod']!=null) {
            result += "<b>发现 " + info['mod'].length + " 个文件发生改变：</b>\n";
            result += info['mod'].join("\n");
            result += "\n&nbsp;\n";
        }
        if(info['miss']!=null) {
            result += "<b>发现 " + info['miss'].length + " 个文件被删除：</b>\n";
            result += info['miss'].join("\n");
            result += "\n&nbsp;\n";
        }
        console.log(result);
        if(result.length==0) {
            alert("未发现改变的文件！");
        } else {
            alert(result, true);
        }
    }, "json");
}
$('#link_3').click(function(){
    $.get("update/empty", function(info){
        if(info.length==0) {
            alert("更新数据已清空！");
        } else {
            alert(info);
        }
    });
});
$('#link_4').click(function(){
    window.open("update/export");
});
$('#link_5').click(function(){
    window.open("pack/");
});
let update_info = null;
function checkUpdate() {
    if(update_info==null) {
        alert("系统当前版本已为最新，无需更新！");
        return;
    }
    let result = "";
    result += '\
<div class="font-weight-bold text-center"  style="font-size:16px;">\
	更新详情\
</div>\
';
    try {
        for(let ver in update_info) {
            result += '\
<div class="mb-3">\
	<div class="font-weight-bold border-bottom">Version: '+ver+'</div>\
	<div>'+update_info[ver].replace(/[\r\n]+/g, "<br />")+'</div>\
</div>\
';
        }
        confirm(result, "applyUpdate", [" 下载更新 ", " 自动更新 "], "系统更新", false);
    } catch(e) {
        alert("获取更新服务器信息有误，请刷新重试！");
    }
}
function applyUpdate(mode) {
    loadingShow("系统正在获取更新，请耐心等待！");
    $.get("update/download?m="+mode, function(info){
        loadingShow();
        try {
            alert_org(info.info);
            if(info.link.length>2) {
                window.open(info.link);
            } else {
                window.top.location.reload();
            }
        } catch(e) {
            alert("更新获取失败，请检查相关设置！");
        }
    }, "json");
}
$(function(){
    $.get("update/check/version?"+Math.random(), function(info){
        if(info==null) return;
        if(info.version.length>0) {
            $('<a href="javascript:" onclick="checkUpdate()">点击更新（v'+info.version+'）</a>').appendTo("#info");
        }
        update_info = info.detail;
    }, "json");
});
</script>