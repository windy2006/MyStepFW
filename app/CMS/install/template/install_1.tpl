<DIV class="border rounded p-4">
	<H2 class="mb-4">步骤一 ： 环境检查</H2>
	<h4 class="title">环境以及文件目录权限检查</h4>
	<div id="err_info" class="my-4 text-center text-danger">由于未能完全通过检测，当前插件有可能无法正确安装，请根据检测信息提示修正相关问题后，点击“复查”按钮！</div>
	<table class="table table-bordered table-striped">
		<tr>
			<th>项目</th>
			<th>所需配置</th>
			<th>最佳配置</th>
			<th>当前服务器</th>
		</tr>
		<tr>
			<td>操作系统</td>
			<td>不限制</td>
			<td>类Unix</td>
			<td><?=PHP_OS?></td>
		</tr>
		<tr>
			<td>PHP 版本</td>
			<td>7.0</td>
			<td>7.3</td>
<?php
	$ver = phpversion();
	$sign = version_compare($ver,'7.0','>') ? "text-success" : "text-danger";
	if($sign=="text-danger") $ver .= '<span id="error"></span>';
?>
			<td class="<?=$sign?>"><?=$ver?></td>
		</tr>
		<tr>
			<td>附件上传</td>
			<td>不限制</td>
			<td>2M</td>
			<td class="text-success"><?=ini_get('upload_max_filesize')?></td>
		</tr>
		<tr>
			<td>GD 库</td>
			<td>1.0</td>
			<td>2.0</td>
<?php
	$ver = "";
	if(function_exists("gd_info")) {
		$ver = gd_info();
		$ver = preg_replace("/^.+?([\d\.]+).+?$/", "\\1", $ver['GD Version']);
	}
	$sign = version_compare($ver,'2.0','>') ? "text-success" : "text-danger";
	if($sign=="text-danger") $ver .= '<span id="error"></span>';
?>
			<td class="<?=$sign?>"><?=$ver?></td>
		</tr>
	</table>

	<h4 class="title">目录、文件权限检查</h4>
	<table class="table table-bordered table-striped">
		<tr>
			<th>目录文件</th>
			<th>所需状态</th>
			<th>当前状态</th>
		</tr>
<?php
$theList = array(
	'cache',
	'static',
);

foreach($theList as $cur) {
    echo "<tr>\n";
	echo "<td>{$cur}</td><td>可写</td>";
	if(is_writeable(ROOT."/".$cur)) {
		echo '<td class="text-success">可写</td>';
	} else {
		echo '<td class="text-danger">不可写<span id="error"></span></td>';
	}
	echo "\n</tr>\n";
}
?>
	</table>

	<h4 class="title">函数依赖性检查</h4>
	<table class="table table-bordered table-striped">
		<tr>
			<th>函数名称</th>
			<th>建议</th>
			<th>检查结果</th>
		</tr>
<?php
$theList = array(
	"mysqli_connect",
	"fsockopen",
	"file_get_contents",
	"xml_parser_create",
	"json_encode",
	"iconv",
);

foreach($theList as $cur) {
	echo "<tr>\n";
	echo "<td>{$cur}</td>";
	echo "<td>支持</td>\n";
	if(function_exists($cur)) {
		echo '<td class="text-success">支持</td>';
	} else {
		echo '<td class="text-danger">不支持<span id="error"></span></td>';
	}
	echo "</tr>\n";
}
?>
	</table>
</DIV>
<DIV class="text-right mt-3 mb-5">
	<FORM method="post" action="CMS/install/2">
		<button class="btn btn-info" type="button" onclick="location.href='CMS/install/0'"">上一步</button>&emsp;
		<button class="btn btn-info" id="install" type="submit">下一步</button>
		<button class="btn btn-info" id="refresh" type="button" onclick="location.reload()">复 查</button>
	</FORM>
</DIV>

<script type="application/javascript">
$(function(){
	if($("#error").length==0) {
		$("#err_info").hide();
		$("#refresh").hide();
		$("#install").show();
	} else {
		$("#err_info").show();
		$("#refresh").show();
		$("#install").hide();
	}
});
</script>