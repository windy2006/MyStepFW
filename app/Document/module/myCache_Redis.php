<?php
/**
 * 文件：app/Document/module/myCache_Redis.php
 * 功能说明：myCache_Redis的隔离展示及应用调用示例。
 * 方法目录：本文件不直接声明方法或函数。
 * 使用说明：由Document配合sample模板展示；不连接Redis、不替换缓存、不启动额外会话。
 */
echo 'Redis缓存通过统一myCache接口使用，false/null与未命中可精确区分。<br />';
echo '本页不替换全局缓存，不清理共享Redis。<br />';
// 先在框架参数设置中配置Redis；以下代码放在应用module内：
// $demo = new myCache('Redis', myConfig::o2a(plugin_redis::options()));
// $demo->set('article:42:published', false, 60);
// $value = $demo->fetch('article:42:published', $hit);
// $demo->remove('article:42:published');
