<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-header bg-info text-white">
		<b><span class="glyphicon glyphicon-cog"></span> 插件设置 - “<!--name-->”</b>
	</div>
	<div class="card-body p-0 table-responsive">
        <form method="post" class="form-inline">
		<table class="table table-striped table-hover m-0 font-sm">
            <tr>
                <td style="line-height:22px;">
                    <!--setting-->
                </td>
            </tr>
			<tr>
				<td align="center">
					<input type="hidden" value="<!--idx-->" name="idx" />
					<button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
					<button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
					<button class="btn btn-primary btn-sm mr-3" type="button" onClick="history.go(-1)" > 返 回 </button>
				</td>
			</tr>
		</table>
        </form>
	</div>
</div>
<script language="JavaScript" type="text/javascript">
$('form').submit(function() {
    var theObjs = $(this).find("input:password[id$=_r]");
    for(var i=0; i<theObjs.length; i++) {
        if(document.getElementById(theObjs[i].id.replace(/_r$/, "")).value!=theObjs[i].value) {
            alert("两次输入密码请保持一致！");
            document.getElementById(theObjs[i].id.replace(/_r$/, "")).focus();
            return false;
        }
    }
    theObjs.remove();
    return true;
});
</script>