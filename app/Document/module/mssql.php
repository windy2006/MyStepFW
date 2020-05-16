<?PHP
$db = new myDb('MSSQL', '192.168.1.47', 'cfna', 'cfnadb!@#$%', 'UTF-8');
$db->connect('cfna_new');
$db->selectDB('cfna');

//select
$db->build('[reset]');
$db->build('sms_stuff_bak')->where(
    array(
        array('id', 'n<=', '20', 'and'),
        array(
            array('stuff', 'like', '复印纸', 'or'),
            array('stuff', 'like', '插线扳', 'or'),
            'and'
        ),
        array('add_user', '=', '韩丽', 'or'),
        array('buy_date', '>', '2005-1-1', 'and'),
    )
)
    ->field('*')
    ->order('id', 0)->limit(5, 10);

$db->build('sms_consumption_bak', array(
    'mode' => 'left',
    'field' => 'stuff_id',
    'field_join' => 'id',
))->where('consume', 'n>=', '1')->order('id', 1);


$sql = $db->select(1);
echo '<pre>';
var_dump($db->records($sql));
echo '</pre>';

echo $db->close();