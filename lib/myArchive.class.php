<?php

/*******************************************
*                                          *
* Name    : Archive Extraction Tools (new) *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/myArchive.class.php
 * 功能说明：归档解包暂存与回滚工具
 *   为 ZIP 和框架自定义包提供目录锁、暂存发布、覆盖备份及失败回滚。
 *
 * 方法目录：
 *   public myArchive::__construct($root) — 锁定解包根目录并建立暂存及覆盖备份目录
 *   public myArchive::stage($name, $directory = false) — 验证并登记唯一归档成员，返回可供写入的暂存路径
 *   protected myArchive::move($from, $to) — 重命名文件，用作发布和回滚时可覆盖的移动入口
 *   protected myArchive::directory($path) — 递归建立发布目录并登记本次创建的目录
 *   public myArchive::commit() — 发布已暂存成员；失败时尝试恢复被覆盖文件并撤销新目录
 *   public myArchive::__destruct() — 清理暂存资源并释放目录锁，回滚失败时保留恢复备份
 *
 * 使用说明：
 *   1. 解包器逐项调用 stage()，将内容写入返回的暂存路径，全部完成后调用 commit()。
 *   2. 实例构造时持有目标目录锁，析构时释放锁并清理暂存；回滚失败时保留恢复备份。
 *   3. 成员大小、数量及 CRC 等检查由上层解包器负责。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：管理解包暂存文件、覆盖备份及发布回滚的资源生命周期。
 *
 * 普通发布失败会尝试回滚；进程崩溃或外部并发写入下不保证完整回滚。
 * 回滚失败时保留备份并记录错误，调用方应据此安排恢复。
 *
 * 方法目录：参见本文件顶部。
 */
class myArchive {
    protected $root, $stage, $backup, $lock, $entries = [], $created = [], $journal = [], $preserve = false;

    /**
     * 功能说明：锁定解包根目录并建立暂存及覆盖备份目录
     *
     * @param string $root 操作限制在其中的受信根目录
     * @return void 初始化或释放资源，无返回值。
     * @throws RuntimeException 根目录、锁或暂存目录准备失败
     */
    public function __construct($root) {
        if(is_link($root) || !myFile::mkdir($root)) throw new RuntimeException('Invalid extraction directory');
        $this->root = rtrim(realpath($root), '/');
        if($this->root === '') throw new RuntimeException('Cannot extract into filesystem root');
        $lock = $this->root.'/.mystep-extract.lock';
        if(is_link($lock)) throw new RuntimeException('Invalid archive lock');
        $this->lock = fopen($lock, 'c');
        if(!$this->lock || !flock($this->lock, LOCK_EX)) throw new RuntimeException('Cannot lock extraction directory');
        $token = bin2hex(random_bytes(12));
        $this->stage = $this->root.'/.mystep-stage-'.$token;
        $this->backup = $this->root.'/.mystep-backup-'.$token;
        if(!mkdir($this->stage, 0700) || !mkdir($this->backup, 0700)) throw new RuntimeException('Cannot stage archive');
    }

    /**
     * 功能说明：验证并登记唯一归档成员，返回可供写入的暂存路径
     *
     * @param string $name 相对解包根目录的合法成员名，不能重复或穿越目录
     * @param bool $directory true登记目录成员，false登记待写入文件（默认：false）
     * @return string|false 已登记成员的暂存路径；名称/冲突校验失败返回false
     */
    public function stage($name, $directory = false) {
        $name = rtrim($name, '/');
        if(preg_match('#(^|/)\.mystep-#', $name) || !myFile::extractionPath($this->root, $name) || isset($this->entries[$name])) return false;
        foreach($this->entries as $other => $isDirectory) {
            if((!$isDirectory && strpos($name, $other.'/') === 0) || (!$directory && strpos($other, $name.'/') === 0)) return false;
        }
        $this->entries[$name] = $directory;
        $path = $this->stage.'/'.$name;
        if(!myFile::mkdir($directory ? $path : dirname($path))) return false;
        return $path;
    }

    /**
     * 功能说明：重命名文件，用作发布和回滚时可覆盖的移动入口
     *
     * @param string $from 已暂存文件或备份文件的源路径
     * @param string $to 发布或恢复到的目标路径
     * @return bool 文件重命名是否成功
     */
    protected function move($from, $to) { return rename($from, $to); }

    /**
     * 功能说明：递归建立发布目录并登记本次创建的目录
     *
     * @param string $path 需要创建的目标目录，已存在时保留
     * @return bool 目录已存在或成功创建为true，创建失败为false
     */
    protected function directory($path) {
        if($path === $this->root) return true;
        if(is_link($path) || (file_exists($path) && !is_dir($path))) return false;
        if(is_dir($path)) return true;
        if(!$this->directory(dirname($path)) || !mkdir($path, 0755)) return false;
        $this->created[] = $path;
        return true;
    }

    /**
     * 功能说明：发布已暂存成员；失败时尝试恢复被覆盖文件并撤销新目录
     *
     * 普通文件回滚失败时备份目录保留并记录错误，不能保证进程崩溃或外部并发写入下的完整回滚。
     *
     * 参数说明：无显式参数。
     *
     * 异常处理：发布阶段异常在内部触发回滚并返回false；回滚失败保留备份并记录错误，不把已捕获的发布异常重新抛给调用方。
     *
     * @return bool 全部发布成功为true；失败并尝试回滚后为false
     */
    public function commit() {
        try {
            foreach($this->entries as $name => $directory) {
                $target = myFile::extractionPath($this->root, $name);
                if(!$target || ($directory ? !$this->directory($target) : !$this->directory(dirname($target)))) throw new RuntimeException('Invalid extraction target');
                if($directory) continue;
                if(is_dir($target)) throw new RuntimeException('File conflicts with directory');
                $backup = null;
                if(file_exists($target)) {
                    $backup = $this->backup.'/'.count($this->journal);
                    if(!$this->move($target, $backup)) throw new RuntimeException('Cannot back up extraction target');
                }
                $this->journal[] = [$target, $backup, false];
                if(!$this->move($this->stage.'/'.$name, $target)) throw new RuntimeException('Cannot publish extracted file');
                $this->journal[count($this->journal)-1][2] = true;
            }
            $this->journal = []; return true;
        } catch(Throwable $error) {
            foreach(array_reverse($this->journal) as $entry) {
                list($target, $backup, $published) = $entry;
                if($published && is_file($target) && !@unlink($target)) $this->preserve = true;
                if($backup !== null && !@rename($backup, $target)) $this->preserve = true;
            }
            foreach(array_reverse($this->created) as $directory) if(is_dir($directory) && !@rmdir($directory)) $this->preserve = true;
            if($this->preserve) error_log('Archive rollback incomplete; recovery files: '.$this->backup);
            return false;
        }
    }

    /**
     * 功能说明：清理暂存资源并释放目录锁，回滚失败时保留恢复备份
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() {
        if($this->stage && is_dir($this->stage)) myFile::del($this->stage);
        if(!$this->preserve && $this->backup && is_dir($this->backup)) myFile::del($this->backup);
        if(is_resource($this->lock)) { flock($this->lock, LOCK_UN); fclose($this->lock); }
    }
}
