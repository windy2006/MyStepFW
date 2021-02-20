<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a id="upload" class="btn btn-primary btn-sm float-right py-0 border" href="javascript:">上传模板</a>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 col-xl-6 offset-xl-3 p-0" method="post" action="set">
        <table class="table table-sm table-bordered border-0 table-hover font-sm my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th colspan="2">子站模板设置</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="website"-->
            <tr>
                <td><!--website_name--></td>
                <td>
                    <input name="idx[]" type="hidden" value="<!--website_idx-->" />
                    <select name="tpl[]" class="website" tpl="<!--website_tpl-->"></select>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
            <tfoot>
            <tr>
                <td class="text-center" colspan="2">
                    <button class="btn btn-primary btn-sm mr-3" type="Submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm" type="reset"> 重 置 </button>
                </td>
            </tr>
            </tfoot>
        </table>
        </form>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 mx-5 gallery">
            <!--loop:start key="tpl_list"-->
            <div class="item col mb-4">
                <div class="card text-center border-0" title="<!--tpl_list_idx-->">
                    <h5 class="card-title"><!--tpl_list_idx--></h5>
                    <img src="<!--tpl_list_img-->" class="card-img-top mb-2" alt="<!--tpl_list_idx-->">
                    <div class="card-body p-0">
                        <div class="btn-group">
                            <a class="btn btn-sm btn-info" href="list&idx=<!--tpl_list_idx-->">编辑</a>
                            <a class="btn btn-sm btn-info" href="export&idx=<!--tpl_list_idx-->">导出</a>
                            <a class="btn btn-sm btn-info" href="remove&idx=<!--tpl_list_idx-->" onclick="return confirm('是否确定删除当前模板？')">删除</a>
                        </div>
                    </div>
                </div>
            </div>
            <!--loop:end-->
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
    let tpl_list = <!--tpl_list-->;
    for(let i=0,m=tpl_list.length;i<m;i++) {
        if(tpl_list[i]==='admin' || tpl_list[i]==='custom') continue;
        $(".website").each(function(){
            let tpl = $(this).attr("tpl");
            let opt = $("<option>").attr("value", tpl_list[i]).text(tpl_list[i]);
            if(tpl==tpl_list[i]) opt.attr("selected", true);
            $(this).append(opt);
        });
    }
    global.root_fix += 'function/template/';
    jQuery.vendor('jquery.powerUpload', {
        callback:function(){
            $('#upload').powerUpload({
                url: global.root_fix + 'upload',
                title: '如果存在同名模板，现有模板将被覆盖，请注意确认！',
                mode: 'browse',
                maxfiles: 1,
                maxfilesize: 8,
                errors: ["浏览器不支持", "一次只能上传1个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
                uploadFinished:function(i,file,result,timeDiff){
                    if(result.error!=0) {
                        alert("上传失败！\n原因：" + result.message);
                    } else {
                        $('#uploader').find(".modal-title > b").html("上传完成，请关闭本对话框！");
                        $('#uploader').on('hidden.bs.modal', function (e) {
                            window.location.reload();
                        });
                    }
                }
            });
        }
    });
});
</script>