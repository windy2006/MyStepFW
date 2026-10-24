<?PHP
/**
 * 文件：app/Document/module/myApi.php
 * 功能说明：演示接口登记、原始调用、响应编码及注销，不发送远程请求。
 * 方法目录：匿名加法接口（参数及返回值见回调注释）。
 * 使用说明：invoke只返回原始值；run返回编码文本并设置响应类型，嵌入网页时由宿主恢复HTML类型。
 */
$demoApi = new myApi();
$demoApi->regMethod('sum',
    /**
     * 功能说明：计算两个数的和，接口参数按数组遍历顺序传递。
     * @param int $left 第一个加数
     * @param int $right 第二个加数
     * @return int 两数之和
     */
    function($left, $right) { return $left + $right; }
);
$demoRaw = $demoApi->invoke('sum', [2, 3]);
$demoJson = $demoApi->run('sum', [2, 3], 'json');
$demoXml = $demoApi->run('sum', [2, 3], 'xml');
$demoApi->regMethods(['upper'=>'strtoupper']);
$demoUpper = $demoApi->invoke('upper', ['mystep']);
$demoRegistered = $demoApi->hasMethod('sum');
$demoApi->removeMethod('sum');

echo '<pre>'.htmlspecialchars(print_r([
    '原始结果（整数）'=>$demoRaw,
    'JSON响应'=>$demoJson,
    'XML响应'=>$demoXml,
    '批量登记后调用'=>$demoUpper,
    '注销前已登记'=>$demoRegistered,
    '注销后已登记'=>$demoApi->hasMethod('sum'),
    'HTTP地址示例'=>myApi::httpURL('myStep', 'error', ''),
], true), ENT_QUOTES, 'UTF-8').'</pre>';
// HTTP接口还需在应用route.php的$api中登记；启停、方法、跨域、后台权限由逐接口策略控制。
// 不在示例中调用httpError/httpPolicy/respond等可能结束当前网页请求的入口。
