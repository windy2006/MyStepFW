<?php
/**
 * 文件：app/CMS/module/admin/plugin/manage.php
 * 功能说明：承载所有已绑定、启用且授权的插件管理页面；不重复启动插件或框架。
 * 方法目录：无新增函数；调用公共renderPluginPage执行同名插件模块与模板。
 * 使用说明：由CMS后台路由及checkPower前置检查后加载，页面选择来自固定声明白名单。
 */
$idx=$_GET['_plugin']??'';
$page=$_GET['_plugin_page']??'';
if(!pluginAdminAuthorized($idx,$page,'CMS')) {
    http_response_code(403);
    throw new DomainException('没有权限访问此插件管理页面');
}
$pluginBody=renderPluginPage($idx,$page,'CMS');
$entry=pluginAdminPages($idx)[$page];
$pluginTemplate=$tpl_setting;
$pluginTemplate['name']='plugin_manage';
$t=new myTemplate($pluginTemplate,false);
$t->assign(['plugin_title'=>$entry['name'],'plugin_content'=>$pluginBody]);
$mystep->addJS(STATICS.'js/checkForm.js');
$content=$mystep->render($t);
