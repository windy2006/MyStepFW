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
 * 文件：lib/myFile.class.php
 * 功能说明：文件目录、路径与远程资源工具
 *   提供本地读写/移动/遍历、路径边界校验、HTTP读取、TLS配置及受限公开图片导入。
 *
 * 方法目录：
 *   public myFile::__construct($file, $headers=array()) — 构造函数
 *   public myFile::__get($para) — 内部变量读取
 *   public myFile::__toString() — 字符串显示
 *   public static myFile::buildContext($url, $headers=array()) — 构建远程连接配置
 *   public static myFile::rootPath($mode = false) — 获取网站根的绝对路径
 *   public static myFile::resolveWithin($root, $relative) — 定位受信根目录内已存在的文件，拒绝路径穿越和符号链接越界
 *   public static myFile::realPath($file, $relative = false, $mode = false) — 获取文件真实绝对路径或相对于根的路径
 *   public static myFile::mkdir($dir) — 建立目录，带递归和容错
 *   public static myFile::getMime($file='', $use_finfo = false) — 获取文件类型
 *   public static myFile::getAttrib($file, $show_txt = false) — 获取文件属性
 *   public static myFile::setAttrib($file, $attrib) — 设置文件属性
 *   public static myFile::getHeader($url, $mode = true) — 获取远程连接头信息
 *   public static myFile::getSize($file, $format = true) — 获取文件大小
 *   public static myFile::formatSize($size, $precision=2) — 容量格式化
 *   public static myFile::getByte($size) — 获取容量字节数
 *   public static myFile::copy($source, $dest, $overwrite = false) — 复制文件，可为多重目录
 *   public static myFile::del($file) — 删除文件，可为多重目录
 *   public static myFile::rename($file, $newname) — 重命名文件或目录
 *   public static myFile::move($file, $newname) — 移动文件或目录
 *   public myFile::save($content = '') — 保存实例文件
 *   public static myFile::saveFile($file, $content='', $mode='wb') — 保存文件
 *   public myFile::get() — 获取文件（本地或远程）
 *   public static myFile::getLocal($file, $length=0, $offset=0) — 获取本地文件（可为部分）
 *   public static myFile::setTLSOptions(array $options) — 设置可信CA文件/目录，保留证书和主机名验证
 *   protected static myFile::tlsOptions() — 返回强制启用证书及主机校验的流TLS选项
 *   public static myFile::getRemote($url, $header=array(), $data='', $method='GET', $timeout=10) — 按可用扩展选择cURL或流方式读取HTTP资源
 *   public static myFile::getRemote_stream($url, $header=array(), $data='', $method='GET', $timeout=10) — 使用验证TLS的流读取HTTP响应并去除UTF-8 BOM
 *   protected static myFile::remoteRequest($url, $data, $method) — 规范HTTP方法及表单请求体，GET数据并入查询串
 *   public static myFile::getRemote_curl($url, $header=array(), $data='', &$return_header=array(), $method=null, $timeout=30) — 使用cURL读取HTTP正文并输出实际请求头；无cURL时回退流读取
 *   public static myFile::show($content) — 输出内容
 *   public static myFile::find($filter='', $dir='./', $recursive=false, $mode = 3) — 文件搜索（支持通配符）
 *   public static myFile::gzdecode($data) — 解压数据
 *   public static myFile::getRemoteFile($remote_file, $local_file='./') — 获取远程文件
 *   public static myFile::judgeChild($dir, $only_dir = true) — 判断是否存在子目录
 *   public static myFile::getTree($dir='./', $recursion = false) — 获取文件树
 *   public static myFile::removeBom($str) — 去除UTF-8文件头
 *   public static myFile::rewritable($file) — 检测某文件或目录是否可写
 *   public static myFile::extractionPath($root, $name) — 验证归档成员并生成根目录内的发布路径，拒绝符号链接
 *   public static myFile::getRemotePublic($url, $max_bytes = 10485760, $timeout = 10) — 固定到经检查的公网地址读取有限大小内容，不跟随重定向
 *   protected static myFile::publicAddresses($host) — 解析主机的IPv4/IPv6地址并去除重复值
 *   protected static myFile::publicAddress($ip) — 判断单个IP是否可用作受限公网资源读取目标
 *   public static myFile::importPublicImage($url, $directory) — 下载受限公网位图，按实际图像格式确定后缀并保存
 *
 * 使用说明：
 *   1. 本地操作优先明确传入受信路径；上传/解包路径使用边界检查入口。
 *   2. 普通远程读取与受限公开资源读取用途不同；后者执行地址和体积约束。
 *   3. getLocal使用字节长度/偏移，find/getTree的递归和过滤规则见方法说明。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：提供本地读写/移动/遍历、路径边界校验、HTTP读取、TLS配置及受限公开图片导入。
 *
 * 使用说明：
 *   1. 本地操作优先明确传入受信路径；上传/解包路径使用边界检查入口。
 *   2. 普通远程读取与受限公开资源读取用途不同；后者执行地址和体积约束。
 *   3. getLocal使用字节长度/偏移，find/getTree的递归和过滤规则见方法说明。
 *
 * 使用示例：
 *     $text = myFile::getLocal($path);
 *     $ok = myFile::saveFile($path, $text) !== false;
 *     $file = myFile::resolveWithin($publicRoot, $relativeName);
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myFile.php 框架现有演示，仅作为文档引用，不在检查时执行
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
     * 功能说明：构造函数
     *
     * @param string $file 本地文件路径或远程URL；远程资源按协议识别
     * @param array $headers HTTP头映射/列表；流上下文还支持method、data和append选项（默认：array()）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct($file, $headers=array()) {

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
                $this->info['size'] = filesize($file);
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
     * 功能说明：内部变量读取
     *
     * @param string $para 已保存的文件信息或属性名称
     * @return mixed 文件属性/信息映射中的原值；键不存在时为null
     */
    public function __get($para) {
        if(array_key_exists($para, $this->info) ) {
            return $this->info[$para];
        } else {
            return $this->__get_base($para);
        }
    }

    /**
     * 功能说明：字符串显示
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前文件内容文本；读取行为与get相同
     */
    public function __toString() {
        return (string)$this->get();
    }

    /**
     * 功能说明：构建远程连接配置
     *
     *
     * 资源所有权：返回的是流上下文，不是已打开的网络连接；无需把它作为文件流传给fclose。
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array $headers HTTP头映射/列表；流上下文还支持method、data和append选项（默认：array()）
     * @return resource 新建的stream-context资源，可交给支持context的流函数
     * @throws InvalidArgumentException 请求头名称或值包含CR/LF
     */
    public static function buildContext($url, $headers=array()) {
        $info = parse_url($url);
        $mode = ($info['scheme'] ?? '') === 'https' ? 'http' : ($info['scheme'] ?? 'http');
        $opts = [$mode => ['request_fulluri'=>false, 'timeout'=>10, 'follow_location'=>0], 'ssl'=>self::tlsOptions()];
        if(isset($info['user'])) {
            $headers['Authorization'] = 'Basic '.base64_encode($info['user'].':'.($info['pass'] ?? ''));
        }
        $method = 'GET';
        if(isset($headers['method'])) {
            $method = $headers['method'];
            unset($headers['method']);
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
            if(preg_match('/[\r\n]/', (string)$k.(string)$v)) throw new InvalidArgumentException('Invalid HTTP header');
            $header .= (is_int($k) ? $v : $k.': '.$v)."\r\n";
        }
        $header .= 'Framework: PHP MyStep';
        $opts[$mode]['header'] = $header;
        return stream_context_create($opts);
    }

    /**
     * 功能说明：获取网站根的绝对路径
     *
     * @param bool $mode true使用当前操作系统分隔符，false统一使用/（默认：false）
     * @return string 网站根目录的绝对路径，使用所选分隔符
     */
    public static function rootPath($mode = false) {
        $path_root = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT'] ?? getcwd());
        $path_root = str_replace('//', '/', $path_root.'/');
        if($mode) $path_root = str_replace('/', DIRECTORY_SEPARATOR, $path_root);
        return $path_root;
    }

    /**
     * 功能说明：定位受信根目录内已存在的文件，拒绝路径穿越和符号链接越界
     *
     * @param string $root 操作限制在其中的受信根目录
     * @param string $relative 相对于受信根目录的待解析路径
     * @return string|false 根目录内真实文件路径；不存在、越界或非法输入为false
     */
    public static function resolveWithin($root, $relative) {
        if(!is_string($relative)) return false;
        $relative = rawurldecode($relative);
        if(strpos($relative, "\0") !== false || strpos($relative, '\\') !== false) return false;
        foreach(explode('/', $relative) as $segment) {
            if($segment === '.' || $segment === '..' || (isset($segment[0]) && $segment[0] === '.')) return false;
        }
        $root = realpath($root);
        if($root === false) return false;
        $file = realpath($root.'/'.ltrim($relative, '/'));
        return $file !== false && is_file($file) && strpos($file, rtrim($root, '/').'/') === 0 ? $file : false;
    }

    /**
     * 功能说明：获取文件真实绝对路径或相对于根的路径
     *
     * 声明ROOT时相对路径固定从网站根解析，与工作目录和调用文件无关；独立使用类库时保留历史规则。本入口不是访问权限检查。读取公开文件或解包时使用resolveWithin/extractionPath。
     *
     * @param string $file 目标文件路径
     * @param bool $relative true返回相对于站点根的路径，false返回完整路径（默认：false）
     * @param bool $mode true使用操作系统分隔符，false使用统一/分隔符（默认：false）
     * @return string 规范化后的绝对路径或站点相对路径；不保证文件存在
     * @see myFile::resolveWithin() 已存在公开文件的根目录边界检查
     * @see myFile::extractionPath() 归档成员路径校验
     */
    public static function realPath($file, $relative = false, $mode = false) {
        if(defined('ROOT')) {
            require_once __DIR__.'/myPath.class.php';
            $path = myPath::absolute($file);
            $real = realpath($path);
            if($real !== false) $path = str_replace('\\', '/', $real);
            if((substr($file, -1) === '/' || substr($file, -1) === '\\' || is_dir($path)) && substr($path, -1) !== '/') $path .= '/';
            // 保留历史relative=true返回/前缀的约定；配置保存使用myPath::relative。
            if($relative) $path = '/'.myPath::relative($path);
            return $mode ? str_replace('/', DIRECTORY_SEPARATOR, $path) : $path;
        }
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
        if($relative && strpos($realpath, $root) === 0) $realpath = '/'.substr($realpath, strlen($root));
        if($mode) $realpath = str_replace('/', DIRECTORY_SEPARATOR, $realpath);
        $realpath = preg_replace('#^/(\w\:)#', '\1', $realpath);
        return $realpath;
    }

    /**
     * 功能说明：建立目录，带递归和容错
     *
     * @param string $dir 目标目录
     * @return bool 目录已存在或递归创建成功为true，创建失败为false
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
     * 功能说明：获取文件类型
     *
     *
     * 兼容行为：未知扩展名可能直接作为返回文本，而非application/octet-stream；此入口不能仅靠返回值证明文件真实内容格式。
     *
     * @param string $file 目标文件路径（默认：''）
     * @param bool $use_finfo 是否优先使用fileinfo检测实际MIME类型（默认：false）
     * @return string|false 已知扩展名返回MIME文本；未知扩展名沿用原扩展名；不是普通文件或finfo失败为false
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
     * 功能说明：获取文件属性
     *
     * @param string $file 目标文件路径
     * @param bool $show_txt true返回rwx文本权限，false返回三位八进制权限文本（默认：false）
     * @return string|false 三位八进制权限文本或九位rwx文本；文件不存在为false
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
     * 功能说明：设置文件属性
     *
     * @param string $file 目标文件路径
     * @param int $attrib chmod使用的权限位，例如0644
     * @return void 无返回值。
     */
    public static function setAttrib($file, $attrib) {
        if(file_exists($file)) {
            chmod($file, $attrib) or trigger_error("Operation Failed in Setting Attrib of{$file} , Please Check Your Power!");
        } else {
            trigger_error("Cannot Find File{$file} !");
        }
    }

    /**
     * 功能说明：获取远程连接头信息
     *
     * @param string $url 目标URL或需要处理的链接
     * @param bool $mode true按响应头名分组，false返回原始头列表（默认：true）
     * @return array|false HEAD响应头；mode为true按小写头名组织，false保留数字行列表；失败为false
     */
    public static function getHeader($url, $mode = true) {
        $context = self::buildContext($url, ['method'=>'HEAD', 'append'=>['timeout'=>10]]);
        if($headers = get_headers($url, $mode, $context)) {
            return array_change_key_case($headers, CASE_LOWER);
        }
        return false;
    }

    /**
     * 功能说明：获取文件大小
     *
     * @param string $file 目标文件路径
     * @param bool $format true格式化为KB/MB等文本，false保留字节计数（默认：true）
     * @return int|float|string|false format为false返回文件/递归目录字节数，为true返回容量文本；不存在为0或0B，filesize底层失败可为false
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
     * 功能说明：容量格式化
     *
     * @param int $size 字节数量
     * @param int $precision 格式化时保留的小数位数（默认：2）
     * @return string 含B/KB/MB等单位的容量文本，按precision保留小数
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
     * 功能说明：获取容量字节数
     *
     * @param int|float|string $size 原始字节数或含K/M/G等容量单位的文本
     * @return float|int 按1024进位换算的字节数；不符合数值/容量单位格式时返回0
     */
    public static function getByte($size) {
        if(!preg_match('/^\s*(\d+(?:\.\d+)?)\s*([KMGTPEZY]?)(?:I?B)?\s*$/iD', (string)$size, $match)) return 0;
        $power = strpos('BKMGTPEZY', strtoupper($match[2] ?: 'B'));
        return (float)$match[1] * pow(1024, $power);
    }

    /**
     * 功能说明：复制文件，可为多重目录
     *
     * @param string $source 源文件或源目录路径
     * @param string $dest 目标文件或目标目录路径
     * @param bool $overwrite 目标已存在时是否允许覆盖（默认：false）
     * @return bool 复制结果；目录递归失败时可能已有部分目标文件，不提供事务回滚
     */
    public static function copy($source, $dest, $overwrite = false) {
        if(is_link($source)) return false;
        $source = self::realPath($source);
        if(!file_exists($source)) {
            trigger_error('Copy source is missing ! ('.$source.')');
            return false;
        }
        $dest = self::realPath($dest);
        if(rtrim($dest, '/') === rtrim($source, '/') || (is_dir($source) && strpos($dest, rtrim($source, '/').'/') === 0)) return false;
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
                    return copy($source, $dest);
                } else {
                    trigger_error('The same-name-file exists at the destination directory! ('.$dest.')');
                    return false;
                }
            } else {
                return copy($source, $dest);
            }
        } else {
            $d=dir($source);
            while(false !== ($file=$d->read())) {
                if($file=='.' || $file=='..') continue;
                if(is_dir($source.'/'.$file)) {
                    if(!self::copy($source.'/'.$file.'/', $dest.'/'.basename($source).'/', $overwrite)) { $d->close(); return false; }
                } else {
                    $dest_file = $dest.'/'.basename($source).'/'.$file;
                    if(is_file($dest_file) && !$overwrite) {
                        rename($dest_file, $dest_file.'.'.time());
                    }
                    self::mkdir(dirname($dest_file));
                    if(is_link($source.'/'.$file) || !copy($source.'/'.$file, $dest_file)) { $d->close(); return false; }
                }
            }
            $d->close();
        }
        return true;
    }

    /**
     * 功能说明：删除文件，可为多重目录
     *
     * @param string $file 目标文件路径
     * @return bool 删除文件/目录的操作结果；递归删除不具备整体回滚能力
     */
    public static function del($file) {
        $flag = false;
        if(is_link($file)) return @unlink($file);
        if(is_dir($file)) {
            $d=dir($file);
            while(false !== ($f=$d->read())) {
                if($f=='.' || $f=='..') continue;
                $cur_file = $file.'/'.$f;
                if(is_dir($cur_file) && !is_link($cur_file)) {
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
     * 功能说明：重命名文件或目录
     *
     * @param string $file 目标文件路径
     * @param string $newname 新文件名或完整目标路径
     * @return bool 底层重命名成功为true，目标冲突或失败为false
     */
    public static function rename($file, $newname) {
        $file = self::realPath($file);
        $newname = self::realPath($newname);
        if(file_exists($file)) {
            if(is_dir($newname)) {
                return rename($file, $newname.'/'.basename($file));
            } elseif(is_file($newname)) {
                trigger_error('File '.$newname.' already exist !');
                return false;
            } else {
                self::mkdir(dirname($newname));
                return rename($file, $newname);
            }
        } else {
            trigger_error('Cannot Find File '.$file.' !');
        }
    }

    /**
     * 功能说明：移动文件或目录
     *
     * @param string $file 目标文件路径
     * @param string $newname 新文件名或完整目标路径
     * @return bool 与rename相同，表示移动是否成功
     */
    public static function move($file, $newname) {
        return self::rename($file, $newname);
    }

    /**
     * 功能说明：保存实例文件
     *
     * @param string $content 待处理或输出的内容字节串（默认：''）
     * @return int|false 成功写入的字节数，包括0；远程文件或写入失败为false
     */
    public function save($content = '') {
        if($this->remote) return false;
        if(func_num_args() === 0) $content = $this->get();
        return self::saveFile($this->file, $content);
    }

    /**
     * 功能说明：保存文件
     *
     *
     * 调用示例：
     *     $written = myFile::saveFile($file, '');
     *     if ($written === false) {
     *         // 保存失败；0字节也可能是成功
     *     }
     *
     * @param string $file 目标文件路径
     * @param string $content 待处理或输出的内容字节串（默认：''）
     * @param string $mode fopen写入模式，例如w/a，按调用方期望覆盖或追加（默认：'wb'）
     * @return int|false 成功写入的字节数，包括0；创建目录、加锁或写入失败为false
     */
    public static function saveFile($file, $content='', $mode='wb') {
        $file = self::realPath($file);
        if(!self::mkdir(dirname($file))) return false;
        if(strpos($mode, 'w') === 0) return file_put_contents($file, $content, LOCK_EX);
        $fp = fopen($file, $mode);
        if(!$fp) return false;
        try {
            if(!flock($fp, LOCK_EX)) return false;
            $written = fwrite($fp, $content);
            flock($fp, LOCK_UN);
            return $written;
        } finally { fclose($fp); }
    }

    /**
     * 功能说明：获取文件（本地或远程）
     *
     * 参数说明：无显式参数。
     *
     * @return string|false 当前文件正文；远程读取失败通常为空串，本地不可用按读取入口约定返回
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
     * 功能说明：获取本地文件（可为部分）
     *
     *
     * length=0且offset=0时读全文件；存在偏移且length=0时读取8192字节。长度/偏移采用字节，不是字符位置。
     *
     * 调用示例：
     *     $chunk = myFile::getLocal($file, 4096, 1024); // 从1024字节偏移读4096字节
     *
     * @param string $file 目标文件路径
     * @param int $length 非负读取字节数；length与offset均为0时读全文件；有偏移且length为0时读8192字节（默认：0）
     * @param int $offset 开始读取的字节偏移（默认：0）
     * @return string 去除UTF-8 BOM后的文件内容；不存在/读取失败时为空串，空正文也为空串
     * @phpstan-param non-negative-int $length
     * @phpstan-param non-negative-int $offset
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
     * 功能说明：设置可信CA文件/目录，保留证书和主机名验证
     *
     * @param array $options 仅允许cafile/capath，值须为可读路径；证书/主机校验始终开启
     * @return void 只更新静态TLS配置，不返回业务值
     * @throws InvalidArgumentException 配置键不是cafile/capath，或指定路径不可读
     */
    public static function setTLSOptions(array $options) {
        foreach($options as $key => $value) {
            if(!in_array($key, ['cafile', 'capath'], true) || !is_string($value) || !is_readable($value)) {
                throw new InvalidArgumentException('Only readable CA files/directories may be configured');
            }
        }
        self::$tls_options = $options;
    }

    protected static $tls_options = [];

    /**
     * 功能说明：返回强制启用证书及主机校验的流TLS选项
     *
     * 参数说明：无显式参数。
     *
     * @return array 用于流上下文的TLS选项，始终验证证书及主机名
     */
    protected static function tlsOptions() {
        return ['verify_peer'=>true, 'verify_peer_name'=>true] + self::$tls_options;
    }

    /**
     * 功能说明：按可用扩展选择cURL或流方式读取HTTP资源
     *
     * 仅HTTP/HTTPS，成功状态要求200；不跟随重定向。失败与合法空正文均为空串。
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array $header 附加HTTP请求头列表（默认：array()）
     * @param array|string $data 请求体；数组按当前实现编码为表单内容（默认：''）
     * @param string $method GET/POST等HTTP请求方法（默认：'GET'）
     * @param int|float $timeout 最长等待/执行时间，单位秒（默认：10）
     * @return string 响应正文，失败或空响应为兼容约定的空串
     * @throws InvalidArgumentException 请求方法不支持或请求头非法；传输失败仍按空串返回约定处理
     */
    public static function getRemote($url, $header=array(), $data='', $method='GET', $timeout=10) {
        if(function_exists('curl_init')) return self::getRemote_curl($url, $header, $data, $unused, $method, $timeout);
        return self::getRemote_stream($url, $header, $data, $method, $timeout);
    }

    /**
     * 功能说明：使用验证TLS的流读取HTTP响应并去除UTF-8 BOM
     *
     * 状态要求200且不跟随重定向；HTTP方法由流上下文发送。
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array $header HTTP头映射/列表；最终流上下文由buildContext建立（默认：array()）
     * @param array|string $data 请求体；数组按当前实现编码为表单内容（默认：''）
     * @param string $method GET/POST等HTTP请求方法（默认：'GET'）
     * @param int|float $timeout 最长等待/执行时间，单位秒（默认：10）
     * @return string 去除UTF-8 BOM后的正文，失败返回空串
     * @throws InvalidArgumentException 请求方法不支持或请求头非法；传输失败仍按空串返回约定处理
     */
    public static function getRemote_stream($url, $header=array(), $data='', $method='GET', $timeout=10) {
        if(!in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) return '';
        list($url, $data, $method) = self::remoteRequest($url, $data, $method);
        $header['method'] = $method;
        if($method === 'POST') $header['data'] = $data;
        $header['append'] = ['timeout'=>max(1, (int)$timeout), 'follow_location'=>0];
        $context = self::buildContext($url, $header);
        $content = @file_get_contents($url, false, $context);
        if($content === false || !preg_match('/^HTTP\/\S+\s+200(?:\s|$)/', $http_response_header[0] ?? '')) return '';
        return self::removeBom($content);
    }

    /**
     * 功能说明：规范HTTP方法及表单请求体，GET数据并入查询串
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array|string $data 请求体；数组按当前实现编码为表单内容
     * @param string $method GET/POST等HTTP请求方法
     * @return array 规范化的[url,body,method]三元组
     * @throws InvalidArgumentException HTTP方法不是GET或POST
     * @phpstan-return array{string,string,string}
     */
    protected static function remoteRequest($url, $data, $method) {
        if(is_array($data)) $data = http_build_query($data, '', '&');
        $data = (string)$data;
        $method = $method === null ? ($data === '' ? 'GET' : 'POST') : strtoupper($method);
        if(!in_array($method, ['GET', 'POST'], true)) throw new InvalidArgumentException('Unsupported HTTP method');
        if($method === 'GET' && $data !== '') {
            $parts = explode('#', $url, 2);
            $url = $parts[0].(strpos($parts[0], '?') === false ? '?' : '&').$data.(isset($parts[1]) ? '#'.$parts[1] : '');
            $data = '';
        }
        return [$url, $data, $method];
    }

    /**
     * 功能说明：使用cURL读取HTTP正文并输出实际请求头；无cURL时回退流读取
     *
     * return_header是历史参数名，实际输出发出的请求头，不是响应头。仅支持GET/POST，其他方法在规范化阶段抛InvalidArgumentException。
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array $header 附加HTTP请求头列表（默认：array()）
     * @param array|string $data 请求体；数组按当前实现编码为表单内容（默认：''）
     * @param string|array &$return_header 引用输出：cURL实际发出的请求头文本；流回退或提前失败时不改写（默认：array()）
     * @param string|null $method 仅支持GET/POST；null按请求体是否为空选择，其他方法由remoteRequest拒绝（默认：null）
     * @param int|float $timeout 最长等待/执行时间，单位秒（默认：30）
     * @return string 状态200时的正文；失败为空串，return_header输出请求头
     * @throws InvalidArgumentException HTTP方法不支持或请求头含CR/LF
     */
    public static function getRemote_curl($url, $header=array(), $data='', &$return_header=array(), $method=null, $timeout=30) {
        if(!in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) return '';
        list($url, $data, $method) = self::remoteRequest($url, $data, $method);
        if(!function_exists('curl_init')) return self::getRemote_stream($url, $header, $data, $method, $timeout);
        $curl = curl_init();
        if($curl === false) return '';
        $headers = [];
        foreach($header as $key => $value) {
            if(is_string($key)) {
                if(preg_match('/[\r\n]/', $key.(string)$value)) throw new InvalidArgumentException('Invalid HTTP header');
                $headers[] = $key.': '.$value;
            } else {
                if(preg_match('/[\r\n]/', (string)$value)) throw new InvalidArgumentException('Invalid HTTP header');
                $headers[] = $value;
            }
        }
        curl_setopt_array($curl, [
            CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>false,
            CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_CONNECTTIMEOUT=>min(10, max(1, (int)$timeout)), CURLOPT_TIMEOUT=>max(1, (int)$timeout),
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION=>false, CURLOPT_HTTPHEADER=>$headers,
            CURLINFO_HEADER_OUT=>true, CURLOPT_USERAGENT=>'MyStep Framework'
        ]);
        $tls = self::tlsOptions();
        $ca = $tls['cafile'] ?? (ini_get('curl.cainfo') ?: ini_get('openssl.cafile'));
        if($ca) curl_setopt($curl, CURLOPT_CAINFO, $ca);
        if(isset($tls['capath'])) curl_setopt($curl, CURLOPT_CAPATH, $tls['capath']);
        if($method === 'POST') { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }
        else curl_setopt($curl, CURLOPT_HTTPGET, true);
        $result = curl_exec($curl);
        if($result === false || curl_errno($curl) || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) $result = '';
        $return_header = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        unset($curl);
        return self::removeBom($result);
    }

    /**
     * 功能说明：输出内容
     *
     * @param string $content 待处理或输出的内容字节串
     * @return void 无返回值。
     */
    public static function show($content) {
        if(count(ob_list_handlers())>0 && ob_get_length()!==false) ob_clean();
        file_put_contents('php://output', $content);
        //echo $content;
        exit;
    }

    /**
     * 功能说明：文件搜索（支持通配符）
     *
     * @param string|array $filter 文件名glob通配模式或模式数组；纯单词自动包含匹配，空串匹配全部（默认：''）
     * @param string $dir 目标目录（默认：'./'）
     * @param bool $recursive 是否递归处理子目录（默认：false）
     * @param int $mode 类型位：1目录、2文件、3两者；对应DIR/FILE/BOTH常量（默认：3）
     * @return array 匹配的完整路径列表，目录项带末尾斜杠；不存在时为空数组
     */
    public static function find($filter='', $dir='./', $recursive=false, $mode = 3) {
        $dir = self::realPath($dir);
        if(!is_dir($dir)) return [];
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
                    if(is_link($name)) continue;
                    $result = array_merge($result, self::find($filter, $name, $recursive, $mode));
                }
            }
        }
        return $result;
    }

    /**
     * 功能说明：解压数据
     *
     * @param string $data gzip封装的压缩字节串
     * @return string|false 解压后的原始数据；gzip头或解压校验失败为false
     */
    public static function gzdecode($data) {
        if(substr($data, 0, 2) !== "\x1f\x8b") return $data;
        $decoded = @gzdecode($data);
        return $decoded === false ? $data : $decoded;
    }

    /**
     * 功能说明：获取远程文件
     *
     * @param string $remote_file 要下载的远程资源URL
     * @param string $local_file 下载内容保存到的本地文件路径（默认：'./'）
     * @return bool 取得非空内容并保存成功为true，否则为false
     */
    public static function getRemoteFile($remote_file, $local_file='./') {
        $local_file = self::realPath($local_file);
        if(substr($local_file, -1) === '/') $local_file .= basename(parse_url($remote_file, PHP_URL_PATH) ?: 'download');
        $content = self::getRemote($remote_file);
        return $content !== '' && self::saveFile($local_file, $content) !== false;
    }

    /**
     * 功能说明：判断是否存在子目录
     *
     * @param string $dir 目标目录
     * @param bool $only_dir 是否只检查子目录而忽略普通文件（默认：true）
     * @return bool 存在符合only_dir筛选的子项为true，否则为false
     */
    public static function judgeChild($dir, $only_dir = true) {
        if(!is_dir($dir) || ($handle = opendir($dir)) === false) return false;
        try {
            while(($file = readdir($handle)) !== false) {
                if($file === '.' || $file === '..') continue;
                if(!$only_dir || is_dir($dir.'/'.$file)) return true;
            }
            return false;
        } finally { closedir($handle); }
    }

    /**
     * 功能说明：获取文件树
     *
     *
     * 记录以文件/目录名为键，每项含name、格式化size、rwx权限attr及Y/m/d H:i:s时间time；目录size为---，递归时仅非链接目录增加sub子树。
     *
     * @param string $dir 目标目录（默认：'./'）
     * @param bool $recursion 是否递归构建下级目录树（默认：false）
     * @return array 目录/文件树记录列表；字段及递归结构见方法中的结构说明
     * @phpstan-return array<array-key,array{name:string,size:string,attr:string|false,time:string,sub?:array}>
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
                if($recursion && !is_link($theFile)) $tree[$file]['sub'] = self::getTree($dir.'/'.$file, true);
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
     * 功能说明：去除UTF-8文件头
     *
     * @param string $str 待处理的字符串
     * @return string 移除开头UTF-8 BOM后的字节串；其余字节原样保留
     */
    public static function removeBom($str) {
        $str = (string)$str;
        return substr($str, 0, 3) === "\xEF\xBB\xBF" ? substr($str, 3) : $str;
    }

    /**
     * 功能说明：检测某文件或目录是否可写
     *
     * @param string $file 目标文件路径
     * @return bool 目标文件或其目录可写为true，否则为false
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
    /**
     * 功能说明：验证归档成员并生成根目录内的发布路径，拒绝符号链接
     *
     * @param string $root 操作限制在其中的受信根目录
     * @param string $name 相对归档成员名；拒绝绝对路径、上级跳转及已有符号链接
     * @return string|false 合法成员的目标路径；非法路径或符号链接为false
     */
    public static function extractionPath($root, $name) {
        if(!is_string($name) || $name === '' || preg_match('/[\x00-\x1f\x7f\\\\]/', $name)
            || $name[0] === '/' || preg_match('/^[a-z]:/i', $name)) return false;
        $root = realpath($root);
        if($root === false || !is_dir($root)) return false;
        $parts = explode('/', rtrim($name, '/'));
        $path = $root;
        foreach($parts as $part) {
            if($part === '' || $part === '.' || $part === '..') return false;
            $path .= '/'.$part;
            if(is_link($path)) return false;
            if(file_exists($path)) {
                $real = realpath($path);
                if($real === false || strpos($real, $root.'/') !== 0) return false;
            }
        }
        return $path;
    }

    /**
     * 功能说明：固定到经检查的公网地址读取有限大小内容，不跟随重定向
     *
     * @param string $url 目标URL或需要处理的链接
     * @param int $max_bytes 最大允许读取的字节数（默认：10485760）
     * @param int|float $timeout 最长等待/执行时间，单位秒（默认：10）
     * @return string|false 2xx响应正文；解析、地址、大小、传输或状态校验失败为false
     */
    public static function getRemotePublic($url, $max_bytes = 10485760, $timeout = 10) {
        if(!is_string($url) || !is_int($max_bytes) || $max_bytes <= 0 || !function_exists('curl_init')) return false;
        $parts = parse_url($url);
        if(!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true) || isset($parts['user']) || isset($parts['pass'])) return false;
        $host = trim($parts['host'] ?? '', '[]');
        if($host === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) return false;
        $port = $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80);
        if(!in_array($port, [80,443], true)) return false;
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : static::publicAddresses($host);
        if(!$ips) return false;
        foreach($ips as $ip) if(!static::publicAddress($ip)) return false;
        $curl = curl_init($url); $body = ''; $overflow = false;
        $options = [CURLOPT_RETURNTRANSFER=>false, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>min(5, max(1, (int)$timeout)),
            CURLOPT_TIMEOUT=>max(1, (int)$timeout), CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS, CURLOPT_PROXY=>'', CURLOPT_MAXREDIRS=>0,
            CURLOPT_WRITEFUNCTION=>function($handle, $chunk) use (&$body, &$overflow, $max_bytes) {
                if(strlen($body)+strlen($chunk) > $max_bytes) { $overflow = true; return 0; }
                $body .= $chunk; return strlen($chunk);
            }];
        if(!filter_var($host, FILTER_VALIDATE_IP)) $options[CURLOPT_RESOLVE] = [$host.':'.$port.':'.(strpos($ips[0], ':') !== false ? '['.$ips[0].']' : $ips[0])];
        curl_setopt_array($curl, $options);
        try {
            $ok = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            return $ok !== false && !$overflow && $status >= 200 && $status < 300 ? $body : false;
        } finally { unset($curl); }
    }

    /**
     * 功能说明：解析主机的IPv4/IPv6地址并去除重复值
     *
     * @param string $host 服务器主机名或地址
     * @return array 不重复的IP地址列表；解析失败为空数组
     */
    protected static function publicAddresses($host) {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA); $ips = [];
        foreach($records ?: [] as $record) if(isset($record['ip']) || isset($record['ipv6'])) $ips[] = $record['ip'] ?? $record['ipv6'];
        return array_values(array_unique($ips));
    }

    /**
     * 功能说明：判断单个IP是否可用作受限公网资源读取目标
     *
     * @param string $ip 待检查的IPv4或IPv6地址
     * @return bool 符合当前公网目标限制为true
     */
    protected static function publicAddress($ip) {
        if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
        $binary = inet_pton($ip);
        if(strlen($binary) === 4) {
            $value = unpack('N', $binary)[1];
            foreach([[0x64400000, 10], [0xc0000000, 24], [0xc0000200, 24], [0xc6336400, 24], [0xcb007100, 24], [0xc6120000, 15], [0xe0000000, 3]] as $range) {
                $mask = (0xffffffff << (32-$range[1])) & 0xffffffff;
                if(($value & $mask) === $range[0]) return false;
            }
        }
        if(strlen($binary) === 16) {
            // Allow global IPv6 unicast only; reject mapped/translated/local ranges.
            if((ord($binary[0]) & 0xe0) !== 0x20) return false;
            if(substr($binary, 0, 4) === "\x20\x01\x0d\xb8" || substr($binary, 0, 2) === "\x20\x02" || substr($binary, 0, 4) === "\x20\x01\x00\x00") return false;
        }
        return true;
    }

    /**
     * 功能说明：下载受限公网位图，按实际图像格式确定后缀并保存
     *
     * @param string $url 目标URL或需要处理的链接
     * @param string $directory 受信的图片保存目录
     * @return string|false 已保存附件的存储文件名，不含目录；失败为false
     */
    public static function importPublicImage($url, $directory) {
        $body = static::getRemotePublic($url);
        if($body === false || !self::mkdir($directory)) return false;
        $temporary = tempnam($directory, '.image-');
        if($temporary === false) return false;
        try {
            if(file_put_contents($temporary, $body) !== strlen($body)) return false;
            $info = myImg::inspect($temporary);
            $extension = $info ? ([IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_GIF=>'gif'][$info[2]] ?? null) : null;
            if(!$extension) return false;
            $image = new myImg(); $resource = $image->load($temporary);
            if(!$resource) return false;
            // Re-encode to remove executable payloads and metadata appended to images.
            $stored = time().'.'.bin2hex(random_bytes(12)).'.'.$extension;
            $path = rtrim($directory, '/').'/'.$stored;
            try { $encoder = ['jpg'=>'imagejpeg', 'png'=>'imagepng', 'gif'=>'imagegif'][$extension]; $saved = $encoder($resource, $path); }
            finally { unset($resource); }
            if(!$saved || !is_file($path)) { if(is_file($path)) unlink($path); return false; }
            $display = rawurldecode(basename(parse_url($url, PHP_URL_PATH) ?: 'image.'.$extension));
            if(!myAttachment::append($directory, $stored, $display)) { unlink($path); return false; }
            return $stored;
        } finally { if(is_file($temporary)) unlink($temporary); }
    }

}