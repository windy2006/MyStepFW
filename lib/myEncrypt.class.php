<?php
/********************************************
*                                           *
* Name    : Functions 4 Encrypt & Decrypt   *
* Modifier: Windy2000                       *
* Time    : 2003-05-03                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
加密解密
    myEncrypt::keyED($str, $encrypt_key)  // Class core
    myEncrypt::encStr($str, $key)         // Encrypt a string
    myEncrypt::encFile($file, $key)       // Encrypt a file
    myEncrypt::decStr($str, $key)         // Decrypt a string
    myEncrypt::decFile($file, $key)       // Decrypt a file
*/
class myEncrypt {
    /**
     * 核心加密方法
     * @param $str
     * @param $encrypt_key
     * @return string
     */
    public static function keyED($str, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr=0;
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            if($ctr==strlen($encrypt_key)) $ctr=0;
            $tmp.= substr($str, $i, 1) ^ substr($encrypt_key, $ctr, 1);
            $ctr++;
        }
        return $tmp;
    }

    /**
     * 加密字符串
     * @param $str
     * @param $key
     * @return string
     */
    public static function encStr($str, $key) {
        mt_srand((double)microtime()*1000000);
        $encrypt_key = md5(mt_rand(0, 32000));
        $ctr=0;
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            if($ctr==strlen($encrypt_key)) $ctr=0;
            $tmp.= substr($encrypt_key, $ctr, 1) . (substr($str, $i, 1) ^ substr($encrypt_key, $ctr, 1));
            $ctr++;
        }
        return self::keyED($tmp, $key);
    }

    /**
     * 加密文件
     * @param $file
     * @param $key
     */
    public static function encFile($file, $key) {
        if(!is_file($file) || filesize($file)==0) {
            trigger_error("File $file Needn't Encrypt !");
            return;
        }
        $info = pathinfo($file);
        $file_enc = $info['dirname'].'/enc_'.$info['basename'];
        $fp_r = fopen($file, 'rb');
        $fp_w = fopen($file_enc, 'wb');
        if(!$fp_r || !$fp_w) trigger_error('Cannot Read or Write File !');
        $enc_text = md5($key);
        fwrite($fp_w, $enc_text);
        while (!feof($fp_r)) {
            $data        = fread($fp_r, 1024);
            $enc_text    = self::encStr($data, $key);
            fwrite($fp_w, $enc_text);
        }
        fclose($fp_r);
        fclose($fp_w);
        unlink($file);
        rename($file_enc, $file);
        return;
    }

    /**
     * 解密字符串
     * @param $str
     * @param $key
     * @return string
     */
    public static function decStr($str, $key) {
        $str = self::keyED($str, $key);
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            $md5 = substr($str, $i, 1);
            $i++;
            $tmp.= (substr($str, $i, 1) ^ $md5);
        }
        return $tmp;
    }

    /**
     * 解密字符串
     * @param $file
     * @param $key
     */
    public static function decFile($file, $key) {
        if(!is_file($file) || filesize($file)==0) {
            trigger_error("File $file Needn't Decrypt !");
            return;
        }
        $info = pathinfo($file);
        $file_dec = $info['dirname'].'/dec_'.$info['basename'];
        $fp_r = fopen($file, 'rb');
        $fp_w = fopen($file_dec, 'wb');
        if(!$fp_r || !$fp_w) trigger_error('Cannot Read or Write File !');
        $enc_key = md5($key);
        if($enc_key != fread($fp_r, strlen($enc_key))) {
            fclose($fp_r);
            fclose($fp_w);
            unlink('dec_'.$file);
            die('Wrong Decryption Key !');
        }
        while (!feof($fp_r)) {
            $data        = fread($fp_r, 1024);
            $dec_text    = self::decStr($data, $key);
            fwrite($fp_w, $dec_text);
        }
        fclose($fp_r);
        fclose($fp_w);
        unlink($file);
        rename($file_dec, $file);
        return;
    }
}