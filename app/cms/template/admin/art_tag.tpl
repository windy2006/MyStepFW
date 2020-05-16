<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
		<a class="btn btn-primary btn-sm float-right py-0 border" href="rebuild/?web_id=<!--web_id-->">重新整理</a>
	</div>
	<div class="form-inline mt-5 pt-3 mx-auto">
		<div class="input-group mb-2 mb-md-0 mx-auto px-2">
			<select class="input-group-prepend custom-select" name="web_id">
				<!--loop:start key="website"-->
				<option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
				<!--loop:end-->
			</select>
			<input type="text" name="keyword" class="form-control" value="<!--keyword-->" placeholder="请输入查询关键字" />
			<div class="input-group-append">
				<button class="btn btn-light btn-outline-secondary" name="keyword" type="button">检索</button>
			</div>
		</div>
	</div>
	<div class="card-body p-0 table-responsive">
		<table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
						col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
			<thead class="thead-light">
			<tr class="text-center">
				<th><a class="text-dark" href="?keyword=<!--keyword-->&order_type=<!--order_type-->&web_id=<!--web_id-->">编号</a></th>
				<th><a class="text-dark" href="?keyword=<!--keyword-->&order=tag&order_type=<!--order_type-->&web_id=<!--web_id-->">关键字</a></th>
				<th><a class="text-dark" href="?keyword=<!--keyword-->&order=count&order_type=<!--order_type-->&web_id=<!--web_id-->">出现次数</a></th>
				<th class="d-none d-md-table-cell"><a class="text-dark" href="?keyword=<!--keyword-->&order=click&order_type=<!--order_type-->&web_id=<!--web_id-->">点击次数</a></th>
				<th class="d-none d-md-table-cell"><a class="text-dark" href="?keyword=<!--keyword-->&order=add_date&order_type=<!--order_type-->&web_id=<!--web_id-->">发表时间</a></th>
				<th><a class="text-dark" href="?keyword=<!--keyword-->&order=update_date&order_type=<!--order_type-->&web_id=<!--web_id-->">更新时间</a></th>
				<th>相关操作</th>
			</tr>
			</thead>
			<tbody>
<!--loop:start key="record" time="20"-->
			<tr align="center">
				<td><!--record_id--></td>
				<td><!--record_tag--></td>
				<td><!--record_count--></td>
				<td class="d-none d-md-table-cell"><!--record_click--></td>
				<td class="d-none d-md-table-cell"><!--record_add_date--></td>
				<td><!--record_update_date--></td>
				<td><a class="btn btn-sm btn-info" href="delete?id=<!--record_id-->&web_id=<!--web_id-->">删除</a></td>
			</tr>
<!--loop:end-->
			</tbody>
		</table>
	</div>
	<nav>
		<ul class="pagination pagination-sm justify-content-center">
			<li class="page-item"><a class="page-link" href="<!--link_first-->">首页</a></li>
			<li class="page-item"><a class="page-link" href="<!--link_prev-->">上页</a></li>
			<li class="page-item"><a class="page-link" href="<!--link_next-->">下页</a></li>
			<li class="page-item"><a class="page-link" href="<!--link_last-->">末页</a></li>
			<li>
				<div class="input-group input-group-sm mb-2 mr-sm-2">
					<input type="text" class="form-control" name="jump" size="2" value="<!--page_current-->" style="text-align:center" />
					<div class="input-group-append">
						<button class="btn btn-light btn-outline-secondary" name="jump" type="button">跳页</button>
					</div>
				</div>
			</li>
			<li class="pl-4 pt-1"> 【 共 <!--page_count--> 页，  <!--record_count--> 条记录 】</li>
		</ul>
	</nav>
</div>
<script type="application/javascript">
	$(function(){
		$('select[name=web_id]').change(function(){
			location.href=global.root_fix+'?web_id='+this.value;
		});
		$('input[name=keyword]').keypress(function(e){
			if(e.keyCode==13) {
				location.href=global.root_fix+'?web_id=<!--web_id-->&keyword='+this.value;
			}
		});
		$('button[name=keyword]').click(function(e){
			location.href=global.root_fix+'?web_id=<!--web_id-->&keyword='+$('input[name=keyword]').val();
		});
		$('input[name=jump]').keypress(function(e){
			if(e.keyCode==13) {
				location.href=global.root_fix+'?web_id=<!--web_id-->&keyword=<!--keyword-->&order=<!--order-->&order_type=<!--order_type_org-->&page='+this.value;
			}
		});
		$('button[name=jump]').click(function(e){
			location.href=global.root_fix+'?web_id=<!--web_id-->&keyword=<!--keyword-->&order=<!--order-->&order_type=<!--order_type_org-->&page='+$('input[name=jump]').val();
		});
		global.root_fix += 'article/tag/';
	});
</script>