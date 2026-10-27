<?PHP
/********************************************
*                                           *
* Name    : Proxy for Database Module       *
* Modifier: Windy2000                       *
* Time    : 2018-11-02                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myDb.class.php
 * 功能说明：数据库代理与通用SQL实现
 *   提供myDb代理、SQL词法分析、查询缓存、事务、参数读取及SQL构造公共trait/类。
 *
 * 方法目录：
 *   public myDb::__construct() — 注册数据库目录自动加载器，并按参数初始化代理驱动
 *   public myDb::supports($interface) — 检查代理驱动是否声明实现指定能力接口，不执行SQL或检测服务器健康。
 *   public myDb::transaction(callable $callback) — 委托驱动执行事务，使回调接收当前代理而不是内部驱动
 *   public static SQLStatement::tokens($sql, $backslash = true) — 扫描 SQL 单词，跳过引号内容及行/块注释，记录括号嵌套深度
 *   public static SQLStatement::segments($sql, $backslash = true) — 按 SQL 引号和注释边界划分文本，保留每段原始字节
 *   public static SQLStatement::mapCode($sql, callable $callback, $backslash = true) — 仅转换 SQL 代码片段，不修改字符串、定界标识符和注释
 *   public static SQLStatement::split($sql, $backslash = true) — 分割可信导入脚本，保留字符串内分号/空白/注释及 MySQL 可执行注释
 *   public static SQLStatement::trimTerminator($sql, $backslash = true) — 去掉最后一个代码分号，保留其后的注释与空白
 *   public static SQLStatement::leadingSQL($sql, $backslash = true) — 去除普通前导注释，仅供导入结果说明与旧方言转换使用
 *   public static SQLStatement::unpaged($sql, $backslash = true) — 移除最外层 ORDER BY、LIMIT/OFFSET/FETCH 及锁定子句
 *   public base_db::quoteIdentifier($name) — 引用完整标识符，不像 safeName 那样删改合法名称中的空格或标点
 *   public base_db::safeName($name) — 获取安全的名称（数据库、表及字段）
 *   public base_db::safeValue($val) — 获取安全的字段值
 *   public base_db::cache($mode=null) — 读取或设置共享查询缓存策略的读写位
 *   public base_db::setCache($cache, $ttl = 600) — 挂接共享缓存后端及查询条目的 TTL，后续写操作通过同后端传播失效
 *   public base_db::hasCache() — 检查是否已挂接共享缓存后端，供外层查询缓存决定是否启用
 *   protected base_db::cacheNamespace() — 生成当前数据库上下文的稳定隔离摘要
 *   public base_db::cacheKey($key) — 为逻辑查询键添加数据库命名空间和当前失效代次
 *   public base_db::invalidateCache() — 更新共享缓存代次，并清空当前请求记录的读写键关联
 *   public base_db::getCache($key) — 按读策略读取查询缓存，并在查询前捕获代次用于后续写入
 *   public base_db::writeCache($key, $result) — 按写策略保存查询结果，优先沿用查询前捕获的代次
 *   public base_db::isCacheable($sql) — 保守判断 SQL 是否可复用共享结果，同时作为 MySQL 安全重试的筛选条件
 *   protected base_db::noteStatement($sql, $rows) — 在语句成功后跟踪事务、会话上下文和写入失效，由驱动内部调用
 *   protected base_db::savepointName($sql) — 提取成功执行的保存点控制语句名称，忽略注释并解开标识符引号
 *   protected base_db::noteSavepoint($sql,$word) — 跟踪原生保存点，防止 SQLite 外层 SAVEPOINT 内的未提交结果进入共享缓存
 *   protected base_db::guardTransaction($sql) — 在执行前拒绝会破坏受管事务边界的已知控制/隐式提交语句
 *   protected base_db::transactionSQL($sql) — 标记框架内部发起的事务 SQL，避免与回调手写控制语句混淆
 *   protected base_db::transactionBoundary($method) — 在回调事务的进入/退出边界调用事务方法，保持嵌套回调的所有权
 *   public base_db::begin() — 开始一层受管事务，嵌套时创建 ms_scope_ 层级保存点
 *   public base_db::commit() — 提交当前受管层：内层释放保存点，最外层 COMMIT 后更新脏查询缓存代次
 *   public base_db::rollback() — 回滚当前受管层：内层回滚并释放保存点，最外层执行 ROLLBACK
 *   public base_db::transaction(callable $callback) — 在受管事务中执行回调，正常返回即提交，抛异常时回滚到进入前层级
 *   public base_sql::build($tbl, $join=null) — 获取或创建指定表的查询构建器，沿用驱动转义器及定界符
 *   public base_sql::create($name, $para=[], $type='db', $show = false) — 按对象类别构建创建库/表/索引SQL，可选择预览或执行
 *   public base_sql::drop($name, $type='db', $show = false) — 按对象类别构建删除库/表/索引SQL，可选择预览或执行
 *   public base_sql::insert($show = false) — 将当前表的字段和值构建为INSERT并预览或执行
 *   public base_sql::select($show = false) — 依据当前一张或多张表的构建状态生成 SELECT，或立即执行
 *   public base_sql::update($show = false) — 将单表或关联表的字段及条件构建为UPDATE并预览或执行
 *   public base_sql::delete($show = false) — 依据当前构建条件生成DELETE并预览或执行
 *   public base_sql::record($sql='', $mode = 2) — 读取查询第一条记录，兼容可空记录缓存并清理结果和构建状态
 *   public base_sql::records($sql='', $mode = 2) — 读取全部记录，按数据库缓存策略复用查询结果，保留空数组
 *   public base_sql::result($sql='') — 通过 record() 的数字下标模式读取第一行第一列
 *   public base_sql::count($sql='') — 统计原查询的结果行数，保留 DISTINCT、GROUP/HAVING 和 UNION 语义
 *   public base_sql::fetchAll($sql, array $params = [], $mode = 2) — 绑定参数读取全部记录，不参与共享结果缓存
 *   public base_sql::fetchOne($sql, array $params = [], $mode = 2) — 绑定参数读取第一条记录，不参与共享结果缓存
 *   public base_sql::fetchValue($sql, array $params = []) — 绑定参数读取第一行第一列，保留 SQL NULL、0 及空字符串
 *   public base_sql::correctDelimiter($sql) — 转换引号和注释之外的 MySQL 简单标识符定界符，保留用户文本
 *   public SQLBuilder::__construct($tbl, $join = array(), $idx='', $delimiter='', $escaper = null) — 初始化表名、关联设置、别名、定界符及驱动值转义器
 *   public SQLBuilder::safeValue($value) — 使用驱动提供的值转义回调，未提供时使用历史 MySQL 转义
 *   public SQLBuilder::reset($complete = false) — 清空字段、值、条件、排序、分组、分页及 SELECT 前缀
 *   public SQLBuilder::exists($para, $condition=[], $not = false, $mode='and') — 追加 EXISTS/NOT EXISTS 子查询条件，沿用当前驱动转义器
 *   protected SQLBuilder::condition(&$field, &$condition=null, &$value=null) — 按历史操作符规则原地规范化一个条件的字段、操作符和值
 *   protected SQLBuilder::condition_multi($condition, $layer=0) — 递归生成带括号的复合条件，保留每层逻辑连接方式
 *   public SQLBuilder::where($field=null, $condition=null, $value=null, $mode='and') — 追加条件、读取条件 SQL，或清空条件
 *   public SQLBuilder::field($field = null) — 设置查询列或写入字段，读取时使用局部副本避免重复预览改写状态
 *   protected static SQLBuilder::fieldList($text) — 分割投影列表，函数参数、字符串和子查询中的逗号保持原样
 *   public SQLBuilder::values() — 直接添加insert的插入值
 *   public SQLBuilder::order($field=null, $desc = false) — 追加排序字段，支持数组形式的排序方向，读取时不修改内部状态
 *   public SQLBuilder::limit($start=null, $count=null) — 设置或读取分页片段，数值转为非负整数
 *   public SQLBuilder::group($group=null) — 设置 GROUP BY 及可选 HAVING，或读取当前分组结构
 *   public SQLBuilder::select() — 生成 SELECT SQL，保留去重前缀、条件、排序、分组/HAVING 和分页
 *   public SQLBuilder::insert($replace=false) — 生成 INSERT/REPLACE，列列表支持 field(列名)，位置行使用局部副本，预览不消耗待写数据
 *   public SQLBuilder::update() — 生成更新查询语句
 *   public SQLBuilder::delete() — 生成删除查询语句
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：代理具体数据库驱动，并提供能力检查及以代理为参数的事务回调
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 补充说明：
 * 数据库代理，转发驱动方法并提供参数化查询及事务入口（MySQL/SQLite）
 *
 *     $db = new myDb('MySQL', $host, $user, $pwd, 'utf8mb4'); // 构造驱动，尚需 connect()
 *     $db->connect(false, $database);                       // 连接并选择数据库
 *     $db->execute($sql, $params);                          // 参数化执行，不用共享结果缓存
 *     $rows = $db->fetchAll($sql, $params);                  // 参数化读取全部记录
 *     $row = $db->fetchOne($sql, $params);                   // 参数化读取第一条记录
 *     $value = $db->fetchValue($sql, $params);               // 参数化读取第一行第一列
 *     $db->transaction(function ($tx) { ... });              // 正常返回提交，异常回滚
 *
 * 使用说明：
 * 1. 初始化遵循 myProxy：驱动存在构造函数时不重复调用 init()；支持直接代理现有驱动。
 * 2. 参数占位符只代替数据值，不代替表名、字段、排序方向或 SQL 片段。
 * 3. 旧 query()/build()/record()/records()/result() 保留。新增读取及受管事务的实现
 *    以 MySQL/SQLite 为准；MySQL 参数化读取需要 mysqlnd，事务表须支持事务。
 * 4. 代理事务回调接收当前 myDb；直接驱动入口接收驱动自身。异常交给调用方处理。
 * 5. supports(接口名)检查驱动声明的API能力；代理本身不充当具体驱动的接口实例。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * IDE提示说明：@method是代理可转发的已知入口，不表示任意后端都实现全部能力。build限SQL后端；execute/fetch*须supports(interface_db_prepared::class)，begin/commit/rollback须supports(interface_db_transaction::class)。事务回调优先使用本类显式transaction。
 *
 * @method mixed connect(mixed ...$args) 连接驱动；参数及返回值由后端约定
 * @method mixed selectDB(mixed $database, mixed ...$args) 切换数据库或文件表
 * @method mixed query(mixed $statement, mixed ...$args) SQL或文件条件查询，语义依后端
 * @method mixed insert(mixed ...$args) 插入数据，参数及预览方式依后端
 * @method mixed select(mixed ...$args) 查询数据，参数及返回形态依后端
 * @method mixed update(mixed ...$args) 更新记录，条件/预览方式依后端
 * @method mixed delete(mixed ...$args) 删除记录，条件/预览方式依后端
 * @method mixed record(mixed ...$args) 读取单条记录，空记录语义依后端
 * @method mixed records(mixed ...$args) 读取多条记录，空列表语义依后端
 * @method mixed result(mixed ...$args) 读取一个字段，保留SQL NULL等值
 * @method array getFields(mixed ...$args) 获取字段信息，文件表与SQL驱动结构不同
 * @method mixed close(mixed ...$args) 关闭驱动，SQL后端可返回查询次数
 * @method SQLBuilder|null build(string $table, mixed $join = null) SQL后端的构建器入口
 * @method int|false execute(string $sql, array $params = []) 参数绑定后端入口，先检查supports
 * @method array|false fetchAll(string $sql, array $params = [], int $mode = 2) 参数绑定后端读取全部行
 * @method array|false fetchOne(string $sql, array $params = [], int $mode = 2) 参数绑定后端读取首行
 * @method mixed fetchValue(string $sql, array $params = []) 参数绑定后端读取首值
 * @method bool begin() 受管事务后端入口，先检查supports
 * @method bool commit() 受管事务后端入口，先检查supports
 * @method bool rollback() 受管事务后端入口，先检查supports
 */
class myDb extends myProxy {
    protected static $database_autoload_registered = false;
    protected
        $func_alias = array(
            'c' => 'connect',
            'i' => 'insert',
            's' => 'select',
            'u' => 'update',
            'd' => 'delete',
            'q' => 'query',
            'r' => 'record',
            'g' => 'result'
        );

    /**
     * 功能说明：注册数据库目录自动加载器，并按参数初始化代理驱动
     *
     * @param mixed ...$args myProxy::init的驱动类名/对象及其初始化参数（历史位置参数，由func_get_args读取）
     * @param string|object $module 历史位置参数说明：第一个可选位置参数：驱动类名或现有驱动实例，省略时不初始化
     * @return void 初始化或释放资源，无返回值。
     * @throws InvalidArgumentException 代理目标不可用或参数不适用
     */
    public function __construct() {
        if(!self::$database_autoload_registered) {
            spl_autoload_register(function($class) {
                if(!preg_match('/\A\w+\z/D', $class)) return;
                // PHP名称不区分大小写，文件路径仍使用发布包中的规范大小写。
                $files = ['mysql'=>'mysql', 'mssql'=>'mssql', 'sqlite'=>'sqlite', 'simpledb'=>'simpleDB',
                    'interface_db'=>'interface_db', 'interface_sql'=>'interface_sql',
                    'interface_db_prepared'=>'interface_db_prepared', 'interface_db_transaction'=>'interface_db_transaction'];
                $file = __DIR__.'/database/'.($files[strtolower($class)] ?? $class).'.class.php';
                if(is_file($file)) require_once($file);
            });
            self::$database_autoload_registered = true;
        }
        if(func_num_args()) parent::init(...func_get_args());
    }

    /**
     * 功能说明：检查代理驱动是否声明实现指定能力接口，不执行SQL或检测服务器健康。
     *
     * 例如supports(interface_db_prepared::class)判断参数绑定API；
     * supports(interface_db_transaction::class)判断受管事务API。MSSQL即使由trait
     * 带有begin方法，也不会误报支持受管事务。第三方驱动可自愿实现对应协议。
     *
     * @param string $interface 接口名称；接受已加载或可自动加载的自定义接口
     * @return bool 目标声明实现该接口时为true；未初始化、未知名称或类名为false
     * @throws Throwable 自定义自动加载器产生的异常继续传递
     */
    public function supports($interface) {
        if(!is_object($this->obj) || !is_string($interface) || !preg_match('/\A[\\\\A-Za-z0-9_\x80-\xff]+\z/D', $interface)) return false;
        return interface_exists($interface) && $this->obj instanceof $interface;
    }

    /**
     * 功能说明：委托驱动执行事务，使回调接收当前代理而不是内部驱动
     *
     * 正常返回（包括 false）提交；异常回滚并继续抛出，嵌套事务由驱动使用保存点。
     * 回调不可自行提交或执行隐式提交 DDL；文件、邮件等外部副作用不受回滚保障。
     *
     *
     * 调用示例：
     *     if ($db->supports(interface_db_transaction::class)) {
     *         $id = $db->transaction(function ($tx) {
     *             $tx->execute('INSERT INTO items(name) VALUES(?)', ['示例']);
     *             return $tx->getInsertId();
     *         });
     *     }
     *
     * @param callable $callback 接收当前 myDb 对象的业务回调，返回值作为本方法结果
     * @return mixed 回调正常返回的值
     * @throws BadMethodCallException 目标没有可调用的事务入口或驱动不支持受管事务
     * @throws Throwable 驱动启动/提交/回滚或业务回调异常原样向外传递
     * @template TResult
     * @phpstan-param callable(myDb): TResult $callback
     * @phpstan-return TResult
     */
    public function transaction(callable $callback) {
        if(!is_object($this->obj) || !is_callable([$this->obj, 'transaction'])) throw new BadMethodCallException('Database adapter does not support transactions');
        return $this->obj->transaction(function() use ($callback) { return $callback($this); });
    }
}

/**
 * 功能说明：扫描SQL词法结构、注释及最外层分页片段的公共辅助类
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 补充说明：
 * SQL 结构扫描辅助类，记录关键字位置及括号层级
 *
 *     SQLStatement::tokens($sql, true);   // 忽略引号和注释中的内容，取得词及层级
 *     SQLStatement::unpaged($sql, true);  // 去掉最外层排序、分页及锁定子句
 *
 * 供计数查询、只读缓存判定及 SQLite 兼容转换使用；它不是完整 SQL 解析器，
 * 不负责数据库语法验证或处理任意方言，也不能把不可信 SQL 变成安全语句。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class SQLStatement {
    /**
     * 功能说明：扫描 SQL 单词，跳过引号内容及行/块注释，记录括号嵌套深度
     *
     * @param string $sql 待分析的可信 SQL 模板，不执行查询
     * @param bool $backslash 是否按 MySQL 方式跳过引号内反斜杠转义；SQLite 使用 false（默认：true）
     * @return array 每项含 word（大写单词）、offset（原串字节位置）、depth（括号深度）；
     *               从零层开始，不包含注释、字符串或标识符引号内的关键字
     */
    public static function tokens($sql, $backslash = true) {
        $tokens = []; $depth = 0;
        foreach(self::segments($sql,$backslash) as $part) {
            if($part['type']!=='code') continue;
            preg_match_all('/[A-Za-z_][A-Za-z0-9_$]*|[()]/', $part['text'], $matches, PREG_OFFSET_CAPTURE);
            foreach($matches[0] as $match) {
                if($match[0]==='(') $depth++;
                elseif($match[0]===')') $depth=max(0,$depth-1);
                else $tokens[]=['word'=>strtoupper($match[0]),'offset'=>$part['offset']+$match[1],'depth'=>$depth];
            }
        }
        return $tokens;
    }
    /**
     * 功能说明：按 SQL 引号和注释边界划分文本，保留每段原始字节
     *
     * @param string $sql SQL 模板
     * @param bool $backslash true 使用 MySQL 反斜杠及 #/--空白注释规则，false 用 SQLite/T-SQL 规则（默认：true）
     * @return array 包含 type/text/offset 的片段；不验证完整 SQL 语法
     */
    public static function segments($sql, $backslash = true) {
        $parts = []; $length = strlen($sql); $plain = 0;
        for($i=0; $i<$length; $i++) {
            $start = $i; $char = $sql[$i]; $pair = substr($sql, $i, 2); $type = null;
            if($pair === '/*') { $type = 'comment'; $end = strpos($sql, '*/', $i+2); $i = $end === false ? $length-1 : $end+1; }
            elseif(($pair === '--' && (!$backslash || !isset($sql[$i+2]) || ord($sql[$i+2])<=32)) || ($backslash && $char === '#')) { $type = 'comment'; $i += strcspn($sql, "\r\n", $i)-1; }
            elseif($char === "'" || $char === '"' || $char === '`' || (!$backslash && $char === '[')) {
                $type = $char === '`' || $char === '[' ? 'identifier' : 'string'; $quote = $char === '[' ? ']' : $char;
                for($i++; $i<$length; $i++) {
                    if($backslash && $sql[$i] === '\\') { $i++; continue; }
                    if($sql[$i] === $quote) { if(($sql[$i+1] ?? '') === $quote) $i++; else break; }
                }
                $i = min($i, $length-1);
            }
            if($type !== null) {
                if($plain < $start) $parts[] = ['type'=>'code','text'=>substr($sql,$plain,$start-$plain),'offset'=>$plain];
                $parts[] = ['type'=>$type,'text'=>substr($sql,$start,$i-$start+1),'offset'=>$start]; $plain = $i+1;
            }
        }
        if($plain < $length) $parts[] = ['type'=>'code','text'=>substr($sql,$plain),'offset'=>$plain];
        return $parts;
    }

    /**
     * 功能说明：仅转换 SQL 代码片段，不修改字符串、定界标识符和注释
     *
     * @param string $sql 完整 SQL
     * @param callable $callback 接收一段代码，返回替换文本
     * @param bool $backslash 方言引号/注释规则（默认：true）
     * @return string 转换结果
     * @phpstan-param callable(string): string $callback
     */
    public static function mapCode($sql, callable $callback, $backslash = true) {
        $result = '';
        foreach(self::segments($sql,$backslash) as $part) $result .= $part['type']==='code' ? $callback($part['text']) : $part['text'];
        return $result;
    }

    /**
     * 功能说明：分割可信导入脚本，保留字符串内分号/空白/注释及 MySQL 可执行注释
     *
     * @param string $sql 脚本；MySQL 支持独立行 DELIMITER 指令，其他方言不解释该指令
     * @param bool $backslash true 为 MySQL，false 为普通 SQLite/T-SQL 分号批次（默认：true）
     * @return array 不含分隔符的语句；不自动识别无 DELIMITER 的过程/触发器体或 GO 指令
     * @throws InvalidArgumentException 分隔符指令出现在未结束的语句中
     */
    public static function split($sql, $backslash = true) {
        $result = []; $buffer = ''; $delimiter = ';';
        $emit = function() use (&$buffer,&$result,$backslash) {
            $text = trim($buffer); $buffer = '';
            if($text !== '' && (self::tokens($text,$backslash) || strpos($text,'/*!')!==false || strpos($text,'/*M!')!==false)) $result[] = $text;
        };
        foreach(self::segments($sql,$backslash) as $part) {
            if($part['type'] !== 'code') { $buffer .= $part['text']; continue; }
            $text = $part['text']; $length = strlen($text);
            for($i=0;$i<$length;) {
                $offset = $part['offset']+$i;
                if($backslash && ($offset===0 || in_array($sql[$offset-1],["\n","\r"],true)) && preg_match('/\G[\t ]*DELIMITER[\t ]+(\S+)[\t ]*(?:\r?\n|$)/Ai',$text,$m,0,$i)) {
                    if(self::tokens($buffer,true)) throw new InvalidArgumentException('DELIMITER requires a completed SQL statement');
                    $buffer = ''; $delimiter = $m[1]; $i += strlen($m[0]); continue;
                }
                if(substr($text,$i,strlen($delimiter)) === $delimiter) { $emit(); $i += strlen($delimiter); }
                else $buffer .= $text[$i++];
            }
        }
        $emit(); return $result;
    }

    /**
     * 功能说明：去掉最后一个代码分号，保留其后的注释与空白
     *
     * @param string $sql 单条 SQL
     * @param bool $backslash 方言规则（默认：true）
     * @return string 去尾分号后的 SQL，可安全再包装子查询（关闭括号前仍需换行）
     */
    public static function trimTerminator($sql, $backslash = true) {
        foreach(array_reverse(self::segments($sql,$backslash)) as $part) {
            if($part['type']==='comment' || trim($part['text'])==='') continue;
            if($part['type']==='code') {
                $text = rtrim($part['text']);
                if(substr($text,-1)===';') $sql = substr_replace($sql,'',$part['offset']+strlen($text)-1,1);
            }
            break;
        }
        return trim($sql);
    }

    /**
     * 功能说明：去除普通前导注释，仅供导入结果说明与旧方言转换使用
     *
     * @param string $sql 完整 SQL，不改动内部字符串
     * @param bool $backslash 方言规则（默认：true）
     * @return string 以实际语句开始的文本；可执行注释仍保留
     */
    public static function leadingSQL($sql, $backslash = true) {
        foreach(self::segments($sql,$backslash) as $part) {
            if($part['type']==='comment' && strpos($part['text'],'/*!')!==0 && strpos($part['text'],'/*M!')!==0) continue;
            if(trim($part['text'])==='') continue;
            return ltrim(substr($sql,$part['offset']));
        }
        return '';
    }

    /**
     * 功能说明：移除最外层 ORDER BY、LIMIT/OFFSET/FETCH 及锁定子句
     *
     * 保留 DISTINCT、GROUP/HAVING、UNION 及嵌套查询/引号中的同名内容，供 count()
     * 包装子查询使用；不等同于对任意数据库方言进行完整语法重写。
     *
     * @param string $sql 待计数的可信 SELECT/WITH 查询
     * @param bool $backslash 引号内是否启用 MySQL 反斜杠转义，SQLite 为 false（默认：true）
     * @return string 截取后的查询，或移除末尾分号及空白后的原查询
     */
    public static function unpaged($sql, $backslash = true) {
        $top = array_values(array_filter(self::tokens($sql, $backslash), function($token) { return $token['depth'] === 0; }));
        foreach($top as $i => $token) {
            if(in_array($token['word'], ['LIMIT','OFFSET','FETCH'], true) || ($token['word'] === 'ORDER' && ($top[$i+1]['word'] ?? '') === 'BY')
                || ($token['word'] === 'FOR' && in_array($top[$i+1]['word'] ?? '', ['UPDATE','SHARE'], true)) || ($token['word'] === 'LOCK' && ($top[$i+1]['word'] ?? '') === 'IN')) return rtrim(substr($sql, 0, $token['offset']));
        }
        return self::trimTerminator($sql,$backslash);
    }
}

/**
 * 功能说明：数据库标识符、共享缓存状态与受管事务公共trait
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 补充说明：
 * 数据库基础能力：标识符辅助、共享缓存代次及受管事务
 *
 * 由驱动混入使用。缓存命名空间隔离连接用户、库、字符集及 SQLite 文件/内存实例；
 * 事务、会话状态、临时表、锁定及已知非确定查询绕过共享缓存。写入更新共享代次，
 * 事务内推迟到提交；回滚不发布未提交结果。绕过驱动的原生 SQL 不能自动跟踪，
 * 外部缓存故障和未识别函数仍受后端可用性及 TTL 约束，不提供跨进程强一致承诺。
 * 受管 begin/commit/rollback/transaction 限定 MySQL/SQLite，并支持嵌套保存点。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
trait base_db {
    protected
        $cache_mode = 255,
        $cache = null,
        $cache_ttl = 600,
        $cache_keys = [];
    protected $transaction_control=false, $managed_callbacks=0, $managed_boundary=false, $cache_savepoints = [], $cache_savepoint_root = false, $cache_transaction = false, $cache_manual = false, $cache_dirty = false, $cache_session = false, $cache_temporary = [], $managed_depth = 0, $cache_memory_identity = '';
    /**
     * 功能说明：引用完整标识符，不像 safeName 那样删改合法名称中的空格或标点
     *
     * @param string|array $name 单个名称，或分段名称数组（如 [数据库, 表]）；不接收 SQL 表达式
     * @return string 按驱动转义定界符后的标识符
     * @throws InvalidArgumentException 空名称、NUL 或不合法数组
     */
    public function quoteIdentifier($name) {
        if(is_array($name)) { if(!$name)throw new InvalidArgumentException('Empty identifier');return implode('.',array_map([$this,'quoteIdentifier'],$name)); }
        if(!is_string($name)||$name===''||strpos($name,"\0")!==false)throw new InvalidArgumentException('Invalid identifier');
        $delimiter=$this instanceof SQLite ? '"' : ($this->delimiter ?? ($this->dl ?? '`'));
        $left=is_array($delimiter)?$delimiter[0]:$delimiter;$right=is_array($delimiter)?$delimiter[1]:$delimiter;
        if($left==='')$left=$right='`';
        return $left.str_replace($right,$right.$right,$name).$right;
    }

    /**
     * 功能说明：获取安全的名称（数据库、表及字段）
     *
     * @param string $name 待验证/引用的数据库标识符，不能作为任意SQL表达式
     * @return string 移除不允许字符后的标识符文本；可能为空，不是对任意SQL表达式的引用
     */
    public function safeName($name) {
        return preg_replace('/[^\w]+/', '', $name);
    }

    /**
     * 功能说明：获取安全的字段值
     *
     * @param mixed $val 需要按当前数据库连接规则转义的数据值
     * @return string|array 标量按历史规则返回转义文本，传入数组时逐项替换并保留键；不执行SQL
     */
    public function safeValue($val) {
        $search  = array('\\',      "\n",   "\r",   "'",    '"',    "\x1a", "\x00", chr(0xdf).chr(0x27));
        $replace = array('\\\\',    '\n',   '\r',   "\'",   '\"',   '\x1a', '\x00', '');
        return str_replace($search, $replace, $val);
    }


    /**
     * 功能说明：读取或设置共享查询缓存策略的读写位
     *
     * @param int|null $mode null 只读取；0 关闭、1 可读、2 可写、3 可读写；默认内部值 255（默认：null）
     * @return int 当前模式；高位不参与现有读写判断，事务及查询资格仍可禁用缓存
     */
    public function cache($mode=null) {
        if($mode!==null) {
            $this->cache_mode = $mode;
        }
        return $this->cache_mode;
    }

    /**
     * 功能说明：挂接共享缓存后端及查询条目的 TTL，后续写操作通过同后端传播失效
     *
     * @param interface_cache|myCache|null $cache 提供 get()/set() 的共享后端，null 关闭挂接
     * @param int $ttl 查询缓存有效秒数，默认 600；共享代次存活至少一天且长于该 TTL（默认：600）
     * @return void 不迁移原后端条目；不同连接需要相同后端及一致的命名空间才能传播失效
     */
    public function setCache($cache, $ttl = 600) {
        $this->cache = $cache;
        $this->cache_ttl = $ttl;
    }

    /**
     * 功能说明：检查是否已挂接共享缓存后端，供外层查询缓存决定是否启用
     *
     * 参数说明：无显式参数。
     *
     * @return bool 后端不为 null 时为 true；不检测后端健康或网络连通性
     */
    public function hasCache() { return $this->cache !== null; }

    /**
     * 功能说明：生成当前数据库上下文的稳定隔离摘要
     *
     * 参数说明：无显式参数。
     *
     * @return string SHA-256 摘要，涵盖驱动类、主机、用户、库、字符集及 SQLite 路径；
     *                每次打开独立内存库时使用不同身份，并隔离 MySQL sql_mode 策略；不把密码作为缓存键的一部分
     */
    protected function cacheNamespace() {
        $file = property_exists($this, 'file_db') ? $this->file_db : '';
        if($file !== '') {
            if($file === ':memory:') { if($this->cache_memory_identity === '') $this->cache_memory_identity = bin2hex(random_bytes(16)); $file .= $this->cache_memory_identity; }
            else $file = realpath($file) ?: $file;
        }
        return hash('sha256', serialize([get_class($this), $this->host ?? '', $this->user ?? '', $this->db ?? '', $this->charset ?? '', $file, property_exists($this,'session_sql_mode')?$this->session_sql_mode:null]));
    }

    /**
     * 功能说明：为逻辑查询键添加数据库命名空间和当前失效代次
     *
     * 共享代次不存在时分配新的随机代次，避免代次过期/淘汰后旧结果重新出现。
     *
     * @param mixed $key 可序列化的逻辑查询键，通常包含 SQL、结果模式及版本
     * @return string v4 物理缓存键；挂接后端时本调用可能初始化其代次元数据
     */
    public function cacheKey($key) {
        $namespace = $this->cacheNamespace(); $revision = false;
        if($this->cache !== null) {
            $revision_key = 'db:revision:v4:'.$namespace;
            $revision = $this->cache->get($revision_key);
            // A missing revision always gets a new generation, never the original empty generation.
            if(!is_string($revision) || $revision === '') {
                $revision = bin2hex(random_bytes(16));
                $this->cache->set($revision_key, $revision, max(86400, (int)$this->cache_ttl+1));
            }
        }
        return 'db:v4:'.hash('sha256', serialize([$namespace, $revision, $key]));
    }

    /**
     * 功能说明：更新共享缓存代次，并清空当前请求记录的读写键关联
     *
     * 参数说明：无显式参数。
     *
     * @return void 不逐条删除旧查询结果；旧代次条目随后按各自 TTL 过期
     */
    public function invalidateCache() {
        if($this->cache !== null) {
            $this->cache->set('db:revision:v4:'.$this->cacheNamespace(), bin2hex(random_bytes(16)), max(86400, (int)$this->cache_ttl + 1));
        }
        $this->cache_keys = [];
    }

    /**
     * 功能说明：按读策略读取查询缓存，并在查询前捕获代次用于后续写入
     *
     * @param string $key 包含 SQL 与返回模式的逻辑键
     * @return mixed 后端缓存值；无后端、禁止读取或未命中时为 false；false 本身需由上层封装
     */
    public function getCache($key) {
        if($this->cache === null) return false;
        // Capture the revision before querying so a concurrent write cannot relabel an old result.
        $this->cache_keys[$key] = $this->cacheKey($key);
        return ($this->cache_mode & 1) != 1 ? false : $this->cache->get($this->cache_keys[$key]);
    }

    /**
     * 功能说明：按写策略保存查询结果，优先沿用查询前捕获的代次
     *
     * 避免并发写库后把较早读取的结果标为新代次；写入后移除该键的临时关联。
     *
     * @param string $key 与 getCache() 相同的逻辑键
     * @param mixed $result 可序列化的查询结果或包含空值的封装
     * @return void 无后端或禁止写入时不保存，但仍清理临时关联
     */
    public function writeCache($key, $result) {
        if($this->cache !== null && ($this->cache_mode & 2) == 2) {
            $this->cache->set($this->cache_keys[$key] ?? $this->cacheKey($key), $result, $this->cache_ttl);
        }
        unset($this->cache_keys[$key]);
    }
    /**
     * 功能说明：保守判断 SQL 是否可复用共享结果，同时作为 MySQL 安全重试的筛选条件
     *
     * 仅允许普通 SELECT；事务/手动提交/会话状态、临时表、锁定读取、用户变量、
     * 可执行注释和已知非确定函数绕过缓存。不能证明任意用户函数的确定性。
     *
     * @param string $sql 完整 SQL 查询；非字符串直接判为不可缓存
     * @return bool 通过已实现筛选时为 true；MSSQL 共享结果缓存保守关闭
     */
    public function isCacheable($sql) {
        if($this instanceof MSSQL || $this->cache_transaction || $this->cache_manual || $this->cache_session || !is_string($sql) || strpos($sql, '@') !== false || strpos($sql, '/*!') !== false) return false;
        $tokens = SQLStatement::tokens($sql, ($this->delimiter ?? '`') === '`');
        if(($tokens[0]['word'] ?? '') !== 'SELECT') return false;
        $words = array_column($tokens, 'word');
        foreach(['FOR','LOCK','INTO','SQL_CALC_FOUND_ROWS','RAND','RANDOM','NOW','CURRENT_TIMESTAMP','CURRENT_DATE','CURRENT_TIME','SYSDATE','UTC_TIMESTAMP','UUID','LAST_INSERT_ID','LAST_INSERT_ROWID','FOUND_ROWS','ROW_COUNT','CHANGES','CONNECTION_ID','USER','CURRENT_USER','SESSION_USER','SYSTEM_USER','GET_LOCK','RELEASE_LOCK','IS_FREE_LOCK','SLEEP','BENCHMARK','CURDATE','CURTIME','UNIX_TIMESTAMP'] as $word) if(in_array($word, $words, true)) return false;
        foreach($this->cache_temporary as $table => $unused) if(preg_match('/\b'.preg_quote($table, '/').'\b/i', $sql)) return false;
        return true;
    }

    /**
     * 功能说明：在语句成功后跟踪事务、会话上下文和写入失效，由驱动内部调用
     *
     * 识别 SQL 事务控制、AUTOCOMMIT、SET/USE/PRAGMA、临时表及 DDL；提交脏事务
     * 更新代次，回滚不发布结果；MySQL 非临时 DDL 按隐式提交处理。
     *
     * @param string $sql 已执行成功的 SQL 模板，绑定参数不展开进 SQL
     * @param bool $rows 是否产生结果集/按驱动判为只读，用于识别实际写操作
     * @return void 仅更新跟踪状态及必要的缓存代次，不执行或重放业务 SQL
     */
    protected function noteStatement($sql, $rows) {
        $tokens = SQLStatement::tokens($sql, ($this->delimiter ?? '`') === '`');
        $word = $tokens[0]['word'] ?? '';
        if($word === 'BEGIN' || ($word === 'START' && ($tokens[1]['word'] ?? '') === 'TRANSACTION')) { $this->cache_transaction = true; return; }
        if($this->noteSavepoint($sql,$word)) return;
        if(in_array($word, ['COMMIT','END','ROLLBACK'], true)) {
            if($word === 'ROLLBACK' && in_array('TO',array_column($tokens,'word'),true)) { $this->cache_session=true;return; }
            if($word !== 'ROLLBACK' && $this->cache_dirty) $this->invalidateCache();
            $this->cache_dirty = false; $this->cache_savepoints=[]; $this->cache_savepoint_root=false;
            $this->cache_transaction = preg_match('/\bAND\s+CHAIN\b/i', $sql) === 1;
            return;
        }
        if(in_array($word, ['SAVEPOINT','RELEASE'], true)) return;
        if($word === 'SET') {
            $this->cache_session = true;
            if(preg_match('/\bAUTOCOMMIT\s*(?:=|:=)\s*([01])\b/i', $sql, $match)) {
                if($match[1] === '1' && $this->cache_dirty) $this->invalidateCache();
                $this->cache_manual = $match[1] === '0';
                if(!$this->cache_manual) { $this->cache_dirty = false; $this->cache_transaction = false; $this->cache_savepoints=[]; $this->cache_savepoint_root=false; }
            }
            return;
        }
        if($word === 'PRAGMA' && !in_array($tokens[1]['word'] ?? '', ['TABLE_INFO','TABLE_XINFO','INDEX_INFO','INDEX_LIST','DATABASE_LIST','COMPILE_OPTIONS'], true)) { $this->cache_session = true; return; }
        if($word === 'USE') { $this->cache_session = true; return; }
        if(preg_match('/\bCREATE\s+TEMP(?:ORARY)?\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\[]?(\w+)/i', $sql, $match)) $this->cache_temporary[strtolower($match[1])] = true;
        if(preg_match('/\bDROP\s+(?:TEMP(?:ORARY)?\s+)?TABLE\s+(?:IF\s+EXISTS\s+)?[`\[]?(\w+)/i', $sql, $match)) unset($this->cache_temporary[strtolower($match[1])]);
        $write = !$rows || in_array($word, ['CALL','WITH'], true) || strpos($sql, '/*!') !== false;
        if(!$write || in_array($word, ['SELECT','SHOW','EXPLAIN','DESCRIBE','DESC','PRAGMA'], true)) return;
        $ddl = in_array($word, ['CREATE','ALTER','DROP','TRUNCATE','RENAME'], true);
        $temporary = preg_match('/\bTEMP(?:ORARY)?\b/i', $sql) === 1;
        if($ddl) {
            $this->invalidateCache();
            if(get_class($this) !== 'SQLite' && !($this instanceof SQLite) && !$temporary) {
                if($this->cache_dirty) $this->invalidateCache();
                $this->cache_dirty = false; $this->cache_transaction = false; $this->cache_savepoints=[]; $this->cache_savepoint_root=false;
            } elseif($this->cache_transaction || $this->cache_manual) $this->cache_dirty = true;
        } elseif($this->cache_transaction || $this->cache_manual) $this->cache_dirty = true;
        else $this->invalidateCache();
    }

    /**
     * 功能说明：提取成功执行的保存点控制语句名称，忽略注释并解开标识符引号
     *
     * @param string $sql SAVEPOINT、RELEASE 或 ROLLBACK TO 语句
     * @return string|null 规范小写名称；不是可识别的保存点控制时返回 null
     */
    protected function savepointName($sql) {
        $plain='';
        foreach(SQLStatement::segments($sql,$this->delimiter==='`') as $part) $plain .= $part['type']==='comment' ? ' ' : $part['text'];
        if(!preg_match('/\A\s*(?:SAVEPOINT|RELEASE(?:\s+SAVEPOINT)?|ROLLBACK(?:\s+TRANSACTION)?\s+TO(?:\s+SAVEPOINT)?)\s+(`(?:``|[^`])*`|"(?:""|[^"])*"|\[(?:\]\]|[^\]])*\]|\'(?:\'\'|[^\'])*\'|[\w$\x80-\xff]+)/i',$plain,$m)) return null;
        $name=$m[1];$first=$name[0];
        if(in_array($first,['`','"',"'",'['],true)) { $end=$first==='['?']':$first;$name=str_replace($end.$end,$end,substr($name,1,-1)); }
        return strtolower($name);
    }

    /**
     * 功能说明：跟踪原生保存点，防止 SQLite 外层 SAVEPOINT 内的未提交结果进入共享缓存
     *
     * @param string $sql 已执行成功的语句
     * @param string $word 首关键字
     * @return bool true 表示已处理保存点语句；SQLite 根保存点 RELEASE 按提交发布失效
     */
    protected function noteSavepoint($sql,$word) {
        if(!in_array($word,['SAVEPOINT','RELEASE','ROLLBACK'],true)) return false;
        $name=$this->savepointName($sql);if($name===null)return false;
        if($word==='SAVEPOINT') {
            if($this instanceof SQLite && !$this->cache_transaction) { $this->cache_transaction=true;$this->cache_savepoint_root=true; }
            $this->cache_savepoints[]=['name'=>$name,'dirty'=>$this->cache_dirty];return true;
        }
        for($i=count($this->cache_savepoints)-1;$i>=0;$i--) if($this->cache_savepoints[$i]['name']===$name) {
            if($word==='ROLLBACK') { $this->cache_dirty=$this->cache_savepoints[$i]['dirty'];$this->cache_savepoints=array_slice($this->cache_savepoints,0,$i+1); }
            else {
                $this->cache_savepoints=array_slice($this->cache_savepoints,0,$i);
                if(!$this->cache_savepoints && $this->cache_savepoint_root) {
                    if($this->cache_dirty)$this->invalidateCache();
                    $this->cache_transaction=$this->cache_dirty=$this->cache_savepoint_root=false;
                }
            }
            return true;
        }
        // An unrecognised external name must never enable shared reads in a SQLite transaction.
        if($this instanceof SQLite)$this->cache_session=true;
        return true;
    }

    /**
     * 功能说明：在执行前拒绝会破坏受管事务边界的已知控制/隐式提交语句
     *
     * @param string $sql 待执行模板，不展开绑定参数
     * @return void 不检查存储过程内部或绕过驱动的原生调用
     * @throws LogicException 回调内尝试原生事务控制或 MySQL 已知隐式提交操作
     */
    protected function guardTransaction($sql) {
        if(!$this->managed_depth||$this->transaction_control)return;
        $tokens=SQLStatement::tokens($sql,$this->delimiter==='`');$word=$tokens[0]['word']??'';
        if(in_array($word,['BEGIN','START','COMMIT','END','ROLLBACK','SAVEPOINT','RELEASE'],true))throw new LogicException('Use managed transaction methods for transaction control');
        if($this instanceof MySQL && (strpos($sql,'/*!')!==false||strpos($sql,'/*M!')!==false||in_array($word,['CREATE','ALTER','DROP','TRUNCATE','RENAME','GRANT','REVOKE','LOCK','UNLOCK','ANALYZE','OPTIMIZE','REPAIR','FLUSH','RESET','INSTALL','UNINSTALL'],true)||($word==='SET'&&in_array('AUTOCOMMIT',array_column($tokens,'word'),true))))throw new LogicException('Implicit-commit statements cannot run in a managed transaction');
    }

    /**
     * 功能说明：标记框架内部发起的事务 SQL，避免与回调手写控制语句混淆
     *
     * @param string $sql 框架生成的 BEGIN/保存点/提交/回滚
     * @return mixed query 的执行结果
     */
    protected function transactionSQL($sql) {
        $previous=$this->transaction_control;$this->transaction_control=true;
        try{return $this->query($sql);}finally{$this->transaction_control=$previous;}
    }

    /**
     * 功能说明：在回调事务的进入/退出边界调用事务方法，保持嵌套回调的所有权
     *
     * @param string $method begin/commit/rollback，由框架固定传入
     * @return bool 对应事务方法结果
     */
    protected function transactionBoundary($method) {
        $previous=$this->managed_boundary;$this->managed_boundary=true;
        try{return $this->$method();}finally{$this->managed_boundary=$previous;}
    }

    /**
     * 功能说明：开始一层受管事务，嵌套时创建 ms_scope_ 层级保存点
     *
     * 不接管 SQL 方式已开启的外部事务；若前一受管事务被外部控制打断，会重置计数并报错。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 开启事务或保存点成功为 true；驱动以 false 报错时为 false
     * @throws BadMethodCallException 驱动不是 MySQL/SQLite
     * @throws LogicException 存在受管范围外的事务或受管事务已被打断
     */
    public function begin() {
        if($this->managed_callbacks && !$this->managed_boundary)throw new LogicException('Cannot manually change a callback transaction boundary');
        if($this->managed_depth > 0 && !$this->cache_transaction) { $this->managed_depth = 0; throw new LogicException('The managed transaction was interrupted'); }
        if(!($this instanceof MySQL) && !($this instanceof SQLite)) throw new BadMethodCallException('Managed transactions require the MySQL or SQLite adapter');
        if($this->managed_depth === 0 && ($this->cache_transaction || $this->cache_manual)) throw new LogicException('A transaction is already active outside this scope');
        $sql = $this->managed_depth ? 'SAVEPOINT ms_scope_'.$this->managed_depth : ($this instanceof SQLite ? 'BEGIN' : 'START TRANSACTION');
        if($this->transactionSQL($sql) === false) return false;
        $this->managed_depth++; return true;
    }

    /**
     * 功能说明：提交当前受管层：内层释放保存点，最外层 COMMIT 后更新脏查询缓存代次
     *
     * 参数说明：无显式参数。
     *
     * @return bool 对应语句成功为 true；驱动以 false 报错时为 false，层级只在成功后减少
     * @throws LogicException 没有活动受管事务或它已被 SQL 控制语句打断
     */
    public function commit() {
        if($this->managed_callbacks && !$this->managed_boundary)throw new LogicException('Cannot manually change a callback transaction boundary');
        if(!$this->managed_depth || !$this->cache_transaction) throw new LogicException('No managed transaction is active');
        $sql = $this->managed_depth > 1 ? 'RELEASE SAVEPOINT ms_scope_'.($this->managed_depth-1) : 'COMMIT';
        if($this->transactionSQL($sql) === false) return false;
        $this->managed_depth--; return true;
    }

    /**
     * 功能说明：回滚当前受管层：内层回滚并释放保存点，最外层执行 ROLLBACK
     *
     * 参数说明：无显式参数。
     *
     * @return bool 对应语句成功为 true；驱动以 false 报错时为 false；不会发布未提交查询结果
     * @throws LogicException 没有活动受管事务或它已被 SQL 控制语句打断
     */
    public function rollback() {
        if($this->managed_callbacks && !$this->managed_boundary)throw new LogicException('Cannot manually change a callback transaction boundary');
        if(!$this->managed_depth || !$this->cache_transaction) throw new LogicException('No managed transaction is active');
        if($this->managed_depth > 1) {
            $savepoint = 'ms_scope_'.($this->managed_depth-1);
            if($this->transactionSQL('ROLLBACK TO SAVEPOINT '.$savepoint) === false || $this->transactionSQL('RELEASE SAVEPOINT '.$savepoint) === false) return false;
        } elseif($this->transactionSQL('ROLLBACK') === false) return false;
        $this->managed_depth--; return true;
    }

    /**
     * 功能说明：在受管事务中执行回调，正常返回即提交，抛异常时回滚到进入前层级
     *
     * 支持嵌套保存点，正常返回 false 也会提交。回调不得自行提交、回滚或执行隐式提交
     * DDL；检查到范围改变会抛异常，但已经发生的外部提交不能撤销。仅支持事务表，
     * 文件、邮件等外部副作用也不能回滚；回滚自身失败会记录日志并继续抛原异常。
     *
     * @param callable $callback 接收当前驱动的业务回调；代理入口另行包装为接收 myDb
     * @return mixed 回调正常返回值
     * @throws RuntimeException 无法开始/提交事务或回调改变受管范围
     * @throws LogicException 外部事务冲突或受管事务中断
     * @throws Throwable 业务回调和驱动异常继续传递
     * @template TResult
     * @phpstan-param callable(object): TResult $callback
     * @phpstan-return TResult
     */
    public function transaction(callable $callback) {
        $depth = $this->managed_depth;
        if(!$this->transactionBoundary('begin')) throw new RuntimeException('Cannot begin transaction');
        try {
            $this->managed_callbacks++;
            try {$value=$callback($this);} finally {$this->managed_callbacks--;}
            if($this->managed_depth !== $depth+1 || !$this->transactionBoundary('commit')) throw new RuntimeException('Transaction scope was changed by callback');
            return $value;
        } catch(Throwable $error) {
            try { while($this->managed_depth > $depth && $this->cache_transaction) { if(!$this->transactionBoundary('rollback')) break; } }
            catch(Throwable $rollback_error) { error_log('Database transaction rollback failed'); }
            if(!$this->cache_transaction) $this->managed_depth = $depth;
            throw $error;
        }
    }

}

/**
 * 功能说明：SQL构建、普通查询与参数化读取公共trait
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 补充说明：
 * SQL 驱动共享的构建、结果读取及参数化读取辅助方法
 *
 * build() 创建的 SQLBuilder 继承当前驱动的标识符定界符及值转义器。
 * record()/records()/result() 可按策略缓存普通查询，空值保留其语义；count() 包装
 * 移除外层分页后的原查询，保留分组及去重。fetchAll/One/Value 委托驱动 execute()
 * 绑定参数，不使用共享查询缓存，结束或异常时释放结果及构建状态。
 * 旧构建器仍接受可信原始表达式，不能将所有参数当作不可信数据的安全字面量。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
trait base_sql {
    protected
        $host = '',
        $user = '',
        $pwd = '',
        $charset = 'utf8',
        $connect = NULL,
        $result = NULL,
        $sql = '',
        $db = '',
        $builder = array(),
        $delimiter = '';

    /**
     * 功能说明：获取或创建指定表的查询构建器，沿用驱动转义器及定界符
     *
     * @param string $tbl 表名或 数据库.表名，各段按 safeName() 规范化；[reset] 清空全部构建器
     * @param array|null $join 联表设置，含 mode、field 和可选 field_join；重用时可更新联表信息（默认：null）
     * @return SQLBuilder|null 正常表名返回对应构建器；[reset]重置所有构建状态并返回null
     */
    public function build($tbl, $join=null) {
        if($tbl=='[reset]') {
            foreach($this->builder as $k => $v) {
                $this->builder[$k]->reset();
                unset($this->builder[$k]);
            }
            return;
        }
        $tbl = implode('.', array_map([$this, 'safeName'], explode('.', $tbl)));
        if(!isset($this->builder[$tbl])) {
            $this->builder[$tbl] = new SQLBuilder($tbl, $join, 't'.count($this->builder), $this->delimiter, [$this, 'safeValue']);
        } else {
            if(!empty($join)) $this->builder[$tbl]->join = $join;
        }
        return $this->builder[$tbl];
    }

    /**
     * 功能说明：按对象类别构建创建库/表/索引SQL，可选择预览或执行
     *
     * @param string $name 待操作的数据库对象名称
     * @param array|string $para 表/索引的col、pri、idx等结构选项；数据库场景为字符集文本（默认：[]）
     * @param string $type 数据库对象类别（默认：'db'）
     * @param bool $show true仅返回生成的SQL，false执行语句（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回驱动query的执行结果
     */
    public function create($name, $para=[], $type='db', $show = false) {
        switch($type) {
            case 't':
            case 'tbl':
            case 'table':
                if(is_string($para)) {
                    $para=['col'=>$para];
                }
                if(!isset($para['col'])) {
                    $this->error('Column data for table is missing!');
                }
                $sql = 'create table IF NOT EXISTS `'.$this->safeName($name).'` ('.chr(10);
                if(is_string($para['col'])) {
                    if(preg_match('#^\w+$#', $para['col'])) {
                        $sql = 'create table IF NOT EXISTS `'.$this->safeName($name).'` like `'.$para['col'].'`';
                        break;
                    } else {
                        $sql .= $para['col'];
                    }
                } else {
                    $cols =& $para['col'];
                    for($i=0,$m=count($cols);$i<$m;$i++) {
                        if(is_string($cols[$i])){
                            $cols[$i] = preg_replace('#^(\w+)#', '`\1`', $cols[$i]);
                            $sql .= $cols[$i].','.chr(10);
                        } else {
                            $sql .= '`'.$cols[$i]['name'].'` '.$cols[$i]['type'].' '.(isset($cols[$i]['null'])?'NULL':'NOT NULL');
                            if(!isset($cols[$i]['condition'])) $cols[$i]['condition'] = '';
                            if(is_string($cols[$i]['condition'])) {
                                $sql .= ' '.$cols[$i]['condition'];
                            } else {
                                foreach($cols[$i]['condition'] as $k => $v) {
                                    $sql .= $k.' \''.$v.'\'';
                                }
                            }
                            $sql .= chr(10);
                        }
                    }
                    if(isset($para['idx'])) {
                        if(is_string($para['idx'])) {
                            $sql .= 'INDEX idx_'.md5($para['idx']).'('.$para['idx'].'),'.chr(10);
                        } else {
                            $sql .= 'INDEX '.$para['idx'][0].'('.$para['idx'][1].'),'.chr(10);
                        }
                    }
                    if(isset($para['uni'])) $sql .= 'UNIQUE (`'.$para['uni'].'`),'.chr(10);
                    if(isset($para['pri'])) {
                        $sql .= 'PRIMARY KEY (`'.$para['pri'].'`)';
                    } else {
                        $sql = substr($sql, 0, -2);
                    }
                }
                $sql .= chr(10).')';
                if(!isset($para['engine'])) $para['engine'] = 'MyISAM';
                if(!isset($para['charset'])) $para['charset'] = $this->charset;
                $sql .= 'ENGINE='.$para['engine'].' DEFAULT CHARSET='.$para['charset'];
                if(isset($para['increment'])) $sql .= ' AUTO_INCREMENT='.$para['increment'];
                if(isset($para['comment'])) $sql .= ' COMMENT=\''.$para['comment'].'\'';
                break;
            case 'i':
            case 'idx':
            case 'index':
                if(is_string($para)) {
                    $para=['col'=>$para];
                }
                if(is_array($para['col'])) $para['col'] = implode(',', $para['col']);
                if(!isset($para['name'])) $para['name'] = 'idx_'.md5($para['col']);
                $sql = 'create index `'.$this->safeName($para['name']).'` on '.$this->safeName($name).'('.$para['col'].')';
                break;
            default:
                $sql = 'create database IF NOT EXISTS `'.$this->safeName($name).'`';
                if(empty($para)) $para = $this->charset;
                $sql .= ' default charset '.$para.' COLLATE '.$para.'_unicode_ci';
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：按对象类别构建删除库/表/索引SQL，可选择预览或执行
     *
     * @param string|array $name 库/表名称；删除索引时为[索引名,表名]
     * @param string $type 数据库对象类别（默认：'db'）
     * @param bool $show true仅返回生成的SQL，false执行语句（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回驱动query的执行结果
     */
    public function drop($name, $type='db', $show = false) {
        $sql = 'drop ';
        switch($type) {
            case 't':
            case 'tbl':
            case 'table':
                $sql .= 'table IF EXISTS `'.$this->safeName($name).'`';
                break;
            case 'i':
            case 'idx':
            case 'index':
                $sql .= 'index IF EXISTS `'.$this->safeName($name[0]).'` on `'.$this->safeName($name[1]).'`';
                break;
            default:
                $sql .= 'database IF EXISTS `'.$this->safeName($name).'`';
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：将当前表的字段和值构建为INSERT并预览或执行
     *
     * @param bool $show true仅返回生成的SQL，false执行语句（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回驱动query的执行结果
     */
    public function insert($show = false) {
        reset($this->builder);
        $sql = current($this->builder)->insert();
        $sql = preg_replace('/^(insert|replace)/i', '\1 LOW_PRIORITY', $sql);
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：依据当前一张或多张表的构建状态生成 SELECT，或立即执行
     *
     * @param bool $show true 仅返回 SQL，false 调用驱动 query()；多表前缀采用第一表设置（默认：false）
     * @return string|int|false 预览 SQL、驱动执行结果或失败 false
     * @throws LogicException 未先调用 build() 建立任何表
     */
    public function select($show = false) {
        if(!$this->builder) throw new LogicException('Build a table before generating a SELECT');
        if(count($this->builder)==1) {
            $sql = current($this->builder)->select();
        } else {
            $sql = 'select ';
            $first = reset($this->builder);
            if($first->sel_prefix !== '') $sql .= $first->sel_prefix.' ';
            $fields = array();
            foreach($this->builder as $cur_tbl) {
                $the_field = $cur_tbl->field();
                if($the_field=='*') {
                    if(empty($fields)) {
                        $the_field = $cur_tbl->dl.$cur_tbl->idx.$cur_tbl->dr.'.*';
                    } else {
                        $the_field = '';
                    }
                }
                if(!empty($the_field)) $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql .= ' from '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= ' '.$cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                if(empty($cur_tbl->join['field_join'])) {
                    $sql .= ' using('.$cur_tbl->join['field'].')';
                } else {
                    $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
                }
            }

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            $group_field = '';
            $group_having = '';
            foreach($this->builder as $cur_tbl) {
                $group = $cur_tbl->group();
                if(!empty($group)) {
                    if(empty($group_field)) {
                        $group_field = ' group by ';
                    } else {
                        $group_field .= ' , ';
                    }
                    $group_field .= $cur_tbl->idx.'.`'.str_replace(',', '`, '.$cur_tbl->idx.'.`', (is_array($group['field']) ? implode(',', $group['field']) : $group['field'])).'`';
                }
                if(isset($group['having']) && !empty($group['having'])) {
                    if(empty($group_having)) {
                        $group_having = ' having ';
                    } else {
                        $group_having .= ' and ';
                    }
                    $tmp = new SQLBuilder('tmp', [], '', $this->delimiter, [$this, 'safeValue']);
                    $tmp->where($group['having']);
                    $group['having'] = $tmp->where();
                    $group_having .= preg_replace('#(`\w+`)#', $cur_tbl->idx.'.\1', $group['having']);
                    unset($tmp);
                }
            }
            if(!empty($group_field)) $sql .= $group_field.$group_having.' ';

            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：将单表或关联表的字段及条件构建为UPDATE并预览或执行
     *
     * @param bool $show true仅返回生成的SQL，false执行语句（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回驱动query的执行结果
     */
    public function update($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->update();
            $sql = preg_replace('/^update/i', 'update LOW_PRIORITY', $sql);
        } else {
            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql = 'update LOW_PRIORITY '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                if(empty($cur_tbl->join['field_join'])) {
                    $sql .= ' using('.$cur_tbl->join['field'].')';
                } else {
                    $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
                }
            }

            $sql .= ' set ';
            $fields = array();
            foreach($this->builder as $cur_tbl) {
                $the_field = $cur_tbl->field();
                if($the_field!=='*') $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            /* don't support yet
            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
            */
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：依据当前构建条件生成DELETE并预览或执行
     *
     * @param bool $show true仅返回生成的SQL，false执行语句（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回驱动执行结果；无条件且事务外可能使用TRUNCATE
     */
    public function delete($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->delete();
            $sql = preg_replace('/^delete/i', 'delete LOW_PRIORITY', $sql);
        } else {
            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql = 'delete LOW_PRIORITY [idx_list] from '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            $idx = $cur_tbl->idx;
            while(($cur_tbl=next($this->builder))!==false) {
                $idx .= ', '.$cur_tbl->idx;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
            }

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            /* don't support yet
            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
            */
            $sql = str_replace('[idx_list]', $idx, $sql);
        }
        if($this->cache_transaction||$this->cache_manual)$sql=preg_replace('/^truncate\s+table\s+/i','delete from ',$sql);
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 功能说明：读取查询第一条记录，兼容可空记录缓存并清理结果和构建状态
     *
     * 无最外层 LIMIT 的普通 SELECT 会补 LIMIT 1，且放在锁定子句前；已有 LIMIT 不改写。
     *
     * @param string $sql 完整 SQL；空字符串由当前构建器生成（默认：''）
     * @param int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2）
     * @return array|false 首条记录；无记录或驱动执行失败时为 false，SQL NULL 留在字段值中
     */
    public function record($sql='', $mode = 2) {
        if($sql === '') $sql = $this->select(true);
        $sql = SQLStatement::trimTerminator($sql, $this->delimiter === '`');
        $tokens = SQLStatement::tokens($sql, $this->delimiter === '`');
        $top = array_values(array_filter($tokens, function($token) { return $token['depth'] === 0; }));
        if(!($this instanceof MSSQL) && ($top[0]['word'] ?? '') === 'SELECT' && !in_array('LIMIT', array_column($top, 'word'), true)) {
            $at = strlen($sql);
            foreach($top as $token) if(in_array($token['word'], ['FOR','LOCK'], true)) { $at = $token['offset']; break; }
            $sql = rtrim(substr($sql, 0, $at))."\nlimit 1\n".substr($sql, $at);
        }
        $key = hash('sha256', serialize(['record:v4', $mode, $sql]));
        $eligible = $this->isCacheable($sql);
        $cached = $eligible ? $this->getCache($key) : false;
        try {
            if(is_array($cached) && ($cached['db-row'] ?? null) === 1) return $cached['value'];
            $result = false;
            if($this->query($sql) !== false) {
                $row = $this->getRS($mode);
                if(is_array($row)) $result = $row;
                if($eligible) $this->writeCache($key, ['db-row'=>1, 'value'=>$result]);
            }
            return $result;
        } finally { $this->free(); $this->build('[reset]'); unset($this->cache_keys[$key]); }
    }

    /**
     * 功能说明：读取全部记录，按数据库缓存策略复用查询结果，保留空数组
     *
     * @param string $sql 完整 SQL，或简单 表名/数据库.表名；空字符串从当前构建器生成（默认：''）
     * @param int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2）
     * @return array|false 记录数组，无记录为 []，执行失败为 false；结束时释放结果和构建状态
     * @phpstan-return list<array<array-key,mixed>>|false
     */
    public function records($sql='', $mode = 2) {
        if($sql !== '' && preg_match('/\A\w+(?:\.\w+)?\z/D', $sql)) { $this->build($sql); $sql = ''; }
        if($sql === '') $sql = $this->select(true);
        $key = hash('sha256', serialize(['records:v4', $mode, $sql]));
        $eligible = $this->isCacheable($sql);
        $cached = $eligible ? $this->getCache($key) : false;
        try {
            if($cached !== false) return $cached;
            if($this->query($sql) === false) return false;
            $result = [];
            while(($row = $this->getRS($mode)) !== false && $row !== null) $result[] = $row;
            if($eligible) $this->writeCache($key, $result);
            return $result;
        } finally { $this->free(); $this->build('[reset]'); unset($this->cache_keys[$key]); }
    }

    /**
     * 功能说明：通过 record() 的数字下标模式读取第一行第一列
     *
     * @param string $sql 完整 SQL；空字符串从当前构建器生成（默认：''）
     * @return mixed 单值，保留 null、0、空字符串；无记录或失败返回 false
     */
    public function result($sql='') {
        $row = $this->record($sql, 1);
        return is_array($row) && array_key_exists(0, $row) ? $row[0] : false;
    }

    /**
     * 功能说明：统计原查询的结果行数，保留 DISTINCT、GROUP/HAVING 和 UNION 语义
     *
     * 只移除最外层排序、分页及锁定子句，再包装为 count(*) 子查询；不直接替换投影列。
     *
     * @param string $sql SELECT/WITH 查询；空字符串从当前构建器生成（默认：''）
     * @return int|string|false 计数值（类型依驱动），非 SELECT/WITH 返回 0，执行失败为 false
     */
    public function count($sql='') {
        if($sql === '') $sql = $this->select(true);
        $tokens = SQLStatement::tokens($sql, $this->delimiter === '`');
        if(!in_array($tokens[0]['word'] ?? '', ['SELECT','WITH'], true)) return 0;
        $sql = SQLStatement::unpaged($sql, $this->delimiter === '`');
        return $this->result('select count(*) from ('.$sql."\n) as cnt");
    }

    /**
     * 功能说明：绑定参数读取全部记录，不参与共享结果缓存
     *
     * 无论成功还是异常都释放结果与构建状态；需要驱动提供 execute()。
     *
     * @param string $sql SQL 模板；MySQL 用 ?，SQLite 用 ? 或命名参数
     * @param array $params 绑定数据值；不能替代表名、字段名、排序方向及 SQL 片段（默认：[]）
     * @param int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2）
     * @return array|false 所有记录，空结果为 []；驱动执行失败为 false，保留原生标量类型
     * @throws Throwable 驱动参数校验及查询异常继续传递
     * @phpstan-return list<array<array-key,mixed>>|false
     */
    public function fetchAll($sql, array $params = [], $mode = 2) {
        try {
            if($this->execute($sql, $params) === false) return false;
            $rows = [];
            while(($row = $this->getRS($mode)) !== false && $row !== null) $rows[] = $row;
            return $rows;
        } finally { $this->free(); $this->build('[reset]'); }
    }

    /**
     * 功能说明：绑定参数读取第一条记录，不参与共享结果缓存
     *
     * 不自动改写 SQL，较大结果集应在模板中显式 LIMIT；结束或异常时释放结果及构建状态。
     *
     * @param string $sql SQL 模板，参数形式由具体驱动决定
     * @param array $params 按驱动约定绑定的数据值，不代替标识符（默认：[]）
     * @param int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2）
     * @return array|false 首条记录；无记录或执行失败为 false
     * @throws Throwable 驱动参数校验及查询异常继续传递
     */
    public function fetchOne($sql, array $params = [], $mode = 2) {
        try {
            if($this->execute($sql, $params) === false) return false;
            $row = $this->getRS($mode);
            return is_array($row) ? $row : false;
        } finally { $this->free(); $this->build('[reset]'); }
    }

    /**
     * 功能说明：绑定参数读取第一行第一列，保留 SQL NULL、0 及空字符串
     *
     *
     * 调用示例：
     *     $value = $db->fetchValue('SELECT value FROM items WHERE id=?', [1]);
     *     if ($value === false) {
     *         // 无记录或执行失败；不要把0、空串或SQL NULL当作false
     *     }
     *
     * @param string $sql SQL 模板，按驱动约定提供占位符
     * @param array $params 数据值参数，不代替表名、字段名或 SQL 片段（默认：[]）
     * @return mixed 第一列值；无记录或执行失败返回 false，SQL NULL 返回 null
     * @throws Throwable 驱动参数校验及查询异常继续传递
     */
    public function fetchValue($sql, array $params = []) {
        $row = $this->fetchOne($sql, $params, 1);
        return is_array($row) && array_key_exists(0, $row) ? $row[0] : false;
    }

    /**
     * 功能说明：转换引号和注释之外的 MySQL 简单标识符定界符，保留用户文本
     *
     * @param string $sql 可信 SQL；仅转换反引号包围的单词标识符，不是完整的方言转换器
     * @return string 使用当前驱动左右定界符的 SQL，并移除两端换行
     */
    public function correctDelimiter($sql) {
        if($this->delimiter === '`') return trim($sql, "\r\n");
        $dl = is_array($this->delimiter) ? $this->delimiter[0] : $this->delimiter;
        $dr = is_array($this->delimiter) ? $this->delimiter[1] : $this->delimiter;
        $result = ''; $length = strlen($sql);
        for($i=0; $i<$length; $i++) {
            $start = $i; $char = $sql[$i]; $pair = substr($sql, $i, 2);
            if($pair === '/*') {
                $end = strpos($sql, '*/', $i+2); $i = $end === false ? $length-1 : $end+1;
            } elseif($pair === '--' || $char === '#') $i += strcspn($sql, "\r\n", $i)-1;
            elseif($char === "'" || $char === '"') {
                for($i++; $i<$length; $i++) if($sql[$i] === $char) { if(($sql[$i+1] ?? '') === $char) $i++; else break; }
                $i = min($i, $length-1);
            } elseif($char === '`' && preg_match('/\G`(\w+)`/A', $sql, $match, 0, $i)) {
                $result .= $dl.$match[1].$dr; $i += strlen($match[0])-1; continue;
            }
            $result .= substr($sql, $start, $i-$start+1);
        }
        return trim($result, "\r\n");
    }
}

/**
 * 功能说明：可链式组合字段、条件、值、排序及分页的SQL构建器
 *
 * 使用说明：
 *   1. 由myDb代理加载lib/database驱动；具体SQL方言和连接由驱动实现。
 *   2. build返回SQL构造入口，execute/fetch*进行参数化执行，transaction管理事务。
 *   3. supports检查目标显式声明的接口；缓存读写受事务和SQL只读判定约束。
 *   4. 目录列出本文件声明的方法；trait继承和别名入口沿用其原声明说明。
 *
 * 补充说明：
 * 链式 SQL 片段构建器，支持字段、条件、排序、分页及分组
 *
 *     $builder->where(['status' => 1, 'id' => [1, 2]]); // 关联映射：相等或 IN
 *     $builder->where('deleted_at', '=', null);        // 输出 IS NULL
 *     $builder->where([['a', '=', 1], ['b', '=', 2], 'or']); // 嵌套条件组
 *     $builder->order(['id', true])->limit(0, 20);     // 降序及分页
 *
 * 使用说明：
 * 1. 由驱动 build() 创建时继承其值转义器；独立构造且未提供转义器时沿用旧 MySQL 规则。
 * 2. 空数组过滤转换为明确布尔条件，不直接丢失过滤；空 IN 为假，空 NOT IN 为真。
 * 3. 旧字符串表达式、字段增量及函数形式仍有特殊含义，调用方须限制输入；
 *    不可信数据和二进制值优先使用驱动 execute()/fetchAll/One/Value 参数绑定。
 * 4. field()/where()/order()/limit() 无参数读取当前片段，group() 无参数返回分组设置；
 *    不会执行 SQL，实际执行由数据库驱动完成。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class SQLBuilder {
    use base_db { safeValue as protected legacySafeValue; }
    protected $escaper = null;
    public 
        $join = array(
            'mode' => 'left',
            'field' => '',
            'field_join' => ''
        ),
        $dl = '',
        $dr = '',
        $tbl = '',
        $sel_prefix = '',
        $idx = '';
    protected
        $condition = array(),
        $fields = array(),
        $order = array(),
        $values = array(),
        $group = array(),
        $limit = '';

    /**
     * 功能说明：初始化表名、关联设置、别名、定界符及驱动值转义器
     *
     * @param string $tbl 表名或 数据库.表名，分段规范化并添加定界符
     * @param array|null $join 联表配置，mode 默认 left，field 为当前表字段，field_join 为目标字段（默认：array()）
     * @param string $idx 表别名，空字符串不添加别名（默认：''）
     * @param string|array $delimiter 左右相同的定界符，或 [左, 右]；空字符串不添加（默认：''）
     * @param callable|null $escaper 接收标量并返回已转义文本（不含外层引号）；null 沿用旧规则（默认：null）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct($tbl, $join = array(), $idx='', $delimiter='', $escaper = null) {
        $this->escaper = $escaper;
        if(!empty($delimiter)) {
            if(is_string($delimiter)) {
                $this->dl = $this->dr = $delimiter;
            } elseif(is_array($delimiter)) {
                $this->dl = $delimiter[0];
                $this->dr = $delimiter[1];
            }
        }
        if(strpos($tbl, '.')) {
            $tbl = explode('.', $tbl);
            $this->tbl = $this->dl.$this->safeName($tbl[0]).$this->dr.'.'.$this->dl.$this->safeName($tbl[1]).$this->dr;
        } else {
            $this->tbl = $this->dl.$this->safeName($tbl).$this->dr;
        }
        if(!empty($join)) {
            if(!isset($join['mode'])) $join['mode'] = 'left';
            $join['field'] = str_replace(' ', '', $join['field']);
            $join['field'] = $this->dl.str_replace(',', $this->dr.'.'.$this->dl, $join['field']).$this->dr;
            if(!isset($join['field_join'])) {
                $join['field_join'] = '';
            } else {
                if(!is_array($join['field_join'])) {
                    if(strpos($join['field_join'], '.')===false) {
                        $join['field_join'] = 't0.'.$join['field_join'];
                    }
                    $join['field_join'] = explode('.', $join['field_join']);
                }
                $join['field_join'] = $this->dl.implode($this->dr.'.'.$this->dl, $join['field_join']).$this->dr;
            }
            $this->join = $join;
        }
        $this->idx = $idx;
    }

    /**
     * 功能说明：使用驱动提供的值转义回调，未提供时使用历史 MySQL 转义
     *
     * @param mixed $value 字段值；布尔先转整数，null 在此辅助入口转空字符串
     * @return string 已转义但未加外层引号的文本；NULL 条件及参数绑定由其他入口处理
     */
    public function safeValue($value) {
        if(is_bool($value)) $value = (int)$value;
        if($value === null) $value = '';
        return $this->escaper !== null ? call_user_func($this->escaper, $value) : $this->legacySafeValue($value);
    }

    /**
     * 功能说明：清空字段、值、条件、排序、分组、分页及 SELECT 前缀
     *
     * @param bool $complete true 另外清空表名、别名及关联设置；false 保留这些结构信息（默认：false）
     * @return void 无返回值。
     */
    public function reset($complete = false) {
        if($complete) {
            $this->join = array();
            $this->tbl = '';
            $this->idx = '';
        }
        $this->sel_prefix = '';
        $this->condition = array();
        $this->fields = array();
        $this->values = array();
        $this->order = array();
        $this->group = array();
        $this->limit = '';
    }

    /**
     * 功能说明：追加 EXISTS/NOT EXISTS 子查询条件，沿用当前驱动转义器
     *
     * @param string|array $para 可信完整子查询，或 [子表名, 与外层同名的关联字段]
     * @param array|string $condition 数组形式子表的额外 where 条件；完整 SQL 模式不使用（默认：[]）
     * @param bool $not true 生成 NOT EXISTS，默认 EXISTS（默认：false）
     * @param string $mode 与已有条件的连接方式，and 或 or，非 and 值按 or（默认：'and'）
     * @return $this 当前构建器；没有额外条件时不输出多余 AND
     */
    public function exists($para, $condition=[], $not = false, $mode='and') {
        $sql = ($not?' NOT ':' ').'EXISTS (';
        if(is_string($para)) {
            $sql .= $para;
        } else {
            list($tbl, $field) = $para;
            $tbl = $this->dl.$this->safeName($tbl).$this->dr;
            $field = $this->dl.$this->safeName($field).$this->dr;
            if(!empty($condition)) {
                $tmp = new self($tbl, [], '', [$this->dl,$this->dr], $this->escaper);
                $tmp->where($condition);
                $condition = $tmp->where();
                unset($tmp);
            } else {
                $condition = '';
            }
            $outer = $this->idx !== '' ? $this->idx : $this->tbl;
            $sql .= 'select 1 from '.$tbl.' where '.$tbl.'.'.$field.'='.$outer.'.'.$field;
            if($condition !== '') $sql .= ' and '.$condition;
        }
        $sql .= ')';
        $mode = strtolower($mode);
        if($mode!='and') $mode = 'or';
        $this->condition[] = array($sql, null, null, $mode);
        return $this;
    }

    /**
     * 功能说明：按历史操作符规则原地规范化一个条件的字段、操作符和值
     *
     * 处理普通比较、数值/日期/原始表达式、LIKE/REGEXP、IN 及 NULL 等。
     * 原始表达式及函数形式须来自可信代码；本辅助方法不替代参数绑定。
     *
     * @param string &$field 引用参数；输入字段/可信表达式，输出规范字段或恒真/恒假表达式
     * @param string|null &$condition 引用参数；输入比较符，输出实际 SQL 操作符；! 表示原始条件（默认：null）
     * @param mixed &$value 引用参数；输入数据，输出转义并加引号的 SQL 值、数值或可信表达式（默认：null）
     * @return void null 相等比较转 IS NULL，不等转 IS NOT NULL；空 IN/NOT IN 明确布尔化
     */
    protected function condition(&$field, &$condition=null, &$value=null) {
        if(
            !preg_match('/^\(.+\)$/', $field)
            &&
            !preg_match('/^[a-z_]\w*\(.+\)$/i', $field)
            &&
            $condition != '!'
        ) $field = $this->safeName($field);
        $condition = strtolower(trim((string)$condition));
        if($value === null && in_array($condition, ['=','!=','<>'], true)) { $condition = $condition === '=' ? 'is' : 'is not'; $value = 'NULL'; return; }
        switch($condition) {
            case '=':
            case '<':
            case '<=':
            case '>':
            case '>=':
            case '<>':
            case '!=':
                $value = "'".$this->safeValue($value)."'";
                break;
            case 'b=':
                $condition = '=';
                $value = " binary '".$this->safeValue($value)."'";
                break;
            case 'n=':
            case 'n<':
            case 'n<=':
            case 'n>':
            case 'n>=':
            case 'n<>':
            case 'n!=':
                $condition = substr($condition, 1);
                $value = intval($value);
                break;
            case 'd=':
            case 'd<':
            case 'd<=':
            case 'd>':
            case 'd>=':
            case 'd<>':
            case 'd!=':
                $condition = substr($condition, 1);
                if(is_array($value) && isset($value[0],$value[1]) && preg_match('/^([ymdqwh])(\+|\-)(\d+)$/i', $value[1], $match)) {
                    switch(strtolower($match[1])) {
                        case 'y': $match[1] = 'YEAR'; break;
                        case 'm': $match[1] = 'MONTH'; break;
                        case 'd': $match[1] = 'DAY'; break;
                        case 'q': $match[1] = 'QUARTER'; break;
                        case 'w': $match[1] = 'WEEK'; break;
                        default: $match[1] = 'HOUR';
                    }
                    if(preg_match('#^\w+$#', $value[0])) $value[0] = $this->dl.$value[0].$this->dr;
                    if($match[2]=='-') {
                        $value = 'DATE_SUB('.$value[0].', INTERVAL '.$match[3].' '.$match[1].')';
                    } else {
                        $value = 'DATE_ADD('.$value[0].', INTERVAL '.$match[3].' '.$match[1].') ';
                    }
                } else {
                    $value = "'".$this->safeValue($value)."'";
                }
                break;
            case 'f=':
            case 'f<':
            case 'f<=':
            case 'f>':
            case 'f>=':
            case 'f<>':
            case 'f!=':
                $condition = substr($condition, 1);
                break;
            case 'like':
            case 'like binary':
            case 'not like':
            case 'not like binary':
                if(strpos($value, '%')!==false) {
                    $value = "'".$this->safeValue($value)."'";
                } else {
                    $value = "'%".$this->safeValue($value)."%'";
                }
                break;
            case 'rlike':
            case 'regexp':
            case 'rlike binary':
            case 'regexp binary':
            case 'not rlike':
            case 'not regexp':
            case 'not rlike binary':
            case 'not regexp binary':
                $value = "'".$this->safeValue($value)."'";
                break;
            case 'in':
            case 'nin':
            case 'not in':
            case 'not nin':
                if($value === []) { $field = strpos($condition, 'not ') === 0 ? '(1=1)' : '(0=1)'; $condition = ''; $value = ''; break; }
                if(!is_string($value) || !preg_match('#^\((.+)\)$#', $value)) {
                    if(is_string($value)) {
                        $value = str_replace("'", '', $value);
                        $value = str_replace('"', '', $value);
                        $value = str_replace(', ', ',', $value);
                        $value = explode(',', $value);
                    }
                    if(strlen($condition)==2 || strlen($condition)==6) {
                        $value = array_map(array($this, 'safeValue'), $value);
                        $value = "('".implode("', '", $value)."')";
                    } else {
                        $value = array_map('intval', $value);
                        $value = '('.implode(',', $value).')';
                    }
                }
                $condition = str_replace('nin', 'in', $condition);
                break;
            case 'is':
            case 'is not':
                $value = 'null'; // IS/IS NOT固定用于NULL，不将null传入字符串函数。
                break;
            case '!':
                $condition = '';
                $value = '';
                break;
            case null:
                if(preg_match('/^\((.+)\)$/', $field, $match)) {
                    $field = $match[1];
                    if(preg_match('#^([a-z_]\w*)\(([a-z_]\w*)(.*)\)$#i', $field, $match)) {
                        $field = $match[1].'('.$this->idx.'.`'.$match[2].'`' . $match[3].')';
                    }
                    $condition = '';
                    $value = '';
                } else {
                    $condition = 'is not';
                    $value = 'NULL';
                }
                break;
            default:
                $condition = '=';
                $value = "'".$this->safeValue($value)."'";
                break;
        }
    }

    /**
     * 功能说明：递归生成带括号的复合条件，保留每层逻辑连接方式
     *
     * @param array|string $condition 可信 SQL 片段、[字段, 操作符, 值, 可选连接方式]，
     *                                或子条件列表（末项可指定 and/or）；空列表生成空片段
     * @param int $layer 当前递归深度，默认 0；内部子层使用 layer+1（默认：0）
     * @return string 生成的条件片段；子层含连接词，最外层不额外添加连接词
     * @throws InvalidArgumentException 非空输入不是支持的条件结构
     */
    protected function condition_multi($condition, $layer=0) {
        if($layer>64)throw new LengthException('SQL condition nesting exceeds 64');
        if(is_string($condition)) return $condition;
        if(!$condition) return '';
        if(!is_array($condition)) throw new InvalidArgumentException('Invalid SQL condition group');
        if(is_string($condition[0] ?? null)) {
            $mode = strtolower($condition[3] ?? 'and') === 'or' ? 'or' : 'and';
            $values = $condition[2] ?? null;
            if(is_array($values) && substr($condition[1] ?? '', -2) !== 'in' && !preg_match('/^d[=<>!]/i',$condition[1] ?? '')) {
                $parts = [];
                foreach($values as $value) {
                    $field = $condition[0]; $operator = $condition[1];
                    $this->condition($field, $operator, $value);
                    if(!preg_match('/^\(.+\)$|^[a-z_]\w*\(/i', $field) && $operator !== '') $field = $this->dl.$field.$this->dr;
                    $parts[] = $field.' '.$operator.' '.$value;
                }
                return ' '.$mode.' ('.($parts ? implode(' '.$mode.' ', $parts) : '0=1').')';
            }
            $field = $condition[0]; $operator = $condition[1] ?? null; $value = $condition[2] ?? null;
            $this->condition($field, $operator, $value);
            if(!preg_match('/^\(.+\)$|^[a-z_]\w*\(/i', $field) && $operator !== '') $field = $this->dl.$field.$this->dr;
            return ' '.$mode.' '.$field.' '.$operator.' '.$value;
        }
        $mode = 'and';
        if(is_string(end($condition))) $mode = strtolower(array_pop($condition)) === 'or' ? 'or' : 'and';
        $parts = '';
        foreach($condition as $child) $parts .= $this->condition_multi($child, $layer+1);
        $parts = preg_replace('/\A\s*(?:and|or)\s+/i', '', $parts);
        return ($layer > 0 ? ' '.$mode.' ' : '').'('.$parts.')';
    }

    /**
     * 功能说明：追加条件、读取条件 SQL，或清空条件
     *
     * 支持三元组、嵌套组和字段到值的关联映射（数组值使用 IN）；=null 转 IS NULL。
     * 空数组等值过滤产生假条件，避免条件被忽略后扩大查询或写入范围。
     *
     * @param string|array|null $field 字段、可信表达式或条件数组；null 读取 SQL；[reset] 清空（默认：null）
     * @param string|null $condition 比较符；数组条件形式可使用 and/or 指定与前文的连接方式（默认：null）
     * @param mixed $value 比较值；普通数组可展开，IN 使用成员列表，原始表达式须可信（默认：null）
     * @param string $mode 与前文连接的 and/or，默认 and；条件数组自身也可带连接方式（默认：'and'）
     * @return $this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值
     */
    public function where($field=null, $condition=null, $value=null, $mode='and') {
        if(is_null($field)) {
            $condition = '';
            if(!empty($this->condition)) {
                $org = $this->condition;
                for($i=0,$m=count($this->condition);$i<$m;$i++) {
                    if($i>0) $condition .= ' '.$this->condition[$i][3];
                    if(empty($this->condition[$i][1])) {
                        if(!empty($this->idx)) $this->condition[$i][0] = preg_replace('/(\s|\()'.preg_quote($this->dl).'/', '\1'.$this->idx.'.'.$this->dl, $this->condition[$i][0]);
                    } else {
                        if(
                            !preg_match('/^[a-z_]\w*\(.+\)/i', $this->condition[$i][0])
                            &&
                            !preg_match('/^\(.+\)/', $this->condition[$i][0])
                        ) {
                            $this->condition[$i][0] = $this->dl.$this->condition[$i][0].$this->dr;
                            if(!empty($this->idx)) $this->condition[$i][0] = $this->dl.$this->idx.$this->dr.'.'.$this->condition[$i][0];
                        }
                    }
                    $condition .= ' '.$this->condition[$i][0].' '.$this->condition[$i][1].' '.$this->condition[$i][2];
                }
                $this->condition = $org;
            }
            $condition = preg_replace('@^\s*and*@', ' ', $condition);
            return $condition;
        } elseif(is_array($field)) {
            if(empty($field)) return $this;
            if(!array_key_exists(0, $field)) {
                foreach($field as $name => $entry) $this->where($name, is_array($entry) ? 'in' : '=', $entry, $mode);
                return $this;
            }
            $requested_mode = $condition;
            $condition = '!';
            if(is_array($field[0] ?? null) && is_string(end($field))
                ||
                is_string($field[0] ?? null) && count($field)==4) {
                $mode = array_pop($field);
            } else {
                if(is_string($requested_mode) && in_array(strtolower($requested_mode), ['and','or'], true)) $mode = strtolower($requested_mode);
            }
            $field = $this->condition_multi($field);
        } elseif($field=='[reset]') {
            $this->condition = array();
            return;
        }
        if($field!='') {
            if(is_null($value) && $condition!='!' &&
                preg_match('#^(.+?)([\=<>]+)(.+)$#', $field, $match)
            ) {
                $mode = $condition;
                $field = $match[1];
                $condition = 'f'.$match[2];
                $value = $match[3];
            }
            $mode = strtolower($mode);
            if($mode!='or') $mode = 'and';
            if($value === [] && substr((string)$condition, -2) !== 'in') return $this->where('(0=1)', '!');
            if(is_array($value) && substr((string)$condition, -2)!='in' && !preg_match('/^d[=<>!]/i',(string)$condition)) {
                $temp = [];
                foreach($value as $k) {
                    $temp[] = array($field, $condition, $k, $mode);
                }
                $this->where($temp);
            } else {
                $this->condition($field, $condition, $value);
                $this->condition[] = array($field, $condition, $value, $mode);
            }
        }
        return $this;
    }

    /**
     * 功能说明：设置查询列或写入字段，读取时使用局部副本避免重复预览改写状态
     *
     * @param string|array|null $field 列字符串/列表、含别名的列、写入字段映射；可传多个位置参数；
     *                                null 读取片段，[reset] 清空，all/distinct/distinctrow 设置前缀（默认：null）
     * @param mixed ...$args 兼容入口的附加字段参数，按field内部规则组合（历史位置参数，由func_get_args读取）
     * @param mixed $fields 历史位置参数说明：多位置参数形式的补充列，存在时与第一个参数一起作为列列表
     * @return $this|string 设置/重置返回当前构建器，无参返回列清单或赋值片段
     *
     * 写入映射沿用 null 文本、+N/-N、括号表达式等特殊语义；外部值应优先使用参数绑定。
     */
    public function field($field = null) {
        if(is_null($field)) {
            if(empty($this->fields)) return '*';
            if(isset($this->fields[0])) { //select
                $fields = $this->fields;
                if(!empty($this->idx)) {
                    for($i=0,$m=count($fields);$i<$m;$i++) {
                        if(!preg_match('/^([a-z_]\w*)\((.+)\)/i', $fields[$i]) && strpos($fields[$i], $this->dl.$this->idx.$this->dr.'.')===false) {
                            $fields[$i] = $this->dl.$this->idx.$this->dr.'.'.$fields[$i];
                        }
                    }
                }
                return implode(',', $fields);
            } else { //update
                $fields = array();
                foreach($this->fields as $key => $value) {
                    $key = $this->dl.$this->safeName($key).$this->dr;
                    if(!empty($this->idx)) $key = $this->dl.$this->idx.$this->dr.'.'.$key;
                    if(is_array($value)) {
                        $value = $this->dl.$this->safeName($value[0]).$this->dr.'.'.$this->dl.$this->safeName($value[1]).$this->dr;
                    } elseif(is_null($value) || strtolower($value)=='null') {
                        $value = 'NULL';
                    } elseif(preg_match('/^(\+|\-)(\d+)$/', $value, $match)) {
                        $value = $key.$match[1].$match[2];
                    } elseif(preg_match('/^\(.+\)$/', $value)) {
                        $value = preg_replace('/^\((.+)\)$/', '\1', $value);
                    } else {
                        $value = "'".$this->safeValue($value)."'";
                    }
                    $fields[] = $key.'='.$value;
                }
                return implode(',', $fields);
            }
        } elseif($field=='[reset]') {
            $this->fields = array();
            return $this;
        } elseif(is_string($field) && in_array(strtolower($field), ['all','distinct','distinctrow'])) {
            $this->sel_prefix = $field;
            return $this;
        }
        if(func_num_args()>1) $field = func_get_args();
        if(is_string($field)) $field = self::fieldList($field);
        if(isset($field[0])) { //select
            for($i=count($field)-1;$i>=0;$i--) {
                if(is_array($field[$i])) {
                    $field[$i] = $field[$i][0].' as '.$this->dl.$field[$i][1].$this->dr;
                }
                if(preg_match('/^([a-z_]\w*)\((.+)\)(.*)$/i', $field[$i], $match)) {
                    if(preg_match('/^[a-z_]\w*$/', $match[2])) $match[2] = $this->dl.$match[2].$this->dr;
                    $field[$i] = $match[1].'('.$match[2].')';
                    if(preg_match('#^\s+as\s+([a-z_]\w*)$#i', $match[3], $m2)) {
                        $field[$i] .= ' as '.$this->dl.$m2[1].$this->dr;
                    } else {
                        $field[$i] .= $match[3];
                    }
                } elseif(preg_match('/^([a-z_]\w*)((:|=>)|\s+as\s+)([a-z_]\w*)$/i', $field[$i], $match)) {
                    $field[$i] = $this->dl.$match[1].$this->dr.' as '.$this->dl.$match[4].$this->dr;
                } elseif($field[$i]!='*') {
                    $field[$i] = $this->dl.$this->safeName($field[$i]).$this->dr;
                }
            }
        } else { //update
            unset($field['submit']);
        }
        $this->fields = array_merge($this->fields, $field);
        return $this;
    }

    /**
     * 功能说明：分割投影列表，函数参数、字符串和子查询中的逗号保持原样
     *
     * @param string $text 可信 SELECT 列列表
     * @return array 去除两端空白后的列片段
     */
    protected static function fieldList($text) {
        $result=[];$start=0;$depth=0;
        foreach(SQLStatement::segments($text,true) as $part) if($part['type']==='code') {
            for($i=0,$m=strlen($part['text']);$i<$m;$i++) {
                $char=$part['text'][$i];
                if($char==='(')$depth++;
                elseif($char===')')$depth=max(0,$depth-1);
                elseif($char===',' && $depth===0){$at=$part['offset']+$i;$result[]=trim(substr($text,$start,$at-$start));$start=$at+1;}
            }
        }
        $result[]=trim(substr($text,$start));return $result;
    }

    /**
     * 功能说明：直接添加insert的插入值
     *
     * @param mixed ...$args 一行的位置值列表，或若干行数组；与field列顺序对应（历史位置参数，由func_get_args读取）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function values() {
        $val = func_get_args();
        if(count($val)>0) {
            if(is_array($val[0])) {
                $this->values = array_merge($this->values, $val);
            } else {
                $this->values[] = $val;
            }
        }
        return $this;
    }

    /**
     * 功能说明：追加排序字段，支持数组形式的排序方向，读取时不修改内部状态
     *
     * @param string|array|null $field 字段或可信函数表达式，亦可传 [字段, 是否降序]；
     *                                null 读取排序 SQL，[reset] 清空排序（默认：null）
     * @param bool $desc true 降序、false 升序；数组模式使用其第 1 项，函数模式忽略（默认：false）
     * @return $this|string|null 添加返回当前构建器，无参返回 SQL，[reset] 不返回值
     */
    public function order($field=null, $desc = false) {
        if(is_null($field)) {
            $order = '';
            if(!empty($this->order)) {
                $org = $this->order;
                for($i=0,$m=count($this->order);$i<$m;$i++) {
                    if($i>0 ) $order .= ', ';
                    if(!empty($this->idx) && !preg_match('/^[\w\s]+\(/', $this->order[$i])) $this->order[$i] = $this->dl.$this->idx.$this->dr.'.'.$this->order[$i];
                    $order .= $this->order[$i];
                }
                $this->order = $org;
            }
            return $order;
        } elseif($field=='[reset]') {
            $this->order = array();
            return;
        }
        if(is_array($field)) {
            $desc = $field[1] ?? false;
            $field = $field[0];
        }
        if(preg_match('#^[\w\s]+\(.*\)$#', $field)) {
            $the_order = $field;
            $desc = false;
        } else {
            $the_order = $this->dl.$this->safeName($field).$this->dr;
        }
        if($desc) $the_order .= ' desc';
        $this->order[] = $the_order;
        return $this;
    }

    /**
     * 功能说明：设置或读取分页片段，数值转为非负整数
     *
     * @param int|string|array|null $start 单参数表示条数；与 count 合用表示偏移；
     *                                     可传 [偏移, 条数] 或逗号/空格分隔文本；null 读取，[reset] 清空（默认：null）
     * @param int|null $count 第二参数表示条数；仅当 start 为数值时采用（默认：null）
     * @return $this|string|null 设置返回当前构建器，无参返回含 limit 的片段，[reset] 不返回值
     */
    public function limit($start=null, $count=null) {
        if(is_null($start)) {
            if($this->limit === '') return '';
            return ' limit '.$this->limit;
        } elseif($start==='[reset]') {
            $this->limit = '';
            return;
        }
        if(is_numeric($start)) {
            $this->limit = max(0, (int)$start);
            if(is_numeric($count)) $this->limit = $this->limit . ',' . max(0, (int)$count);
        } else {
            if(is_string($start)) {
                $start = preg_split('#[\s,]+#', $start);
            }
            $this->limit = max(0, (int)($start[0] ?? 0));
            if(isset($start[1]) && is_numeric($start[1])) $this->limit = $this->limit . ',' . max(0, (int)$start[1]);
        }
        return $this;
    }

    /**
     * 功能说明：设置 GROUP BY 及可选 HAVING，或读取当前分组结构
     *
     * @param string|array|null $group 分组字段，或含 field/having 的数组，也可传 [字段, 条件]；
     *                                null 返回当前结构，字段列表可为逗号字符串或数组（默认：null）
     * @param mixed ...$args 兼容第二位置参数：HAVING条件（历史位置参数，由func_get_args读取）
     * @return $this|array 设置返回当前构建器；读取返回结构数组，SQL 生成时不改写其内容
     */
    public function group($group=null) {
        if(is_null($group)) {
            return $this->group;
        } else {
            if(func_num_args()>1) $group = func_get_args();
            if(is_string($group)) $group = ['field'=>$group];
            if(isset($group[0])) {
                $group['field'] = $group[0];
                unset($group[0]);
            }
            if(is_array($group['field'])) $group['field'] = implode(',', $group['field']);
            $group['field'] = str_replace(' ', '', $group['field']);
            if(isset($group[1])) {
                $group['having'] = $group[1];
                unset($group[1]);
            }
            if(isset($group['field'])) $this->group = $group;
        }
        return $this;
    }

    /**
     * 功能说明：生成 SELECT SQL，保留去重前缀、条件、排序、分组/HAVING 和分页
     *
     * 参数说明：无显式参数。
     *
     * @return string SQL 文本，不执行查询；分组及字段预览不消耗已有构建状态
     */
    public function select() {
        $idx = $this->idx;
        $this->idx = '';
        try {
        $sql = 'select ';
        if(!empty($this->sel_prefix)) $sql = $sql.$this->sel_prefix.' ';
        if(empty($this->fields)) {
            $sql .= '*';
        } else {
            $sql .= $this->field();
        }
        $sql .= ' from '.$this->tbl;
        if(!empty($idx)) $sql .= ' as '.$idx;
        if(!empty($this->condition)) {
            $sql .= ' where '.$this->where();
        }
        if(!empty($this->group)) {
             $group_fields = is_string($this->group['field']) ? explode(',', $this->group['field']) : $this->group['field'];
            $group_fields = array_map(function($str) {
                                                return $this->dl.$this->safeName($str).$this->dr;
                                            }, $group_fields);
            $sql .= ' group by '.implode(',', $group_fields);
            if(isset($this->group['having']) && !empty($this->group['having'])) {
                $tmp = new self('tmp', [], '', [$this->dl,$this->dr], $this->escaper);
                $tmp->where($this->group['having']);
                $sql .= ' having '.$tmp->where();
                unset($tmp);
            }
        }
        if(!empty($this->order)) {
            $sql .= ' order by '.$this->order();
        }
        $sql .= $this->limit();
        return $sql;
        } finally { $this->idx = $idx; }
    }

    /**
     * 功能说明：生成 INSERT/REPLACE，列列表支持 field(列名)，位置行使用局部副本，预览不消耗待写数据
     *
     * @param bool $replace 是否生成REPLACE而非INSERT语句（默认：false）
     * @return string 普通值仍沿用旧函数表达式/null 文本约定；不可信值优先参数绑定
     */
    public function insert($replace=false) {
        $sql = ($replace?'replace':'insert').' into '.$this->tbl;
        $fields = array();
        $values = array();
        foreach($this->fields as $key => $value) {
            if(is_numeric($key)) { $fields[]=$value; continue; }
            if(strtolower($key)==='submit')continue;
            if(is_null($value) || strtolower($value)=='null') {
                $value = 'NULL';
            } elseif(!preg_match('/^\w{3,15}\(.*\)$/', $value)) {
                $value = "'".$this->safeValue($value)."'";
            }
            $fields[] = $this->dl.$this->safeName($key).$this->dr;
            $values[] = $value;
        }
        $pending = $this->values;
        for($i=0,$m=count($pending);$i<$m;$i++) {
            if(!is_array($pending[$i])) continue;
            for($j=0,$m2=count($pending[$i]);$j<$m2;$j++) {
                if($pending[$i][$j]===null || strtolower($pending[$i][$j])=='null') {
                    $pending[$i][$j] = 'NULL';
                } elseif(!preg_match('/^\w{3,15}\(.*\)$/', $pending[$i][$j])) {
                    $pending[$i][$j] = "'".$this->safeValue($pending[$i][$j])."'";
                }
            }
            $pending[$i] = '('.implode(',', $pending[$i]).')';
        }
        if(!empty($fields)) $sql .= ' ('.implode(',', $fields).')';
        $sql .= ' values ';
        if(count($values)>0) $sql .= ' ('.implode(',', $values).')';
        if(count($pending)>0) $sql .= ','.implode(',', $pending);
        $sql = str_replace('values ,(', 'values (', $sql);
        return $sql;
    }

    /**
     * 功能说明：生成更新查询语句
     *
     * 参数说明：无显式参数。
     *
     * @return string 根据当前字段/条件/排序/分页生成的UPDATE文本，不执行SQL
     */
    public function update() {
        $idx = $this->idx;
        $this->idx = '';
        try {
        $sql = 'update '.$this->tbl;
        $sql .= ' set '.$this->field();
        
        if(!empty($this->condition)) {
            $sql .= ' where '.$this->where();
        }
        if(!empty($this->order)) {
            $sql .= ' order by '.$this->order();
        }
        $sql .= $this->limit();
        return $sql;
        } finally { $this->idx = $idx; }
    }

    /**
     * 功能说明：生成删除查询语句
     *
     * 参数说明：无显式参数。
     *
     * @return string 根据当前条件生成的DELETE/TRUNCATE文本，不执行SQL
     */
    public function delete() {
        $idx = $this->idx;
        $this->idx = '';
        try {
        if(empty($this->condition) && $this->limit === '') {
            $sql = 'truncate table '.$this->tbl;
        } else {
            $sql = 'delete from '.$this->tbl;
            if(!empty($this->condition)) {
                $sql .= ' where '.$this->where();
            }
            if(!empty($this->order)) {
                $sql .= ' order by '.$this->order();
            }
            $sql .= $this->limit();
        }
        return $sql;
        } finally { $this->idx = $idx; }
    }
}