<?PHP
/*--- myCache_File ---*/

class myCache_File implements interface_cache {
    protected $path = '';

    public function __construct($path) {
        if(!class_exists('myFile')) {
            trigger_error('Class myFile must be loaded first!');
            exit;
        }
        if(!empty($path)) {
            $this->path = $path.date('/Ymd/');
            myFile::mkdir($this->path);
        }
    }

    public function set($key, $value = '', $ttl = 600) {
        $new_key = substr(md5($key), 0, 8);
        $the_path = $this->path.substr($new_key, 0, 2).'/';
        if(empty($value)) {
            myFile::del($the_path.$new_key);
        } else {
            $result = array(
                    'expire' => $_SERVER['REQUEST_TIME']+$ttl,
                    'value' => $value,
            );
            myFile::mkdir($the_path);
            myFile::saveFile($the_path.$new_key, serialize($result));
        }
    }

    public function get($key) {
        $new_key = substr(md5($key), 0, 8);
        $the_path = $this->path.substr($new_key, 0, 2).'/';
        if(is_file($the_path.$new_key)) {
            if(($result = unserialize(myFile::getLocal($the_path.$new_key))) && $result['expire']>$_SERVER['REQUEST_TIME']) {
                return $result['value'];
            } else {
                myFile::del($the_path.$new_key);
                return false;
            }
        } else {
            return false;
        }
    }

    public function remove($key) {
        $new_key = substr(md5($key), 0, 8);
        $the_path = $this->path.substr($new_key, 0, 2).'/';
        myFile::del($the_path.$new_key);
    }

    public function clean() {
        $path = dirname($this->path).'/';
        if($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if($file=='.' || $file=='..' || $file==date('Ymd')) continue;
                myFile::del($path.$file);
            }
            closedir($handle);
            return true;
        }
        return false;
    }
}