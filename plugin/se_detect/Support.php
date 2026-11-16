<?php
/** 插件内独立服务：锁定读改写、原子发布、通用管理视图；不依赖其他移植插件。 */
final class PSeDetectSupport {
    public const ID='se_detect';
    private const HEADER="<?php http_response_code(404); exit; ?>\n";
    public static function active(): bool {return isset(myStep::activePlugins()[self::ID]);}
    public static function admin(): void {
        $user=$GLOBALS['ms_setting']->value('gen.s_usr','');
        if(!self::active()||!is_string($user)||$user===''||myReq::session('ms_user')!==$user||myReq::session('sysop')!=='y'){http_response_code(403);throw new DomainException('需要已启用插件及框架管理员权限');}
    }
    public static function post(): void {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');throw new DomainException('此操作只接受POST');}
        // 框架start中的mySecurity::guard已验证并移除CSRF表单参数。
    }
    public static function directory(): string {
        $dir=CONFIG.'plugins/'.self::ID;
        foreach([CONFIG.'plugins',$dir] as $p){if(is_link($p))throw new RuntimeException('拒绝符号链接数据目录');if(!is_dir($p)&&!mkdir($p,0700,true)&&!is_dir($p))throw new RuntimeException('无法建立插件数据目录');}
        return $dir;
    }
    /** 只在安装或明确业务写入时创建目录；无数据的只读访问返回默认状态。 */
    public static function state(?callable $change=null): array {
        $dir=CONFIG.'plugins/'.self::ID;
        $empty=['format'=>1,'next_id'=>1,'items'=>[],'logs'=>[],'enabled'=>true];
        if($change===null&&!is_dir($dir))return $empty;
        $dir=self::directory();$lock=$dir.'/state.lock';$file=$dir.'/state.php';
        if(is_link($lock)||is_link($file))throw new RuntimeException('拒绝符号链接数据文件');
        $fp=fopen($lock,'c+b');if(!$fp)throw new RuntimeException('无法打开插件锁');$tmp='';
        try{
            if(!flock($fp,$change?LOCK_EX:LOCK_SH))throw new RuntimeException('无法锁定插件数据');
            $data=$empty;
            if(is_file($file)){
                if(filesize($file)>16777216)throw new RuntimeException('插件数据超过16MiB预算，请先导出归档');
                $raw=file_get_contents($file);
                if(!str_starts_with($raw,self::HEADER))throw new UnexpectedValueException('插件数据格式损坏');
                $data=json_decode(substr($raw,strlen(self::HEADER)),true,64,JSON_THROW_ON_ERROR);
                self::shape($data);
            }
            if($change){
                $change($data);self::shape($data);$bytes=self::HEADER.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
                if(strlen($bytes)>16777216)throw new RuntimeException('插件数据超过16MiB预算，请先导出归档');
                $tmp=$dir.'/.'.bin2hex(random_bytes(12)).'.tmp';
                if(file_put_contents($tmp,$bytes,LOCK_EX)!==strlen($bytes))throw new RuntimeException('保存插件数据失败');
                chmod($tmp,0600);if(!rename($tmp,$file))throw new RuntimeException('发布插件数据失败');$tmp='';
            }return $data;
        }finally{if($tmp!==''&&is_file($tmp))unlink($tmp);flock($fp,LOCK_UN);fclose($fp);}
    }
    /**
     * 功能说明：在读写两端校验存储结构，损坏文件或无效回调不得覆盖原数据。
     * @param mixed $data 待验证的完整状态，包含format、next_id、enabled、items、logs。
     * @return void 验证成功无返回值。
     * @throws UnexpectedValueException 状态类型、字段类型或记录编号不合法。
     */
    private static function shape(mixed $data): void {
        if(!is_array($data)||($data['format']??null)!==1||!is_int($data['next_id']??null)||$data['next_id']<1||!is_bool($data['enabled']??null)||!is_array($data['items']??null)||!is_array($data['logs']??null))throw new UnexpectedValueException('插件数据结构损坏');
        foreach($data['items']as $row){
            if(!is_array($row))throw new UnexpectedValueException('插件记录结构损坏');
            if(isset($row['id'])&&(!is_int($row['id'])||$row['id']<1||$row['id']>=$data['next_id']))throw new UnexpectedValueException('插件编号状态损坏');
        }
    }
    /** 统计集合按最近时间保留，并以字节预算兜底；不清理业务配置。 */
    public static function bound(array &$state,array $keys,int $budget=8388608): void {
        foreach($keys as $key){if(!isset($state[$key]))continue;if($key==='daily')ksort($state[$key]);else uasort($state[$key],fn($a,$b)=>($a['last_time']??0)<=>($b['last_time']??0));}
        while(strlen(json_encode($state,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))>$budget){
            $changed=false;foreach($keys as $key){$n=count($state[$key]??[]);if($n){$state[$key]=array_slice($state[$key],max(1,(int)ceil($n/10)),null,true);$changed=true;}}
            if(!$changed)throw new RuntimeException('插件配置本身超过容量预算');
        }
    }
    public static function install(): void {self::state(static function(&$s){});}
    public static function text(mixed $v,int $max=1000): string {
        if(!is_string($v)||!mb_check_encoding($v,'UTF-8')||mb_strlen($v)>$max||str_contains($v,"\0"))throw new InvalidArgumentException('文字格式或长度无效');return $v;
    }
    public static function integer(mixed $v,int $min,int $max): int {
        if(filter_var($v,FILTER_VALIDATE_INT)===false||(int)$v<$min||(int)$v>$max)throw new InvalidArgumentException('数字超出范围');return (int)$v;
    }
    public static function flag(mixed $v): bool {return in_array($v,[true,1,'1','on','true'],true);}
    public static function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    public static function url(array $params=[]): string {return pluginPageURL(myStep::setURL(self::ID.'/'),$params);}
    public static function csrf(): string {return '<input type="hidden" name="_ms_csrf" value="'.mySecurity::token().'">';}
    public static function config(): array {$c=include __DIR__.'/config.php';if(!is_array($c))throw new RuntimeException('插件配置无效');return $c;}
    public static function tpl(string $name,array $vars=[]): myTemplate {
        $tpl=new myTemplate(['name'=>$name,'path'=>__DIR__.'/template','style'=>'','path_compile'=>CACHE.'template/plugins/'.self::ID.'/'],false);
        foreach($vars as $k=>$v)$tpl->assign($k,$v);return $tpl;
    }
    public static function render(string $name,array $vars=[]): string {return self::tpl($name,$vars)->render('',false);}
    public static function error(string $text): string {return self::render('message',['message'=>$text]);}
    /** 构建表单；HTML外壳在template/editor.tpl，控件按声明字段生成。 */
    public static function editor(string $title,array $spec,array $item=[],string $action='save'): string {
        $controls='';foreach($spec as $key=>$field){$type=$field['type']??'text';$v=$item[$key]??($field['default']??'');$attrs=' name="'.self::h($key).'"'.(!empty($field['required'])?' need="" required':'');$body='';
            if($type==='checkbox')$body='<input type="checkbox"'.$attrs.' value="1"'.(self::flag($v)?' checked':'').'>';
            elseif($type==='textarea'||$type==='code')$body='<textarea class="form-control"'.$attrs.' rows="'.($type==='code'?12:4).'"'.($type==='code'?' spellcheck="false"':'').'>'.self::h($v).'</textarea>';
            elseif($type==='select'){$body='<select class="form-control"'.$attrs.'>';foreach($field['options'] as $val=>$label)$body.='<option value="'.self::h($val).'"'.((string)$v===(string)$val?' selected':'').'>'.self::h($label).'</option>';$body.='</select>';}
            else $body='<input class="form-control" type="'.$type.'"'.$attrs.' value="'.self::h($v).'">';
            $controls.=self::render('field',['label'=>$field['label'],'control'=>$body,'help'=>$field['help']??'']);
        }
        return self::render('editor',['title'=>$title,'fields'=>$controls,'id'=>$item['id']??0,'revision'=>$item['revision']??0,'action'=>$action,'url'=>self::url(),'csrf'=>self::csrf()]);
    }
    public static function table(array $columns,array $rows): string {
        $headers='';foreach($columns as $label)$headers.='<th scope="col">'.self::h($label).'</th>';
        $body='';foreach($rows as $row){$body.='<tr>';foreach($columns as $key=>$label)$body.='<td>'.($key==='_actions'?($row[$key]??''):self::h($row[$key]??'')).'</td>';$body.='</tr>';}
        return self::render('table',['headers'=>$headers,'rows'=>$body]);
    }
    public static function actions(int|string $id,bool $edit=true,array $extra=[]): string {
        $out=$edit?'<a class="btn btn-sm btn-light" href="'.self::h(self::url(['action'=>'edit','id'=>$id])).'">编辑</a> ':'';
        foreach($extra+['delete'=>'删除'] as $action=>$label)$out.='<form method="post" action="'.self::h(self::url()).'" class="d-inline">'.self::csrf().'<input type="hidden" name="action" value="'.self::h($action).'"><input type="hidden" name="id" value="'.self::h($id).'"><button class="btn btn-sm btn-outline-secondary"'.($action==='delete'?' onclick="return confirm(\'确认删除此项？\')"':'').'>'.self::h($label).'</button></form> ';return $out;
    }
    public static function get(int $id): array {$s=self::state();$r=$s['items'][(string)$id]??null;if(!is_array($r))throw new OutOfBoundsException('记录不存在');return $r;}
    /** revision防止编辑覆盖；只能由各插件模块提交已验证白名单数据。 */
    public static function save(array $row,int $id=0,int $revision=0): int {
        self::state(function(&$s)use($row,&$id,$revision){
            if($id){$old=$s['items'][(string)$id]??null;if(!$old)throw new OutOfBoundsException('记录不存在');if(($old['revision']??0)!==$revision)throw new RuntimeException('记录已被修改，请刷新后重试',409);}
            else{$id=$s['next_id']++;}
            $s['items'][(string)$id]=array_replace($id&&isset($old)?$old:[],$row,['id'=>$id,'revision'=>$revision+1]);
        });return $id;
    }
    public static function delete(string $id): void {self::state(function(&$s)use($id){unset($s['items'][$id]);});}
    public static function shell(string $body,string $message=''): string {return self::render('manage',['body'=>$body,'message'=>$message,'url'=>self::url(),'csrf'=>self::csrf()]);}
    public static function redirect(string $message='已保存'): never {
        $_SESSION[self::ID.'_notice']=$message;header('Location: '.self::url(),true,303);exit;
    }
    public static function notice(): string {$s=(string)($_SESSION[self::ID.'_notice']??'');unset($_SESSION[self::ID.'_notice']);return $s;}
    public static function date(string $date): string {
        if($date==='')return '';if(!preg_match('/\A\d{4}-\d{2}-\d{2}\z/D',$date)||!($d=DateTimeImmutable::createFromFormat('!Y-m-d',$date))||$d->format('Y-m-d')!==$date)throw new InvalidArgumentException('日期格式应为YYYY-MM-DD');return $date;
    }
    public static function httpURL(string $url,bool $empty=true): string {
        if($url===''&&$empty)return '';
        if(preg_match('/[\x00-\x20\x7f]/',$url)||!filter_var($url,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)||parse_url($url,PHP_URL_USER)!==null||parse_url($url,PHP_URL_PASS)!==null)throw new InvalidArgumentException('需要有效HTTP/HTTPS地址（不带用户名密码）');return $url;
    }
    public static function path(): string {$r=$GLOBALS['info_app']['route']??($_SERVER['REQUEST_URI']??'/');return '/'.ltrim(explode('?',(string)$r,2)[0],'/');}
    public static function bot(string $ua): bool {return preg_match('/bot|spider|crawler|slurp|ChatGPT-User|Claude-User|GoogleOther/i',$ua)===1;}
    /** 导出包含受信代码时只允许管理员，导入不覆盖旧记录，代码项默认停用。 */
    public static function export(array $data): never {
        self::admin();self::post();while(ob_get_level())ob_end_clean();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store');header('Content-Disposition: attachment; filename="'.self::ID.'.json"');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);exit;
    }
    public static function code(string $code): string {
        try{$tokens=token_get_all('<?php '.$code,TOKEN_PARSE);}catch(ParseError){throw new InvalidArgumentException('PHP代码语法无效');}
        foreach($tokens as $i=>$token)if(is_array($token)&&($token[0]===T_CLOSE_TAG||$token[0]===T_INLINE_HTML||($i>0&&in_array($token[0],[T_OPEN_TAG,T_OPEN_TAG_WITH_ECHO],true))))throw new InvalidArgumentException('PHP代码不包含开始/结束标签');
        return $code;
    }
    /** 管理员受信PHP，和旧版相同具有服务器执行权限；在当前框架上下文运行。 */
    public static function execute(string $code): mixed {
        global $mystep,$ms_setting,$db,$cache,$info_app; $setting=$ms_setting;
        return (static function()use($code,$mystep,$ms_setting,$setting,$db,$cache,$info_app){return eval($code);})();
    }
}

/** 通用安装与后台CRUD，具体字段及业务处理留在本插件。 */
trait PSeDetectLifecycle {
    public static function check(&$result=''): bool {
        $result='PHP >=8.5.12且<9 / mbstring；数据写入私有config/plugins/se_detect，卸载保留。';
        return PHP_VERSION_ID>=80512&&PHP_VERSION_ID<90000&&extension_loaded('mbstring')&&is_writable(CONFIG);
    }
    public static function install(): void {
        $s=static::S;$s::install();
        if(method_exists(static::class,'seed'))$s::state(static function(&$state){static::seed($state);});
        try{setPriority('se_detect');if(!regPluginRoute('se_detect'))throw new RuntimeException('插件路由登记失败');addPluginLink('搜索引擎监控','se_detect');}
        catch(Throwable $e){removePluginRoute('se_detect');removePriority('se_detect');removePluginLink('se_detect');throw $e;}
    }
    public static function uninstall(): void {removePluginRoute('se_detect');removePriority('se_detect');removePluginLink('se_detect');}
    public static function main(): void {showPluginPage('se_detect','manage');}
    public static function importRows(mixed $json): int {
        $s=static::S;$data=json_decode($s::text($json,1000000),true,64,JSON_THROW_ON_ERROR);$rows=$data['items']??$data;
        if(!is_array($rows)||count($rows)>1000)throw new InvalidArgumentException('最多导入1000条记录');
        $valid=[];foreach($rows as $row){if(!is_array($row))throw new InvalidArgumentException('导入记录无效');$row=static::validate($row);if(array_key_exists('code',$row))$row['active']=false;$valid[]=$row;}
        $s::state(function(&$state)use($valid){foreach($valid as $row){$id=$state['next_id']++;$state['items'][(string)$id]=$row+['id'=>$id,'revision'=>1];}});return count($valid);
    }
    public static function crud(string $title,array $fields,array $columns): string {
        return include __DIR__.'/module/crud.php';
    }
}
