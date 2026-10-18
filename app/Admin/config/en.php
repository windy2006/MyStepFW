<?php
/** 复用框架配置结构，独立应用入口不重复声明旧后台路径。 */
$detail=include APP.'myStep/config/en.php';
unset($detail['gen']['list']['path_admin']);
return $detail;
