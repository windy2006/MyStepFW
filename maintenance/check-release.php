<?php
/**
 * 文件：maintenance/check-release.php (new)
 * 功能说明：发布前检查Git索引未纳入文档备份，且未来备份路径受忽略规则保护。
 * 方法目录：releaseGit；参数见该函数PHPDoc。
 * 使用说明：PHP7.4 CLI，php maintenance/check-release.php --repository=/path/to/publish-clone。
 *           只读检查，不stage、commit、push或删除任何文件；违规返回非0。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
/**
 * 功能说明：无shell执行发布仓库的只读Git命令。
 * @param string $repository Git工作目录
 * @param array $arguments Git参数列表
 * @return string 成功输出
 * @throws RuntimeException Git命令失败
 */
function releaseGit($repository,array $arguments){$pipes=[];$p=proc_open(array_merge(['git','-C',$repository],$arguments),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))throw new RuntimeException('无法执行Git');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException(trim($err)?:'Git检查未通过');return $out;}
try{$args=getopt('',['repository:']);$repo=realpath($args['repository']??dirname(__DIR__));if(!$repo)throw new RuntimeException('仓库不存在');
    $tracked=releaseGit($repo,['ls-files','-z']);foreach(explode("\0",$tracked) as $file)if(preg_match('#\Adocs/backup(?:/|\z)#i',$file))throw new RuntimeException('Git索引包含备份，请先从索引移除：'.$file);
    foreach(['docs/backup/probe.md','docs/backup/20261023/file.md','docs/backup/archive.tar.gz'] as $file){$ignored=releaseGit($repo,['check-ignore','--no-index',$file]);if(trim($ignored)!==$file)throw new RuntimeException('备份忽略规则未生效：'.$file);}
    echo "Release check passed: docs/backup excluded from Git index and future additions.\n";
}catch(Throwable $error){fwrite(STDERR,$error->getMessage()."\n");exit(1);}
