<?php
/**
 * 文件：plugin/redis/config_default.php (new)
 * 功能说明：Redis插件的连接和存储默认设置，供首次安装和更新时补建config.php；不保存站点密码。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由myConfig读取；实际密码属于站点私有配置，不应公开提交。
 */
return ['host'=>'127.0.0.1','port'=>6379,'database'=>0,'auth'=>false,'username'=>'','password'=>'','prefix'=>'mystep:','site_id'=>'',
    'timeout'=>1,'read_timeout'=>2,'tls'=>false,'cafile'=>'','lock_wait'=>5,'lock_ttl'=>120,'max_bytes'=>1048576];
