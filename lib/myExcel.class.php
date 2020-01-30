<?php
/********************************************
*                                           *
* Name    : Excel File Builder              *
* Modifier: Windy2000                       *
* Time    : 2004-08-08                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
  Excel 表格生成:
        $xls->init($file_name, $sheet_name)                     // Set the Database Class
        $xls->addSheet($sheet_name, $change_sheet)              // add new sheet
        $xls->delSheet($sheet_name)                             // delete sheet
        $xls->resetSheet($sheet_name, $change_sheet)            // empty sheet data
        $xls->chgSheet($sheet_name)                             // change working sheet
        $xls->addRow()                                          // add a new working row to working sheet
        $xls->addCells($cells, $idx)                            // add data to working row
        $xls->getContent()                                      // get xls file content
        $xls->make()                                            // push content to browser
*/
class myExcel extends myBase {
    protected
        $workSheets = array(),
        $cur_sheet = '',
        $file = '';

    /**
     * 变量初始化
     * @param string $file_name
     * @param string $sheet_name
     */
    public function init($file_name='', $sheet_name='') {
        if(empty($file_name)) $file_name = 'export';
        if(empty($sheet_name)) $sheet_name = 'sheet1';
        $this->workSheets = array();
        $this->addSheet($sheet_name);
        $this->cur_sheet = $sheet_name;
        $this->file = $file_name;
    }

    /**
     * 添加新表
     * @param string $sheet_name
     * @param bool $change_sheet
     */
    public function addSheet($sheet_name='', $change_sheet=true) {
        if(!isset($this->workSheets[$sheet_name])) {
            if(empty($sheet_name)) {
                $i = 1;
                while(isset($this->workSheets['sheet'.$i])) {
                    $i++;
                }
                $sheet_name = 'sheet'.$i;
            }
            $this->workSheets[$sheet_name] = array();
            if($change_sheet) $this->chgSheet($sheet_name);
        }
    }

    /**
     * 删除现存表
     * @param $sheet_name
     */
    public function delSheet($sheet_name) {
        if(isset($this->workSheets[$sheet_name])) {
            unset($this->workSheets[$sheet_name]);
            $this->cur_sheet = count($this->workSheets)>0 ? reset($this->workSheets) : '';
        }
    }

    /**
     * 清空表数据
     * @param $sheet_name
     * @param bool $change_sheet
     */
    public function resetSheet($sheet_name, $change_sheet=true) {
        if(isset($this->workSheets[$sheet_name])) {
            $this->workSheets[$sheet_name] = array();
            if($change_sheet) $this->chgSheet($sheet_name);
        }
    }

    /**
     * 变更操作表
     * @param $sheet_name
     */
    public function chgSheet($sheet_name) {
        if(isset($this->workSheets[$sheet_name])) $this->cur_sheet = $sheet_name;
    }

    /**
     * 添加新行
     */
    public function addRow() {
        if(empty($this->cur_sheet) || !isset($this->workSheets[$this->cur_sheet])) return;
        array_push($this->workSheets[$this->cur_sheet], array());
    }

    /**
     * 添加行数据
     * @param $cells
     * @param string $idx
     */
    public function addCells($cells, $idx='') {
        if(empty($this->cur_sheet) || !isset($this->workSheets[$this->cur_sheet])) return;
        if(empty($idx) || !isset($this->workSheets[$this->cur_sheet][$idx])) $idx = count($this->workSheets[$this->cur_sheet])-1;
        if(is_array($cells)) {
            $cells_new = array_values($cells);
            $this->workSheets[$this->cur_sheet][$idx] = array_merge($this->workSheets[$this->cur_sheet][$idx], $cells_new);
        } else {
            array_push($this->workSheets[$this->cur_sheet][$idx], $cells);
        }
    }

    /**
     * 生成数据表源代码
     * @return string
     */
    public function getContent() {
        $now = date('Y-m-d').'T'.date('H:i:s').'Z';
        $content = '';
        $content .= <<<CODE
<?xml version="1.0" encoding="gb2312"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>MyStep Framework</Author>
  <LastAuthor>windy2006@gmail.com</LastAuthor>
  <Created>{$now}</Created>
  <Company>Homebrew</Company>
  <Version>11.5606</Version>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>9000</WindowHeight>
  <WindowWidth>11700</WindowWidth>
  <WindowTopX>240</WindowTopX>
  <WindowTopY>15</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Times New Roman" x:CharSet="134" ss:Size="12"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
 </Styles>
CODE;
        foreach($this->workSheets as $key => $value) {
            $count_rows = count($value);
            if($count_rows<=0) continue;
            $count_cells = 0;
            for($i=0;$i<$count_rows;$i++) {
                if(count($value[$i])>$count_cells) $count_cells = count($value[$i]);
            }
            $content .= <<<CODE

 <Worksheet ss:Name='{$key}'>
  <Table ss:ExpandedColumnCount='{$count_cells}' ss:ExpandedRowCount='{$count_rows}' x:FullColumns='1'
   x:FullRows='1' ss:DefaultColumnWidth='54' ss:DefaultRowHeight='14.25'>

CODE;
            for($i=0; $i<$count_rows; $i++) {
                $content .= '   <Row>'.chr(13).chr(10);
                for($j=0, $m=count($value[$i]); $j<$m; $j++) {
                    $value[$i][$j] = htmlspecialchars($value[$i][$j]);
                    $value[$i][$j] = str_replace(chr(13), '', $value[$i][$j]);
                    $value[$i][$j] = str_replace(chr(10), '&#10;', $value[$i][$j]);
                    $content .= '        <Cell><Data ss:Type="String">'.$value[$i][$j].' </Data></Cell>'.chr(13).chr(10);
                }
                $content .= '   </Row>'.chr(13).chr(10);
            }
            $content .= <<<CODE
  </Table>
  <WorksheetOptions xmlns='urn:schemas-microsoft-com:office:excel'>
   <Selected/>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>

CODE;
        }
        $content .= '</Workbook>';
        return $content;
    }

    /**
     * 生成数据表文件
     * @param string $charset
     */
    public function make($charset = 'gbk') {
        $content = $this->getContent();
        header('Content-type: application/vnd.ms-excel; charset='.$charset);
        header('Accept-Ranges: bytes');
        header('Accept-Length: '.strlen($content));
        header('Content-Disposition: attachment; filename='.$this->file.'.xls');
        echo myString::setCharset($content, $charset);
        exit();
    }
}
