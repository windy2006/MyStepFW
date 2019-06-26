<?PHP
use myEncrypt as e;

$key = 'mystep';
$file = PATH.'data/file/utf8.txt';
$content = f::g($file);
echo 'Original String: '.$content.'<br />';
e::encFile($file, $key);
$content = f::g($file);
echo 'Encrypted String: '.$content.'<br />';
e::decFile($file, $key);
