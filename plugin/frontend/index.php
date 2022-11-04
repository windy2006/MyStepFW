<?PHP
require_once(__DIR__."/class.php");
$this->setFunction('start', function() {
    global $I;
    $setting = new myConfig(__DIR__.'/config.php');
    if($setting = myString::fromJson(stripslashes($setting->detail))) {
        if(isset($setting[$I['app']])) {
            foreach($this->css as $k => $v) {
                if(basename($v)=='bootstrap.css') {
                    $this->css[$k] = __DIR__.'/files/bootstrap/'.$setting[$I['app']]['bs'].'/css/bootstrap.css';
                    break;
                }
            }
            $result = [];
            foreach($this->js as $k => $v) {
                if(basename($v)=='jquery.js') {
                    $file = __DIR__.'/files/jquery/'.$setting[$I['app']]['jq'];
                    $result[md5($file)] = $file;
                    preg_match('#^jquery-(.+)\.js$#', $setting[$I['app']]['jq'], $match);
                    $ver = $match[1];
                    if(version_compare($ver,'1.9')) {
                        $file = __DIR__.'/files/jquery/jquery-migrate-3.4.0.js';
                    } else {
                        $file = __DIR__.'/files/jquery/jquery-migrate-1.4.1.js';
                    }
                    $result[md5($file)] = $file;
                }elseif(basename($v)=='bootstrap.bundle.js') {
                    $file = __DIR__.'/files/bootstrap/'.$setting[$I['app']]['bs'].'/js/bootstrap.bundle.js';
                    $result[md5($file)] = $file;
                } else {
                    $result[$k] = $v;
                }
            }
            $this->js = $result;
            unset($result);
        }
    }
});