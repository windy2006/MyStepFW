<?PHP

/**
 * 文件：lib/session/interface_session.class.php
 * 功能说明：PHP会话处理器兼容协议
 *   声明open/close/read/write/destroy/gc六个基础存储回调。
 *
 * 方法目录：
 *   public static interface_session::open($sess_path, $sess_name) — 初始化存储。
 *   public static interface_session::close() — 释放连接和锁。
 *   public static interface_session::read($sid) — 读取并持有同ID请求锁。
 *   public static interface_session::write($sid, $sess_data) — 写入完整字节串。
 *   public static interface_session::destroy($sid) — 销毁会话。
 *   public static interface_session::gc($maxlifetime) — 回收过期存储。
 *
 * 使用说明：
 *   1. 由框架Session后端实现，并由myReq登记到PHP会话机制。
 *   2. 其他validateId/updateTimestamp等可选钩子由具体处理器提供。
 *   3. read成功返回序列化会话字节串，空串与后端失败不能随意混为同一状态。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：声明open/close/read/write/destroy/gc六个基础存储回调。
 *
 * 使用说明：
 *   1. 由框架Session后端实现，并由myReq登记到PHP会话机制。
 *   2. 其他validateId/updateTimestamp等可选钩子由具体处理器提供。
 *   3. read成功返回序列化会话字节串，空串与后端失败不能随意混为同一状态。
 *
 * 补充说明：
 * 框架静态SESSION处理器的兼容协议（PHP 7.4）。
 * read返回原始字符串（不存在为空串，失败为false），其余回调返回bool。
 * 可选validateId($sid)和updateTimestamp($sid,$data)由myReq自动注册，
 * 不增加必需方法，保留现有第三方六回调处理器兼容性。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
interface interface_session {
    /**
     * 功能说明：初始化存储。
     *
     * @param string $sess_path 路径
     * @param string $sess_name Cookie名
     * @return bool 存储初始化成功为true，目录/连接/结构准备失败为false
     */
    public static function open($sess_path, $sess_name);
    /**
     * 功能说明：释放连接和锁。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 连接及锁释放后的结果；内置实现允许重复关闭
     */
    public static function close();
    /**
     * 功能说明：读取并持有同ID请求锁。
     *
     * @param string $sid 会话ID
     * @return string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false
     */
    public static function read($sid);
    /**
     * 功能说明：写入完整字节串。
     *
     * @param string $sid 会话ID
     * @param string $sess_data 原始数据
     * @return bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false
     */
    public static function write($sid, $sess_data);
    /**
     * 功能说明：销毁会话。
     *
     * @param string $sid 会话ID
     * @return bool 目标会话删除及相关一致性处理成功为true，失败为false
     */
    public static function destroy($sid);
    /**
     * 功能说明：回收过期存储。
     *
     * @param int $maxlifetime 有效秒数
     * @return bool 处理器报告的回收结果；文件处理器可能跳过忙文件，true不表示所有过期文件均已删除，也不是删除条数
     */
    public static function gc($maxlifetime);
}
