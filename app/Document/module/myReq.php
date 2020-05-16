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

$req = new myReq(array(
            'path' => '/',
            'prefix' => 'pre_'
        ), array(
            'id' => r::cookie('sid'),
            'name' => 'cool',
            'path' => PATH.'data/session/',
            'expire' => 30,
            'gc' => true,
            'trans_sid' => true
        ));

sess_mysql::set(array(
    'host' => '192.168.1.47:3306',
    'user' => 'root',
    'pass' => 'cfnadb!@#$%',
    'db_name' => 'mystep',
    'charset' => 'gbk',
));
sess_mysql::set(array(
    'host' => '127.0.0.1:3306',
    'user' => 'root',
    'pass' => '123456',
    'db_name' => 'mystep',
    'charset' => 'gbk',
));
//r::sessionStart('sess_mysql', true);
r::sessionStart('sess_file', true);

r::setCookie('cookie_test1', 'xxxx1', 3000);
r::setCookie('cookie_test2', 'xxxx2', 3000);
r::removeCookie('cookie_test1');

$i = r::s('sess_test2');
if(empty($i)) $i = 1;
r::s('sess_test1', 'xxxxxxx1');
r::s('sess_test2', $i+1);
r::removeSession('sess_test1');

echo "QueryString : ".r::svr('Query_String')."<br>";
echo "IP : ".r::ip()."<br>";

echo '<pre>';
var_dump($_COOKIE, $_SESSION);
echo '</pre>';
