<?php
return ['name'=>'插件设置','list'=>[
'interval'=>['name'=>'检查间隔','describe'=>'10至86400秒；CLI循环也使用此值','type'=>['text','','8']],
'batch'=>['name'=>'每轮任务上限','describe'=>'1至20','type'=>['text','','8']],
]];
