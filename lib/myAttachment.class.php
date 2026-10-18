<?php

/*******************************************
*                                          *
* Name    : Attachment Index Tools (new)   *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/myAttachment.class.php
 * 功能说明：上传附件索引与安全查找工具
 *   校验附件标识，读写 log.txt 索引，并按唯一匹配定位或删除附件。
 *
 * 方法目录：
 *   protected static myAttachment::validName($name) — 验证附件标识的字符范围、长度及连续点限制
 *   protected static myAttachment::record($directory, $line, $id) — 解析附件索引行并验证标识匹配及文件路径边界
 *   public static myAttachment::find($root, $id, $mode = 'Ym/') — 按日期目录或有限递归扫描附件索引，仅返回唯一匹配
 *   public static myAttachment::append($directory, $stored, $display) — 清理显示文件名并加锁追加一条附件索引
 *   public static myAttachment::remove($root, $id, $mode = 'Ym/') — 加锁复核索引中的唯一匹配，删除附件并重写剩余索引
 *
 * 使用说明：
 *   1. 上传成功后调用 append() 登记；下载及删除通过 find()、remove() 定位。
 *   2. 兼容时间戳文件名和历史随机名称，使用调用方提供的受信附件根目录。
 *   3. 查找结果包含 file、name、stored、log；未找到或匹配不唯一时返回 false。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：通过附件索引集中处理上传登记、唯一查找和删除。
 *
 * 仅操作受信根目录中的合法附件，查找失败或存在歧义时不返回附件记录。
 *
 *
 * 方法目录：参见本文件顶部。
 *
 * @phpstan-type AttachmentRecord array{file:string,name:string,stored:string,log:string}
 */
class myAttachment {
    /**
     * 功能说明：验证附件标识的字符范围、长度及连续点限制
     *
     * @param string $name 附件存储名称，限字母数字及._-且不含连续点
     * @return bool 名称满足存储规则为true
     */
    protected static function validName($name) {
        return is_string($name) && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,220}\z/D', $name) && strpos($name, '..') === false;
    }

    /**
     * 功能说明：解析附件索引行并验证标识匹配及文件路径边界
     *
     * @param string $directory 当前附件索引所在目录
     * @param string $line log.txt中的一条name::display::记录
     * @param string $id 待匹配的完整存储名或不含扩展名的唯一标识
     * @return array|false 含file/name/stored/log的附件记录；无匹配或不合法为false
     * @phpstan-return AttachmentRecord|false
     */
    protected static function record($directory, $line, $id) {
        $parts = explode('::', rtrim($line, "\r\n"));
        $name = $parts[0] ?? '';
        if(!self::validName($name) || ($name !== $id && pathinfo($name, PATHINFO_FILENAME) !== $id)) return false;
        $path = myFile::extractionPath($directory, $name);
        if(!$path) return false;
        if(is_file($path.'.upload') && !is_link($path.'.upload')) $path .= '.upload';
        if(!is_file($path) || is_link($path)) return false;
        return ['file'=>$path, 'name'=>$parts[1] ?? $name, 'stored'=>$name, 'log'=>$directory.'/log.txt'];
    }

    /**
     * 功能说明：按日期目录或有限递归扫描附件索引，仅返回唯一匹配
     *
     * @param string $root 操作限制在其中的受信根目录
     * @param string $id 操作对象的标识
     * @param string $mode 由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'）
     * @return array|false 唯一附件记录；未找到、歧义或路径不合法为false
     * @phpstan-return AttachmentRecord|false
     */
    public static function find($root, $id, $mode = 'Ym/') {
        if(!self::validName($id) || !is_dir($root) || is_link($root)) return false;
        $root = realpath($root); $logs = [];
        $stamp = explode('.', $id)[0];
        if(ctype_digit($stamp) && strlen($stamp) === 10) {
            $relative = trim(date($mode, (int)$stamp), '/');
            $directory = $relative === '' ? $root : myFile::extractionPath($root, $relative);
            if($directory && is_dir($directory)) $logs[] = $directory.'/log.txt';
        } else {
            // Compatibility for random-only names emitted by an earlier uploader.
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            $seen = 0;
            foreach($iterator as $entry) {
                if(++$seen > 50000) return false;
                if(!$entry->isLink() && $entry->isFile() && $entry->getFilename() === 'log.txt') $logs[] = $entry->getPathname();
            }
        }
        $found = false;
        foreach($logs as $log) {
            if(!myFile::extractionPath($root, substr($log, strlen($root)+1))) continue;
            if(!is_file($log) || is_link($log) || filesize($log) > 16777216) continue;
            $stream = fopen($log, 'rb');
            if(!$stream) continue;
            try {
                if(!flock($stream, LOCK_SH)) continue;
                while(($line = fgets($stream, 4096)) !== false) {
                    $record = self::record(dirname($log), $line, $id);
                    if($record) { if($found) return false; $found = $record; }
                }
            } finally { fclose($stream); }
        }
        return $found;
    }

    /**
     * 功能说明：清理显示文件名并加锁追加一条附件索引
     *
     * @param string $directory 存放附件文件及log.txt的目录
     * @param string $stored 实际落盘的附件文件名
     * @param string $display 用于展示和下载的原始文件名，会移除日志分隔符/换行
     * @return bool 索引记录是否完整追加
     */
    public static function append($directory, $stored, $display) {
        if(!self::validName($stored) || !is_dir($directory) || is_link($directory) || is_link($directory.'/log.txt')) return false;
        $display = substr((string)$display, 0, 1024);
        $display = str_replace(["\r", "\n", "\0", '::'], [' ', ' ', '', ':'], (string)$display);
        $line = $stored.'::'.$display."::\n";
        return file_put_contents($directory.'/log.txt', $line, FILE_APPEND | LOCK_EX) === strlen($line);
    }

    /**
     * 功能说明：加锁复核索引中的唯一匹配，删除附件并重写剩余索引
     *
     * @param string $root 操作限制在其中的受信根目录
     * @param string $id 操作对象的标识
     * @param string $mode 由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'）
     * @return bool 附件删除及索引改写是否成功
     */
    public static function remove($root, $id, $mode = 'Ym/') {
        $record = self::find($root, $id, $mode);
        if(!$record || is_link($record['log'])) return false;
        $stream = fopen($record['log'], 'r+b');
        if(!$stream) return false;
        try {
            if(!flock($stream, LOCK_EX)) return false;
            $lines = []; $matches = [];
            while(($line = fgets($stream, 4096)) !== false) {
                if(self::record(dirname($record['log']), $line, $id)) $matches[] = $line;
                else $lines[] = $line;
            }
            if(count($matches) !== 1) return false;
            // Keep unrelated files and the directory even when this was the last record.
            if(!unlink($record['file'])) return false;
            $content = implode('', $lines);
            rewind($stream);
            return fwrite($stream, $content) === strlen($content) && ftruncate($stream, strlen($content)) && fflush($stream);
        } finally { fclose($stream); }
    }
}
