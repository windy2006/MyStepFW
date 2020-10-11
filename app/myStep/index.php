<?PHP
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
myStep::redirect(ROOT_WEB.$s->gen->path_admin.'/', 302);