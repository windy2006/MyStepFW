<?PHP
/**
 * 文件：app/Document/module/myTrait.php
 * 功能说明：演示trait混入、辅助属性、运行时方法及方法别名。
 * 方法目录：匿名类greet及匿名扩展方法caption（说明见各回调PHPDoc）。
 * 使用说明：trait不能直接new；在自己的类中使用use myTrait，别名只登记在该示例类上。
 */
$demoObject = new class {
    use myTrait;
    /**
     * 功能说明：返回传入名字的问候语。
     * @param string $name 问候对象
     * @return string 问候语
     */
    public function greet($name) { return '你好，'.$name; }
};
$demoObject->title = 'MyStep';
$demoObject->addMethod(
    /**
     * 功能说明：扩展方法的普通闭包绑定到当前实例，可读取辅助属性。
     * @param string $suffix 标题后缀
     * @return string 拼接后的标题
     */
    function($suffix) { return $this->title.$suffix; },
    'caption'
);
$demoObject::regAlias(['hello'=>'greet']);
$demoCaption = $demoObject->caption(' 框架');
$demoFresh = $demoObject->instance; // 新建同类实例，不复制辅助属性或扩展方法。
unset($demoObject->title);

echo '<pre>'.htmlspecialchars(print_r([
    '动态方法结果'=>$demoCaption,
    '方法别名'=>$demoObject->hello('开发者'),
    '新实例不复制标题'=>$demoFresh->title,
    '删除辅助属性后isset'=>isset($demoObject->title),
    '当前类别名表'=>$demoObject::regAlias(),
], true), ENT_QUOTES, 'UTF-8').'</pre>';
