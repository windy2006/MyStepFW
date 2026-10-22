<?php
/** PHP 7.4输入预算专项；只在内存检查小型合成数据，不发送攻击、耗尽内存或修改配置。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/lib/mySecurity.class.php';
$passed=$failed=0;
function same($a,$b){if($a!==$b)throw new RuntimeException('Unexpected result');}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function status($server=[],$input=[],$limits=[]){$result=mySecurity::inspectRequest($server,$input,$limits);return $result['status']??200;}
test('ordinary GET POST cookies and upload metadata fit the default budget',function(){same(200,status(['CONTENT_LENGTH'=>'100'],[['q'=>'中文'],['form'=>['id'=>'1']],['session'=>'example'],['file'=>['name'=>'a.txt','size'=>2,'error'=>0,'tmp_name'=>'/tmp/example']]]));});
test('declared length handles leading zeros and exact boundaries',function(){same(200,status(['CONTENT_LENGTH'=>'000128'],[],['body_bytes'=>128]));same(413,status(['CONTENT_LENGTH'=>'129'],[],['body_bytes'=>128]));same(200,status(['CONTENT_LENGTH'=>0]));});
test('very large decimal lengths do not overflow into a small value',function(){same(413,status(['CONTENT_LENGTH'=>str_repeat('9',200)]));});
test('invalid declared lengths reject arrays negatives fractions and signed strings',function(){foreach([[],false,-1,'-1','+1','1.0','1e3',' 10',"10\r\nX-Test: yes"] as $bad)same(400,status(['CONTENT_LENGTH'=>$bad]));});
test('missing length still checks fields without pretending to know stream size',function(){same(200,status([],[],['body_bytes'=>1]));same(413,status([],[['abc'=>'def']],['bytes'=>5]));});
test('all input sources share the same node budget',function(){same(200,status([],[['a'=>'1'],['b'=>'2']],['nodes'=>2]));same(413,status([],[['a'=>'1'],['b'=>'2']],['nodes'=>1]));});
test('array keys and string values both consume byte budgets',function(){same(200,status([],[['ab'=>'cd']],['bytes'=>4]));same(413,status([],[['ab'=>'cd']],['bytes'=>3]));same(413,status([],[['q'=>'中文']],['bytes'=>6]));});
test('deep arrays and cyclic references terminate at the configured boundary',function(){ $value='x';for($i=0;$i<4;$i++)$value=['n'=>$value];same(200,status([],[$value],['depth'=>4]));same(413,status([],[$value],['depth'=>2]));$cycle=[];$cycle['self']=&$cycle;same(413,status([],[$cycle],['depth'=>4]));});
test('objects are rejected without invoking user callbacks',function(){ $GLOBALS['request_string_calls']=0;$value=new class{public function __toString(){$GLOBALS['request_string_calls']++;return 'unsafe';}};same(413,status([],[['v'=>$value]]));same(0,$GLOBALS['request_string_calls']);});
test('the inspector does not consume streams or modify request arrays',function(){ $value=[['s'=>'0','n'=>null,'b'=>false,'i'=>0]];$before=$value;same(200,status([],$value));same($before,$value);});
test('trusted budget configuration rejects unknown keys and invalid values',function(){foreach([['depth'=>65],['nodes'=>0],['bytes'=>-1],['body_bytes'=>'32M'],['unknown'=>1],['bytes'=>INF]] as $limits){try{status([],[],$limits);throw new LogicException('Expected configuration failure');}catch(InvalidArgumentException $e){}}});
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
