<?php
/********************************************
*                                           *
* Name    : Zip file handler                *
* Modifier: Windy2000                       *
* Time    : 2011-12-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
压缩文件处理
  $zip = new myZip($zip_file);
  $zip->zip($file);
  $zip->unzip($dir);
 */
class myZip extends ZipArchive {
	protected $file;

	/**
	 * 构造函数
	 * @param string $zip_file
	 */
	public function __construct($zip_file = '') {
		$this->file = $zip_file;
	}

	/**
	 * 往压缩包添加目录
	 * @param $path
	 */
	public function addDir($path) {
		if(!is_dir($path)) return;
		$root = myFile::rootPath();
		$this->addEmptyDir(str_replace($root, '', $path));
		$files = glob($path . '/*');
		foreach($files as $file) {
			if(is_dir($file)) {
				$this->addDir($file);
			} elseif(is_file($file))  {
				$this->addFile($file, str_replace($root, '', $file));
			}
		}
	}

	/**
	 * 压缩文件（单文件、目录或文件列表）
	 * @param $file
	 * @return bool
	 */
	public function zip($file) {
		$res = false;
		if(!empty($this->file)){
			$this->file = myFile::realPath($this->file);
			myFile::mkdir(dirname($this->file));
		}
		if(func_num_args()>1) $file = func_get_args();
		if(is_array($file)) {
			if(empty($this->file)) $this->file = tempnam(dirname($_SERVER['PHP_SELF']), '').'.zip';
			if($res = $this->open($this->file, ZIPARCHIVE::CREATE)) {
				$root = myFile::rootPath();
				foreach($file as $cur_file) {
					$cur_file = myFile::realPath($cur_file);
					if(is_dir($cur_file)) {
						$this->addDir($cur_file);
					} elseif(is_file($cur_file)) {
						$this->addFile($cur_file, str_replace($root, '', $cur_file));
					}
				}
				$this->close();
			}
		} elseif(is_dir($file)) {
			$file = myFile::realPath($file);
			if(empty($this->file)) $this->file = basename(trim($file, '/')).'.zip';
			if($res = $this->open($this->file, ZIPARCHIVE::CREATE)) {
				$this->addDir($file);
				$this->close();
			}
		} elseif(is_file($file)) {
			$file = myFile::realPath($file);
			if(empty($this->file)) $this->file = str_replace(pathinfo($file, PATHINFO_EXTENSION), 'zip', basename($file));
			if($res = $this->open($this->file, ZIPARCHIVE::CREATE)) {
				$this->addFile($file, basename($file));
				$this->close();
			}
		}
		$basedir = dirname($this->file).'/';
		if($res) {
			$this->open($this->file);
			for($i=0; $i<$this->numFiles; $i++) {
				$theName = $this->getNameIndex($i);
				$newName = str_replace($basedir, '', $theName);
				$newName = preg_replace('/\/+/', '/', $newName);
				$this->renameName($theName, $newName);
			}
			$this->close();
		}
		return $res ? true : false;
	}

	/**
	 * 解压文件
	 * @param string $dir
	 * @param bool $remove
	 * @return bool
	 */
	public function unzip($dir='./', $remove = false) {
		if($res = $this->open($this->file)) {
			$dir = myFile::realPath($dir);
			myFile::mkdir($dir);
			$this->extractTo($dir);
			$this->close();
			if($remove) myFile::del($this->file);
			return true;
		} else {
			return false;
		}
	}
}