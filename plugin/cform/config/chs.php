<?php
return ['name'=>'表单设置','list'=>[
 'table_prefix'=>['name'=>'独立表前缀','describe'=>'安装前确定，安装后请勿更改。','type'=>['text','','40']],
 'upload_bytes'=>['name'=>'附件字节限制','describe'=>'默认5 MiB，范围1字节至20 MiB。','type'=>['text','','10']],
 'submit_interval'=>['name'=>'提交间隔秒数','describe'=>'同一会话同一表单的提交间隔。','type'=>['text','','5']],
]];
