<?php
$db = new myDb('simpleDB', 'simpleDB', PATH.'data/file/');

if(!$db->check()) {
	$db->create(array(
		array('id',10),
		array('idx',10),
		array('value',10),
		array('description',200)
	));
}

for($i=1;$i<10;$i++) {
	$db->insert(array(
		'id' => $i,
		'idx' => 'xxx_'.$i,
		'value' => $i%2,
		'description' => 'desc_'.$i
	), true);
}

$db->delete("value=0");

$db->update("id<6 && id>2", array('description' => 'desc_222222'));

$records = $db->select('id>0');
$records = $db->setOrder($records, 'id', 'desc');

echo '<pre>';
var_dump($records);
echo "\n====================\n";
var_dump($db->record(5));
echo "\n====================\n";
var_dump($db->random(2));
echo "\n====================\n";
$db->query('idx=xxx_3');
var_dump($db->getData($db->row, 'idx'));
echo "\n====================\n";
var_dump($db->result('idx=xxx_3', 'idx'));
echo '</pre>';

$db->destory();

