<?PHP

$setting = array();
$setting['server'] = '127.0.0.1:11211';
$setting['expire'] = 86400;
$setting['persistent'] = true;
$setting['weight'] = 5;
$setting['timeout'] = 1;
$setting['retry_interval'] = 10;
return $setting;