<?PHP
class myPack {
    protected
        $file_count        = 0,
        $file_ignore    = array(),        // ignore these files when packing
        $pack_file        = "pack.bin",        // the file name of packed file
        $file_list    = array(),        // only files in the list will be pack
        $pack_dir        = "./",            // the directory of pack or unpack to
        $pack_fp        = null,
        $pack_result    = array(),
        $charset = array();

    public function __construct($pack_dir = "./", $pack_file = "pack.pkg") {
        $this->pack_dir        = str_replace("//", "/", $pack_dir);
        $this->pack_file    = $pack_file;
        return;
    }

    public function AddIgnore() {
        $args_list = func_get_args();
        $this->file_ignore += $args_list;
        return;
    }

    public function setCharset($from, $to, $file_ext="") {
        $this->charset['from'] = $from;
        $this->charset['to'] = $to;
        $this->charset['file_ext'] = $file_ext;
        return;
    }

    public function AddFile() {
        $args_list = func_get_args();
        $this->file_list += $args_list;
        return;
    }

    protected function PackFileList($separator="|") {
        for($i=0,$m=count($this->file_list); $i<$m; $i++) {
            $this->PackFile($this->file_list[$i], $separator);
        }
        return;
    }

    protected function PackFile($dir=".", $separator="|") {
        $dir = str_replace("//", "/", $dir);

        $ignore = array();
        if(is_file($dir."/ignore")) {
            $ignore = file_get_contents($dir."/ignore");
            if(strlen($ignore)==0) return;
            $ignore = str_replace("\r", "", $ignore);
            $ignore = explode("\n", $ignore);
        }

        for($i=0,$m=count($this->file_ignore);$i<$m;$i++) {
            if(substr($dir, -(strlen($this->file_ignore[$i])))==$this->file_ignore[$i]) return;
        }

        if(is_dir($dir)) {
            $content = "dir".$separator.str_replace($this->pack_dir, "", $dir).$separator.filemtime($dir)."\n";
            fwrite($this->pack_fp, $content);
            $mydir = opendir($dir);
            while($file = readdir($mydir)) {
                if(trim($file, ".") == "" || ($file!="install" && array_search($file, $ignore)!==false)) continue;
                $this->PackFile($dir."/".$file);
            }
            closedir($mydir);
        } elseif(is_file($dir)) {
            $content  =  "file".$separator.str_replace($this->pack_dir, "", $dir).$separator.filesize($dir).$separator.filemtime($dir)."\n";
            if(isset($this->charset['file_ext'])) {
                $file_content = GetFile($dir);
                $path_parts = pathinfo($dir);
                if(strpos($this->charset['file_ext'], $path_parts["extension"])!==false) {
                    $file_content = str_ireplace(strtolower($this->charset['from']), strtolower($this->charset['to']), $file_content);
                    $file_content = str_ireplace(strtoupper($this->charset['from']), strtoupper($this->charset['to']), $file_content);
                    if(strtolower($this->charset['to'])=="big5") {
                        $file_content = chs2cht($file_content, $this->charset['from']);
                    }
                    if(strpos($dir, "include/config.php")) {
                        $file_content = preg_replace("/\\\$setting\['web'\]\['s_pass'\].+?;/", "\$setting['web']['s_pass'] = '';", $file_content);
                        $file_content = preg_replace("/\\\$setting\['db'\]\['pass'\].+?;/", "\$setting['db']['pass'] = '';", $file_content);
                        $file_content = preg_replace("/\\\$setting\['email'\]\['password'\].+?;/", "\$setting['email']['password'] = '';", $file_content);
                    }
                    $result = chg_charset($file_content, $this->charset['from'], $this->charset['to']);
                    $content  =  "file".$separator.str_replace($this->pack_dir, "", $dir).$separator.strlen($result).$separator.filemtime($dir)."\n";
                    $file_content = $result;
                }
                $content .= $file_content;
            } else {
                $content .= GetFile($dir);
            }
            fwrite($this->pack_fp, $content);
            $this->file_count++;
            array_push($this->pack_result, "<b>Packing File</b> <span style='color:blue'>$dir</span> (".GetFileSize($dir).")");
        }
        return;
    }

    protected function UnpackFile($outdir=".", $separator="|") {
        if(!is_dir($outdir)) mkdir($outdir, 0777);
        while(!feof($this->pack_fp)) {
            $data = explode($separator, trim(fgets($this->pack_fp, 1024), "\r\n"));
            if($data[0]=="dir") {
                if(trim($data[1], ".") != "") {
                    $flag = mkdir($outdir."/".$data[1], 0777);
                    array_push($this->pack_result, "<b>Build Directory</b> $outdir/$data[1] ".($flag?"<span style='color:green'>Successfully!</span>":"<span style='color:red'>failed!</span>"));
                }
            }elseif($data[0]=="file") {
                $the_file = $outdir.$data[1];
                MakeDir(dirname($the_file));
                if($data[2]==0) {
                    $flag = touch($the_file);
                } else {
                    $fp_w = fopen($the_file, "wb");
                    $content = fread($this->pack_fp, $data[2]);
                    if(in_array(substr($content, -3), array('dir', 'fil'))) {
                        $content = substr($content, 0, -3);
                        fseek($this->pack_fp, -3, SEEK_CUR);
                    }
                    $flag = fwrite($fp_w, $content);
                }
                $this->file_count++;
                array_push($this->pack_result, "<b>Unpacking File</b> $outdir/$data[1] ".($flag?"<span style='color:green'>Successfully!</span>(".GetFileSize($this->pack_dir."/".$data[1]).")":"<span style='color:red'>failed!</span>"));
            }
        }
        return;
    }

    public function GetResult() {
        return join("<br />\n", $this->pack_result);
    }

    public function DoIt($type = "pack", $separator="|") {
        $this->pack_result = array();
        if($type == "pack") {
            $this->pack_fp    = fopen($this->pack_file, "wb");
            if(!$this->pack_fp) die("Error Occurs In Creating Output File !");
            $time = $_SERVER['REQUEST_TIME'];
            if(count($this->file_list)>0) {
                $this->PackFileList($separator);
            } else {
                $this->PackFile($this->pack_dir, $separator);
            }
            fclose($this->pack_fp);
            if($_SERVER['REQUEST_TIME']-$time <= 1) sleep(1);
            WriteFile($this->pack_file, gzcompress(GetFile($this->pack_file), 9));
        }else {
            WriteFile($this->pack_file, gzuncompress(GetFile($this->pack_file)));
            $this->pack_fp    = fopen($this->pack_file, "rb");
            if(!$this->pack_fp) die("Error Occurs In Reading Pack File !");
            $this->UnpackFile($this->pack_dir, $separator);
            fclose($this->pack_fp);
            unlink($this->pack_file);
        }
        $filename    = $this->pack_file;
        $filesize    = GetFileSize($filename);
        array_push($this->pack_result, "<br />File Count:{$this->file_count} File(s) ".$filesize);
        return $filename;
    }
}
function encoding_detect($str) {
    if(function_exists("iconv")) {
        $cs_list = array("GBK", "UTF-8", "BIG5", "ASCII");
        foreach ($cs_list as $item) {
            $sample = iconv($item, $item, $str);
            if (md5($sample) == md5($str)) return $item;
        }
    } elseif(function_exists("mb_detect_encoding")) {
        return mb_detect_encoding($str, array("ASCII", "GB2312", "GBK", "BIG5", "UTF-8", "EUC-CN", "ISO-8859-1", "windows-1251", "Shift-JIS"));
    }
    return null;
}
function chg_charset($content, $from="gbk", $to="utf-8") {
    if(strtolower($from)==strtolower($to)) return $content;
    $result = null;
    if(is_string($content)) {
        $result = iconv($from, $to.'//TRANSLIT//IGNORE', $content);
        if($result===false && function_exists("mb_detect_encoding")) {
            $encoding = encoding_detect($content);
            if($encoding!="" && strtolower($encoding)!=strtolower($to)) {
                $result = mb_convert_encoding($content, $to, $encoding);
            } else {
                $result = $content;
            }
        }
    } elseif(is_array($content)) {
        foreach($content as $key => $value) {
            $result[$key] = chg_charset($value, $from, $to);
        }
    } else {
        $result = $content;
    }
    return $result;
}
function WriteFile($file_name, $content, $mode="wb") {
    //Coded By Windy2000 20040410 v1.0
    MakeDir(dirname($file_name));
    if($fp = fopen($file_name, $mode)) {
        if(flock($fp, LOCK_EX)) {
            fwrite($fp, $content);
            flock($fp, LOCK_UN);
        } else {
            fwrite($fp, $content);
        }
        fclose($fp);
        @chmod($file_name, 0777);
    }
    return $fp;
}
function MakeDir($dir) {
    //Coded By Windy2000 20031001 v1.0
    $dir = str_replace("\\", "/", $dir);
    $dir = preg_replace("/\/+/", "/", $dir);
    $flag = true;
    if(!is_dir($dir)) {
        $dir_list = explode("/", $dir);
        if($dir_list[0]=="") $dir_list[0]="/";
        $this_dir = "";
        $oldumask=umask(0);
        $max_count = count($dir_list);
        for($i=0; $i<$max_count; $i++) {
            if(empty($dir_list[$i])) continue;
            $this_dir .= $dir_list[$i]."/";
            if(!is_dir($this_dir)) {
                if(!mkdir($this_dir, 0777)) {
                    $flag = false;
                }
            }
        }
        umask($oldumask);
    }
    return $flag;
}
function GetFile($file, $length=0, $offset=0) {
    //Coded By Windy2000 20020503 v1.5
    if(!is_file($file)) return "";
    if($length==0 && $offset==0) {
        $data = file_get_contents($file);
    } else {
        if($length==0) $length = 8192;
        $fp = fopen($file, "rb");
        fseek($fp, $offset);
        $data = fread($fp, $length);
        fclose($fp);
    }
    //if(get_magic_quotes_runtime()) $data = stripcslashes($data);
    return $data;
}
function GetFileSize($para) {
    if(is_file($para)) {
        $filesize = filesize($para);
    } elseif(is_numeric($para)) {
        $filesize = $para;
    } else {
        $para = strtoupper($para);
        $para = str_replace(" ", "", $para);
        switch(substr($para, -1)) {
            case "G":
                $filesize = ((int)str_replace("G", "", $para)) * 1024 * 1024 * 1024;
                break;
            case "M":
                $filesize = ((int)str_replace("M", "", $para)) * 1024 * 1024;
                break;
            case "K":
                $filesize = ((int)str_replace("K", "", $para)) * 1024;
                break;
            default:
                $filesize = 0;
                break;
        }
        return $filesize;
    }
    if($filesize <1024) {
        $filesize = (string)$filesize . " Bytes";
    }else if($filesize <(1024 * 1024)) {
        $filesize = number_format((double)($filesize / 1024), 1) . " KB";
    }else if($filesize <(1024 * 1024 * 1024)) {
        $filesize = number_format((double)($filesize / (1024 * 1024)), 1) . " MB";
    }else {
        $filesize = number_format((double)($filesize / (1024 * 1024 * 1024)), 1) . " GB";
    }
    return $filesize;
}
$tmp_file = tempnam("./", "mystep");
if($fp = fopen($tmp_file, "w")) {
    fclose($fp);
    unlink($tmp_file);
} else {
    die("Current directory cannot be writen!");
}
set_time_limit(0);
ini_set('memory_limit', '512M');
$pack_file = "mystep.pack";
$mypack = new MyPack('./', $pack_file);
$mypack->DoIt("unpack");
echo $mypack->GetResult();
unset($mypack);
@unlink($pack_file);
?>
<script type="application/javascript">
alert("All files are unpacked and ready to be installed.");
setTimeout(function () {location.href = "./";}, 2000);
</script>