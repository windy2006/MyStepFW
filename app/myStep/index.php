<?php
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
myStep::redirect(str_replace(myFile::rootPath(), '/', ROOT).'/manager/', 302);