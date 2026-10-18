<?php
/**
 * 文件：app/Admin/config.php
 * 功能说明：应用标题、默认模板、页面缓存和按需数据库连接配置。
 * 方法目录：无新增函数；返回应用配置数组。
 * 使用说明：框架在初始化当前应用时合并；模板路径相对于网站根，保留全局管理员、Cookie和Session设置。
 */
return ['gen'=>['cache_page'=>false],'web'=>['title'=>'简明管理后台','minify'=>false],'template'=>['name'=>'main','path'=>'app/Admin/template','style'=>'default'],'db'=>['auto'=>false]];
