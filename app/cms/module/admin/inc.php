<?PHP
// Admin Path
$path_admin = $s->web->path_admin;
if(defined('URL_FIX')) {
    $path_admin = preg_replace('#^'.URL_FIX.'#', '', $path_admin);
}
$path_admin = myStep::setURL($path_admin.'/');
