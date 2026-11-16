<?php
require_once __DIR__.'/class.php';
$this->regTag('ad_show',['plugin_ad_show','ad_show']);
$this->regTag('ad',['plugin_ad_show','ad_show']);

$this->addCSS(__DIR__.'/asset/display.css');
