<?php
class plugin_sample implements interface_plugin {
    public static function check(&$result = ''){
        $result = "";
        $theList = array(
            "dir1/",
            "dir2/",
        );
        $flag = true;
        $dir = dirname(__FILE__);
        foreach($theList as $cur) {
            if(myFile::rewritable($dir.'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    public static function install(){
        $dir = dirname(__FILE__);
        myFile::mkdir($dir.'/dir1');
        myFile::mkdir($dir.'/dir2');
    }
    public static function uninstall(){
        $dir = dirname(__FILE__);
        myFile::del($dir.'/dir1');
        myFile::del($dir.'/dir2');
    }
    public static function setPage($content){
        return str_replace('<title>', '<title>插件调整(可关闭) - ', $content);
    }
}