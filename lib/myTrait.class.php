<?PHP
/********************************************
*                                           *
* Name    : Magical Functions               *
* Modifier: Windy2000                       *
* Time    : 2018-11-02                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
 * Magic Trait for other class
 * 类内魔术方法，并添加设置方法别名（regAlias($alias)）和添加新方法（addMethod($func)）的接口
 */
trait myTrait {
    protected
        $methods = array(),
        $vars = array();

    public function __get($para) {
        if($para=="instatnce") {
            $class_name = get_called_class();
            return new $class_name();
        } else {
            return array_key_exists($para, $this->vars) ? $this->vars[$para] : null;
        }
    }

    public function __set($para, $value) {
        $this->vars[$para] = $value;
    }

    public function __destruct() {
        if(!isset($this)) return;
        $varList = array_keys(get_class_vars(get_class($this)));
        for($i=0,$m=count($varList);$i<$m;$i++) {
            if($varList[$i] == 'vars') continue;
            unset($this->{$varList[$i]});
        }
        unset($this->vars);
    }

    public function __isset($para) {
        return isset($this->$para) || isset($this->vars[$para]);
    }

    public function __unset($para) {
        unset($this->$para, $this->vars[$para]);
    }

    public function __call($func, array $args) {
        if(isset(self::$func_alias)) $this->func_alias = self::$func_alias;
        if(isset($this->func_alias) && isset($this->func_alias[$func])) {
            $result = call_user_func_array(array($this, $this->func_alias[$func]), $args);
        }elseif(is_callable($func)) {
            $result = call_user_func_array($func, $args);
        } elseif(is_callable("\\".$func)) {
            $result = call_user_func_array("\\".$func, $args);
        } elseif(isset($this->methods[$func])) {
            $result = call_user_func_array($this->methods[$func], $args);
        } else {
            $result = null;
        }
        return $result;
    }

    public static function __callStatic($func, $args) {
        if(isset(self::$func_alias) && isset(self::$func_alias[$func])) {
            $result = call_user_func_array('self::'.(self::$func_alias[$func]), $args);
        }elseif(is_callable($func)) {
            $result = call_user_func_array($func, $args);
        } elseif(is_callable('\\'.$func)) {
            $result = call_user_func_array('\\'.$func, $args);
        } else {
            $result = null;
        }
        return $result;
    }

    public function __sleep() {
        return array_keys(get_class_vars(get_class($this)));
    }

    public function __wakeup() {
        if(is_callable(array($this, '__construct'))) {
            $this->__construct();
        }
    }

    public function __toString() {
        $result = array();
        $result['name'] = get_class($this);
        $result['vars'] = array();
        $vars = get_class_vars(get_class($this));
        foreach($vars as $var => $value) {
            $result['vars'][$var] = $this->$var;
        }
        $result['methods'] = array();
        $methods = get_class_methods(get_class($this));
        foreach($methods as $method) {
            $result['methods'][] = $method;
        }
        return serialize($result);
    }

    public function __invoke($para) {
        if(is_string($para)) {
            return str_shuffle(md5($para));
        } elseif(is_numeric($para)) {
            mt_srand($para);
            return mt_rand();
        } elseif(is_array($para) || is_object($para)) {
            foreach($para as $key => $value) {
                $this->$key = $value;
            }
            return true;
        } else {
            return debug_backtrace();
        }
    }

    public static function __set_state($para_arr) {
        $class_name = get_class();
        $instance = new $class_name();
        foreach($para_arr as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }

    public function __debugInfo() {
        $result = array();
        $result['name'] = get_class($this);
        $result['vars'] = array();
        $vars = get_class_vars(get_class($this));
        foreach($vars as $var => $value) {
            $result['vars'][$var] = $this->$var;
        }
        $result['methods'] = array();
        $methods = get_class_methods(get_class($this));
        foreach($methods as $method) {
            $result['methods'][] = $method;
        }
        return $result;
    }

    public function addMethod($func, $name='') {
        if(is_callable($func)) {
            if(empty($name)) {
                $name = is_array($func)?$func[1]:$func;
            }
            $this->methods[$name] = Closure::bind($func, $this, get_class());
        } else {
            if(is_callable($this, 'error')) {
                $this->error('The Function must be callable');
            } else {
                trigger_error('The Function must be callable', E_USER_ERROR);
            }
        }
    }

    public static function regAlias($alias = array()) {
        if(!isset(self::$func_alias)) {
            self::$func_alias = array();
        }
        foreach($alias as $k => $v) {
            self::$func_alias[$k] = $v;
        }
        return self::$func_alias;
    }
}