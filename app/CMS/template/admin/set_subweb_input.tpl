<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">网站索引</span>
                </div>
                <input name="web_id" type="hidden" value="<!--web_id-->" />
                <input name="idx" class="form-control" placeholder="网站内部索引，只能为英文或数字" type="text" maxlength="80" value="<!--idx-->" need="word" />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">网站域名</span>
                </div>
                <input name="domain" class="form-control" placeholder="多个域名请用半角逗号间隔" type="text" maxlength="80" value="<!--domain-->" />
            </div>
            <div class="input-group mb-2">
                <h5>子站参数设置：</h5>
            </div>
            <table class="table table-sm table-striped table-hover table-bordered m-0 bg-white">
                <tbody>
                <!--loop:start key="setting"-->
                <!--setting_content-->
                <!--loop:end-->
                </tbody>
            </table>
            <div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
                <div class="float-right p-2 border-0">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
                    <button class="btn btn-primary btn-sm" type="button"  onClick="location.href='<!--back_url-->'"> 返 回 </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
$(function(){
    global.root_fix += 'setting/subweb/';
});
</script>