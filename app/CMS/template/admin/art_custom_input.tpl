<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 px-0 py-2" method="post" action="<!--method-->_ok">
            <div class="input-group mb-2" title="当前内容所属网站">
                <div class="input-group-prepend">
                    <input type="hidden" name="id" value="<!--record_id-->" />
                    <span class="input-group-text item-name">所属网站</span>
                </div>
                <select name="web_id" onchange="setCata()" class="custom-select">
                    <option value="0">未限定</option>
                    <!--loop:start key="website"-->
                    <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                    <!--loop:end-->
                </select>
            </div>
            <div class="input-group mb-2" title="用于在模版中通过此索引调用对应内容">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">内容索引</span>
                </div>
                <input name="idx" class="form-control" type="text" value="<!--record_idx-->" maxlength="100" required />
            </div>
            <div class="input-group mb-2">
                <textarea id="content" name="content" class="form-control" style="height:300px;"><!--record_content--></textarea>
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
<script type="application/javascript" src="vendor/tinymce/tinymce.min.js"></script>
<script type="application/javascript" src="app/CMS/asset/admin/tinymce_init.js"></script>
<script type="text/javascript">
let setting_tinymce_css = '/asset/<!--tpl_style-->/style.css';
$(function(){
    let web_id = '<!--web_id_site-->';
    if(web_id !== '1') {
        $('select[name=web_id]').val(web_id).parent().hide();
    }
    global.root_fix += 'article/custom/';
});
</script>