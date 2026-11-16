<?php
/** 受信PHP扩展代码：按应用、路径、阶段及顺序运行，不把钩子等同所有请求的finally。 */
require_once __DIR__.'/Support.php';
class plugin_xcode implements interface_plugin {
    use PXcodeLifecycle;
    protected const S=PXcodeSupport::class;
    public static function validate(array $p): array {
        $s=self::S;$r=[];foreach(['name'=>100,'app'=>80,'page'=>500,'code'=>100000]as $key=>$max)$r[$key]=$s::text($p[$key]??'',$max);
        if(trim($r['name'])==='')throw new InvalidArgumentException('需要代码名称');
        if($r['app']!==''&&!preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$r['app']))throw new InvalidArgumentException('应用标识无效');
        if($r['page']!==''&&($r['page'][0]!=='/'||preg_match('/[\x00-\x20?#]/',$r['page'])||str_contains($r['page'],'..')))throw new InvalidArgumentException('路径使用/开头，可用*通配符，不包含查询串');
        $r['http_method']=strtoupper($s::text($p['http_method']??'ALL',10));if(!isset(self::methods()[$r['http_method']]))throw new InvalidArgumentException('请求方法无效');
        $r['position']=$s::integer($p['position']??0,0,1);$r['order']=$s::integer($p['order']??0,0,9999);$r['active']=$s::flag($p['active']??false);$r['code']=$s::code($r['code']);return $r;
    }
    /**
     * 功能说明：按启停、应用、实际路径、执行阶段及HTTP方法匹配片段。
     * @param array $r 已验证的片段记录；缺少http_method的记录按ALL处理。
     * @param string $app 当前应用标识。
     * @param string $path 当前路由路径，不含查询串。
     * @param int $position 执行阶段，0为start，1为end。
     * @param string|null $method HTTP方法；为空时取当前请求方法。
     * @return bool 当前上下文是否匹配。
     */
    public static function matches(array $r,string $app,string $path,int $position,?string $method=null): bool {
        return (($r['http_method']??'ALL')==='ALL'||($r['http_method']??'ALL')===strtoupper($method??($_SERVER['REQUEST_METHOD']??'GET')))&&$r['active']&&$r['position']===$position&&($r['app']===''||$r['app']===$app)&&($r['page']===''||fnmatch($r['page'],$path));
    }
    /**
     * 功能说明：返回片段可用的HTTP方法筛选，ALL适用于所有现有页面钩子。
     * @return array<string,string> 方法标识及显示名称。
     */
    public static function methods(): array {return ['ALL'=>'全部方法','GET'=>'GET','POST'=>'POST','PUT'=>'PUT','PATCH'=>'PATCH','DELETE'=>'DELETE','HEAD'=>'HEAD','OPTIONS'=>'OPTIONS'];}
    public static function executeStage(int $position): void {
        if(!PXcodeSupport::active())return;$app=(string)($GLOBALS['info_app']['app']??'');$path=PXcodeSupport::path();
        // 不在插件管理页运行用户代码，避免坏代码阻断修复入口。
        if(preg_match('#^/(?:xcode|ad_show|se_detect|visitstat|crontab)(?:/|$)#',$path))return;
        $rows=PXcodeSupport::state()['items'];uasort($rows,fn($a,$b)=>($b['order']<=>$a['order'])?:($a['id']<=>$b['id']));
        foreach($rows as $r){if(!self::matches($r,$app,$path,$position))continue;
            try{PXcodeSupport::execute($r['code']);}
            catch(Throwable $e){PXcodeSupport::state(function(&$s)use($r,$e){$s['logs'][]=['time'=>date('c'),'id'=>$r['id'],'message'=>get_class($e).': '.mb_substr($e->getMessage(),0,1000)];$s['logs']=array_slice($s['logs'],-200);});}
        }
    }
    public static function page_start(): void {self::executeStage(0);}
    public static function page_end(): void {self::executeStage(1);}
    /**
     * 功能说明：将记录的类型及运行状态转换为后台列表的可读显示值，不修改存储。
     * @param array $row 当前分页中的记录，保留编号与操作所需字段。
     * @return array 用于后台列表的格式化记录。
     */
    public static function formatRow(array $row): array {
        $row['position']=$row['position']===0?'开始阶段':'结束阶段';return $row;
    }
    public static function manage(): string {
        return include __DIR__.'/module/page.php';
    }
}
