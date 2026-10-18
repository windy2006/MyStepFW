<?PHP

/**
 * 文件：lib/myApi.class.php
 * 功能说明：可注册的应用接口调用器
 *   管理接口别名、回调执行及JSON/XML/文本输出。
 *
 * 方法目录：
 *   public myApi::init() — 初始化空接口对象；再次调用不主动清空已登记方法
 *   protected myApi::methodName($method) — 验证接口名称，避免非法数组键及控制字符
 *   public myApi::regMethod($method, $func) — 登记或覆盖单个接口，失败时保留原有登记
 *   public myApi::regMethods($method_list) — 先验证整批回调，再一次性合并，避免错误批次只登记了一部分
 *   public myApi::hasMethod($method) — 查询接口是否已明确登记，不进行全局函数回退
 *   public myApi::removeMethod($method) — 注销接口并释放该回调的引用
 *   public myApi::invoke($method, array $para = []) — 执行已登记接口并返回原始结果，不编码、不修改响应头
 *   protected myApi::xmlResult($value) — 将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环
 *   public myApi::run($method, $para=[], $return='json', $charset='utf-8') — 执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型
 *
 * 使用说明：
 *   1. 先注册接口或选择已登记接口，再按约定调用/输出。
 *   2. 输入过滤和权限由框架或业务入口完成，回调异常按当前执行策略传递。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：管理接口别名、回调执行及JSON/XML/文本输出。
 *
 * 使用说明：
 *   1. 先注册接口或选择已登记接口，再按约定调用/输出。
 *   2. 输入过滤和权限由框架或业务入口完成，回调异常按当前执行策略传递。
 *
 * 补充说明：
 * 应用接口注册、原始执行及响应编码，执行目标仅来自明确登记的回调
 *
 *     $api->regMethod('sum', function ($a, $b) { return $a + $b; });
 *     $value = $api->invoke('sum', [2, 3]);  // 原始结果，不发送响应头
 *     $json = $api->run('sum', [2, 3]);     // 编码 JSON 并设置内容类型
 *     $api->hasMethod('sum');              // 查询登记状态
 *     $api->removeMethod('sum');           // 注销单个接口
 *
 * run() 保留未知接口编码空字符串的旧约定，invoke() 对未知接口明确报错。
 * 参数使用位置顺序，不将关联键解释为 PHP 8 命名参数。JSON 恒为 UTF-8；XML 先以
 * UTF-8 生成，再按显式 charset 无损转码。回调、格式或编码异常不包装成成功空响应。
 * 注册表不是认证/权限系统；HTTP 方法、CSRF、授权仍由应用及框架入口负责。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class myApi extends myBase {
    use myTrait;
    protected $methods = [];

    /**
     * 功能说明：初始化空接口对象；再次调用不主动清空已登记方法
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function init() {}

    /**
     * 功能说明：验证接口名称，避免非法数组键及控制字符
     *
     * @param string|int $method 接口标识，保留数字键兼容
     * @return string 非空规范名称；大小写仍按旧注册表区分
     * @throws InvalidArgumentException 名称类型、空值或控制字符非法
     */
    protected function methodName($method) {
        if((!is_string($method) && !is_int($method)) || (string)$method === '' || preg_match('/[\x00-\x1f\x7f]/', (string)$method)) throw new InvalidArgumentException('Invalid API method name');
        return (string)$method;
    }

    /**
     * 功能说明：登记或覆盖单个接口，失败时保留原有登记
     *
     * @param string|int $method 非空接口标识
     * @param callable $func 函数、静态/对象方法、闭包或可调用对象
     * @return $this 当前 API 对象，可连续登记
     * @throws InvalidArgumentException 名称非法或回调不可调用
     */
    public function regMethod($method, $func) {
        $name = $this->methodName($method);
        if(!is_callable($func)) throw new InvalidArgumentException('API callback must be callable');
        $this->methods[$name] = $func;
        return $this;
    }

    /**
     * 功能说明：先验证整批回调，再一次性合并，避免错误批次只登记了一部分
     *
     * @param array|Traversable $method_list 接口名称到回调的映射
     * @return $this 当前 API 对象；同名接口覆盖旧回调
     * @throws InvalidArgumentException 列表类型、名称或任一回调非法
     */
    public function regMethods($method_list) {
        if(!is_array($method_list) && !($method_list instanceof Traversable)) throw new InvalidArgumentException('API methods must be a map');
        $next = [];
        foreach($method_list as $key => $value) {
            $name = $this->methodName($key);
            if(!is_callable($value)) throw new InvalidArgumentException('API callback must be callable');
            $next[$name] = $value;
        }
        $this->methods = array_replace($this->methods, $next);
        return $this;
    }

    /**
     * 功能说明：查询接口是否已明确登记，不进行全局函数回退
     *
     * @param string|int $method 接口标识
     * @return bool 名称存在于当前注册表时为 true
     */
    public function hasMethod($method) { return array_key_exists($this->methodName($method), $this->methods); }

    /**
     * 功能说明：注销接口并释放该回调的引用
     *
     * @param string|int $method 接口标识
     * @return bool 原先存在并已删除为 true；不存在为 false
     */
    public function removeMethod($method) {
        $name = $this->methodName($method); $exists = array_key_exists($name, $this->methods);
        unset($this->methods[$name]); return $exists;
    }

    /**
     * 功能说明：执行已登记接口并返回原始结果，不编码、不修改响应头
     *
     *
     * 调用示例：
     *     $api->regMethod('sum', function ($a, $b) { return $a + $b; });
     *     $sum = $api->invoke('sum', [2, 3]); // 原始整数结果5
     *
     * @param string|int $method 明确登记的接口名
     * @param array $para 按遍历顺序作为位置参数传递，关联键不作为参数名（默认：[]）
     * @return mixed 回调结果，保留 false/null/0/空数组等类型
     * @throws BadMethodCallException 接口未登记，不自动寻找同名全局函数
     * @throws Throwable 业务回调异常继续传递
     */
    public function invoke($method, array $para = []) {
        $name = $this->methodName($method);
        if(!array_key_exists($name, $this->methods)) throw new BadMethodCallException('API method is not registered');
        return call_user_func_array($this->methods[$name], array_values($para));
    }

    /**
     * 功能说明：将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环
     *
     * @param mixed $value 原始结果；对象使用公开属性，数字/非法元素名回退 item
     * @return string 以 mystep 为根的 XML，不发送响应头
     * @throws InvalidArgumentException 文本不是有效 UTF-8 或包含资源
     * @throws RuntimeException 无 XMLWriter、结果嵌套超限或包含循环引用
     */
    protected function xmlResult($value) {
        if(!class_exists('XMLWriter')) throw new RuntimeException('XML responses require XMLWriter');
        $xml = new XMLWriter(); $xml->openMemory(); $xml->startDocument('1.0', 'UTF-8'); $xml->startElement('mystep');
        $objects = new SplObjectStorage();
        $write = function($item, $depth) use (&$write, $xml, $objects) {
            if($depth > 64) throw new RuntimeException('API XML nesting limit exceeded');
            if(is_object($item) || is_array($item)) {
                $object = is_object($item) ? $item : null;
                if($object !== null) { if($objects->contains($object)) throw new RuntimeException('Cyclic API XML result'); $objects->attach($object); }
                try {
                    foreach(is_object($item) ? get_object_vars($item) : $item as $key => $part) {
                        $tag = is_string($key) && preg_match('/\A[A-Za-z_][A-Za-z0-9_.-]*\z/D', $key) ? $key : 'item';
                        $xml->startElement($tag); $write($part, $depth+1); $xml->endElement();
                    }
                } finally { if($object !== null) $objects->detach($object); }
            } else {
                if(is_resource($item)) throw new InvalidArgumentException('API XML cannot encode resources');
                $text = $item === null ? '' : (is_bool($item) ? ($item ? 'true' : 'false') : (string)$item);
                if(!preg_match('//u', $text)) throw new InvalidArgumentException('API XML requires UTF-8 text');
                $text = preg_replace('/[^\x09\x0a\x0d\x20-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]/u', '', $text);
                $xml->text($text);
            }
        };
        $write($value, 0); $xml->endElement(); $xml->endDocument(); return $xml->outputMemory();
    }

    /**
     * 功能说明：执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型
     *
     * JSON 不猜测或改写对象编码，避免修改回调返回对象；非法 UTF-8/循环会明确报错。
     * XML 字符串需要有效 UTF-8，非法 XML 控制字符移除；charset 仅控制文本类响应
     * 的输出标签及 XML 转码，不用于把任意二进制猜测为 Unicode。
     *
     * @param string|int $method 已登记接口；未知接口沿用编码空字符串的旧行为
     * @param array $para 位置参数数组，关联键按遍历顺序传递（默认：[]）
     * @param string $return json/j、xml/x、string/s、hex/h、code/c/script；未知值回退 JSON（默认：'json'）
     * @param string $charset 文本输出字符集，空值使用 UTF-8；JSON 恒为 UTF-8（默认：'utf-8'）
     * @return string 编码后的内容，不 echo、不结束请求
     * @throws InvalidArgumentException 参数/字符集标签非法或 XML 文本非法
     * @throws JsonException JSON 编码失败，不静默返回 false
     * @throws RuntimeException XML 超限/循环、字符转码失败或编码不可用
     * @throws Throwable 业务回调异常继续传递
     */
    public function run($method, $para=[], $return='json', $charset='utf-8') {
        if(!is_array($para)) throw new InvalidArgumentException('API parameters must be an array');
        if(!is_string($return)) throw new InvalidArgumentException('API response format must be a string');
        if($charset === '') $charset = 'UTF-8';
        if(!is_string($charset) || !preg_match('/\A[A-Za-z][A-Za-z0-9._-]*\z/D', $charset)) throw new InvalidArgumentException('Invalid API response charset');
        $result = $this->hasMethod($method) ? $this->invoke($method, $para) : '';
        switch(strtolower($return)) {
            case 'x': case 'xml':
                $result = $this->xmlResult($result);
                if(!in_array(strtoupper($charset), ['UTF-8','UTF8'], true)) {
                    if(!function_exists('iconv')) throw new RuntimeException('XML charset conversion requires iconv');
                    $result = str_replace('encoding="UTF-8"', 'encoding="'.$charset.'"', $result);
                    try { $converted = @iconv('UTF-8', $charset, $result); } catch(Throwable $error) { $converted = false; }
                    if($converted === false) throw new RuntimeException('Cannot encode XML response without data loss');
                    $result = $converted;
                }
                $type = 'application/xml; charset='.$charset; break;
            case 's': case 'string': $result = myString::fromAny($result); $type = 'text/plain; charset='.$charset; break;
            case 'h': case 'hex': $result = myString::toHex(myString::fromAny($result)); $type = 'text/plain; charset=US-ASCII'; break;
            case 'c': case 'code': case 'script': $result = myString::toScript(['result'=>$result]); $type = 'text/plain; charset='.$charset; break;
            default: $result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); $type = 'application/json; charset=UTF-8';
        }
        if(!headers_sent()) header('Content-Type: '.$type);
        return $result;
    }
}
