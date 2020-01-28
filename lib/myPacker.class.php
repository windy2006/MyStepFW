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
			$allow = array();
			if(is_file($dir.'/allow')) {
				$allow = file_get_contents($dir.'/allow');
				if(strlen($allow)==0) return;
				$allow = str_replace(chr(13), '', $allow);
				$allow = explode(chr(10), $allow);
			}
			$content = 'dir'.$this->separator.str_replace($this->pack_dir, '', $dir).$this->separator.filemtime($dir).chr(10);
			fwrite($this->pack_fp, $content);
			$mydir = opendir($dir);
			while($file = readdir($mydir)){
				if(trim($file, '.') == '' || $file == 'ignore' || $file == 'allow') continue;
				if(!empty($allow) && array_search($file, $allow)===false) continue;
				if(!empty($ignore) && array_search($file, $ignore)!==false) continue;
				$this->packFile($dir.'/'.$file);
			}
			closedir($mydir);
		} elseif(is_file($dir)) {
			$file_content = myFile::getLocal($dir);
			if(substr($file_content,0,3) == pack("CCC",0xef,0xbb,0xbf)) $file_content = myFile::removeBom($file_content);
			$content = 'file'.$this->separator.str_replace($this->pack_dir, '', $dir).$this->separator.strlen($file_content).$this->separator.filemtime($dir).chr(10);
			if(isset($this->charset['file_ext'])) {
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
			}
			$content .= $file_content;
			fwrite($this->pack_fp, $content);
			$this->file_count++;
			$this->pack_result[] = ['Packing File', str_replace($root, '/', $dir), myFile::getSize($dir)];
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
			$data = explode($this->separator, trim(fgets($this->pack_fp, 1024),"\r\n"));
			$n++;
			if($data[0]=='dir') {
				if(trim($data[1], '.') != '') {
					$flag = myFile::mkdir($outdir.$data[1]);
					$this->pack_result[] = ['Build Directory', str_replace($root, '/', $outdir.$data[1]),($flag?'Successfully':'failed')];
				}
			}elseif($data[0]=='file') {
				$the_file = $outdir.$data[1];
				myFile::mkdir(dirname($the_file));
				if($data[2]==0) {
					$flag = touch($the_file);
				} else {
					$fp_w = fopen($the_file,"wb");
					$content = fread($this->pack_fp,$data[2]);
					if(in_array(substr($content, -3),array('dir','fil'))) {
						$content = substr($content, 0, -3);
						fseek($this->pack_fp,-3,SEEK_CUR);
					}
					$flag = fwrite($fp_w, $content);
				}
				$this->file_count++;
				$this->pack_result[] = ['Unpacking File', str_replace($root, '/', $outdir.$data[1]), ($flag?'Successfully':'failed')];
			} else {
				$n--;
			}
		}
		return $n-1;
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
		$this->pack_result[] = ['pack', $this->file_count, $filename, $filesize];
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
		$this->pack_result[] = ['unpack', $this->file_count, $n-$this->file_count];
		return true;
	}

	/**
	 * 返回操作结果
	 * @param int $mode
	 * @return array|string
	 */
	public function getResult($mode = 0) {
		if($mode) return $this->pack_result;
		$result = '';
		for($i=0,$m=count($this->pack_result)-1;$i<$m;$i++) {
			$result .= '<b>'.$this->pack_result[$i][0].'</b> - <i>'.$this->pack_result[$i][1].'</i> &nbsp; '.$this->pack_result[$i][2].'<br />'.chr(10);
		}
		if($this->pack_result[$i][0]=='pack') {
			$result .= 'Count: '.$this->pack_result[$i][1].' File(s).<br />'.chr(10);
			$result .= 'Packed File: <a href="'.$this->pack_result[$i][2].'">'.basename($this->pack_result[$i][2]).'</a> ('.$this->pack_result[$i][3].')';
		} else {
			array_push($this->pack_result,'Extract: '.$this->file_count.' File(s) & '.($m-$this->file_count).' Dir(s).');
			$result .= 'Extract: '.$this->pack_result[$i][1].' File(s) & '.$this->pack_result[$i][2].' Dir(s).';
		}
		return $result;
	}
}