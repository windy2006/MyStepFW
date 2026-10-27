<?php
/**
 * 文件：app/Document/module/interface_user.php (new)
 * 功能说明：只读列举普通会员协议及原生方法签名，帮助应用实现适配器。
 * 方法目录：无具名方法。
 * 使用说明：反射不实例化会员适配器，不连接数据库，不产生登录/邮件副作用。
 */
$contract=new ReflectionClass(interface_user::class);$methods=[];
foreach($contract->getMethods() as $method){$args=[];foreach($method->getParameters() as $parameter){$arg=($parameter->hasType()?(string)$parameter->getType().' ':'').'$'.$parameter->getName();if($parameter->isDefaultValueAvailable())$arg.='='.var_export($parameter->getDefaultValue(),true);$args[]=$arg;}$methods[]=$method->getName().'('.implode(', ',$args).'): '.(string)$method->getReturnType();}
echo '<pre>'.htmlspecialchars(implode("\n",$methods),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</pre>';
// 完整实现见app/CMS/lib.php的MemberProvider；业务类保留在应用目录。
