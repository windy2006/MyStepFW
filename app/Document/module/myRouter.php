<?PHP
$router = new myRouter(['delimiter_para'=>',']);
$router->format('hex', '[a-fA-F0-9]+')
       ->rule('/test/[any]/[str]/[hex]/[yyy]/[int]', function() {
   echo '<b>rule: </b>/test/[any]/[str]/[hex]/[yyy]/[int]<br />';
   echo '<b>url: </b>/test/哈哈/string/aB123f/yyy/123456<br />';
   debug_show(func_get_args());
});

$router->check('/test/哈哈/string/aB123f/yyy/123456');

$q = '/aaa/bbb/ccc/a=111,b=222,c=333';
echo '<b>url: </b>'.$q.'<br />';
debug_show($router->parseQuery($q), $_GET);
