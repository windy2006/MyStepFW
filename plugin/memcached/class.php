<?PHP
class plugin_memcached implements interface_plugin {
    public static function check(&$result = '') {
        $result = 'OK!';
        return class_exists('Memcached');
    }
    public static function install() {
        return true;
    }
    public static function uninstall() {
        return true;
    }
}