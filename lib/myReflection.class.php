<?php
/********************************************
*                                           *
* Name    : Reflection Function For All     *
* Modifier: Windy2000                       *
* Time    : 2018-11-2                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
    获取类、函数及扩展信息
    $this->init($para)
    $this->info()
    $this->export()
    $this->new()
    $this->getName($mode)
    $this->getVariants()
    $this->getFunc($method)
    $this->getComment($method)
    $this->is($type)
    $this->check($name, $type)
*/
class myReflection extends myProxy {
    protected
        $info = array();

    /**
     * 获取对象基本信息
     * @param $para
     * @throws ReflectionException
     */
    public function init($para = '') {
        $this->info = array();
        switch (true) {
            case is_string($para) && get_extension_funcs($para):
                $this->obj = new ReflectionExtension($para);
                $type = 'extension';
                break;
            case is_string($para) && class_exists($para):
                $this->obj = new ReflectionClass($para);
                $type = 'class';
                break;
            case is_string($para) && function_exists($para):
                $this->obj = new ReflectionFunction($para);
                $type = 'function';
                break;
            case is_object($para):
                $this->obj = new ReflectionObject($para);
                $type = 'class';
                break;
            default:
                $this->error('Only class or function is allowed!');
                return;
                break;
        }
        $this->info = array(
            'para' => $para, 
            'name' => $this->obj->getName(), 
            'type' => $type, 
            'file' => $this->getFileName(), 
            'start' => $this->getStartLine(), 
            'end' => $this->getEndLine(), 
            'ext' => $this->getExtensionName(), 
            'doc' => $this->getComment(), 
        );

        if($type=='class') {
            $this->info['interface'] = $this->getInterfaceNames();
            $this->info['parent'] = $this->getParentClass()?$this->getParentClass()->getName():'';
            $this->info['trail'] = $this->getTraitNames();
            $this->info['instance'] = is_object($para)?$this->isInstance($para):false;
            $this->info['method'] = $this->getFunc();
            switch(true) {
                case $this->isAbstract():
                    $this->info['type'] = 'abstract ';
                    break;
                case $this->isAnonymous():
                    $this->info['type'] = 'anonymous';
                    break;
                case $this->isInterface():
                    $this->info['type'] = 'interface';
                    break;
                case $this->isTrait():
                    $this->info['type'] = 'trait';
                    break;
                default:
                    $this->info['type'] = 'class';
                    break;
            }
        }elseif($type=='extension') {
            $this->info['version'] = $this->getVersion();
            $this->info['setting'] = $this->getINIEntries();
            $this->info['temporary'] = $this->isTemporary();
        }
    }

    /**
     * 返回对象某一基本信息
     * @param $para
     * @return mixed|null
     */
    public function __get($para) {
        return array_key_exists($para, $this->info) ? $this->info[$para] : null;
    }

    /**
     * 返回全部基本信息
     * @return array
     */
    public function info() {
        return $this->info;
    }

    /**
     * 返回对象详细信息
     * @return string
     */
    public function export() {
        return $this->obj->export($this->info['para'], true);
    }

    /**
     * 建立新的类实例或返回函数结果
     * @return mixed|null
     */
    public function get() {
        if(empty($this->info)) return false;
        $result = null;
        if($this->info['type']=='function') {
            //$result = call_user_func_array([$this, 'invoke'], func_get_args());
            $result = $this->invokeArgs(func_get_args());
        } elseif($this->info['type']=='class') {
            if($this->getConstructor()==null) {
                $result = $this->newInstanceWithoutConstructor();
            } else {
                //$result = call_user_func_array([$this, 'newInstance'], func_get_args());
                $result = $this->newInstanceArgs(func_get_args());
            }
        }
        return $result;
    }

    /**
     * 获取对象名称
     * @param bool $mode    返回全路径或路径加名称
     * @return array
     */
    public function getName($mode = false) {
        if($this->inNamespace()) {
            if($mode) {
                $result = array(
                    'namespace' => $this->getNamespaceName(), 
                    'name' => $this->getShortName()
                );
            } else {
                $result = $this->obj->getName();
            }
        } else {
            $result = $this->getShortName();
        }
        return $result;
    }

    /**
     * 获取对象所有变量及常量
     * @return array
     */
    public function getVariants() {
        if(empty($this->info)) return;
        if($this->info['type']=='function') return null;
        return array(
            'constant' => $this->getConstants(), 
            'variant' => $this->getDefaultProperties()
        );
    }

    /**
     * 获取对象方法
     * @param bool $mode
     * @return array
     */
    public function getFunc($mode = false) {
        if(empty($this->info)) return;
        $result = array();
        switch($this->info['type']) {
            case 'extension':
                if($mode) {
                    $result = array(
                        'function' => $this->getFunctions(), 
                        'class' => $this->getClasses()
                    );
                } else {
                    $result = array(
                        'function' => get_extension_funcs($this->info['name']), 
                        'class' => $this->getClassNames()
                    );
                }
                break;
            case 'class':
            case 'abstract':
            case 'anonymous':
            case 'interface':
            case 'trait':
                $result = $this->getMethods();
                break;
        }
        return $result;
    }

    /**
     * 获取对象说明
     * @param string $method
     * @return string
     */
    public function getComment($method = '') {
        if(empty($method)) {
            $result = $this->getDocComment();
        } elseif($method == '__ALL__') {
            $result = array();
            foreach($this->getMethods() as $method) {
                $result[$method->name] = $method->getDocComment();
            }
        } else {
            $result = $this->getMethod($method)->getDocComment();
        }
        if($result) {
            $result = preg_replace('/^\/[\*\r\n\s]*(.+)[\r\n\s\*]+\/$/ms', '\1', $result);
            $result = preg_replace('/[\r\n]+[\s\*]+/m', chr(10), $result);
            $result = preg_replace('/[\r\n]+$/m', '', $result);
        }
        return $result;
    }

    /**
     * 对象相关特性检测
     * @param string $type
     * @return bool
     */
    public function is($type = '') {
        if(empty($this->info)) return false;
        if($this->info['type']=='function') return false;
        $result = false;
        switch($type) {
            case 'a':
            case 'abstract':
                $result = $this->isAbstract();
                break;
            case 'n':
            case 'anonymous':
                $result = $this->isAnonymous();
                break;
            case 'i':
            case 'interface':
                $result = $this->isInterface();
                break;
            case 'c':
            case 'clone':
                $result = $this->isCloneable();
                break;
            case 'f':
            case 'final':
                $result = $this->isFinal();
                break;
            case 'ia':
            case 'instantiable':
                $result = $this->isInstantiable();
                break;
            case 'in':
            case 'internal':
                $result = $this->isInternal();
                break;
            case 'it':
            case 'iterable':
                $result = $this->isIterable();
                break;
            case 'ita':
            case 'iterateable':
                $result = $this->isIterateable();
                break;
            case 't':
            case 'trait':
                $result = $this->isTrait();
                break;
            case 'u':
            case 'userdefined':
                $result = $this->isUserDefined();
                break;
        }
        return $result;
    }

    /**
     * 对象相关属性检测
     * @param $name
     * @param string $type
     * @return bool
     */
    public function check($name, $type = 'method') {
        if(empty($this->info)) return false;
        if($this->info['type']=='function') return false;
        $result = false;
        switch($type) {
            case 'm':
            case 'method':
                $result = $this->hasMethod($name);
                break;
            case 'c':
            case 'constant':
                $result = $this->hasConstant($name);
                break;
            case 'p':
            case 'property':
            case 'v':
            case 'variant':
                $result = $this->hasProperty($name);
                break;
            case 'i':
            case 'interface':
                $result = $this->implementsInterface($name);
                break;
            case 'n':
            case 'namespace':
                $result = $this->inNamespace($name);
                break;
            case 's':
            case 'subclass':
                $result = $this->isSubclassOf($name);
                break;
        }
        return $result;
    }
}