<?PHP
$tpl_setting = array(
    "name" => 'test',
    "path" => PATH.'data/template/',
    'path_compile' => CACHE.'/template/'.$info_app['app'].'/test/'
);

$tpl_cache = array(
    'path' => CACHE.'/app/'.$info_app['app'].'/html/',
    'expire' => 5
);
//$tpl_cache = false;

if(!isset($_GET['c'])) $_GET['c'] = 'NoSet';

$tpl_test = new myTemplate($tpl_setting, $tpl_cache);

global $test, $test1, $test2, $test_if, $test_switch;
$test = rand();
$test1 = 'test1';
$test2 = array('a'=>'test2-a');
$test_if = rand(0, 9) > 5;
$test_switch = ['white', 'yellow', 'black'][array_rand(['white', 'yellow', 'black'])];
$test_obj = new stdClass();
$test_obj->name = 'o_name';
$test_obj->age = 'o_age';
$test_arr = ['name'=>'a_name', 'age'=>'a_age'];

if(!$tpl_test->checkCache()) {
    $tpl_test->assign('subject', '页面标题')
        ->assign('obj', $test_obj)
        ->assign('arr', $test_arr);
    $record = array(
        ['id'=>'1', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'2', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'3', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'4', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'5', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'6', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'7', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
        ['id'=>'8', 'url'=>'###', 'ad_text'=>'ad_text', 'name'=>'name', 'image'=>'image', 'province'=>'province', 'city'=>'City', 'tel'=>'tel', 'QQ'=>'QQ', 'expire'=>'2018.1.1', 'uid'=>'1', 'username'=>'sk'],
    );
    $tpl_test->setLoop('record', $record, true)
        ->setIf('if_show', rand(1, 10)>5)
        ->setSwitch('sw_show', rand(1, 3));

    function test_var() {
        return <<<'mytpl'
<?PHP
echo 'test1 : '. {myTemplate::var1}.'<br />';
echo 'test2 : '. {myTemplate::var2}.'<br />';
echo 'test3 : '. {myTemplate::var3}.'<br />';
?>
mytpl;
    }

    $tpl_test->regTag('test_var', 'test_var')
        ->regTag('test_loop', function(myTemplate &$tpl_test, &$att_list = array()) {
            $tpl_test_content = $tpl_test->getTemplate(PATH.'data/template/block_loop.tpl');
            list($block, $att_list['unit'], $att_list['unit_blank'])= $tpl_test->getBlock($tpl_test_content, 'loop', 'news');
            $result = <<<'mytpl'
<?PHP
$result = array(
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
            ['style'=>'style', 'catalog'=>'catalog', 'link'=>'###', 'subject'=>'subject', 'add_date'=>'0'],
        );
$n = 0;
foreach($result as $news) {
    $news['add_date'] = date('{myTemplate::time}', $news['add_date']);
    echo <<<content
 {myTemplate::unit}
content;
    if(++$n>= {myTemplate::loop}) break;
}
for(; $n< {myTemplate::loop}; $n++) {
    echo <<<content
 {myTemplate::unit_blank}
content;
}
?>
mytpl;
            return str_replace($block, $result, $tpl_test_content);
        })->regTag('test_if', function(myTemplate &$tpl_test, &$att_list = array()) {
            $tpl_test_content = $tpl_test->getTemplate(PATH.'data/template/block_if.tpl');
            list($block, $att_list['yes'], $att_list['no'])= $tpl_test->getBlock($tpl_test_content, 'if');
            $result = <<<'mytpl'
<?PHP
echo ( {myTemplate::key}) ? "{myTemplate::yes}" : "{myTemplate::no}";
?>
mytpl;
            return str_replace($block, $result, $tpl_test_content);
        })->regTag('test_switch', function(myTemplate &$tpl_test, &$att_list = array()) {
            $tpl_test_content = $tpl_test->getTemplate(PATH.'data/template/block_switch.tpl');
            list($block, $cases)= $tpl_test->getBlock($tpl_test_content, 'switch');
            $result = <<<'mytpl'
<?PHP
switch("{{myTemplate::key}}") {
mytpl;
            foreach($cases as $k => $v) {
                $k = addslashes($k);
                $v = addslashes($v);
                $result .= <<<mytpl
            
    case "{$k}":
        echo "{$v}";
        break;
mytpl;
        }
        $result .= <<<'mytpl'
        
}
?>
mytpl;
            return str_replace($block, $result, $tpl_test_content);
        })->loadSet(PATH.'data/template/setting.ini', 'pre', 1);
}
$tpl_test->render('$test,$test1', true, false);
