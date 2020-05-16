<?PHP
global $news_cat, $news_cat_plat, $website, $web_info;

if(($news_cat=\app\cms\getCache('news_cat'))===false) {
    myStep::info('error_para');
}
$news_cat_plat = $cache->func('\app\cms\setCatList', [$news_cat]);
