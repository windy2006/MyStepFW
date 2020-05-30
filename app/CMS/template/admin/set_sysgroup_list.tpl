<div class="card border-bottom-0 bg-transparent">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
		<a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加新群组</a>
	</div>
	<div class="card-body p-0 table-responsive col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2 p-0 mt-5">
		<table class="table table-sm table-striped table-bordered table-hover font-sm my-md-3 bg-white">
			<thead class="thead-light">
			<tr class="text-center">
				<th width="40">编号</th>
				<th width="80">群组名称</th>
				<th>管理权限</th>
				<th>网站权限</th>
				<th width="92">操作</th>
			</tr>
			</thead>
			<tbody>
			<!--loop:start key="record" time="15"-->
			<tr align="center">
				<td><!--record_group_id--></td>
				<td><!--record_name--></td>
				<td><!--record_power_func--></td>
				<td><!--record_power_web--></td>
				<td>
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
		global.root_fix += 'setting/sysgroup/';
	});
</script>