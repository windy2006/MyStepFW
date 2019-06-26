<?php
/*
just put <!--lang_plugin_offical_test--> to anywhere u like
*/
return include(dirname(__FILE__).'/'.(rand(0,10)>5?'chs':'en').'.php');