<?php
/**
 * 文件：plugin/redis/config/chs.php (new)
 * 功能说明：Redis插件连接、认证、TLS和会话锁的表单定义。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由myConfig::build生成表单，输入规则由框架checkForm处理，保存前仍执行服务端验证。
 */
return array(
    'name' => 'Redis 设置',
    'list' => [
        'host' => array(
            'name' => '服务器',
            'describe' => 'Redis服务器主机名或IP，不含协议和端口',
            'type' => array('text',false,'253')
        ),
        'port' => array(
            'name' => '端口',
            'describe' => 'Redis端口，1至65535',
            'type' => array('text','digital','5')
        ),
        'database' => array(
            'name' => '逻辑数据库',
            'describe' => 'Redis逻辑库编号；缓存和会话使用不同用途前缀',
            'type' => array('text','digital','5')
        ),
        'auth' => array(
            'name' => '启用认证',
            'describe' => '服务需要密码时开启；关闭不清除已保存密码',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'username' => array(
            'name' => 'ACL用户名',
            'describe' => 'Redis 6及以上可指定ACL用户；默认用户可留空',
            'type' => array('text',false,'128')
        ),
        'password' => array(
            'name' => '访问密码',
            'describe' => '留空保留原密码；无需认证时关闭启用认证',
            'name2' => '重复密码',
            'type' => array('password','_','256')
        ),
        'prefix' => array(
            'name' => '键前缀',
            'describe' => '字母、数字、下划线、冒号或短横线；自动追加站点及用途隔离标识',
            'type' => array('text','[A-Za-z0-9_:-]{1,128}','128')
        ),
        'site_id' => array(
            'name' => '站点标识',
            'describe' => '留空按网站根隔离；同一项目多台服务器需要共享时填写相同标识',
            'type' => array('text',false,'128')
        ),
        'timeout' => array(
            'name' => '连接超时',
            'describe' => '秒，0.01至60，不使用无限等待',
            'type' => array('text',false,'8')
        ),
        'read_timeout' => array(
            'name' => '读写超时',
            'describe' => '秒，0.01至60；阻塞命令应使用独立客户端',
            'type' => array('text',false,'8')
        ),
        'tls' => array(
            'name' => '加密连接',
            'describe' => '启用TLS并验证服务器证书；服务端须已配置TLS',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'cafile' => array(
            'name' => 'CA证书',
            'describe' => '可选PEM证书文件，相对于网站根；留空使用系统证书',
            'type' => array('text',false,'256')
        ),
        'lock_wait' => array(
            'name' => '锁等待时间',
            'describe' => '秒，0至60；0表示立即尝试，失败不执行回调或继续会话',
            'type' => array('text',false,'8')
        ),
        'lock_ttl' => array(
            'name' => '锁租约时间',
            'describe' => '秒，1至3600，须大于最长请求或计算时间；丢失锁后拒绝会话写入',
            'type' => array('text','digital','4')
        ),
        'max_bytes' => array(
            'name' => '单条容量上限',
            'describe' => '字节，1至67108864，限制单条缓存序列化数据及会话正文',
            'type' => array('text','digital','8')
        ),
    ]
);
