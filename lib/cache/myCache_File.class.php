<?PHP

/**
 * 文件：lib/cache/myCache_File.class.php
 * 功能说明：基于文件的缓存驱动
 *   提供V3校验帧、旧V2兼容、原子写入、精确命中、计算锁及有限预算回收。
 *
 * 方法目录：
 *   public myCache_File::__construct($path = null, array $options = []) — 初始化私有缓存根目录，不更改已有目录权限。
 *   protected myCache_File::directory($path) — 创建私有目录；竞态下已创建目录也视为成功。
 *   protected myCache_File::now() — 返回真实Unix时间，测试子类可覆盖。
 *   public myCache_File::getLastError() — 返回最近存储错误摘要，不含缓存正文。
 *   protected myCache_File::filename($key) — 将标量键映射为分片文件，沿用空namespace的MD5布局。
 *   protected myCache_File::locked($file, callable $operation, $blocking = true) — 分片写锁内执行操作；非阻塞方式返回null表示繁忙。
 *   public myCache_File::set($key, $value = '', $ttl = 600) — 写入有长度和SHA-256校验的V3帧，同目录原子替换；失败保留原值。
 *   protected myCache_File::record($file) — 读取帧元数据及有效负载；旧格式先禁用对象解码，过期帧不实例化对象。
 *   public myCache_File::fetch($key, &$hit = null) — 精确读取，命中状态独立于值；过期/损坏时尽力回收，繁忙时不阻塞。
 *   public myCache_File::get($key) — 兼容旧get，值false与未命中仍返回同一标记。
 *   protected myCache_File::expire($file) — 非阻塞锁内复查，防止删除其他进程刚发布的新值，GC不运行对象生命周期。
 *   public myCache_File::remove($key) — 删除单个键，不删除锁文件，不跟随符号链接。
 *   public myCache_File::clean() — 全量回收过期及可识别损坏条目，保留有效值和无关文件。
 *   public myCache_File::clear() — 显式删除本目录所有namespace的v2/V3条目，保留分片写锁、计算锁及无关文件。
 *   public myCache_File::collectGarbage($limit = 200) — 分片轮转回收，持久化扫描位置，避免总是只检查同一批有效文件。
 *   public myCache_File::synchronized($key, callable $operation, $timeout = 5) — 独立的256分片计算锁；不复用数据写锁，可在回调内set/remember。
 *
 * 使用说明：
 *   1. 通过new myCache('File',CACHE.'data')使用，或传入含path的选项数组。
 *   2. set/get/fetch/remove读写条目，rememberLocked经代理执行防击穿计算。
 *   3. clean仅回收过期/损坏项，clear清空有效数据但保留活动锁文件。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
require_once __DIR__.'/myCacheCodec.class.php';
/**
 * 功能说明：提供V3校验帧、旧V2兼容、原子写入、精确命中、计算锁及有限预算回收。
 *
 * 使用说明：
 *   1. 通过new myCache('File',CACHE.'data')使用，或传入含path的选项数组。
 *   2. set/get/fetch/remove读写条目，rememberLocked经代理执行防击穿计算。
 *   3. clean仅回收过期/损坏项，clear清空有效数据但保留活动锁文件。
 *
 * 补充说明：
 * 文件缓存：稳定v2路径、V3校验帧、原子发布及有限预算GC。
 * 兼容旧V2序列化条目；过期判断先于对象实例化，目录须为受信私有存储。
 * fetch可区分有效false/null，synchronized提供显式防击穿锁；clean只回收过期，clear显式清空并保留锁。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @phpstan-import-type CacheCodecOptions from myCacheCodec
 */
class myCache_File implements interface_cache, interface_cache_lookup, interface_cache_lock {
    protected $path = '', $codec, $error = '', $computing = [];

    /**
     * 功能说明：初始化私有缓存根目录，不更改已有目录权限。
     *
     * @param string|array|null $path 目录或含path及公共编解码选项的数组，null使用框架/应用临时目录（默认：null）
     * @param array $options max_bytes/max_depth/allowed_classes/namespace，覆盖数组配置中的同名项（默认：[]）
     * @return void 初始化或释放资源，无返回值。
     * @throws InvalidArgumentException 配置或根目录非法
     * @phpstan-param string|array{path?:string,max_bytes?:int,max_depth?:int,allowed_classes?:bool|list<string>,namespace?:string}|null $path
     * @phpstan-param CacheCodecOptions $options
     */
    public function __construct($path = null, array $options = []) {
        if(is_array($path)) { $options += $path; $path=$options['path']??null; }
        $this->codec=new myCacheCodec($options);
        if($path===null || $path==='') $path=defined('CACHE')?CACHE.'data':sys_get_temp_dir().'/mystep-cache-'.substr(hash('sha256',defined('ROOT')?ROOT:getcwd()),0,16);
        if(defined('ROOT')) { require_once dirname(__DIR__).'/myPath.class.php'; $path=myPath::absolute($path); }
        if(!is_string($path) || strpos($path,"\0")!==false || strpos($path,'://')!==false || is_link($path) || !$this->directory($path)) throw new InvalidArgumentException('Invalid file cache root');
        $real=realpath($path);$this->path=$real===false?'':rtrim($real,'/');
        if($this->path==='' || is_link($this->path.'/v2') || !$this->directory($this->path.'/v2')) throw new InvalidArgumentException('Cannot create cache directory');
    }

    /**
     * 功能说明：创建私有目录；竞态下已创建目录也视为成功。
     *
     * @param string $path 受信目录
     * @return bool 目录已存在或成功创建为true，创建失败为false
     */
    protected function directory($path) { return !is_link($path) && (is_dir($path) || @mkdir($path,0700,true) || is_dir($path)); }

    /**
     * 功能说明：返回真实Unix时间，测试子类可覆盖。
     *
     * 参数说明：无显式参数。
     *
     * @return int 当前Unix时间戳，单位秒
     */
    protected function now() { return time(); }

    /**
     * 功能说明：返回最近存储错误摘要，不含缓存正文。
     *
     * 参数说明：无显式参数。
     *
     * @return string 最近一次安全错误摘要；无已记录错误时为空串
     */
    public function getLastError() { return $this->error; }

    /**
     * 功能说明：将标量键映射为分片文件，沿用空namespace的MD5布局。
     *
     * @param mixed $key 有限标量或null
     * @return string|false 安全路径或被符号链接等阻断时false
     */
    protected function filename($key) {
        $hash=$this->codec->key($key);
        return myFile::extractionPath($this->path,'v2/'.substr($hash,0,2).'/'.$hash);
    }

    /**
     * 功能说明：分片写锁内执行操作；非阻塞方式返回null表示繁忙。
     *
     * @param string $file 已校验条目路径
     * @param callable $operation 无参数操作
     * @param bool $blocking 是否等待其他写操作（默认：true）
     * @return mixed 回调结果，锁/路径故障为false，繁忙为null
     */
    protected function locked($file, callable $operation, $blocking = true) {
        $lockfile=dirname($file).'/.lock';
        if(!$this->directory(dirname($file)) || is_link($lockfile)) return false;
        $lock=@fopen($lockfile,'c+b');if(!$lock)return false;
        try {
            if(!flock($lock,LOCK_EX|($blocking?0:LOCK_NB)))return $blocking?false:null;
            clearstatcache(true,$lockfile);$a=@lstat($lockfile);$b=fstat($lock);
            if(!$a||!$b||($a['mode']&0170000)!==0100000||$a['ino']!==$b['ino']||$a['dev']!==$b['dev'])return false;
            @chmod($lockfile,0600);
            return $operation();
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }

    /**
     * 功能说明：写入有长度和SHA-256校验的V3帧，同目录原子替换；失败保留原值。
     *
     * @param mixed $key 缓存键
     * @param mixed $value 可序列化受信数据，省略时删除（默认：''）
     * @param int|float $ttl 秒数，非正数删除（默认：600）
     * @return bool 原子发布成功为true；省略value或TTL非正时是remove的结果；校验/编码/存储失败为false
     * @throws Throwable 非法参数或序列化回调异常；预算超限返回false
     */
    public function set($key, $value = '', $ttl = 600) {
        if(func_num_args()<2)return $this->remove($key);
        $expire=myCacheCodec::expires($ttl,$this->now());if(!$expire)return $this->remove($key);
        $file=$this->filename($key);if(!$file)return false;
        try{$payload=$this->codec->pack($value);}catch(LengthException$e){$this->error='Cache value exceeds encoding budget.';return false;}
        $content='MS_CACHE3:'.$expire.':'.strlen($payload).':'.hash('sha256',$payload)."\n".$payload;
        $this->error='';
        $ok=$this->locked($file,function()use($file,$content){
            if(is_link($file))return false;
            $temporary=@tempnam(dirname($file),'.cache-');if($temporary===false)return false;
            try {return @file_put_contents($temporary,$content)===strlen($content)&&@chmod($temporary,0600)&&@rename($temporary,$file);}
            finally {if(is_file($temporary))@unlink($temporary);}
        });
        if(!$ok)$this->error='Cannot publish cache record.';
        return (bool)$ok;
    }

    /**
     * 功能说明：读取帧元数据及有效负载；旧格式先禁用对象解码，过期帧不实例化对象。
     *
     * @param string $file 条目路径
     * @return array|false 含expire/payload或legacy/raw；损坏/超限/读取失败为false
     */
    protected function record($file) {
        if(is_link($file))return false;
        $h=@fopen($file,'rb');if(!$h)return false;
        try {
            $stat=fstat($h);if(!$stat||($stat['mode']&0170000)!==0100000)return false;
            $head=fgets($h,160);if($head===false)return false;
            if(strpos($head,'MS_CACHE3:')===0) {
                if(!preg_match('/\AMS_CACHE3:([0-9]{1,19}):([0-9]{1,9}):([a-f0-9]{64})\n\z/D',$head,$m))return false;
                if((strlen($m[1])>strlen((string)PHP_INT_MAX)||(strlen($m[1])===strlen((string)PHP_INT_MAX)&&strcmp($m[1],(string)PHP_INT_MAX)>0)) || $stat['size']!==strlen($head)+(int)$m[2])return false;
                $entry=['expire'=>(int)$m[1]];
                if($entry['expire']<=$this->now())return $entry;
                if((int)$m[2]>$this->codec->maxBytes()){$entry['oversize']=true;return $entry;}
                $payload=stream_get_contents($h,$this->codec->maxBytes()+1);
                if($payload===false||strlen($payload)!==(int)$m[2]||!hash_equals($m[3],hash('sha256',$payload)))return false;
                $entry['payload']=$payload;return $entry;
            }
            if($stat['size']>$this->codec->maxBytes())return ['expire'=>PHP_INT_MAX,'oversize'=>true];
            rewind($h);$raw=stream_get_contents($h,$this->codec->maxBytes()+513);
            if($raw===false || strlen($raw)>$this->codec->maxBytes())return false;
            try {$entry=@unserialize($raw,['allowed_classes'=>false,'max_depth'=>$this->codec->maxDepth()+1]);}catch(Throwable$e){return false;}
            if(!is_array($entry)||($entry['version']??null)!==2||!is_int($entry['expire']??null)||!array_key_exists('value',$entry))return false;
            return ['expire'=>$entry['expire'],'legacy'=>true,'raw'=>$raw];
        }finally{fclose($h);}
    }

    /**
     * 功能说明：精确读取，命中状态独立于值；过期/损坏时尽力回收，繁忙时不阻塞。
     *
     * @param mixed $key 缓存键
     * @param bool|null &$hit 引用参数；命中输出，false/null值同样可以命中（默认：null）
     * @return mixed 命中时返回原值（含false/null），未命中/过期/损坏/后端失败为false；须结合hit判断
     */
    public function fetch($key, &$hit = null) {
        $hit=false;$this->error='';$file=$this->filename($key);
        if(!$file || !is_file($file))return false;
        $entry=$this->record($file);
        if(is_array($entry)&&$entry['expire']>$this->now()) {
            if(isset($entry['oversize']))return false;
            $value=$this->codec->unpack($entry['raw']??$entry['payload'],$valid);
            if($valid){$hit=true;return isset($entry['legacy'])?$value['value']:$value;}
        }
        $this->expire($file);return false;
    }

    /**
     * 功能说明：兼容旧get，值false与未命中仍返回同一标记。
     *
     * @param mixed $key 缓存键
     * @return mixed 有效缓存值；未命中或失败为false，不能区分已缓存的false
     */
    public function get($key) {return $this->fetch($key,$hit);}

    /**
     * 功能说明：非阻塞锁内复查，防止删除其他进程刚发布的新值，GC不运行对象生命周期。
     *
     * @param string $file 条目路径
     * @return bool 繁忙或已不存在视为跳过成功
     */
    protected function expire($file) {
        $result=$this->locked($file,function()use($file){
            clearstatcache(true,$file);if(is_link($file))return false;if(!is_file($file))return true;
            $entry=$this->record($file);
            if(is_array($entry)&&$entry['expire']>$this->now()) {
                if(isset($entry['legacy'])||isset($entry['oversize']))return true;
                try {$value=@unserialize($entry['payload'],['allowed_classes'=>false,'max_depth'=>$this->codec->maxDepth()+1]);}catch(Throwable$e){$value=false;}
                if($value!==false||$entry['payload']==='b:0;')return true;
            }
            return @unlink($file);
        },false);
        return $result===null?true:(bool)$result;
    }

    /**
     * 功能说明：删除单个键，不删除锁文件，不跟随符号链接。
     *
     * @param mixed $key 缓存键
     * @return bool 不存在也为成功
     */
    public function remove($key) {
        $file=$this->filename($key);if(!$file)return false;
        if(!is_dir(dirname($file)))return true;
        return (bool)$this->locked($file,function()use($file){return !is_link($file)&&(!is_file($file)||@unlink($file));});
    }

    /**
     * 功能说明：全量回收过期及可识别损坏条目，保留有效值和无关文件。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 全量回收是否成功；已有其他GC持锁时跳过并返回true，不表示实际回收数量
     */
    public function clean() {return $this->collectGarbage(0);}

    /**
     * 功能说明：显式删除本目录所有namespace的v2/V3条目，保留分片写锁、计算锁及无关文件。
     *
     * 每个条目在原写锁内删除；不实例化缓存对象，不递归删除目录或跟随符号链接。
     * 并发生产者可能在清理后重新发布，此操作不保证全局原子快照。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 所有已发现条目删除成功为true，路径/锁/删除失败为false
     */
    public function clear() {
        $root=$this->path.'/v2';
        if(is_link($root) || !is_dir($root)) return false;
        $ok=true;
        foreach(glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if(is_link($dir) || !preg_match('/\A[a-f0-9]{2}\z/D', basename($dir))) continue;
            try { $entries=new DirectoryIterator($dir); } catch(Throwable $e) { $ok=false; continue; }
            foreach($entries as $entry) {
                if(!preg_match('/\A[a-f0-9]{32}\z/D', $entry->getFilename())) continue;
                if($entry->isLink()) { $ok=false; continue; }
                if(!$entry->isFile()) continue;
                $file=$entry->getPathname();
                $removed=$this->locked($file, function() use ($file) {
                    clearstatcache(true, $file);
                    return !is_link($file) && (!file_exists($file) || (is_file($file) && @unlink($file)));
                });
                $ok=(bool)$removed && $ok;
            }
        }
        return $ok;
    }

    /**
     * 功能说明：分片轮转回收，持久化扫描位置，避免总是只检查同一批有效文件。
     *
     * @param int $limit 最多检查条目数，默认200；0不限，负数非法（默认：200）
     * @return bool 回收/游标更新成功为true；GC锁已忙时跳过并返回true；路径、锁或I/O失败为false
     * @throws InvalidArgumentException 回收预算不是非负整数
     */
    public function collectGarbage($limit = 200) {
        if(!is_int($limit)||$limit<0)throw new InvalidArgumentException('Invalid cache GC limit');
        $root=$this->path.'/v2';$lockfile=$root.'/.gc.lock';$cursorfile=$root.'/.gc.cursor';
        if(is_link($root)||!is_dir($root)||is_link($lockfile)||is_link($cursorfile))return false;
        $lock=@fopen($lockfile,'c+b');if(!$lock)return false;
        try {
            if(!flock($lock,LOCK_EX|LOCK_NB))return true;
            @chmod($lockfile,0600);$cursor=[];
            if(is_file($cursorfile)&&filesize($cursorfile)<16384)$cursor=json_decode(@file_get_contents($cursorfile),true)?:[];
            if(!is_array($cursor))$cursor=[];
            $dirs=glob($root.'/*',GLOB_ONLYDIR)?:[];shuffle($dirs);$checked=0;$ok=true;
            foreach($dirs as$dir) {
                $name=basename($dir);if(is_link($dir)||!preg_match('/\A[a-f0-9]{2}\z/D',$name))continue;
                try{$it=new DirectoryIterator($dir);}catch(Throwable$e){continue;}
                $offset=$limit&&isset($cursor[$name])&&is_int($cursor[$name])&&$cursor[$name]>0&&$cursor[$name]<10000000?$cursor[$name]:0;
                if($offset)try{$it->seek($offset);}catch(Throwable$e){$it->rewind();}
                while($it->valid()) {
                    if($limit&&$checked>=$limit){$cursor[$name]=$it->key();break 2;}
                    if(!$it->isLink()&&$it->isFile()&&preg_match('/\A[a-f0-9]{32}\z/D',$it->getFilename())){$checked++;$ok=$this->expire($it->getPathname())&&$ok;}
                    $it->next();
                }
                $cursor[$name]=0;
            }
            $json=json_encode($cursor);
            if($json===false||@file_put_contents($cursorfile,$json)!==strlen($json)||!@chmod($cursorfile,0600))$ok=false;
            return $ok;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }

    /**
     * 功能说明：独立的256分片计算锁；不复用数据写锁，可在回调内set/remember。
     *
     * @param mixed $key 逻辑锁键
     * @param callable $operation 无参数回调
     * @param int|float $timeout 等待秒数，0..60（默认：5）
     * @return mixed 回调结果
     * @throws RuntimeException 路径故障或锁超时
     * @throws LogicException 同适配器递归请求同一逻辑键
     * @template TResult
     * @phpstan-param callable(): TResult $operation
     * @phpstan-return TResult
     */
    public function synchronized($key, callable $operation, $timeout = 5) {
        $wait=myCacheCodec::timeout($timeout);$hash=$this->codec->key($key);$slot=substr($hash,0,2);
        if(isset($this->computing[$slot])) {
            if(isset($this->computing[$slot][$hash]))throw new LogicException('Recursive cache computation lock');
            $this->computing[$slot][$hash]=true;
            try{return $operation();}finally{unset($this->computing[$slot][$hash]);}
        }
        $root=$this->path.'/v2/.compute';$file=myFile::extractionPath($this->path,'v2/.compute/'.$slot.'.lock');
        if(!$file)throw new RuntimeException('Invalid cache computation lock path');
        if(!$this->directory($root)||is_link($file))throw new RuntimeException('Cannot open cache computation lock');
        $h=@fopen($file,'c+b');if(!$h)throw new RuntimeException('Cannot open cache computation lock');
        $deadline=microtime(true)+$wait;$acquired=false;
        try {
            do {if(flock($h,LOCK_EX|LOCK_NB)){$acquired=true;break;}if(microtime(true)>=$deadline)break;usleep(10000);}while(true);
            if(!$acquired)throw new RuntimeException('Cache computation lock timeout');
            clearstatcache(true,$file);$actual=@lstat($file);$opened=fstat($h);
            if(!$actual||!$opened||($actual['mode']&0170000)!==0100000||$actual['ino']!==$opened['ino']||$actual['dev']!==$opened['dev'])throw new RuntimeException('Cache computation lock path changed');
            @chmod($file,0600);$this->computing[$slot]=[$hash=>true];
            try{return $operation();}finally{unset($this->computing[$slot]);}
        }finally{if($acquired)flock($h,LOCK_UN);fclose($h);}
    }
}
