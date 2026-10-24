<?PHP
$module = $info_app['path'][0] ?? null;
$mode = $info_app['path'][1] ?? null;
if (is_null($mode)) $mode = 'show';
// 样例文件名同时作为公开反射白名单，避免任意类名触发自动加载及路径拼接。
$examples = glob(PATH.'module/*.php');
$allowed = array_map(function($file) { return pathinfo($file,PATHINFO_FILENAME); },$examples);
if(!is_null($module) && $module!=='' && (!is_string($module) || !in_array($module,$allowed,true))) myStep::header('404');
set_include_path(get_include_path().PATH_SEPARATOR.LIB.'database/'.PATH_SEPARATOR.LIB.'cache/');
spl_autoload_extensions('.class.php,.php');
spl_autoload_register();
$sp_module = ['myExcel', 'myImg'];
$tpl_setting['name'] = 'main';
$tpl = new myTemplate($tpl_setting, false);

if(empty($module)) {
    $list = f::find('*.php', PATH . 'module');
    $tpl_setting['name'] = 'list';
    $tpl_sub = new myTemplate($tpl_setting, false);
    $t = new myReflection('stdClass');
    $n = 1;
    foreach ($list as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $doc = '';
        if (class_exists($name) || interface_exists($name) || trait_exists($name)) {
            $t->init($name);
            $doc = $t->doc;
        }
        $tpl_sub->setLoop('item', ['no' => $n++, 'name' => $name, 'doc' => $doc]);
    }
    $tpl->assign('main', $tpl_sub->render('', false));
} else {
    // 类、抽象类、接口及trait共用说明入口；仍必须先通过上方样例白名单。
    if (!class_exists($module) && !interface_exists($module) && !trait_exists($module)) myStep::header('404');
    switch ($mode) {
        case 'show':
            if (array_search($module, $sp_module) !== false) {
                include(PATH . 'module/' . $module . '.php');
                $mystep->end();
            } elseif (is_file(PATH . 'module/' . $module . '.php')) {
                $tpl_setting['name'] = 'sample';
                $tpl_sub = new myTemplate($tpl_setting, false);
                ob_clean();
                include(PATH . 'module/' . $module . '.php');
                $content = ob_get_contents();
                ob_clean();
                $tpl_sub->assign('name', $module);
                $tpl_sub->assign('code', htmlspecialchars(f::g(PATH . 'module/' . $module . '.php')));
                $tpl_sub->assign('sample', $content);
                $content = $tpl_sub->render('', false);
                $mystep->setting->web->title = 'Sample : ' . $module . ' - ' . $mystep->setting->web->title;
            } else {
                $content = '模块不存在！';
            }
            break;
        default:
            $tpl_setting['name'] = 'detail';
            $tpl_sub = new myTemplate($tpl_setting, false);
            $detail = new myReflection($module);
            $methods = $detail->getFunc();
            $tpl_sub->assign('name', $module);
            $tpl_sub->assign('doc', $detail->getComment());

            $n = 1;
            foreach ($methods as $method) {
                $doc = $method->getDocComment();
                $doc = trim($doc, '/*');
                if (empty($doc)) continue;
                $tpl_sub->setLoop('item', ['no' => $n++, 'name' => $method->getName(), 'doc' => $doc]);
            }
            $content = $tpl_sub->render('', false);
            $mystep->setting->web->title = 'Class : ' . $module . ' - ' . $mystep->setting->web->title;
    }
    $tpl->assign('main', $content);
}

// 普通说明页始终输出 HTML；示例内部的 API 调用可能把响应类型改成纯文本。
// 图片、Excel 等直接响应已在前面的分支终止，不经过此处。
if(!headers_sent()) header('Content-Type: text/html; charset='.$mystep->setting->gen->charset);
