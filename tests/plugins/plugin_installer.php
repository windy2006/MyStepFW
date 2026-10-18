<?php
/** 独立安装器回归：提取真实解包类与辅助函数，仅对隔离文件调整权限，不执行正式安装或业务配置清理。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=file_get_contents(dirname(__DIR__, 2).'/plugin/manager/install.php');
$a=strpos($source,'class unpacker {');$b=strpos($source,'function WriteFile(',$a);
eval(substr($source,$a,$b-$a));
eval(substr($source,$b,strpos($source,"\nsession_start(",$b)-$b));
$lng=[7=>'Files %u, bytes %s'];$passed=$failed=0;
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
$scratch=sys_get_temp_dir().'/mystep-installer-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
$scratch=realpath($scratch);
$cwd=getcwd();chdir($scratch);
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function check($v){if(!$v)throw new RuntimeException('Assertion failed');}
function runPack($content,$dir){$file=$dir.'.pack';file_put_contents($file,gzcompress($content));$u=new unpacker($dir,$file);$u->DoIt();return $file;}
try {
    test('installer template cannot run inside an installed site',function()use($scratch){
        $dir=$scratch.'/site';mkdir($dir.'/plugin/manager',0700,true);mkdir($dir.'/config');
        copy(dirname(__DIR__, 2).'/plugin/manager/install.php',$dir.'/plugin/manager/install.php');
        file_put_contents($dir.'/plugin/manager/class.php','<?php');file_put_contents($dir.'/config/config.php','installed');file_put_contents($dir.'/index.php','keep');
        $pipes=[];$p=proc_open([PHP_BINARY,$dir.'/plugin/manager/install.php'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$dir);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);check(proc_close($p)===0);check($out==='');check(file_get_contents($dir.'/index.php')==='keep');check(file_get_contents($dir.'/config/config.php')==='installed');check(!is_file($dir.'/install.php'));
    });
    test('zero-length files and binary suffix bytes are preserved',function()use($scratch){$data="x\0dir";$dir=$scratch.'/normal';runPack("dir|\nfile|empty.txt|0|1\nfile|binary.txt|".strlen($data)."|1\n".$data,$dir);check(file_get_contents($dir.'/empty.txt')==='');check(file_get_contents($dir.'/binary.txt')===$data);});
    test('headers and exact lengths work without trailing data newline',function()use($scratch){$dir=$scratch.'/consecutive';runPack("file|a.txt|1\n0file|b.txt|3\nfil",$dir);check(file_get_contents($dir.'/a.txt')==='0');check(file_get_contents($dir.'/b.txt')==='fil');});
    foreach(['../escape','/absolute','a/../../escape','C:/drive','a\\file'] as $i=>$bad)test('rejects member '.json_encode($bad),function()use($scratch,$bad,$i){try{runPack("file|ok.txt|1\nxfile|".$bad."|1\nx",$scratch.'/bad'.$i);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check(!is_file($scratch.'/bad'.$i.'/ok.txt'));}});
    test('truncation rejects before writing any member and retains package',function()use($scratch){$dir=$scratch.'/truncated';try{runPack("file|ok.txt|1\nxfile|bad.txt|20\nx",$dir);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check(!is_file($dir.'/ok.txt'));check(is_file($dir.'.pack'));}});
    test('existing symlink ancestor is rejected',function()use($scratch){$dir=$scratch.'/symlink';mkdir($dir);mkdir($scratch.'/outside');symlink($scratch.'/outside',$dir.'/link');try{runPack("file|link/out.txt|1\nx",$dir);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check(!is_file($scratch.'/outside/out.txt'));}});
    test('readonly nested directory rejects before the first member is written',function()use($scratch){
        $dir=$scratch.'/readonly';mkdir($dir);mkdir($dir.'/blocked');chmod($dir.'/blocked',0555);
        try {try{runPack("file|first.txt|1\nxfile|blocked/last.txt|1\ny",$dir);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check($e->getCode()===403);check(strpos($e->getMessage(),'blocked/last.txt')!==false);check(strpos($e->getMessage(),'Web PHP')!==false);check(!is_file($dir.'/first.txt'));check(is_file($dir.'.pack'));check((fileperms($dir.'/blocked')&0777)===0555);}}
        finally {chmod($dir.'/blocked',0755);}
    });
    test('a readonly file can be atomically replaced when its directory is writable',function()use($scratch){
        $dir=$scratch.'/replace';mkdir($dir);file_put_contents($dir.'/old.txt','old');chmod($dir.'/old.txt',0444);runPack("file|old.txt|3\nnew",$dir);clearstatcache();check(file_get_contents($dir.'/old.txt')==='new');check((fileperms($dir.'/old.txt')&0777)===0644);
    });
    test('new files and directories retain owner write permissions under a restrictive umask',function()use($scratch){
        $dir=$scratch.'/umask';file_put_contents($dir.'.pack',gzcompress("dir|nested|1\nfile|nested/a.txt|1\nx"));$old=umask(0777);
        try {(new unpacker($dir,$dir.'.pack'))->DoIt();clearstatcache();check((fileperms($dir)&0777)===0755);check((fileperms($dir.'/nested')&0777)===0755);check((fileperms($dir.'/nested/a.txt')&0777)===0644);}
        finally {umask($old);}
    });
    test('file parent conflicts are found before any member is published',function()use($scratch){
        $dir=$scratch.'/parentconflict';try{runPack("file|first.txt|1\nxfile|node/child.txt|1\nyfile|node|1\nz",$dir);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check(!is_file($dir.'/first.txt'));check(strpos($e->getMessage(),'parent is a file')!==false);}
    });
    test('readonly progress log fails before extraction and keeps the package',function()use($scratch){
        chmod('install.log',0444);$dir=$scratch.'/readonlylog';
        try {try{runPack("file|first.txt|1\nx",$dir);throw new LogicException('Expected rejection');}catch(RuntimeException $e){check($e->getCode()===403);check(strpos($e->getMessage(),'install.log')!==false);check(!is_file($dir.'/first.txt'));check(is_file($dir.'.pack'));}}
        finally {chmod('install.log',0644);}
    });
    test('caller can retain source package until post-extraction configuration succeeds',function()use($scratch){
        $dir=$scratch.'/retained';file_put_contents($dir.'.pack',gzcompress("file|a.txt|1\nx"));(new unpacker($dir,$dir.'.pack'))->DoIt('|',false);check(is_file($dir.'.pack'));check(file_get_contents($dir.'/a.txt')==='x');
    });
    test('relative archive paths remain relative to the working directory',function(){
        file_put_contents('relative.pack',gzcompress("file|a.txt|1\nx"));(new unpacker('relative-target','relative.pack'))->DoIt();check(file_get_contents('relative-target/a.txt')==='x');check(!is_file('relative.pack'));
    });
    test('installer writer verifies complete binary append overwrite and empty writes',function()use($scratch){
        $file=$scratch.'/writer/value';check(WriteFile($file,"a\0b")===3);check(WriteFile($file,'0','ab')===1);check(file_get_contents($file)==="a\0b0");check(WriteFile($file,'')===0);check(file_get_contents($file)==='');
    });
} finally {
    chdir($cwd);
    $clean=function($path)use(&$clean){if(is_file($path)||is_link($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};$clean($scratch);
}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
