<?PHP
/**
 * 文件：app/Document/module/mssql.php
 * 功能说明：离线展示 SQL Server 建表、索引和查询语句的生成，不连接或修改数据库。
 * 方法目录：无新增方法或函数。
 * 使用说明：预览无需 SQLSRV 扩展；实际查询需安装 sqlsrv、配置自己的连接，调用 connect 后再 records/execute。
 */
$demoDb = new myDb('MSSQL', 'localhost', 'demo', '', 'UTF-8');
echo '<p>本示例仅生成 SQL，不连接数据库。实际查询需要 PHP sqlsrv 扩展及有效的 SQL Server 连接配置。</p>';

//create
echo $demoDb->convertSQL($demoDb->create('my_db', '', 'db', 0)).'<br /><br />';
echo $demoDb->convertSQL($demoDb->create('my_table', 'my_col_1, my_col_2', 'idx', 0)).'<br /><br />';
echo $demoDb->convertSQL($demoDb->create('my_table', 'tbl2', 'tbl', 0)).'<br /><br />';
echo $demoDb->convertSQL($demoDb->create('my_table', 'my_col nvarchar(1000)', 'tbl', 0)).'<br /><br />';
debug_show($demoDb->convertSQL($demoDb->create('my_table', [
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
$demoDb->build('[reset]');
$demoDb->build('sms_stuff_bak')->where(
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

$demoDb->build('sms_consumption_bak', array(
    'mode' => 'left',
    'field' => 'stuff_id',
    'field_join' => 'id',
))->field('dept, stuff_id, c_date, t_value, person, operator')
    ->where('consume', 'n>=', '1')->order('id', 1);


$sql = $demoDb->select(1);
echo '<pre>';
echo htmlspecialchars($demoDb->convertSQL($sql), ENT_QUOTES, 'UTF-8');
echo '</pre>';

unset($demoDb);