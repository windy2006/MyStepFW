<?PHP
require_once("./lib/pinyin.php");

$abbr = '';
$content = f::g(PATH.'data/file/utf8.txt');
echo 'Original String: '.$content.'<br />';
echo 'Chatset: '.s::c($content).'<br />';
echo 'PinYin: '.implode(',', pinyin($content, $abbr)).'<br />';
echo 'Abbreviation: '.$abbr;