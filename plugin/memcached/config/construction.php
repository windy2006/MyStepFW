<?PHP

$construction = array();

$construction['textarea'] = array();
$construction['textarea'][0] = 'server';

$construction['text'] = array();
$construction['text'][0] = 'expire';
$construction['text'][1] = 'timeout';
$construction['text'][2] = 'retry_interval';

$construction['radio'] = array();
$construction['radio'][0] = 'persistent';
return $construction;