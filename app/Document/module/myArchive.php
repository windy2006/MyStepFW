<?PHP
/**
 * 文件：app/Document/module/myArchive.php
 * 功能说明：演示归档成员检查、暂存写入和统一发布。
 * 方法目录：无新增方法或函数。
 * 使用说明：仅发布到随机示例目录；成员数量、大小及CRC等仍由上层解包器检查。
 */
$demoRoot = CACHE.'tmp/document-archive-'.bin2hex(random_bytes(8));
$demoArchive = null;
try {
    $demoArchive = new myArchive($demoRoot);
    $demoStaged = $demoArchive->stage('guide/readme.txt');
    if($demoStaged === false || file_put_contents($demoStaged, 'MyStep 暂存发布示例') === false) throw new RuntimeException('无法暂存示例成员。');
    $demoRejected = $demoArchive->stage('../outside.txt') === false;
    $demoDuplicate = $demoArchive->stage('guide/readme.txt') === false;
    $demoBefore = is_file($demoRoot.'/guide/readme.txt');
    $demoCommitted = $demoArchive->commit();

    echo '<pre>'.htmlspecialchars(print_r([
        '发布前目标文件存在'=>$demoBefore,
        '拒绝越界成员'=>$demoRejected,
        '拒绝重复成员'=>$demoDuplicate,
        '统一发布成功'=>$demoCommitted,
        '发布后内容'=>$demoCommitted ? myFile::getLocal($demoRoot.'/guide/readme.txt') : null,
    ], true), ENT_QUOTES, 'UTF-8').'</pre>';
} finally {
    unset($demoArchive); // 先释放目标目录锁及暂存，再清理这个示例自己的目录。
    myFile::del($demoRoot);
}
// commit失败时会尝试回滚覆盖；进程崩溃及外部并发写入不保证完整回滚。
