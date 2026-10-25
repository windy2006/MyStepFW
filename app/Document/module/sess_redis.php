<?php
/**
 * 文件：app/Document/module/sess_redis.php
 * 功能说明：sess_redis的隔离展示及应用调用示例。
 * 方法目录：本文件不直接声明方法或函数。
 * 使用说明：由Document配合sample模板展示；不连接Redis、不替换缓存、不启动额外会话。
 */
echo 'Redis会话由框架按session.mode=sess_redis启动。<br />';
echo '同一SID持锁读取和保存，使用TTL过期；会话故障不切换存储。<br />';
// 后台选择Redis存储后，应用代码沿用原接口：
// myReq::session('editor_id', 7);
// $editor = myReq::session('editor_id');
// myReq::removeSession('editor_id');
