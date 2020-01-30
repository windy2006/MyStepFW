<?PHP
use myFile as f;

f::regAlias(array(
            'rt' => 'rootPath',
            'p' => 'realPath',
            'd' => 'mkdir',
            'mime' => 'getMime',
            'c' => 'copy',
            'r' => 'rename',
            'm' => 'rename',
            's' => 'saveFile',
            'g' => 'getLocal',
            'url' => 'getRemote_curl',
            'grab' => 'getRemoteFile',
));

$f = new f(PATH.'data/file/utf8.txt');
echo '<pre>';
var_dump($f->info);
echo 'Content : '.$f."\n\n";
echo 'Mime Type : '.$f->info['mime']."\n\n";
echo 'Root Path : '.f::rt()."\n\n";
echo 'Real Path : '.f::p(PATH.'data/file/utf8.txt')."\n\n";

$dir = PATH.'data/file/a/b/c';
f::d($dir);
echo 'Make Dir : '.$dir."\n\n";

f::c(PATH.'module', PATH.'module_bak');
echo 'Copy : '."'module', -> 'module_bak'\n\n";

f::r(PATH.'module_bak', PATH.'module_2');
echo 'Renmae(move) : '."'module_bak', -> 'module_2'\n\n";

f::del(PATH.'module_2');
echo 'Delete : '."PATH.'module_2'\n\n";

$file = 'https://www.baidu.com/img/bd_logo.png';
f::del(PATH.'data/file/bd_logo.png');
f::grab($file, PATH.'data/file/bd_logo.png');
echo 'Get Remote File : '.$file."\n\n\n";

echo "Search File(s) : *.php\n";
$result = f::find('*.php', './', false);
var_dump($result);
echo "\n\n\n";

echo "Get Dir Tree : \n";
$tree = f::getTree(PATH.'module', true);
var_dump($tree);
echo "\n\n\n";

$f = new f('https://www.baidu.com');
var_dump($f->info, $f->headers, $f->get());
echo '</pre>';