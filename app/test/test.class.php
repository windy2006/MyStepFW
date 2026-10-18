<?PHP
$GLOBALS['the_order'] = 1;
echo $GLOBALS['the_order']++.' - app/test/test.class.php<br />';
class test extends myStep {
    public static $log = '';
    //登录
    public function login($usr='', $pwd='') {
        return parent::login($usr, $pwd);
    }
    //登出
    public function logout() {
        myReq::sessionEnd();
        return true;
    }
    //预执行程序
    public function preload() {
        echo $GLOBALS['the_order']++.' - myStep::preload<br />';
        $this->setting->web->title = '标题调整';
        //code
    }
    //执行首尾程序
    public function shutdown() {
        echo $GLOBALS['the_order']++.' - myStep::shutdown<br />';
        //code
    }
}