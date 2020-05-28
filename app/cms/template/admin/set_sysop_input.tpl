<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
	</div>
	<div class="card-body p-0 table-responsive mt-5">
		<form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this)">
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">管理组群</span>
				</div>
				<select name="group_id" class="custom-select">
					<!--loop:start key="sys_group"-->
					<option value="<!--sys_group_group_id-->" <!--sys_group_selected-->><!--sys_group_name--></option>
					<!--loop:end-->
				</select>
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">用户名称</span>
				</div>
				<input name="id" type="hidden" value="<!--id-->" />
				<input name="username_org" type="hidden" maxlength="20" value="<!--username-->" />
				<input name="username" type="text" class="form-control" len="4-16" maxlength="20" value="<!--username-->" required />
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">用户密码</span>
				</div>
				<input name="password" type="password" id="password" class="form-control" maxlength="20" value="" />
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">密码确认</span>
				</div>
				<input name="password_r" type="password" id="password_r" class="form-control" maxlength="20" value="" />
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">电子邮件</span>
				</div>
				<input name="email" type="email" maxlength="50" class="form-control"value="<!--email-->" required />
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
	$(function(){
		global.root_fix += 'setting/sysop/';
	});
</script>