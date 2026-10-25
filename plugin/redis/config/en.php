<?php
/**
 * 文件：plugin/redis/config/en.php (new)
 * 功能说明：Redis插件连接、认证、TLS和会话锁的表单定义。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由myConfig::build生成表单，输入规则由框架checkForm处理，保存前仍执行服务端验证。
 */
return array(
    'name' => 'Redis Settings',
    'list' => [
        'host' => array(
            'name' => 'Host',
            'describe' => 'Hostname or IP without scheme or port',
            'type' => array('text',false,'253')
        ),
        'port' => array(
            'name' => 'Port',
            'describe' => 'Redis port, 1 to 65535',
            'type' => array('text','digital','5')
        ),
        'database' => array(
            'name' => 'Database',
            'describe' => 'Logical database index; cache and sessions have separate key scopes',
            'type' => array('text','digital','5')
        ),
        'auth' => array(
            'name' => 'Authentication',
            'describe' => 'Enable if the server requires a password; disabling retains the saved password',
            'type' => array('radio', array('Enabled'=>'true', 'Disabled'=>'false'))
        ),
        'username' => array(
            'name' => 'ACL username',
            'describe' => 'Optional Redis 6+ ACL user; leave empty for the default user',
            'type' => array('text',false,'128')
        ),
        'password' => array(
            'name' => 'Password',
            'describe' => 'Leave empty to retain the saved password; disable authentication if no password is required',
            'name2' => 'Confirm password',
            'type' => array('password','_','256')
        ),
        'prefix' => array(
            'name' => 'Key prefix',
            'describe' => 'Letters, digits, _, : and -; site and purpose scopes are added automatically',
            'type' => array('text','[A-Za-z0-9_:-]{1,128}','128')
        ),
        'site_id' => array(
            'name' => 'Site identity',
            'describe' => 'Empty isolates by site root; use the same identity on servers that should share data',
            'type' => array('text',false,'128')
        ),
        'timeout' => array(
            'name' => 'Connect timeout',
            'describe' => 'Seconds, 0.01 to 60; never unlimited',
            'type' => array('text',false,'8')
        ),
        'read_timeout' => array(
            'name' => 'Read timeout',
            'describe' => 'Seconds, 0.01 to 60; use a separate client for blocking commands',
            'type' => array('text',false,'8')
        ),
        'tls' => array(
            'name' => 'TLS',
            'describe' => 'Use TLS with server certificate verification; requires a TLS-enabled server',
            'type' => array('radio', array('Enabled'=>'true', 'Disabled'=>'false'))
        ),
        'cafile' => array(
            'name' => 'CA file',
            'describe' => 'Optional PEM CA file relative to site root; empty uses system trust',
            'type' => array('text',false,'256')
        ),
        'lock_wait' => array(
            'name' => 'Lock wait',
            'describe' => 'Seconds, 0 to 60; zero tries immediately and fails without running protected work',
            'type' => array('text',false,'8')
        ),
        'lock_ttl' => array(
            'name' => 'Lock lease',
            'describe' => 'Seconds, 1 to 3600; must exceed request/computation duration; lost leases reject session writes',
            'type' => array('text','digital','4')
        ),
        'max_bytes' => array(
            'name' => 'Payload limit',
            'describe' => 'Bytes, 1 to 67108864; limits one cache value or raw session payload',
            'type' => array('text','digital','8')
        ),
    ]
);
