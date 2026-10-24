<?PHP
/**
 * 文件：app/Document/module/myClassLoader.php
 * 功能说明：演示类索引映射解析、规则归一化、原子保存和并发版本检查。
 * 方法目录：无新增方法或函数。
 * 使用说明：特别指定的值相对于所在目录；仅操作随机示例配置，不读取或保存真实config/class.php。
 */
$demoRoot = CACHE.'tmp/document-class-loader-'.bin2hex(random_bytes(8));
try {
    if(!myFile::mkdir($demoRoot)) throw new RuntimeException('无法创建示例类配置目录。');
    $demoMapping = myClassLoader::mapping("array('interface_plugin'=>'../plugin/interface_plugin.class.php')");
    $demoRules = myClassLoader::normalize([
        ['path'=>'./lib/', 'ext'=>'.php,.class.php', 'idx'=>$demoMapping],
        ['path'=>'lib/database/', 'ext'=>'.php,.class.php', 'idx'=>[]],
    ], true);
    $demoFile = $demoRoot.'/class.php';
    myClassLoader::save($demoFile, $demoRules, 'missing');
    $demoVersion = hash_file('sha256', $demoFile);
    $demoLoaded = myClassLoader::load($demoFile);
    $demoConflict = false;
    try {
        myClassLoader::save($demoFile, $demoRules, 'missing'); // 原版本已经改变，不能继续当成新文件保存。
    } catch(RuntimeException $demoError) {
        if($demoError->getCode() !== 409) throw $demoError;
        $demoConflict = true;
    }
    echo '<pre>'.htmlspecialchars(print_r([
        '纯数据映射解析'=>$demoMapping,
        '保存并读取的规则'=>$demoLoaded['rules'],
        '需要重建相关缓存'=>$demoLoaded['reset'],
        '示例内容版本长度'=>strlen($demoVersion),
        '拒绝过期版本覆盖'=>$demoConflict,
    ], true), ENT_QUOTES, 'UTF-8').'</pre>';
} finally {
    myFile::del($demoRoot);
}
// mapping不执行函数或PHP表达式；load会include受信配置，不可加载用户上传的PHP文件。
