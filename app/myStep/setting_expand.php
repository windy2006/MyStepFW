<?php
if($_POST['setting']['gen']['path_admin']!==$ms_setting->gen->path_admin) {
    $mystep->setAddedContent('end', '<script>location.href=location.href.replace("'.ROOT_WEB.$ms_setting->gen->path_admin.'","'.ROOT_WEB.$_POST['setting']['gen']['path_admin'].'");</script>');
    $content = myFile::getLocal(PATH.'menu.json');
    $content = str_replace('"'.$ms_setting->gen->path_admin, '"'.$_POST['setting']['gen']['path_admin'], $content);
    myFile::saveFile(PATH.'menu.json', $content);
    $ms_setting->gen->path_admin = $_POST['setting']['gen']['path_admin'];
    $router->remove(CONFIG.'route.php', $app);
    $router->checkRoute(CONFIG.'route.php', PATH.'route.php', $app);
}