<?php
// 运行声明随维护分支提供；应用启动前检查，避免错误PHP版本加载核心代码。
if(!function_exists('json_decode')) die('The JSON extension is required.');
$mystep_runtime_file=__DIR__.'/config/runtime.json';
$mystep_runtime=is_file($mystep_runtime_file)?json_decode(file_get_contents($mystep_runtime_file),true):null;
if(!is_array($mystep_runtime) || !is_string($mystep_runtime['php_min']??null) || !is_string($mystep_runtime['php_max']??null)) die('Invalid framework runtime declaration: config/runtime.json');
if(version_compare(PHP_VERSION,$mystep_runtime['php_min'],'<') || version_compare(PHP_VERSION,$mystep_runtime['php_max'],'>='))
    die('MyStep Framework '.$mystep_runtime['channel'].' requires PHP >='.$mystep_runtime['php_min'].' and <'.$mystep_runtime['php_max'].'; current '.PHP_VERSION);
unset($mystep_runtime,$mystep_runtime_file);

define('ROOT', str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/');
const LIB = ROOT . 'lib/';
const APP = ROOT . 'app/';
const CACHE = ROOT . 'cache/';
const CONFIG = ROOT . 'config/';
const PLUGIN = ROOT . 'plugin/';
const STATICS = ROOT . 'static/';
const VENDOR = ROOT . 'vendor/';
const FILE = ROOT . 'files/';

// 应用和插件的独立README只读分发，共用同一个安全阅读器，不启动应用或会话。
$mystep_guide_base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/.').'/';
$mystep_guide_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$mystep_guide_route = explode('&', $_SERVER['QUERY_STRING'] ?? '', 2)[0];
$mystep_guide_url = null;
foreach(['plugin','app'] as $mystep_guide_kind) {
    $mystep_guide_entry = $mystep_guide_kind.'/guide';
    if(in_array($mystep_guide_path, [$mystep_guide_base.$mystep_guide_entry, $mystep_guide_base.$mystep_guide_entry.'/'], true)) {
        $mystep_guide_url = $mystep_guide_base.$mystep_guide_entry;
    } elseif(in_array($mystep_guide_path, [$mystep_guide_base.'index.php/'.$mystep_guide_entry, $mystep_guide_base.'index.php/'.$mystep_guide_entry.'/'], true)) {
        $mystep_guide_url = $mystep_guide_base.'index.php/'.$mystep_guide_entry;
    } elseif($mystep_guide_path === $mystep_guide_base.'index.php' && in_array($mystep_guide_route, ['/'.$mystep_guide_entry,'/'.$mystep_guide_entry.'/'], true)) {
        $mystep_guide_url = $mystep_guide_base.'index.php?/'.$mystep_guide_entry;
    } elseif($mystep_guide_kind === 'plugin' && $mystep_guide_path === $mystep_guide_base.'plugin/guide.php') {
        $mystep_guide_url = $mystep_guide_base.'plugin/guide.php';
    }
    if($mystep_guide_url === null) continue;
    if(in_array($mystep_guide_route, ['/'.$mystep_guide_entry,'/'.$mystep_guide_entry.'/'], true)) unset($_GET[$mystep_guide_route]);
    define('MYSTEP_PLUGIN_GUIDE_WEB', $mystep_guide_base);
    define('MYSTEP_PLUGIN_GUIDE_URL', $mystep_guide_url);
    define('MYSTEP_PLUGIN_GUIDE_KIND', $mystep_guide_kind);
    require PLUGIN.'guide.php';
    exit;
}
unset($mystep_guide_base, $mystep_guide_path, $mystep_guide_route, $mystep_guide_url, $mystep_guide_kind, $mystep_guide_entry);

require_once(LIB.'myStep.class.php');
myStep::init();
