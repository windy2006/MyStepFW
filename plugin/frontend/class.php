<?PHP
class plugin_frontend implements interface_plugin {
    public static function check(&$result = '') {
        $result = 'OK!';
        return true;
    }
    public static function install() {
        global $mystep;
        regPluginRoute('frontend');
        addPluginLink($mystep->getLanguage('plugin_frontend_info_name'), 'frontend');
    }
    public static function uninstall() {
        removePluginRoute('frontend');
        removePluginLink('frontend');
    }
    public static function main() {
        showPluginPage('frontend', 'frontend');
    }
}