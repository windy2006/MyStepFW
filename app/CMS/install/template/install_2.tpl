<DIV id="main_frm" class="border rounded p-4 mb-5">
	<H2 class="mb-4">步骤二 ： 环境设置</H2>
	<h4 class="title">检测服务器环境以及设置基本网站参数</h4>
	<form method="post" action="CMS/install/3" onsubmit="return myChecker(this)">
		<table class="table table-bordered table-striped">
			<tbody>
			<!--loop:start key="setting"-->
			<!--setting_content-->
			<!--loop:end-->
			</tbody>
			<tfoot class="position-fixed bg-white border-top">
			<tr>
				<td colspan="2" class="p-3 border-0 text-center">
					<button class="btn btn-info btn-sm mr-3" type="submit"> 提 交 </button>
					<button class="btn btn-info btn-sm" type="reset"> 复 位 </button>
				</td>
			</tr>
			</tfoot>
		</table>
	</form>
</DIV>

<script type="application/javascript">
    function myChecker(theForm) {
        if ($('#db_password').val() == $('#db_password_r').val()){
            return checkForm(theForm);
        }else{
            alert('两次输入的密码不一致！');
            if($('#db_password').val() != $('#db_password_r').val()) {
                highlightIt($('#db_password').get(0));
            }
            return false;
        }
    }
    function setPosition() {
        $("#main_frm").css('padding-bottom', 200);
        $("tfoot").find("td").width($(window).width());
        $("tfoot").css({'right':0, 'bottom':40});
        $("tfoot").width($(window).width());
        $("tfoot").height($(window).width()>530?60:80);
    }
    $(window).resize(setPosition);
    $(function(){
        $('[data-toggle="tooltip"]').tooltip();
        setPosition();
    });
</script>