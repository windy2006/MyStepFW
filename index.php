<?php
if(version_compare(PHP_VERSION, '7.0.0', '<'))
    die('MyStep Framework can only run under PHP 7.0 or upper version!');

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
