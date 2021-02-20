<?PHP
/*
just put <!--lng_plugin_offical_test--> to anywhere u like
*/
return include(__DIR__.'/'.(rand(0, 10)>5?'chs':'en').'.php');