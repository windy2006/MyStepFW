<?php
/*******************************************
* Name    : Session Handler Bridge (new)   *
* Modifier: MyStep Framework               *
* Time    : 2026-10-25                     *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*******************************************/
/**
 * 文件：lib/session/mySessionHandler.class.php
 * 功能说明：将框架静态会话协议或回调数组桥接为PHP8.5原生会话对象。
 * 方法目录：
 *   public mySessionHandler::__construct(array $callbacks) — 验证六项基础或九项完整会话回调。
 *   public mySessionHandler::open(string $path, string $name): bool — 打开实际存储。
 *   public mySessionHandler::close(): bool — 关闭实际存储并释放锁。
 *   public mySessionHandler::read(string $id): string|false — 保留原始会话字节和读取失败状态。
 *   public mySessionHandler::write(string $id, string $data): bool — 将会话正文交给实际存储。
 *   public mySessionHandler::destroy(string $id): bool — 删除会话，不将失败转换成成功。
 *   public mySessionHandler::gc(int $max_lifetime): int|false — 将旧协议的true回收结果转换为原生协议的零条计数。
 *   public mySessionHandler::create_sid(): string — 调用自定义ID生成器或生成随机ID。
 *   public mySessionHandler::validateId(string $id): bool — 调用可选的严格ID检查；六回调模式沿用原有接受规则。
 *   public mySessionHandler::updateTimestamp(string $id, string $data): bool — 调用可选续期回调，否则完整写入。
 * 使用说明：myReq::sessionStart自动使用本桥接，不需要改写sess_file/sess_mysql/插件Session的静态接口。
 *   只接受受信callable，不负责插件启用检查、配置加载、注册会话或切换活动Session。
 */
/**
 * 功能说明：实现PHP原生会话、ID生成和惰性写入接口，避免逐回调注册的弃用提示。
 * 方法目录：参见文件头；原始存储异常继续传递给调用方。
 */
class mySessionHandler implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface {
    private array $callbacks;
    /**
     * 功能说明：验证并保存六个基础回调或九个完整回调，不打开存储。
     * @param array $callbacks 依次为open/close/read/write/destroy/gc及可选create_sid/validateId/updateTimestamp
     * @return void 初始化完成
     * @throws InvalidArgumentException 数量错误或任一条目不可调用
     */
    public function __construct(array $callbacks) {
        if(!array_is_list($callbacks) || !in_array(count($callbacks),[6,9],true))throw new InvalidArgumentException('Session callbacks must contain six or nine ordered entries');
        foreach($callbacks as $callback)if(!is_callable($callback))throw new InvalidArgumentException('Invalid session callback');
        $this->callbacks=$callbacks;
    }
    /**
     * 功能说明：打开底层存储，传递PHP提供的路径和Cookie名称。
     * @param string $path 原生会话保存路径
     * @param string $name 原生会话Cookie名称
     * @return bool 实际处理器的打开结果
     */
    public function open(string $path, string $name): bool {return (bool)($this->callbacks[0])($path,$name);}
    /**
     * 功能说明：关闭底层连接，释放会话互斥资源。
     * 参数说明：无显式参数。
     * @return bool 实际关闭结果
     */
    public function close(): bool {return (bool)($this->callbacks[1])();}
    /**
     * 功能说明：读取原始Session正文，空字符串保持为有效的空会话。
     * @param string $id 当前会话ID
     * @return string|false 原始字节或失败；不把false变成空字符串
     * @throws UnexpectedValueException 处理器返回非字符串且非false
     */
    public function read(string $id): string|false {
        $data=($this->callbacks[2])($id);
        if(!is_string($data) && $data!==false)throw new UnexpectedValueException('Session read must return a string or false');
        return $data;
    }
    /**
     * 功能说明：写入原始Session正文，不更改序列化器。
     * @param string $id 当前会话ID
     * @param string $data PHP序列化后的会话字节
     * @return bool 底层写入结果
     */
    public function write(string $id, string $data): bool {return (bool)($this->callbacks[3])($id,$data);}
    /**
     * 功能说明：通过底层处理器删除指定会话。
     * @param string $id 待删除的会话ID
     * @return bool 底层删除结果
     */
    public function destroy(string $id): bool {return (bool)($this->callbacks[4])($id);}
    /**
     * 功能说明：兼容旧静态协议的布尔回收结果及原生删除条数。
     * @param int $max_lifetime 原生会话有效期秒数
     * @return int|false 非负删除条数、旧true转换为0、失败保留false
     * @throws UnexpectedValueException 返回类型或计数不合法
     */
    public function gc(int $max_lifetime): int|false {
        $result=($this->callbacks[5])($max_lifetime);
        if($result===true)return 0;
        if($result===false || (is_int($result) && $result>=0))return $result;
        throw new UnexpectedValueException('Session GC must return a non-negative count or boolean');
    }
    /**
     * 功能说明：使用可选生成器，未指定时创建48位十六进制随机ID。
     * 参数说明：无显式参数。
     * @return string 非空且符合PHP会话ID字符规则的字符串
     * @throws UnexpectedValueException 自定义生成器返回无效ID
     */
    public function create_sid(): string {
        $id=isset($this->callbacks[6])?($this->callbacks[6])():bin2hex(random_bytes(24));
        if(!is_string($id) || !preg_match('/\A[a-zA-Z0-9,-]{1,128}\z/D',$id))throw new UnexpectedValueException('Invalid generated session ID');
        return $id;
    }
    /**
     * 功能说明：执行可选严格ID存在性检查，六回调模式保留原有兼容语义。
     * @param string $id 客户端提供的会话ID
     * @return bool 已声明检查器时返回其结果，否则接受格式合法的ID
     */
    public function validateId(string $id): bool {
        if(!preg_match('/\A[a-zA-Z0-9,-]{1,128}\z/D',$id))return false;
        return isset($this->callbacks[7])?(bool)($this->callbacks[7])($id):true;
    }
    /**
     * 功能说明：调用可选惰性续期入口；缺少入口时按原协议完整写入。
     * @param string $id 当前会话ID
     * @param string $data PHP提供的现有会话正文
     * @return bool 续期或回退写入是否成功
     */
    public function updateTimestamp(string $id, string $data): bool {
        return isset($this->callbacks[8])?(bool)($this->callbacks[8])($id,$data):$this->write($id,$data);
    }
}
