<?PHP
myException::init(array(
    'callback_type' => E_ALL ^ E_NOTICE,
    'exit_on_error' => true
));

$xls = new MyExcel('test', 'sheet1');
$xls->addRow();
$fields = ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff', 'ggg'];
$xls->addCells($fields);
$xls->addRow();
$xls->addCells([1, 2, 3, 4, 5, 6, 7, 8]);

$xls->addSheet('sheet2', true);
$xls->addRow();
$fields = ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff', 'ggg', 'hhh'];
$xls->addCells($fields);
$xls->addRow();
$xls->addCells([1, 2, 3, 4, 5, 6, 7]);

$xls->delSheet('sheet1');

$xls->make();




