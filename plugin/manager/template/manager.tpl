<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 框架更新</b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="<!--plugin_setting_url|html-->">设置</a>
    </div>
    <div class="card-body p-0 table-responsive">
        <div id="manager-feedback" class="alert m-3 d-none" role="alert" aria-live="polite"></div>
        <table class="table table-striped table-hover m-0">
            <tr>
                <td style="width:120px;">当前框架版本</td>
                <td>
                    <!--version-->
                    <span id="info">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <td>上传更新</td>
                <td>
                    <a href="javascript:" id="upload"> 点击上传 </a><b>（请上传通过更新功能下载的压缩包）</b>
                </td>
            </tr>
            <tr>
                <td>更新地址</td>
                <td><!--link|html--><b>（如果更新来源不确定或者DNS被劫持，可能会造成不可预测的问题！）</b></td>
            </tr>
            <tr>
                <td>文件校验</td>
                <td>
                    <a href="javascript:" id="link_1">更新本地校验</a>
                    |
                    <a href="javascript:" id="link_2">检查文件改动</a>
                    |
                    <a href="javascript:" id="link_6">服务器端差异文件下载</a>
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
                <tr title="<!--app_intro|html-->" align="center" data-manager-kind="app" data-manager-id="<!--app_app|html-->">
                    <td align="left"><!--app_name--></td>
                    <td><!--app_ver--></td>
                    <td class="manager-remote-version"><!--app_remote_display|html--></td>
                    <td class="btn-group">
                        <a class="btn btn-sm btn-info" href="<!--app_setting_url|html-->">设置</a>
                        <a class="btn btn-sm btn-info manager-update <!--app_update_class-->" href="<!--app_link|html-->" target="">更新</a>
                        <a class="btn btn-sm btn-info" href="<!--url_prefix-->manager/pack/app/<!--app_app-->" target="">打包</a>
                    </td>
                </tr>
            <!--loop:end-->
            </tbody>
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
                <tr title="<!--plugin_intro|html-->" align="center" data-manager-kind="plugin" data-manager-id="<!--plugin_idx|html-->">
                    <td align="left"><!--plugin_name--></td>
                    <td><!--plugin_ver--></td>
                    <td class="manager-remote-version"><!--plugin_remote_display|html--></td>
                    <td class="btn-group">
                        <a class="btn btn-sm btn-info" href="<!--plugin_setting_url|html-->">设置</a>
                        <a class="btn btn-sm btn-info manager-update <!--plugin_update_class-->" href="<!--plugin_link|html-->" target="">更新</a>
                        <a class="btn btn-sm btn-info" href="<!--url_prefix-->manager/pack/plugin/<!--plugin_idx-->" target="">打包</a>
                    </td>
                </tr>
            <!--loop:end-->
            </tbody>
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
                showUpdateFeedback(result.message || (result.error?'上传更新失败':'更新完成'),!!result.error);
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
        //console.log(info);
        if(info==false || typeof(info.error)!="undefined") {
            alert(mode==0?"校验失败，请确认校验信息是否已成功建立！":"服务器连接失败！");
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
        loadingShow();
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
        loadingShow();
        alert('操作失败！');
    });
});
$('#link_4').click(function(){
    window.open("<!--url_prefix-->manager/export");
});
$('#link_5').click(function(){
    loadingShow("正在打包框架系统，完成后将自动开始下载！");
    $.get("<!--url_prefix-->pack/", function(url) {
        //console.log(url);
        loadingShow();
        location.replace(url);
    }, 'text').fail(function (e) {
        console.log(e);
        loadingShow();
        alert('文件打包失败！');
    });
});
$('#link_6').click(function(){
    if(!hasUpdateServer()) return;
    loadingShow("正在确认差异文件……");
    $.get("<!--url_prefix-->manager/check_server", function(info){
        if(info==false || typeof(info.error)!="undefined") {
            alert("服务器校验失败！");
            loadingShow();
            return;
        }
        let list = [];
        if(info['mod']!=null) {
            list.push.apply(list,info['mod']);
        }
        if(info['miss']!=null) {
            list.push.apply(list,info['miss']);
        }
        if(list.length==0) {
            loadingShow();
            alert("未发现改变的文件！");
        } else {
            loadingShow('获取差异文件……');
            $.post("<!--update_url-->/api/plugin_manager/difference", {'data':JSON.stringify(list)}, function(info){
                if(info==false || typeof(info.error)!="undefined") {
                    alert("服务器校验失败 或 文件无需更新！");
                    loadingShow();
                    return;
                }
                try {
                    loadingShow();
                    if(info.link.length>2) {
                        alert("文件正在下载。。。");
                        location.href = new URL(info.link, "<!--update_url-->/").href;
                    }
                } catch(e) {
                    alert("文件获取失败，请检查相关设置！");
                }
            }, "json").fail(function (e) {
                console.log(e);
                loadingShow();
                alert('差异文件失败！');
            });
        }
        
    }, "json").fail(function (e) {
        console.log(e);
        loadingShow();
        alert('服务器校验失败！');
    });
});
let update_info = null;
function showUpdateFeedback(message,error) {
    $('#manager-feedback').text(String(message || '')).removeClass('d-none alert-danger alert-success').addClass(error?'alert-danger':'alert-success');
}
function checkUpdate() {
    if(update_info==null) {
        alert("系统当前版本已为最新，无需更新！");
        return;
    }
    let result = "";
    const escapeUpdate = value => $('<div>').text(String(value)).html();
    result += '\
<div class="font-weight-bold text-center"  style="font-size:16px;">\
    更新详情\
</div>\
';
    try {
        for(let ver in update_info) {
            result += '\
<div class="mb-3">\
    <div class="font-weight-bold border-bottom">Version: '+escapeUpdate(ver)+'</div>\
    <div>'+escapeUpdate(update_info[ver]).replace(/[\r\n]+/g, "<br />")+'</div>\
</div>\
';
        }
        confirm(result, "applyUpdate", [" 下载更新 ", " 自动更新 "], "系统更新", false);
    } catch(e) {
        alert("获取更新服务器信息有误，请刷新重试！");
    }
}
function applyUpdate(mode) {
    if(!hasUpdateServer()) return;
    loadingShow("系统正在获取更新，请耐心等待！");
    $.get("<!--url_prefix-->manager/download?m="+mode, function(info){
        loadingShow();
        try {
            showUpdateFeedback(info.info,!!info.error);
            alert_org(info.info);
            if(info.error) return;
            if(info.link.length>2) {
                location.href = info.link;
            } else {
                window.top.location.reload();
            }
        } catch(e) {
            alert("更新获取失败，请检查相关设置！");
        }
    }, "json").fail(function (e) {
        console.log(e);
        loadingShow();
        showUpdateFeedback('更新请求失败，请检查服务器响应后重试。',true);
        alert('操作失败！');
    });
}
// 按服务端组件标识刷新版本列；更新资格由 PHP 比较，避免浏览器重新实现版本规则。
function refreshComponentVersions(info, unavailable) {
    $('[data-manager-kind]').each(function(){
        const row = $(this), kind = row.attr('data-manager-kind'), id = row.attr('data-manager-id');
        const versions = info && info[kind];
        const version = versions && Object.prototype.hasOwnProperty.call(versions,id) ? versions[id] : null;
        row.find('.manager-remote-version').text(typeof version === 'string' && version !== '' ? version : (unavailable || '未提供'));
        const updates = info && info.updates && info.updates[kind];
        row.find('.manager-update').toggleClass('d-none',!(typeof version === 'string' && version !== '' && updates && updates[id] === true));
    });
}
function hasUpdateServer() {
    if("<!--update_url-->" !== '') return true;
    showUpdateFeedback('请先在 manager 插件设置中填写更新网址；离线上传更新仍可使用。',true);
    return false;
}
$(function(){
    if("<!--update_url-->" === '') {
        refreshComponentVersions(null,'未配置');
        $('#info').text('未设置更新网址，仅支持离线更新');
        return;
    }
    $.get("<!--url_prefix-->manager/check/version?"+Math.random(), function(info){
        if(info.error || typeof info.version!=='string') {
            refreshComponentVersions(null,'检查失败');
            $('#info').text('版本检查失败，请检查更新服务器连接');
            return;
        }
        refreshComponentVersions(info);
        if(info.version.length>0) {
            $('<a href="javascript:" onclick="checkUpdate()"></a>').text('点击更新（v'+info.version+'）').appendTo("#info");
            update_info = info.detail;
        } else if(info.server_version && info.server_compare > 0) {
            $('#info').text('服务器版本 v'+info.server_version+'，尚无可用升级包');
        } else if(info.server_version && info.server_compare < 0) {
            $('#info').text('本地版本高于服务器（v'+info.server_version+'）');
        } else {
            $('#info').text('已是最新版本'+(info.server_version?'（服务器 v'+info.server_version+'）':''));
        }
    }, "json").fail(function (e) {
        console.log(e);
        refreshComponentVersions(null,'检查失败');
        $('#info').text('版本检查失败，请检查更新服务器连接');
    });
});
</script>
