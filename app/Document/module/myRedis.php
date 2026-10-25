<?php
/**
 * 文件：app/Document/module/myRedis.php
 * 功能说明：myRedis的隔离展示及应用调用示例。
 * 方法目录：本文件不直接声明方法或函数。
 * 使用说明：由Document配合sample模板展示；不连接Redis、不替换缓存、不启动额外会话。
 */
$demo = new myRedis(['prefix'=>'document:', 'site_id'=>'examples']);
echo 'Redis业务键：'.htmlspecialchars($demo->key('business', 'article:42:views'), ENT_QUOTES, 'UTF-8').'<br />';
echo '连接延迟到client()调用；本页不会连接Redis。<br />';
// 在应用module中使用实际配置连接：
// $redis = new myRedis(plugin_redis::options());
// $redis->client()->incr($redis->key('business','article:42:views'));
// $redis->close();
