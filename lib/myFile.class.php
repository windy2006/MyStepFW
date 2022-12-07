<?PHP
/********************************************
*                                           *
* Name    : Functions For File Operations   *
* Modifier: Windy2000                       *
* Time    : 2018-10-18                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
  文件处理
    $this->__get                                                // return the value of any public variant or from the info variant
    $this->__toString                                           // return the value of content
    self::rootPath($mode = false)                               // return the real path of document root
    self::realPath($file, $mode = false)                        // return the real and safe path of some file
    self::mkdir($dir)                                           // make a new directory with recursive feature
    self::getMime($file)                                        // return the mime type of the file
    self::getAttrib($file, $show_txt = false)                   // return the attrib of the file
    self::setAttrib($file, $attrib)                             // set the attrib of the file
    self::getHeader($url, $mode = true)                         // return the headers of any remote connection
    self::getSize($file, $format = ture)                        // return the size of the file
    self::formatSize($size, $precision=2)                       // return the formatted size like 12.34MB
    self::getByte($size)                                        // return the byte of any formatted size
    self::copy($source, $dest, $overwrite = false)              // copy any file or directory to another path
    self::del($file)                                            // delete any file or directory
    self::rename($file, $newname)                               // rename file or directory
    $this->save()                                               // save current content to the current file
    self::saveFile($file, $content='', $mode='wb')              // save some content to some file
    $this->get()                                                // get the content from current file
    self::getLocal($file, $length=0, $offset=0)                 // get the content from the local file
    self::getRemote($url, $header, $method, $data, $timeout)    // get the content from the remote file
    self::getRemote_curl($url, $data='', $header=array())       // get the content from the remote file
    self::show($content)                                        // content output
    self::find($filter='', $dir='./', $recursive=false)         // return a list of the specified files for specified directory
    self::getRemoteFile($remote_file, $local_file)              // get remote file to local
    self::judgeChild($dir, $only_dir = true)                    // To judge is there any file or subdirectory in a diretory
    self::getTree($dir='./')                                    // Get the info of the files in some directory
    self::removeBom($str)                                       // remove the bom of utf-8 file content
    self::rewritable($file)                                     // Check if a file or directory is rewritable
*/
class myFile {
    const
        DIR = 1,
        FILE = 2,
        BOTH = 3;

    use myTrait {
        myTrait::__get as public __get_base;
        myTrait::__toString as public __toString_base;
    }

    public static
        $func_alias = array(
            'rt' => 'rootPath',
            'p' => 'realPath',
            'd' => 'mkdir',
            'mime' => 'getMime',
            'c' => 'copy',
            'r' => 'rename',
            'm' => 'rename',
            's' => 'saveFile',
            'g' => 'getLocal',
            'get' => 'getLocal',
            'url' => 'getRemote',
            'grab' => 'getRemoteFile',
        );
    public
        $file = '',
        $remote = false,
        $exist = true,
        $headers = array(),
        $info = array(),
        $content = '',
        $context = null,
        $func_str = array('myFile', 'get');

    /**
     * 构造函数
     * @param $file
     * @param array $headers
     */
    public function __construct($file, $headers=array()) {
        ini_set("auto_detect_line_endings", true);
        if(preg_match('/^[\w]{2,}:/', $file)) $this->remote = true;
        if($this->remote) {
            $this->file = $file;
            $this->context = self::buildContext($file, $headers);
            $this->headers = self::getHeader($file);
            if(!(isset($this->headers[0]) && (strpos($this->headers[0], '200') || strpos($this->headers[0], '304')))) {
                trigger_error('Remote file cannot be found.');
                $this->exist = false;
            }
        } else {
            $file = $this->realPath($file);
            $this->file = $file;
            if(is_file($file)) {
                clearstatcache(true, $file);
                $this->info['type'] = filetype($file);
                $this->info['is_writable'] = is_writable($file);
                $this->info['time_visit'] = fileatime($file);
                $this->info['time_edit'] = filemtime($file);
                $this->info['size'] = ($this->info['type']=='file')?0:filesize($file);
                $this->info['perms'] = substr(decoct(fileperms($file)), -4);
                $this->info['mime'] = self::getMime($file);
            } else {
                $this->exist = false;
            }
        }
        $info = pathinfo($file);
        $this->info['path'] = $info['dirname'];
        $this->info['fullname'] = $info['basename'];
        $this->info['name'] = $info['filename'];
        $this->info['ext'] = isset($info['extension'])?$info['extension']:'';
    }

    /**
     * 内部变量读取
     * @param $para
     * @return mixed|null
     */
    public function __get($para) {
        if(array_key_exists($para, $this->info) ) {
            return $this->info[$para];
        } else {
            return $this->__get_base($para);
        }
    }

    /**
     * 字符串显示
     * @return mixed|string
     */
    public function __toString() {
        if(is_callable($this->func_str)) {
            return call_user_func($this->func_str, $this);
        } else {
            return $this->content;
        }
    }

    /**
     * 构建远程连接配置
     * @param $url
     * @param array $headers
     * @return resource
     */
    public static function buildContext($url, $headers=array()) {
        $info = parse_url($url);
        switch($info['scheme']) {
            case 'https':
                $mode = 'ssl';
                stream_context_set_default( [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                break;
            default:
                $mode = $info['scheme'];
        }
        $opts = array(
            $mode => array(
            'request_fulluri'=>true,
            )
        );
        if(isset($info['user'])) {
            $headers['Authorization'] = 'Basic '.base64_encode($info['user'].':'.$info['pass']);
        }
        $method = 'GET';
        if(isset($headers['method'])) {
            $method = $headers['method'];
            unset($opts['method']);
        }
        $opts[$mode]['method'] = strtoupper($method);
        if($opts[$mode]['method']=='POST') {
            if(!isset($headers['data'])) $headers['data'] = array();
            if(is_array($headers['data']))$headers['data'] = http_build_query($headers['data']);
            $headers['Content-type'] = 'application/x-www-form-urlencoded';
            $headers['Content-Length'] = strlen($headers['data']);
            $opts[$mode]['content'] = $headers['data'];
            unset($headers['data']);
        }
        if(isset($headers['append'])) {
            foreach($headers['append'] as $k => $v) {
                $opts[$mode][$k] = $v;
            }
            unset($headers['append']);
        }
        $header = '';
        foreach($headers as $k => $v) {
            $header .= $k . ': '. $v ."\r\n";
        }
        $header .= 'Framework: PHP MyStep';
        $opts[$mode]['header'] = $header;
        return stream_context_create($opts);
    }

    /**
     * 获取网站根的绝对路径
     * @param bool $mode
     * @return mixed
     */
    public static function rootPath($mode = false) {
        $path_root = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT']);
        $path_root = str_replace('//', '/', $path_root.'/');
        if($mode) $path_root = str_replace('/', DIRECTORY_SEPARATOR, $path_root);
        return $path_root;
    }

    /**
     * 获取文件真实绝对路径或相对于根的路径
     * @param $file
     * @param bool $relative
     * @param bool $mode
     * @return bool|mixed|null|string|string[]
     */
    public static function realPath($file, $relative = false, $mode = false) {
        $is_dir = (array_search(substr($file, -1), array('\\', '/'))!==false);
        $root = self::rootPath();
        if($realpath = realpath($file)) {
            $realpath = str_replace(DIRECTORY_SEPARATOR, '/', $realpath);
            if(is_dir($realpath)) $realpath .= '/';
            if(preg_match('/^(\/|[a-z]\:\/)/i', $realpath) && strpos($realpath, $root)!==0) {
                $realpath = preg_replace('/^(\/|[a-z]\:\/)/i', $root, $realpath);
            }
        } else {
            $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
            $drv_chk = false;
            if(preg_match('/^[a-z]\:/i', $file)) $drv_chk = true;
            $file = preg_replace('/[\\"\:\|\*\?<>]/', '', $file);
            if($drv_chk) $file = preg_replace('/^(\w)(.+)$/', '\1:\2', $file);
            if(substr($file, 0, 1)=='/' || $drv_chk) {
                if(strpos($file, $root)===0) {
                    $realpath = $file;
                } else {
                    $realpath = $root.$file;
                }
            } else {
                $info = debug_backtrace();
                $realpath = dirname(end($info)['file']).'/'.$file;
            }
            $realpath = str_replace(DIRECTORY_SEPARATOR, '/', $realpath);
            $realpath = preg_replace('/\/+/', '/', $realpath);
            $realpath = str_replace('/./', '/', $realpath);
            $realpath = preg_replace('/[\.]{2,}/', '..', $realpath);
            if(!preg_match('/^'.preg_quote($root, '/').'/', $realpath)) $realpath = $root.$realpath;
            while(preg_match('/\/[^\/]+\/\.\.\//', $realpath)) {
                $realpath = preg_replace('/\/[^\/]+\/\.\.\//', '/', $realpath);
            }
            $realpath = preg_replace('/\/\.\.\//', '', $realpath);
            $realpath = str_replace('//', '/', $realpath);
        }
        if($is_dir && substr($realpath, -1)!='/') $realpath .= '/';
        if($relative) $realpath = str_replace($root, '/', $realpath);
        if($mode) $realpath = str_replace('/', DIRECTORY_SEPARATOR, $realpath);
        $realpath = preg_replace('#^/(\w\:)#', '\1', $realpath);
        return $realpath;
    }

    /**
     * 建立目录，带递归和容错
     * @param $dir
     * @return bool
     */
    public static function mkdir($dir) {
        $dir = self::realPath($dir);
        if(is_dir($dir)) return true;
        $flag = true;
        $oldumask=umask(0);
        if(!file_exists($dir) && @mkdir($dir, 0777, true)===false) {
            $dir_list = explode('/', $dir);
            if($dir_list[0]=='') $dir_list[0]='/';
            $cur_dir = '';
            for($i=0,$m=count($dir_list); $i<$m; $i++) {
                if(empty($dir_list[$i])) continue;
                $cur_dir .= $dir_list[$i].'/';
                if(!is_dir($cur_dir)) {
                    if(!mkdir($cur_dir, 0777)) {
                        $flag = false;
                        break;
                    }
                }
            }
        }
        umask($oldumask);
        return $flag;
    }

    /**
     * 获取文件类型
     * @param string $file
     * @param bool $use_finfo
     * @return bool|mixed|string
     */
    public static function getMime($file='', $use_finfo = false) {
        if(!is_file($file)) return false;
        if(is_dir($file)) return 'DIR';
        $ext = 'application/octet-stream';
        if($use_finfo && function_exists('finfo_open')) {
            $file = self::realPath($file);
            if(is_file($file)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $ext = finfo_file($finfo, $file);
            }
        } else {
            $mime_types = array(
                'css'    => 'text/css',
                'htm'    => 'text/html',
                'html'    => 'text/html',
                'txt'    => 'text/plain',
                'rtf'    => 'text/richtext',
                'xml'    => 'text/xml',
                'wml'    => 'text/wml',
                'uu'    => 'text/x-uuencode',
                'uue'    => 'text/x-uuencode',
                'gif'    => 'image/gif',
                'jpg'    => 'image/jpeg',
                'jpeg'    => 'image/jpeg',
                'jpe'    => 'image/jpeg',
                'bmp'    => 'image/bmp',
                'png'    => 'image/png',
                'tif'    => 'image/tiff',
                'tiff'    => 'image/tiff',
                'pict'    => 'image/x-pict',
                'pic'    => 'image/x-pict',
                'pct'    => 'image/x-pict',
                'psd'    => 'image/x-photoshop',
                'wbmp'    => 'image/vnd.wap.wbmp',
                'mid'    => 'audio/midi',
                'wav'    => 'audio/wav',
                'mp3'    => 'audio/mpeg',
                'mp2'    => 'audio/mpeg',
                'wma'    => 'audio/x-ms-wma',
                'ram'    => 'audio/x-pn-realaudio',
                'avi'    => 'video/x-msvideo',
                'asx'    => 'video/x-ms-asf',
                'asf'    => 'video/x-ms-asf',
                'mpeg'    => 'video/mpeg',
                'mpg'    => 'video/mpeg',
                'qt'    => 'video/quicktime',
                'mov'    => 'video/quicktime',
                'rm'    => 'application/vnd.rn-realmedia',
                'swf'    => 'application/x-shockwave-flash',
                'js'    => 'application/x-javascript',
                'pdf'    => 'application/pdf',
                'ps'    => 'application/postscript',
                'eps'    => 'application/postscript',
                'ai'    => 'application/postscript',
                'wmf'    => 'application/x-msmetafile',
                'lha'    => 'application/x-lha',
                'lzh'    => 'application/x-lha',
                'z'    => 'application/x-compress',
                'gtar'    => 'application/x-gtar',
                'gz'    => 'application/x-gzip',
                'gzip'    => 'application/x-gzip',
                'tgz'    => 'application/x-gzip',
                'tar'    => 'application/x-tar',
                'bz2'    => 'application/bzip2',
                'zip'    => 'application/zip',
                'arj'    => 'application/x-arj',
                'rar'    => 'application/x-rar-compressed',
                'hqx'    => 'application/mac-binhex40',
                'sit'    => 'application/x-stuffit',
                'bin'    => 'application/x-macbinary',
                'latex'    => 'application/x-latex',
                'ltx'    => 'application/x-latex',
                'tcl'    => 'application/x-tcl',
                'pgp'    => 'application/pgp',
                'asc'    => 'application/pgp',
                'exe'    => 'application/x-msdownload',
                'doc'    => 'application/msword',
                'docx'    => 'application/msword',
                'xls'    => 'application/vnd.ms-excel',
                'ppt'    => 'application/vnd.ms-powerpoint',
                'mdb'    => 'application/x-msaccess',
                'wri'    => 'application/x-mswrite',
                'svg'   => 'image/svg',
            );
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if(isset($mime_types[$ext])) {
                $ext = $mime_types[$ext];
            }
        }
        return $ext;
    }

    /**
     * 获取文件属性
     * @param $file
     * @param bool $show_txt
     * @return bool|string
     */
    public static function getAttrib($file, $show_txt = false) {
        if(!file_exists($file)) {
            trigger_error("Cannot Find File{$file} !");
            return false;
        }
        $attr = substr(DecOct(fileperms($file)), -3);
        if($show_txt) {
            $att_list = array('---', '--x', '-w-', '-wx', 'r--', 'r-x', 'rw-', 'rwx');
            $the_attrib = '';
            for($i=0; $i<3; $i++) {
                $this_char=(int)substr($attr, $i, 1);
                $the_attrib .= $att_list[$this_char];
            }
            $attr = $the_attrib;
        }
        return $attr;
    }

    /**
     * 设置文件属性
     * @param $file
     * @param $attrib
     */
    public static function setAttrib($file, $attrib) {
        if(file_exists($file)) {
            chmod($file, $attrib) or trigger_error("Operation Failed in Setting Attrib of{$file} , Please Check Your Power!");
        } else {
            trigger_error("Cannot Find File{$file} !");
        }
    }

    /**
     * 获取远程连接头信息
     * @param $url
     * @param bool $mode
     * @return array|bool
     */
    public static function getHeader($url, $mode = true) {
        stream_context_set_default( [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        if($headers = get_headers($url, $mode)) {
            return array_change_key_case($headers, CASE_LOWER);
        }
        return false;
    }

    /**
     * 获取文件大小
     * @param $file
     * @param bool $format
     * @return int|string
     */
    public static function getSize($file, $format = true) {
        $size = 0;
        if(is_file($file)) {
            $size = filesize($file);
        } elseif(is_dir($file)) {
            $files = self::find('*', $file, true, self::FILE);
            foreach($files as $f) {
                $size += self::getSize($f, false);
            }
        }
        if($format) $size = self::formatSize($size);
        return $size;
    }

    /**
     * 容量格式化
     * @param $size
     * @param int $precision
     * @return string
     */
    public static function formatSize($size, $precision=2) {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $idx = 0;
        $size = intval($size);
        while ($size >= 1024) {
            $size /= 1024;
            $idx++;
        }
        return round($size, $precision).$unit[$idx];
    }

    /**
     * 获取容量字节数
     * @param $size
     * @return float|int
     */
    public static function getByte($size) {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        if(preg_match('/(\d+)\s*([a-zA-Z]+)/', $size, $match)) {
            $match[2] = strtoupper($match[2]);
            if(strlen($match[2])==1 && $match[2]!='B') $match[2] .= 'B';
            if(false === ($idx = array_search($match[2], $unit))) return 0;
            return $match[1] * pow(1024, $idx);
        }
        return 0;
    }

    /**
     * 复制文件，可为多重目录
     * @param $source
     * @param $dest
     * @param bool $overwrite
     * @return bool
     */
    public static function copy($source, $dest, $overwrite = false) {
        $source = self::realPath($source);
        if(!file_exists($source)) {
            trigger_error('Copy source is missing ! ('.$source.')');
            return false;
        }
        $dest = self::realPath($dest);
        $source_info = pathinfo($source);
        $dest_info = pathinfo($dest);
        if(!isset($dest_info['extension'])) {
            self::mkdir($dest);
            $dest .= '/';
        } else {
            self::mkdir(dirname($dest));
        }
        if(is_file($source)) {
            if(substr($dest, -1)=='/') {
                $dest .= $source_info['basename'];
            }
            if(is_file($dest)) {
                if($overwrite) {
                    self::del($dest);
                    copy($source, $dest);
                    return true;
                } else {
                    trigger_error('The same-name-file exists at the destination directory! ('.$dest.')');
                    return false;
                }
            } else {
                copy($source, $dest);
            }
        } else {
            $d=dir($source);
            while(false !== ($file=$d->read())) {
                if($file=='.' || $file=='..') continue;
                if(is_dir($source.'/'.$file)) {
                    self::copy($source.'/'.$file.'/', $dest.'/'.basename($source).'/', $overwrite);
                } else {
                    $dest_file = $dest.'/'.basename($source).'/'.$file;
                    if(is_file($dest_file) && !$overwrite) {
                        rename($dest_file, $dest_file.'.'.time());
                    }
                    self::mkdir(dirname($dest_file));
                    copy($source.'/'.$file, $dest_file);
                }
            }
            $d->close();
        }
        return true;
    }

    /**
     * 删除文件，可为多重目录
     * @param $file
     * @return bool
     */
    public static function del($file) {
        $flag = false;
        if(is_dir($file)) {
            $d=dir($file);
            while(false !== ($f=$d->read())) {
                if($f=='.' || $f=='..') continue;
                $cur_file = $file.'/'.$f;
                if(is_dir($cur_file)) {
                    self::del($cur_file);
                } else {
                    @unlink($cur_file);
                }
            }
            $d->close();
            $flag = @rmdir($file);
        }elseif(is_file($file)) {
            $flag = @unlink($file);
        }
        return $flag;
    }

    /**
     * 重命名文件或目录
     * @param $file
     * @param $newname
     */
    public static function rename($file, $newname) {
        $file = self::realPath($file);
        $newname = self::realPath($newname);
        if(file_exists($file)) {
            if(is_dir($newname)) {
                rename($file, $newname.'/'.basename($file)) or trigger_error('Operation Failed in Renaming '.$file.' !');
            } elseif(is_file($newname)) {
                trigger_error('File '.$newname.' already exist !');
                return false;
            } else {
                self::mkdir(dirname($newname));
                rename($file, $newname) or trigger_error('Operation Failed in Renaming '.$file.' !');
            }
        } else {
            trigger_error('Cannot Find File '.$file.' !');
        }
    }

    /**
     * 移动文件或目录
     * @param $file
     * @param $newname
     */
    public static function move($file, $newname) {
        self::rename($file, $newname);
    }

    /**
     * 保存实例文件
     * @param string $content
     * @return bool|int|resource
     */
    public function save($content = '') {
        if($this->remote) return false;
        if(empty($content)) $content = $this->get();
        return self::saveFile($this->file, $content);
    }

    /**
     * 保存文件
     * @param $file
     * @param string $content
     * @param string $mode
     * @return bool|int|resource
     */
    public static function saveFile($file, $content='', $mode='wb') {
        $file = self::realPath($file);
        self::mkdir(dirname($file));
        if(strpos($mode, 'w')===0) return file_put_contents($file, $content, LOCK_EX);
        if($fp = fopen($file, $mode)) {
            if(flock($fp, LOCK_EX)) {
                fwrite($fp, $content);
                flock($fp, LOCK_UN);
            } else {
                fwrite($fp, $content);
            }
            fclose($fp);
            @chmod($file, 0777);
        }
        return $fp;
    }

    /**
     * 获取文件（本地或远程）
     * @return bool|string
     */
    public function get() {
        if(empty($this->content) && (false === ($this->content=file_get_contents($this->file, false, $this->context)))) {
            if($this->remote) {
                $this->content = self::getRemote($this->file);
            } else {
                $this->content = self::getLocal($this->file);
            }
        }
        return $this->content;
    }

    /**
     * 获取本地文件（可为部分）
     * @param $file
     * @param int $length
     * @param int $offset
     * @return string
     */
    public static function getLocal($file, $length=0, $offset=0) {
        $file = self::realPath($file);
        if(!is_file($file)) return '';
        if($length==0 && $offset==0) {
            $data = @file_get_contents($file) or '';
        } else {
            if($length==0) $length = 8192;
            $data = '';
            if($fp = @fopen($file, 'rb')) {
                fseek($fp, $offset);
                $data = fread($fp, $length);
                fclose($fp);
            }
        }
        return self::removeBom($data);
    }

    /**
     * 获取远程文件
     * @param $url
     * @param array $header
     * @param string $data
     * @param string $method
     * @param int $timeout
     * @return false|string
     */
    public static function getRemote($url, $header=array(), $data='', $method='GET', $timeout=10) {
        if(function_exists('curl_init')) return self::getRemote_curl($url, $header, $data);
        $separator = "\r\n";
        $errno = '';
        $errmsg = '';
        $scheme = '';
        $host = '';
        $path = '';
        $query = '';
        extract(parse_url($url), EXTR_OVERWRITE);
        if($scheme=='https') {
            $transports = 'ssl://';
            if(!isset($port)) $port = '443';
        } else {
            $transports = 'tcp://';
            if(!isset($port)) $port = '80';
        }
        if(isset($user)) $header['Authorization'] = 'Basic ' . base64_encode($user . (isset($pass)?':'.$pass:''));
        if(!empty($data) && is_array($data)) $data = http_build_query($data);
        if($method!='POST') {
            $method='GET';
            if(strlen($data)>0 && !empty($query)) $query .= '&';
            $query .= $data;
        } else {
            $header['Content-Type'] = 'application/x-www-form-urlencoded';
            if(strlen($data)>0) $header['Content-Length'] = strlen($data);
        }
        if(empty($path)) $path = '/';
        if(!empty($query)) $path .= '?'.$query;

        if(!isset($header['Referer'])) $header['Referer'] = $scheme.'://'.$host;
        if(!isset($header['User-Agent'])) $header['User-Agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.204 Safari/534.16';
        if(isset($header['fakeIP'])) {
            $header['HTTP_X_FORWARDED_FOR'] = $header['fakeIP'];
            $header['HTTP_CLIENT_IP'] = $header['fakeIP'];
            unset($header['fakeIP']);
        }

        $output = sprintf('%s %s HTTP/1.1'.$separator, $method, $path);
        $output .= sprintf('Host: %s'.$separator, $host);
        $output .= 'Accept: */*'.$separator;
        //$output .= 'Accept-Encoding: gzip,deflate'.$separator;
        foreach($header as $key => $value) {
            $output .= $key.':'.$value.$separator;
        }
        $output .= 'Cache-Control:no-cache'.$separator;
        $output .= 'Connection: Close'.$separator;
        $output .= $separator;
        if(!empty($data)) $output .= $data.$separator;

        stream_context_set_default( [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        if(false === ($fp = @fsockopen($transports.$host, $port, $errno, $errmsg, $timeout))) {
            if($errno==0 && empty($errmsg)) $errmsg = 'Socket initialize failed!';
            trigger_error('Error occurs when remote file getting: '.$errno.' - '.$errmsg.'('.$url.')');
            return false;
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);

        fputs($fp, $output);
        $status = stream_get_meta_data($fp);
        if(isset($status['timeout'])) return false;

        $response = '';
        while(!feof($fp)) {
            $response .= stream_get_contents($fp, 1024);
        }
        $response = preg_split('#\r\n\r\n|\n\n|\r\r#', $response, 2);
        //$response = preg_split('#\r\n\r\n|\n\n|\r\r#', stream_get_contents($fp), 2);
        $content = $response[1];
        $content = preg_replace('#^[\da-f]{1,4}[\r\n]+#', '', $content);
        $content = preg_replace('#[\r\n]+0[\r\n]+$#', '', $content);
        $content = preg_replace('#[\r\n]+[\da-f]{1,3}[\r\n]+#', '', $content);
        fclose($fp);
        return self::removeBom($content);
    }

    /**
     * 通过curl类获取远程文件
     * @param $url
     * @param array $header
     * @param string $data
     * @param array $return_header
     * @return bool|string
     */
    public static function getRemote_curl($url, $header=array(), $data='', &$return_header=array()) {
        if(!function_exists('curl_init')) return self::getRemote($url, $header, $data, (empty($data)?'POST':'GET'));
        $info = parse_url($url);
        $curl = curl_init();
        if(!is_object($curl) && !is_resource($curl)) return self::getRemote($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        if($info['scheme']=='https') {
            curl_setopt($curl, CURLOPT_SSLVERSION, 4);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if(!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0)');
        }
        if(!empty($data)) {
            if(is_array($data)) {
                $data = http_build_query($data, '', '&');
            }
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_HEADER, false);
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        if(curl_getinfo($curl, CURLINFO_HTTP_CODE) != '200' || curl_errno($curl)) {
            //trigger_error('Error occurs when remote file getting'.curl_error($curl), E_USER_ERROR);
            $result = '';
        } else {
            $return_header = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        }
        curl_close($curl);
        unset($curl);
        return self::removeBom($result);
    }

    /**
     * 输出内容
     * @param $content
     */
    public static function show($content) {
        if(count(ob_list_handlers())>0 && ob_get_length()!==false) ob_clean();
        file_put_contents('php://output', $content);
        //echo $content;
        exit;
    }

    /**
     * 文件搜索（支持通配符）
     * @param string $filter
     * @param string $dir
     * @param bool $recursive
     * @param int $mode
     * @return array|bool
     */
    public static function find($filter='', $dir='./', $recursive=false, $mode = 3) {
        $dir = self::realPath($dir);
        if(!is_dir($dir)) return false;
        $result = array();
        if(is_array($filter)) {
            foreach($filter as $f) {
                $result = array_merge($result, self::find($f, $dir, $recursive, $mode));
            }
        } else {
            if(empty($filter)) {
                $filter = '*';
            } elseif(preg_match('/^\w+$/', $filter)) {
                $filter = '*'.$filter.'*';
            }
            foreach (glob(preg_replace('/(\*|\?|\[)/', '[$1]', $dir).$filter) as $name) {
                if(is_dir($name) && $mode&1) {
                    array_push($result, $name.'/');
                } elseif(is_file($name) && $mode&2) {
                    array_push($result, $name);
                }
            }
            if($recursive) {
                foreach (glob($dir.'/*', GLOB_ONLYDIR) as $name) {
                    $result = array_merge($result, self::find($filter, $name, $recursive, $mode));
                }
            }
        }
        return $result;
    }

    /**
     * 解压数据
     * @param $data
     * @return bool|string
     */
    public static function gzdecode($data) {
        if(mb_strpos($data, '\x1f\x8b\x08', 'US-ASCII') === false || @gzuncompress($data) === FALSE) return $data;
        return function_exists('gzdecode') ? gzdecode($data) : file_get_contents('compress.zlib://data:who/cares;base64, '. base64_encode($data));
    }

    /**
     * 获取远程文件
     * @param $remote_file
     * @param string $local_file
     */
    public static function getRemoteFile($remote_file, $local_file='./') {
        $local_file = self::realPath($local_file);
        if(substr($local_file, -1)=='/') $local_file .= basename($remote_file);
        if($content = self::getRemote($remote_file)) {
            self::saveFile($local_file, $content);
        }
    }

    /**
     * 判断是否存在子目录
     * @param $dir
     * @param bool $only_dir
     * @return bool
     */
    public static function judgeChild($dir, $only_dir = true) {
        $mydir = dir($dir);
        if(!$mydir) return false;
        while(($file = $mydir->read()) !== false) {
            if($file!='.' && $file!='..') {
                if($only_dir) {
                    if(is_dir($dir.'/'.$file)) return true;
                } else {
                    return true;
                }
            }
        }
        $mydir->close();
        return false;
    }

    /**
     * 获取文件树
     * @param string $dir
     * @param bool $recursion
     * @return array
     */
    public static function getTree($dir='./', $recursion = false) {
        $tree = array();
        $mydir = dir($dir);
        if(!$mydir) return array();
        while(($file = $mydir->read()) !== false) {
            if($file=='.' || $file=='..') continue;
            $theFile = $dir.'/'.$file;
            $tree[$file]['name'] = $file;
            if(is_dir($theFile)) {
                if($recursion) $tree[$file]['sub'] = self::getTree($dir.'/'.$file, true);
                $tree[$file]['size'] = '---';
            } else {
                $tree[$file]['size'] = self::getSize($theFile);
            }
            $tree[$file]['attr'] = self::getAttrib($theFile, 1);
            $tree[$file]['time'] = date('Y/m/d H:i:s', filemtime($theFile));
        }
        $mydir->close();
        return $tree;
    }

    /**
     * 去除UTF-8文件头
     * @param $str
     * @return string
     */
    public static function removeBom($str) {
        //return preg_replace('/^\xEF\xBB\xBF/', '', $str);
        return ltrim((STRING)$str, "\XEF\XBB\XBF");
    }

    /**
     * 检测某文件或目录是否可写
     * @param $file
     * @return bool
     */
    public static function rewritable($file) {
        $flag = false;
        if(is_file($file)) {
            $flag = is_writable($file);
        } else {
            if(is_dir($file)) {
                $dir = $file;
            } else {
                $dir = dirname($file);
                $n = 10;
                while(!is_dir($dir) && --$n>0) {
                    $dir = dirname($dir);
                }
            }
            $dir = realpath($dir);
            $tmpfname = $dir.'/'.basename(tempnam($dir, 'chk_'));
            if($fp = @fopen($tmpfname, "w")) {
                $flag = true;
                fclose($fp);
                unlink($tmpfname);
            }
        }
        return $flag;
    }
}