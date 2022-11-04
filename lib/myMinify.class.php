<?PHP
/********************************************
*                                           *
* Name    : Functions for minify CSS or JS  *
* Modifier: Windy2000                       *
* Time    : 2018-11-02                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
 * 生成js或css的压缩代码
 */
class myMinify extends myBase {
    protected
        $mode = null,
        $cache = '',
        $content = '',
        $result = '';

    /**
     * 初始化类变量
     * @param string $mode
     * @param string $cache
     * @param bool $renew
     */
    public function init($mode = 'js', $cache = '', $renew = false) {
        if($mode != 'js') $mode = 'css';
        $this->mode = $mode;
        $this->cache = myFile::realPath($cache);
        if($renew && is_file($cache)) unlink($cache);
    }

    /**
     * 检测是否已有缓存
     * @param int $expires
     * @return bool
     */
    public function check($expires = 604800) {
        if(is_file($this->cache) && filemtime($this->cache)+$expires>myReq::server('REQUEST_TIME')) myFile::del($this->cache);
        return is_file($this->cache);
    }

    /**
     * 添加文件
     * @param $code
     * @return $this
     */
    public function add($code) {
        if(is_file($code)) {
            $ext = strtolower(pathinfo($code, PATHINFO_EXTENSION));
            if($ext==$this->mode) {
                $this->content .= file_get_contents($code).chr(10);
            }
        } else {
            $this->content .= $code.chr(10);
        }
        return $this;
    }

    /**
     * 生成压缩代码
     * @param bool $pack
     * @return bool|mixed|string
     */
    public function get($pack = false) {
        if($this->check()) {
            $this->result = file_get_contents($this->cache);
        } else {
            if($this->mode == 'js' && $pack) {
                $packer = new JavaScriptPacker($this->content, 62, false, false);
                $this->result = $packer->pack();
            } else {
                $this->result = self::minify($this->content, $this->mode);
            }
            if(!empty($this->cache)) file_put_contents($this->cache, $this->result);
        }
        return $this->result;
    }

    /**
     * 显示代码
     * @param bool $pack
     */
    public function show($pack = false) {
        $this->get($pack);
        header("Expires: " . date("D, j M Y H:i:s", strtotime("now + 10 years")) ." GMT");
        if($this->mode == 'js') {
            header('Content-Type:text/javascript');
        } else {
            header('Content-Type:text/css');
        }
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".strlen($this->result));
        echo $this->result;
        exit;
    }

    /**
     * 压缩代码
     * @param $code
     * @param string $mode
     * @return string
     */
    public static function minify($code, $mode = 'js') {
        if($mode == 'js') {
            $code = \JSMin\JSMin::minify($code);
        } else {
            $code = CssMin::minify($code,[
                "ImportImports"					=> false,
                "RemoveComments"				=> true,
                "RemoveEmptyRulesets"			=> true,
                "RemoveEmptyAtBlocks"			=> true,
                "ConvertLevel3Properties"		=> false,
                "ConvertLevel3AtKeyframes"		=> false,
                "Variables"						=> true,
                "RemoveLastDelarationSemiColon"	=> true
            ], [
                "Variables"						=> false,
                "ConvertFontWeight"				=> false,
                "ConvertHslColors"				=> false,
                "ConvertRgbColors"				=> false,
                "ConvertNamedColors"			=> false,
                "CompressColorValues"			=> false,
                "CompressUnitValues"			=> false,
                "CompressExpressionValues"		=> false
            ]);
        }
        return $code;
    }

    public function __toString() {
        if(empty($this->result)) $this->get();
        return $this->result;
    }
}