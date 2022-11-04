<?PHP
$db = new myDb('sqlite', PATH.'data/sqlite.db');
$fields = $db->getFields('tbl_game');
$db->build('tbl_game')->field($fields)->where('gameid', 'n<', '10')->order('RANDOM()', 1)->limit(5, 2);
$db->select();
while($row = $db->getRS()) {
    debug_show($row['game']);
}
debug_show($fields);
$tbls = $db->getTbls();
debug_show($tbls);