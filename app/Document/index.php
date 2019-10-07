<?php
$module = $info_app['path'][1] ?? null;
$mode = $info_app['path'][2] ?? null;
if (is_null($mode)) $mode = 'show';
$sp_module = ['myExcel', 'myImg'];
$tpl_setting['name'] = 'main';
$tpl = new myTemplate($tpl_setting, false);

if (is_null($module)) {
    set_include_path(get_include_path() . PATH_SEPARATOR . './lib/database/' . PATH_SEPARATOR . './lib/cache/');
    spl_autoload_extensions('.class.php,.php');
    spl_autoload_register();
    $list = f::find('*.php', PATH . 'module');

    $tpl_setting['name'] = 'list';
    $sub_tpl = new myTemplate($tpl_setting, false);

    $t = new myReflection('stdClass');
    $n = 1;
    foreach ($list as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $doc = '';
        if (class_exists($name)) {
            $t->init($name);
            $doc = $t->doc;
        }
        $sub_tpl->setLoop('item', ['no' => $n++, 'name' => $name, 'doc' => $doc]);
    }
    $tpl->assign('main', $sub_tpl->display('', false));
} else {
    if (!class_exists($module)) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }
    switch ($mode) {
        case 'show':
            if (array_search($module, $sp_module) !== false) {
                include(PATH . 'module/' . $module . '.php');
                $mystep->end();
            } elseif (is_file(PATH . 'module/' . $module . '.php')) {
                $tpl_setting['name'] = 'sample';
                $sub_tpl = new myTemplate($tpl_setting, false);
                $mystep->setAddedContent('start', '
<link href="http://alexgorbatchev.com/pub/sh/current/styles/shCore.css" rel="stylesheet" type="text/css">
<link href="http://alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css" rel="stylesheet" type="text/css">
                ');
                $mystep->setAddedContent('end', '
<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shCore.js" type="text/javascript"></script>
<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shBrushPhp.js" type="text/javascript"></script>
<script type="text/javascript">SyntaxHighlighter.all();</script>
                ');

                ob_clean();
                include(PATH . 'module/' . $module . '.php');
                $content = ob_get_contents();
                ob_clean();

                $sub_tpl->assign('name', $module);
                $sub_tpl->assign('code', htmlspecialchars(f::g(PATH . 'module/' . $module . '.php')));
                $sub_tpl->assign('sample', $content);
                $content = $sub_tpl->display('', false);
                $mystep->setting->web->title = 'Sample : ' . $module . ' - ' . $mystep->setting->web->title;
            } else {
                $content = '模块不存在！';
            }
            break;
        default:
            $tpl_setting['name'] = 'detail';
            $sub_tpl = new myTemplate($tpl_setting, false);
            $detail = new myReflection($module);
            $methods = $detail->getFunc();
            $sub_tpl->assign('name', $module);
            $sub_tpl->assign('doc', $detail->getComment());

            $n = 1;
            foreach ($methods as $method) {
                $doc = $method->getDocComment();
                $doc = trim($doc, '/*');
                if (empty($doc)) continue;
                $sub_tpl->setLoop('item', ['no' => $n++, 'name' => $method->getName(), 'doc' => $doc]);
            }
            $content = $sub_tpl->display('', false);
            $mystep->setting->web->title = 'Class : ' . $module . ' - ' . $mystep->setting->web->title;
    }
    $tpl->assign('main', $content);
}
$mystep->setting->show = true;
$mystep->show($tpl);
$mystep->end();