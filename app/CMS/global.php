<?PHP
global $news_cat, $news_cat_plat, $website, $web_info;

if(($news_cat=\app\CMS\getCache('news_cat'))===false) {
    myStep::info('error_para');
}
$news_cat_plat = $cache->func('\app\CMS\setCatList', [$news_cat]);
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
