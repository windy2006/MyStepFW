<?php

/**
 * 文件：plugin/sample/module/sample.php
 * 功能说明：sample 插件的 sample 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 准备演示标题和框架缓存管理入口，供对应 template/sample.tpl 展示。
 *   2. 由 showPluginPage 在函数作用域中包含，使用 $tpl_sub 和框架设置 $S。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

$tpl_sub->assign('txt', '管理后台模版测试');

$tpl_sub->assign('sample_cache_url',pluginAdminURL('function/cache/'));
