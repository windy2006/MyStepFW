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
  文件上传处理:
  $upload = new myUploader($path, $rename, $banlst);
  $upload->do();
  That's all...
*/
class myUploader extends myBase {
    public
        $result = array();
    protected
        $path = '',
        $counter = 0,
        $rename = false,
        $banlst = '';

    /**
     * 初始化类变量
     * @param string $path
     * @param bool $rename
     * @param string $banlst
     */
    public function init($path = '', $rename = false, $banlst='php,exe,com,bat,pif') {
        if(empty($_FILES)) return;
        if(!empty($path)) {
            $path = myFile::realPath($path);
            if(!myFile::mkdir($path)) {
                $this->error('Operation Failed in Creating Directory '.$path.' , Please Check Your Power!');
            };
        } else {
            $path = './';
        }
        $this->path = $path;
        $this->rename = $rename;
        $this->banlst = $banlst;
        return;
    }

    public function do($getsize = true) {
        $the_path = str_replace(myFile::rootPath(), '/', $this->path);
        foreach($_FILES as $key => $value) {
            if(is_array($value['name'])) {
                if(is_array($value['name'][0])) {
                    $this->error('Structure of FILE is too complex!');
                    exit;
                }
                $max_count = count($value['name']);
                for($i=0; $i<$max_count; $i++) {
                    if($value['error'][$i] == 4) continue;
                    $this->result[$this->counter] = array();
                    $this->result[$this->counter]['idx'] = $key;
                    $this->result[$this->counter]['path'] = $the_path;
                    $this->result[$this->counter]['name'] = $value['name'][$i];
                    $this->result[$this->counter]['type'] = $value['type'][$i];
                    $this->result[$this->counter]['tmp_name'] = $value['tmp_name'][$i];
                    $this->result[$this->counter]['error'] = $value['error'][$i];
                    $this->result[$this->counter]['size'] = $getsize ? myFile::formatSize($value['size'][$i]) : $value['size'][$i];
                    $this->uploadFile();
                }
            } else {
                if($value['error'] == 4) continue;
                $this->result[$this->counter] = array();
                $this->result[$this->counter]['idx'] = $key;
                $this->result[$this->counter]['path'] = $the_path;
                $this->result[$this->counter]['name'] = $value['name'];
                $this->result[$this->counter]['type'] = $value['type'];
                $this->result[$this->counter]['tmp_name'] = $value['tmp_name'];
                $this->result[$this->counter]['error'] = $value['error'];
                $this->result[$this->counter]['size'] = $getsize ? myFile::formatSize($value['size']) : $value['size'];
                $this->uploadFile();
            }
        }
        return;
    }

    private function uploadFile() {
        switch($this->result[$this->counter]['error']) {
            case 0:
                $file_ext = strtolower(pathinfo($this->result[$this->counter]['name'], PATHINFO_EXTENSION));
                if(empty($file_ext) || strpos($this->banlst, $file_ext)!==false) $file_ext = 'upload';
                $this->result[$this->counter]['new_name'] = $this->rename?(getMicrotime().substr(md5($this->result[$this->counter]['size']), 0, 5).'.'.$file_ext):$this->result[$this->counter]['name'];
                if(file_exists($this->path.$this->result[$this->counter]['new_name'])) {
                    $this->result[$this->counter]['message'] = 'The Same-name-file Has Existed In The Upload Path !';
                    $this->result[$this->counter]['error'] = 8;
                    unlink($this->result[$this->counter]['tmp_name']);
                } else {
                    if(filesize($this->result[$this->counter]['tmp_name'])==0) {
                        $this->result[$this->counter]['message'] = 'Upload File Is Zero-length !';
                        $this->result[$this->counter]['error'] = 9;
                    } else {
                        if(move_uploaded_file($this->result[$this->counter]['tmp_name'], $this->path.$this->result[$this->counter]['new_name'])) {
                            $this->result[$this->counter]['message'] = 'Upload Succeeded !';
                        } else {
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
                $this->result[$this->counter]['message'] = 'You can only upload file within the size of '.$_POST['MAX_FILE_SIZE'].' Bytes (MAX_FILE_SIZE by the Supervisor)';
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
        return;
    }

    public function getResult($mode = 1) {
        if($mode == 1) {
            $result = '<table width="700" border="0" align="center" cellpadding="0" cellspacing="1" style="font-size:12px;border:1px black solid;">
                <tr align="center" bgcolor="#999999">
                  <td><b>File Name (Rename)</b></td>
                  <td width="70"><b>File Size</b></td>
                  <td width="160"><b>File Type</b></td>
                  <td><b>Result</b></td>
                </tr>
                ';
            $max_count = count($this->result);
            for($i=0; $i<$max_count; $i++) {
                $result .= '<tr bgcolor="#eeeeee">
                  <td style="padding: 4px"><a href="'.$this->result[$i]['path'].$this->result[$i]['new_name'].'" target="_blank">'.$this->result[$i]['name'].($this->rename?' ('.$this->result[$i]['new_name'].')':'').'</a></td>
                  <td style="padding: 4px">'.$this->result[$i]['size'].'</td>
                  <td style="padding: 4px">'.$this->result[$i]['type'].'</td>
                  <td style="padding: 4px">'.$this->result[$i]['message'].'</td>
                </tr>
                ';
            }
            $result .= '</table>';
        } else {
            $result = $this->result;
        }
        return $result;
    }
}