<?PHP
/********************************************
*                                           *
* Name    : Upload Manager                  *
* Modifier: Windy2000                       *
* Time    : 2003-05-10                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myUploader.class.php
 * 功能说明：HTTP文件上传与附件落盘工具
 *   遍历单个/嵌套上传字段，校验真实上传、大小/扩展名并生成落盘结果。
 *
 * 方法目录：
 *   public myUploader::setLimits($bytes = 33554432, $allowed = []) — 设置单文件上传大小及允许的扩展名
 *   public myUploader::init($path = '', $rename = false, $banlst=[]) — 初始化上传目录、改名策略和禁止扩展名列表
 *   public myUploader::setBan($list) — 追加禁止扩展名，合并到内置隔离规则
 *   public myUploader::do($getsize = true) — 遍历单个或嵌套上传字段，逐项上传并收集结果
 *   private myUploader::uploadFile() — 校验当前上传项并落盘，写入本项成功或失败结果
 *   public myUploader::result($html = false) — 取得结构化上传结果或HTML结果表
 *
 * 使用说明：
 *   1. 配置目标目录、改名策略和扩展名规则后执行do，再读取result。
 *   2. 危险后缀会被隔离为upload形式；具体业务仍应设置允许扩展名。
 *   3. 本类完成上传落盘，附件可下载索引由myAttachment/框架上传入口登记。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：遍历单个/嵌套上传字段，校验真实上传、大小/扩展名并生成落盘结果。
 *
 * 使用说明：
 *   1. 配置目标目录、改名策略和扩展名规则后执行do，再读取result。
 *   2. 危险后缀会被隔离为upload形式；具体业务仍应设置允许扩展名。
 *   3. 本类完成上传落盘，附件可下载索引由myAttachment/框架上传入口登记。
 *
 * 使用示例：
 *     $upload = new myUploader($directory, true);
 *     $upload->setLimits(8388608, ['jpg', 'png']);
 *     $upload->do(); $result = $upload->result();
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myUploader.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myUploader extends myBase {
    public
        $result = array();
    protected
        $path = '',
        $counter = 0,
        $rename = false,
        $banlst = ['php','exe','com','bat','pif'];

    protected $max_bytes = 33554432, $allowed = [];

    /**
     * 功能说明：设置单文件上传大小及允许的扩展名
     *
     * @param int $bytes 最大允许处理的字节数，必须为正整数（默认：33554432）
     * @param array|string $allowed 允许上传的扩展名列表或逗号分隔文本（默认：[]）
     * @return $this 返回当前上传器，便于连续设置
     * @throws InvalidArgumentException 大小上限非法或扩展名列表类型不正确
     */
    public function setLimits($bytes = 33554432, $allowed = []) {
        if(!is_int($bytes) || $bytes <= 0) throw new InvalidArgumentException('Upload size must be positive');
        if(is_string($allowed)) $allowed = array_filter(array_map('trim', explode(',', $allowed)));
        if(!is_array($allowed)) throw new InvalidArgumentException('Upload extensions must be an array or CSV');
        $this->max_bytes = $bytes; $this->allowed = array_map('strtolower', $allowed);
        return $this;
    }

    /**
     * 功能说明：初始化上传目录、改名策略和禁止扩展名列表
     *
     * @param string $path 目标目录或路径（默认：''）
     * @param bool $rename 是否为上传文件生成新的唯一名称（默认：false）
     * @param array $banlst 附加禁止上传的扩展名列表（默认：[]）
     * @return void 无返回值。
     */
    public function init($path = '', $rename = false, $banlst=[]) {
        if(!empty($path)) {
            $path = myFile::realPath($path);
            if(!myFile::mkdir($path)) {
                $this->error('Operation Failed in Creating Directory '.$path.' , Please Check Your Power!');
            };
        } else {
            $path = './';
        }
        $this->path = rtrim($path, '/').'/';
        $this->rename = $rename;
        $this->setBan($banlst);
    }

    /**
     * 功能说明：追加禁止扩展名，合并到内置隔离规则
     *
     * @param array|string $list 追加禁止扩展名数组或逗号分隔文本
     * @return void 无返回值。
     */
    public function setBan($list) {
        if(is_string($list)) {
            $list = str_replace(' ', '', $list);
            $list = explode(',', $list);
        }
        $list = array_map('strtolower', $list);
        $this->banlst = array_merge($this->banlst, ['phtml','pht','phar','php3','php4','php5','php7','php8','cgi','pl','sh','htaccess'], $list);
        $this->banlst = array_unique($this->banlst);
    }

    /**
     * 功能说明：遍历单个或嵌套上传字段，逐项上传并收集结果
     *
     * @param bool $getsize 结果中的size是否格式化为可读容量（默认：true）
     * @return void 无返回值。
     */
    public function do($getsize = true) {
        $the_path = str_replace(myFile::rootPath(), '/', $this->path);
        $visit = function($entry, $key) use (&$visit, $the_path, $getsize) {
            if(!is_array($entry) || !array_key_exists('name', $entry)) return;
            if(is_array($entry['name'])) {
                foreach($entry['name'] as $index => $name) {
                    $child = [];
                    foreach(['name','type','tmp_name','error','size'] as $field) $child[$field] = is_array($entry[$field] ?? null) ? ($entry[$field][$index] ?? null) : null;
                    $visit($child, $key);
                }
                return;
            }
            if(($entry['error'] ?? null) === UPLOAD_ERR_NO_FILE) return;
            $valid = is_string($entry['name']) && is_string($entry['tmp_name'] ?? null) && is_int($entry['error'] ?? null) && is_numeric($entry['size'] ?? null);
            $size = $valid ? max(0, $entry['size']) : 0;
            $this->result[$this->counter] = [
                'idx'=>$key, 'path'=>$the_path, 'name'=>is_string($entry['name']) ? $entry['name'] : '',
                'type'=>is_string($entry['type'] ?? null) ? $entry['type'] : '',
                'tmp_name'=>$valid ? $entry['tmp_name'] : '', 'error'=>$valid ? $entry['error'] : 10,
                'size'=>$getsize ? myFile::formatSize($size) : $size,
            ];
            $this->uploadFile();
        };
        foreach($_FILES as $key => $entry) $visit($entry, $key);
    }
    /**
     * 功能说明：校验当前上传项并落盘，写入本项成功或失败结果
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private function uploadFile() {
        switch($this->result[$this->counter]['error']) {
            case 0:
                $name = basename(str_replace('\\', '/', $this->result[$this->counter]['name']));
                $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
                $name = rtrim($name, '. ');
                if($name === '' || $name[0] === '.') $name = 'file'.bin2hex(random_bytes(6));
                $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $blocked = empty($file_ext) || in_array($file_ext, $this->banlst, true) || preg_match('/^php[0-9]*$/D', $file_ext);
                if($blocked) $file_ext = 'upload';
                $this->result[$this->counter]['new_name'] = $this->rename
                    ? (time().'.'.bin2hex(random_bytes(12)).'.'.$file_ext)
                    : ($blocked ? $name.'.upload' : $name);
                if(!is_uploaded_file($this->result[$this->counter]['tmp_name'])) {
                    $this->result[$this->counter]['error'] = 10;
                    $this->result[$this->counter]['message'] = 'Invalid HTTP upload !';
                    break;
                }
                $temporary = $this->result[$this->counter]['tmp_name'];
                if(filesize($temporary) > $this->max_bytes || ($this->allowed && !in_array($file_ext, $this->allowed, true))) {
                    $this->result[$this->counter]['error'] = 10;
                    $this->result[$this->counter]['message'] = 'File exceeds configured size or extension policy !';
                    break;
                }
                if(in_array($file_ext, ['jpg','jpeg','png','gif','webp','bmp'], true)) {
                    $info = myImg::inspect($temporary);
                    $types = ['jpg'=>IMAGETYPE_JPEG, 'jpeg'=>IMAGETYPE_JPEG, 'png'=>IMAGETYPE_PNG, 'gif'=>IMAGETYPE_GIF, 'webp'=>IMAGETYPE_WEBP, 'bmp'=>IMAGETYPE_BMP];
                    if(!$info || $info[2] !== $types[$file_ext]) {
                        $this->result[$this->counter]['error'] = 10;
                        $this->result[$this->counter]['message'] = 'Invalid image content or image resource limit exceeded !';
                        break;
                    }
                }
                if(file_exists($this->path.$this->result[$this->counter]['new_name'])) {
                    $this->result[$this->counter]['message'] = 'The Same-name-file Has Existed In The Upload Path !';
                    $this->result[$this->counter]['error'] = 8;
                    // PHP owns the temporary upload.
                } else {
                    if(filesize($this->result[$this->counter]['tmp_name'])==0) {
                        $this->result[$this->counter]['message'] = 'Upload File Is Zero-length !';
                        $this->result[$this->counter]['error'] = 9;
                    } else {
                        $target = $this->path.$this->result[$this->counter]['new_name'];
                        $reservation = @fopen($target, 'x');
                        if(!$reservation) {
                            $this->result[$this->counter]['error'] = 8;
                            $this->result[$this->counter]['message'] = 'Upload destination already exists or cannot be created !';
                            break;
                        }
                        fclose($reservation);
                        if(move_uploaded_file($this->result[$this->counter]['tmp_name'], $target)) {
                            $this->result[$this->counter]['message'] = 'Upload Succeeded !';
                        } else {
                            unlink($target);
                            $this->result[$this->counter]['message'] = 'Upload Failed In Moving File !';
                            $this->result[$this->counter]['error'] = 10;
                        }
                    }
                }
                break;
            case 1:
                $this->result[$this->counter]['message'] = 'You can only upload file within the size of '.ini_get('upload_max_filesize').' ( upload_max_filesize in php.ini ) !';
                break;
            case 2:
                $this->result[$this->counter]['message'] = 'You can only upload file within the size of '.($_POST['MAX_FILE_SIZE'] ?? ini_get('upload_max_filesize')).' Bytes (MAX_FILE_SIZE by the Supervisor)';
                break;
            case 3:
                $this->result[$this->counter]['message'] = 'Upload finished incompletely !';
                break;
            case 4:
                $this->result[$this->counter]['message'] = 'No File has been upload !';
                break;
            case 5:
                $this->result[$this->counter]['message'] = 'Empty File !';
                break;
            case 6:
                $this->result[$this->counter]['message'] = 'Temporary directory error !';
                break;
            case 7:
                $this->result[$this->counter]['message'] = 'File cannot be written !';
                break;
            default:
                $this->result[$this->counter]['message'] = 'Unknown error !';
        }
        $this->counter++;
    }

    /**
     * 功能说明：取得结构化上传结果或HTML结果表
     *
     *
     * 调用示例：
     *     $uploader->do();
     *     foreach ($uploader->result() as $item) {
     *         // 按error/message/new_name处理各项
     *     }
     *
     * @param bool $html true返回HTML结果表，false返回结构化上传结果数组（默认：false）
     * @return array|string html为false返回结果数组，为true返回HTML表格
     */
    public function result($html = false) {
        if($html) {
            $result = '<h4 style="text-align: center">Total '.$this->counter.' File(s) Uploaded!</h4>';
            $result .= '<table width="700" border="0" align="center" cellpadding="0" cellspacing="1" style="font-size:12px;border:1px black solid;">
                <tr align="center" bgcolor="#999999">
                  <td><b>File Name (Rename)</b></td>
                  <td width="70"><b>File Size</b></td>
                  <td width="160"><b>File Type</b></td>
                  <td><b>Result</b></td>
                </tr>
                ';
            for($i=0; $i<$this->counter; $i++) {
                $row = [];
                foreach($this->result[$i] as $key => $value) $row[$key] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                $row['new_name'] = $row['new_name'] ?? '';
                $result .= '<tr bgcolor="#eeeeee">
                  <td style="padding: 4px"><a href="'.$row['path'].$row['new_name'].'" target="_blank" title="'.($this->rename?$row['new_name']:'').'">'.$row['name'].'</a></td>
                  <td style="padding: 4px;text-align: right;">'.$row['size'].'</td>
                  <td style="padding: 4px;text-align: center;">'.$row['type'].'</td>
                  <td style="padding: 4px;text-align: center;">'.$row['message'].'</td>
                </tr>
                ';
            }
            $result .= '</table>';
            $result .= '<div>&nbsp;</div>';
        } else {
            $result = $this->result;

        }
        return $result;
    }
}