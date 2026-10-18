<?php
for($i=1;$i<=5;$i++) {
    $tpl_sub->setLoop('list', [
        'link'=>myStep::setURL('test/detail/'.$i),
        'name'=>'链接 '.$i
    ]);
}