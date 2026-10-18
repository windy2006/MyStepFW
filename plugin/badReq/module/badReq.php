<?php

/**
 * 文件：plugin/badReq/module/badReq.php
 * 功能说明：badReq 插件的 badReq 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：在锁内登记单个 IP 的封禁信息。
 *   回调 2：在锁内解除指定 IP 的封禁。
 *
 * 使用说明：
 *   1. 处理日志筛选、分组、分页、诊断读取及封禁/解除/删除/重置。
 *   2. 由受保护的 showPluginPage 装配，使用 $tpl_sub 及必要的全局上下文；写操作限制 POST。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

global $page,$query,$count,$page_size;
$m=$info_app['path'][1] ?? r::r('m','!');$ip=r::r('ip','!');
$setting=new myConfig(__DIR__.'/../config.php');
$badDb=new myDb('SQLite',__DIR__.'/../data.db');plugin_badReq::schema($badDb);
$list=is_file(__DIR__.'/../ban_ip.php') ? include __DIR__.'/../ban_ip.php' : [];if(!is_array($list))$list=[];
if(in_array($m,['ban','unban','del','reset'],true)) {
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='POST') {http_response_code(405);header('Allow: POST');exit;}
    $ips=is_string($ip)?array_values(array_filter(array_map('trim',explode(',',$ip)))):[];
    foreach($ips as $address) if(!filter_var($address,FILTER_VALIDATE_IP)) myStep::info('error_para');
    if($m==='ban') {
        if(count($ips)!==1 || $ips[0]===myReq::ip()) myStep::info('error_para');
        $message=r::r('msg','!');if(!is_string($message))$message=$setting->forward;
        plugin_badReq::bans(
        /**
         * 功能说明：在锁内登记单个 IP 的封禁信息。
         *
         * 闭包依赖：通过 use 捕获 ips 和 message；调用前已完成 IP 与操作权限检查。
         *
         * @param array $list 当前封禁字典
         * @return array 合并指定 IP 后的字典
         */
        function($list) use ($ips,$message) {$list[$ips[0]]=$message;return $list;});
    } else {
        if($m==='unban') plugin_badReq::bans(
        /**
         * 功能说明：在锁内解除指定 IP 的封禁。
         *
         * 闭包依赖：通过 use 捕获 ips；其他 IP 的记录保持不变。
         *
         * @param array $list 当前封禁字典
         * @return array 移除指定 IP 后的字典
         */
        function($list) use ($ips) {foreach($ips as $address)unset($list[$address]);return $list;});
        if($m==='reset') $badDb->query('DELETE FROM requests');
        elseif($ips) {$badDb->build('requests')->where('ip','in',$ips);$badDb->delete();}
    }
    $badDb->close();myStep::redirect(plugin_badReq::url());
}
if($m==='detail') {
    $file=myFile::resolveWithin(CACHE.'tmp/post',r::g('file','!'));
    if(!$file || pathinfo($file,PATHINFO_EXTENSION)!=='txt') myStep::header('404');
    header('Content-Type: text/plain; charset=utf-8');echo myFile::getLocal($file);$badDb->close();exit;
}
$mystep->regTag('pages','plugin_badReq::parsePages');
$order=r::g('order');if(!in_array($order,['ip','url','req','cnt'],true))$order='req';
$order_type=r::g('order_type')==='asc'?'asc':'desc';
$keyword=r::g('keyword','!') ?? '';if(!is_string($keyword))$keyword='';
$mode=r::g('mode');if(!in_array($mode,['ip','url'],true))$mode='';
if($keyword!=='') $badDb->build('requests')->where([['ip','like',$keyword],['url','like',$keyword,'or'],['qry','like',$keyword,'or'],['ua','like',$keyword,'or']]);
else $badDb->build('requests');
if($mode!=='') {
    $other=$mode==='ip'?'url':'ip';
    $badDb->build('requests')->field($mode,'MAX('.$other.') AS '.$other,'MAX(qry) AS qry','MAX(ua) AS ua','MAX(req) AS req','SUM(cnt) AS cnt')->group($mode);
    $order='cnt';$order_type='desc';
}
$sql=$badDb->select(true);$count=$badDb->count($sql);
$page=r::g('page','int');$page_size=20;
$query=http_build_query(['mode'=>$mode,'keyword'=>$keyword,'order'=>$order,'order_type'=>$order_type],'','&',PHP_QUERY_RFC3986);
list($page_info,$record_start,$page_size)=plugin_badReq::pages($count,$page,$page_size,$query);$page=$page_info['page_current'];
// count()释放构建状态；对已经生成的SQL追加已校验排序与整数分页，保留筛选/分组。
$rows=$badDb->records($sql.' ORDER BY "'.$order.'" '.strtoupper($order_type).' LIMIT '.$page_size.' OFFSET '.$record_start);
foreach($rows ?: [] as $record) {
    $record['url2']=shortUrl($record['url']);$record['file']='';
    $files=myFile::find($record['req'].'.*',CACHE.'tmp/post/'.date('Ymd',$record['req']).'/');
    if($files) $record['file']=plugin_badReq::url(['m'=>'detail','file'=>date('Ymd',$record['req']).'/'.basename($files[0])]);
    $record['mode']=isset($list[$record['ip']])?'unban':'ban';$record['req']=date('Y-m-d H:i:s',$record['req']);
    foreach($record as &$value) $value=htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');unset($value);
    $record['delete_url']=htmlspecialchars(plugin_badReq::url(['m'=>'del','ip'=>htmlspecialchars_decode($record['ip'],ENT_QUOTES)]),ENT_QUOTES,'UTF-8');
    $tpl_sub->setLoop('record',$record);
}
$tpl_sub->assign('mode',$mode);$tpl_sub->assign('keyword',htmlspecialchars($keyword,ENT_QUOTES,'UTF-8'));
$tpl_sub->assign('order',$order);$tpl_sub->assign('order_type_org',$order_type);$tpl_sub->assign('order_type',$order_type==='asc'?'desc':'asc');
$tpl_sub->assign('msg',htmlspecialchars((string)$setting->forward,ENT_QUOTES,'UTF-8'));
$badDb->close();

$tpl_sub->assign('reset_url',htmlspecialchars(plugin_badReq::url(['m'=>'reset']),ENT_QUOTES,'UTF-8'));
$tpl_sub->assign('badreq_page_url_json',json_encode(plugin_badReq::url(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT));
foreach(['ip','url','cnt','req'] as $field) $tpl_sub->assign('order_'.$field.'_url',htmlspecialchars(plugin_badReq::url(['mode'=>$mode,'keyword'=>$keyword,'order'=>$field,'order_type'=>$order_type==='asc'?'desc':'asc']),ENT_QUOTES,'UTF-8'));

$tpl_sub->assign('badreq_list_url_json',json_encode(plugin_badReq::url(['mode'=>$mode,'keyword'=>$keyword,'order'=>$order,'order_type'=>$order_type]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT));
