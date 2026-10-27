<?PHP
/********************************************
*                                           *
* Name    : Functions For String Operations *
* Modifier: Windy2000                       *
* Time    : 2018-10-18                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myString.class.php
 * 功能说明：字符串、结构序列化与字符编码工具
 *   提供HEX/INI/JSON/XML/PHP脚本转换、HTML处理、字符集和文本操作。
 *
 * 方法目录：
 *   public myString::__construct($str='', $from_charset = null) — 初始化字符串及已知/推测编码，显式来源不会被后续 get() 再猜测覆盖
 *   public myString::__toString() — 作为字符串显示时的操作
 *   public myString::set($val, $from_charset = null) — 设置默认字符串及来源，验证通过后才替换原值
 *   public myString::get($charset='') — 读取默认字符串，指定目标时严格转换并记住明确编码
 *   public static myString::normalizeCharset($charset) — 规范字符集名称并校验后端支持，拒绝非字符编码及隐含丢弃选项
 *   protected static myString::charsetBom($text) — 识别 Unicode BOM，UTF-32 先于共享前缀的 UTF-16 检查
 *   protected static myString::iconvCharset($encoding) — 将规范名称映射到 iconv 后端使用的别名
 *   protected static myString::validCharset($text, $encoding) — 严格检查字节串能否按给定编码解释，不把“合法字节”当作来源证明
 *   public static myString::charset($str, $encodings = null) — 在候选集内按明确顺序推测编码，BOM 优先；不声称能自动识别真实来源
 *   protected static myString::charsetOptions(array $options) — 校验转换选项，避免拼错策略后悄悄退回有损默认值
 *   public static myString::convertCharset($text, $from = null, $to = 'UTF-8', array $options = []) — 在两个明确编码之间转换原始字节，严格模式验证往返，不静默丢失数据
 *   protected static myString::charsetTree($value, $to, $from, array $options, SplObjectStorage $copies, SplObjectStorage $active, $depth = 0, $json = false) — 递归复制并转换值，拒绝循环及过深结构；JSON 模式只取可序列化的数据形状
 *   public static myString::setCharset($str, $charset='UTF-8', $from_charset = null, array $options = []) — 兼容批量转码入口，支持明确来源，复制对象而不原地修改
 *   public static myString::toHex($str) — 将字符串转换为16进制
 *   public static myString::fromHex($hex) — 将16进制转换为字符串
 *   public static myString::fromIni($ini, $mode = true) — 以相同的原始扫描规则解析INI文件或INI文本
 *   public static myString::toIni($array, $level = 1) — 递归把配置数组转换为带分组的INI文本
 *   public static myString::toJson($var, $charset='UTF-8', $from_charset = null) — 编码为 UTF-8 JSON，复制转换值及非 UTF-8 键，不修改输入对象
 *   public static myString::fromJson($json, $assoc=true) — 解析JSON文本，按assoc选择关联数组或对象
 *   public static myString::fromAny($var) — 将任意值转换为文本，复杂类型使用PHP序列化
 *   public static myString::toXML($var, $item_tag='item', $main_tag='') — 将数组转换为XML
 *   public static myString::toScript($var, $name='') — 转换变量为php变量脚本
 *   public static myString::fileCharset($file_src, $file_dst='', $charset='UTF-8', $from_charset = null, array $options = []) — 在边界内完整转换本地文本文件，以同目录临时文件原子替换，失败保留旧文件
 *   public static myString::htmlTrans($str) — 将字符串转换为超文本格式
 *   public static myString::txt2html($content) — 将纯文本转换为超文本格式
 *   public static myString::rndKey($length, $scope=1, $charset='UTF-8') — 生成随机字符串或可在目标编码表示的随机汉字
 *   public static myString::addSlash(&$str) — 批量添加转移斜杠
 *   public static myString::stripSlash(&$str) — 批量去除转移斜杠
 *   public static myString::breakStr($str) — 将字符串打碎为单个的字符或汉字
 *   public static myString::watermark($html, $rate=2, $scope=4, $str_append='', $charset='utf-8', $class_name='watermark', $tag_name='span', $jam_tag=false) — 为超文本添加水印
 *   public static myString::substr($string, $start, $length = 0, $mode = false) — 截取任意字符串，可为任意字符集的中文
 *
 * 使用说明：
 *   1. 编码转换明确来源/目标，严格或容错策略按方法参数选择。
 *   2. 数组/对象转换、BOM和文件原子转换见相关入口；不可表示字符按所选策略处理。
 *   3. 字符串转义与HTML转义各有用途，不能替代SQL值绑定或完整HTML过滤。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：提供HEX/INI/JSON/XML/PHP脚本转换、HTML处理、字符集和文本操作。
 *
 * 使用说明：
 *   1. 编码转换明确来源/目标，严格或容错策略按方法参数选择。
 *   2. 数组/对象转换、BOM和文件原子转换见相关入口；不可表示字符按所选策略处理。
 *   3. 字符串转义与HTML转义各有用途，不能替代SQL值绑定或完整HTML过滤。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myString.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myString {
    use myTrait;

    public static
        $func_alias = array(
            'c' => 'charset',
            'sc' => 'setCharset',
            'hex' => 'toHex',
            's16' => 'fromHex',
            'json' => 'toJson',
            'obj' => 'fromJson',
            'str' => 'fromAny',
            'xml' => 'toXML',
            'html' => 'htmlTrans',
            'rnd' => 'rndKey',
            'txt' => 'txt2html',
        );

    protected
        $str = '',
        $charset = '',
        $func_str = array('myString', 'get');

    /**
     * 功能说明：初始化字符串及已知/推测编码，显式来源不会被后续 get() 再猜测覆盖
     *
     * @param string|scalar|null|object $str 初值，允许可转字符串标量和 __toString 对象（默认：''）
     * @param string|null $from_charset 可选明确来源编码；省略时在候选集中推测（默认：null）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct($str='', $from_charset = null) { $this->set($str, $from_charset); }

    /**
     * 功能说明：作为字符串显示时的操作
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前字符串按get约定取得的文本
     */
    public function __toString() {
        return (string)$this->get();
    }

    /**
     * 功能说明：设置默认字符串及来源，验证通过后才替换原值
     *
     * @param string|scalar|null|object $val 初值，不接受数组、资源及不可转字符串对象
     * @param string|null $from_charset 明确来源；null 推测，不能确定时保存未知状态（默认：null）
     * @return void 不立即改变编码，不移除原始 BOM
     * @throws InvalidArgumentException 类型或编码非法
     * @throws UnexpectedValueException 显式来源与字节格式不符
     */
    public function set($val, $from_charset = null) {
        if(!is_scalar($val) && $val !== null && !(is_object($val) && method_exists($val, '__toString'))) throw new InvalidArgumentException('String value must be scalar or stringable');
        $text = (string)$val;
        $encoding = $from_charset === null ? self::charset($text) : self::normalizeCharset($from_charset);
        if($from_charset !== null && !self::validCharset($text, $encoding)) throw new UnexpectedValueException('Invalid explicit source charset');
        $this->str = $text; $this->charset = $encoding;
    }

    /**
     * 功能说明：读取默认字符串，指定目标时严格转换并记住明确编码
     *
     * @param string $charset 空字符串返回原始字节；非空指定目标编码（默认：''）
     * @return string 成功转换后更新实例状态；失败保留旧字符串及来源
     * @throws Throwable 来源未知、不能无损转换或目标非法时明确报错
     */
    public function get($charset='') {
        if($charset !== '') {
            $target = self::normalizeCharset($charset);
            $converted = self::convertCharset($this->str, $this->charset ?: null, $target);
            $this->str = $converted;
            $this->charset = in_array($target, ['UTF-16','UTF-32'], true) ? $target.'BE' : $target;
        }
        return $this->str;
    }





    /**
     * 功能说明：规范字符集名称并校验后端支持，拒绝非字符编码及隐含丢弃选项
     *
     * @param string $charset 名称或常见别名，例如 utf8、CP936、latin1、Shift-JIS
     * @return string 规范名称，例如 UTF-8、GBK、ISO-8859-1；不接受 //IGNORE 等后缀
     * @throws InvalidArgumentException 名称非法、不是字符编码或当前后端不支持
     * @throws RuntimeException 没有可用的 mbstring/iconv 转换后端
     */
    public static function normalizeCharset($charset) {
        if(!is_string($charset) || !preg_match('/\A[A-Za-z][A-Za-z0-9._-]{0,63}\z/D', $charset)) throw new InvalidArgumentException('Invalid charset name');
        static $aliases = null;
        if($aliases === null) {
            $aliases = [];
            $key = function($name) { return strtoupper(str_replace(['-','_','.'], '', $name)); };
            if(function_exists('mb_list_encodings') && function_exists('mb_encoding_aliases')) foreach(mb_list_encodings() as $encoding) {
                if(in_array($key($encoding), ['BASE64','QPRINT','QUOTEDPRINTABLE','UUENCODE','HTMLENTITIES','7BIT','8BIT','PASS'], true)) continue;
                $canonical = strtoupper($encoding); if($canonical === 'CP936') $canonical = 'GBK';
                foreach(array_merge([$encoding], mb_encoding_aliases($encoding) ?: []) as $alias) $aliases[$key($alias)] = $canonical;
            }
            foreach(['UTF8'=>'UTF-8','ASCII'=>'ASCII','USASCII'=>'ASCII','GBK'=>'GBK','CP936'=>'GBK','WINDOWS936'=>'GBK','GB2312'=>'EUC-CN','EUCCN'=>'EUC-CN','GB18030'=>'GB18030','BIG5'=>'BIG5','CP950'=>'CP950','UTF16'=>'UTF-16','UTF16LE'=>'UTF-16LE','UTF16BE'=>'UTF-16BE','UTF32'=>'UTF-32','UTF32LE'=>'UTF-32LE','UTF32BE'=>'UTF-32BE','LATIN1'=>'ISO-8859-1','ISO88591'=>'ISO-8859-1','WINDOWS1251'=>'WINDOWS-1251','CP1251'=>'WINDOWS-1251','WINDOWS1252'=>'WINDOWS-1252','CP1252'=>'WINDOWS-1252','SJIS'=>'SJIS','SHIFTJIS'=>'SJIS','CP932'=>'SJIS-WIN'] as $alias => $canonical) if(!isset($aliases[$alias])) $aliases[$alias] = $canonical;
        }
        $key = strtoupper(str_replace(['-','_','.'], '', $charset));
        if(!isset($aliases[$key])) throw new InvalidArgumentException('Unsupported charset');
        $encoding = $aliases[$key];
        if(!function_exists('mb_convert_encoding') && !function_exists('iconv')) throw new RuntimeException('Charset conversion requires mbstring or iconv');
        return $encoding;
    }

    /**
     * 功能说明：识别 Unicode BOM，UTF-32 先于共享前缀的 UTF-16 检查
     *
     * @param string $text 原始字节串
     * @return array|null [明确字节序编码, BOM字节数]，没有已知 BOM 为 null
     */
    protected static function charsetBom($text) {
        foreach(["\x00\x00\xfe\xff"=>'UTF-32BE',"\xff\xfe\x00\x00"=>'UTF-32LE',"\xef\xbb\xbf"=>'UTF-8',"\xfe\xff"=>'UTF-16BE',"\xff\xfe"=>'UTF-16LE'] as $mark => $encoding) if(strncmp($text, $mark, strlen($mark)) === 0) return [$encoding, strlen($mark)];
        return null;
    }

    /**
     * 功能说明：将规范名称映射到 iconv 后端使用的别名
     *
     * @param string $encoding 已规范名称
     * @return string iconv 标签；例如 mbstring 的 SJIS-WIN 对应 CP932
     */
    protected static function iconvCharset($encoding) { return $encoding === 'SJIS-WIN' ? 'CP932' : $encoding; }

    /**
     * 功能说明：严格检查字节串能否按给定编码解释，不把“合法字节”当作来源证明
     *
     * @param string $text 原始字节串
     * @param string $encoding 已规范的字符集
     * @return bool 格式合法为 true；UTF-8/ASCII 使用快速检查，其他优先 mbstring
     * @throws RuntimeException 非UTF-8/ASCII校验缺少mbstring及iconv后端
     */
    protected static function validCharset($text, $encoding) {
        if($encoding === 'UTF-8') return preg_match('//u', $text) === 1;
        if($encoding === 'ASCII') return !preg_match('/[\x80-\xff]/', $text);
        if(function_exists('mb_check_encoding')) return mb_check_encoding($text, $encoding);
        if(!function_exists('iconv')) throw new RuntimeException('Charset validation requires mbstring or iconv');
        try { return @iconv(self::iconvCharset($encoding), 'UTF-8', $text) !== false; } catch(Throwable $error) { return false; }
    }

    /**
     * 功能说明：在候选集内按明确顺序推测编码，BOM 优先；不声称能自动识别真实来源
     *
     * @param string $str 待检查字节串；空串及普通 ASCII 默认按 UTF-8 兼容
     * @param array|string|null $encodings 候选列表或逗号串；默认 UTF-8、GBK、BIG5、GB18030（默认：null）
     * @return string|false 首个严格合法候选，BOM 编码只有符合候选时使用；均不符为 false
     * @throws InvalidArgumentException 输入/候选非法；单字节西文编码须由调用方明确加入
     */
    public static function charset($str, $encodings = null) {
        if(!is_string($str)) throw new InvalidArgumentException('Charset detection expects a string');
        $explicit = $encodings !== null;
        if($encodings === null) $encodings = ['UTF-8','GBK','BIG5','GB18030'];
        elseif(is_string($encodings)) $encodings = array_map('trim', explode(',', $encodings));
        if(!is_array($encodings) || !$encodings) throw new InvalidArgumentException('Charset candidates must not be empty');
        $candidates = []; foreach($encodings as $encoding) $candidates[] = self::normalizeCharset($encoding);
        $bom = self::charsetBom($str);
        if($bom !== null) {
            $family = preg_replace('/(?:LE|BE)\z/', '', $bom[0]);
            if($explicit && !in_array($bom[0], $candidates, true) && !in_array($family, $candidates, true)) return false;
            return self::validCharset(substr($str, $bom[1]), $bom[0]) ? $bom[0] : false;
        }
        foreach(array_unique($candidates) as $encoding) if(self::validCharset($str, $encoding)) return $encoding;
        return false;
    }

    /**
     * 功能说明：校验转换选项，避免拼错策略后悄悄退回有损默认值
     *
     * @param array $options on_error=throw/replace/ignore，bom=remove/preserve/add，backend=auto/mbstring/iconv，
     *                       convert_keys 默认 false，max_depth 默认 64；文件另支持 max_bytes 默认 32 MiB
     * @return array 补齐默认值的选项；严格策略默认拒绝损失，容错必须显式选择
     * @throws InvalidArgumentException 未知选项、枚举值或边界类型非法
     */
    protected static function charsetOptions(array $options) {
        $defaults = ['on_error'=>'throw','bom'=>'remove','backend'=>'auto','convert_keys'=>false,'max_depth'=>64,'max_bytes'=>33554432];
        if(array_diff_key($options, $defaults)) throw new InvalidArgumentException('Unknown charset option');
        $options = array_replace($defaults, $options);
        foreach(['on_error'=>['throw','replace','ignore'],'bom'=>['remove','preserve','add'],'backend'=>['auto','mbstring','iconv']] as $key => $values) if(!in_array($options[$key], $values, true)) throw new InvalidArgumentException('Invalid charset policy');
        if(!is_bool($options['convert_keys']) || !is_int($options['max_depth']) || $options['max_depth'] < 1 || $options['max_depth'] > 512 || !is_int($options['max_bytes']) || $options['max_bytes'] < 1) throw new InvalidArgumentException('Invalid charset limits');
        return $options;
    }

    /**
     * 功能说明：在两个明确编码之间转换原始字节，严格模式验证往返，不静默丢失数据
     *
     * @param string $text 原始字符串，不能直接用于任意压缩/图片等二进制文件
     * @param string|null $from 来源编码；null/auto 使用 charset() 推测，已知来源应显式指定（默认：null）
     * @param string $to 目标编码，默认 UTF-8；UTF-16/32 泛称统一按 BE 并输出 BOM（默认：'UTF-8'）
     * @param array $options on_error 默认 throw；replace/ignore 明确允许替换/丢弃；BOM 默认 remove；
     *                       preserve 保留有 BOM 的 Unicode 标记，add 强制添加；后端及边界见 charsetOptions()（默认：[]）
     * @return string 转换字节；显式同编码仍校验输入，不用猜测覆盖已知编码
     * @throws InvalidArgumentException 编码/选项非法、来源与 BOM 冲突或目标不支持所需 BOM
     * @throws UnexpectedValueException 来源未知、字节非法或严格转换不能往返
     * @throws RuntimeException 后端不可用、不能执行选定容错策略或转换失败
     */
    public static function convertCharset($text, $from = null, $to = 'UTF-8', array $options = []) {
        if(!is_string($text)) throw new InvalidArgumentException('Charset conversion expects a string');
        $options = self::charsetOptions($options); $to = self::normalizeCharset($to);
        if($from === null || is_string($from) && strcasecmp($from, 'auto') === 0) $from = self::charset($text);
        else $from = self::normalizeCharset($from);
        if($from === false) throw new UnexpectedValueException('Cannot infer source charset; specify it explicitly');
        $bom = self::charsetBom($text); $hadBom = $bom !== null;
        if($bom !== null) {
            if($from !== $bom[0] && $from !== preg_replace('/(?:LE|BE)\z/', '', $bom[0])) throw new InvalidArgumentException('Source charset conflicts with BOM');
            $from = $bom[0]; $text = substr($text, $bom[1]);
        }
        $generic = in_array($to, ['UTF-16','UTF-32'], true);
        if(in_array($from, ['UTF-16','UTF-32'], true)) $from .= 'BE';
        if($generic) $to .= 'BE';
        $strict = $options['on_error'] === 'throw';
        if($strict && !self::validCharset($text, $from)) throw new UnexpectedValueException('Invalid source bytes for '.$from);
        $mb = function_exists('mb_convert_encoding') && function_exists('mb_substitute_character');
        $backend = $options['backend'] === 'auto' ? ($mb ? 'mbstring' : 'iconv') : $options['backend'];
        if($backend === 'mbstring') {
            if(!$mb) throw new RuntimeException('mbstring conversion is unavailable');
            $previous = mb_substitute_character();
            try {
                mb_substitute_character($options['on_error'] === 'ignore' ? 'none' : 0xfffd);
                $result = mb_convert_encoding($text, $to, $from);
                if($result === false) throw new RuntimeException('Charset conversion failed');
                if($strict && mb_convert_encoding($result, $from, $to) !== $text) throw new UnexpectedValueException('Charset conversion would lose or normalize source data');
            } finally { mb_substitute_character($previous); }
        } else {
            if(!function_exists('iconv')) throw new RuntimeException('iconv conversion is unavailable');
            if($options['on_error'] === 'replace') throw new RuntimeException('Replacement policy requires mbstring; choose strict or ignore for iconv');
            $suffix = $options['on_error'] === 'ignore' ? '//IGNORE' : '';
            try { $result = @iconv(self::iconvCharset($from), self::iconvCharset($to).$suffix, $text); } catch(Throwable $error) { $result = false; }
            if($result === false) throw new UnexpectedValueException('iconv could not convert source bytes without loss');
            if($strict) {
                try { $back = @iconv(self::iconvCharset($to), self::iconvCharset($from), $result); } catch(Throwable $error) { $back = false; }
                if($back !== $text) throw new UnexpectedValueException('Charset conversion would lose or normalize source data');
            }
        }
        $wantBom = $generic || $options['bom'] === 'add' || $hadBom && $options['bom'] === 'preserve';
        if($wantBom) {
            $marks = ['UTF-8'=>"\xef\xbb\xbf",'UTF-16LE'=>"\xff\xfe",'UTF-16BE'=>"\xfe\xff",'UTF-32LE'=>"\xff\xfe\x00\x00",'UTF-32BE'=>"\x00\x00\xfe\xff"];
            if(!isset($marks[$to])) throw new InvalidArgumentException('Target charset does not support a Unicode BOM');
            $result = $marks[$to].$result;
        }
        return $result;
    }

    /**
     * 功能说明：递归复制并转换值，拒绝循环及过深结构；JSON 模式只取可序列化的数据形状
     *
     * @param mixed $value 输入数据
     * @param string $to 已规范目标编码
     * @param string|null $from 明确来源或 null 自动推测
     * @param array $options 已补齐策略
     * @param SplObjectStorage $copies 已复制对象到结果的映射，共享引用仅转换一次
     * @param SplObjectStorage $active 当前递归栈，用于识别对象循环
     * @param int $depth 当前深度，根为 0，数组自引用同样受上限保护（默认：0）
     * @param object|false $json JSON 内部错误上下文；false 为普通复制转换（默认：false）
     * @return mixed 独立转换结果；非字符串标量和资源保持类型，资源仍不能 JSON 编码
     * @throws RuntimeException|JsonException 循环或深度超限，JSON 模式带对应错误码
     */
    protected static function charsetTree($value, $to, $from, array $options, SplObjectStorage $copies, SplObjectStorage $active, $depth = 0, $json = false) {
        if($depth > $options['max_depth']) { if($json) { $json->error = new JsonException('Charset tree depth exceeded', JSON_ERROR_DEPTH); throw $json->error; } throw new RuntimeException('Charset tree depth exceeded'); }
        if(is_string($value)) {
            if($value === '' && $from === null && $options['bom'] !== 'add') return '';
            // 自动模式中合法 UTF-8 不再做多编码探测；BOM 仍交给统一流程处理。
            if($to === 'UTF-8' && $from === null && $options['bom'] !== 'add' && self::charsetBom($value) === null && preg_match('//u', $value)) return $value;
            return self::convertCharset($value, $from, $to, $options);
        }
        if(!is_array($value) && !is_object($value)) return $value;
        $object = is_object($value) ? $value : null;
        if($object !== null) {
            if($active->offsetExists($object)) { if($json) { $json->error = new JsonException('Recursive charset object', JSON_ERROR_RECURSION); throw $json->error; } throw new RuntimeException('Recursive charset object'); }
            if($copies->offsetExists($object)) return $copies[$object];
            $active->offsetSet($object);
            try {
                if($json && $value instanceof JsonSerializable) {
                    $result = self::charsetTree($value->jsonSerialize(), $to, $from, $options, $copies, $active, $depth+1, $json);
                    $copies[$object] = $result; return $result;
                }
                if($json) $result = new stdClass();
                else {
                    try { $result = clone $value; } catch(Throwable $error) { throw new RuntimeException('Cannot copy object for charset conversion', 0, $error); }
                }
                $copies[$object] = $result;
                if($value instanceof ArrayObject) $entries = $value->getArrayCopy();
                else {
                    // 数组转换可取得 DateTime 等内部对象的 JSON 数据，带 NUL 的非公开键必须排除。
                    $public = array_filter((array)$value, function($key) { return !is_string($key) || strpos($key, "\0") === false; }, ARRAY_FILTER_USE_KEY);
                    $entries = $json ? $public : array_intersect_key(get_object_vars($value), $public);
                }
                $converted = [];
                foreach($entries as $key => $part) {
                    $targetKey = $key;
                    if($options['convert_keys'] && is_string($key)) $targetKey = $json && preg_match('//u', $key) ? $key : self::convertCharset($key, $json ? null : $from, $to, $options);
                    if(array_key_exists($targetKey, $converted)) throw new UnexpectedValueException('Converted charset keys collide');
                    $converted[$targetKey] = self::charsetTree($part, $to, $from, $options, $copies, $active, $depth+1, $json);
                }
                if(!$json && $value instanceof ArrayObject) $result->exchangeArray($converted);
                else foreach($converted as $key => $part) {
                    if(!$json && $options['convert_keys'] && !($value instanceof stdClass) && !array_key_exists($key, $entries)) throw new InvalidArgumentException('Renaming declared object properties is unsupported');
                    $result->$key = $part;
                }
                if(!$json && $value instanceof stdClass && $options['convert_keys']) foreach($entries as $key => $part) if(!array_key_exists($key, $converted)) unset($result->$key);
                return $result;
            } finally { $active->offsetUnset($object); }
        }
        $result = [];
        foreach($value as $key => $part) {
            $targetKey = $key;
            if($options['convert_keys'] && is_string($key)) $targetKey = $json && preg_match('//u', $key) ? $key : self::convertCharset($key, $json ? null : $from, $to, $options);
            if(array_key_exists($targetKey, $result)) throw new UnexpectedValueException('Converted charset keys collide');
            $result[$targetKey] = self::charsetTree($part, $to, $from, $options, $copies, $active, $depth+1, $json);
        }
        return $result;
    }

    /**
     * 功能说明：兼容批量转码入口，支持明确来源，复制对象而不原地修改
     *
     * @param mixed $str 字符串、数组、对象；非字符串标量保持原类型，仅转换对象公开状态
     * @param string $charset 目标编码，默认 UTF-8（默认：'UTF-8'）
     * @param string|null $from_charset 来源编码，默认自动；已知来源必须明确传入以消除歧义（默认：null）
     * @param array $options 严格转换/BOM/容错/键转换及最大深度，详见 charsetOptions()（默认：[]）
     * @return mixed 独立转换结果；数组键默认不转换，显式 convert_keys 才转换并检测冲突
     * @throws Throwable 来源未知、损失、对象循环/不可复制或边界非法时明确报错
     */
    public static function setCharset($str, $charset='UTF-8', $from_charset = null, array $options = []) {
        $charset = self::normalizeCharset($charset);
        if($from_charset !== null && is_string($from_charset) && strcasecmp($from_charset, 'auto') === 0) $from_charset = null;
        if($from_charset !== null) $from_charset = self::normalizeCharset($from_charset);
        return self::charsetTree($str, $charset, $from_charset, self::charsetOptions($options), new SplObjectStorage(), new SplObjectStorage());
    }

    /**
     * 功能说明：将字符串转换为16进制
     *
     * @param string $str 待处理的字符串
     * @return string 小写十六进制编码文本，每个输入字节对应两个字符
     */
    public static function toHex($str) {
        return bin2hex($str);
    }

    /**
     * 功能说明：将16进制转换为字符串
     *
     * @param string $hex 十六进制编码字符串
     * @return string|false 解码后的原始字节；非字符串、奇数长度或非HEX字符在解码前返回false，空串合法
     */
    public static function fromHex($hex) {
        if(!is_string($hex) || strlen($hex) % 2 || ($hex !== '' && !ctype_xdigit($hex))) return false;
        return hex2bin($hex);
    }

    /**
     * 功能说明：以相同的原始扫描规则解析INI文件或INI文本
     *
     * @param string $ini INI文件路径或直接INI文本
     * @param bool $mode 是否保留INI节分组；true生成分组数组，false合并为平面数组（默认：true）
     * @return array|false 按mode组织的配置数组；解析失败为false
     */
    public static function fromIni($ini, $mode = true) {
        if(is_file($ini)) {
            $result = parse_ini_file($ini, $mode, INI_SCANNER_RAW);
        } else {
            $result = parse_ini_string($ini, $mode, INI_SCANNER_RAW);
        }
        return $result;
    }


    /**
     * 功能说明：递归把配置数组转换为带分组的INI文本
     *
     * @param array $array 需要编码或转换的数组
     * @param int $level 当前递归层级，由顶层调用使用默认值（默认：1）
     * @return string 完整INI文本
     */
    public static function toIni($array, $level = 1) {
        $result = '';
        foreach($array as $k => $v) {
            if(is_array($v)) {
                if($level==1) {
                    $result .= '['.$k.']'.chr(10);
                    $result .= self::toIni($v, $level+1);
                } else {
                    $result .= $k.' = '.self::fromAny($v);
                }
            } elseif(is_bool($v)) {
                $result .= $k.' = '.($v?1:0);
            } else {
                $result .= $k.' = '.preg_replace('/[\r\n]/', '\n', self::fromAny($v));
            }
            $result .= chr(10);
        }
        return $result;
    }

    /**
     * 功能说明：编码为 UTF-8 JSON，复制转换值及非 UTF-8 键，不修改输入对象
     *
     * JSON 模式尊重 JsonSerializable，ArrayObject 取存储内容；共享对象只转换一次。
     * 原生 JSON 不支持的类型、递归及深度错误继续返回 false 并保留 json_last_error()；
     * 转码本身按严格策略明确报错，不把损坏数据猜成单字节编码后继续保存。
     *
     * @param mixed $var 待编码数据
     * @param string $charset 兼容旧参数，非空时统一转 UTF-8；空字符串完全跳过预处理（默认：'UTF-8'）
     * @param string|null $from_charset 新增明确源编码；null 自动推测字符串值，键单独推测（默认：null）
     * @return string|false JSON 文本或原生编码错误 false
     * @throws Throwable 来源/字节/转码非法或业务 jsonSerialize() 抛出的异常继续传递
     */
    public static function toJson($var, $charset='UTF-8', $from_charset = null) {
        if($charset !== '') {
            if(!is_string($charset)) throw new InvalidArgumentException('JSON compatibility charset must be a string');
            if($from_charset !== null) $from_charset = self::normalizeCharset($from_charset);
            $options = self::charsetOptions(['convert_keys'=>true,'bom'=>'preserve']); $context = new stdClass();
            try { $var = self::charsetTree($var, 'UTF-8', $from_charset, $options, new SplObjectStorage(), new SplObjectStorage(), 0, $context); }
            catch(JsonException $error) {
                if(($context->error ?? null) !== $error) throw $error;
                if($error->getCode() === JSON_ERROR_DEPTH) json_encode([[]], 0, 1);
                else { $cycle = new stdClass(); $cycle->self = $cycle; json_encode($cycle); unset($cycle->self); }
                return false;
            }
        }
        return json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 功能说明：解析JSON文本，按assoc选择关联数组或对象
     *
     * @param string $json JSON格式内容
     * @param bool $assoc true将JSON对象转换为关联数组（默认：true）
     * @return mixed JSON对象/数组/标量；assoc控制对象形态，合法null与失败均可返回null，应检查json_last_error
     */
    public static function fromJson($json, $assoc=true) {
        return json_decode($json, $assoc);
    }

    /**
     * 功能说明：将任意值转换为文本，复杂类型使用PHP序列化
     *
     * @param mixed $var 字符串原样返回，标量转文本，数组及其他值序列化
     * @return string 标量/可字符串化对象的文本，其他值使用PHP序列化表示
     */
    public static function fromAny($var) {
        switch(true) {
            case is_string($var):
                $result = $var;
                break;
            case is_object($var) && method_exists($var, '__toString'):
            case is_numeric($var):
                $result = (STRING)$var;
                break;
            case is_bool($var):
                $result = $var?'true':'false';
                break;
            case is_array($var):
            default:
                $result = serialize($var);
                break;
        }
        return $result;
    }

    /**
     * 功能说明：将数组转换为XML
     *
     * @param mixed $var 需要编码为XML的数据，数组/对象按当前转换规则展开
     * @param string $item_tag XML数组成员使用的元素名（默认：'item'）
     * @param string $main_tag XML根元素名称（默认：''）
     * @return string XML文本；根元素/数组成员名按main_tag/item_tag规则生成
     * @throws InvalidArgumentException item_tag或main_tag不是支持的XML元素名称
     */
    public static function toXML($var, $item_tag='item', $main_tag='') {
        $valid = function($tag) { return is_string($tag) && preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $tag); };
        if(!$valid($item_tag) || ($main_tag !== '' && !$valid($main_tag))) throw new InvalidArgumentException('Invalid XML element name');
        if(is_object($var)) $var = get_object_vars($var);
        $result = '';
        if(is_array($var)) {
            foreach($var as $key => $value) {
                $key = $valid($key) ? $key : $item_tag;
                $result .= '<'.$key.'>'.self::toXML($value, $item_tag).'</'.$key.'>'."\n";
            }
        } else {
            $result = htmlspecialchars(self::fromAny($var), ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
        }
        return $main_tag === '' ? $result : '<'.$main_tag.'>'.$result.'</'.$main_tag.'>';
    }

    /**
     * 功能说明：转换变量为php变量脚本
     *
     * @param mixed $var 通过var_export输出为PHP变量脚本的值
     * @param string $name 生成PHP赋值脚本所使用的变量名（默认：''）
     * @return string 可供受信程序使用的PHP变量表示/赋值脚本文本，不自动执行
     * @throws InvalidArgumentException 目标变量名或展开的键不是合法PHP变量名
     */
    public static function toScript($var, $name='') {
        if($name === '' && is_array($var)) {
            $result = '';
            foreach($var as $key => $value) {
                if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', (string)$key)) throw new InvalidArgumentException('Invalid PHP variable name');
                $result .= '$'.$key.' = '.var_export($value, true).';'."\n";
            }
            return $result;
        }
        $name = ltrim($name, '$');
        if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $name)) throw new InvalidArgumentException('Invalid PHP variable name');
        return '$'.$name.' = '.var_export($var, true).';'."\n";
    }

    /**
     * 功能说明：在边界内完整转换本地文本文件，以同目录临时文件原子替换，失败保留旧文件
     *
     * @param string $file_src 源文件，不接受流包装器或符号链接；不存在时返回 false
     * @param string $file_dst 目标文件，空字符串覆盖源；父目录须存在，拒绝符号链接（默认：''）
     * @param string $charset 目标编码，默认 UTF-8（默认：'UTF-8'）
     * @param string|null $from_charset 明确来源，null 自动推测（默认：null）
     * @param array $options 转码策略；max_bytes 默认 32 MiB，另检查当前 PHP 剩余内存（默认：[]）
     * @return int|false 写入字节数（空文件为 0）；源不存在为 false
     * @throws LengthException 文件超过字节预算
     * @throws RuntimeException 内存不足、读取/锁定/发布失败或读写期间版本变化
     * @throws Throwable 转码失败等异常向外传递，目标不写入部分结果
     */
    public static function fileCharset($file_src, $file_dst='', $charset='UTF-8', $from_charset = null, array $options = []) {
        if(!is_string($file_src) || !is_string($file_dst) || strpos($file_src, "\0") !== false || strpos($file_dst, "\0") !== false || (preg_match('#\A[a-z][a-z0-9+.-]*://#i', $file_src) || preg_match('#\A[a-z][a-z0-9+.-]*://#i', $file_dst))) throw new InvalidArgumentException('Charset files must be local paths');
        $options = self::charsetOptions($options);
        if(is_link($file_src)) throw new RuntimeException('Charset source must not be a symlink');
        if(!is_file($file_src)) return false;
        $file_src = realpath($file_src);
        if($file_dst === '') $file_dst = $file_src;
        else {
            if(is_link($file_dst)) throw new RuntimeException('Charset destination must not be a symlink');
            $parent = realpath(dirname($file_dst));
            if($parent === false) throw new RuntimeException('Charset destination directory is missing');
            $file_dst = $parent.DIRECTORY_SEPARATOR.basename($file_dst);
        }
        if(is_link($file_dst) || is_link($file_dst.'.lock') || !is_dir(dirname($file_dst))) throw new RuntimeException('Invalid charset destination');
        clearstatcache(true, $file_src); $size = filesize($file_src);
        if($size > $options['max_bytes']) throw new LengthException('Charset file exceeds byte budget');
        $memory = myFile::getByte(ini_get('memory_limit'));
        if($memory > 0 && $size*12 > $memory-memory_get_usage(true)) throw new RuntimeException('Insufficient memory for charset file conversion');
        $original = is_file($file_dst) ? hash_file('sha256', $file_dst) : null;
        $content = file_get_contents($file_src);
        if($content === false || strlen($content) !== $size || hash('sha256', $content) !== hash_file('sha256', $file_src)) throw new RuntimeException('Charset source changed while reading');
        $content = self::convertCharset($content, $from_charset, $charset, $options);
        if(strlen($content) > $options['max_bytes']*4+4) throw new LengthException('Charset output exceeds byte budget');
        $lock = fopen($file_dst.'.lock', 'c'); if(!$lock) throw new RuntimeException('Cannot open charset file lock');
        $temporary = null;
        try {
            if(!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock charset destination');
            clearstatcache(true, $file_dst);
            if(is_link($file_dst) || $original !== (is_file($file_dst) ? hash_file('sha256', $file_dst) : null)) throw new RuntimeException('Charset destination changed before save');
            $mode = is_file($file_dst) ? fileperms($file_dst) & 0777 : 0600;
            $temporary = tempnam(dirname($file_dst), '.charset-');
            if($temporary === false || file_put_contents($temporary, $content) !== strlen($content) || !chmod($temporary, $mode) || !rename($temporary, $file_dst)) throw new RuntimeException('Cannot publish charset file');
            if(function_exists('opcache_invalidate')) opcache_invalidate($file_dst, true);
            return strlen($content);
        } finally {
            if(is_string($temporary) && is_file($temporary)) unlink($temporary);
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    /**
     * 功能说明：将字符串转换为超文本格式
     *
     * @param mixed $str 字符串或嵌套数组；转换引号/尖括号/空白，其他类型原样保留
     * @return mixed 递归转换后的值，保持数组键和其他类型
     */
    public static function htmlTrans($str) {
        $search = array("'", "\"", "<", ">", "    ", "\t");
        $replace = array("&#39;", "&quot;", "&lt;", "&gt;", "&nbsp; ", "&nbsp; &nbsp; ");
        if(is_array($str)) {
            foreach($str as $key => $value) {
                $str[$key] = self::htmlTrans($value);
            }
        } elseif(is_string($str)) {
            $str = str_replace($search, $replace, $str);
        }
        return $str;
    }

    /**
     * 功能说明：将纯文本转换为超文本格式
     *
     * @param string $content 待处理或输出的内容字节串
     * @return string 经过文本替换规则转换的HTML片段，不是完整HTML过滤器
     */
    public static function txt2html($content) {
        $content = str_replace('    ', '&nbsp; ', $content);
        $content = str_replace("\r\n", chr(10), $content);
        $content = str_replace(chr(10), "<br />\n", $content);
        $content = str_replace("\t", ' &nbsp; &nbsp;', $content);
        return $content;
    }

    /**
     * 功能说明：生成随机字符串或可在目标编码表示的随机汉字
     *
     * @param int $length 生成字符数量，沿用原有循环约定
     * @param int $scope 1 数字，2 加小写，3 加大写，4 加特殊符；其他值生成汉字（默认：1）
     * @param string $charset 汉字的目标编码，默认 UTF-8；英文/数字分支不做字符转码（默认：'UTF-8'）
     * @return string 汉字候选从明确 GBK 字节生成，过滤未定义/不可表示候选，最后整体转码
     * @throws RuntimeException 尝试 128 次仍无法为某字符找到可表示的汉字
     * @throws Throwable 目标非法或后端不可用等异常继续传递；不静默输出替代字符
     */
    public static function rndKey($length, $scope=1, $charset='UTF-8') {
        //Coded By Windy2000 20020501 v1.0
        $char_list    = array();
        $char_list[]    = '1234567890';
        $char_list[]    = 'abcdefghijklmnopqrstuvwxyz';
        $char_list[]    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $char_list[]    = '!@^()_:+\-';
        $Rnd_Key    = '';
        if($scope>0 && $scope<=count($char_list)) {
            for($i=1; $i<=$length; $i++) {
                $Rnd_Str    = $char_list[random_int(1, $scope) - 1];
                $Rnd_Key .= substr($Rnd_Str, random_int(0, strlen($Rnd_Str)-1), 1);
            }
        } else {
            for($i=1; $i<=$length; $i++) {
                $accepted = false;
                for($attempt=0; $attempt<128; $attempt++) {
                    $pair = chr(random_int(0xb0, 0xf7)).chr(random_int(0xa1, 0xfe));
                    try {
                        $unicode = self::convertCharset($pair, 'GBK', 'UTF-8');
                        if(!preg_match('/\p{Han}/u', $unicode)) continue;
                        self::convertCharset($pair, 'GBK', $charset);
                    } catch(UnexpectedValueException $error) { continue; }
                    $Rnd_Key .= $pair; $accepted = true; break;
                }
                if(!$accepted) throw new RuntimeException('Cannot generate Chinese characters in target charset');
            }
            if(self::normalizeCharset($charset) !== 'GBK') $Rnd_Key = self::setCharset($Rnd_Key, $charset, 'GBK');
        }
        return($Rnd_Key);
    }

    /**
     * 功能说明：批量添加转移斜杠
     *
     * @param mixed &$str 引用传入的字符串或嵌套数组，递归原地处理；其他类型原样保留
     * @return mixed 转换后的原值，同时更新引用参数
     */
    public static function addSlash(&$str) {
        if(is_array($str)) {
            foreach($str as $key => $value) {
                $str[$key] = self::addSlash($value);
            }
        } elseif(is_string($str)) {
            $str = addslashes($str);
        }
        return $str;
    }

    /**
     * 功能说明：批量去除转移斜杠
     *
     * @param mixed &$str 引用传入的字符串或嵌套数组，递归原地处理；其他类型原样保留
     * @return mixed 转换后的原值，同时更新引用参数
     */
    public static function stripSlash(&$str) {
        if(is_array($str)) {
            foreach($str as $key => $value) {
                $str[$key] = self::stripSlash($value);
            }
        } elseif(is_string($str)) {
            $str = stripslashes($str);
        }
        return $str;
    }

    /**
     * 功能说明：将字符串打碎为单个的字符或汉字
     *
     * @param string $str 待处理的字符串
     * @return array 逐字符字节串列表；优先按UTF-8，否则按历史双字节规则拆分
     */
    public static function breakStr($str) {
        if(mb_check_encoding($str, 'UTF-8')) return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        preg_match_all('/[\xa0-\xff]?./s', $str, $matches);
        return $matches[0];
    }

    /**
     * 功能说明：为超文本添加水印
     *
     * 需要为class_name配置隐藏样式；本方法是展示水印/干扰文本，不承担内容访问控制。
     *
     * @param string $html 待处理的HTML内容
     * @param int $rate 出现几率（默认：2）
     * @param int $scope 水印内容对应 rndkey（默认：4）
     * @param string $str_append 自定义水印内容（默认：''）
     * @param string $charset 生成水印随机字串所使用的字符编码，默认utf-8（默认：'utf-8'）
     * @param string $class_name 水印节点使用的CSS类名（默认：'watermark'）
     * @param string $tag_name 包裹水印字符使用的HTML标签名（默认：'span'）
     * @param bool $jam_tag 干扰标签（默认：false）
     * @return string 插入随机隐藏水印后的HTML；输入超过50000字节时原样返回
     */
    public static function watermark($html, $rate=2, $scope=4, $str_append='', $charset='utf-8', $class_name='watermark', $tag_name='span', $jam_tag=false) {
        /*
        Please make sure that the following style exist on your style sheet of the watermark page
        .{$class_name} {
            position:absolute;width:1px;height:1px;overflow:hidden;
        }
        */
        if(strlen($html)>50000) return $html;
        if($scope>5 && empty($charset)) $charset = 'utf-8';
        $result = '';
        preg_match_all('/(<(.+?)>)|(&([#\w]+);)/is', $html, $arr_tag);
        $arr_tag = $arr_tag[0];
        $html = str_replace($arr_tag, chr(0), $html);
        $arr_char = self::breakStr($html);
        for($i=0,$m=count($arr_char); $i<$m; $i++) {
            if(ord($arr_char[$i])==0) {
                $cur_tag = array_shift($arr_tag);
                if(!empty($str_append) && preg_match('/<(\/(p|div))|(br( +\/)?)>/i', $cur_tag)) {
                    $cur_tag = "<{$tag_name} class='{$class_name}'>".$str_append."</{$tag_name}>".$cur_tag;
                }
                $result .= $cur_tag;
            } elseif(mt_rand(1, 10)<$rate) {
                $rnd_str = self::rndKey(mt_rand(1, 2), $scope, $charset);
                if($jam_tag && mt_rand(1, 10)<6) {
                    $rnd_str .= "<{$tag_name} class='".self::rndKey(mt_rand(3, 6), 2)."'>".self::rndKey(mt_rand(1, 2), $scope, $charset)."</{$tag_name}>".self::rndKey(mt_rand(1, 2), $scope, $charset);
                }
                $result .= "<{$tag_name} class='{$class_name}'>".$rnd_str."</{$tag_name}>".$arr_char[$i];
            } else {
                $result .= $arr_char[$i];
            }
        }
        return $result;
    }

    /**
     * 功能说明：截取任意字符串，可为任意字符集的中文
     *
     * @param string $string 待截取的文本
     * @param int $start 起始位置，计数单位由截取模式决定
     * @param int $length 截取长度；0延伸到末尾，负值从可用长度中扣除（默认：0）
     * @param bool $mode true按字符计数；false按字节范围截取并保留完整字符（默认：false）
     * @return string 落在指定字符/字节范围内的完整字符文本；范围外或长度不足返回空串
     */
    public static function substr($string, $start, $length = 0, $mode = false) {
        $chars = self::breakStr($string);
        $total = $mode ? count($chars) : strlen($string);
        $start = $start < 0 ? max(0, $total+$start) : $start;
        if($length <= 0) $length += $total-$start;
        if($length <= 0 || $start >= $total) return '';
        if($mode) return implode('', array_slice($chars, $start, $length));
        $offset = 0; $result = '';
        foreach($chars as $char) {
            $next = $offset+strlen($char);
            if($offset >= $start && $next <= $start+$length) $result .= $char;
            $offset = $next;
            if($offset >= $start+$length) break;
        }
        return $result;
    }

}