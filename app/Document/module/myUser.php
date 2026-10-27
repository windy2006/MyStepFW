<?php
/**
 * 文件：app/Document/module/myUser.php (new)
 * 功能说明：演示普通会员输入规范及公开字段白名单，不访问会员存储。
 * 方法目录：无具名方法。
 * 使用说明：只使用虚构资料；不注册、登录、修改会话、数据库或发送邮件。
 */
$example=['id'=>'12','username'=>'sample','display_name'=>myUser::displayName(' 示例会员 '),'email'=>myUser::email('sample@EXAMPLE.com'),'email_verified'=>false,'active'=>true,'has_password'=>false,'password'=>'不会输出'];
echo '<pre>'.htmlspecialchars(print_r(myUser::profile($example),true),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</pre>';
// 应用模块使用$mystep->users()；userProvider由应用同名核心类覆写。
// verifyEmail/loginVerified只接受已认证服务内部的结果，不能拿HTTP的ID直接登录。
