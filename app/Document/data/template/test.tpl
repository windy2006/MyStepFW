<style type="text/css">
BODY {
	font-size: 12px;
	font-family: Tahoma, Verdana, Arial, Sans-Serif;
	color: <!--pre_color-->;
	background-color: <!--pre_backgroundColor-->;
	padding: 0px;
	margin: auto;
	overflow: auto;
	cursor: default;
}

a:link,
a:visited,
a:active {
	color:#EDF4FA;
	text-decoration: none;
}

a:hover {
	color:#EEEEEE;
	text-decoration: underline;
}

table {
	align-content: center;
	font-size: 12px;
	margin:0px auto;
	width:100%;
}

img {
	border: 0px;
	vertical-align: middle;
}

#page_main {
	margin: auto;
	vertical-align: top;
	width: 100%;
}

#main_show {
	margin: 20px;
	width: auto;
	overflow: auto;
	overflow-x: hidden;
	vertical-align: top;
	border: 1px #3275AB solid;
}

#main_nav {
	background-color: #3860BB;
	margin: auto;
	padding: 10px 0px;
}

#main_nav .font {
	font-size:18px;
	font-weight:bold;
	color:white;
	text-align:center;
}

#main_copyright {
	color: #EDF4FA;
	font-size: 12px;
	font-family: Tahoma;
	text-align: center;
	line-height: 20px;
	margin-top: 20px;
	padding: 0px;
	border-top: 1px #0C5280 solid;
}

table td {
	font-size: 12px;
	background-color:#4791C5;
	border-bottom:1px solid #0C5280;
	border-right:1px solid #0C5280;
	border-top:1px solid #629DC5;
	border-left:1px solid #629DC5;
	padding:5px;
	color: #EDF4FA;
	vertical-align: middle;
}

table td.cat {
	border:0px solid;
	font-size: 12px;
	font-weight:bold;
	color:#15324A;
	padding:5px;
	background-color:#8AB4D6;
	border-right:1px solid #0C5280;
	border-bottom:1px solid #0C5280;
	border-top:1px solid #629DC5;
	border-left:1px solid #629DC5;
	vertical-align: middle;
}

#list_area {
	width: 1100px;
	border: 0px;
	margin: 10px;
}
</style>
<div id="page_main">
	<div id="main_show">
		<div id="main_nav">
			<div class="font"><a href="###" target="_blank"><!--subject--></a></div>
		</div>
		<div id="main_content">
			<table id="list_area" cellSpacing="0" cellPadding="0" align="center">
				<tr align="center">
					<td class="cat" width="20"><a href="?"><font color="#000000">编号</font></a></td>
					<td class="cat" width="30"><a href="?&order=name"><font color="#000000">店铺名称</font></a></td>
					<td class="cat" width="40"><a href="?&order=city"><font color="#000000">所在城市</font></a></td>
					<td class="cat" width="40"><a href="?&order=tel"><font color="#000000">电话</font></a></td>
					<td class="cat" width="40"><a href="?&order=QQ"><font color="#000000">QQ</font></a></td>
					<td class="cat" width="40"><a href="?&order=expire"><font color="#000000">过期时间</font></a></td>
					<td class="cat" width="40"><a href="?&order=uid"><font color="#000000">论坛用户</font></a></td>
					<td class="cat" width="40">相关操作</td>
				</tr>
				<!--loop:start key="record" time="5"-->
				<tr align="center">
					<td><!--record_id--></td>
					<td><a href="<!--record_url-->" title="<!--record_ad_text-->" target="_blank"><!--record_name--></a> <a href="<!--record_image-->" target="_blank">[图]</a></td>
					<td><!--record_province--> - <!--record_city--></td>
					<td><!--record_tel--></td>
					<td><!--record_QQ--></td>
					<td><!--record_expire--></td>
					<td><a href="http://bbs.a9vg.com/profile.php?action=show&uid=<!--record_uid-->"><!--record_username--></a></td>
					<td><a href="?method=edit&id=<!--record_id-->">修改</a> <a href="?method=delete&id=<!--record_id-->" onclick="return confirm('是否确认删除该项目？')">删除</a></td>
				</tr>
				<!--loop:end-->

				<tr align="center">
					<td class="cat" colspan="8">Block-IF test</td>
				</tr>
				<!--if:start key='if_show'-->
				<tr align="center">
					<td colspan="8">xxxxxxx</td>
				</tr>
				<!--else-->
				<tr align="center">
					<td colspan="8">yyyyyyyy</td>
				</tr>
				<!--if:end-->

				<tr align="center">
					<td class="cat" colspan="8">Block-SWITCH test</td>
				</tr>
				<!--switch:start key='sw_show'-->
				<!--1-->
				<tr align="center">
					<td colspan="8">11111</td>
				</tr>
				<!--break-->
				<!--2-->
				<tr align="center">
					<td colspan="8">222222</td>
				</tr>
				<!--break-->
				<!--3-->
				<tr align="center">
					<td colspan="8">3333331</td>
				</tr>
				<!--break-->
				<!--switch:end-->


				<tr align="center">
					<td class="cat" colspan="8">Block-RANDOM test</td>
				</tr>
				<!--random:start-->
				<tr align="center">
					<td colspan="8">aaaaaa</td>
				</tr>
				<line>
				<tr align="center">
					<td colspan="8">bbbbbbb</td>
				</tr>
				<line>
				<tr align="center">
					<td colspan="8">ccccccc</td>
				</tr>
				<!--random:end-->
			</table>
			<div>
				<b>Variants Test:</b><br />
				<b>Array:</b> <!--arr_name--> - <!--arr_age--><br />
				<b>Object:</b> <!--obj_name--> - <!--obj_age--><br />
			</div>
			<div>&nbsp;</div>
			<div style="font-weight:bold">Sub-template with Variants</div>
			<!--test_let var1='$test1' var2='$test2["a"]' var3='$_get["c"]'-->
			<div>&nbsp;</div>
			<div style="font-weight:bold">Sub-template with Loop Block</div>
			<!--test_loop name='list - name' author="myStep" time='Y-m-d' loop='5'-->
			<div>&nbsp;</div>
			<div style="font-weight:bold">Sub-template with If Block</div>
			<!--test_if key='$test_if'-->
			<div>&nbsp;</div>
			<div style="font-weight:bold">Sub-template with Switch Block</div>
			<!--test_switch key='$test_switch'-->
		</div>
	</div>
</div>