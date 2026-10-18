<?php
// Listing attachments must never delete directories or rewrite upload logs.
$the_year = myReq::request('y');
$the_month = myReq::request('m');
$the_year = is_string($the_year) && preg_match('/\A\d{4}\z/D', $the_year) ? $the_year : '';
$the_month = is_string($the_month) && preg_match('/\A(?:0[1-9]|1[0-2])\z/D', $the_month) ? $the_month : '';
if(myReq::get('m') === 'del') {
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
    $relative = myReq::post('idx', '!');
    $target = is_string($relative) ? myFile::extractionPath(FILE, $relative) : false;
    if($target && is_file($target) && basename($target) !== 'log.txt') {
        $logged = false; $log = dirname($target).'/log.txt';
        if(is_file($log) && !is_link($log)) foreach(file($log) as $line) if(explode('::', $line)[0] === basename($target)) $logged = true;
        if(!$logged) unlink($target);
    }
    myStep::redirect(myStep::setURL($ms_setting->gen->path_admin.'/function/attachment'));
}
$directories = function($directory, $pattern) {
    if(!is_dir($directory) || is_link($directory)) return [];
    $result = [];
    foreach(new DirectoryIterator($directory) as $entry) if(!$entry->isDot() && !$entry->isLink() && $entry->isDir() && preg_match($pattern, $entry->getFilename())) $result[] = $entry->getFilename();
    rsort($result); return $result;
};
$years = $directories(FILE, '/\A\d{4}\z/D');
if(!in_array($the_year, $years, true)) $the_year = $years[0] ?? '';
foreach($years as $year) $t->setLoop('years', ['year'=>$year, 'selected'=>$year === $the_year ? 'selected' : '']);
$months = $the_year === '' ? [] : $directories(FILE.$the_year, '/\A(?:0[1-9]|1[0-2])\z/D');
if(!in_array($the_month, $months, true)) $the_month = $months[0] ?? '';
foreach($months as $month) $t->setLoop('months', ['month'=>$month, 'selected'=>$month === $the_month ? 'selected' : '']);
$empty = true;
if($the_year !== '' && $the_month !== '') {
    $directory = FILE.$the_year.'/'.$the_month; $records = [];
    if(is_file($directory.'/log.txt') && !is_link($directory.'/log.txt')) {
        foreach(file($directory.'/log.txt') as $line) {
            $parts = explode('::', rtrim($line, "\r\n"));
            if(count($parts) >= 2) $records[$parts[0]] = $parts[1];
        }
    }
    foreach(new DirectoryIterator($directory) as $entry) {
        if($entry->isDot() || $entry->isLink() || !$entry->isFile() || $entry->getFilename() === 'log.txt' || $entry->getFilename()[0] === '.') continue;
        $stored = $entry->getFilename(); $logged = array_key_exists($stored, $records);
        $name = htmlspecialchars($logged ? $records[$stored] : $stored, ENT_QUOTES, 'UTF-8');
        $t->setLoop('files', [
            'idx'=>htmlspecialchars($logged ? pathinfo($stored, PATHINFO_FILENAME) : $the_year.'/'.$the_month.'/'.$stored, ENT_QUOTES, 'UTF-8'),
            'name'=>$name.($logged ? '' : ' <span class="badge badge-danger">Not Logged!</span>'),
            'size'=>myFile::formatSize($entry->getSize()), 'date'=>date('Y-m-d H:i:s', $entry->getMTime())
        ]);
        $empty = false;
    }
}
$t->setIf('empty', $empty);
