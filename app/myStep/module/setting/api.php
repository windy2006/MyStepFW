<?php
/**
 * 文件：app/myStep/module/setting/api.php (new)
 * 功能说明：旧独立API参数入口兼容跳转，实际字段由全局参数设置生成。
 * 方法目录：本文件不声明具名方法或函数。
 * 对应模板：setting_main；不再显示独立API表单。
 * 使用说明：仅在原后台鉴权后加载，过期POST表单返回405。
 */
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){http_response_code(405);header('Allow: GET, HEAD');exit;}
myStep::redirect(myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/setting/').'#api');
