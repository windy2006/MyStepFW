<?PHP
/**
 * 文件：app/Document/module/myProxy.php
 * 功能说明：演示抽象代理类的目标对象、方法别名及公开属性引用。
 * 方法目录：匿名目标类greet（说明见方法PHPDoc）。
 * 使用说明：继承myProxy后把类名或已有对象传给构造/init；已有对象不能再附带初始化参数。
 */
$demoTarget = new class {
    /** @var array 示例项目列表 */
    public $items = ['初始项目'];
    /** @var string 问候前缀 */
    public $prefix = '你好，';
    /**
     * 功能说明：返回目标对象的问候结果。
     * @param string $name 问候对象
     * @return string 带前缀的问候语
     */
    public function greet($name) { return $this->prefix.$name; }
};
$demoProxy = new class($demoTarget) extends myProxy {};
$demoProxy->prefix = '欢迎，';
$demoProxy->regAlias(['hello'=>'greet']);
$demoItems =& $demoProxy->propertyReference('items');
$demoItems[] = '通过引用增加';
unset($demoItems);

echo '<pre>'.htmlspecialchars(print_r([
    '普通方法转发'=>$demoProxy->greet('开发者'),
    '别名调用'=>$demoProxy->hello('MyStep'),
    '属性写入作用于目标'=>$demoTarget->prefix,
    '数组引用修改作用于目标'=>$demoTarget->items,
], true), ENT_QUOTES, 'UTF-8').'</pre>';
// 普通__get不按引用返回；追加数组元素应显式使用propertyReference。
