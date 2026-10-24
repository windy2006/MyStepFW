<?php
/**
 * 文件：tests/utilities/crypto_regression.php
 * 功能说明：认证字符串及文件转换的边界、篡改、互操作、文件锁和内存预算回归。
 * 方法目录：cryptoCheck、cryptoTest、cryptoPreserved（参数说明见函数PHPDoc）。
 * 使用说明：PHP 7.4 CLI运行，需要OpenSSL及Sodium；仅使用随机临时目录，不访问业务文件。
 */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2).'/lib/myEncrypt.class.php';
if(!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push') || !function_exists('openssl_encrypt')) {
    fwrite(STDERR, "Requires PHP OpenSSL and Sodium Secretstream\n"); exit(2);
}
/**
 * 功能说明：断言安全约束，失败时抛出异常。
 * @param bool $condition 是否通过
 * @param string $message 失败说明
 * @return void
 * @throws RuntimeException 断言失败
 */
function cryptoCheck($condition, $message = 'Assertion failed') {
    if(!$condition) throw new RuntimeException($message);
}
/**
 * 功能说明：运行一个回归用例并累计结果。
 * @param string $name 用例名
 * @param callable $callback 用例实现
 * @return void
 */
function cryptoTest($name, $callback) {
    global $passed, $failed;
    try { $callback(); $passed++; echo 'PASS '.$name."\n"; }
    catch(Throwable $error) { $failed++; echo 'FAIL '.$name.': '.$error->getMessage()."\n"; }
}
/**
 * 功能说明：确认非法密文未被解密发布，原文件和受限暂存清理均符合约定。
 * @param string $file 随机临时测试文件
 * @param string $cipher 待拒绝的文件字节
 * @param string $key 解密密钥
 * @return void
 */
function cryptoPreserved($file, $cipher, $key) {
    file_put_contents($file, $cipher);
    $before = hash_file('sha256', $file);
    cryptoCheck(myEncrypt::openFile($file, $key) === false, 'Invalid stream accepted');
    cryptoCheck(hash_equals($before, hash_file('sha256', $file)), 'Original ciphertext changed');
    cryptoCheck(glob(dirname($file).'/.ms-crypt-*') === [], 'Plaintext staging file remains');
}

if(($argv[1] ?? '') === '--large') {
    $file = $argv[2]; $stream = fopen($file, 'wb'); $block = random_bytes(1048576);
    for($i = 0; $i < 24; $i++) cryptoCheck(fwrite($stream, $block) === strlen($block));
    fclose($stream); unset($block);
    $key = random_bytes(32); $hash = hash_file('sha256', $file);
    cryptoCheck(myEncrypt::sealFile($file, $key));
    cryptoCheck(myEncrypt::openFile($file, $key));
    cryptoCheck(hash_equals($hash, hash_file('sha256', $file)));
    echo json_encode(['bytes'=>filesize($file), 'peak'=>memory_get_peak_usage(true)]); exit;
}

$scratch = sys_get_temp_dir().'/mystep-crypto-'.bin2hex(random_bytes(8));
mkdir($scratch, 0700); $passed = $failed = 0;
set_error_handler(function($severity, $message, $file, $line) {
    if(error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    cryptoTest('obsolete XOR entry points are removed', function() {
        foreach(['keyED','encStr','decStr','encFile','decFile'] as $method) cryptoCheck(!method_exists('myEncrypt', $method), $method.' still exists');
    });
    cryptoTest('strings retain empty binary and Unicode plaintext with randomized envelopes', function() {
        $key = random_bytes(32);
        foreach(['', '0', "\0\xff\x01", '中文😀'] as $plain) {
            $sealed = myEncrypt::seal($plain, $key);
            cryptoCheck(is_string($sealed));
            cryptoCheck(myEncrypt::open($sealed, $key) === $plain);
            cryptoCheck(myEncrypt::open($sealed, random_bytes(32)) === false);
            cryptoCheck(myEncrypt::seal($plain, $key) !== $sealed);
        }
    });
    cryptoTest('string authentication covers salt nonce tag and ciphertext', function() {
        $key = random_bytes(32); $sealed = myEncrypt::seal('message', $key);
        $bytes = base64_decode(substr($sealed, 7), true);
        foreach([0,16,28,44] as $offset) {
            $bad = $bytes; $bad[$offset] = chr(ord($bad[$offset]) ^ 1);
            cryptoCheck(myEncrypt::open('MSENC1:'.base64_encode($bad), $key) === false);
        }
        foreach(['', 'not-an-envelope', 'MSENC1:!!!', 'MSENC1:'.base64_encode(substr($bytes, 0, 43))] as $bad) cryptoCheck(myEncrypt::open($bad, $key) === false);
    });
    cryptoTest('invalid encryption arguments fail before changing files', function()use($scratch) {
        $file = $scratch.'/arguments'; file_put_contents($file, 'keep');
        foreach(['', null, []] as $key) {
            try { myEncrypt::sealFile($file, $key); throw new RuntimeException('Invalid key accepted'); }
            catch(InvalidArgumentException $expected) {}
        }
        try { myEncrypt::seal([], 'key'); throw new RuntimeException('Non-string plaintext accepted'); }
        catch(InvalidArgumentException $expected) {}
        cryptoCheck(file_get_contents($file) === 'keep');
        foreach([null, [], $scratch.'/missing', 'php://memory', 'file://'.$file] as $path) cryptoCheck(myEncrypt::sealFile($path, 'key') === false);
    });
    cryptoTest('file round trips cover empty partial exact and multiple chunk boundaries', function()use($scratch) {
        $key = random_bytes(32); $file = $scratch.'/boundaries';
        foreach([0,1,65535,65536,65537,131072,131089] as $size) {
            $plain = $size ? random_bytes($size) : '';
            file_put_contents($file, $plain); chmod($file, 0640);
            cryptoCheck(myEncrypt::sealFile($file, $key), 'Seal failed at '.$size);
            $cipher = file_get_contents($file);
            cryptoCheck(substr($cipher, 0, 8) === 'MSFILE1:');
            clearstatcache(true, $file); cryptoCheck((fileperms($file) & 0777) === 0640);
            cryptoCheck(myEncrypt::openFile($file, $key), 'Open failed at '.$size);
            cryptoCheck(file_get_contents($file) === $plain, 'Plaintext differs at '.$size);
            clearstatcache(true, $file); cryptoCheck((fileperms($file) & 0777) === 0640);
        }
    });
    $key = random_bytes(32); $file = $scratch.'/corruption';
    file_put_contents($file, random_bytes(4 * 65536 + 23));
    cryptoCheck(myEncrypt::sealFile($file, $key));
    $cipher = file_get_contents($file); $header = substr($cipher, 0, 48); $frame = 65536 + 17;
    cryptoTest('wrong keys and damaged headers leave the exact original intact', function()use($file, $cipher, $key) {
        cryptoPreserved($file, $cipher, random_bytes(32));
        foreach([0,8,24,48,strlen($cipher)-1] as $offset) {
            $bad = $cipher; $bad[$offset] = chr(ord($bad[$offset]) ^ 1);
            cryptoPreserved($file, $bad, $key);
        }
    });
    cryptoTest('truncation including complete final-frame removal is rejected', function()use($file, $cipher, $key, $frame) {
        foreach([0,7,47,48,49,48+$frame,48+4*$frame,strlen($cipher)-1] as $length) cryptoPreserved($file, substr($cipher, 0, $length), $key);
    });
    cryptoTest('removed duplicated and reordered authenticated chunks are rejected', function()use($file, $cipher, $key, $header, $frame) {
        $chunks = str_split(substr($cipher, 48), $frame);
        $bad = $chunks; array_splice($bad, 1, 1); cryptoPreserved($file, $header.implode('', $bad), $key);
        $bad = $chunks; array_splice($bad, 1, 0, [$chunks[1]]); cryptoPreserved($file, $header.implode('', $bad), $key);
        $bad = $chunks; $bad[1] = $chunks[2]; $bad[2] = $chunks[1]; cryptoPreserved($file, $header.implode('', $bad), $key);
    });
    cryptoTest('bytes appended after a final record are rejected', function()use($scratch) {
        $key = random_bytes(32); $file = $scratch.'/trailing';
        foreach([1,65536] as $size) {
            file_put_contents($file, str_repeat('x', $size)); cryptoCheck(myEncrypt::sealFile($file, $key));
            $cipher = file_get_contents($file);
            cryptoPreserved($file, $cipher.'x', $key);
            cryptoPreserved($file, $cipher.str_repeat('x', 65553), $key);
        }
    });
    cryptoTest('locked files and symbolic links are refused without modifying targets', function()use($scratch) {
        $file = $scratch.'/locked'; file_put_contents($file, 'keep');
        $locked = fopen($file, 'rb'); flock($locked, LOCK_SH);
        try { cryptoCheck(myEncrypt::sealFile($file, 'key') === false); }
        finally { flock($locked, LOCK_UN); fclose($locked); }
        symlink($file, $scratch.'/link');
        cryptoCheck(myEncrypt::sealFile($scratch.'/link', 'key') === false);
        cryptoCheck(file_get_contents($file) === 'keep');
    });
    cryptoTest('string and file formats cannot be confused and no old-format fallback occurs', function()use($scratch) {
        $file = $scratch.'/formats'; $key = random_bytes(32);
        cryptoPreserved($file, myEncrypt::seal('string', $key), $key);
        cryptoPreserved($file, str_repeat('0', 32).'old-format-data', $key);
        file_put_contents($file, 'file'); cryptoCheck(myEncrypt::sealFile($file, $key));
        cryptoCheck(myEncrypt::open(file_get_contents($file), $key) === false);
    });
    cryptoTest('file output is independently readable using native Sodium primitives', function()use($scratch) {
        $file = $scratch.'/native'; $key = random_bytes(32); $plain = random_bytes(131089);
        file_put_contents($file, $plain); cryptoCheck(myEncrypt::sealFile($file, $key));
        $bytes = file_get_contents($file); $header = substr($bytes, 0, 48);
        $derived = hash_hkdf('sha256', $key, 32, 'MyStepFW file seal v1', substr($header, 8, 16));
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull(substr($header, 24), $derived);
        $parts = str_split(substr($bytes, 48), 65553); $opened = '';
        foreach($parts as $index=>$part) {
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $part, $header);
            cryptoCheck($result !== false); $opened .= $result[0];
            cryptoCheck($result[1] === ($index === count($parts)-1 ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE));
        }
        cryptoCheck($opened === $plain);
    });
    cryptoTest('a 24 MiB file round trips below a 16 MiB PHP memory limit', function()use($scratch) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-d', 'memory_limit=16M', __FILE__, '--large', $scratch.'/large'], [1=>['pipe','w'], 2=>['pipe','w']], $pipes);
        cryptoCheck(is_resource($process));
        $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
        foreach($pipes as $pipe) fclose($pipe);
        cryptoCheck(proc_close($process) === 0, $stderr);
        $result = json_decode($stdout, true);
        cryptoCheck($result['bytes'] === 24 * 1048576 && $result['peak'] < 16 * 1048576, 'Memory budget exceeded');
    });
    cryptoCheck(glob($scratch.'/.ms-crypt-*') === [], 'Unreleased staging files');
} finally {
    restore_error_handler();
    foreach(new FilesystemIterator($scratch) as $entry) unlink($entry->getPathname());
    rmdir($scratch);
}
echo "Passed: $passed; Failed: $failed\n";
exit($failed ? 1 : 0);
