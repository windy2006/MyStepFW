<?php
if($_POST['setting']['web']['path_admin']!==$config->web->path_admin) {
    if(file_exists(APP.$app.'/config_'.$config->web->path_admin.'.php')) {
        rename(APP.$app.'/config_'.$config->web->path_admin.'.php', APP.$app.'/config_'.$_POST['setting']['web']['path_admin'].'.php');
    }
    $tmp = $s->web->path_admin ?? '';
    $s->web->path_admin = $_POST['setting']['web']['path_admin'];
    $router->remove(CONFIG.'route.php', $app);
    $router->checkRoute(CONFIG.'route.php', APP.$app.'/route.php', $app);
    $s->web->path_admin = $tmp;
}