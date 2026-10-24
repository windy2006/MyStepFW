<?PHP
/********************************************
*                                           *
* Name    : My Simple Datebase              *
* Author  : Windy2000                       *
* Time    : 2005-09-15                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：lib/database/simpleDB.class.php
 * 功能说明：文件型简单数据库
 *   提供固定格式文件存储、条件匹配、CRUD、格式校验及原子并发写入。
 *
 * 方法目录：
 *   public simpleDB::__construct() — 按可选位置参数初始化，不立即创建文件
 *   public simpleDB::init($name,$path='./',$tlen=500,$max_bytes=33554432) — 关闭旧句柄并设置本地数据文件
 *   public simpleDB::connect($mode='r') — 打开现有数据文件；write/w 不再隐式截断，清空数据使用 empty
 *   public simpleDB::selectDB($name,$path='',$tlen=500) — 切换本地表并打开只读句柄
 *   public simpleDB::check() — 检查文件是否存在，不隐式创建或修复
 *   public simpleDB::close() — 安全关闭公开 connect 打开的句柄
 *   public simpleDB::__destruct() — 析构只释放当前对象句柄
 *   private simpleDB::decode($content) — 校验表头及固定记录边界，读取旧格式；损坏文件明确失败
 *   private simpleDB::encode(array $schema,array $rows) — 在任何写入之前验证所有记录并编码固定长度内容
 *   private simpleDB::access($write,callable $callback) — 在共享/排他锁内取得快照并执行操作，写前完成全部编码
 *   public simpleDB::mutate(callable $callback) — 在单个排他锁内执行批量记录变更，异常不保存
 *   public simpleDB::create($setting) — 原子独占创建空表，已存在时不截断
 *   public simpleDB::destroy() — 关闭当前表并删除文件，不与其他进程的文件替换组成事务
 *   public simpleDB::empty() — 在排他锁内清空记录，保留原字段设置
 *   private simpleDB::matcher($condition) — 将字符串比较或字段映射转换为 AND 过滤器，值中的等号保持原样
 *   public simpleDB::query($condition=[],$mode=2,$limit=1) — 查询记录，包含空条件时也按指定模式和条数返回
 *   public simpleDB::select($condition=null,$limit=null) — 读取纯记录，尊重传入条数
 *   public simpleDB::insert($data=[],$mode=false) — 在单次排他锁内追加一条或多条记录，按最新表头计算偏移
 *   public simpleDB::update($condition=null,$data=[],$mode=true) — 在同一排他锁内筛选并更新记录，空条件明确更新全部
 *   public simpleDB::delete($condition=null) — 在同一排他锁内删除并压实记录，不使用空白正则改写数据
 *   public simpleDB::record($row=0) — 按 1 起始行号读取记录，0/非数字取首行，负数取随机行，越界正数取末行
 *   public simpleDB::records($tmp='') — 读取全部纯记录
 *   public simpleDB::random($rows=1) — 无重复随机抽取记录，不通过反复碰撞重试
 *   public simpleDB::getData($line=0,$field='') — 读取某行或指定字段
 *   public simpleDB::result($condition,$field='') — 读取首个匹配项的字段，未指定字段时返回第一列
 *   public simpleDB::getFields($tmp='') — 取得旧格式字段设置及最新长度元数据
 *   public simpleDB::setOrder($result,$col='rand',$mode='asc') — 排序结果，随机模式使用 shuffle，不使用不满足传递性的随机比较器
 *
 * 使用说明：
 *   1. 适合少量本地结构化记录；通过create定义字段后读写。
 *   2. 参数采用文件/字段/条件/行号语义，不能直接套用SQL驱动参数。
 *   3. 使用destroy删除当前表文件。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：提供固定格式文件存储、条件匹配、CRUD、格式校验及原子并发写入。
 *
 * 使用说明：
 *   1. 适合少量本地结构化记录；通过create定义字段后读写。
 *   2. 参数采用文件/字段/条件/行号语义，不能直接套用SQL驱动参数。
 *   3. 使用destroy删除当前表文件。
 *
 * 补充说明：
 * 兼容旧固定长度 .db 格式的本地小表，读写在同一个文件锁内完成。
 * 字段按字节宽度保存，尾部空格作为填充；不支持 NUL/换行及事务 SQL。
 * 超长值明确报错，避免覆盖后续记录。默认文件预算 32 MiB，可在 init 第四参调整。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../../app/Document/module/simpleDB.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class simpleDB implements interface_db {
    private $name='', $path='', $file='', $tlen=500, $fp=null, $maxlen=10, $separator="\0", $max_bytes=33554432;
    public $pos=0, $row=0;

    /**
     * 功能说明：按可选位置参数初始化，不立即创建文件
     *
     * @param mixed ...$args 按init声明顺序传入的数据文件等设置（历史位置参数，由func_get_args读取）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct() { if(func_num_args())$this->init(...func_get_args()); }

    /**
     * 功能说明：关闭旧句柄并设置本地数据文件
     *
     * @param string $name 单个表名，不含路径分隔符、上级目录或 NUL
     * @param string $path 文件目录，默认当前目录（默认：'./'）
     * @param int $tlen 兼容旧表头字节数，范围 32..1048576，已有表须使用原值（默认：500）
     * @param int $max_bytes 文件内存预算，默认 32 MiB（默认：33554432）
     * @return void 无返回值。
     * @throws InvalidArgumentException 参数非法
     */
    public function init($name,$path='./',$tlen=500,$max_bytes=33554432) {
        if(!is_string($name)||!preg_match('/\A[\w-]+\z/D',$name)||!is_string($path)||strpos($path,"\0")!==false||strpos($path,'://')!==false)throw new InvalidArgumentException('Invalid simpleDB path');
        if($tlen<32||$tlen>1048576||$max_bytes<($tlen+1))throw new InvalidArgumentException('Invalid simpleDB size limit');
        if(defined('ROOT')) { require_once dirname(__DIR__).'/myPath.class.php'; $path = myPath::absolute($path); }
        $this->close();$this->name=$name;$this->path=rtrim($path,'/\\');$this->tlen=(int)$tlen;$this->max_bytes=(int)$max_bytes;$this->file=$this->path.'/'.$name.'.db';$this->pos=$this->row=0;
    }

    /**
     * 功能说明：打开现有数据文件；write/w 不再隐式截断，清空数据使用 empty
     *
     * @param string $mode read/r/rb 只读，其余历史 write/append/r+ 形式读写（默认：'r'）
     * @return bool 成功 true，文件不存在或不能打开 false
     */
    public function connect($mode='r') { $this->close();if(!$this->check())return false;$this->fp=@fopen($this->file,in_array($mode,['r','rb','read'],true)?'rb':'r+b');return is_resource($this->fp); }

    /**
     * 功能说明：切换本地表并打开只读句柄
     *
     * @param string $name 新表名，空值沿用原名
     * @param string $path 新目录，空值沿用原目录（默认：''）
     * @param int $tlen 表头长度，默认 500（默认：500）
     * @return bool 文件可读为 true
     */
    public function selectDB($name,$path='',$tlen=500) { $this->init($name===''?$this->name:$name,$path===''?$this->path:$path,$tlen,$this->max_bytes);return $this->connect(); }

    /**
     * 功能说明：检查文件是否存在，不隐式创建或修复
     *
     * 参数说明：无显式参数。
     *
     * @return bool 目标数据文件存在为true；不会创建、打开或验证文件格式
     */
    public function check() { return is_file($this->file); }

    /**
     * 功能说明：安全关闭公开 connect 打开的句柄
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function close() { if(is_resource($this->fp))fclose($this->fp);$this->fp=null; }

    /**
     * 功能说明：析构只释放当前对象句柄
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() { $this->close(); }

    /**
     * 功能说明：校验表头及固定记录边界，读取旧格式；损坏文件明确失败
     *
     * @param string $content 完整文件快照
     * @return array [字段宽度映射, 记录数组]
     * @throws UnexpectedValueException 文件长度、字段或分隔符不符合格式
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private function decode($content) {
        if(strlen($content)<$this->tlen+1||$content[$this->tlen]!=="\n")throw new UnexpectedValueException('Invalid simpleDB header');
        $head=explode($this->separator,rtrim(substr($content,0,$this->tlen),' '));$schema=[];
        foreach(array_slice($head,2)as$spec){if(!preg_match('/\A(\w+):(\d+)\z/D',$spec,$m)||(int)$m[2]<1||isset($schema[$m[1]])||in_array($m[1],['mydb_max_length','mydb_rec_length','max_row','db_row','db_pos'],true))throw new UnexpectedValueException('Invalid simpleDB schema');$schema[$m[1]]=(int)$m[2];}
        $length=array_sum($schema)+count($schema)+1;
        if(!$schema||(int)($head[0]??0)!==strlen($content)||(int)($head[1]??0)!==$length||(strlen($content)-$this->tlen-1)%$length!==0)throw new UnexpectedValueException('Invalid simpleDB record length');
        $rows=[];
        for($offset=$this->tlen+1;$offset<strlen($content);$offset+=$length){$row=[];$at=$offset;foreach($schema as$key=>$width){$row[$key]=rtrim(substr($content,$at,$width),' ');$at+=$width;if($content[$at++]!==$this->separator)throw new UnexpectedValueException('Invalid simpleDB field boundary');}if($content[$at]!=="\n")throw new UnexpectedValueException('Invalid simpleDB row boundary');$rows[]=$row;}
        return [$schema,$rows];
    }

    /**
     * 功能说明：在任何写入之前验证所有记录并编码固定长度内容
     *
     * @param array $schema 字段宽度
     * @param array $rows 记录
     * @return string 完整文件内容
     * @throws LengthException 值超过字段宽度或文件预算
     * @throws InvalidArgumentException 值含 NUL/换行或不是标量/null
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private function encode(array $schema,array $rows) {
        $length=array_sum($schema)+count($schema)+1;$size=$this->tlen+1+count($rows)*$length;
        if($size>$this->max_bytes||strlen((string)$size)>$this->maxlen)throw new LengthException('simpleDB file size budget exceeded');
        $header=str_pad((string)$size,$this->maxlen).$this->separator.$length;
        foreach($schema as$key=>$width)$header.=$this->separator.$key.':'.$width;
        if(strlen($header)>$this->tlen)throw new LengthException('simpleDB header too long');
        $result=str_pad($header,$this->tlen)."\n";
        foreach($rows as$row){foreach($schema as$key=>$width){$value=$row[$key]??'';if(!is_scalar($value)&&$value!==null)throw new InvalidArgumentException('simpleDB values must be scalar');$value=(string)$value;if(strpbrk($value,"\0\r\n")!==false)throw new InvalidArgumentException('simpleDB values cannot contain NUL or line breaks');if(strlen($value)>$width)throw new LengthException('simpleDB field width exceeded: '.$key);$result.=str_pad($value,$width).$this->separator;}$result.="\n";}
        return $result;
    }

    /**
     * 功能说明：在共享/排他锁内取得快照并执行操作，写前完成全部编码
     *
     * @param bool $write true 写操作，false 只读
     * @param callable $callback 接收记录引用和字段映射，返回业务结果
     * @return mixed 文件不存在/无法打开时 false，否则回调结果
     * @throws RuntimeException 锁或读写失败；不提供断电原子性，预算适用于小型本地表
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private function access($write,callable $callback) {
        $this->close();if(!$this->check())return false;$fp=@fopen($this->file,$write?'r+b':'rb');if(!$fp)return false;
        try {
            if(!flock($fp,$write?LOCK_EX:LOCK_SH))throw new RuntimeException('Cannot lock simpleDB');
            $size=fstat($fp)['size'];if($size>$this->max_bytes)throw new LengthException('simpleDB file size budget exceeded');
            $content=stream_get_contents($fp);if($content===false)throw new RuntimeException('Cannot read simpleDB');list($schema,$rows)=$this->decode($content);
            $result=$callback($rows,$schema);
            if($write){$content=$this->encode($schema,$rows);rewind($fp);$offset=0;while($offset<strlen($content)){$n=fwrite($fp,substr($content,$offset));if($n===false||$n===0)throw new RuntimeException('Cannot write simpleDB');$offset+=$n;}if(!ftruncate($fp,strlen($content))||!fflush($fp))throw new RuntimeException('Cannot finish simpleDB write');}
            return $result;
        } finally {flock($fp,LOCK_UN);fclose($fp);}
    }

    /**
     * 功能说明：在单个排他锁内读取、变更及保存整个记录集，避免empty/insert多次操作的空表窗口。
     *
     * 回调接收引用记录数组及只读结构数组；抛异常时保留原文件，返回值原样传回调用方。
     * 返回false本身不会取消保存；需要取消时应抛异常。受信代码负责具体业务约束。
     *
     * @param callable $callback function(array &$rows, array $schema): mixed
     * @return mixed 回调结果；表不存在/打不开为false，编码、容量和I/O异常继续传递
     * @throws InvalidArgumentException 回调产生无法编码的记录
     * @throws RuntimeException 无法锁定、读取或完整写入
     */
    public function mutate(callable $callback) {
        return $this->access(true, function (&$rows, $schema) use ($callback) {
            return $callback($rows, $schema);
        });
    }

    /**
     * 功能说明：原子独占创建空表，已存在时不截断
     *
     * @param array $setting [[字段名,字节宽度],...]，字段名不能与内部元数据重名
     * @return bool 创建成功 true，文件已存在 false
     * @throws InvalidArgumentException 数据文件/字段结构不合法或配置预算超限
     * @throws RuntimeException 无法创建、写入或发布数据文件
     */
    public function create($setting) {
        $schema=[];
        foreach($setting as$spec){if(!is_array($spec)||count($spec)!==2||!is_string($spec[0])||!preg_match('/\A\w+\z/D',$spec[0])||(int)$spec[1]<1||isset($schema[$spec[0]])||in_array($spec[0],['mydb_max_length','mydb_rec_length','max_row','db_row','db_pos'],true))throw new InvalidArgumentException('Invalid simpleDB field');$schema[$spec[0]]=(int)$spec[1];}
        if(!$schema)throw new InvalidArgumentException('No simpleDB fields');$content=$this->encode($schema,[]);$dir=dirname($this->file);
        if(!is_dir($dir)&&!@mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Cannot create simpleDB directory');
        $fp=@fopen($this->file,'x+b');if(!$fp)return false;
        try {if(!flock($fp,LOCK_EX)||fwrite($fp,$content)!==strlen($content)||!fflush($fp))throw new RuntimeException('Cannot create simpleDB');return true;}finally{flock($fp,LOCK_UN);fclose($fp);}
    }

    /**
     * 功能说明：关闭当前表并删除文件，不与其他进程的文件替换组成事务。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前表文件存在且成功删除时为true，否则false
     */
    public function destroy() { $this->close();return $this->check()?unlink($this->file):false; }

    /**
     * 功能说明：在排他锁内清空记录，保留原字段设置
     *
     * 参数说明：无显式参数。
     *
     * @return bool 文件存在并写入成功 true
     */
    public function empty() { return $this->access(true,function(&$rows){$rows=[];return true;}); }

    /**
     * 功能说明：将字符串比较或字段映射转换为 AND 过滤器，值中的等号保持原样
     *
     * @param string|array|null $condition field>=value && field%part，或字段到等值的映射
     * @return callable 接收记录返回 bool；未知字段视为不匹配
     * @throws InvalidArgumentException 条件格式非法
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private function matcher($condition) {
        $tests=[];
        if(is_array($condition)){foreach($condition as$key=>$value)$tests[]=[$key,'=',$value];}
        elseif($condition!==null&&trim((string)$condition)!==''){foreach(explode('&&',$condition)as$term){if(!preg_match('/\A\s*(\w+)\s*(>=|<=|!=|<>|=|>|<|%)\s*(.*?)\s*\z/sD',$term,$m))throw new InvalidArgumentException('Invalid simpleDB condition');$tests[]=[$m[1],$m[2],$m[3]];}}
        return function($row)use($tests){foreach($tests as$test){list($key,$op,$value)=$test;if(!array_key_exists($key,$row))return false;$actual=$row[$key];switch($op){case '=':$ok=$actual==$value;break;case '!=':case '<>':$ok=$actual!=$value;break;case '>':$ok=$actual>$value;break;case '>=':$ok=$actual>=$value;break;case '<':$ok=$actual<$value;break;case '<=':$ok=$actual<=$value;break;default:$ok=strpos($actual,(string)$value)!==false;}if(!$ok)return false;}return true;};
    }

    /**
     * 功能说明：查询记录，包含空条件时也按指定模式和条数返回
     *
     * @param string|array|null $condition AND 条件或等值映射（默认：[]）
     * @param int $mode 0 纯记录，1 行号/位置，2 记录加元数据，3 仅行号（默认：2）
     * @param int|string $limit 最多条数，all 不限制；无参数 query 沿用返回所有纯记录（默认：1）
     * @return array|false 匹配记录；无匹配 false
     */
    public function query($condition=[],$mode=2,$limit=1) {
        if(!func_num_args())return $this->records();$match=$this->matcher($condition);$max=$limit==='all'?PHP_INT_MAX:max(0,(int)$limit);$this->pos=$this->row=0;
        return $this->access(false,function($rows,$schema)use($match,$max,$mode){$out=[];$length=array_sum($schema)+count($schema)+1;foreach($rows as$i=>$row){if(count($out)>=$max)break;$this->row=$i+1;$this->pos=$this->tlen+1+($i+1)*$length;if(!$match($row))continue;$meta=['db_row'=>$i+1,'db_pos'=>$this->pos];$out[]=$mode===3?$i+1:($mode===1?$meta:($mode===2?array_merge($row,$meta):$row));}return $out?:false;});
    }

    /**
     * 功能说明：读取纯记录，尊重传入条数
     *
     * @param string|array|null $condition 过滤条件（默认：null）
     * @param int|string|null $limit null/all 返回全部（默认：null）
     * @return array|false 匹配记录列表；没有匹配或存储失败为false，应结合驱动错误区分
     */
    public function select($condition=null,$limit=null) {return $this->query($condition,0,$limit===null?'all':$limit);}

    /**
     * 功能说明：在单次排他锁内追加一条或多条记录，按最新表头计算偏移
     *
     * @param array $data 单记录或记录列表（默认：[]）
     * @param bool $mode true 使用字段名，false 按位置（默认：false）
     * @return bool 全部记录编码并保存成功为true；校验异常向外传递，I/O失败为false
     * @throws InvalidArgumentException 待插入值不是要求的记录/记录列表
     */
    public function insert($data=[],$mode=false) {
        if(!(isset($data[0])&&is_array($data[0])))$data=[$data];
        return $this->access(true,function(&$rows,$schema)use($data,$mode){foreach($data as$entry){if(!is_array($entry))throw new InvalidArgumentException('Invalid simpleDB row');$row=[];$i=0;foreach($schema as$key=>$width)$row[$key]=$entry[$mode?$key:$i++]??'';$rows[]=$row;}return true;});
    }

    /**
     * 功能说明：在同一排他锁内筛选并更新记录，空条件明确更新全部
     *
     * @param string|array|null $condition 过滤条件（默认：null）
     * @param array $data 字段映射或按位置的数据（默认：[]）
     * @param bool $mode true 合并字段，false 按位置替换整行（默认：true）
     * @return bool 至少匹配一行 true，无匹配 false
     */
    public function update($condition=null,$data=[],$mode=true) {
        $match=$this->matcher($condition);
        return $this->access(true,function(&$rows,$schema)use($match,$data,$mode){$changed=false;foreach($rows as&$row){if(!$match($row))continue;$i=0;foreach($schema as$key=>$width){if($mode){if(array_key_exists($key,$data))$row[$key]=$data[$key];}else $row[$key]=$data[$i++]??'';}$changed=true;}unset($row);return $changed;});
    }

    /**
     * 功能说明：在同一排他锁内删除并压实记录，不使用空白正则改写数据
     *
     * @param string|array|null $condition 空值删除全部记录（默认：null）
     * @return bool 至少匹配一行 true，无匹配 false
     */
    public function delete($condition=null) {$match=$this->matcher($condition);return $this->access(true,function(&$rows)use($match){$old=count($rows);$rows=array_values(array_filter($rows,function($row)use($match){return !$match($row);}));return count($rows)!==$old;});}

    /**
     * 功能说明：按 1 起始行号读取记录，0/非数字取首行，负数取随机行，越界正数取末行
     *
     * @param int|string $row 行号（默认：0）
     * @return array|false 一行字段映射；空表或读取失败为false，具体行号选择遵守参数约定
     */
    public function record($row=0) {return $this->access(false,function($rows)use($row){if(!$rows)return false;$n=is_numeric($row)?(int)$row:0;if($n<0)$n=random_int(1,count($rows));$n=max(1,min(count($rows),$n));return $rows[$n-1];});}

    /**
     * 功能说明：读取全部纯记录
     *
     * @param string $tmp 接口兼容占位参数，不使用（默认：''）
     * @return array|false 文件不存在 false，空表 []
     */
    public function records($tmp='') {return $this->access(false,function($rows){return $rows;});}

    /**
     * 功能说明：无重复随机抽取记录，不通过反复碰撞重试
     *
     * @param int $rows 最多条数（默认：1）
     * @return array|false 空表 false，非正条数 []
     */
    public function random($rows=1) {return $this->access(false,function($data)use($rows){if(!$data)return false;shuffle($data);return array_slice($data,0,max(0,(int)$rows));});}

    /**
     * 功能说明：读取某行或指定字段
     *
     * @param int $line 行号，0 为首行（默认：0）
     * @param string $field 空串返回整行（默认：''）
     * @return mixed 缺字段为空串
     */
    public function getData($line=0,$field='') {$row=$this->record($line);return $field===''?$row:($row[$field]??'');}

    /**
     * 功能说明：读取首个匹配项的字段，未指定字段时返回第一列
     *
     * @param string|array|null $condition 查询条件
     * @param string $field 字段名（默认：''）
     * @return mixed 未匹配为空串
     */
    public function result($condition,$field='') {$rows=$this->query($condition,0,1);return $rows===false?'':($field===''?reset($rows[0]):($rows[0][$field]??''));}

    /**
     * 功能说明：取得旧格式字段设置及最新长度元数据
     *
     * @param string $tmp 接口占位参数（默认：''）
     * @return array|false 字段宽度加 mydb_max_length/mydb_rec_length/max_row
     */
    public function getFields($tmp='') {return $this->access(false,function($rows,$schema){return $schema+['mydb_max_length'=>$this->tlen+1+count($rows)*(array_sum($schema)+count($schema)+1),'mydb_rec_length'=>array_sum($schema)+count($schema)+1,'max_row'=>count($rows)];});}

    /**
     * 功能说明：排序结果，随机模式使用 shuffle，不使用不满足传递性的随机比较器
     *
     * @param array $result 查询结果
     * @param string $col 排序列，缺失时随机（默认：'rand'）
     * @param string $mode asc/desc，其余随机（默认：'asc'）
     * @return array 排序后的记录副本
     */
    public function setOrder($result,$col='rand',$mode='asc') {if(!is_array($result))return [];if(!$result)return [];$mode=strtolower($mode);if(!array_key_exists($col,$result[0])||!in_array($mode,['asc','desc'],true)){shuffle($result);return $result;}usort($result,function($a,$b)use($col,$mode){$n=($a[$col]??null)<=>($b[$col]??null);return $mode==='desc'?-$n:$n;});return $result;}
}
