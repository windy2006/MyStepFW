<?PHP
/**
 * 文件：app/Document/module/myAuth.php
 * 功能说明：演示密码摘要、历史摘要兼容及用途绑定的记住登录令牌。
 * 方法目录：无新增方法或函数。
 * 使用说明：仅使用虚构账号及临时随机密钥；不读取真实账号，不修改登录状态、Cookie或数据库。
 */
$demoPassword = 'Example-only:Pass#2026';
$demoHash = myAuth::hashPassword($demoPassword);
$demoLegacy = myAuth::protectLegacyHash(md5($demoPassword));
$demoSecret = bin2hex(random_bytes(32)); // 实际项目应使用服务器持久保存的高熵密钥。
$demoToken = myAuth::makeToken('example-user', $demoHash, 'document-demo', $demoSecret, 300);
$demoPayload = myAuth::readToken($demoToken); // 只解析结构和时效，不能据此信任身份。

echo '<pre>'.htmlspecialchars(print_r([
    '摘要使用v2前缀'=>strpos($demoHash, 'v2:') === 0,
    '正确密码'=>myAuth::verifyPassword($demoPassword, $demoHash),
    '错误密码'=>myAuth::verifyPassword('wrong-password', $demoHash),
    '历史摘要仍可校验'=>myAuth::verifyPassword($demoPassword, $demoLegacy),
    '历史摘要需要升级'=>myAuth::needsUpgrade($demoLegacy),
    '令牌载荷中的示例账号'=>$demoPayload['u'],
    '用途及签名正确'=>myAuth::verifyToken($demoToken, 'example-user', $demoHash, 'document-demo', $demoSecret),
    '不同用途被拒绝'=>!myAuth::verifyToken($demoToken, 'example-user', $demoHash, 'another-app', $demoSecret),
], true), ENT_QUOTES, 'UTF-8').'</pre>';
// 成功登录后再升级并保存密码摘要；登录或身份变更后可调用myAuth::renewSession()。
// 示例不执行renewSession，不展示真实密钥或当前用户的登录令牌。
