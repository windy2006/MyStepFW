<?php
/**
 * 文件：plugin/guide.php (new)
 * 功能说明：独立应用／插件Markdown说明，共用安全读取、链接转换、标题锚点及页面输出。
 * 方法目录：pluginGuide::__construct、identifier、url、source、read、settings、document、anchor、localLink。
 * 使用说明：由框架固定guide入口访问；应用／插件默认对应自己的README，引用的公开Markdown
 *           使用受限ref参数。仅阅读文件，不启动应用或Session，不执行插件元信息。
 *           require_once本文件只加载声明；直接访问或index的固定分发才输出页面。
 */
class pluginGuide {
    /** @var string 受信的插件根目录，构造时固定 */
    private $root;
    /** @var string 当前网站根URL，含末尾斜杠 */
    private $web;
    /** @var string 宿主框架生成的指南入口，保留rewrite、PATH_INFO或查询串形式 */
    private $baseURL;
    /** @var string plugin或app，固定公开说明的所属目录 */
    private $kind;

    /**
     * 功能说明：固定应用／插件根、说明类型及网站URL根，不从请求接受文件路径。
     * @param string $root 服务器提供的应用或插件根目录
     * @param string $web 网站根URL，如/或/site/（默认：'/'）
     * @param string|null $baseURL 框架生成的指南入口；null默认rewrite形式（默认：null）
     * @param string $kind 固定说明类型plugin或app，不接受请求指定磁盘目录（默认：plugin）
     * @throws InvalidArgumentException 根目录、类型或URL不合法
     */
    public function __construct($root, $web='/', $baseURL=null, $kind='plugin') {
        if(!in_array($kind, ['plugin','app'], true)) throw new InvalidArgumentException('Invalid guide kind');
        $this->kind=$kind;
        $real=is_string($root)?realpath($root):false;
        if($real===false || !is_dir($real) || !is_string($web) || !preg_match('~\A/(?!/)[^\x00-\x20\x7f?\#\\\\]*\z~D',$web)) throw new InvalidArgumentException('Invalid plugin guide root');
        $this->root=rtrim($real,'/').'/';
        $this->web=rtrim($web,'/').'/';
        if($baseURL===null) $baseURL=$this->web.$kind.'/guide';
        if(!is_string($baseURL) || !preg_match('~\A/(?!/)[^\x00-\x20\x7f\#\\\\]*\z~D',$baseURL)) throw new InvalidArgumentException('Invalid plugin guide URL');
        $this->baseURL=$baseURL;
    }

    /**
     * 功能说明：验证应用／插件标识，禁止路径片段或数组等非字符串参数。
     * @param mixed $idx 应用或插件目录名
     * @return string 已验证的原始插件标识
     * @throws InvalidArgumentException 标识不合法
     */
    public static function identifier($idx) {
        if(!is_string($idx) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$idx)) throw new InvalidArgumentException('Invalid plugin guide identifier');
        return $idx;
    }

    /**
     * 功能说明：按宿主框架提供的入口生成指南及标题锚点，不依赖插件启用状态。
     * @param string $idx 当前类型的应用或插件目录名
     * @param string $fragment 标题锚点，不含#（默认：''）
     * @param string $ref 允许的公开Markdown根相对路径；空为本插件README（默认：''）
     * @return string 原始URL，写入HTML属性前需转义
     */
    public function url($idx, $fragment='', $ref='') {
        self::identifier($idx);
        if($ref!=='') $this->source($idx,$ref);
        return $this->baseURL.(strpos($this->baseURL,'?')===false?'?':'&').'idx='.rawurlencode($idx).($ref===''?'':'&ref='.rawurlencode($ref)).($fragment===''?'':'#'.rawurlencode($fragment));
    }

    /**
     * 功能说明：限制阅读当前应用／插件的README、历史、事件及引用的公开开发资料。
     * @param string $idx 当前应用／插件名，目录及info.php须存在；不执行该文件
     * @param string $ref 网站根相对Markdown路径；空为当前README（默认：''）
     * @return string 已校验的根相对文档路径，不接受请求传入任意文件
     * @throws OutOfBoundsException 不是允许的公开Markdown文档
     */
    public function source($idx, $ref='') {
        self::identifier($idx);
        if(!is_string($ref)) throw new OutOfBoundsException('Invalid guide reference');
        $dir=$this->root.$idx;
        if(is_link($dir) || !is_dir($dir) || !is_file($dir.'/info.php')) throw new OutOfBoundsException('Plugin not found');
        $path=$ref===''?$this->kind.'/'.$idx.'/README.md':$ref;
        $allowed=in_array($path,['readme.md','plugin/README.md','app/README.md','docs/README.md'],true)
            || preg_match('~\Adocs/(?:basics|backend|frontend|development|showcase|history)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.md\z~D',$path)
            || in_array($path,[$this->kind.'/'.$idx.'/README.md',$this->kind.'/'.$idx.'/CHANGELOG.md',$this->kind.'/'.$idx.'/EVENTS.md'],true);
        if(!$allowed) throw new OutOfBoundsException('Guide reference is not public documentation');
        return $path;
    }

    /**
     * 功能说明：读取受限公开Markdown，逐段拒绝符号链接及越界路径。
     * @param string $idx 当前类型的应用或插件目录名
     * @param string $ref 根相对公开Markdown路径；空为本插件README（默认：''）
     * @return string UTF-8 Markdown原文，上限2MiB
     * @throws OutOfBoundsException 文档缺失、不可读、过大、编码错误或路径不安全
     */
    public function read($idx, $ref='') {
        $path=$this->source($idx,$ref);$segments=explode('/',$path);
        $file=$segments[0]===$this->kind?rtrim($this->root,'/'):dirname($this->root);
        if($segments[0]===$this->kind)array_shift($segments);
        foreach($segments as $segment) {
            $file.='/'.$segment;
            if(is_link($file)) throw new OutOfBoundsException('Symbolic guide path rejected');
        }
        if(!is_file($file) || !is_readable($file)) throw new OutOfBoundsException('Plugin document not found');
        $text=file_get_contents($file,false,null,0,2097153);
        if(!is_string($text) || strlen($text)>2097152 || preg_match('//u',$text)!==1) throw new OutOfBoundsException('Plugin document is unreadable');
        return $text;
    }

    /**
     * 功能说明：从真实标题提取设置项锚点，跳过围栏代码中的示例标题。
     * @param string $text README原文，支持LF及CRLF
     * @return array setting-参数名及field-控件名到true的字典
     */
    public static function settings($text) {
        $anchors=[];$fence='';$length=0;
        foreach(preg_split('/\r\n|\n|\r/',$text) as $line) {
            if(preg_match('/^ {0,3}(`{3,}|~{3,})(.*)$/',$line,$mark)) {
                if($fence==='') {$fence=$mark[1][0];$length=strlen($mark[1]);}
                elseif($mark[1][0]===$fence && strlen($mark[1])>=$length && trim($mark[2])==='') $fence='';
                continue;
            }
            if($fence==='' && preg_match('/^### (setting-[a-z0-9_-]+|field-[a-z0-9_-]+)[ \t]*$/',$line,$heading))$anchors[$heading[1]]=true;
        }
        return $anchors;
    }

    /**
     * 功能说明：安全解析单篇公开Markdown，按标题生成稳定且去重的锚点和本篇目录。
     * @param string $idx 当前类型的应用或插件目录名
     * @param string $ref 已允许的公开Markdown路径；空为本插件README（默认：''）
     * @return array idx/title/html/outline/url；outline项目含title/id/level；另含path、home_url与reference
     * @throws RuntimeException Markdown依赖或解析失败
     * @throws OutOfBoundsException README不可读取
     */
    public function document($idx, $ref='') {
        $source=$this->source($idx,$ref);
        $text=$this->read($idx,$ref);
        if(!class_exists('Parsedown',false)) require_once dirname($this->root).'/vendor/erusev/parsedown/Parsedown.php';
        $parser=new Parsedown();$parser->setSafeMode(true);
        $dom=new DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        try {$loaded=$dom->loadHTML('<?xml encoding="UTF-8"><div id="plugin-document">'.$parser->text($text).'</div>',LIBXML_NONET|LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);}
        finally {libxml_clear_errors();libxml_use_internal_errors($previous);}
        if(!$loaded) throw new RuntimeException('Cannot render plugin README');
        $xpath=new DOMXPath($dom);$ids=[];$outline=[];$title=$idx;
        $headings=$xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        foreach($headings as $heading) {
            $label=trim($heading->textContent);$version='';
            // 只为标准标题中的数字版本生成small；其他原始HTML仍由安全模式转义。
            if($heading->nodeName==='h1' && preg_match('/\A(.+?)\s+<small>(v[0-9]+\.[0-9]+\.[0-9]+)<\/small>\z/u',$label,$parts)) {
                $label=$parts[1];$version=$parts[2];
                while($heading->firstChild)$heading->removeChild($heading->firstChild);
                $heading->appendChild($dom->createTextNode($label.' '));
                $small=$dom->createElement('small');$small->setAttribute('class','plugin-guide-version');
                $small->appendChild($dom->createTextNode($version));$heading->appendChild($small);
            }
            if($heading->nodeName==='h1' && $title===$idx)$title=$label.($version===''?'':' '.$version);
            $slug=self::anchor($label);
            if($slug==='')$slug='section';$id=$slug;$suffix=0;
            while(isset($ids[$id]))$id=$slug.'-'.(++$suffix);
            $ids[$id]=true;$heading->setAttribute('id',$id);
            $level=(int)substr($heading->nodeName,1);
            if($level===2 || $level===3)$outline[]=['id'=>$id,'title'=>$label,'level'=>$level];
        }
        foreach($xpath->query('//a[@href]') as $link) {
            $target=$this->localLink($idx,$link->getAttribute('href'),$source);
            if($target===null)$link->removeAttribute('href');
            else {
                $link->setAttribute('href',$target);
                if($target!=='' && $target[0]!=='#') {$link->setAttribute('target','_blank');$link->setAttribute('rel','noopener noreferrer');}
            }
        }
        foreach($xpath->query('//img[@src]') as $image) {
            $target=$this->localLink($idx,$image->getAttribute('src'),$source);
            if($target===null || $target==='' || $target[0]!=='/')$image->removeAttribute('src');
            else $image->setAttribute('src',$target);
        }
        $wrapper=$dom->getElementById('plugin-document');$html='';
        foreach($wrapper->childNodes as $node)$html.=$dom->saveHTML($node);
        return ['idx'=>$idx,'title'=>$title,'html'=>$html,'outline'=>$outline,'url'=>$this->url($idx,'',$ref),'path'=>$source,'home_url'=>$this->url($idx),'reference'=>$ref!==''];
    }

    /**
     * 功能说明：统一标题与引用锚点的大小写、标点及空格处理。
     * @param string $title 已通过UTF-8校验的标题或锚点
     * @return string 可用于id的标题标识；空标题由调用方补section
     */
    private static function anchor($title) {
        $value=mb_strtolower($title,'UTF-8');
        $value=preg_replace('/[^\pL\pN_\-\s]/u','',$value);
        return preg_replace('/\s+/u','-',trim($value));
    }

    /**
     * 功能说明：转换同篇锚点、公开Markdown引用和普通资源链接，保持当前路由模式。
     * @param string $idx 当前应用或插件名
     * @param string $href 原始Markdown链接
     * @param string $source 当前文档的网站根相对路径
     * @return string|null 安全链接；非法协议、越界、私有文档或源脚本返回null
     */
    private function localLink($idx, $href, $source) {
        if(!is_string($href) || preg_match('/[\x00-\x20\x7f\\\\]/',$href)) return null;
        if(preg_match('/\A(?:https?:|mailto:)/i',$href))return $href;
        if(preg_match('/\A(?:[a-z][a-z0-9+.-]*:|\/\/)/i',$href))return null;
        // parse_url会替换UTF-8中的C1字节，先按字节编码，再只对路径与锚点解码一次。
        $encoded=preg_replace_callback('/[\x80-\xff]/',static function($match) {return '%'.strtoupper(bin2hex($match[0]));},$href);
        $parts=parse_url($encoded);if($parts===false)return null;
        $path=rawurldecode($parts['path']??'');$fragment=rawurldecode($parts['fragment']??'');
        if(preg_match('//u',$fragment)!==1 || preg_match('/[\x00-\x1f\x7f]/',$fragment))return null;
        if($path==='')return $fragment===''?'':'#'.rawurlencode(self::anchor($fragment));
        if(preg_match('/[\x00-\x20\x7f\\\\]/',$path))return null;
        if($path[0]==='/') {
            $prefix=rtrim($this->web,'/');
            if($prefix!=='' && strpos($path,$prefix.'/')===0)$path=substr($path,strlen($prefix));
            $relative=$path;
        } else $relative=dirname($source).'/'.$path;
        $segments=[];
        foreach(explode('/',$relative) as $segment) {
            if($segment==='' || $segment==='.')continue;
            if($segment==='..') {if(!$segments)return null;array_pop($segments);}
            else $segments[]=$segment;
        }
        $relative=implode('/',$segments);
        if(preg_match('/\.md\z/i',$relative)) {
            $fragment=self::anchor($fragment);
            $owner=$idx;$reader=$this;
            if(preg_match('~\A(plugin|app)/([a-zA-Z][a-zA-Z0-9_]*)/(?:README|CHANGELOG|EVENTS)\.md\z~D',$relative,$match)) {
                $owner=$match[2];
                if($match[1]!==$this->kind) {
                    $base=preg_replace('~(?<![A-Za-z0-9_])'.preg_quote($this->kind,'~').'/guide(?=\.php|[/?&]|$)~',$match[1].'/guide',$this->baseURL);
                    // 物理兼容入口仅有plugin/guide.php；跨类型链接使用统一框架路由。
                    $base=preg_replace('~/app/guide\.php(?=[?]|$)~','/app/guide',$base);
                    try {$reader=new self(dirname($this->root).'/'.$match[1],$this->web,$base,$match[1]);}
                    catch(InvalidArgumentException $error) {return null;}
                }
            }
            $kind=$reader->kind;
            $ref=$relative===$kind.'/'.$owner.'/README.md'?'':$relative;
            try {$reader->read($owner,$ref);return $reader->url($owner,$fragment,$ref);}
            catch(OutOfBoundsException|InvalidArgumentException $error) {return null;}
        }
        // 只保留可展示的资源或说明文件；PHP、数据库和配置路径不是阅读链接。
        $asset=preg_match('~\A(?:static/|vendor/|plugin/[A-Za-z][A-Za-z0-9_]*/asset/|app/[A-Za-z][A-Za-z0-9_]*/asset/)~D',$relative) && preg_match('/\.(?:js|css|json|map)\z/i',$relative);
        if(!$asset && !preg_match('/\.(?:png|jpe?g|gif|webp|avif|svg|pdf|txt)\z/i',$relative))return null;
        return $this->web.$relative.(isset($parts['query'])?'?'.$parts['query']:'').($fragment===''?'':'#'.rawurlencode($fragment));
    }
}

// 业务后台require_once时只取得上述声明，不输出页面或修改响应头。
if(!defined('MYSTEP_PLUGIN_GUIDE_WEB') && realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__) return;

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; frame-ancestors 'self'; form-action 'none'");
header('Cache-Control: no-cache');
$method=$_SERVER['REQUEST_METHOD']??'GET';
$kind=defined('MYSTEP_PLUGIN_GUIDE_KIND')?MYSTEP_PLUGIN_GUIDE_KIND:'plugin';
$guideLabel=$kind==='app'?'应用说明':'插件指南';
$ownerLabel=$kind==='app'?'应用':'插件';
$data=['title'=>$guideLabel,'html'=>'','outline'=>[],'idx'=>'','reference'=>false];$message='';
$script=$_SERVER['SCRIPT_NAME']??'/plugin/guide.php';
$web=defined('MYSTEP_PLUGIN_GUIDE_WEB')?MYSTEP_PLUGIN_GUIDE_WEB:(substr($script,-strlen('plugin/guide.php'))==='plugin/guide.php'?substr($script,0,-strlen('plugin/guide.php')):'/');
try {
    if(!in_array($method,['GET','HEAD'],true)) {http_response_code(405);header('Allow: GET, HEAD');$message=$guideLabel.'只接受读取请求。';}
    elseif(array_diff(array_keys($_GET),['idx','ref'])) {http_response_code(400);$message='请选择'.$ownerLabel.'及允许的说明文档。';}
    else {
        $baseURL=defined('MYSTEP_PLUGIN_GUIDE_URL')?MYSTEP_PLUGIN_GUIDE_URL:$web.'plugin/guide.php';
        $guide=new pluginGuide($kind==='app'?dirname(__DIR__).'/app':__DIR__,$web,$baseURL,$kind);
        $data=$guide->document($_GET['idx']??'',$_GET['ref']??'');
    }
} catch(InvalidArgumentException $error) {http_response_code(400);$message='请选择有效的'.$ownerLabel.'。';}
catch(OutOfBoundsException $error) {http_response_code(404);$message='说明文档不存在或不可读取。';}
catch(Throwable $error) {http_response_code(500);$message=$guideLabel.'暂时无法读取，请检查文档及Markdown组件。';}
if($method==='HEAD')exit;
$styleFile=dirname(__DIR__).'/static/css/pluginGuide.css';
$styleVersion=is_file($styleFile)?substr(hash_file('sha256',$styleFile),0,12):'0';

?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=htmlspecialchars($data['title'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?> · <?=htmlspecialchars($guideLabel,ENT_QUOTES,'UTF-8')?></title>
    <link rel="stylesheet" href="<?=htmlspecialchars($web.'static/css/pluginGuide.css?v='.$styleVersion,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?>">
</head>
<body>
<header class="plugin-guide-heading"><strong><?=htmlspecialchars($guideLabel,ENT_QUOTES,'UTF-8')?></strong><span><?=htmlspecialchars($data['idx'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?></span>
<?php if($data['reference']) { ?><a href="<?=htmlspecialchars($data['home_url'],ENT_QUOTES,'UTF-8')?>">返回<?=htmlspecialchars($ownerLabel,ENT_QUOTES,'UTF-8')?>说明</a><?php } ?></header>
<?php if($message!=='') { ?>
<main class="plugin-guide-error"><h1><?=htmlspecialchars($guideLabel,ENT_QUOTES,'UTF-8')?></h1><p role="alert"><?=htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?></p></main>
<?php } else { ?>
<div class="plugin-guide-layout">
    <aside class="plugin-guide-nav"><details open><summary>本篇目录</summary><nav aria-label="<?=htmlspecialchars($ownerLabel,ENT_QUOTES,'UTF-8')?>说明章节"><ul>
    <?php foreach($data['outline'] as $item) { ?>
        <li data-level="<?=(int)$item['level']?>"><a href="#<?=htmlspecialchars(rawurlencode($item['id']),ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($item['title'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?></a></li>
    <?php } ?>
    </ul></nav></details></aside>
    <main class="plugin-guide-document"><?=$data['html']?></main>
</div>
<?php } ?>
</body>
</html>
