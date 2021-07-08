<?PHP
$years = [];
$the_year = myReq::request('y');
$the_month = myReq::request('m');
$list = myFile::find('*', FILE, false, 1);
$m = myReq::get('m');
$empty = true;
if($m=='del') {
    $idx = myReq::get('idx');
    list($the_year, $the_month, $the_file) = explode('/', $idx);
    if($the_file!='log.txt') myFile::del(FILE.$idx);
}
if($list===false) $list = [];
for($i=0,$m=count($list);$i<$m;$i++) {
    $tmp = myFile::find('log.txt', $list[$i], true, 2);
    if(empty($tmp)) {
        myFile::del($list[$i]);
    } else {
        if(empty($the_year) && empty($years)) $the_year = basename($list[$i]);
        $years[] = basename($list[$i]);
        $tmp = [
            'year' => basename($list[$i]),
            'selected' => $the_year == basename($list[$i]) ? 'selected' : ''
        ];
        $t->setLoop('years', $tmp);
    }
}
if(!empty($years)) {
    $months = [];
    $logs = [];
    if(!is_dir(FILE.$the_year)) {
        $the_year = $years[0];
    }
    $list = myFile::find('*', FILE.$the_year, false, 1);
    for($i=0,$m=count($list);$i<$m;$i++) {
        $tmp = myFile::find('log.txt', $list[$i], true, 2);
        if(empty($tmp)) {
            myFile::del($list[$i]);
        } else {
            if(empty($the_month) && empty($months)) $the_month = basename($list[$i]);
            $months[] = basename($list[$i]);
            $logs[] = myFile::find('log.txt', $list[$i], true);
            $tmp = [
                'month' => basename($list[$i]),
                'selected' => $the_month == basename($list[$i]) ? 'selected' : ''
            ];
            $t->setLoop('months', $tmp);
        }
    }
    if(!is_dir(FILE.$the_year.'/'.$the_month)) {
        $the_month = $months[0]??'';
    }
    $files = myFile::getTree(FILE.$the_year.'/'.$the_month);
    $the_log = myFile::getLocal(FILE.$the_year.'/'.$the_month.'/log.txt');
    if(strlen($the_log)>10) {
        foreach($files as $k => $v) {
            if($k=='log.txt') continue;
            $tmp = [
                'idx' => preg_replace('/\.\w+$/','', $k),
                'name' => $k,
                'size' => $v['size'],
                'date' => $v['time'],
            ];
            if(preg_match('#'.$k.'::(.+?)::#', $the_log, $match)) {
                $the_log = preg_replace('#'.$k.'\:\:.+?\n#ms', '', $the_log);
                $tmp['name'] = $match[1];
            } else {
                $tmp['idx'] = $the_year.'/'.$the_month.'/'.$tmp['name'];
                $tmp['name'] .= ' <span class="badge badge-danger">Not Logged!</span>';
            }
            $empty = false;
            $t->setLoop('files', $tmp);
        }
        if(strlen($the_log)>0) {
            $list = explode(chr(10), $the_log);
            $the_log = myFile::getLocal(FILE.$the_year.'/'.$the_month.'/log.txt');
            for($i=0,$m=count($list);$i<$m;$i++) {
                if(strpos($list[$i], '::')===false) continue;
                $tmp = [
                    'idx' => '',
                    'name' => '',
                    'size' => '0',
                    'date' => '----',
                ];
                list($tmp['idx'], $tmp['name']) = explode('::', $list[$i]);
                $tmp['name'] .= ' <span class="badge badge-danger">File is Missing!</span>';
                $empty = false;
                $t->setLoop('files', $tmp);
                $the_log = str_replace($list[$i].chr(10), '', $the_log);
            }
            myFile::saveFile(FILE.$the_year.'/'.$the_month.'/log.txt', $the_log);
        }
    } else {
        myFile::del(FILE.$the_year.'/'.$the_month);
    }
}
$t->setIf('empty',  $empty);