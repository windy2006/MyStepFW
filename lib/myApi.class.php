<?php
/********************************************
*                                                *
* Name    : My Api                          *
* Author  : Windy2000                       *
* Time    : 2010-12-12                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
系统API处理
    $Api->regMethod($method, $func)   // Set the Callback function
    $Api->run($method)                // run registered function
*/
class myApi extends myBase {
    use myTrait;

    protected $methods = array();

    public function init() {}

    public function regMethod($method, $func) {
        if(is_callable($func)) $this->methods[$method] = $func;
    }

    public function regMethods($method_list) {
        foreach($method_list as $key => $value) {
            if(is_callable($value)) $this->methods[$key] = $value;
        }
    }

    public function run($method, $para=array(), $return="json", $charset="utf-8") {
        $result = "";
        if(isset($this->methods[$method])) {
            if(empty($para)) {
                $result = call_user_func($this->methods[$method]);
            } else {
                $result = call_user_func_array($this->methods[$method], $para);
            }
        }
        if(empty($charset)) $charset="utf-8";
        switch($return) {
            case "x":
            case "xml":
                $result = '<?xml version="1.0" encoding="'.$charset.'"?>
<mystep>
'.myString::toXML($result).'
</mystep>';
                header('Content-Type: application/xml; charset='.$charset);
                break;
            case "s":
            case "string":
                $result = myString::fromAny($result);
                break;
            case "h":
            case "hex":
                $result = myString::toHex(myString::fromAny($result));
                break;
            case "c":
            case "code":
            case "script":
                $result = myString::toScript(['result'=>$result]);
                break;
            case "j":
            case "json":
            default:
                $result = myString::toJson($result, $charset);
                break;
        }
        return $result;
    }
}
