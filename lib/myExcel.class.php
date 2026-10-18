<?PHP

/**
 * 文件：lib/myExcel.class.php
 * 功能说明：SpreadsheetML格式Excel文档生成器
 *   生成基于XML的工作簿、工作表、样式、行列及单元格，并支持输出/流式写入。
 *
 * 方法目录：
 *   public myExcel::init($file_name='', $sheet_name='') — 初始化工作簿，清空旧数据并创建首张工作表
 *   protected myExcel::validateSheet($name) — 验证 Excel 表名，不截断或静默替换开发者指定的名称
 *   protected myExcel::findSheet($name) — 按大小写不敏感的 Unicode 比较查找工作表
 *   public myExcel::addSheet($sheet_name='', $change_sheet=true) — 添加工作表，未指定名称时自动选取未占用的 sheetN
 *   public myExcel::delSheet($sheet_name) — 删除工作表，仅当前表被删除时切换到剩余首表
 *   public myExcel::resetSheet($sheet_name, $change_sheet=true) — 清空指定工作表的行及列数记录
 *   public myExcel::chgSheet($sheet_name) — 切换当前工作表，不自动创建不存在的表
 *   public myExcel::addRow() — 在当前表追加空行，无当前表时自动创建 sheet1
 *   protected myExcel::cellText($value) — 转为有效 XML 文本，保留文本内容及前导零，拒绝无效 UTF-8
 *   public myExcel::addCells($cells, $idx='') — 向指定行末尾追加单元格，整批验证成功后才更新行内容
 *   protected myExcel::outputCharset($charset) — 规范导出字符集，限定为无需 BOM 的 ASCII 兼容 XML 编码
 *   protected myExcel::xmlText($text, $charset) — 转义 XML 文本/属性并按旧编码需要生成 Unicode 数值实体
 *   protected myExcel::contentChunks($charset) — 按头部、工作表及行生成 XML 块，避免循环拼接完整文档
 *   public myExcel::writeTo($stream, $charset='UTF-8') — 将 XML 分块写入可写流，处理部分写入，不关闭调用方流
 *   public myExcel::getContent($charset='UTF-8') — 获取完整 XML 字符串，与 writeTo() 共用编码及生成逻辑
 *   public myExcel::make($charset='UTF-8') — 生成下载响应并结束请求，先完成临时流后才发送头部
 *
 * 使用说明：
 *   1. 生成的是SpreadsheetML XML，不是XLSX ZIP格式；扩展名和响应头按使用场景选择。
 *   2. 按工作表、行、单元格顺序添加内容，使用getContent/writeTo/make输出。
 *   3. 大量数据可使用流式方式，避免一次性在内存组装完整工作簿。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：生成基于XML的工作簿、工作表、样式、行列及单元格，并支持输出/流式写入。
 *
 * 使用说明：
 *   1. 生成的是SpreadsheetML XML，不是XLSX ZIP格式；扩展名和响应头按使用场景选择。
 *   2. 按工作表、行、单元格顺序添加内容，使用getContent/writeTo/make输出。
 *   3. 大量数据可使用流式方式，避免一次性在内存组装完整工作簿。
 *
 * 补充说明：
 * XML Spreadsheet 2003 工作簿生成器，保留旧 .xls 下载入口（不是二进制 XLS/XLSX）
 *
 *     $xls = new myExcel('export', 'sheet1');
 *     $xls->addRow(); $xls->addCells(['标题', '0012']);
 *     $xls->addCells('补充', 0);          // 显式第 0 行不再被当作缺省
 *     $xls->writeTo($stream);            // 分块写入调用方提供的流，不关闭它
 *     $xml = $xls->getContent();          // 取完整文本，默认 UTF-8
 *     $xls->make();                      // 下载并结束请求，文件名保留 .xls 兼容
 *
 * 单元格保留文本类型，不把以 =/+/-/@ 开头的文本自动转换为公式；不添加尾空格。
 * 工作表名称按 Excel 规则验证、大小写不敏感地去重。输入文本要求有效 UTF-8，移除
 * XML 1.0 禁用控制字符；行/列上限 65536/256，单元格上限 32767 个 UTF-16 单元。
 * writeTo() 避免同时建立完整输出副本，但工作簿行数据仍在内存中，不是无限量导出接口。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myExcel.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myExcel extends myBase {
    protected $workSheets = [], $cur_sheet = '', $file = '';
    /** @var array 各工作表最大列数，追加时更新，输出时无需扫描全部单元格 */
    protected $sheet_columns = [];

    /**
     * 功能说明：初始化工作簿，清空旧数据并创建首张工作表
     *
     * @param string $file_name 下载文件名，空字符串使用 export；响应时另做路径/头清理（默认：''）
     * @param string $sheet_name 首张表名，空字符串使用 sheet1；字符串 0 是有效名称（默认：''）
     * @return void 无返回值。
     * @throws InvalidArgumentException 文件名或表名类型、编码及格式非法
     */
    public function init($file_name='', $sheet_name='') {
        if(!is_string($file_name) || !is_string($sheet_name)) throw new InvalidArgumentException('Excel names must be strings');
        if($file_name === '') $file_name = 'export';
        if($sheet_name === '') $sheet_name = 'sheet1';
        $this->validateSheet($sheet_name);
        if(!preg_match('//u', $file_name)) throw new InvalidArgumentException('Excel filename must be UTF-8');
        $this->workSheets = []; $this->sheet_columns = []; $this->cur_sheet = ''; $this->file = $file_name;
        $this->addSheet($sheet_name);
    }

    /**
     * 功能说明：验证 Excel 表名，不截断或静默替换开发者指定的名称
     *
     * @param string $name 有效 UTF-8 名称，1 到 31 个 Unicode 字符
     * @return void 名称不得含控制符、[]:*?/反斜杠或以单引号开头/结尾
     * @throws InvalidArgumentException 名称不符合约束
     */
    protected function validateSheet($name) {
        if(!is_string($name) || $name === '' || !preg_match('//u', $name) || preg_match('/[\x00-\x1f\x7f\[\]:*?\/\\\\]/u', $name) || substr($name,0,1) === "'" || substr($name,-1) === "'" || preg_match_all('/./us', $name) > 31) throw new InvalidArgumentException('Invalid Excel worksheet name');
    }

    /**
     * 功能说明：按大小写不敏感的 Unicode 比较查找工作表
     *
     * @param string $name 表名
     * @return string|int|false 原始存储键，不存在为 false；数字名称可能为整数键
     */
    protected function findSheet($name) {
        if(!is_string($name) || !preg_match('//u', $name)) return false;
        if(array_key_exists($name, $this->workSheets)) return $name;
        foreach($this->workSheets as $key => $rows) if(preg_match('/\A'.preg_quote($name, '/').'\z/iu', (string)$key)) return $key;
        return false;
    }

    /**
     * 功能说明：添加工作表，未指定名称时自动选取未占用的 sheetN
     *
     * @param string $sheet_name 新表名，空字符串自动命名；大小写重复时不新增、不切表（默认：''）
     * @param bool $change_sheet 新增成功后是否切换当前表，默认 true（默认：true）
     * @return string 已存在或新建的表名；可供后续 chgSheet() 使用
     * @throws InvalidArgumentException 表名非法
     */
    public function addSheet($sheet_name='', $change_sheet=true) {
        if($sheet_name === '') { $i=1; do { $sheet_name='sheet'.$i++; } while($this->findSheet($sheet_name) !== false); }
        $this->validateSheet($sheet_name); $existing = $this->findSheet($sheet_name);
        if($existing !== false) return (string)$existing;
        $this->workSheets[$sheet_name] = []; $this->sheet_columns[$sheet_name] = 0;
        if($change_sheet) $this->cur_sheet = $sheet_name;
        return $sheet_name;
    }

    /**
     * 功能说明：删除工作表，仅当前表被删除时切换到剩余首表
     *
     * @param string $sheet_name 表名，大小写不敏感
     * @return bool 原表存在并删除为 true；删完后当前表为空，输出仍生成一个空 sheet1
     */
    public function delSheet($sheet_name) {
        $key = $this->findSheet($sheet_name); if($key === false) return false;
        unset($this->workSheets[$key], $this->sheet_columns[$key]);
        if((string)$key === $this->cur_sheet) $this->cur_sheet = $this->workSheets ? (string)array_key_first($this->workSheets) : '';
        return true;
    }

    /**
     * 功能说明：清空指定工作表的行及列数记录
     *
     * @param string $sheet_name 已存在表名，大小写不敏感
     * @param bool $change_sheet true 同时切换到该表（默认：true）
     * @return bool 表存在并已清空为 true，不存在为 false
     */
    public function resetSheet($sheet_name, $change_sheet=true) {
        $key = $this->findSheet($sheet_name); if($key === false) return false;
        $this->workSheets[$key] = []; $this->sheet_columns[$key] = 0;
        if($change_sheet) $this->cur_sheet = (string)$key;
        return true;
    }

    /**
     * 功能说明：切换当前工作表，不自动创建不存在的表
     *
     * @param string $sheet_name 已存在表名，大小写不敏感
     * @return bool 切换成功为 true，不存在为 false
     */
    public function chgSheet($sheet_name) {
        $key = $this->findSheet($sheet_name); if($key === false) return false;
        $this->cur_sheet = (string)$key; return true;
    }

    /**
     * 功能说明：在当前表追加空行，无当前表时自动创建 sheet1
     *
     * 参数说明：无显式参数。
     *
     * @return int 新行的零起始索引
     * @throws LengthException 超过 XML Spreadsheet 2003 的 65536 行限制
     */
    public function addRow() {
        if($this->cur_sheet === '' || !array_key_exists($this->cur_sheet, $this->workSheets)) $this->addSheet();
        $index = count($this->workSheets[$this->cur_sheet]);
        if($index >= 65536) throw new LengthException('Excel worksheet row limit exceeded');
        $this->workSheets[$this->cur_sheet][] = []; return $index;
    }

    /**
     * 功能说明：转为有效 XML 文本，保留文本内容及前导零，拒绝无效 UTF-8
     *
     * @param scalar|null|object $value 标量、null 或实现 __toString() 的对象
     * @return string null/false 为空字符串，true 为 1；禁用 XML 控制符移除
     * @throws InvalidArgumentException 值类型、浮点数或编码非法
     * @throws LengthException 单元格超过 32767 个 UTF-16 单元
     */
    protected function cellText($value) {
        if((!is_scalar($value) && $value !== null && !(is_object($value) && method_exists($value, '__toString'))) || is_float($value) && !is_finite($value)) throw new InvalidArgumentException('Excel cells must contain finite scalar text');
        $text = (string)$value;
        if(!preg_match('//u', $text)) throw new InvalidArgumentException('Excel cell text must be UTF-8');
        $text = preg_replace('/[^\x09\x0a\x0d\x20-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]/u', '', $text);
        $units = preg_match_all('/./us', $text) + preg_match_all('/[\x{10000}-\x{10ffff}]/u', $text);
        if($units > 32767) throw new LengthException('Excel cell text limit exceeded');
        return $text;
    }

    /**
     * 功能说明：向指定行末尾追加单元格，整批验证成功后才更新行内容
     *
     * @param scalar|null|object|array $cells 单个单元格或一行值，数组按值顺序追加，不保留键
     * @param int|string|null $idx 零起始行号；空字符串/null 使用末行，无行时自动创建；0 有效（默认：''）
     * @return int 本次写入的行号；显式不存在的行号不会回退覆盖末行
     * @throws InvalidArgumentException 索引或单元格类型/编码非法
     * @throws OutOfBoundsException 显式行号不存在
     * @throws LengthException 超过 256 列或单元格长度上限
     */
    public function addCells($cells, $idx='') {
        $values = is_array($cells) ? array_values($cells) : [$cells];
        $normalized = []; foreach($values as $value) $normalized[] = $this->cellText($value);
        if(count($normalized) > 256) throw new LengthException('Excel worksheet column limit exceeded');
        if($idx !== '' && $idx !== null && (!is_int($idx) && !(is_string($idx) && preg_match('/\A(?:0|[1-9]\d*)\z/D', $idx)) || (int)$idx < 0)) throw new InvalidArgumentException('Invalid Excel row index');
        $automatic = $idx === '' || $idx === null;
        if(!$automatic && ($this->cur_sheet === '' || !isset($this->workSheets[$this->cur_sheet][(int)$idx]))) throw new OutOfBoundsException('Excel row does not exist');
        if($automatic) {
            if($this->cur_sheet === '' || empty($this->workSheets[$this->cur_sheet])) $this->addRow();
            $idx = count($this->workSheets[$this->cur_sheet])-1;
        } else $idx = (int)$idx;
        $width = count($this->workSheets[$this->cur_sheet][$idx]) + count($normalized);
        if($width > 256) throw new LengthException('Excel worksheet column limit exceeded');
        $this->workSheets[$this->cur_sheet][$idx] = array_merge($this->workSheets[$this->cur_sheet][$idx], $normalized);
        $this->sheet_columns[$this->cur_sheet] = max($this->sheet_columns[$this->cur_sheet], $width);
        return $idx;
    }

    /**
     * 功能说明：规范导出字符集，限定为无需 BOM 的 ASCII 兼容 XML 编码
     *
     * @param string $charset UTF-8/UTF8、GBK、GB2312、GB18030、BIG5、ASCII/US-ASCII、ISO-8859-1、Windows-1252
     * @return string 规范编码标签；旧编码将非 ASCII 字符表示为数值实体，避免丢失 Unicode
     * @throws InvalidArgumentException 不是支持的编码，不允许控制符及任意头部内容
     */
    protected function outputCharset($charset) {
        if(!is_string($charset)) throw new InvalidArgumentException('Invalid Excel charset');
        $charset = strtoupper($charset);
        if($charset === 'UTF8') $charset = 'UTF-8';
        if($charset === 'ASCII') $charset = 'US-ASCII';
        if(!in_array($charset, ['UTF-8','GBK','GB2312','GB18030','BIG5','US-ASCII','ISO-8859-1','WINDOWS-1252'], true)) throw new InvalidArgumentException('Unsupported Excel charset');
        return $charset;
    }

    /**
     * 功能说明：转义 XML 文本/属性并按旧编码需要生成 Unicode 数值实体
     *
     * @param string $text 已验证的 UTF-8 文本
     * @param string $charset outputCharset() 返回的编码标签
     * @return string 可直接放入 XML 的内容，CR/LF/TAB 用实体保留原值
     */
    protected function xmlText($text, $charset) {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = str_replace(["\r", "\n", "\t"], ['&#13;', '&#10;', '&#9;'], $text);
        if($charset !== 'UTF-8') $text = preg_replace_callback('/[^\x00-\x7f]/u', function($match) {
            $bytes = array_values(unpack('C*', $match[0])); $length = count($bytes);
            $point = $bytes[0] & (0x7f >> $length);
            for($i=1; $i<$length; $i++) $point = ($point << 6) | ($bytes[$i] & 0x3f);
            return '&#'.$point.';';
        }, $text);
        return $text;
    }

    /**
     * 功能说明：按头部、工作表及行生成 XML 块，避免循环拼接完整文档
     *
     * @param string $charset 已规范的输出字符集
     * @return Generator 每次产生一个文本块；空工作簿生成默认空 sheet1
     */
    protected function contentChunks($charset) {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        yield '<?xml version="1.0" encoding="'.$charset.'"?>'."\n".'<?mso-application progid="Excel.Sheet"?>'."\n";
        yield '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><o:DocumentProperties><o:Author>MyStep Framework</o:Author><o:Created>'.$now.'</o:Created></o:DocumentProperties><Styles><Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Times New Roman" ss:Size="12"/></Style></Styles>'."\n";
        $sheets = $this->workSheets ?: ['sheet1'=>[]];
        foreach($sheets as $name => $rows) {
            $nameXml = $this->xmlText((string)$name, $charset); $width = $this->sheet_columns[$name] ?? 0;
            $dimensions = $rows ? ' ss:ExpandedRowCount="'.count($rows).'"' : '';
            if($width > 0) $dimensions .= ' ss:ExpandedColumnCount="'.$width.'"';
            yield '<Worksheet ss:Name="'.$nameXml.'"><Table'.$dimensions.' ss:DefaultColumnWidth="54" ss:DefaultRowHeight="14.25">'."\n";
            foreach($rows as $row) {
                $xml = '<Row>';
                foreach($row as $cell) $xml .= '<Cell><Data ss:Type="String" xml:space="preserve">'.$this->xmlText($cell, $charset).'</Data></Cell>';
                yield $xml.'</Row>'."\n";
            }
            $selected = (string)$name === $this->cur_sheet || !$this->workSheets ? '<Selected/>' : '';
            yield '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'.$selected.'<ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios></WorksheetOptions></Worksheet>'."\n";
        }
        yield '</Workbook>';
    }

    /**
     * 功能说明：将 XML 分块写入可写流，处理部分写入，不关闭调用方流
     *
     * @param resource $stream 已打开的可写流，从当前位置开始写，不回卷
     * @param string $charset 输出编码，默认 UTF-8；其他支持编码用数值实体保留 Unicode（默认：'UTF-8'）
     * @return int 实际写入的字节数
     * @throws InvalidArgumentException 流不是资源或字符集不支持
     * @throws RuntimeException 写入失败或连续零字节写入
     */
    public function writeTo($stream, $charset='UTF-8') {
        if(!is_resource($stream) || get_resource_type($stream) !== 'stream') throw new InvalidArgumentException('Excel target must be a writable stream');
        $charset = $this->outputCharset($charset); $written = 0;
        foreach($this->contentChunks($charset) as $chunk) {
            $length = strlen($chunk); $offset = 0;
            while($offset < $length) {
                $bytes = @fwrite($stream, substr($chunk, $offset));
                if($bytes === false || $bytes === 0) throw new RuntimeException('Cannot write Excel stream');
                $offset += $bytes; $written += $bytes;
            }
        }
        return $written;
    }

    /**
     * 功能说明：获取完整 XML 字符串，与 writeTo() 共用编码及生成逻辑
     *
     * @param string $charset 输出编码，默认 UTF-8，保留旧无参数调用（默认：'UTF-8'）
     * @return string 完整 XML；大导出优先 writeTo()，避免同时保留完整结果副本
     */
    public function getContent($charset='UTF-8') {
        $content = '';
        foreach($this->contentChunks($this->outputCharset($charset)) as $chunk) $content .= $chunk;
        return $content;
    }

    /**
     * 功能说明：生成下载响应并结束请求，先完成临时流后才发送头部
     *
     * @param string $charset 输出编码，默认 UTF-8；显式 GBK 等通过数值实体无损兼容（默认：'UTF-8'）
     * @return void 输出 XML 内容并 exit；兼容 .xls 扩展名，不生成二进制 XLS/XLSX
     * @throws RuntimeException 已发送头部、输出缓冲非空、无法创建/写入临时流
     * @throws InvalidArgumentException 字符集不受支持
     */
    public function make($charset='UTF-8') {
        $charset = $this->outputCharset($charset);
        if(headers_sent()) throw new RuntimeException('Excel download headers have already been sent');
        foreach(ob_get_status(true) as $buffer) if($buffer['buffer_used'] > 0) throw new RuntimeException('Excel download requires empty output buffers');
        $stream = fopen('php://temp/maxmemory:2097152', 'w+b');
        if($stream === false) throw new RuntimeException('Cannot create Excel download stream');
        try {
            $size = $this->writeTo($stream, $charset); rewind($stream);
            $name = basename(str_replace('\\', '/', $this->file));
            $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
            if($name === '' || $name === '.' || $name === '..') $name = 'export';
            if(!preg_match('/\.xls\z/i', $name)) $name .= '.xls';
            $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
            header('Content-Type: application/vnd.ms-excel; charset='.$charset);
            header('Content-Disposition: attachment; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($name));
            header('Cache-Control: private, no-store');
            $plain = !ini_get('zlib.output_compression') && !ini_get('output_handler');
            foreach(ob_get_status(true) as $buffer) if($buffer['name'] !== 'default output handler') $plain = false;
            if($plain) header('Content-Length: '.$size);
            if(fpassthru($stream) === false) throw new RuntimeException('Cannot send Excel download');
        } finally { fclose($stream); }
        exit;
    }
}
