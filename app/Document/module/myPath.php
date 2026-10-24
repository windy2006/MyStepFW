<?PHP
/**
 * 文件：app/Document/module/myPath.php
 * 功能说明：演示网站根相对路径与运行时绝对路径之间的转换及配置路径归一化。
 * 方法目录：无新增方法或函数。
 * 使用说明：配置保存相对路径，文件操作时调用absolute；路径规范化不代替权限和符号链接边界检查。
 */
$demoRelative = myPath::relative('./app/Document/../Document/template/');
$demoAbsolute = myPath::absolute($demoRelative);
$demoSettings = myPath::settings([
    'template'=>['path'=>'template/default/'],
    'session'=>['path'=>'cache/session/'],
    'db'=>['type'=>'SQLite', 'host'=>'cache/demo.sqlite'],
], 'Document');
$demoRejected = false;
try {
    myPath::relative('../outside.txt');
} catch(InvalidArgumentException $demoError) {
    $demoRejected = true;
}

echo '<pre>'.htmlspecialchars(print_r([
    '归一化后的相对目录'=>$demoRelative,
    '绝对路径可转换回相对路径'=>myPath::relative($demoAbsolute),
    '根目录以斜杠结束'=>substr(myPath::root(), -1) === '/',
    '规范化后的配置'=>$demoSettings,
    '拒绝越过网站根目录'=>$demoRejected,
], true), ENT_QUOTES, 'UTF-8').'</pre>';
