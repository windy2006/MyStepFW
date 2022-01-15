<?PHP
list($tpl, $t) = setPluginTemplate('sample', 'show', false);
$t->assign('topic', 'Bootstrap 4 Dashboard');
$content = $mystep->render($t);