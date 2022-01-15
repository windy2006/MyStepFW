<?PHP
global $news_cat, $news_cat_plat, $website, $web_info;
app\CMS\installCheck();
if(($news_cat=\app\CMS\getCache('news_cat'))===false) {
    myStep::info('error_para');
}
$news_cat_plat = $cache->func('\app\CMS\setCatList', [$news_cat]);
define('ROOT_APP', ROOT_WEB.(defined('URL_FIX')?'':$info_app['app']));