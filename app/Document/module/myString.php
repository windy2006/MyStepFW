<?PHP
use myString as s;

$alias = array(
    'c' => 'charset',
    'sc' => 'setCharset',
    'hex' => 'toHex',
    's16' => 'fromHex',
    'json' => 'toJson',
    'obj' => 'fromJson',
    'str' => 'fromAny',
    'xml' => 'toXML',
    'html' => 'htmlTrans',
    'rnd' => 'RndKey',
);
s::regAlias($alias);

$str = '中国汉字abcd';
echo "<pre>";
echo $str.chr(10);
echo 'Current Charset is : '.s::c($str).chr(10);
echo 'Transfer to UTF-8 : '.s::sc($str, 'utf-8').chr(10);
echo 'Transfer to GBK : '.s::sc($str, 'gbk').chr(10);
echo 'To Hex : '.s::hex($str).chr(10);
echo 'Random String : '.s::rnd(10, 4).chr(10);
echo 'HTML String Transfer : '.s::html('<a href="http://www.baidu.com">aaa</a>').chr(10);
echo 'Break the String - UTF-8: '.s::str(s::breakStr(s::sc($str, 'utf-8'))).chr(10);
echo 'Break the String - GBK: '.s::str(s::breakStr(s::sc($str, 'gbk'))).chr(10);
echo 'substr($str, 0, 3) - UTF-8: '.s::substr(s::sc($str, 'utf-8'), 0, 3).chr(10);
echo 'substr($str, 0, 3, true) - UTF-8: '.s::substr(s::sc($str, 'utf-8'), 0, 3, true).chr(10);
echo 'substr($str, 0, 3) - GBK: '.s::substr(s::sc($str, 'gbk'), 0, 3).chr(10);
echo 'substr($str, 0, 3, true) - GBK: '.s::substr(s::sc($str, 'gbk'), 0, 3, true).chr(10);
echo 'toXML: '.s::html(s::xml(s::breakStr($str))).chr(10);
echo 'toScript: ';
$alias['xxx'] = array(
    'xxx',
    'a' => 222,
    'bbb' => [1, 2, 3, 5]
);
var_dump(s::toScript($alias, 'alias'));
var_dump(s::toIni($alias));

echo "</pre>";