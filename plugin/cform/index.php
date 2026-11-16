<?php
require_once __DIR__.'/class.php';
$this->setLanguagePack(__DIR__.'/language/', $this->setting->gen->language);
$this->regTag('cf_list', ['plugin_cform','tagList']);
$this->regModule('cf_submit', __DIR__.'/module/submit.php');
$this->regModule('cf_list', __DIR__.'/module/list.php');
$this->regUrl('cf_submit', ['plugin_cform','getUrl_submit']);
$this->regUrl('cf_list', ['plugin_cform','getUrl_list']);
