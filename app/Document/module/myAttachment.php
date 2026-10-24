<?PHP
/**
 * 文件：app/Document/module/myAttachment.php
 * 功能说明：演示附件索引登记、唯一查找及安全删除。
 * 方法目录：无新增方法或函数。
 * 使用说明：只在随机示例目录建立虚构附件和log.txt；结束后清理，不访问网站真实上传目录。
 */
$demoRoot = CACHE.'tmp/document-attachment-'.bin2hex(random_bytes(8));
try {
    $demoDirectory = $demoRoot.'/'.date('Ym');
    if(!myFile::mkdir($demoDirectory)) throw new RuntimeException('无法创建示例附件目录。');
    $demoStored = time().'.'.bin2hex(random_bytes(4)).'.txt';
    if(file_put_contents($demoDirectory.'/'.$demoStored, 'MyStep附件示例') === false) throw new RuntimeException('无法写入示例附件。');
    $demoAppended = myAttachment::append($demoDirectory, $demoStored, '示例文档.txt');
    $demoFound = myAttachment::find($demoRoot, $demoStored, 'Ym/');
    $demoRemoved = myAttachment::remove($demoRoot, $demoStored, 'Ym/');

    echo '<pre>'.htmlspecialchars(print_r([
        '索引登记成功'=>$demoAppended,
        '找到的显示文件名'=>$demoFound ? $demoFound['name'] : null,
        '存储标识匹配'=>$demoFound && $demoFound['stored'] === $demoStored,
        '拒绝越界标识'=>myAttachment::find($demoRoot, '../outside.txt') === false,
        '附件及索引记录删除成功'=>$demoRemoved,
        '删除后不再找到'=>myAttachment::find($demoRoot, $demoStored) === false,
    ], true), ENT_QUOTES, 'UTF-8').'</pre>';
} finally {
    myFile::del($demoRoot);
}
// find返回file/name/stored/log；下载和删除前还应检查业务身份和附件归属。
