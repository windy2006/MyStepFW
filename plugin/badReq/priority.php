<?php

/**
 * 文件：plugin/badReq/priority.php
 * 功能说明：请求过滤插件的前置执行脚本
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：在独立作用域中执行封禁、Cookie 验证、可疑请求记录和跳转。
 *   回调 2：将达到近期阈值的 IP 登记到封禁字典。
 *
 * 使用说明：
 *   1. 安装时由 setPriority 复制到 CONFIG/priority，引用源资源使用 PLUGIN 常量。
 *   2. 在独立闭包作用域中执行，早于常规应用会话与认证；封禁和重定向分支可直接退出。
 *   3. 仅活动插件的已安装前置脚本由框架执行；源文件调整后需通过安装流程重新发布复制文件。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

// 独立作用域执行优先过滤，避免覆盖框架 $setting/$url 等全局变量。
require_once PLUGIN.'interface_plugin.class.php';
require_once PLUGIN.'badReq/class.php';
(
/**
 * 功能说明：在独立作用域中执行封禁、Cookie 验证、可疑请求记录和跳转。
 *
 * 闭包依赖：global ms_setting、框架路径常量及请求环境；不启动常规 Session。
 *
 * 参数说明：无显式参数。
 *
 * @return void 正常请求返回；封禁、验证等待或跳转分支可能直接结束请求
 */
function() {
    global $ms_setting;
    $ip=myReq::ip();$list=is_file(PLUGIN.'badReq/ban_ip.php') ? include PLUGIN.'badReq/ban_ip.php' : [];
    if(is_array($list) && isset($list[$ip])) {
        $message=$list[$ip];
        if(is_string($message) && !preg_match('/[\r\n]/',$message) && filter_var($message,FILTER_VALIDATE_URL) && preg_match('#^https?://#i',$message)) {
            header('Location: '.$message,true,302);
        } else {http_response_code(403);echo htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8');}
        exit;
    }
    $setting=new myConfig(PLUGIN.'badReq/config.php');
    $ua=substr(myReq::svr('HTTP_USER_AGENT') ?? '',0,250);
    $uri=myReq::svr('REQUEST_URI') ?? '/';$parts=parse_url($uri);
    if($parts===false) return;
    $info=['path'=>substr($parts['path'] ?? '/',0,250),'query'=>substr($parts['query'] ?? '',0,250)];
    $script=($parts['path'] ?? '')===ROOT_WEB.'index.php';
    if($script && $info['query']!=='' && strpos(explode('&',$info['query'],2)[0],'=')===false) {
        $q=explode('&',$info['query'],2);$info['path']=substr($q[0],0,250);$info['query']=$q[1] ?? '';
    }
    $api=preg_match('#(?:^|/)api(?:/|$)#i',$info['path']);
    if(is_string($setting->cookie) && preg_match('/\A\w+\z/D',$setting->cookie) && stripos($ua,'bot')===false && stripos($ua,'spider')===false && !$api) {
        $key='BQ_'.$setting->cookie.'_'.date('YmdH');$value=myReq::cookie($key,'!');
        $count=is_scalar($value) ? max(0,(int)$value) : 0;myReq::setCookie($key,min(1000000,$count+1),3600);
        if($count===0) {echo '<script>setTimeout(function(){location.reload();},1000);</script>';exit;}
    }
    $flag=$ua==='' && !$api;
    // PATH_INFO及查询字符串路由本来就是合法入口，不能按“非rewrite”模式一律拦截。
    foreach(explode(',',(string)$setting->bad_chars) as $char) if($char!=='' && strpos($info['query'],$char)!==false) {$flag=true;break;}
    $ext=strtolower(pathinfo($info['path'],PATHINFO_EXTENSION));
    $allowed=array_map('strtolower',explode(',',(string)$ms_setting->gen->static));
    if(!$script && $ext!=='' && strlen($ext)<=5 && !in_array($ext,$allowed,true)) $flag=true;
    if(!$flag) return;
    $forward=is_string($setting->forward) && $setting->forward!=='' ? $setting->forward : ROOT_WEB;
    if(plugin_badReq::record($ip,$info,$ua,(int)(myReq::svr('REQUEST_TIME') ?? time()))>=3) plugin_badReq::bans(
    /**
     * 功能说明：将达到近期阈值的 IP 登记到封禁字典。
     *
     * 闭包依赖：通过 use 捕获 ip、forward。
     *
     * @param array $list 当前封禁字典
     * @return array 加入当前 IP 处理地址后的字典
     */
    function($list) use ($ip,$forward) {$list[$ip]=$forward;return $list;});
    if($setting->post && myReq::check('post')) {
        $log=$ip."\n".date('Y-m-d H:i:s')."\n".$info['path']."\n".var_export(plugin_badReq::redact($_POST),true)."\n";
        $file=CACHE.'tmp/post/'.date('Ymd').'/'.time().'.'.bin2hex(random_bytes(6)).'.txt';
        myFile::saveFile($file,substr($log,0,16384));
    }
    myStep::redirect(pluginQueryURL($forward,['wd'=>$info['path'],'q'=>$info['query']]),302);
})();
