<?PHP
$rule = array(
    array('/badReq/[any]', array('app\myStep\logCheck', 'plugin_badReq::main')),
);