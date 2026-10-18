<?PHP
/********************************************
*                                           *
* Name    : Pack Manager                    *
* Modifier: Windy2000                       *
* Time    : 2003-05-30                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myPacker.class.php
 * 功能说明：框架自定义压缩包管理器
 *   打包/解包自定义归档格式，提供体积/条目预算、字符集转换和暂存回滚发布。
 *
 * 方法目录：
 *   public myPacker::setLimits($bytes = 268435456, $entries = 10000) — 设置自定义归档的未压缩字节及成员数量上限
 *   public myPacker::init($pack_dir = './', $pack_file = 'mypack.pkg', $separator = '|') — 初始化类变量
 *   public myPacker::addIgnore() — 添加排除文件
 *   public myPacker::setCharset($charset, $lng_type='', $file_ext='') — 设定字符集相关变量
 *   public myPacker::addFile() — 添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件）
 *   protected myPacker::packFileList() — 打包已添加文件
 *   protected myPacker::packFile($dir='./') — 打包文件或目录
 *   protected myPacker::write($content) — 累计归档写入预算并完整写入数据，超限或短写抛异常
 *   protected myPacker::unpackFile($outdir='./') — 解开文件包
 *   public myPacker::pack() — 打包文件
 *   public myPacker::unpack($remove = true) — 解包文件
 *   public myPacker::result($mode = 0) — 返回操作结果
 *
 * 使用说明：
 *   1. 通过pack_dir/pack_file选择目录和包文件；不是Composer或JS打包器。
 *   2. 解包接受受信包来源，并对成员路径/资源预算进行检查。
 *   3. 与myArchive共同完成发布；历史包格式及编码规则按方法说明使用。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：打包/解包自定义归档格式，提供体积/条目预算、字符集转换和暂存回滚发布。
 *
 * 使用说明：
 *   1. 通过pack_dir/pack_file选择目录和包文件；不是Composer或JS打包器。
 *   2. 解包接受受信包来源，并对成员路径/资源预算进行检查。
 *   3. 与myArchive共同完成发布；历史包格式及编码规则按方法说明使用。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myPacker.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myPacker extends myBase {
    protected
        $file_count = 0,
        $file_ignore = array(),        // ignore these files when packing
        $file_list = array(),        // only files in the list will be pack
        $pack_file = 'pack.bin',    // the file name of packed file
        $pack_dir = './',            // the directory of pack or unpack to
        $pack_fp = null,
        $pack_result = array(),
        $separator = '|',
        $charset = array();

    protected $max_bytes = 268435456, $max_entries = 10000, $raw_bytes = 0, $entry_count = 0;

    /**
     * 功能说明：设置自定义归档的未压缩字节及成员数量上限
     *
     * @param int $bytes 最大允许处理的字节数，必须为正整数（默认：268435456）
     * @param int $entries 归档允许的最大成员数量（默认：10000）
     * @return $this 返回当前实例，便于连续设置
     * @throws InvalidArgumentException 归档体积或条目上限不是正整数
     */
    public function setLimits($bytes = 268435456, $entries = 10000) {
        if(!is_int($bytes) || !is_int($entries) || $bytes <= 0 || $entries <= 0) throw new InvalidArgumentException('Archive limits must be positive integers');
        $this->max_bytes = $bytes; $this->max_entries = $entries;
        return $this;
    }

    /**
     * 功能说明：初始化类变量
     *
     * @param string $pack_dir 待打包或待解包的根目录（默认：'./'）
     * @param string $pack_file 自定义归档文件路径（默认：'mypack.pkg'）
     * @param string $separator 自定义归档格式的分隔标记（默认：'|'）
     * @return void 无返回值。
     */
    public function init($pack_dir = './', $pack_file = 'mypack.pkg', $separator = '|') {
        $this->pack_dir = myFile::realPath($pack_dir);
        $this->pack_file = myFile::realPath($pack_file);
        if(!empty($separator)) $this->separator = $separator;
        $this->addIgnore(basename($pack_file));
    }

    /**
     * 功能说明：添加排除文件
     *
     * @param string|array ...$args 排除项列表，或一个包含排除项的数组（历史位置参数，由func_get_args读取）
     * @return void 无返回值。
     */
    public function addIgnore() {
        $args_list = func_get_args();
        if(!$args_list) return;
        if(is_array($args_list[0])) $args_list = $args_list[0];
        $this->file_ignore = array_merge($this->file_ignore, $args_list);
    }

    /**
     * 功能说明：设定字符集相关变量
     *
     * @param string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定
     * @param string $lng_type 地区用语转换目标代号，空串不转换（默认：''）
     * @param string $file_ext 需要转码的扩展名集合文本，内部按子串匹配，例如php,html,txt（默认：''）
     * @return void 无返回值。
     */
    public function setCharset($charset, $lng_type='', $file_ext='') {
        $this->charset['to'] = $charset;
        $this->charset['lng_type'] = $lng_type;
        $this->charset['file_ext'] = $file_ext;
    }

    /**
     * 功能说明：添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件）
     *
     * @param string|array ...$args 待打包的路径列表，或一个路径数组（历史位置参数，由func_get_args读取）
     * @return void 无返回值。
     */
    public function addFile() {
        $args_list = func_get_args();
        if(isset($args_list[0]) && is_array($args_list[0])) $args_list = $args_list[0];
        $this->file_list = array_merge($this->file_list, $args_list);
    }

    /**
     * 功能说明：打包已添加文件
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    protected function packFileList() {
        for($i=0,$m=count($this->file_list); $i<$m; $i++) {
            $this->packFile($this->file_list[$i]);
        }
    }

    /**
     * 功能说明：打包文件或目录
     *
     * @param string $dir 目标目录（默认：'./'）
     * @return void 无返回值。
     * @throws RuntimeException 源路径越界、文件名不受格式支持、资源超限或I/O失败
     */
    protected function packFile($dir='./') {
        $root = myFile::rootPath();
        if(is_link($dir)) return;
        $dir = myFile::realPath($dir);
        if(rtrim($dir, '/') === rtrim($this->pack_file, '/')) return;
        $member = substr($dir, strlen(rtrim($this->pack_dir, '/'))+1);
        if($dir === $this->pack_dir) $member = '';
        if(strpos($dir, rtrim($this->pack_dir, '/').'/') !== 0 && $dir !== $this->pack_dir) throw new RuntimeException('Pack source is outside root');
        if(strpbrk($member, $this->separator."\r\n") !== false) throw new RuntimeException('Unsupported archive filename');
        for($i=0,$m=count($this->file_ignore);$i<$m;$i++) {
            if(substr($dir, -(strlen($this->file_ignore[$i])))==$this->file_ignore[$i]) return;
        }
        if(++$this->entry_count > $this->max_entries) throw new RuntimeException('Too many archive entries');
        if(is_dir($dir)) {
            $ignore = array();
            if(is_file($dir.'/ignore')) {
                $ignore = file_get_contents($dir.'/ignore');
                if(strlen($ignore)==0) return;
                $ignore = str_replace(chr(13), '', $ignore);
                $ignore = explode(chr(10), $ignore);
            }
            $allow = array();
            if(is_file($dir.'/allow')) {
                $allow = file_get_contents($dir.'/allow');
                if(strlen($allow)==0) return;
                $allow = str_replace(chr(13), '', $allow);
                $allow = explode(chr(10), $allow);
            }
            $content = 'dir'.$this->separator.$member.$this->separator.filemtime($dir).chr(10);
            $this->write($content);
            $mydir = opendir($dir);
            if(!$mydir) throw new RuntimeException('Cannot open pack source directory');
            try {
            while($file = readdir($mydir)) {
                if(trim($file, '.') == '' || $file == 'ignore' || $file == 'allow' || strpos($file, '.mystep-') === 0) continue;
                if(!empty($allow) && array_search($file, $allow)===false) continue;
                if(!empty($ignore) && array_search($file, $ignore)!==false) continue;
                $this->packFile($dir.'/'.$file);
            }
            } finally { closedir($mydir); }
        } elseif(is_file($dir)) {
            $size = filesize($dir);
            if($size > $this->max_bytes) throw new RuntimeException('Pack source exceeds limit');
            $extension = pathinfo($dir, PATHINFO_EXTENSION);
            $convert = isset($this->charset['file_ext']) && $extension !== '' && strpos($this->charset['file_ext'], $extension) !== false;
            if($convert) {
                $memory = myFile::getByte(ini_get('memory_limit'));
                if($memory > 0 && $size*5 > $memory-memory_get_usage(true)) throw new RuntimeException('Insufficient memory for charset conversion');
                $data = file_get_contents($dir);
                if($data === false) throw new RuntimeException('Cannot read pack source');
                if(!empty($this->charset['lng_type'])) $data = chg_lng_custom($data, $this->charset['lng_type']);
                elseif(strtolower($this->charset['to']) === 'big5') $data = chs2cht($data, 1);
                $data = myString::setCharset($data, $this->charset['to']); $size = strlen($data);
            }
            $this->write('file'.$this->separator.$member.$this->separator.$size.$this->separator.filemtime($dir)."\n");
            if($convert) $this->write($data);
            else {
                $input = fopen($dir, 'rb');
                if(!$input) throw new RuntimeException('Cannot read pack source');
                $read = 0;
                try {
                    while(!feof($input)) {
                        $chunk = fread($input, 65536);
                        if($chunk === false) throw new RuntimeException('Cannot read pack source');
                        $read += strlen($chunk); $this->write($chunk);
                    }
                } finally { fclose($input); }
                if($read !== $size) throw new RuntimeException('Pack source changed during read');
            }
            $this->file_count++;
            $this->pack_result[] = ['Packing File', str_replace($root, '/', $dir), myFile::getSize($dir)];
        }
    }

    /**
     * 功能说明：累计归档写入预算并完整写入数据，超限或短写抛异常
     *
     * 写入预算按当前归档已累计字节检查，异常由上层pack流程处理。
     *
     * @param string $content 待处理或输出的内容字节串
     * @return void 无返回值。
     * @throws RuntimeException 累计写入超过上限或底层写入不完整
     */
    protected function write($content) {
        $this->raw_bytes += strlen($content);
        if($this->raw_bytes > $this->max_bytes || fwrite($this->pack_fp, $content) !== strlen($content)) throw new RuntimeException('Cannot write package or size limit exceeded');
    }

    /**
     * 功能说明：解开文件包
     *
     * @param string $outdir 解包文件发布到的目标目录（默认：'./'）
     * @return int|false 成功发布的归档成员数；校验、写入或发布失败为false
     */
    protected function unpackFile($outdir='./') {
        if(!myFile::mkdir($outdir)) return false;
        $entries = []; $total = 0; $transaction = new myArchive($outdir);
        // Preflight the whole package before any file is replaced.
        while(!feof($this->pack_fp)) {
            $header = fgets($this->pack_fp, 4096);
            if($header === false) break;
            if(substr($header, -1) !== "\n" || count($entries) >= $this->max_entries) return false;
            $data = explode($this->separator, rtrim($header, "\r\n"));
            if(($data[0] ?? '') === 'dir' && count($data) === 3) {
                if($data[1] === '') continue; // Legacy root marker.
                $path = myFile::extractionPath($outdir, $data[1]);
                if(!$path) return false;
                $staged = $transaction->stage($data[1], true);
                if(!$staged) return false;
                $entries[] = ['dir', $staged, 0, 0];
            } elseif(($data[0] ?? '') === 'file' && count($data) === 4 && ctype_digit($data[2])) {
                $path = myFile::extractionPath($outdir, $data[1]);
                $size = (float)$data[2]; $total += $size;
                if(!$path || $size > $this->max_bytes || $total > $this->max_bytes) return false;
                $offset = ftell($this->pack_fp);
                $meta = fstat($this->pack_fp);
                if($offset+$size > $meta['size'] || fseek($this->pack_fp, (int)$size, SEEK_CUR) !== 0) return false;
                if($path === realpath($this->pack_file)) return false;
                $staged = $transaction->stage($data[1]);
                if(!$staged) return false;
                $entries[] = ['file', $staged, $offset, (int)$size];
            } else return false;
        }
        foreach($entries as $entry) {
            list($type, $path, $offset, $size) = $entry;
            if($type === 'dir') {
                if(!myFile::mkdir($path)) return false;
            } else {
                if(!myFile::mkdir(dirname($path))) return false;
                $temporary = tempnam(dirname($path), '.unpack-');
                if($temporary === false) return false;
                $output = fopen($temporary, 'wb');
                try {
                    fseek($this->pack_fp, $offset);
                    $written = stream_copy_to_stream($this->pack_fp, $output, $size);
                    fclose($output); $output = null;
                    if($written !== $size || !rename($temporary, $path)) return false;
                } finally {
                    if(is_resource($output)) fclose($output);
                    if(is_file($temporary)) unlink($temporary);
                }
                $this->file_count++;
            }
            $this->pack_result[] = [$type === 'dir' ? 'Build Directory' : 'Unpacking File', $path, 'Successfully'];
        }
        return $transaction->commit() ? count($entries) : false;
    }

    /**
     * 功能说明：打包文件
     *
     * 参数说明：无显式参数。
     *
     * @return bool 归档完整生成并发布为true；预算、输入或写入失败为false，详细状态见result
     */
    public function pack() {
        $this->pack_result = []; $this->file_count = $this->raw_bytes = $this->entry_count = 0;
        if(!myFile::mkdir(dirname($this->pack_file))) return false;
        $temporary = tempnam(dirname($this->pack_file), '.pack-');
        if($temporary === false) return false;
        $this->addIgnore(basename($temporary));
        $this->pack_fp = fopen($temporary, 'wb');
        if(!$this->pack_fp) { unlink($temporary); return false; }
        try {
            $filter = stream_filter_append($this->pack_fp, 'zlib.deflate', STREAM_FILTER_WRITE, ['window'=>15, 'level'=>9]);
            if(!$filter) return false;
            if($this->file_list) $this->packFileList();
            else $this->packFile($this->pack_dir);
            if(!stream_filter_remove($filter)) return false;
            fclose($this->pack_fp); $this->pack_fp = null;
            if(!rename($temporary, $this->pack_file)) return false;
            $this->pack_result[] = ['pack', $this->file_count, $this->pack_file, myFile::getSize($this->pack_file)];
            return true;
        } catch(Throwable $error) { return false; }
        finally {
            if(is_resource($this->pack_fp)) fclose($this->pack_fp);
            if(is_file($temporary)) unlink($temporary);
        }
    }

    /**
     * 功能说明：解包文件
     *
     * @param bool $remove 操作成功后是否删除源归档文件（默认：true）
     * @return bool 解包发布成功为true；remove启用时还要求源归档删除成功
     */
    public function unpack($remove = true) {
        $this->pack_result = []; $this->file_count = 0;
        if(!is_file($this->pack_file) || filesize($this->pack_file) > $this->max_bytes) return false;
        $input = fopen($this->pack_file, 'rb');
        $this->pack_fp = fopen('php://temp/maxmemory:2097152', 'w+b');
        if(!$input || !$this->pack_fp) { if(is_resource($input)) fclose($input); if(is_resource($this->pack_fp)) fclose($this->pack_fp); return false; }
        try {
            $inflate = inflate_init(ZLIB_ENCODING_DEFLATE); $total = 0; $read = 0;
            while(!feof($input)) {
                $chunk = fread($input, 1024);
                if($chunk === false) return false;
                $read += strlen($chunk);
                $raw = @inflate_add($inflate, $chunk, ZLIB_SYNC_FLUSH);
                if($raw === false || ($total += strlen($raw)) > $this->max_bytes || fwrite($this->pack_fp, $raw) !== strlen($raw)) return false;
                if(inflate_get_status($inflate) === ZLIB_STREAM_END) break;
            }
            if(inflate_get_status($inflate) !== ZLIB_STREAM_END || inflate_get_read_len($inflate) !== $read || fgetc($input) !== false) return false;
            rewind($this->pack_fp);
            $count = $this->unpackFile($this->pack_dir);
            if($count === false) return false;
            $this->pack_result[] = ['unpack', $this->file_count, $count-$this->file_count];
        } catch(Throwable $error) { return false; }
        finally { fclose($input); fclose($this->pack_fp); $this->pack_fp = null; }
        return !$remove || unlink($this->pack_file);
    }

    /**
     * 功能说明：返回操作结果
     *
     * @param int $mode 0返回HTML说明，非零返回原始操作记录数组（默认：0）
     * @return array|string mode为0返回HTML摘要，非零返回操作记录数组；尚无结果时HTML模式返回空串
     */
    public function result($mode = 0) {
        if($mode) return $this->pack_result;
        if(!$this->pack_result) return '';
        $result = '';
        for($i=0,$m=count($this->pack_result)-1;$i<$m;$i++) {
            $result .= '<b>'.$this->pack_result[$i][0].'</b> - <i>'.htmlspecialchars((string)$this->pack_result[$i][1], ENT_QUOTES, 'UTF-8').'</i> &nbsp; '.$this->pack_result[$i][2].'<br />'.chr(10);
        }
        if($this->pack_result[$i][0]=='pack') {
            $result .= 'Count: '.$this->pack_result[$i][1].' File(s).<br />'.chr(10);
            $result .= 'Packed File: <a href="'.$this->pack_result[$i][2].'">'.basename($this->pack_result[$i][2]).'</a> ('.$this->pack_result[$i][3].')';
        } else {
            $result .= 'Extract: '.$this->pack_result[$i][1].' File(s) & '.$this->pack_result[$i][2].' Dir(s).';
        }
        return $result;
    }
}