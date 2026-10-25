<?PHP
/**
 * 文件：plugin/redis/config/construction.php (new)
 * 功能说明：myConfig生成的表单类型索引，包括密码保留及开关类型处理。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由config/default.php自动生成，字段调整后框架会重建，不直接保存连接密码。
 */
$construction = array (
  'text' =>
  array (
    0 => 'host',
    1 => 'port',
    2 => 'database',
    3 => 'username',
    4 => 'prefix',
    5 => 'site_id',
    6 => 'timeout',
    7 => 'read_timeout',
    8 => 'cafile',
    9 => 'lock_wait',
    10 => 'lock_ttl',
    11 => 'max_bytes',
  ),
  'radio' =>
  array (
    0 => 'auth',
    1 => 'tls',
  ),
  'password' =>
  array (
    0 => 'password',
    'func' =>
    array (
      'password' => '_',
    ),
  ),
);
return $construction;
