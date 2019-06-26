<?php
$check_pass = '<font color="green"><b>√</b></font>';
$check_fail = '<font color="red"><b>×</b></font>';
?>
<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-header bg-info text-white">
		<b><span class="glyphicon glyphicon-info-sign"></span> PHP 基本信息</b>
	</div>
	<div class="card-body p-0 table-responsive">
		<table class="table table-striped table-hover m-0 font-sm">
			<tr>
				<td width="140">PHP版本</td>
				<td><?=PHP_VERSION;?></td>
			</tr>
			<tr>
				<td>PHP运行方式</td>
				<td><?=strtoupper(php_sapi_name())?></td>
			</tr>
			<tr>
				<td>Zend引擎版本</td>
				<td><?=zend_version()?></td>
			</tr>
			<tr>
				<td>自动定义全局变量</td>
				<td><?=get_cfg_var("register_globals") ? 'ON' : 'OFF';?></td>
			</tr>
			<tr>
				<td>运行于安全模式</td>
				<td><?=get_cfg_var("safe_mode") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>显示错误信息</td>
				<td><?=get_cfg_var("display_errors") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>动态加载连接库支持</td>
				<td><?=get_cfg_var("enable_dl") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>使用URL打开文件</td>
				<td><?=get_cfg_var("allow_url_fopen") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>允许使用的最大内存量</td>
				<td><?=get_cfg_var("memory_limit")?></td>
			</tr>
			<tr>
				<td>POST最大字节数</td>
				<td><?=get_cfg_var("post_max_size")?></td>
			</tr>
			<tr>
				<td>允许最大上传文件</td>
				<td><?=get_cfg_var("file_uploads") ? get_cfg_var("upload_max_filesize") : $check_fail;?></td>
			</tr>
			<tr>
				<td>程序超时限制</td>
				<td><?=get_cfg_var("max_execution_time")."秒";?></td>
			</tr>
			<tr>
				<td>被禁用的函数</td>
				<td><?=get_cfg_var("disable_functions") ? get_cfg_var("disable_functions") : "没有";?></td>
			</tr>
			<tr>
				<td>标记&lt;% %&gt;支持</td>
				<td><?=get_cfg_var("asp_tags") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>COOKIE支持</td>
				<td><?=(isset($HTTP_COOKIE_VARS) || isset($_COOKIE)) ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>浮点运算有效数字显示位数</td>
				<td><?=get_cfg_var("precision")?></td>
			</tr>
			<tr>
				<td>强制y2k兼容</td>
				<td><?=get_cfg_var("y2k_compliance") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>调试器地址</td>
				<td><?=get_cfg_var("debugger.host") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>调试器端口</td>
				<td><?=get_cfg_var("debugger.port") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>SMTP支持</td>
				<td><?=get_cfg_var("SMTP") ? $check_pass : $check_fail;?></td>
			</tr>
			<tr>
				<td>SMTP地址</td>
				<td><?=get_cfg_var("SMTP")?></td>
			</tr>
			<tr>
				<td>Html错误显示</td>
				<td><?=get_cfg_var("html_errors") ? $check_pass : $check_fail;?></td>
			</tr>
		</table>
	</div>
</div>