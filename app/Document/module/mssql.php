<?PHP
$db = new myDb('MSSQL', '192.168.1.47', 'cfna', 'cfnadb!@#$%', 'UTF-8');
$db->connect('cfna_new');
$db->selectDB('cfna');

//create
echo $db->convertSQL($db->create('my_db', '', 'db', 0)).'<br /><br />';
echo $db->convertSQL($db->create('my_table', 'my_col_1, my_col_2', 'idx', 0)).'<br /><br />';
echo $db->convertSQL($db->create('my_table', 'tbl2', 'tbl', 0)).'<br /><br />';
echo $db->convertSQL($db->create('my_table', 'my_col nvarchar(1000)', 'tbl', 0)).'<br /><br />';
debug_show($db->convertSQL($db->create('my_table', [
    'col' => [
        'id int identity(1,1)',
        'my_col_1 nvarchar(1000)',
        'my_col_2 int',
    ],

    'pri' => 'id',
    'uni' => 'my_col_2',
    'idx' => 'my_col_2',
    'charset' => 'Japanese'
], 'tbl', 0)));

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
)->order('id', 0)->limit(5, 10);

$db->build('sms_consumption_bak', array(
    'mode' => 'left',
    'field' => 'stuff_id',
    'field_join' => 'id',
))->field('dept, stuff_id, c_date, t_value, person, operator')
    ->where('consume', 'n>=', '1')->order('id', 1);


$sql = $db->select(1);
echo '<pre>';
var_dump($db->records($sql));
echo '</pre>';

echo $db->close();