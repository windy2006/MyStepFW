<?php
/********************************************
*                                           *
* Name    : Functions For String Operations *
* Modifier: Windy2000                       *
* Time    : 2018-10-18                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
	各类字符串类函数
		$this->__get                                        // return the value of any public variant or from the info variant
		$this->__toString                                   // return the value of string
		$this->set($val)                                    // set the value of string
		$this->get($charset='')                             // return the value of string
		self::charset($str)                                 // return the charset of the string
		self::setCharset($str, $charset='UTF-8')            // change the charset of the string
		self::toHex($str)                                   // change the string to hex code
		self::fromHex($hex)                                 // get string from hex code
		self::toJson($var, $charset='UTF-8')                // get json string from any array
		self::fromJson($json, $assoc = false)               // get array from string
		self::fromAny($var)                                 // transfer any variant to string
		self::toXML($var)                                   // get xml string from any array
		self::toScript($var, $name)                         // get php code from any variant
		self::htmlTrans($str)                               // transfer the html code to show
		self::txt2html($content)                            // transfer the text code to html
		self::breakStr($string)                             // break a string into chars
		self::fileCharset($file_src, $file_dst, $charset)   // change a file to anyother charset
		self::substr($string, $start, $length, $mode)       // get a part of same string
		self::rndKey($length, $scope, $charset)             // get a random string
		self::addSlash(&$str)                               // add slashes to string
		self::stripSlash(&$str)                             // remove slashes to string
		self::watermark($html, $rate, $scope, $str_append, $charset, $class_nam, $tag_name, $jam_tag)       // insert jam string to html code
*/
class myString {
	use myTrait;

	public static
		$func_alias = array(
			'c' => 'charset',
			'sc' => 'setCharset',
			'hex' => 'toHex',
			's16' => 'fromHex',
			'json' => 'toJson',
			'obj' => 'fromJson',
			'str' => 'fromAny',
			'xml' => 'toXML',
			'html' => 'htmlTrans',
			'rnd' => 'rndKey',
			'txt' => 'txt2html',
		);
		
	protected
		$str = '',
		$charset = '',
		$func_str = array('myString', 'get');

	/**
	 * 构造函数
	 * @param $str 默认字符串，用于各类内操作
	 */
	public function __construct($str='') {
		$this->str = $str;
		$this->charset = self::charset($str);
		return;
	}

	/**
	 * 作为字符串显示时的操作
	 * @return 默认字符串
	 */
	public function __toString() {
		return $this->get();
	}

	/**
	 * 设置初始字符串
	 * @param $val
	 */
	public function set($val) {
		$this->str = $val;;
	}

	/**
	 * 返回指定字符集的默认字符串
	 * @param string $charset
	 * @return 默认字符串
	 */
	public function get($charset='') {
		if(!empty($charset) && $charset != $this->charset) {
			$this->str = self::setCharset($this->str, $charset);
		}
		return $this->str;
	}

	/**
	 * 获取指定字符的字符集
	 * @param $str
	 * @return false|string
	 */
	public static function charset($str) {
		$charset = mb_detect_encoding($str, array('UTF-8','GBK','BIG5','ASCII','EUC-CN','ISO-8859-1','windows-1251','Shift-JIS'));
		if($charset=='CP936') $charset = 'GBK';
		return $charset;
	}

	/**
	 * 将字符串转换为指定字符集
	 * @param $str
	 * @param string $charset
	 * @return array|string
	 */
	public static function setCharset($str, $charset='UTF-8') {
		$charset = strtoupper($charset);
		if(is_array($str)) {
			foreach($str as $k => $v) {
				$str[$k] = self::setCharset($v, $charset);
			}
		} else {
			$charset_str = self::charset($str);
			if($charset!=$charset_str) {
				if(function_exists('iconv')) {
					$str = iconv($charset_str, $charset.'//TRANSLIT//IGNORE', $str);
				} else {
					$str = mb_convert_encoding($str, $charset, 'UTF-8,GBK');
				}
			}
		}
		return $str;
	}

	/**
	 * 将字符串转换为16进制
	 * @param $str
	 * @return string
	 */
	public static function toHex($str) {
		$hex='';
		for($i=0, $m=strlen($str); $i<$m; $i++) {
			$hex .= dechex(ord($str[$i]));
		}
		return $hex;
	}

 	/**
	 * 将ini文件转换为数组
	 * @param $ini
	 * @return array
	 */
	public static function fromIni($ini, $mode = true) {
		if(is_file($ini)) {
			$result = parse_ini_file($ini, $mode, INI_SCANNER_RAW);
		} else {
			$result = parse_ini_string($ini, $mode);
		}
		return $result;
	}

	/**
	 * 将数组转换为ini格式
	 * @param $array
	 * @return string
	 */
	public static function toIni($array, $level = 1) {
		$result = '';
		foreach($array as $k => $v) {
			if(is_array($v)) {
				if($level==1) {
					$result .= '['.$k.']'.chr(10);
					$result .= self::toIni($v, $level+1);
				} else {
					$result .= $k.' = '.self::fromAny($v);
				}
			} elseif(is_bool($v)) {
				$result .= $k.' = '.($v?1:0);
			} else {
				$result .= $k.' = '.preg_replace('/[\r\n]/', '\n', self::fromAny($v));
			}
			$result .= chr(10);
		}
		return $result;
	}


	/**
	 * 将16进制转换为字符串
	 * @param $hex
	 * @return string
	 */
	public static function fromHex($hex) {
		$str='';
		for($i=0, $m=strlen($hex)-1; $i<$m; $i+=2) {
			$str .= chr(hexdec($hex[$i].$hex[$i+1]));
		}
		return $str;
	}

	/**
	 * 将Json字符串编译为Json对象
	 * @param $var
	 * @param string $charset
	 * @return false|string
	 */
	public static function toJson($var, $charset='UTF-8') {
		if(!empty($charset)) $var = self::setCharset($var, $charset);
		return json_encode($var);
	}

	/**
	 * 将Json对象转换为字符串
	 * @param $json
	 * @param bool $assoc
	 * @return mixed
	 */
	public static function fromJson($json, $assoc = true) {
		$json = str_replace(array(chr(10),chr(13)),'',$json);
		$json = preg_replace('/([{,])(\s*)([^"]+?)\s*:/','$1"$3":',$json);
		$json = str_replace('\\"',"&#34;",$json);
		return json_decode($json, $assoc);
	}

	/**
	 * 将任意类型变量转换为字符串
	 * @param $var
	 * @return string
	 */
	public static function fromAny($var) {
		switch(true) {
			case is_string($var):
				$result = $var;
				break;
			case is_numeric($var):
				$result = (STRING)$var;
				break;
			case is_bool($var):
				$result = $var?'true':'false';
				break;
			/*
			case is_array($var):
				$result = join(',', $var);
				break;
			case is_object($var):
				$result = (STRING)$var;
				break;
			*/
			default:
				$result = serialize($var);
				break;
		}
		return $result;
	}

	/**
	 * 将数组转换为XML
	 * @param $var
	 * @return string
	 */
	public static function toXML($var) {
		$result = '';
		if(is_array($var)) {
			$result .= chr(10);
			foreach($var as $key => $value) {
				if(is_numeric($key)) $key = 'item';
				$result .= "<{$key}>";
				$result .= self::toXML($value);
				$result .= "</{$key}>";
				$result .= chr(10);
			}
		} else {
			if(preg_match("/[<>&\r\n]+/", $var)) {
				$result = "<![CDATA[".$var."]]>";
			} else {
				$result = $var;
			}
		}
		return $result;
	}

	/**
	 * 转换变量为php变量脚本
	 * @param $var
	 * @param string $name
	 * @return string
	 */
	public static function toScript($var, $name='') {
		$result = '';
		if(is_array($var)) {
			if(!empty($name)) {
				if(strpos($name, '$')!==0) $name = '$'.$name;
				$result .= chr(10).$name.' = array();'.chr(10);
			}
			foreach($var as $key => $value) {
				if(empty($name)) {
					$name_new = '$'.$key;
				} else {
					$name_new = $name.'['.(is_numeric($key) ? $key : '\''.addslashes($key).'\'').']';
				}
				if(is_array($value)) {
					$result .= self::toScript($value, $name_new);
				} else {
					$result .= $name_new.' = ';
					if(is_bool($value)) {
						$result .= $value?'true':'false';
					} elseif(is_numeric($value) || strtolower($value)=='true' || strtolower($value)=='false' ) {
						$result .= strtolower($value);
					} else {
						$result .= '\''.addslashes($value).'\'';
					}
					$result .= ';'.chr(10);
				}
			}
		} else {
			$result = '$'.$name.' = '.(is_numeric($var) ? $var : '"'.addslashes($var).'"').';';
		}
		return $result;
	}

	/**
	 * 转换指定文件的字符集
	 * @param $file_src
	 * @param $file_dst
	 * @param string $charset
	 * @return bool|int|void
	 */
	public static function fileCharset($file_src, $file_dst='', $charset="UTF-8") {
		if(!is_file($file_src)) return;
		if(empty($file_dst)) $file_dst = $file_src;
		$content = file_get_contents($file_src);
		$content = self::setCharset($content, $charset);
		return file_put_contents($file_dst, $content);
	}

	/**
	 * 将字符串转换为超文本格式
	 * @param $str
	 * @return array|mixed
	 */
	public static function htmlTrans($str) {
		$search = array("'", "\"", "<", ">", "	", "\t");
		$replace = array("&#39;", "&quot;", "&lt;", "&gt;", "&nbsp; ", "&nbsp; &nbsp; ");
		if(is_array($str)) {
			foreach($str as $key => $value) {
				$str[$key] = self::htmlTrans($value);
			}
		} elseif(is_string($str)) {
			$str = str_replace($search, $replace, $str);
		}
		return $str;
	}

	/**
	 * 将纯文本转换为超文本格式
	 * @param $content
	 * @return mixed
	 */
	public static function txt2html($content) {
		$content = str_replace('	', '&nbsp; ', $content);
		$content = str_replace("\r\n", chr(10), $content);
		$content = str_replace(chr(10), "<br />\n", $content);
		$content = str_replace("\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $content);
		return $content;
	}

	/**
	 * 生成随机字符串
	 * @param $length
	 * @param int $scope 1-数字，2-小写字母，3-大写字母，4-特殊字符，5-汉字
	 * @param string $charset
	 * @return array|string
	 */
	public static function rndKey($length, $scope=1, $charset='gbk') {
		//Coded By Windy2000 20020501 v1.0
		$char_list	= array();
		$char_list[]	= '1234567890';
		$char_list[]	= 'abcdefghijklmnopqrstuvwxyz';
		$char_list[]	= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$char_list[]	= '!@^()_:+\-';
		$Rnd_Key	= '';
		if($scope>0 && $scope<=count($char_list)) {
			for($i=1; $i<=$length; $i++) {
				$Rnd_Str	= $char_list[mt_rand(1,$scope) - 1];
				$Rnd_Key .= substr($Rnd_Str, mt_rand(0,strlen($Rnd_Str)-1), 1);
			}
		} else {
			for($i=1; $i<=$length; $i++) {
				$Rnd_Key .= chr(mt_rand(0xb0,0xf7)).chr(mt_rand(0xa0,0xfe));
			}
			if($charset!='gbk') $Rnd_Key = self::setCharset($Rnd_Key, $charset);
		}
		return($Rnd_Key);
	}

	/**
	 * 批量添加转移斜杠
	 * @param $str array|string
	 * @return array|string
	 */
	public static function addSlash(&$str) {
		if(is_array($str)) {
			foreach($str as $key => $value) {
				$str[$key] = self::addSlash($value);
			}
		} elseif(is_string($str)) {
			$str = addslashes($str);
		}
		return $str;
	}

	/**
	 * 批量去除转移斜杠
	 * @param $str
	 * @return array|string
	 */
	public static function stripSlash(&$str) {
		if(is_array($str)) {
			foreach($str as $key => $value) {
				$str[$key] = self::stripSlash($value);
			}
		} elseif(is_string($str)) {
			$str = stripslashes($str);
		}
		return $str;
	}

	/**
	 * 将字符串打碎为单个的字符或汉字
	 * @param $str
	 * @return mixed
	 */
	public static function breakStr($str) {
		if(self::charset($str)=='UTF-8') {
			preg_match_all('/([\xE0-\xEF][\x80-\xBF]{2})|./', $str, $arr);
		} else {
			preg_match_all('/[\xa0-\xff]?./', $str, $arr);
		}
		return $arr[0];
	}

	/**
	 * 为超文本添加水印
	 * @param $html
	 * @param int $rate 出现几率
	 * @param int $scope	水印内容对应 rndkey
	 * @param string $str_append	自定义水印内容
	 * @param string $charset
	 * @param string $class_name
	 * @param string $tag_name
	 * @param bool $jam_tag 干扰标签
	 * @return mixed|string
	 */
	public static function watermark($html, $rate=2, $scope=4, $str_append='', $charset='', $class_name='watermark', $tag_name='span', $jam_tag=false) {
		/*
		Please make sure that the following style exist on your style sheet of the watermark page
		.{$class_name} {
			position:absolute;width:1px;height:1px;overflow:hidden;
		}
		*/
		if(strlen($html)>50000) return $html;
		if($scope>5 && empty($charset)) $charset = 'utf-8';
		$result = '';
		preg_match_all('/(<(.+?)>)|(&([#\w]+);)/is', $html, $arr_tag);
		$arr_tag = $arr_tag[0];
		$html = str_replace($arr_tag, chr(0), $html);
		$arr_char = self::breakStr($html);
		for($i=0,$m=count($arr_char); $i<$m; $i++) {
			if(ord($arr_char[$i])==0) {
				$cur_tag = array_shift($arr_tag);
				if(!empty($str_append) && preg_match('/<(\/(p|div))|(br( +\/)?)>/i', $cur_tag)) {
					$cur_tag = "<{$tag_name} class='{$class_name}'>".$str_append."</{$tag_name}>".$cur_tag;
				}
				$result .= $cur_tag;
			} elseif(mt_rand(1, 10)<$rate) {
				$rnd_str = self::rndKey(mt_rand(1, 2), $scope, $charset);
				if($jam_tag && mt_rand(1, 10)<6) {
					$rnd_str .= "<{$tag_name} class='".self::rndKey(mt_rand(3, 6),2)."'>".self::rndKey(mt_rand(1, 2), $scope, $charset)."</{$tag_name}>".self::rndKey(mt_rand(1, 2), $scope, $charset);
				}
				$result .= "<{$tag_name} class='{$class_name}'>".$rnd_str."</{$tag_name}>".$arr_char[$i];
			} else {
				$result .= $arr_char[$i];
			}
		}
		return $result;
	}

	/**
	 * 截取任意字符串，可为任意字符集的中文
	 * @param $string
	 * @param $start
	 * @param int $length
	 * @param bool $mode
	 * @return string
	 */
	public static function substr($string, $start, $length = 0, $mode = false) {
		$arr = self::breakStr($string);
		$m = $mode?count($arr):strlen($string);
		if($start<0) $start += $m;
		if($start<0) $start = 0;
		if($start>$m) return '';
		if($length<=0) $length += $m - $start;
		if($length<=0) return '';
		if($mode) return implode('', array_slice($arr, $start, $length));
		$str = '';
		$sub_start = false;
		for($i=0; $i<$m; $i++) {
			if(strlen($str)>=$start && $sub_start==false) {
				$str = $arr[$i];
				$sub_start = true;
			} else {
				$str .= $arr[$i];
			}
			if($sub_start && strlen($str)>=$length) break;
		}
		return $str;
	}
}