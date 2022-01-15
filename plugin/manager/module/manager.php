<?php
$paras = [
    'version' => include(CONFIG.'version.php'),
    'link'=> $mystep->setting->web->update
];
$tpl_sub->assign($paras);