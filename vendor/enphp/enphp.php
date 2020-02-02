<?php
if(class_exists('myException')) {
    myException::init(array(
        'log_mode' => 0, 
        'log_type' => (E_ALL & ~E_NOTICE), 
        'callback_type' => (E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE)), 
        'exit_on_error' => false
    ));
} else {
    error_reporting(E_ALL & ~E_NOTICE);
}
require_once('func_v2.php');
class enphp {
    protected
        $options = array(
            //混淆方法名 1=字母混淆 2=乱码混淆
            'ob_function'        => 2, 
            //混淆函数产生变量最大长度
            'ob_function_length' => 3, 
            //混淆函数调用 1=混淆 0=不混淆 或者 array('eval', 'strpos') 为混淆指定方法
            'ob_call'            => 1, 
            //随机插入乱码
            'insert_mess'        => 0, 
            //混淆函数调用变量产生模式  1=字母混淆 2=乱码混淆
            'encode_call'        => 2, 
            //混淆class
            'ob_class'           => 0, 
            //混淆变量 方法参数  1=字母混淆 2=乱码混淆
            'encode_var'         => 2, 
            //混淆变量最大长度
            'encode_var_length'  => 5, 
            //混淆字符串常量  1=字母混淆 2=乱码混淆
            'encode_str'         => 2, 
            //混淆字符串常量变量最大长度
            'encode_str_length'  => 3, 
            // 混淆html 1=混淆 0=不混淆
            'encode_html'        => 2, 
            // 混淆数字 1=混淆为0x00a 0=不混淆
            'encode_number'      => 1, 
            // 混淆的字符串 以 gzencode 形式压缩 1=压缩 0=不压缩
            'encode_gz'          => 1, 
            // 加换行（增加可阅读性）
            'new_line'           => 0, 
            // 移除注释 1=移除 0=保留
            'remove_comment'     => 1, 
            // 文件头部增加的注释
            'comment'            => 'MyStep Framework', 
            // debug
            'debug'              => 1, 
            // 重复加密次数，加密次数越多反编译可能性越小，但性能会成倍降低
            'deep'               => 1, 
            // PHP 版本
            'php'                => 7, 
        ), 
        $paras = array();

    public function __construct($option=[]) {
        $this->options = array_merge($this->options, $option);
    }

    public function encode($source) {
        if(is_file($source)) {
            if(substr($source, -4)!='.php') return;
            $content = file_get_contents($source);
            check_bom($content);
            $content = enphp($content, $this->options);
            file_put_contents($source, $content);
        } elseif(is_dir($source)) {
            $d=dir($source);
            while(false !== ($file=$d->read())) {
                if($file=='.' || $file=='..') continue;
                $this->encode($source.'/'.$file);
            }
            $d->close();
        }
    }

    public function __call($func, $paras) {
        if(is_callable($func)) {
            $result = call_user_func_array($func, $paras);
        } else {
            $result = false;
        }
        return $result;
    }
}
