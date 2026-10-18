<?php
return array (
  'gen' => 
  array (
    'force_domain' => true,
  ),
  'web' => 
  array (
    'title' => '迈思内容管理系统 MyStep CMS',
    'path_admin' => 'admin_cms',
  ),
  'db' => 
  array (
    'auto' => true,
    'type' => 'mysql',
    'host' => '127.0.0.1:3306',
    'user' => 'root',
    'password' => '',
    'pconnect' => false,
    'charset' => 'utf-8',
    'name' => 'mystep_cms',
    'pre' => 'cms_',
  ),
  'content' => 
  array (
    'cat_pos' => '主导航,列表导航,自定义位置',
    'push_pos' => '首页,列表页,内容页',
    'push_mode' => '不推送,标题列表,幻灯图片',
    'upload' => 'files/',
    'get_remote_img' => true,
  ),
  'template' => 
  array (
    'name' => 'main',
    'path' => 'app/CMS/template',
    'style' => 'default',
  ),
  'session' => 
  array (
    'mode' => 'sess_mystep',
    'name' => 'MyStepCMSSession',
  ),
  'list' => 
  array (
    'txt' => 20,
    'img' => 24,
    'mix' => 10,
  ),
  'expire' => 
  array (
    'default' => 600,
    'index' => 1800,
    'catalog' => 3600,
    'tag' => 86400,
    'article' => 604800,
  ),
  'watermark' => 
  array (
    'thumb' => 1024,
    'mode' => 2,
    'txt' => 'MyStep CMS',
    'img' => 'static/images/logo.png',
    'position' => 3,
    'img_rate' => 4,
    'txt_font' => 'static/fonts/font.ttc',
    'txt_fontsize' => 12,
    'txt_fontcolor' => '#FFFFFF',
    'txt_bgcolor' => '#000000',
    'alpha' => 50,
    'credit' => 'Original From MyStep',
  ),
);
