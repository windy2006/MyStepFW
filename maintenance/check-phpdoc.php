<?php
/**
 * lib PHPDoc覆盖、类型格式、目录同步及可选代码一致性检查（PHP 7.4）。
 *
 * 使用：php maintenance/check-phpdoc.php [--json] [--root=网站目录] [--compare=修改前lib目录]
 * 自检：php maintenance/check-phpdoc.php --self-test
 *
 * 只读取源码并进行词法/语法分析，不include业务文件、不连接数据库、不执行示例。
 * 结构检查不代替PHPStan等完整静态分析，也不推断每一条运行路径的真实类型。
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/**
 * 将PHP源码转为带字节偏移的词元；TOKEN_PARSE同时检查当前运行时的PHP语法。
 * @param string $source 源码字节
 * @return array 词元列表，每项为id/text/start/end/line
 */
function phpdocTokens($source) {
    $tokens = []; $offset = 0;
    foreach (token_get_all($source, TOKEN_PARSE) as $token) {
        $text = is_array($token) ? $token[1] : $token;
        $tokens[] = ['id'=>is_array($token)?$token[0]:null, 'text'=>$text,
            'start'=>$offset, 'end'=>$offset+strlen($text), 'line'=>is_array($token)?$token[2]:null];
        $offset += strlen($text);
    }
    return $tokens;
}

/**
 * 判断词元是否为可跳过的空白或注释。
 * @param int|null $id PHP词元ID，单字符词元为null
 * @return bool 属于空白/注释为true
 */
function phpdocSpace($id) { return in_array($id, [T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true); }

/**
 * 提取源码直接声明的类/接口/trait及具名方法/函数，不展开继承或trait别名。
 * @param string $source 已读入的源码
 * @return array 包含classes/methods的声明清单；offset用于定位而非修改文件
 */
function phpdocDeclarations($source) {
    $tokens=phpdocTokens($source); $count=count($tokens); $depth=0; $stack=[]; $pending=null;
    $classes=[]; $methods=[];
    for ($i=0;$i<$count;$i++) {
        $token=$tokens[$i]; $id=$token['id'];
        if (in_array($id,[T_CLASS,T_INTERFACE,T_TRAIT],true)) {
            $previous=$i-1; while($previous>=0 && phpdocSpace($tokens[$previous]['id'])) $previous--;
            if($previous>=0 && $tokens[$previous]['id']===T_DOUBLE_COLON) continue;
            $next=$i+1; while($next<$count && phpdocSpace($tokens[$next]['id'])) $next++;
            if(($tokens[$next]['id']??null)!==T_STRING) continue;
            $name=$tokens[$next]['text']; $pending=['name'=>$name,'depth'=>$depth+1];
            $start=$i; while($start>0 && in_array($tokens[$start-1]['id'],[T_WHITESPACE,T_FINAL,T_ABSTRACT],true)) $start--;
            $doc=$start>0 && $tokens[$start-1]['id']===T_DOC_COMMENT ? $tokens[$start-1]['text'] : '';
            $classes[]=['name'=>$name,'doc'=>$doc,'line'=>$token['line']];
        }
        if($id===T_FUNCTION) {
            $next=$i+1; while($next<$count && (phpdocSpace($tokens[$next]['id'])||$tokens[$next]['text']==='&')) $next++;
            $name=$tokens[$next]['text']??'';
            // PHP允许do/empty等关键字作为方法名，不能只判断T_STRING。
            if(preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D',$name)) {
                $open=$next+1; while($open<$count && $tokens[$open]['text']!=='(') $open++;
                $level=1; $square=0; $curly=0; $part=$open+1; $parts=[];
                for($end=$open+1;$end<$count;$end++) {
                    $text=$tokens[$end]['text'];
                    if($text==='(')$level++; elseif($text===')')$level--;
                    elseif($text==='[')$square++; elseif($text===']')$square--;
                    elseif($text==='{')$curly++; elseif($text==='}')$curly--;
                    if(($text===','&&$level===1&&$square===0&&$curly===0)||$level===0) {
                        $parts[]=array_slice($tokens,$part,$end-$part); $part=$end+1;
                    }
                    if($level===0) break;
                }
                $params=[];
                foreach($parts as $items) foreach($items as $position=>$item) if($item['id']===T_VARIABLE) {
                    $raw=implode('',array_column($items,'text'));
                    $params[substr($item['text'],1)]=['reference'=>strpos(implode('',array_column(array_slice($items,0,$position),'text')),'&')!==false,
                        'default'=>strpos($raw,'=')!==false]; break;
                }
                $tail=$end+1; while($tail<$count && !in_array($tokens[$tail]['text'],['{',';'],true))$tail++;
                $bodyEnd=$tail;
                if($tokens[$tail]['text']==='{') {
                    $balance=0;
                    for(;$bodyEnd<$count;$bodyEnd++) {
                        if($tokens[$bodyEnd]['text']==='{'||$tokens[$bodyEnd]['id']===T_DOLLAR_OPEN_CURLY_BRACES)$balance++;
                        elseif($tokens[$bodyEnd]['text']==='}')$balance--;
                        if($balance===0)break;
                    }
                }
                $start=$i;
                while($start>0 && in_array($tokens[$start-1]['id'],[T_WHITESPACE,T_PUBLIC,T_PROTECTED,T_PRIVATE,T_STATIC,T_FINAL,T_ABSTRACT],true))$start--;
                $doc=$start>0 && $tokens[$start-1]['id']===T_DOC_COMMENT ? $tokens[$start-1]['text'] : '';
                $owner=$stack && end($stack)['depth']===$depth ? end($stack)['name'] : null;
                $key=($owner?$owner.'::':'').$name;
                $signature=trim(substr($source,$tokens[$start]['start'],$tokens[$tail]['start']-$tokens[$start]['start']));
                $methods[]=['key'=>$key,'name'=>$name,'owner'=>$owner,'doc'=>$doc,'line'=>$token['line'],'params'=>$params,
                    'signature'=>$signature,'body'=>substr($source,$tokens[$tail]['start'],$tokens[$bodyEnd]['end']-$tokens[$tail]['start'])];
            }
        }
        if($token['text']==='{'||$id===T_DOLLAR_OPEN_CURLY_BRACES) {
            $depth++; if($pending && $pending['depth']===$depth){$stack[]=$pending;$pending=null;}
        } elseif($token['text']==='}') {
            if($stack && end($stack)['depth']===$depth)array_pop($stack); $depth--;
        }
    }
    return ['classes'=>$classes,'methods'=>$methods];
}

/**
 * 去掉PHPDoc边框，并把多行标签描述保留在同一条目。
 * @param string $doc PHPDoc原文
 * @return array 以标签名称为键、标签内容列表为值的映射
 */
function phpdocTags($doc) {
    $text=preg_replace('/\A\/\*\*|\*\/\z/','',trim($doc)); $tags=[]; $name=null; $index=0;
    foreach(preg_split('/\r\n|\n|\r/',$text) as $line) {
        $line=preg_replace('/^\s*\* ?/','',$line);
        if(preg_match('/^\s*@([\w-]+)\s*(.*)$/',$line,$match)) {
            $name=$match[1]; $tags[$name][]=$match[2]; $index=count($tags[$name])-1;
        } elseif($name!==null) $tags[$name][$index].="\n".rtrim($line);
    }
    foreach($tags as &$values)foreach($values as &$value)$value=trim($value);
    unset($values,$value); return $tags;
}

/**
 * 检查类型括号/引号配对并分割最外层联合类型，保留数组结构与callable内部的联合。
 * @param string $type 类型文本
 * @return array 包含parts及error，error为空串表示结构合法
 */
function phpdocType($type) {
    $stack=[]; $quote=''; $parts=[]; $start=0; $pairs=[')'=>'(',']'=>'[','}'=>'{','>'=>'<'];
    for($i=0,$length=strlen($type);$i<$length;$i++) {
        $char=$type[$i];
        if($quote!==''){if($char==='\\')$i++;elseif($char===$quote)$quote='';continue;}
        if($char==='\''||$char==='"'){$quote=$char;continue;}
        if(strpos('([{<',$char)!==false)$stack[]=$char;
        elseif(isset($pairs[$char])){if(!$stack||array_pop($stack)!==$pairs[$char])return ['parts'=>[],'error'=>'类型括号不配对'];}
        elseif($char==='|'&&!$stack){$parts[]=trim(substr($type,$start,$i-$start));$start=$i+1;}
    }
    $parts[]=trim(substr($type,$start));
    if($stack||$quote!=='')return ['parts'=>$parts,'error'=>'类型括号或引号未闭合'];
    if(in_array('',$parts,true))return ['parts'=>$parts,'error'=>'联合类型包含空项'];
    $lower=array_map('strtolower',$parts);
    if(count(array_unique($lower))!==count($lower))return ['parts'=>$parts,'error'=>'联合类型有重复项'];
    if(count($parts)>1&&(in_array('mixed',$lower,true)||in_array('void',$lower,true)))return ['parts'=>$parts,'error'=>'mixed/void不应与其他顶层类型重复联合'];
    if(in_array('bool',$lower,true)&&(in_array('false',$lower,true)||in_array('true',$lower,true)))return ['parts'=>$parts,'error'=>'bool与true/false重复'];
    return ['parts'=>$parts,'error'=>''];
}

/**
 * 对一个文件检查文件头、直接声明、参数、返回值、扩展类型和示例引用。
 * @param string $relative lib内相对路径
 * @param string $source PHP源码
 * @param string $filename 实际文件路径，供解析相对@example使用
 * @return array errors及该文件各项覆盖数量
 */
function phpdocCheckFile($relative,$source,$filename) {
    $errors=[]; $data=phpdocDeclarations($source); $parameters=0; $advanced=0; $examples=0; $methodHints=0;
    preg_match_all('/\/\*\*.*?\*\//s',$source,$docs); $header='';
    foreach($docs[0] as $doc)if(strpos($doc,'文件：lib/')!==false){$header=$doc;break;}
    foreach(['文件：lib/'.$relative,'功能说明：','方法目录：','使用说明：']as $section)if(strpos($header,$section)===false)$errors[]=$relative.': 文件头缺少'.$section;
    preg_match_all('/^\s*\*\s{3}(.+?) — /m',$header,$catalog);
    if(count($catalog[1])!==count($data['methods']))$errors[]=$relative.': 方法目录条数与直接声明不一致';
    $expected=[];
    foreach($data['methods'] as $method) {
        $signature=preg_replace('/\s+/',' ',$method['signature']);
        $expected[]=preg_replace('/\bfunction\s+(&\s*)?'.preg_quote($method['name'],'/').'\b/',($method['owner']?'$1'.$method['key']:'$1'.$method['name']),$signature);
    }
    if($expected!==$catalog[1])$errors[]=$relative.': 方法目录签名/默认值/顺序与源码不一致';
    foreach(array_merge($data['classes'],$data['methods']) as $declaration) {
        $where=$relative.':'.$declaration['line'].' '.($declaration['key']??$declaration['name']); $doc=$declaration['doc'];
        if($doc===''||strpos($doc,'功能说明：')===false){$errors[]=$where.': 缺少功能PHPDoc';continue;}
        $tags=phpdocTags($doc); $isMethod=isset($declaration['params']);
        if($isMethod) {
            $params=[];
            foreach($tags['param']??[]as $value) {
                if(!preg_match('/\A(.+?)\s+(&?\.{0,3}\$(\w+))\s+(.+)\z/s',$value,$match)){$errors[]=$where.': @param缺少类型/名称/说明';continue;}
                if(isset($params[$match[3]]))$errors[]=$where.': @param重复'.$match[3];
                $params[$match[3]]=[$match[1],$match[2],$match[4]];
                $valid=phpdocType($match[1]);if($valid['error']!=='')$errors[]=$where.': '.$match[3].' '.$valid['error'];
            }
            foreach($declaration['params']as $name=>$formal) {
                $parameters++;
                if(!isset($params[$name])){$errors[]=$where.': 缺少参数$'.$name;continue;}
                if($formal['reference']&&strpos($params[$name][1],'&')===false)$errors[]=$where.': 引用参数未标明&$'.$name;
                if($formal['default']&&strpos($params[$name][2],'默认')===false)$errors[]=$where.': 参数未说明默认值$'.$name;
            }
            foreach($params as $name=>$value)if(!isset($declaration['params'][$name])&&!preg_match('/func_get_arg(?:s)?\s*\(/',$declaration['body']))$errors[]=$where.': 注释包含不存在的参数$'.$name;
            if(!$declaration['params']&&!isset($params['args'])&&strpos($doc,'无显式参数')===false)$errors[]=$where.': 无参数入口缺少说明';
            $returns=$tags['return']??[];
            if(count($returns)!==1||!preg_match('/\A(\S+)\s+(.+)\z/s',$returns[0]??'',$match))$errors[]=$where.': 必须有一条含类型和说明的@return';
            else{$valid=phpdocType($match[1]);if($valid['error']!=='')$errors[]=$where.': @return '.$valid['error'];}
            if(preg_match('/\bthrow\s+new\s+\w+/',$declaration['body'])&&empty($tags['throws'])&&strpos($doc,'异常处理：')===false)$errors[]=$where.': 显式throw缺少@throws或内部捕获说明';
        }
        foreach($tags['throws']??[]as $value)if(!preg_match('/\A\S+\s+\S/s',$value))$errors[]=$where.': @throws缺少触发条件';
        foreach($tags['phpstan-param']??[]as $value) {
            $advanced++;
            if(!preg_match('/\A(.+)\s+\$(\w+)\z/s',$value,$match)||!isset($declaration['params'][$match[2]])){$errors[]=$where.': 扩展参数类型未对应真实参数';continue;}
            $valid=phpdocType($match[1]);if($valid['error']!=='')$errors[]=$where.': 扩展参数 '.$valid['error'];
        }
        foreach($tags['phpstan-return']??[]as $value){$advanced++;$valid=phpdocType($value);if($valid['error']!=='')$errors[]=$where.': 扩展返回 '.$valid['error'];}
        foreach($tags['phpstan-type']??[]as $value){$advanced++;$parts=preg_split('/\s+/',$value,2);$valid=phpdocType($parts[1]??'');if($valid['error']!=='')$errors[]=$where.': 类型别名 '.$valid['error'];}
        foreach($tags['method']??[]as $value){$methodHints++;if($isMethod||!preg_match('/\A(?:static\s+)?\S+\s+\w+\s*\(/',$value))$errors[]=$where.': @method位置或格式非法';}
        foreach($tags['example']??[]as $value){$examples++;$path=preg_split('/\s+/',$value)[0];if(strpos($path,'://')===false&&!is_file(dirname($filename).'/'.$path))$errors[]=$where.': @example文件不存在 '.$path;}
        if(strpos($doc,'调用示例：')!==false)$examples++;
    }
    return ['files'=>1,'classes'=>count($data['classes']),'methods'=>count($data['methods']),'parameters'=>$parameters,
        'advanced_types'=>$advanced,'method_hints'=>$methodHints,'examples'=>$examples,'errors'=>$errors];
}

/**
 * 去掉空白和注释后取得可执行标记，用于与外部快照比较。
 * @param string $source PHP源码
 * @return string 可执行词元的SHA-256指纹；带ID及字节长度边界，避免字典大文件的多份数组副本
 */
function phpdocExecutable($source) {
    $hash=hash_init('sha256');
    foreach(token_get_all($source,TOKEN_PARSE)as $token) {
        $id=is_array($token)?$token[0]:null;$text=is_array($token)?$token[1]:$token;
        if(!phpdocSpace($id))hash_update($hash,($id??0).':'.strlen($text).':'.$text);
    }
    return hash_final($hash);
}

/**
 * 以正反样本检查扫描器，确保遗漏、目录漂移和注释差异不会被误判。
 * @return array 通过数、失败描述及退出结果；不写夹具文件
 */
function phpdocSelfTest() {
    $source=<<<'FIXTURE'
<?php
/**
 * 文件：lib/fixture.php
 * 功能说明：检查器夹具
 * 方法目录：
 *   public Fixture::run(&$value = null) — 原样返回
 * 使用说明：仅词法读取。
 */
/** 功能说明：夹具类。 */
class Fixture {
    /**
     * 功能说明：原样返回。
     * @param mixed &$value 引用输入值（默认：null）
     * @return mixed 原值
     */
    public function run(&$value = null) { return $value; }
}
FIXTURE;
    $checks=[];$check=function($name,$ok)use(&$checks){$checks[]=['name'=>$name,'ok'=>$ok];};
    $run=function($text){return phpdocCheckFile('fixture.php',$text,__DIR__.'/fixture.php');};
    $check('完整注释及目录通过',!$run($source)['errors']);
    $check('漏参数被发现',(bool)$run(str_replace('@param mixed &$value','@param mixed &$missing',$source))['errors']);
    $check('只有返回类型被发现',(bool)$run(str_replace('@return mixed 原值','@return mixed',$source))['errors']);
    $check('目录默认值漂移被发现',(bool)$run(str_replace('Fixture::run(&$value = null)','Fixture::run(&$value = false)',$source))['errors']);
    $check('引用标记缺失被发现',(bool)$run(str_replace('@param mixed &$value','@param mixed $value',$source))['errors']);
    $check('格式错误的泛型被发现',phpdocType('array<string,int')['error']!=='');
    $check('数组结构内的联合合法',phpdocType('array{value:int|string}|false')['error']==='');
    $check('回调内的联合合法',phpdocType('(callable(string): mixed)|null')['error']==='');
    $check('重复mixed被发现',phpdocType('mixed|string')['error']!=='');
    $check('重复bool/false被发现',phpdocType('bool|false')['error']!=='');
    $check('注释差异不影响执行标记',phpdocExecutable($source)===phpdocExecutable(str_replace('原样返回。','返回原值。',$source)));
    $check('代码差异会改变执行标记',phpdocExecutable($source)!==phpdocExecutable(str_replace('return $value;','return null;',$source)));
    return ['checks'=>$checks,'passed'=>count(array_filter($checks,function($check){return $check['ok'];})),
        'errors'=>array_values(array_filter($checks,function($check){return !$check['ok'];}))];
}

$root=dirname(__DIR__);$compare=null;$json=false;$selfTest=false;
foreach(array_slice($argv,1)as $arg) {
    if($arg==='--json')$json=true;
    elseif($arg==='--self-test')$selfTest=true;
    elseif(strpos($arg,'--root=')===0)$root=substr($arg,7);
    elseif(strpos($arg,'--compare=')===0)$compare=substr($arg,10);
    elseif($arg==='--help'){echo "php maintenance/check-phpdoc.php [--json] [--root=网站目录] [--compare=修改前lib目录] [--self-test]\n";exit;}
    else{fwrite(STDERR,"未知参数：".$arg."\n");exit(2);}
}
if($selfTest){$result=phpdocSelfTest();echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";exit($result['errors']?1:0);}
$lib=rtrim($root,'/').'/lib';$report=['files'=>0,'classes'=>0,'methods'=>0,'parameters'=>0,'advanced_types'=>0,'method_hints'=>0,'examples'=>0,'compared_files'=>0,'errors'=>[]];
if(!is_dir($lib)||($compare!==null&&!is_dir($compare))){fwrite(STDERR,"lib或对比目录不存在\n");exit(2);}
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lib,FilesystemIterator::SKIP_DOTS))as $file)if(strtolower($file->getExtension())==='php')$files[]=$file->getPathname();sort($files);
if(!$files)$report['errors'][]='lib目录内未发现PHP源文件';
foreach($files as $file) {
    $relative=substr($file,strlen($lib)+1);
    try {
        $source=file_get_contents($file);$result=phpdocCheckFile($relative,$source,$file);
        foreach($result as $key=>$value)if($key==='errors')$report['errors']=array_merge($report['errors'],$value);else$report[$key]+=$value;
        if($compare!==null){$baseline=rtrim($compare,'/').'/'.$relative;
            if(!is_file($baseline))$report['errors'][]=$relative.': 对比快照缺少文件';
            elseif(phpdocExecutable(file_get_contents($baseline))!==phpdocExecutable($source))$report['errors'][]=$relative.': 可执行标记与快照不同';
            else$report['compared_files']++;
        }
    }catch(Throwable $error){$report['errors'][]=$relative.': '.$error->getMessage();}
}
if($compare!==null)foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($compare,FilesystemIterator::SKIP_DOTS))as $file)if(strtolower($file->getExtension())==='php'&&!is_file($lib.'/'.substr($file->getPathname(),strlen(rtrim($compare,'/'))+1)))$report['errors'][]='对比快照中的PHP文件已缺失：'.$file->getFilename();
if($json)echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
else{echo sprintf("PHPDoc：%d文件，%d类/接口/trait，%d方法/函数，%d参数，%d扩展类型，%d代理提示，%d示例；问题%d项\n",$report['files'],$report['classes'],$report['methods'],$report['parameters'],$report['advanced_types'],$report['method_hints'],$report['examples'],count($report['errors']));foreach($report['errors']as $error)echo 'FAIL '.$error."\n";}
exit($report['errors']?1:0);
