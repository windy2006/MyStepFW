<?php
/**
 * 文件：plugin/redis/info.php (new)
 * 功能说明：Redis插件展示信息；元数据扫描不连接服务、不启动会话。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由插件发现和管理流程读取，idx与目录及plugin_redis后缀一致。
 */
global $mystep;
$mystep->setLanguagePack(__DIR__.'/language/',$mystep->setting->gen->language);
return ['name'=>$mystep->getLanguage('plugin_redis_name'),'idx'=>'redis','ver'=>'1.0.1',
    'intro'=>$mystep->getLanguage('plugin_redis_intro'),'copyright'=>$mystep->getLanguage('plugin_redis_copyright'),
    'description'=>$mystep->getLanguage('plugin_redis_description')];
