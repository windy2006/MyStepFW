<?php
/**
 * 文件：app/Document/module/mySessionHandler.php (new)
 * 功能说明：展示PHP8.5原生会话桥接器的用途与调用方法，不启动或改动当前会话。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：由Document配合sample模板展示；实际框架启动由myReq自动选择桥接器。
 */
echo 'PHP8.5通过SessionHandlerInterface对象注册会话；原有sess_file、数据库及Redis静态处理器继续使用。<br />';
echo '六个或九个回调由mySessionHandler桥接，保留失败false、原始正文、严格ID与续期规则。<br />';
// 通常沿用框架入口：
// myReq::sessionStart('sess_file');
// 原生对象也可传入，须在输出及活动Session之前调用：
// myReq::sessionStart(new SessionHandler());
// 手动适配静态协议的基础示例（启动注册仍交给myReq）：
// $handler = new mySessionHandler(['sess_file::open','sess_file::close','sess_file::read',
//     'sess_file::write','sess_file::destroy','sess_file::gc']);
// $id = myReq::sessionStart($handler);
