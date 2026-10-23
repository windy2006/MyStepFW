<?php
/**
 * 文件：app/myStep/module/function/api.php (new)
 * 功能说明：原框架后台API管理入口。
 * 方法目录：本文件不声明具名方法或函数。
 * 对应模板：template/function_api.tpl。
 * 使用说明：由已鉴权的后台模块加载，共用api_admin.php装配，保存动作仍受POST/CSRF约束。
 */
$apiKind='endpoint';require APP.'myStep/api_admin.php';
