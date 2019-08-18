<?php
class plugin_test implements interface_plugin {
    public static function check(&$result = ''){
        $result = "";
        $theList = array(
            "dir1/",
            "dir2/",
        );
        $flag = true;
        foreach($theList as $cur) {
            if(myFile::rewritable(dirname(__FILE__).'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    public static function install(){
        myFile::mkdir(PLUGIN.'test/dir1');
        myFile::mkdir(PLUGIN.'test/dir2');
    }
    public static function uninstall(){
        myFile::del(PLUGIN.'test/dir1');
        myFile::del(PLUGIN.'test/dir2');
    }
}