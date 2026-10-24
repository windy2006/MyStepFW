<?PHP
/**
 * 文件：app/Document/module/mySecurity.php
 * 功能说明：演示请求输入预算检查及当前会话CSRF令牌校验。
 * 方法目录：无新增方法或函数。
 * 使用说明：使用合成输入，不调用guard/respond等会拒绝或结束真实请求的入口，不变更用户身份。
 */
$demoLimits = ['depth'=>4, 'nodes'=>20, 'bytes'=>128, 'body_bytes'=>1024];
$demoAllowed = mySecurity::inspectRequest(
    ['CONTENT_LENGTH'=>'12'],
    [['title'=>'示例', 'page'=>'1']],
    $demoLimits
);
$demoTooLarge = mySecurity::inspectRequest(
    ['CONTENT_LENGTH'=>'2048'],
    [[]],
    $demoLimits
);
$demoToken = mySecurity::token(); // 框架已启动会话；这里只获取现有令牌，不展示其内容。

echo '<pre>'.htmlspecialchars(print_r([
    '合法输入（空数组表示通过）'=>$demoAllowed,
    '正文超限的检查结果'=>$demoTooLarge,
    'CSRF令牌长度'=>strlen($demoToken),
    '当前会话令牌有效'=>mySecurity::valid($demoToken),
    '非法令牌被拒绝'=>!mySecurity::valid('invalid-demo-token'),
    '普通只读链接是否为修改动作'=>mySecurity::mutates('/Document/mySecurity'),
], true), ENT_QUOTES, 'UTF-8').'</pre>';
// 框架在启动时调用guardRequest/guard，在输出HTML时调用decorate。
// 自定义POST表单提交_ms_csrf，Ajax提交X-CSRF-Token；CSRF校验不代替登录及资源权限检查。
