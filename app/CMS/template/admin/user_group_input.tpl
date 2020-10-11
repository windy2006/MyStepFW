<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">群组名称</span>
                </div>
                <input name="group_id" type="hidden" value="<!--group_id-->" />
                <input name="name" class="form-control" type="text" maxlength="20" value="<!--name-->" required />
            </div>
            <!--loop:start key="user_power"-->
            <div class="input-group mb-2" title="<!--user_power_comment-->">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name"><!--user_power_name--></span>
                </div>
                <input name="<!--user_power_idx-->" class="form-control" type="text" value="<!--user_power_value-->" need="<!--user_power_format-->" />
            </div>
            <!--loop:end-->
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
        global.root_fix += 'user/group/';
    });
</script>
