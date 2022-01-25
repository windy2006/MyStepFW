<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 框架更新</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover m-0">
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
                    <a href="javascript:" id="upload"> 点击上传 </a>
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

<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 应用更新</b>
    </div>
    <div class="card-body p-0 table-responsive" style="overflow-y:hidden;">
        <table class="table table-striped table-hover m-0">
            <thead class="thead-light">
                <tr align="center">
                    <th>应用名称</th>
                    <th width="100">本地版本</th>
                    <th width="100">服务器版本</th>
                    <th width="150">操作</th>
                </tr>
            </thead>
            <tbody>
            <!--loop:start key='app'-->
                <tr title="<!--app_intro-->" align="center">
                    <td align="left"><!--app_name--></td>
                    <td><!--app_ver--></td>
                    <td><!--app_remote--></td>
                    <td class="btn-group">
                        <a class="btn btn-sm btn-info" href="/<!--path_admin-->/setting/<!--app_app-->">设置</a>
                        <a class="btn btn-sm btn-info" href="<!--app_link-->" target="">更新</a>
                        <a class="btn btn-sm btn-info" href="manager/pack/app/<!--app_app-->" target="">打包</a>
                    </td>
                </tr>
            <!--loop:end-->
            <tbody>
        </table>
    </div>
</div>

<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 插件更新</b>
    </div>
    <div class="card-body p-0 table-responsive" style="overflow-y:hidden;">
        <table class="table table-striped table-hover m-0">
            <thead class="thead-light">
                <tr align="center">
                    <th>插件名称</th>
                    <th width="100">本地版本</th>
                    <th width="100">服务器版本</th>
                    <th width="150">操作</th>
                </tr>
            </thead>
            <tbody>
            <!--loop:start key='plugin'-->
                <tr title="<!--plugin_intro-->" align="center">
                    <td align="left"><!--plugin_name--></td>
                    <td><!--plugin_ver--></td>
                    <td><!--plugin_remote--></td>
                    <td class="btn-group">
                        <a class="btn btn-sm btn-info" href="/<!--path_admin-->/function/plugin/setting/?idx=<!--plugin_idx-->">设置</a>
                        <a class="btn btn-sm btn-info" href="<!--plugin_link-->" target="">更新</a>
                        <a class="btn btn-sm btn-info" href="manager/pack/plugin/<!--plugin_idx-->" target="">打包</a>
                    </td>
                </tr>
            <!--loop:end-->
            <tbody>
        </table>
    </div>
</div>


<script type="text/javascript">
jQuery.vendor('jquery.jmpopups', {add_css:true});
jQuery.vendor('jquery.powerUpload', {
    callback:function(){
        $('#upload').powerUpload({
            title: '请选择需要上传的升级文件',
            mode: 'browse',
            maxfiles: 1,
            maxfilesize: 8,
            errors: ["浏览器不支持", "一次只能上传1个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
            url: '<!--url_prefix-->manager/upload',
            uploadFinished:function(i,file,result,timeDiff){
                if(result.error!=0) {
                    alert("上传失败！\n原因：" + result.message);
                } else {
                    $('#uploader').find(".modal-title > b").html("更新完成，请关闭本对话框！");
                    $('#uploader').on('hidden.bs.modal', function (e) {
                        window.location.reload();
                    });
                }
            }
        });
    }
});
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
    $.get("<!--url_prefix-->manager/build?"+(new Date()).getTime(), function(info){
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
function checkModify(mode) {
    loadingShow("正在检测系统文件的变更情况，请等待！");
    let url = "<!--url_prefix-->manager/check_" + (mode==1?'server':'local');
    $.get(url, function(info){
        console.log(info);
        if(info==false || typeof(info.error)!="undefined") {
            alert(mode==0?"校验失败，请确认校验信息是否已成功建立！":"服务器连接失败，或不存在本网站对应字符集的校验信息");
            loadingShow();
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
        if(result.length==0) {
            alert("未发现改变的文件！");
        } else {
            alert(result, true);
        }
    }, "json").fail(function (e) {
        console.log(e);
        alert('校验失败！');
    });
}
$('#link_3').click(function(){
    $.get("<!--url_prefix-->manager/empty", function(info){
        if(info.length==0) {
            alert("更新数据已清空！");
        } else {
            alert(info);
        }
    }).fail(function (e) {
        console.log(e);
        alert('操作失败！');
    });
});
$('#link_4').click(function(){
    window.open("manager/export");
});
$('#link_5').click(function(){
    loadingShow("正在打包框架系统，完成后将自动开始下载！");
    $.get("pack/", function(url) {
        console.log(url);
        loadingShow();
        location.replace(url);
    }, 'text').fail(function (e) {
        console.log(e);
        alert('文件打包失败！');
    });
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
    $.get("<!--url_prefix-->manager/download?m="+mode, function(info){
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
    }, "json").fail(function (e) {
        console.log(e);
        alert('操作失败！');
    });
}
$(function(){
    $.get("<!--url_prefix-->manager/check/version?"+Math.random(), function(info){
        if(info.version=='') return;
        if(info.version.length>0) {
            $('<a href="javascript:" onclick="checkUpdate()">点击更新（v'+info.version+'）</a>').appendTo("#info");
        }
        update_info = info.detail;
    }, "json").fail(function (e) {
        console.log(e);
        alert('操作失败！');
    });
});
</script>