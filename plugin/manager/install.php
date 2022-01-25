<?PHP
$lng = array();
if (preg_match("/zh/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $lng[0] = ' 扩展未启用！';
    $lng[1] = '当前目录不可写！';
    $lng[2] = '完成！';
    $lng[3] = '失败！';
    $lng[4] = '释放文件';
    $lng[5] = '建立目录';
    $lng[6] = '安装包读取失败！';
    $lng[7] = '文件统计: 共释放 %u 个文件，共计 %s';
    $lng[8] = '安装';
    $lng[9] = '设置';
    $lng[10] = '安装进度';
    $lng[11] = '所有文件释放完毕，准备开始安装！';
    $lng[12] = '迈思PHP框架安装';
    $lng[13] = '开始安装';
    $lng[14] = '框架设置';
    $lng[15] = '安装进度';
    $lng[16] = '所有文件解压完毕，请设置基本参数！';
    $lng[17] = '检测到 Nginx 系统，请将框架根目录下的 Nginx 文件中的 rewrite 配置信息添加至配置文件！';
} else {
    $lng[0] = ' extension is unavailable!';
    $lng[1] = 'The target direction is read only!';
    $lng[2] = 'Successfully!';
    $lng[3] = 'Failed!';
    $lng[4] = 'Unpacking File';
    $lng[5] = 'Build Directory';
    $lng[6] = 'Error Occurs In Reading Pack File!';
    $lng[7] = 'File Count: %u File(s), %s in size';
    $lng[8] = 'Install';
    $lng[9] = 'Setting';
    $lng[10] = 'Installation progress';
    $lng[11] = 'All files are unpacked and ready to be installed.';
    $lng[12] = 'MyStep PHP Framework';
    $lng[13] = 'Start';
    $lng[14] = 'Setting';
    $lng[15] = 'Installation progress';
    $lng[16] = 'All files are unpacked and will jump to the setting page.';
    $lng[17] = 'Framework is under Nginx system, please check the Nginx file under the framework directory for the setting!';
}
$ext_list = ['zlib', 'zip', 'sockets', 'curl', 'gd', 'curl', 'iconv', 'mysqli','openssl'];
foreach ($ext_list as $v) {
    if (!extension_loaded($v)) {
        die($v.$lng[0]);
    }
}
if(!rewritable('./')) {
    die($lng[1]);
}
class unpacker {
    protected
        $file_count = 0,
        $pack_file = "pack.bin",     // the file name of packed file
        $pack_dir = "./",            // the directory of pack or unpack to
        $pack_fp = null;

    public function __construct($pack_dir = "./", $pack_file = "pack.pkg") {
        $this->pack_dir = str_replace("//", "/", $pack_dir);
        $this->pack_file = $pack_file;
    }

    protected function log($log) {
        WriteFile('install.log', $log.'<br />'.chr(10), 'ab');
    }

    protected function UnpackFile($outdir="./", $separator="|") {
        global $lng;
        if(!is_dir($outdir)) mkdir($outdir, 0777);
        while(!feof($this->pack_fp)) {
            $data = explode($separator, trim(fgets($this->pack_fp, 1024), "\r\n"));
            if($data[0]=="dir") {
                if(trim($data[1], ".") != "") {
                    $flag = mkdir($outdir."/".$data[1], 0777);
                    $this->log("<b>".$lng[5]."</b> $outdir/$data[1] ".($flag?"<span style='color:green'>".$lng[2]."</span>":"<span style='color:red'>".$lng[3]."</span>"));
                }
            }elseif($data[0]=="file") {
                if($data[1]=='.htaccess' || $data[1]=='web.config') $data[1] .= '.bak';
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
                $this->log("<b>".$lng[4]."</b> $outdir/$data[1] ".($flag?"<span style='color:green'>".$lng[2]."</span>(".GetFileSize($this->pack_dir."/".$data[1]).")":"<span style='color:red'>".$lng[3]."</span>"));
            }
        }
    }

    public function DoIt($separator="|") {
        WriteFile($this->pack_file, gzuncompress(GetFile($this->pack_file)));
        $this->pack_fp = fopen($this->pack_file, "rb");
        if(!$this->pack_fp) die($lng[6]);
        $this->UnpackFile($this->pack_dir, $separator);
        fclose($this->pack_fp);
        $filename    = $this->pack_file;
        $filesize    = GetFileSize($filename);
        $this->log("<br />". sprintf($lng[7], $this->file_count, $filesize));
        unlink($this->pack_file);
        return $filename;
    }
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
function rewritable($file) {
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

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$svr = strtolower($_SERVER['SERVER_SOFTWARE']);
switch($step) {
    case 1:
        @unlink('install.php');
        copy('index.php', 'install.php');
        echo <<<myStep
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>MyStep Framework Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="windows-Target" content="_top" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" media="screen" type="text/css" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css" />
</head>
<body>

<div class="pt-3 text-center">
    <img src="data:image/png;base64,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" />
</div>
<div class="container" style="padding-bottom:100px;">
    <div class="row">&nbsp;</div>
    <div class="row">
        <div class="col-md-10 offset-md-1 col-sm-12">
            <div class="text-center mb-3">
                <button id="install" class="btn btn-primary btn-lg p-4">{$lng[8]}</button>
                <button id="jump" class="btn btn-primary btn-lg p-4 d-none">{$lng[9]}}</button>
            </div>
            <div id="show" class="card mb-3 d-none">
                <div class="card-header p-2 bg-info">
                    <h5 class="text-white card-title m-0">{$lng[10]}</h5>
                </div>
                <div class="card-body" style="height:400px;overflow-y:auto;" id="detail"></div>
            </div>
        </div>
    </div>
</div>
<footer class="border-top text-center mt-3 text-secondary fixed-bottom bg-light pt-2 font-sm">
    <p>Powered by  MyStep Framework &nbsp;Copyright&copy; 2010-2021 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<script type="application/javascript" src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</body>
<script type="application/javascript">
let txt_done = '{$lng[11]}';
if((navigator.browserLanguage || navigator.language).toLowerCase().indexOf('zh-')>-1) {
    $('title').text('{$lng[12]}');
    $('#install').text('{$lng[13]}');
    $('#jump').text('{$lng[14]}');
    $('#show h5').text('{$lng[15]}');
    txt_done = '{$lng[16]}';
}
$('#install').click(function(){
    $(this).hide();
    $('#show').removeClass('d-none');
    let timer = setInterval(function(){
        $.get('install.php?step=2', function(detail){
            $('#detail').html(detail);
            $('#detail').scrollTop($('#detail').prop("scrollHeight"));
        })
    }, 50);
    $.get('install.php?step=999', function(result){
        alert(txt_done);
        clearInterval(timer);
        $.get('install.php?step=2', function(detail){
            $('#detail').html(detail);
            $('#detail').scrollTop($('#detail').prop("scrollHeight"));
        })
        $('#jump').removeClass('d-none').click(function(){
            location.href="install.php?step=3";
        });
        if(('{$svr}').indexOf('nginx')!==-1) {
            alert('{$lng[17]}');
        } else {
            setTimeout(function(){location.href="install.php?step=3";}, 5000);
        }
    })
});
</script>
</html>
myStep;
        break;
    case 2:
        echo GetFile('install.log');
        break;
    case 3:
        @unlink('install.php');
        @unlink('install.log');
        rename('.htaccess.bak', '.htaccess');
        rename('web.config.bak', 'web.config');
        header('location: ./');
        break;
    default:
        @unlink('index.php');
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $pack_file = "mystep.pack";
        $mypack = new unpacker('./', $pack_file);
        $mypack->DoIt();
        unset($mypack);
        @unlink($pack_file);
        $empty = '<?PHP
return array();        
';
        WriteFile('config/domain.php', $empty);
        WriteFile('config/route_plugin.php', $empty);
        WriteFile('config/route.php', '');
        @unlink('config/config.php');
        echo 'done!';
}