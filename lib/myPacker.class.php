<?php
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
  文件打包:
	$mypacker = new myPacker($pack_dir, $pack_file)     // Set the Class
	$mypacker->addIgnore()                              // add files which will not be packed
	$mypacker->pack()  or   $mypacker->unpack()         // pack or unpack file(s)
*/
class myPacker extends myBase {
	protected
		$file_count = 0,
		$file_ignore = array(),		// ignore these files when packing
		$file_list = array(),		// only files in the list will be pack
		$pack_file = 'pack.bin',	// the file name of packed file
		$pack_dir = './',			// the directory of pack or unpack to
		$pack_fp = null,
		$pack_result = array(),
		$separator = '|',
		$charset = array();

	/**
	 * 初始化类变量
	 * @param string $pack_dir
	 * @param string $pack_file
	 * @param string $separator
	 */
	public function init($pack_dir = './', $pack_file = 'mypack.pkg', $separator = '|') {
		$this->pack_dir = myFile::realPath($pack_dir);
		$this->pack_file = myFile::realPath($pack_file);
		if(!empty($separator)) $this->separator = $separator;
		$this->addIgnore(basename($pack_file));
		return;
	}

	/**
	 * 添加排除文件
	 */
	public function addIgnore() {
		$args_list = func_get_args();
		$this->file_ignore = array_merge($this->file_ignore, $args_list);
		return;
	}

	/**
	 * 设定字符集相关变量
	 * @param $charset
	 * @param string $lng_type
	 * @param string $file_ext
	 */
	public function setCharset($charset, $lng_type='', $file_ext='') {
		$this->charset['to'] = $charset;
		$this->charset['lng_type'] = $lng_type;
		$this->charset['file_ext'] = $file_ext;
		return;
	}

	/**
	 * 添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件）
	 */
	public function addFile() {
		$args_list = func_get_args();
		$this->file_list += $args_list;
		return;
	}

	/**
	 * 打包已添加文件
	 */
	protected function packFileList() {
		for($i=0, $m=count($this->file_list); $i<$m; $i++) {
			$this->packFile($this->file_list[$i]);
		}
		return;
	}

	/**
	 * 打包文件或目录
	 * @param string $dir
	 */
	protected function packFile($dir='.') {
		$root = myFile::rootPath();
		$dir = myFile::realPath($dir);

		for($i=0,$m=count($this->file_ignore);$i<$m;$i++) {
			if(substr($dir, -(strlen($this->file_ignore[$i])))==$this->file_ignore[$i]) return;
		}
		if(is_dir($dir)) {
			$ignore = array();
			if(is_file($dir.'/ignore')) {
				$ignore = file_get_contents($dir.'/ignore');
				if(strlen($ignore)==0) return;
				$ignore = str_replace(chr(13), '', $ignore);
				$ignore = explode(chr(10), $ignore);
			}
			$content = 'dir'.$this->separator.str_replace($this->pack_dir, '', $dir).$this->separator.filemtime($dir).chr(10);
			fwrite($this->pack_fp, $content);
			$mydir = opendir($dir);
			while($file = readdir($mydir)){
				if(trim($file, '.') == '' || $file == 'ignore' || array_search($file, $ignore)!==false) continue;
				$this->packFile($dir.'/'.$file);
			}
			closedir($mydir);
		} elseif(is_file($dir)) {
			$content  =  'file'.$this->separator.str_replace($this->pack_dir, '', $dir).$this->separator.filesize($dir).$this->separator.filemtime($dir).chr(10);
			if(isset($this->charset['file_ext'])) {
				$file_content = myFile::getLocal($dir);
				$path_parts = pathinfo($dir);
				if(strpos($this->charset['file_ext'], $path_parts['extension'])!==false) {
					if(!empty($this->charset['lng_type'])) {
						$file_content = chg_lng_custom($file_content, $this->charset['lng_type']);
					} else {
						if(strtolower($this->charset['to'])=='big5') {
							$file_content = chs2cht($file_content, 1);
						}
					}
					$result = myString::setCharset($file_content, $this->charset['to']);
					$content  =  'file'.$this->separator.str_replace($this->pack_dir, '', $dir).$this->separator.strlen($result).$this->separator.filemtime($dir).chr(10);
					$file_content = $result;
				}
				$content .= $file_content;
			} else {
				$content .= myFile::getLocal($dir);
			}
			fwrite($this->pack_fp, $content);
			$this->file_count++;
			array_push($this->pack_result, '<b>Packing File</b> - <i>"'.str_replace($root, '/', $dir).'"</i> &nbsp; ('.myFile::getSize($dir).')');
		}
		return;
	}

	/**
	 * 解开文件包
	 * @param string $outdir
	 * @return int
	 */
	protected function unpackFile($outdir='.') {
		$root = myFile::rootPath();
		$outdir = myFile::realPath($outdir);
		if(!is_dir($outdir)) myFile::mkdir($outdir);
		$n = 0;
		while(!feof($this->pack_fp)) {
			$data = explode($this->separator, fgets($this->pack_fp, 1024));
			$n++;
			if($data[0]=='dir') {
				if(trim($data[1], '.') != '') {
					$flag = myFile::mkdir($outdir.$data[1]);
					array_push($this->pack_result, '<b>Build Directory</b> - <i>"'.str_replace($root, '/', $outdir.$data[1]).'"</i> &nbsp; '.($flag?'<span style="color:green">Successfully!</span>':'<span style="color:red">failed!</span>'));
				}
			}elseif($data[0]=='file') {
				$flag = false;
				$the_file = $outdir.$data[1];
				myFile::mkdir(dirname($the_file));
				if($fp_w = fopen($the_file,'wb')) {
					if($data[2]>0) $flag = fwrite($fp_w, fread($this->pack_fp,$data[2]));
					$this->file_count++;
				}
				array_push($this->pack_result, '<b>Unpacking File</b> - <i>"'.str_replace($root, '/', $outdir.$data[1]).'"</i> &nbsp; '.($flag?'<span style="color:green">Successfully!</span> ('.myFile::getSize($this->pack_dir.'/'.$data[1]).')':'<span style="color:red">failed!</span>'));
			} else {
				$n--;
			}
		}
		return $n-1;
	}

	/**
	 * 返回操作结果
	 * @return string
	 */
	public function getResult() {
		return join('<br />'.chr(10), $this->pack_result);
	}

	/**
	 * 打包文件
	 * @return bool
	 */
	public function pack() {
		$this->pack_result = array();
		myFile::del($this->pack_file);
		myFile::mkdir(dirname($this->pack_file));
		$this->pack_fp = fopen($this->pack_file, 'wb');
		if(!$this->pack_fp) {
			$this->error('Error Occurs In Creating Output File !');
			return false;
		}
		$time = $_SERVER['REQUEST_TIME'];
		if(count($this->file_list)>0) {
			$this->packFileList();
		} else {
			$this->packFile($this->pack_dir);
		}
		fclose($this->pack_fp);
		if(time()-$time <= 1) sleep(1);
		myFile::saveFile($this->pack_file, gzcompress(myFile::getLocal($this->pack_file), 9));
		$filename = $this->pack_file;
		$filesize = myFile::getSize($filename);
		array_push($this->pack_result,'Count: '.$this->file_count.' File(s)');
		array_push($this->pack_result,'Packed File: <a href="'.$filename.'">'.basename($filename).'</a> ('.$filesize.')');
		return true;
	}

	/**
	 * 解包文件
	 * @return bool
	 */
	public function unpack() {
		$this->pack_result = array();
		myFile::saveFile($this->pack_file, gzuncompress(myFile::getLocal($this->pack_file)));
		$this->pack_fp = fopen($this->pack_file, 'rb');
		if(!$this->pack_fp) {
			$this->error('Error Occurs In Reading Pack File !');
			return false;
		}
		$n = $this->unpackFile($this->pack_dir);
		fclose($this->pack_fp);
		unlink($this->pack_file);
		array_push($this->pack_result,'Extract: '.$this->file_count.' File(s) & '.($n-$this->file_count).' Dir(s).');
		return true;
	}
}