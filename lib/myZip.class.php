<?PHP
/********************************************
*                                           *
* Name    : Zip file handler                *
* Modifier: Windy2000                       *
* Time    : 2011-12-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myZip.class.php
 * 功能说明：ZIP打包与受限解包工具
 *   扩展ZipArchive，提供目录收集、原子生成ZIP、预算校验及事务式解包。
 *
 * 方法目录：
 *   public myZip::setLimits($bytes = 268435456, $entries = 10000) — 设置ZIP解包的总未压缩字节及成员数量上限
 *   public myZip::__construct($zip_file = '', $root_fix = '') — 登记ZIP路径及成员相对根目录，延迟到实际操作时打开归档
 *   protected myZip::memberName($path) — 按相对根目录生成ZIP成员名，根外路径使用文件名
 *   public myZip::addDir($path) — 递归向已打开ZIP加入目录成员，跳过链接及内部暂存文件
 *   public myZip::zip($file) — 收集指定文件/目录，在临时ZIP中构建成功后原子发布
 *   public myZip::unzip($dir='./', $remove = false) — 验证ZIP路径、大小及CRC，在暂存目录解包后统一发布
 *
 * 使用说明：
 *   1. 需要ZipArchive扩展；构造时选择ZIP路径及成员相对根目录。
 *   2. zip接收文件/目录列表；unzip先校验成员再通过myArchive暂存发布。
 *   3. 拒绝路径越界、符号链接及超预算归档；不会为方便解包而放宽边界。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：扩展ZipArchive，提供目录收集、原子生成ZIP、预算校验及事务式解包。
 *
 * 使用说明：
 *   1. 需要ZipArchive扩展；构造时选择ZIP路径及成员相对根目录。
 *   2. zip接收文件/目录列表；unzip先校验成员再通过myArchive暂存发布。
 *   3. 拒绝路径越界、符号链接及超预算归档；不会为方便解包而放宽边界。
 *
 * 使用示例：
 *     $zip = new myZip($archive, $sourceRoot); $ok = $zip->zip([$file, $directory]);
 *     $ok = (new myZip($archive))->unzip($destination);
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myZip.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myZip extends ZipArchive {
    protected $file, $root_fix, $working_file;

    protected $max_bytes = 268435456, $max_entries = 10000;

    /**
     * 功能说明：设置ZIP解包的总未压缩字节及成员数量上限
     *
     * @param int $bytes 最大允许处理的字节数，必须为正整数（默认：268435456）
     * @param int $entries 归档允许的最大成员数量（默认：10000）
     * @return $this 返回当前ZIP实例
     * @throws InvalidArgumentException 归档体积或条目上限不是正整数
     */
    public function setLimits($bytes = 268435456, $entries = 10000) {
        if(!is_int($bytes) || !is_int($entries) || $bytes <= 0 || $entries <= 0) throw new InvalidArgumentException('Archive limits must be positive integers');
        $this->max_bytes = $bytes; $this->max_entries = $entries;
        return $this;
    }

    /**
     * 功能说明：登记ZIP路径及成员相对根目录，延迟到实际操作时打开归档
     *
     * @param string $zip_file 需要创建或读取的ZIP文件路径（默认：''）
     * @param string $root_fix ZIP成员名使用的相对根目录，空串采用默认根目录（默认：''）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct($zip_file = '', $root_fix = '') {
        $this->file = $zip_file; $this->root_fix = $root_fix;
    }

    /**
     * 功能说明：按相对根目录生成ZIP成员名，根外路径使用文件名
     *
     * @param string $path 目标目录或路径
     * @return string 写入ZIP使用的相对成员名
     */
    protected function memberName($path) {
        $root = rtrim($this->root_fix ?: myFile::rootPath(), '/').'/';
        $path = str_replace('\\', '/', $path);
        return strpos($path, $root) === 0 ? substr($path, strlen($root)) : basename(rtrim($path, '/'));
    }

    /**
     * 功能说明：递归向已打开ZIP加入目录成员，跳过链接及内部暂存文件
     *
     * @param string $path 目标目录或路径
     * @return bool 目录成员全部加入成功为true，否则为false
     */
    public function addDir($path) {
        if(!is_dir($path) || is_link($path)) return false;
        $name = $this->memberName($path);
        if($name !== '' && !$this->addEmptyDir(rtrim($name, '/'))) return false;
        foreach(new DirectoryIterator($path) as $entry) {
            if($entry->isDot() || $entry->isLink() || strpos($entry->getFilename(), '.mystep-') === 0) continue;
            $file = $entry->getPathname();
            if(realpath($file) === realpath($this->file) || ($this->working_file && realpath($file) === realpath($this->working_file))) continue;
            if($entry->isDir()) { if(!$this->addDir($file)) return false; }
            elseif(!$this->addFile($file, $this->memberName($file))) return false;
        }
        return true;
    }

    /**
     * 功能说明：收集指定文件/目录，在临时ZIP中构建成功后原子发布
     *
     * @param string|array $file 待压缩的文件/目录路径或列表；兼容多个位置参数
     * @param string ...$args 额外的待压缩文件/目录路径；与首项一起处理（历史位置参数，由func_get_args读取）
     * @return bool 临时ZIP完整构建并发布成功为true，否则为false
     */
    public function zip($file) {
        $files = func_num_args() > 1 ? func_get_args() : (is_array($file) ? $file : [$file]);
        if(!$files) return false;
        if($this->file === '') {
            if(count($files) === 1) $this->file = preg_replace('/\.[^.]+$/', '', basename(rtrim($files[0], '/'))).'.zip';
            else { $temporary = tempnam(sys_get_temp_dir(), 'mystep-zip-'); if($temporary === false) return false; unlink($temporary); $this->file = $temporary.'.zip'; }
        }
        $this->file = myFile::realPath($this->file);
        if(!myFile::mkdir(dirname($this->file))) return false;
        $temporary = tempnam(dirname($this->file), '.zip-');
        if($temporary === false) return false;
        $this->working_file = $temporary;
        if($this->open($temporary, self::CREATE | self::OVERWRITE) !== true) { unlink($temporary); $this->working_file = null; return false; }
        $ok = true;
        try {
            foreach($files as $path) {
                if(is_link($path)) { $ok = false; break; }
                $path = myFile::realPath($path);
                if(is_dir($path)) $ok = $this->addDir($path);
                elseif(is_file($path) && realpath($path) !== realpath($this->file)) $ok = $this->addFile($path, count($files) === 1 ? basename($path) : $this->memberName($path));
                else $ok = false;
                if(!$ok) break;
            }
        } catch(Throwable $error) {
            $ok = false;
        } finally {
            $ok = $this->close() && $ok;
            if($ok) $ok = rename($temporary, $this->file);
            if(is_file($temporary)) unlink($temporary);
            $this->working_file = null;
        }
        return $ok;
    }

    /**
     * 功能说明：验证ZIP路径、大小及CRC，在暂存目录解包后统一发布
     *
     *
     * 调用示例：
     *     $ok = (new myZip($zipFile))->setLimits(67108864, 1000)->unzip($destination);
     *
     * @param string $dir ZIP成员发布到的受信目标目录（默认：'./'）
     * @param bool $remove 操作成功后是否删除源归档文件（默认：false）
     * @return bool 解包及发布成功为true；remove启用时还要求源ZIP删除成功
     */
    public function unzip($dir='./', $remove = false) {
        if($this->open($this->file) !== true) return false;
        $ok = false; $transaction = null;
        try {
            $dir = myFile::realPath($dir);
            $transaction = new myArchive($dir);
            $members = []; $total = 0;
            if($this->numFiles > $this->max_entries) return false;
            for($i=0; $i<$this->numFiles; $i++) {
                $stat = $this->statIndex($i);
                if(!$stat || ($target = myFile::extractionPath($dir, $stat['name'])) === false || $target === realpath($this->file)) return false;
                $os = $attributes = 0;
                if($this->getExternalAttributesIndex($i, $os, $attributes) && (($attributes >> 16) & 0170000) === 0120000) return false;
                $total += $stat['size'];
                if($total > $this->max_bytes) return false;
                $directory = substr($stat['name'], -1) === '/';
                $staged = $transaction->stage($stat['name'], $directory);
                if(!$staged) return false;
                $members[] = [$stat, $staged, $directory];
            }
            foreach($members as $member) {
                list($stat, $path, $directory) = $member;
                if($directory) continue;
                $input = $this->getStream($stat['name']);
                if(!$input) return false;
                $output = fopen($path, 'wb');
                if(!$output) { fclose($input); return false; }
                $crc = hash_init('crc32b'); $written = 0;
                try {
                    while(!feof($input)) {
                        $chunk = fread($input, 65536);
                        if($chunk === false) return false;
                        $written += strlen($chunk);
                        if($written > $stat['size'] || fwrite($output, $chunk) !== strlen($chunk)) return false;
                        hash_update($crc, $chunk);
                    }
                    if($written !== $stat['size'] || strtolower(hash_final($crc)) !== sprintf('%08x', $stat['crc'])) return false;
                } finally { fclose($input); fclose($output); }
            }
            $ok = $transaction->commit();
        } catch(Throwable $error) { $ok = false; }
        finally { $this->close(); unset($transaction); }
        if($ok && $remove) return unlink($this->file);
        return $ok;
    }
}
