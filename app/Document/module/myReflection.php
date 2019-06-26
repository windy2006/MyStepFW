<?PHP
$t = new myReflection('myReflection');
echo '<pre>';
echo $t->doc.chr(10).chr(10);
$t->init('curl');
var_dump($t->getFunc());
echo '</pre>';
