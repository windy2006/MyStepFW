<?PHP
$code = htmlspecialchars_decode(r::p('code'));
$result = '';
if(isset($info_app['path'][3])) {
    $module = $info_app['path'][3];
    $file = APP . 'Document/module/' . $module . '.php';
    $code = htmlspecialchars(f::g($file));
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
