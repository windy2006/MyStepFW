<?php
/*******************************************
*                                          *
* Name    : Redis Session Handler (new)    *
* Modifier: MyStep Framework               *
* Time    : 2026-10-24                     *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/
/**
 * 文件：plugin/redis/sess_redis.class.php
 * 功能说明：框架Redis会话处理器，提供严格ID、TTL及read至close的同ID互斥。
 * 方法目录：
 *   public static sess_redis::set($setting) — 验证并设置Redis会话参数，活动连接期间拒绝重配。
 *   public static sess_redis::getLastError() — 读取不包含凭据、会话ID和正文的最近错误摘要。
 *   public static sess_redis::open($sess_path,$sess_name) — 建立会话专用连接并按Cookie名称隔离存储。
 *   private static sess_redis::validId($sid) — 验证PHP会话ID的长度和字符集，拒绝控制字符及分隔符。
 *   private static sess_redis::lock($sid) — 获取指定ID锁；会话ID轮换时释放旧锁，再获取新锁。
 *   private static sess_redis::unlock() — 核对所有者后释放当前锁，清空本请求锁状态。
 *   private static sess_redis::operate($sid,$action,$data='') — 在一次Lua操作内核对锁令牌，再读取、保存、删除或续期会话。
 *   public static sess_redis::read($sid) — 持锁读取原始会话字节，不存在返回空串，故障返回false。
 *   public static sess_redis::write($sid,$sess_data) — 持锁完整保存会话数据，并使用session.gc_maxlifetime续期。
 *   public static sess_redis::validateId($sid) — 严格模式只接受实际存在的未过期会话，未知ID不会写入空会话。
 *   public static sess_redis::updateTimestamp($sid,$sess_data) — 惰性写入续期；记录被回收时在持锁状态下恢复PHP提供的数据。
 *   public static sess_redis::destroy($sid) — 持锁删除会话并释放锁，避免排队请求再次读取旧身份。
 *   public static sess_redis::gc($maxlifetime) — Redis通过键TTL自动回收，不扫描或删除其他会话，不关闭当前连接。
 *   public static sess_redis::close() — 释放本请求的会话锁并关闭专用连接，可重复调用。
 * 使用说明：sess_redis::set($options)后通过myReq::sessionStart('sess_redis')启动，或在后台选择Redis会话。
 *   会话原始字节不经过缓存序列化器；按站点、Redis前缀、Cookie名称隔离，不使用业务数据库连接。
 *   锁超时/服务故障返回失败，不回退文件会话；Lua在读写时核对所有者，租约丢失不能覆盖后继请求。
 *   lock_ttl应大于最长请求时间；不提供sess_mystep的数据库在线用户统计。
 */
require_once LIB.'session/interface_session.class.php';
require_once __DIR__.'/myRedis.class.php';
/**
 * 功能说明：实现框架静态会话协议及PHP严格模式、惰性续期回调。
 * 方法目录：参见文件头部；连接和锁只在本次会话打开期间持有。
 */
class sess_redis implements interface_session {
    private static $setting=[], $store=null, $name='', $sid='', $token='', $error='';

    /**
     * 功能说明：验证并设置Redis会话参数，活动连接期间拒绝重配。
     * @param array|object $setting myRedis公共参数；max_bytes为单会话字节上限
     * @return bool 参数合法且未打开连接为true，否则false
     */
    public static function set($setting) {
        if(self::$store!==null)return false;
        try{self::$setting=myRedis::settings($setting);self::$error='';return true;}
        catch(Throwable $error){self::$error='Invalid Redis session configuration.';return false;}
    }

    /**
     * 功能说明：读取不包含凭据、会话ID和正文的最近错误摘要。
     * 参数说明：无显式参数。
     * @return string 最近错误摘要，无错误时为空字符串
     */
    public static function getLastError() { return self::$error; }

    /**
     * 功能说明：建立会话专用连接并按Cookie名称隔离存储。
     * @param string $sess_path PHP协议参数，Redis不使用文件路径
     * @param string $sess_name PHP会话Cookie名称
     * @return bool 配置和连接成功为true，失败false
     */
    public static function open($sess_path,$sess_name) {
        if(self::$store!==null && !self::close())return false;
        try{
            self::$setting=myRedis::settings(self::$setting);self::$name=(string)$sess_name;self::$error='';
            self::$store=new myRedis(self::$setting);self::$store->client();return true;
        }catch(Throwable $error){self::$store=null;self::$error='Redis session connection unavailable.';return false;}
    }

    /**
     * 功能说明：验证PHP会话ID的长度和字符集，拒绝控制字符及分隔符。
     * @param mixed $sid 会话ID
     * @return bool 1..128个字母、数字、逗号或短横线为true
     */
    private static function validId($sid) { return is_string($sid)&&preg_match('/\A[a-zA-Z0-9,-]{1,128}\z/D',$sid)===1; }

    /**
     * 功能说明：获取指定ID锁；会话ID轮换时释放旧锁，再获取新锁。
     * @param string $sid 已校验或待校验的会话ID
     * @return bool 锁可用为true；未打开、ID非法或等待失败false
     */
    private static function lock($sid) {
        if(self::$store===null||!self::validId($sid))return false;
        if(self::$sid===$sid&&self::$token!=='')return true;
        if(!self::unlock())return false;
        try{
            $key=self::$store->key('session-lock',self::$name."\0".$sid);
            self::$token=self::$store->acquire($key);self::$sid=$sid;return true;
        }catch(Throwable $error){self::$error='Redis session lock unavailable or timed out.';return false;}
    }

    /**
     * 功能说明：核对所有者后释放当前锁，清空本请求锁状态。
     * 参数说明：无显式参数。
     * @return bool 无锁或释放成功为true，锁丢失/连接失败false
     */
    private static function unlock() {
        if(self::$token==='')return true;
        $sid=self::$sid;$token=self::$token;self::$sid='';self::$token='';
        try{
            $ok=self::$store->release(self::$store->key('session-lock',self::$name."\0".$sid),$token);
            if(!$ok)self::$error='Redis session lock lease lost.';
            return $ok;
        }catch(Throwable $error){self::$error='Redis session lock release failed.';return false;}
    }

    /**
     * 功能说明：在一次Lua操作内核对锁令牌，再读取、保存、删除或续期会话。
     * @param string $sid 会话ID
     * @param string $action read/exists/write/touch/delete
     * @param string $data PHP会话原始字节，默认空串
     * @return string|bool read返回原始字节，其余返回操作是否成功；所有后端故障返回false
     */
    private static function operate($sid,$action,$data='') {
        if(!self::lock($sid))return false;
        if(!is_string($data)||strlen($data)>self::$setting['max_bytes']){self::$error='Redis session payload exceeds byte limit.';return false;}
        $script=<<<'LUA'
if redis.call('GET',KEYS[1])~=ARGV[1] then return {0,''} end
local action=ARGV[2]
if action=='delete' then redis.call('DEL',KEYS[2]); return {1,''} end
if action=='exists' then return {redis.call('EXISTS',KEYS[2]),''} end
if action=='read' then
  if redis.call('STRLEN',KEYS[2])>tonumber(ARGV[5]) then return {0,''} end
  return {1,redis.call('GET',KEYS[2]) or ''}
end
if action=='touch' and redis.call('EXPIRE',KEYS[2],ARGV[4])==1 then return {1,''} end
redis.call('SET',KEYS[2],ARGV[3],'EX',ARGV[4]); return {1,''}
LUA;
        try{
            $result=self::$store->client()->eval($script,[self::$store->key('session-lock',self::$name."\0".$sid),
                self::$store->key('session',self::$name."\0".$sid),self::$token,$action,$data,
                (string)max(1,(int)ini_get('session.gc_maxlifetime')),(string)self::$setting['max_bytes']],2);
            if(!is_array($result)||($result[0]??null)!==1){
                if($action!=='exists')self::$error='Redis session operation failed or lock lease lost.';
                return false;
            }
            return $action==='read'?$result[1]:true;
        }catch(Throwable $error){self::$error='Redis session storage operation failed.';return false;}
    }

    /**
     * 功能说明：持锁读取原始会话字节，不存在返回空串，故障返回false。
     * @param string $sid 会话ID
     * @return string|false 会话正文或失败false
     */
    public static function read($sid) { return self::operate($sid,'read'); }

    /**
     * 功能说明：持锁完整保存会话数据，并使用session.gc_maxlifetime续期。
     * @param string $sid 会话ID
     * @param string $sess_data 原始数据，包含空串、中文和NUL均保留
     * @return bool 原子保存成功为true
     */
    public static function write($sid,$sess_data) { return self::operate($sid,'write',$sess_data); }

    /**
     * 功能说明：严格模式只接受实际存在的未过期会话，未知ID不会写入空会话。
     * @param string $sid 客户端提供的会话ID
     * @return bool 有效现存会话为true，未知、过期或故障false
     */
    public static function validateId($sid) { return self::operate($sid,'exists'); }

    /**
     * 功能说明：惰性写入续期；记录被回收时在持锁状态下恢复PHP提供的数据。
     * @param string $sid 会话ID
     * @param string $sess_data PHP提供的未变化会话字节
     * @return bool 续期或恢复成功为true
     */
    public static function updateTimestamp($sid,$sess_data) { return self::operate($sid,'touch',$sess_data); }

    /**
     * 功能说明：持锁删除会话并释放锁，避免排队请求再次读取旧身份。
     * @param string $sid 待销毁的会话ID
     * @return bool 删除及释放均成功为true
     */
    public static function destroy($sid) {
        if(!self::operate($sid,'delete'))return false;
        return self::unlock();
    }

    /**
     * 功能说明：Redis通过键TTL自动回收，不扫描或删除其他会话，不关闭当前连接。
     * @param int $maxlifetime PHP协议参数；到期时间已经由write/touch写入
     * @return bool 当前连接可响应为true，否则false
     */
    public static function gc($maxlifetime) {
        try{return self::$store!==null&&self::$store->client()->ping()!==false;}catch(Throwable $error){return false;}
    }

    /**
     * 功能说明：释放本请求的会话锁并关闭专用连接，可重复调用。
     * 参数说明：无显式参数。
     * @return bool 锁和连接均完成释放为true，否则false
     */
    public static function close() {
        $ok=self::unlock();$store=self::$store;self::$store=null;
        return ($store===null||$store->close())&&$ok;
    }
}
