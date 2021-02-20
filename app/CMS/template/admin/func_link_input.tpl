<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
            <div class="input-group mb-2" title="当前内容所属网站">
                <div class="input-group-prepend">
                    <input type="hidden" name="id" value="<!--id-->" />
                    <span class="input-group-text item-name">所属网站</span>
                </div>
                <select name="web_id" class="custom-select">
                    <option value="0">未限定</option>
                    <!--loop:start key="website"-->
                    <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                    <!--loop:end-->
                </select>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">链接名称</span>
                </div>
                <input name="name" class="form-control" type="text" maxlength="40" value="<!--name-->" required />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">链接地址</span>
                </div>
                <input name="url" class="form-control" type="text" maxlength="100" value="<!--url-->" need="url" />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">链接索引</span>
                </div>
                <input id="idx" name="idx" class="form-control" type="text" maxlength="20" value="<!--idx-->" required />
                <select class="custom-select" onchange="$id('idx').value=this.value">
                    <option value="">请选择</option>
                    <!--loop:start key="idx"-->
                    <option value="<!--idx_idx-->" <!--idx_selected-->><!--idx_idx--></option>
                    <!--loop:end-->
                </select>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">链接图示</span>
                </div>
                <input name="image" class="form-control" type="text" maxlength="100" value="<!--image-->" />
                <div class="input-group-append" id="button-addon4">
                    <button id="upload" class="btn btn-light btn-outline-secondary" type="button" data-title="请选择需要上传的图示文件">上传</button>
                </div>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">显示级别</span>
                </div>
                <div class="form-control pt-0">
                    <input name="level" type="range" class="form-control-range custom-range mt-2" min="0" max="9" value="<!--level-->" need="digital" />
                </div>
            </div>
            <div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
                <div class="float-right p-2 border-0">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
                    <button class="btn btn-primary btn-sm" type="button" onClick="location.href='<!--back_url-->'"> 返 回 </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
$(function(){
    jQuery.vendor('jquery.powerUpload', {
        callback:function(){
            $('#upload').powerUpload({
                url: '<!--url_prefix-->api/myStep/upload',
                title: '请选择需要上传的图示文件',
                mode: 'browse',
                max_files: 1,
                max_file_size: 8,
                errors: ["浏览器不支持", "一次只能上传1个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
                uploadFinished:function(i,file,result,timeDiff){
                    if(result.error!=0) {
                        alert("上传失败！\n原因：" + result.message);
                    } else {
                        $('#uploader').find(".modal-title > b").html("上传完成，请关闭本对话框！");
                        $("input[name=image]").val('<!--url_prefix-->api/myStep/download/'+result.new_name.split('.').slice(0,2).join('.'));
                        $('#uploader').unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
                            $("input[name=image]").select();
                        });
                    }
                }
            });
        }
    });
    let web_id = '<!--web_id_site-->';
    if(web_id !== '1') {
        $('select[name=web_id]').val(web_id).parent().hide();
    }
    global.root_fix += 'function/link/';
});
</script>