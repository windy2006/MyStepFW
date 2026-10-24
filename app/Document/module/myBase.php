<?PHP
/**
 * 文件：app/Document/module/myBase.php
 * 功能说明：演示基础抽象类的构造参数转交及延迟初始化。
 * 方法目录：两个匿名子类的init（说明见各方法PHPDoc）。
 * 使用说明：myBase不能直接实例化，应用功能类通过继承并实现init获得统一初始化行为。
 */
$demoReady = new class('构造时初始化') extends myBase {
    /** @var string|null 示例标题 */
    public $title = null;
    /**
     * 功能说明：接收父类构造函数转交的标题。
     * @param string $title 示例标题
     * @return void
     */
    public function init($title) { $this->title = $title; }
};
$demoDelayed = new class extends myBase {
    /** @var string|null 初始化前保持null */
    public $title = null;
    /**
     * 功能说明：显式传入必需参数后完成延迟初始化。
     * @param string $title 示例标题
     * @return void
     */
    public function init($title) { $this->title = $title; }
};
$demoBefore = $demoDelayed->title;
$demoDelayed->init('手动延迟初始化');

echo '<pre>'.htmlspecialchars(print_r([
    '有参数时自动调用init'=>$demoReady->title,
    '必需参数缺失时暂不调用init'=>$demoBefore,
    '显式初始化后'=>$demoDelayed->title,
    '基础类为抽象类'=>(new ReflectionClass('myBase'))->isAbstract(),
], true), ENT_QUOTES, 'UTF-8').'</pre>';
// setErrHandler/error是受保护入口，供子类封装使用；错误是否终止由myException策略决定。
// 不在公开示例中触发E_USER_ERROR，避免中断说明页或污染错误日志。
