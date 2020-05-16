<?PHP
$mystep->regTag('news', 'app\cms\parseNews');
$mystep->regTag('info', 'app\cms\parseInfo');
$mystep->regTag('link', 'app\cms\parseLink');
$mystep->regTag('tag', 'app\cms\parseTag');
$mystep->regTag('catalog', 'app\cms\parseCatalog');

$user_info = \app\cms\getUserInfo();
$module = empty($info_app['path'][0]) ? 'index' : $info_app['path'][0];
app\cms\installCheck($module);
if(!is_file(PATH.'module/'.$module.'.php')) myStep::info('module_missing', '/');
if($tpl_cache) {
    $tpl_cache['path'] .= $web_info['idx'].'/';
    if(!defined('URL_FIX')) $tpl_cache['path'] .= '_sub/';
    $tpl_cache['path'] .= trim(implode('/', $info_app['path']), '/');
    $tpl_cache['path'] = str_replace('/article/', '/catalog/', $tpl_cache['path']);
    $tpl_cache['name'] = implode('_', $info_app['para']);
}
$tpl = new myTemplate($tpl_setting, $tpl_cache);
if(!in_array($module, ['article','tag','user'])) $mystep->checkCache($tpl);
require(PATH.'module/'.$module.'.php');
for($i=0,$m=count($news_cat);$i<$m;$i++) {
    if(($news_cat[$i]['show'] & 1) == 0) continue;
    if($news_cat[$i]['web_id'] != $web_info['web_id']) continue;
    $news_cat[$i]['idx'] =  $i;
    $news_cat[$i]['link'] = \app\cms\getLink($news_cat[$i], 'catalog');
    $tpl->setLoop('news_cat', $news_cat[$i]);
}
$tpl->assign('news_cat', myString::toJson($news_cat));
if(isset($t) && ($t instanceof myTemplate)) $content = $mystep->render($t);
if(!isset($content)) $content = 'No content has been set!';
$tpl->assign('main', $content);
$mystep->show($tpl);
$mystep->end();