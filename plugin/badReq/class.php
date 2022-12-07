<?PHP
class plugin_badReq implements interface_plugin {
    public static function check(&$result = '') {
        $result = 'OK!';
        return true;
    }
    public static function install() {
        global $mystep;
        if (extension_loaded('sqlite3')) {
            regPluginRoute('badReq');
            addPluginLink($mystep->getLanguage('plugin_badReq_info_name'), 'badReq');
            $db = new myDb('sqlite', __DIR__.'/data.db');
            $db->query("CREATE TABLE if not exists requests(
                id INTEGER PRIMARY KEY autoincrement, 
                ip varchar(40),
                url varchar(255), 
                qry varchar(255), 
                ua varchar(255), 
                cnt integer,
                req DATETIME
            )");
        }
        setPriority(basename(__DIR__));
    }
    public static function uninstall() {
        if (extension_loaded('sqlite3')) {
            myFile::del(__DIR__.'/data.db');
            removePluginRoute('badReq');
            removePluginLink('badReq');
        }
        myFile::saveFile(__DIR__.'/ban_ip.php', '<?PHP'.chr(10).'return [];');
        myFile::del(CACHE.'tmp/post/');
        removePriority(basename(__DIR__));
    }
    public static function main() {
        showPluginPage('badReq', 'badReq');
    }
}