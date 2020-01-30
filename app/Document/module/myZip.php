<?PHP
$file = PATH.'data/zip/test.zip';
$zip = new myZip($file);

echo 'Compress files to '.myFile::realPath($file).'<br>';
$zip->zip('lib/pinyin.php', PATH.'data/file/', '/cache', '/sbin', '/aaaa');

$dir = PATH.'data/zip';
echo 'Uncompress files to '.myFile::realPath($dir).'<br>';
$zip->unzip($dir, 1);
