<?PHP
/********************************************
*                                           *
* Name    : My Proxy                        *
* Modifier: Windy2000                       *
* Time    : 2018-11-2                       *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
 * 用于代理模式的相关方法，可自动调用被代理实例方法
 */
abstract class myProxy extends myBase {
    use myTrait {
        __get as public __get_base;
        __set as public __set_base;
        __call as public __call_base;
    }

    /**
     * 被代理实例
     * @var string
     */
    protected
        $obj = '';

    /**
     * 初始化类，并调用构造或init方法
     * @param string $module
     */
    public function init($module = "") {
        $args = func_get_args();
        $module = array_shift($args);
        if(class_exists($module)) {
            $this->obj = new $module();
            if(is_callable(array($this->obj, 'init'))) {
                call_user_func_array(array($this->obj, 'init'), $args);
            } elseif(is_callable(array($this->obj, '__construct'))) {
                call_user_func_array(array($this->obj, '__construct'), $args);
            }
        } else {
            $this->error('Cannot initialize module ['.$module.']!', E_USER_ERROR);
        }
    }

    /**
     * 参数调用
     * @param $para
     * @return mixed|null
     */
    public function __get($para) {
        if(isset($this->obj->$para)) {
            return $this->obj->$para;
        } else {
            return $this->__get_base($para);
        }
    }

    /**
     * 参数设置
     * @param $para
     * @param $value
     */
    public function __set($para, $value) {
        if(isset($this->obj->$para)) {
            $this->obj->$para = $value;
        } else {
            $this->__set_base($para, $value);
        }
    }

    /**
     * 方法调用
     * @param $func
     * @param array $args
     * @return mixed|null
     */
    public function __call($func, array $args) {
        if(is_callable(array($this->obj, $func))) {
            return call_user_func_array(array($this->obj, $func), $args);
        } else {
            return $this->__call_base($func, $args);
        }
    }

    /**
     * 方法别名设置
     * @param array $alias
     * @return array|mixed|null
     */
    public function regAlias($alias = array()) {
        if(!isset($this->func_alias)) {
            $this->func_alias = array();
        }
        foreach($alias as $k => $v) {
            $this->func_alias[$k] = $v;
        }
        return $this->func_alias;
    }
}