<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
		<a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加新用户组</a>
	</div>
	<div class="card-body p-0 table-responsive mt-5">
		<table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
						col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
			<thead class="thead-light">
			<tr class="text-center">
				<th width="50">编号</th>
				<th width="80">类型名称</th>
				<!--loop:start key="user_power"-->
				<th><!--user_power_name--></th>
				<!--loop:end-->
				<th width="92">相关操作</th>
			</tr>
			</thead>
			<tbody>
			<!--loop:start key="record"-->
			<tr align="center">
				<td><!--record_group_id--></td>
				<td><!--record_name--></td>
				<!--record_user_power-->
				<td class="align-middle">
					<div class="btn-group">
						<a class="btn btn-sm btn-info" href="edit/<!--record_group_id-->">修改</a>
						<a class="btn btn-sm btn-info" href="delete/<!--record_group_id-->" onclick="return confirm('是否确认删除？')">删除</a>
					</div>
				</td>
			</tr>
			<!--loop:end-->
			</tbody>
		</table>
	</div>
</div>
<script type="application/javascript">
	$(function(){
		global.root_fix += 'user/group/';
	});
</script>
