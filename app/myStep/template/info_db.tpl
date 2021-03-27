<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-info-sign"></span> 数据库基本信息</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover m-0 font-sm">
<?php
$db->reconnect(0, $S->db->name);
$db_stat = array('Database Type'=>$S->db->type);
$db_stat += $db->getStat();
foreach($db_stat as $key => $value) {
$value = nl2br($value);
echo <<<mystep
<tr>
    <td width="200">{$key}</td>
    <td>{$value}</td>
</tr>
mystep;
}
?>
        </table>
    </div>
</div>