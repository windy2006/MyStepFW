<?PHP
/**
 * 文件：app/myStep/module/setting/execute.php
 * 功能说明：载入受信功能类示例或执行框架管理员提交的PHP代码，提供代码与结果给同名模板。
 * 方法目录：无具名方法；依次读取代码、校验示例、捕获执行输出并分配模板变量。
 * 使用说明：经框架后台鉴权和POST的CSRF检查后加载；GET未提交代码时使用默认示例。
 * 参数说明：POST code为字符串；path[3]为Document/module下的单个示例名，禁止路径跳转。
 * 返回说明：输出写入模板变量，不直接输出页面；非字符串代码返回422，示例不存在返回404。
 */
$submittedCode = myReq::post('code');
if($submittedCode !== null && !is_string($submittedCode)) {
    http_response_code(422);
    $t->assign('code', '');
    $t->assign('result', '代码内容必须为字符串。');
    return;
}
$code = htmlspecialchars_decode($submittedCode ?? '');
$result = '';
if(isset($info_app['path'][3])) {
    $module = $info_app['path'][3];
    if(!is_string($module) || !preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $module)) myStep::header('404');
    $file = myFile::resolveWithin(APP.'Document/module/', $module.'.php');
    if($file === false || !is_file($file)) myStep::header('404');
    $code = htmlspecialchars(myFile::getLocal($file));
    ob_clean();
    include($file);
    $result = ob_get_contents();
    ob_clean();
} else {
    if(empty($code)) {
        $code = '<?PHP
echo "<pre>";
echo var_export($_SERVER, true);
echo "</pre>";
';
    }
    $result = myEval($code, false);
}
$t->assign('code', $code);
$t->assign('result', $result);
