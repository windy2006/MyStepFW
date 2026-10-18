<?php
/**
 * 文件：app/Admin/route.php
 * 功能说明：声明应用路由，鉴权、页面及动作由同名核心类Admin分发。
 * 方法目录：无新增函数；仅登记路由，不执行页面或业务写入。
 * 使用说明：框架读取本应用配置后汇总；功能实现位于module/并对应template/。
 */
$rule = [['/Admin/[any]', 'Admin::dispatch']];
