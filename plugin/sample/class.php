<?PHP
class plugin_sample implements interface_plugin {
    public static function check(&$result = '') {
        $result = "";
        $theList = array(
            "dir1/",
            "dir2/",
        );
        $flag = true;
        $dir = __DIR__;
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
    public static function install() {
        global $mystep;
        $dir = __DIR__;
        myFile::mkdir($dir.'/dir1');
        myFile::mkdir($dir.'/dir2');
        myFile::del(CACHE.'script');
        regPluginRoute('sample');
        addPluginLink($mystep->getLanguage('plugin_sample_name_link'), 'sample_route');
    }
    public static function uninstall() {
        $dir = __DIR__;
        myFile::del($dir.'/dir1');
        myFile::del($dir.'/dir2');
        myFile::del(CACHE.'script');
        removePluginRoute('sample');
        removePluginLink('sample_route');
    }
    public static function setPage($content) {
        return str_replace('<title>', '<title>插件测试 - ', $content);
    }
    public static function main() {
        showPluginPage('sample', 'sample');
    }
    public static function api() {
        return include(__DIR__.'/config.php');
    }
}