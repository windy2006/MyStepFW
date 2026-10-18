<?PHP
/********************************************
*                                           *
* Name    : Functions for minify CSS or JS  *
* Modifier: Windy2000                       *
* Time    : 2018-11-02                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myMinify.class.php
 * 功能说明：JS/CSS压缩与文件缓存工具
 *   使用JSMin/CssMin压缩合并输入，按源码/引擎摘要复用缓存并原子发布。
 *
 * 方法目录：
 *   public myMinify::init($mode = 'js', $cache = '', $renew = false) — 初始化类变量
 *   public myMinify::check($expires = 604800) — 检测是否已有缓存
 *   public myMinify::add($code) — 添加文件
 *   protected myMinify::source() — 读取当前输入并记录文件摘要；JS片段之间插入分号，避免拼接改变表达式边界。
 *   protected myMinify::signature($code, $pack) — 计算源码和当前模式压缩引擎的指纹；只加载实际使用的组件。
 *   protected myMinify::atomicWrite($path, $content) — 在目标目录写入临时文件并原子发布代码或元数据。
 *   public myMinify::get($pack = false) — 生成或复用压缩代码；JS统一使用JSMin，CSS使用CssMin。
 *   public myMinify::show($pack = false) — 输出压缩代码、缓存及内容类型响应头，并结束请求。
 *   public static myMinify::minify($code, $mode = 'js') — 压缩代码
 *   public myMinify::__toString() — 返回当前已有压缩结果，不主动触发生成
 *
 * 使用说明：
 *   1. add可接收同类型文件或源码，get返回结果，show发送响应并结束请求。
 *   2. 旧pack参数保留调用兼容，true/false均采用普通压缩。
 *   3. 组件或本文件内容变化会使引擎指纹失效，调用方需按原约定保留源输入。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：使用JSMin/CssMin压缩合并输入，按源码/引擎摘要复用缓存并原子发布。
 *
 * 使用说明：
 *   1. add可接收同类型文件或源码，get返回结果，show发送响应并结束请求。
 *   2. 旧pack参数保留调用兼容，true/false均采用普通压缩。
 *   3. 组件或本文件内容变化会使引擎指纹失效，调用方需按原约定保留源输入。
 *
 * 补充说明：
 * 生成js或css的压缩代码，使用JSMin/CssMin；旧pack参数仅保留调用兼容。
 * 不再依赖JavaScriptPacker或生成eval打包代码，组件变化会使缓存指纹失效。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myMinify.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myMinify extends myBase {
    protected
        $mode = null,
        $cache = '',
        $content = '',
        $result = '';
    protected $inputs = [];

    /**
     * 功能说明：初始化类变量
     *
     * @param string $mode js使用JSMin，其他值按CSS模式处理（默认：'js'）
     * @param string $cache 压缩结果缓存文件路径，空串不写文件（默认：''）
     * @param bool $renew 是否清除已有结果和元数据后重新初始化（默认：false）
     * @return void 无返回值。
     */
    public function init($mode = 'js', $cache = '', $renew = false) {
        if($mode != 'js') $mode = 'css';
        $this->mode = $mode;
        $this->cache = $cache === '' ? '' : myFile::realPath($cache);
        if($renew && is_file($this->cache)) unlink($this->cache);
        if($renew && is_file($this->cache.'.meta')) unlink($this->cache.'.meta');
        $this->inputs = []; $this->content = ''; $this->result = '';
    }

    /**
     * 功能说明：检测是否已有缓存
     *
     * @param int $expires 缓存有效秒数（默认：604800）
     * @return bool 源码/引擎指纹及有效期均匹配为true，否则为false；命中时载入压缩结果
     */
    public function check($expires = 604800) {
        if($this->cache === '' || !is_file($this->cache)) return false;
        clearstatcache(true, $this->cache);
        if(filemtime($this->cache)+$expires <= (myReq::server('REQUEST_TIME') ?? time())) return false;
        $meta = is_file($this->cache.'.meta') ? json_decode(file_get_contents($this->cache.'.meta'), true) : null;
        if(!is_array($meta) || ($meta['engine'] ?? '') !== $this->signature('', false) || ($meta['output'] ?? '') !== hash_file('sha256', $this->cache)) return false;
        foreach($meta['files'] ?? [] as $file => $hash) if(!is_file($file) || hash_file('sha256', $file) !== $hash) return false;
        if($this->inputs) {
            list($code, $files) = $this->source();
            return ($meta['signature'] ?? '') === $this->signature($code, (bool)($meta['pack'] ?? false));
        }
        return true;
    }

    /**
     * 功能说明：添加文件
     *
     * @param string $code 代码文本或状态代码，按对应入口解释
     * @return $this 返回当前实例，支持链式调用。
     */
    public function add($code) {
        if(is_file($code)) {
            if(strtolower(pathinfo($code, PATHINFO_EXTENSION)) !== $this->mode) return $this;
            $this->inputs[] = ['file', realpath($code)];
        } else $this->inputs[] = ['code', (string)$code];
        return $this;
    }

    /**
     * 功能说明：读取当前输入并记录文件摘要；JS片段之间插入分号，避免拼接改变表达式边界。
     *
     * 参数说明：无显式参数。
     *
     * @return array 源码及文件路径到SHA-256摘要的映射
     * @throws RuntimeException 输入文件已丢失或读取失败
     */
    protected function source() {
        $chunks = []; $files = [];
        foreach($this->inputs as $input) {
            if($input[0] === 'file') {
                if(!is_file($input[1])) throw new RuntimeException('Missing minification source');
                $code = file_get_contents($input[1]);
                if($code === false) throw new RuntimeException('Cannot read minification source');
                $files[$input[1]] = hash('sha256', $code);
            } else $code = $input[1];
            $chunks[] = $code;
        }
        return [implode($this->mode === 'js' ? "\n;\n" : "\n", $chunks), $files];
    }

    /**
     * 功能说明：计算源码和当前模式压缩引擎的指纹；只加载实际使用的组件。
     *
     * @param string $code 待压缩源码，空串用于引擎指纹
     * @param bool $pack 历史兼容参数，已不影响输出或缓存身份
     * @return string SHA-256摘要
     */
    protected function signature($code, $pack) {
        $dependencies = [hash_file('sha256', __FILE__)];
        foreach($this->mode === 'js' ? ['JSMin\\JSMin'] : ['CssMin'] as $class) {
            if(class_exists($class)) { $file = (new ReflectionClass($class))->getFileName(); if($file) $dependencies[] = hash_file('sha256', $file); }
        }
        return hash('sha256', serialize([$this->mode, $dependencies, $code]));
    }

    /**
     * 功能说明：在目标目录写入临时文件并原子发布代码或元数据。
     *
     * @param string $path 目标文件路径
     * @param string $content 完整文件内容
     * @return void 无返回值。
     * @throws RuntimeException 临时文件、写入、权限或替换失败
     */
    protected function atomicWrite($path, $content) {
        $tmp = tempnam(dirname($path), '.minify-');
        if($tmp === false) throw new RuntimeException('Cannot create minification cache');
        try {
            if(file_put_contents($tmp, $content, LOCK_EX) !== strlen($content) || (substr($path, -5) !== '.meta' && !chmod($tmp, 0644)) || !rename($tmp, $path)) throw new RuntimeException('Cannot publish minification cache');
        } finally { if(is_file($tmp)) unlink($tmp); }
    }

    /**
     * 功能说明：生成或复用压缩代码；JS统一使用JSMin，CSS使用CssMin。
     *
     * @param bool $pack 历史兼容参数，true/false均普通压缩，不再执行旧eval打包（默认：false）
     * @return string 压缩代码
     * @throws Throwable 源文件、组件压缩或缓存发布失败
     */
    public function get($pack = false) {
        if(!$this->inputs && $this->check()) return $this->result = file_get_contents($this->cache);
        list($code, $files) = $this->source();
        $signature = $this->signature($code, (bool)$pack);
        $lock = null;
        if($this->cache !== '') {
            if(!myFile::mkdir(dirname($this->cache))) throw new RuntimeException('Cannot create minification directory');
            $lock = fopen($this->cache.'.lock', 'c');
            if(!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock minification cache');
        }
        try {
            $meta = $this->cache !== '' && is_file($this->cache.'.meta') ? json_decode(file_get_contents($this->cache.'.meta'), true) : null;
            if($this->check() && ($meta['signature'] ?? '') === $signature) return $this->result = file_get_contents($this->cache);
            $this->result = self::minify($code, $this->mode);
            if($this->cache !== '') {
                $this->atomicWrite($this->cache, $this->result);
                $this->atomicWrite($this->cache.'.meta', json_encode(['engine'=>$this->signature('', false), 'signature'=>$signature, 'output'=>hash('sha256', $this->result), 'files'=>$files, 'pack'=>false]));
            }
            return $this->result;
        } finally { if(is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); } }
    }

    /**
     * 功能说明：输出压缩代码、缓存及内容类型响应头，并结束请求。
     *
     * @param bool $pack 历史兼容参数，不再启用eval打包（默认：false）
     * @return void 无返回值。
     */
    public function show($pack = false) {
        $this->get($pack);
        header("Expires: " . gmdate("D, j M Y H:i:s", time()+604800) ." GMT");
        if($this->mode == 'js') {
            header('Content-Type:text/javascript');
        } else {
            header('Content-Type:text/css');
        }
        header("Content-Length: ".strlen($this->result));
        echo $this->result;
        exit;
    }

    /**
     * 功能说明：压缩代码
     *
     * @param string $code 代码文本或状态代码，按对应入口解释
     * @param string $mode js使用JSMin，其他值按CSS模式处理（默认：'js'）
     * @return string 按mode压缩后的JS/CSS文本，不写缓存也不发送响应头
     */
    public static function minify($code, $mode = 'js') {
        if($mode == 'js') {
            $code = \JSMin\JSMin::minify($code);
        } else {
            $code = CssMin::minify($code,[
                "ImportImports"					=> false,
                "RemoveComments"				=> true,
                "RemoveEmptyRulesets"			=> true,
                "RemoveEmptyAtBlocks"			=> true,
                "ConvertLevel3Properties"		=> false,
                "ConvertLevel3AtKeyframes"		=> false,
                "Variables"						=> true,
                "RemoveLastDelarationSemiColon"	=> true
            ], [
                "Variables"						=> false,
                "ConvertFontWeight"				=> false,
                "ConvertHslColors"				=> false,
                "ConvertRgbColors"				=> false,
                "ConvertNamedColors"			=> false,
                "CompressColorValues"			=> false,
                "CompressUnitValues"			=> false,
                "CompressExpressionValues"		=> false
            ]);
        }
        return $code;
    }

    /**
     * 功能说明：返回当前已有压缩结果，不主动触发生成
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前保存的压缩结果
     */
    public function __toString() {
        if(empty($this->result)) $this->get();
        return $this->result;
    }
}