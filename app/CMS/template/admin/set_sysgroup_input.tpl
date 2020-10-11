<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">组群名称</span>
                </div>
                <input name="group_id" type="hidden" value="<!--group_id-->" />
                <input name="name" type="text" class="form-control" len="4-16" maxlength="20" value="<!--name-->" required />
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">管理权限</span>
                </div>
                <div class="form-control h-auto">
                    <div id="power_func">
                        <label><input type="checkbox" id="power_func_all" name="power_func[]" value="all" <!--power_func_all_checked--> /> 全部权限</label> <br />
                        <!--loop:start key="power_func"-->
                        <label><input type="checkbox" id="power_func_<!--power_func_key-->" name="power_func[]" pid="<!--power_func_pid-->" value="<!--power_func_key-->" <!--power_func_checked--> /> <!--power_func_value--></label> <br />
                        <!--loop:end-->
                    </div>
                </div>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">网站权限</span>
                </div>
                <div class="form-control h-auto">
                    <div id="power_web">
                        <label><input type="checkbox" id="power_web_all" name="power_web[]" value="all" <!--power_web_all_checked--> /> 全部权限</label> <br />
                        <!--loop:start key="power_web"-->
                        <label><input type="checkbox" id="power_web_<!--power_web_web_id-->" name="power_web[]" value="<!--power_web_web_id-->" <!--power_web_checked--> /> <!--power_web_name--></label> <br />
                        <!--loop:end-->
                    </div>
                </div>
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
<script type="application/javascript">
$('#power_func_all').click(function(){
    checkAll('power_func');
});
$('input[pid]').click(function(){
    checkFunc($(this).val());
    let pid = $(this).attr('pid');
    if(pid==='0') {
        checkStatus('power_func');
        return;
    }
    let objs = $('input[pid='+pid+']');
    let flag = this.checked;
    for(let i=0,m=objs.length;i<m;i++) {
        if(objs[i].checked!==flag) {
            $id("power_func_"+pid).checked = true;
            $id("power_func_"+pid).indeterminate = true;
            flag = null;
            break;
        }
    }
    if(flag!==null) {
        $id("power_func_"+pid).indeterminate = false;
        $id("power_func_"+pid).checked = flag;
    }
    checkStatus('power_func');
});
$('#power_web_all').click(function(){
    checkAll('power_web');
});
$('input[name="power_web[]"]').click(function(){
    checkStatus('power_web');
});
function checkFunc(pid) {
    let objs = document.getElementsByName("power_func[]");
    let flag = $id("power_func_"+pid).checked;
    for(let i=0; i<objs.length; i++) {
        if(objs[i].getAttribute("pid")===pid) {
            objs[i].checked = flag;
        }
    }
}

function checkAll(checkSet) {
    let objs = document.getElementsByName(checkSet + "[]");
    let flag = $id(checkSet+"_all").checked;
    for(let i=0; i<objs.length; i++) {
        objs[i].checked = flag;
    }
}

function checkStatus(checkSet) {
    let objs = document.getElementsByName(checkSet + "[]");
    if(objs.length<2) return;
    let curStatus = objs[1].checked;
    let flag = curStatus?1:0;
    for(let i=1; i<objs.length; i++) {
        if(objs[i].checked===curStatus) continue;
        flag = 2;
        break;
    }
    if(flag===2) {
        $id(checkSet+"_all").checked = false;
        $id(checkSet+"_all").indeterminate = true;
    } else {
        $id(checkSet+"_all").checked = (flag===1);
        $id(checkSet+"_all").indeterminate = false;
    }
}

$(function(){
    $id('power_func_all').checked ? checkAll('power_func') : checkStatus('power_func');
    $id('power_web_all').checked ? checkAll('power_web', false) : checkStatus('power_web');
    global.root_fix += 'setting/sysgroup/';
});
</script>
