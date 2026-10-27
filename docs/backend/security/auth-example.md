# 认证示例

本例分别使用myAuth验证口令、myEncrypt加解密可恢复的数据。例子不创建账户、不改变会话、不保存临时密钥。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$password = 'Only-for-this-example-2026!';
$hash = myAuth::hashPassword($password);
$key = random_bytes(32);
$envelope = myEncrypt::seal('0', $key);
if ($envelope === false) throw new RuntimeException('认证加密不可用');
echo json_encode([
    'password_ok'=>myAuth::verifyPassword($password, $hash),
    'password_wrong'=>myAuth::verifyPassword('wrong', $hash),
    'restored'=>myEncrypt::open($envelope, $key),
    'wrong_key_rejected'=>myEncrypt::open($envelope, random_bytes(32)) === false,
]);
// 不输出或持久化本例的hash/key；实际系统由受保护配置或密钥服务保管持久密钥。
unset($password, $hash, $key, $envelope);
```

## 预期结果

password_ok=true、password_wrong=false、restored为字符串"0"、wrong_key_rejected=true。不能用if(!$restored)判断解密是否失败。

## 调用顺序与实际应用

密码不需要还原明文，使用myAuth的单向摘要；需要恢复的数据才用myEncrypt的认证封装。登录由当前应用的认证流程建立会话，生成摘要本身不表示登录成功，也不授予管理员权限。会话CSRF的用法见[安全机制](security.md)。
