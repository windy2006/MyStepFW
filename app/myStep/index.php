<?php
define('PATH', myFile::realPath(dirname(__FILE__)));
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
myStep::redirect(str_replace(myFile::rootPath(),'/',ROOT).'index.php?manager/',302);