<?php
if($_POST['setting']['gen']['path_admin']!==$s->gen->path_admin) {
    $mystep->setAddedContent('end', '<script>location.href=location.href.replace("'.ROOT_WEB.$s->gen->path_admin.'","'.ROOT_WEB.$_POST['setting']['gen']['path_admin'].'");</script>');
    $content = f::g(PATH.'menu.json');
    $content = str_replace('"'.$s->gen->path_admin, '"'.$_POST['setting']['gen']['path_admin'], $content);
    f::s(PATH.'menu.json', $content);
    $s->gen->path_admin = $_POST['setting']['gen']['path_admin'];
    $router->remove(CONFIG.'route.php', $app);
    $router->checkRoute(CONFIG.'route.php', PATH.'route.php', $app);
}