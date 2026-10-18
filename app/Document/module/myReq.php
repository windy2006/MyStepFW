<?PHP
use myReq as r;

r::regAlias(array(
            'g' => 'get',
            'p' => 'post',
            'f' => 'files',
            'r' => 'request',
            'c' => 'cookie',
            's' => 'session',
            'e' => 'env',
            'svr' => 'server',
            'gl' => 'global',
));

// 框架已经初始化请求和会话。示例不能再次 init/sessionStart，避免切换真实用户的会话。
// 如需演示独立的 sess_file/sess_mysql 处理器，请在隔离的 CLI 测试中配置。
r::setCookie('cookie_test1', 'xxxx1', 3000);
r::setCookie('cookie_test2', 'xxxx2', 3000);
r::removeCookie('cookie_test1');

$i = r::s('sess_test2');
if(empty($i)) $i = 1;
r::s('sess_test1', 'xxxxxxx1');
r::s('sess_test2', $i+1);
r::removeSession('sess_test1');

echo "QueryString : ".htmlspecialchars(r::svr('Query_String') ?? '',ENT_QUOTES,'UTF-8')."<br>";
echo "IP : ".r::ip()."<br>";

echo '<pre>';
echo htmlspecialchars(var_export(['cookie_test2'=>r::cookie('cookie_test2'),'sess_test2'=>r::s('sess_test2')],true),ENT_QUOTES,'UTF-8');
echo '</pre>';
