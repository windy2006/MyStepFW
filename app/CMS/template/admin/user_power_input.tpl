<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">权限名称</span>
                </div>
                <input name="id" type="hidden" value="<!--id-->" />
                <input name="name" class="form-control" type="text" maxlength="20" value="<!--name-->" required />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">权限索引</span>
                </div>
                <input name="idx_org" type="hidden" value="<!--idx-->" />
                <input name="idx" class="form-control" type="text" value="<!--idx-->" need="alpha" />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">默 认 值</span>
                </div>
                <input name="value" class="form-control" type="text" value="<!--value-->" need="" />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">要求格式</span>
                </div>
                <input name="format_org" type="hidden" value="<!--format-->" />
                <select class="custom-select" name="format" onchange="$('input[name=value]').attr('need', this.value)">
                    <option value="">任意字符串</option>
                    <!--loop:start key="format"-->
                    <option value="<!--format_key-->" <!--format_select-->><!--format_value--></option>
                    <!--loop:end-->
                </select>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">权限描述</span>
                </div>
                <input name="comment" class="form-control" type="text" value="<!--comment-->" need="" />
            </div>
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
    global.root_fix += 'user/power/';
});
</script>
