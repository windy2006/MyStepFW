<?php
/**
 * 文件：maintenance/sync-history.php (new)
 * 功能说明：从明确的Git发布引用生成按年份分类的更新历史，不包含未提交或未发布修改。
 * 方法目录：historyGit、historyWrite、historyText、historyChinese；详细参数见各声明。
 * 使用说明：PHP7.4 CLI；--repository=Git工作目录 --ref=origin/master [--verify-remote] [--root=网站根]。
 *           verify-remote核对origin/master与GitHub当前分支一致，不执行fetch、commit或push。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
/**
 * 功能说明：通过参数数组执行Git，只返回成功输出，避免shell插值。
 * @param string $repository Git工作目录
 * @param array $arguments Git参数
 * @return string 命令标准输出
 * @throws RuntimeException Git不可用或命令失败
 */
function historyGit($repository,array $arguments){
    $pipes=[];$process=proc_open(array_merge(['git','-C',$repository],$arguments),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if(!is_resource($process))throw new RuntimeException('无法启动Git。');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    if(proc_close($process)!==0)throw new RuntimeException('Git读取失败：'.trim($err));return $out;
}
/**
 * 功能说明：将生成文本原子发布，内容相同不写入。
 * @param string $file 受信生成目标
 * @param string $content 新文本
 * @return void 文件更新或保持不变
 * @throws RuntimeException 目录或发布失败
 */
function historyWrite($file,$content){
    if(is_file($file) && file_get_contents($file)===$content)return;
    if(!is_dir(dirname($file)) && !mkdir(dirname($file),0755,true))throw new RuntimeException('无法创建历史目录。');
    if(is_link($file))throw new RuntimeException('历史输出不能是符号链接。');
    $temp=$file.'.'.bin2hex(random_bytes(6)).'.tmp';try{if(file_put_contents($temp,$content,LOCK_EX)!==strlen($content))throw new RuntimeException('历史写入不完整。');chmod($temp,is_file($file)?fileperms($file)&0777:0644);if(!rename($temp,$file))throw new RuntimeException('历史发布失败。');}finally{if(is_file($temp))unlink($temp);}
}
/**
 * 功能说明：把提交主题转换为不会创建额外Markdown链接或HTML的文字。
 * @param string $text 原主题
 * @return string 适用于标题的转义文本
 */
function historyText($text){$text=preg_replace('/[\x00-\x1f\x7f]/',' ',$text);return str_replace(['\\','[',']','<','>','*','_','`'],['\\\\','\\[','\\]','&lt;','&gt;','\\*','\\_','\\`'],$text);}
/**
 * 功能说明：按原文完整指纹取得中文译文，中文提交直接保留分段；未翻译英文在写文件前拒绝。
 * @param string $subject Git提交主题原文
 * @param string $body Git提交说明原文
 * @param array $translations 指纹到title及items的受信中文译文
 * @return array title/items/paragraphs，标题、列表和段落分别排版
 * @throws RuntimeException 译文不完整、格式无效或存在未翻译的英文说明
 */
function historyChinese($subject,$body,array $translations){
    $key=hash('sha256',$subject."\n".trim($body));
    if(isset($translations[$key])){
        $entry=$translations[$key];
        if(!is_array($entry) || !isset($entry['title'],$entry['items']) || !is_string($entry['title']) || !is_array($entry['items']) || !preg_match('/\p{Han}/u',$entry['title']))throw new RuntimeException('历史中文译文格式错误：'.$key);
        foreach($entry['items'] as $item)if(!is_string($item) || !preg_match('/\p{Han}/u',$item) || preg_match('/[\r\n]/',$item))throw new RuntimeException('历史更新项目译文格式错误：'.$key);
        return ['title'=>$entry['title'],'items'=>$entry['items'],'paragraphs'=>[]];
    }
    $paragraphs=trim($body)===''?[]:preg_split('/\R\s*\R/u',trim($body));
    foreach(array_merge([$subject],$paragraphs) as $text)if(!preg_match('/\p{Han}/u',$text))throw new RuntimeException('提交说明尚无中文译文，请先补充maintenance/history.zh-cn.json：'.$key);
    return ['title'=>$subject,'items'=>[],'paragraphs'=>$paragraphs];
}
try{
    $args=getopt('',['repository:','ref:','root:','verify-remote']);$repository=realpath($args['repository']??'');$root=realpath($args['root']??dirname(__DIR__));$ref=$args['ref']??'origin/master';
    if(!$repository || !$root || !is_dir($root.'/docs'))throw new InvalidArgumentException('提供有效的--repository与--root。');
    $sha=trim(historyGit($repository,['rev-parse','--verify','--end-of-options',$ref.'^{commit}']));if(!preg_match('/\A[a-f0-9]{40,64}\z/',$sha))throw new RuntimeException('发布引用无效。');
    $verified=false;if(array_key_exists('verify-remote',$args)){$remote=trim(historyGit($repository,['ls-remote','--heads','origin','master']));$remoteSha=preg_split('/\s+/',$remote)[0]??'';if($sha!==$remoteSha)throw new RuntimeException('本地发布引用与远端不同，请先由发布流程更新引用后再生成历史。');$verified=true;}
    $log=historyGit($repository,['log',$sha,'--date=iso-strict','--format=%H%x00%aI%x00%s%x00%b%x1e']);$years=[];$commits=[];
    foreach(explode("\x1e",$log) as $row){$row=ltrim($row,"\r\n");if(trim($row)==='')continue;$parts=explode("\0",$row,4);if(count($parts)!==4 || !preg_match('/\A[a-f0-9]{40,64}\z/',$parts[0]))throw new RuntimeException('提交格式异常。');[$hash,$date,$subject,$body]=$parts;$year=substr($date,0,4);if(!preg_match('/\A\d{4}\z/',$year))throw new RuntimeException('提交日期异常。');$item=['sha'=>$hash,'date'=>$date,'subject'=>$subject,'body'=>trim($body)];$years[$year][]=$item;$commits[]=$item;}
    // 先验证全量译文，避免读到新的英文提交后只更新部分年份文件。
    $dictionary=json_decode(file_get_contents(__DIR__.'/history.zh-cn.json'),true,512,JSON_THROW_ON_ERROR);
    if(($dictionary['version']??null)!==1 || !is_array($dictionary['translations']??null))throw new RuntimeException('历史中文译文库不可用。');
    foreach($years as &$items)foreach($items as &$item)$item['chinese']=historyChinese($item['subject'],$item['body'],$dictionary['translations']);
    unset($item,$items);
    krsort($years);
    $catalog=[['id'=>'history-index','title'=>'更新历史','path'=>'docs/history/README.md','group'=>'更新历史']];
    $index="# GitHub更新历史\n\n本栏按真实Git提交整理，每条包含日期、中文更新说明及原始提交链接。同一版本的多次提交分别保留，以便追溯。\n\n";
    $index.='当前发布提交：[`'.substr($sha,0,10).'`](https://github.com/windy2006/MyStepFW/commit/'.$sha.")。\n\n";
    $index.="## 按年份阅读\n\n| 年份 | 提交数 |\n| --- | ---: |\n";
    foreach($years as $year=>$items){
        $file='docs/history/'.$year.'.md';
        $content='# '.$year." 年更新历史\n\n更新内容按提交逐条列出；早期英文说明已译为中文，技术名称保留原称。历史描述反映当时版本，当前行为请以功能指南为准。\n\n";
        foreach($items as $item){
            $short=substr($item['sha'],0,10);$translated=$item['chinese'];
            $content.='## '.substr($item['date'],0,10).' · '.historyText($translated['title'])."\n\n";
            $content.='[查看提交 '.$short.'](https://github.com/windy2006/MyStepFW/commit/'.$item['sha'].")\n\n";
            foreach($translated['items'] as $i=>$line)$content.=($i+1).'. '.historyText($line)."\n\n";
            foreach($translated['paragraphs'] as $paragraph){
                $lines=preg_split('/\R/u',$paragraph);
                $content.=implode("  \n",array_map('historyText',$lines))."\n\n";
            }
        }
        $content.="[更新历史目录](README.md)\n";historyWrite($root.'/'.$file,$content);
        $index.='| ['.$year.']('.$year.'.md) | '.count($items)." |\n";
        $catalog[]=['id'=>'history-'.$year,'title'=>$year.'年更新历史','path'=>$file,'group'=>'更新历史'];
    }
    $index.="\n## 记录来源\n\n以上内容由发布引用中的实际提交生成，日期与提交标识保持不变。可通过每条记录的GitHub链接核对原文。";
    $index.=($verified?'本次已核对远端分支。':'本次根据本地已有的发布引用生成。')."\n\n[文档总览](../README.md)\n";
    historyWrite($root.'/docs/history/README.md',$index);
    $record=['ref'=>$ref,'head'=>$sha,'remote_verified'=>$verified,'commits'=>count($commits),'entries'=>$catalog];historyWrite($root.'/docs/history/published.json',json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
    $manifest=$root.'/docs/catalog.json';if(is_file($manifest)){$data=json_decode(file_get_contents($manifest),true,512,JSON_THROW_ON_ERROR);$data['entries']=array_values(array_filter($data['entries'],static function($entry){return !in_array($entry['id'],['history-index','history-published','history-current'],true) && !preg_match('/\Ahistory-\d{4}\z/',$entry['id']);}));$data['entries']=array_merge($data['entries'],$catalog);historyWrite($manifest,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");}
    echo 'History: '.count($commits).' commits, '.count($years)." years; commit/push: none\n";
}catch(Throwable $error){fwrite(STDERR,$error->getMessage()."\n");exit(1);}
