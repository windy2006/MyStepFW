<?php
$config = new myConfig(PATH.'data/config/config_multi.php');
if(myReq::check('post')) {
    $config->set($_POST['setting']);
} else {
    $config->test->new_value = 'new';
    $config->text->para_1 = 'change';
    $config->choice->para_4 = !$config->choice->para_4;
}
$config->merge('./config/config.php');
$config->save('ini');

$list = $config->build(PATH.'data/config/setting_multi.php');
echo '<form method="post" action="">';
foreach($list as $v) {
    if(isset($v['idx'])) {
        echo '<b>'.$v['name'].'</b><br />'.chr(10);
    } else {
        echo $v['name'].' : '.$v['html'].' '.$v['describe'].'<br />'.chr(10);
    }
}
echo '<input type="submit">';
echo '</form>';
echo '<p>&nbsp;</p>';
echo '<p><b>para_7</b> : '.$config->text->para_7.'</p>';
echo '<p><b>JSON</b> : '.$config.'</p>';