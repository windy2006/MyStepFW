<?PHP
$list = include(PLUGIN.'badurl/ban_ip.php');
$ip = myReq::ip();
if(isset($list[$ip])) {
    if(strpos($list[$ip], 'http')===0) {
        header('Location: '.$list[$ip], 302);
    } else {
        die($list[$ip]);
    }
}
$badUrl = function () {
    $cnt = 0;
    if (extension_loaded('sqlite3')) {
        $db = new myDb('sqlite', PLUGIN.'badurl/data.db');
        $info = parse_url(  (isHttps()?'https':'http').'://'.myReq::svr('HTTP_HOST').str_replace('.php/', '.php?', myReq::svr('REQUEST_URI')));
        $info['path'] = substr($info['path']??'', 0, 250);
        $info['query'] = substr($info['query']??'', 0, 250);
        if(preg_match('#(&.+)$#',$info['path'], $match)) {
            $info['path'] = str_replace($match[1], '',$info['path']);
            $info['query'] .= $match[1];
        }
        $db->build('requests')->field('sum(cnt) as cnt')->where('ip', '=', myReq::ip());
        $cnt = $db->result() ?? 0;
        $db->build('[reset]');
        if($cnt==0) {
            $data = [
                'ip'=>myReq::ip(),
                'url'=>$info['path'],
                'qry'=>$info['query'],
                'ua'=>substr(myReq::svr('HTTP_USER_AGENT')??'', 0, 250),
                'cnt'=>1,
                'req'=>time()
            ];
            $db->build('requests')->field($data);
            $db->insert();
        } else {
            $data = [
                'cnt'=>'+1',
                'req'=>time()
            ];
            $db->build('requests')->field($data)->where('url', '=', $info['path'])->where('ip', '=', myReq::ip());
            $db->update();
        }
        $db->close();
    }

    $setting = new myConfig(PLUGIN.'badurl/config.php');
    $url = $setting->forward;
    if(empty($url)) $url = '/';

    if($cnt<5) $cnt = myReq::cookie('badurl') ?? $cnt;
    if($cnt>=5) {
        $list = include(PLUGIN.'/badurl/ban_ip.php');
        $ip = myReq::ip();
        $ip = str_replace(' ', '', $ip);
        $ip = explode(',', $ip);
        $ip = $ip[0];
        $list[$ip] = $url;
        krsort($list, SORT_NUMERIC);
        $result = '<?PHP
return '.var_export($list, true).';';
        myFile::saveFile(PLUGIN.'/badurl/ban_ip.php', $result);
    }
    myReq::setCookie('badurl', ++$cnt, 60*60);

    if(myReq::check('post')) {
        myFile::saveFile(CACHE.'tmp/post/'.getMicrotime().'.txt',
            myReq::ip().chr(10).'-------'.chr(10).
            date('Y-m-d H:i:s').chr(10).'-------'.chr(10).
            (isHttps()?'https':'http').'://'.myReq::svr('HTTP_HOST').str_replace('.php/', '.php?', myReq::svr('REQUEST_URI')).chr(10).'-------'.chr(10).
            var_export($_POST, true));
    }

    myStep::redirect($url.'?wd='.urlencode($info['path']).'&q='.urlencode($info['path']), 302);
    exit;
};
$info = parse_url(str_replace('.php/', '.php?', myReq::svr('REQUEST_URI')));
$info['path'] = substr($info['path']??'', 0, 250);
$info['query'] = substr($info['query']??'', 0, 250);
if(preg_match('#(&.+)$#',$info['path'], $match)) {
    $info['path'] = str_replace($match[1], '',$info['path']);
    $info['query'] .= $match[1];
}
$setting = new myConfig(PLUGIN.'badurl/config.php');

if($ms_setting->router->mode!=='rewrite' && trim($info['path'], '/')!='index.php') {
    $badUrl();
} else {
    if(isset($info['query'])) {
        $bad_chars = explode(',', $setting->bad_chars);
        foreach ($bad_chars as $k) {
            if(strpos($info['query'], $k)!==false) {
                $badUrl();
            }
        }
    }
    $ext = pathinfo($info['path'], PATHINFO_EXTENSION);
    if($info['path']!='/index.php' && !empty($ext) && strlen($ext)<=5 && !in_array($ext, explode(',', $ms_setting->gen->static))) {
        $badUrl();
    }
}