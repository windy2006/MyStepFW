<?PHP
/********************************************
*                                           *
* Name    : Base Function For All Classes   *
* Modifier: Windy2000                       *
* Time    : 2008-07-16                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
 * 基础类，包含构建方法和错误处理
 */
abstract class myBase {
    protected $err_handler = null;

    /**
     * 通用构造方法，转移到 init 方法
     */
    public function __construct() {
        $argList = func_get_args();
        if(is_callable(array($this, "init"))) {
            if(count($argList)>0 ) {
                call_user_func_array(array($this, "init"), $argList);
            } else {
                $reflection = new ReflectionMethod($this, "init");
                if($reflection->getNumberOfParameters()>0) {
                    $paras = $reflection->getParameters();
                    if(!$paras[0]->isOptional()) return;
                }
                call_user_func(array($this, "init"));
            }
        }
        return;
    }

    /**
     * 设置错误处理函数
     * @param $func
     */
    protected function setErrHandler($func) {
        if(is_callable($func)) $this->err_handler = $func;
    }

    /**
     * 通用错误处理方法
     * @param $msg
     * @param bool $exit
     * @return bool
     */
    protected function error($msg, $exit = false) {
        if(is_null($this->err_handler)) {
            myException::$callback_type |= E_USER_ERROR;
            if(myException::$log_type & E_USER_ERROR) trigger_error($msg, E_USER_ERROR);
        } else {
            call_user_func($this->err_handler, $msg);
            $type = myException::$callback_type;
            myException::$callback_type = 0;
            trigger_error($msg, E_USER_ERROR);
            myException::$callback_type = $type;
        }
        if($exit || myException::$exit_on_error) exit;
        return true;
    }
}
