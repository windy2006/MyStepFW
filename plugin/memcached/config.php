<?PHP

$setting = array();
$setting['server'] = '[{
    \"server\" : \"127.0.0.1\",
    \"port\" : \"11211\",
    \"weight\" : \"5\"
}]';
$setting['expire'] = 86400;
$setting['timeout'] = 1;
$setting['retry_interval'] = 10;
return $setting;