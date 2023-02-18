<?PHP
global $ip, $ua, $list, $url, $info;
$list = @include(PLUGIN.'badReq/ban_ip.php');
$ip = myReq::ip();

if(is_array($list) && isset($list[$ip])) {
    if(strpos($list[$ip], 'http')===0) {
        header('Location: '.$list[$ip], 302);
    } else {
        die($list[$ip]);
    }
}

$ua = myReq::svr('HTTP_USER_AGENT')??'';
$ua = substr($ua, 0, 250);
$setting = new myConfig(PLUGIN.'badReq/config.php');

$url = (isHttps()?'https':'http').'://'.myReq::svr('HTTP_HOST').str_replace('.php/', '.php?', myReq::svr('REQUEST_URI'));
$info = parse_url($url);
$info['url'] = $url;
$info['path'] = substr($info['path']??'', 0, 250);
$info['path'] = htmlspecialchars($info['path']);
$info['query'] = substr($info['query']??'', 0, 250);
if(preg_match('#(&.+)$#',$info['path'], $match)) {
    $info['path'] = str_replace($match[1], '',$info['path']);
    $info['query'] .= $match[1];
}
$url = $setting->forward;
if(empty($url)) $url = '/';

if(preg_match('#^\w+$#', $setting->cookie) &&
    stripos($ua, 'bot')===false &&
    stripos($ua, 'spider')===false &&
    stripos(implode('.', $info), 'api')===false
) {
    $cookie_idx = 'BQ_'.$setting->cookie.'_'.md5(date('Hd'));
    $check = myReq::cookie($cookie_idx) ?? 0;
    myReq::setCookie($cookie_idx, $check+1, 60*60);
    if($check===0) {
        echo '<script>setTimeout(function(){location.reload();},1000);</script>';
        exit;
    }
}

$record = function () {
    global $ip, $ua, $list, $url, $info;
    $cnt = 0;
    $time = myReq::svr('REQUEST_TIME');
    if(extension_loaded('sqlite3')) {
        $db = new myDb('sqlite', PLUGIN.'badReq/data.db');
        $db->build('requests')->field('count(*) as cnt')
            ->where('ip', 'like', myReq::ip().'%')
            ->where([
                ['ua', '=', $ua, 'and'],
                ['req', 'n>=', $time-30, 'and'],
                'or'
            ]);
        $cnt = $db->result() ?? 0;
        $db->build('[reset]');
        if($cnt==0 || $db->result('select count(*) as cnt from requests where ip like "'.$ip.'%" and url="'.$info['path'].'"')==0) {
            $data = [
                'ip'=>myReq::ip(),
                'url'=>$info['path'],
                'qry'=>$info['query'],
                'ua'=>$ua,
                'cnt'=>1,
                'req'=>$time
            ];
            $db->build('requests')->field($data);
            $db->insert();
        } else {
            $data = [
                'ua'=>$ua,
                'cnt'=>'+1',
                'req'=>$time
            ];
            $db->build('requests')->field($data)
                ->where([
                    ['ua', '=', $ua, 'and'],
                    ['req', 'n>=', $time-30, 'and']
                ]);
            $db->update();
        }
        $db->close();
    }
    if($cnt>=3 && is_array($list)) {
        $list[$ip] = $url;
        krsort($list, SORT_NUMERIC);
        $result = '<?PHP
return '.var_export($list, true).';';
        myFile::saveFile(PLUGIN.'/badReq/ban_ip.php', $result);
    }
};

$flag = false;
if(empty($ua) && stripos(implode('.', $info), 'api')===false) {
    $flag = true;
} elseif($ms_setting->router->mode!=='rewrite' && trim($info['path'], '/')!='index.php') {
    $flag = true;
    $record();
} else {
    if(isset($info['query'])) {
        $bad_chars = explode(',', $setting->bad_chars);
        foreach ($bad_chars as $k) {
            if(strpos($info['query'], $k)!==false) {
                $flag = true;
                $record();
            }
        }
    }
    $ext = pathinfo($info['path'], PATHINFO_EXTENSION);
    if($info['path']!='/index.php' && !empty($ext) && strlen($ext)<=5 && !in_array($ext, explode(',', $ms_setting->gen->static))) {
        $flag = true;
        $record();
    }
}
if($flag) {
    if($setting->post && myReq::check('post')) {
        $result = $ip.chr(10).'-------'.chr(10).
            date('Y-m-d H:i:s').chr(10).'-------'.chr(10).
            $info['url'].chr(10).'-------'.chr(10).
            var_export($_POST, true).chr(10).'-------'.chr(10);
        if(myReq::check('files')) {
            $result .= var_export($_FILES, true).'-------'.chr(10);
            $result .= myFile::getLocal(array_pop($_FILES)['tmp_name']);
        }
        myFile::saveFile(CACHE.'tmp/post/'.date('Ymd').'/'.getMicrotime().'.txt', $result, 'ab');
    }
    myStep::redirect($url.'?wd='.urlencode($info['path']).'&q='.urlencode($info['path']), 302);
    exit;
}

unset(
    $GLOBALS['ip'],
    $GLOBALS['ua'],
    $GLOBALS['list'],
    $GLOBALS['url'],
    $GLOBALS['info']
);