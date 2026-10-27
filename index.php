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

require_once(LIB.'myStep.class.php');
myStep::init();
