<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
		<a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加新权限</a>
	</div>
	<div class="card-body p-0 table-responsive mt-5">
		<table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
						col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
			<thead class="thead-light">
			<tr class="text-center">
				<th width="50">编号</th>
				<th>权限索引</th>
				<th>权限名称</th>
				<th>默认值</th>
				<th>要求格式</th>
				<th>权限描述</th>
				<th width="92">相关操作</th>
			</tr>
			</thead>
			<tbody>
			<!--loop:start key="record" time="10"-->
			<tr align="center">
				<td><!--record_id--></td>
				<td><!--record_idx--></td>
				<td><!--record_name--></td>
				<td><!--record_value--></td>
				<td><!--record_format--></td>
				<td><!--record_comment--></td>
				<td class="align-middle">
					<div class="btn-group">
						<a class="btn btn-sm btn-info" href="edit/<!--record_id-->">修改</a>
						<a class="btn btn-sm btn-info" href="delete/<!--record_id-->" onclick="return confirm('是否确认删除？')">删除</a>
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
	global.root_fix += 'user/power/';
});
</script>
