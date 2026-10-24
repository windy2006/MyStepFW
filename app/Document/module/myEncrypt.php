<?PHP
/**
 * 文件：app/Document/module/myEncrypt.php
 * 功能说明：演示字符串认证加密及本地文件分块认证转换。
 * 方法目录：无新增方法或函数。
 * 使用说明：字符串需要OpenSSL，文件需要Sodium；密钥及文件均为随机独立样例，不改写原有示例数据。
 */
$demoKey = random_bytes(32); // 实际项目应安全持久保存密钥，不要把密钥与密文一同公开。
$demoPlain = 'MyStep 认证加密示例';
$demoEnvelope = myEncrypt::seal($demoPlain, $demoKey);
if($demoEnvelope === false) throw new RuntimeException('无法加密示例字符串。');
$demoResults = [
    '字符串封装'=>$demoEnvelope,
    '字符串往返一致'=>myEncrypt::open($demoEnvelope, $demoKey) === $demoPlain,
    '错误密钥被拒绝'=>myEncrypt::open($demoEnvelope, random_bytes(32)) === false,
    '空字符串可还原'=>myEncrypt::open(myEncrypt::seal('', $demoKey), $demoKey) === '',
];

if(function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
    $demoRoot = CACHE.'tmp/document-encrypt-'.bin2hex(random_bytes(8));
    try {
        if(!myFile::mkdir($demoRoot)) throw new RuntimeException('无法创建加密示例目录。');
        $demoFile = $demoRoot.'/sample.txt';
        if(file_put_contents($demoFile, $demoPlain) === false) throw new RuntimeException('无法写入示例文件。');
        $demoResults['文件加密成功'] = myEncrypt::sealFile($demoFile, $demoKey);
        if(!$demoResults['文件加密成功']) throw new RuntimeException('无法加密示例文件。');
        $demoEncryptedHash = hash_file('sha256', $demoFile);
        $demoRejected = myEncrypt::openFile($demoFile, random_bytes(32)) === false;
        $demoResults['错误密钥保留文件'] = $demoRejected && hash_equals($demoEncryptedHash, hash_file('sha256', $demoFile));
        $demoResults['文件解密成功'] = myEncrypt::openFile($demoFile, $demoKey);
        $demoResults['文件明文恢复一致'] = myFile::getLocal($demoFile) === $demoPlain;
    } finally {
        myFile::del($demoRoot);
    }
} else {
    $demoResults['文件样例运行前提'] = '请启用 PHP Sodium Secretstream 扩展；字符串样例不依赖Sodium。';
}
echo '<pre>'.htmlspecialchars(print_r($demoResults, true), ENT_QUOTES, 'UTF-8').'</pre>';
// seal/open用于字符串；sealFile/openFile直接原子转换本地文件，适用于大文件及二进制内容。
// 密码存储使用myAuth::hashPassword；加密失败时按=== false判断，不能把空明文误判为失败。
