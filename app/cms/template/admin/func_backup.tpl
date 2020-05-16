<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
	</div>
	<div class="card-body p-0 table-responsive mt-5">
		<form name="db_bak" class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 px-0 py-2" method="post" ENCTYPE="multipart/form-data">
			<div class="input-group mb-2">
				<h3><!--result--></h3>
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">数 据 表</span>
				</div>
				<select name="table" class="custom-select">
					<option value="all">全部数据表</option>
					<!--loop:start key="tbls"-->
					<option value="<!--tbls_name-->"><!--tbls_name--></option>
					<!--loop:end-->
				</select>
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">操作模式</span>
				</div>
				<select name="method" class="custom-select" need="">
					<option value="">请选择</option>
					<option value="import">导入</option>
					<option value="export">导出</option>
					<option value="repair">修复</option>
					<option value="optimize">优化</option>
				</select>
			</div>
			<div class="input-group mb-2" title="上传文件不要大于 <!--max_size-->B" data-placement="bottom">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">上传数据</span>
				</div>
				<input type="hidden" name="MAX_FILE_SIZE" value="<!--upload_max_filesize-->" />
				<div class="custom-file">
					<label><input type="file" class="custom-file-input" name="the_file" disabled />
					<span class="custom-file-label nowrap" data-browse="浏览">SQL文件选择（多个文件请用ZIP格式压缩）</span></label>
				</div>
			</div>
			<div class="mb-2">
				<!--op_info-->
			</div>
			<div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
				<div class="float-right p-2 border-0">
					<button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
					<button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
				</div>
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">
	$('form[name=db_bak]').submit(function(){
		if($('select[name=method]').val()==='import') {
			if(!confirm('重新导入数据将破坏已有的数据文件！ 是否继续？')) {
				return;
			}
		}
		if(checkForm(this)) {
			if($('select[name=method]').val()!=='export') {
				loadingShow("操作正在进行，请耐心等待！");
			}
			return true;
		} else {
			return false;
		}
	});
	$('select[name=method]').change(function(){
		let mode = this.value;
		if(mode==='export') {
			$('input[name=the_file]').prop('disabled', true);
			$('select[name=table]').prop('disabled', false);
		} else if(mode==='import') {
			$('input[name=the_file]').prop('disabled', false);
			$('select[name=table]').prop('disabled', true);
		} else if(mode==='repair' || mode==='optimize') {
			$('input[name=the_file]').prop('disabled', true);
			$('select[name=table]').prop('disabled', false);
		} else {
			$('input[name=the_file]').prop('disabled', true);
			$('select[name=table]').prop('disabled', false);
		}
	});
	$(function(){
		$('input[type=file]').change(function(){
			$(this).next().text(this.value.replace(/^.+[\/\\]([^\/\\]+)$/, '$1'));
		});
	});
</script>