<?PHP
if(!empty($s->gen->close)) cms::redirect($s->gen->close);
$mystep->regTag('news', 'app\CMS\parseNews');
$mystep->regTag('news_next', 'app\CMS\parseNewsNext');
$mystep->regTag('info', 'app\CMS\parseInfo');
$mystep->regTag('link', 'app\CMS\parseLink');
$mystep->regTag('tag', 'app\CMS\parseTag');
$mystep->regTag('catalog', 'app\CMS\parseCatalog');

$module = $info_app['path'][0] ?? 'index';
$user_info = \app\CMS\getUserInfo();
if(!is_file(PATH.'module/'.$module.'.php')) {
    $info_app['path'][1] = $info_app['path'][0];
    $info_app['path'][0] = $module = 'article';
}
if($tpl_cache) {
    $tpl_cache['path'] .= $web_info['idx'].'/';
    if(!defined('URL_FIX')) $tpl_cache['path'] .= '_sub/';
    $tpl_cache['path'] .= trim(implode('/', $info_app['path']), '/');
    $tpl_cache['path'] = str_replace('/article/', '/catalog/', $tpl_cache['path']);
    $tpl_cache['name'] = implode('_', $info_app['para']);
    $tpl_cache['expire'] = $s->expire->$module;
    if(is_null($tpl_cache['expire'])) $tpl_cache['expire'] = $s->expire->default;
}
$tpl = new myTemplate($tpl_setting, $tpl_cache);
if(!in_array($module, ['article','tag','user'])) $mystep->checkCache($tpl);
require(PATH.'module/'.$module.'.php');
if(is_file(PATH.'template/'.$tpl_setting['style'].'/module/'.$module.'.php')) {
    require(PATH.'template/'.$tpl_setting['style'].'/module/'.$module.'.php');
}
for($i=0,$m=count($news_cat);$i<$m;$i++) {
    if(($news_cat[$i]['show'] & 1) == 0) continue;
    if($news_cat[$i]['web_id'] != $web_info['web_id']) continue;
    $news_cat[$i]['idx'] =  $i;
    if(empty($news_cat[$i]['link'])) $news_cat[$i]['link'] = \app\CMS\getLink($news_cat[$i], 'catalog');
    $tpl->setLoop('news_cat', $news_cat[$i]);
}
$tpl->assign('news_cat', myString::toJson($news_cat));
if(isset($t) && ($t instanceof myTemplate)) $content = $mystep->render($t);
if(!isset($content)) $content = 'No content has been set!';
$tpl->assign('main', $content);
