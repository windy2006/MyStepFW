<?php
/**
 * 文件：app/myStep/MyStepGuide.class.php
 * 功能说明：新旧后台共用的文档目录、Markdown阅读及站内文档链接转换。
 *
 * 方法目录：
 *   public MyStepGuide::__construct($baseURL) — 记录当前后台指南地址，不启动框架或会话。
 *   public static MyStepGuide::catalog() — 返回受信文档目录，所有路径相对于网站根。
 *   public MyStepGuide::url($id, $fragment = '') — 生成当前后台中的文档及章节地址。
 *   public MyStepGuide::page($id = null) — 读取已登记文档，准备正文、分类导航及章节目录。
 *   private MyStepGuide::resolveLink($href, $source) — 将相对文档引用转换为当前后台阅读地址。
 *   private MyStepGuide::render($text, $source) — 安全解析Markdown，补充章节锚点并修正链接。
 *
 * 使用说明：
 *   1. 管理入口验证登录后显式加载；new MyStepGuide(当前后台guide地址)->page(doc标识)。
 *   2. 只读固定清单中的Markdown，绝不将请求参数作为文件路径；不复制或保存文档副本。
 *   3. 返回的数据由各应用自己的guide及guide_group模板展示；正文不使用页面缓存。
 *   4. 依赖框架已有Parsedown、DOM和myFile，兼容PHP 7.4，无新增外部资源。
 */
/**
 * 功能说明：将现有框架文档组织为可在任一管理后台阅读的文档中心。
 * 方法目录：参见文件顶部。
 * 使用说明：本类只处理阅读数据，登录与HTTP状态由调用模块负责。
 */
class MyStepGuide {
    /** @var string 当前后台指南URL，支持框架的三种路由模式 */
    private $baseURL;

    /**
     * 功能说明：记录当前后台指南地址，不启动框架或会话。
     * @param string $baseURL 由框架生成的当前后台guide URL，不接受请求直接指定
     * @return void 初始化只读文档阅读器
     */
    public function __construct($baseURL) {
        $this->baseURL=$baseURL;
    }

    /**
     * 功能说明：返回受信文档目录，所有路径相对于网站根。
     * 参数说明：无显式参数。
     * @return array 文档标识到title、path、group的固定映射；应用及站点资料独立分组
     */
    public static function catalog() {
        $groups=[
            '开始阅读'=>[
                'index'=>['文档总览','docs/README.md'],
                'overview'=>['框架介绍','readme.md'],
                'execution'=>['框架执行流程','docs/framework/execution.md'],
            ],
            '应用与插件开发'=>[
                'app-rules'=>['应用编写规范','app/README.md'],
                'first-app'=>['开发第一个应用','docs/guides/first-application.md'],
                'plugin-rules'=>['插件编写规范','plugin/README.md'],
                'first-plugin'=>['开发第一个插件','docs/guides/first-plugin.md'],
            ],
            '框架核心'=>[
                'routing'=>['路由、配置与代理','docs/framework/routing-config-proxy.md'],
                'paths'=>['路径与类加载','docs/framework/paths.md'],
                'database'=>['数据库','docs/framework/database.md'],
                'cache'=>['缓存','docs/framework/cache.md'],
                'session'=>['Session','docs/framework/session.md'],
                'templates'=>['模板','docs/framework/templates.md'],
                'extensions'=>['接口与扩展','docs/framework/extensions.md'],
                'utilities'=>['通用工具','docs/framework/utilities.md'],
                'security'=>['请求安全与附件','docs/framework/security-attachments.md'],
                'php-runtime'=>['PHP运行时加固','docs/framework/php-runtime-hardening.md'],
            ],
            '前端开发'=>[
                'frontend'=>['前端文档入口','static/README.md'],
                'frontend-runtime'=>['加载流程与主要变量','static/docs/runtime.md'],
                'frontend-functions'=>['公共函数与示例','static/docs/functions.md'],
                'frontend-forms'=>['表单校验与need规则','static/docs/forms.md'],
                'frontend-extensions'=>['前端扩展及资源','static/docs/extensions.md'],
                'javascript'=>['JavaScript维护说明','docs/framework/javascript.md'],
            ],
            '维护与验证'=>[
                'dependencies'=>['Composer与依赖','docs/framework/dependencies.md'],
                'phpdoc'=>['lib索引与PHPDoc','docs/framework/phpdoc.md'],
                'tests'=>['测试目录与运行说明','tests/README.md'],
                'maintenance'=>['执行链与维护记录','docs/framework/maintenance.md'],
                'consolidation'=>['文档整理与迁移对照','docs/consolidation.md'],
            ],
            '应用、插件与站点资料'=>[
                'applications'=>['应用专属文档目录','docs/applications/README.md'],
                'app-layouts'=>['应用文件及脚本说明','docs/applications/layouts.md'],
                'admin'=>['Admin后台','docs/applications/Admin.md'],
                'mystep'=>['myStep核心应用','docs/applications/myStep.md'],
                'cms'=>['CMS应用','docs/applications/CMS.md'],
                'examples'=>['示例应用','docs/applications/examples.md'],
                'test-app'=>['test应用','docs/applications/test.md'],
                'plugins'=>['现有插件','docs/plugins/existing-plugins.md'],
                'deployment'=>['本机部署资料','docs/site/deployment.md'],
            ],
        ];
        $catalog=[];
        foreach($groups as $group=>$items) foreach($items as $id=>$item) $catalog[$id]=['title'=>$item[0],'path'=>$item[1],'group'=>$group];
        return $catalog;
    }

    /**
     * 功能说明：生成当前后台中的文档及章节地址。
     * @param string $id 已登记的文档标识
     * @param string $fragment 原始章节锚点，不含#（默认：''）
     * @return string 未经HTML转义的指南URL，保留query_string路由首段
     */
    public function url($id, $fragment = '') {
        $url=$this->baseURL.(strpos($this->baseURL,'?')===false?'?':'&').'doc='.rawurlencode($id);
        return $url.($fragment===''?'':'#'.rawurlencode($fragment));
    }

    /**
     * 功能说明：读取已登记文档，准备正文、分类导航及章节目录。
     * @param string|null $id 文档标识；空值优先总览，精简安装缺少总览时读取框架介绍（默认：null）
     * @return array id/title/path/html/groups/outline；groups内含title与links，outline含title/url/level
     * @throws OutOfBoundsException 标识不是字符串、不在固定清单或对应文档不可读
     */
    public function page($id = null) {
        $catalog=self::catalog();
        if($id===null || $id==='') $id=myFile::resolveWithin(ROOT,$catalog['index']['path'])?'index':'overview';
        if(!is_string($id) || !isset($catalog[$id])) throw new OutOfBoundsException('文档不存在，请从目录选择。');
        $entry=$catalog[$id];$file=myFile::resolveWithin(ROOT,$entry['path']);
        if(!$file || !is_readable($file)) throw new OutOfBoundsException('文档不存在或不可读取，请检查站点文档是否完整。');
        $text=file_get_contents($file);
        if($text===false) throw new OutOfBoundsException('文档读取失败，请从目录重新选择。');
        list($html,$outline)=$this->render($text,$entry['path']);
        $groups=[];
        foreach($catalog as $key=>$item) {
            if(!myFile::resolveWithin(ROOT,$item['path'])) continue;
            $groups[$item['group']][]=['title'=>$item['title'],'url'=>$this->url($key),'current'=>$key===$id?'page':'false'];
        }
        $navigation=[];
        foreach($groups as $title=>$links) $navigation[]=['title'=>$title,'links'=>$links];
        return ['id'=>$id,'title'=>$entry['title'],'path'=>$entry['path'],'html'=>$html,'groups'=>$navigation,'outline'=>$outline];
    }

    /**
     * 功能说明：将相对文档引用转换为当前后台阅读地址。
     * @param string $href Markdown中的链接；仅保留http、https、mailto外部协议
     * @param string $source 当前文档的网站根相对路径
     * @return string|null 内部阅读URL或合法外部链接；非文档源文件、越界及非法协议返回null
     */
    private function resolveLink($href, $source) {
        if(preg_match('/[\x00-\x20\x7f\\\\]/',$href)) return null;
        // PHP 7.4的parse_url可能将UTF-8中的C1字节替换为下划线，先逐字节编码再拆分。
        static $encoding=null;
        if($encoding===null) { $encoding=[];for($i=128;$i<256;$i++)$encoding[chr($i)]=sprintf('%%%02X',$i); }
        $parts=parse_url(strtr($href,$encoding));
        if($parts===false) return null;
        if(isset($parts['scheme'])) return in_array(strtolower($parts['scheme']),['http','https','mailto'],true)?$href:null;
        if(isset($parts['host'])) return null;
        $fragment=rawurldecode($parts['fragment']??'');
        $path=rawurldecode($parts['path']??'');
        if($path==='') $path=$source;
        elseif($path[0]!=='/') $path=dirname($source).'/'.$path;
        $segments=[];
        foreach(explode('/',$path) as $part) {
            if($part==='' || $part==='.') continue;
            if($part==='..') {if(!$segments)return null;array_pop($segments);}
            else $segments[]=$part;
        }
        $path=implode('/',$segments);
        foreach(self::catalog() as $id=>$entry) if($entry['path']===$path && myFile::resolveWithin(ROOT,$path)) return $this->url($id,$fragment);
        return null;
    }

    /**
     * 功能说明：安全解析Markdown，补充章节锚点并修正链接。
     * @param string $text 当前文档原文；代码块保留原有换行和内容
     * @param string $source 网站根相对文档路径，用于解析相对链接
     * @return array 第0项为正文HTML，第1项为h2/h3章节导航
     * @throws RuntimeException Markdown解析后的HTML无法读取
     */
    private function render($text, $source) {
        $md=new Parsedown();$md->setSafeMode(true);
        $html=$md->text($text);$dom=new DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        try {$loaded=$dom->loadHTML('<?xml encoding="UTF-8"><div id="guide-document">'.$html.'</div>',LIBXML_NONET|LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);}
        finally {libxml_clear_errors();libxml_use_internal_errors($previous);}
        if(!$loaded) throw new RuntimeException('Cannot render framework guide');
        $wrapper=$dom->getElementsByTagName('div')->item(0);$outline=[];$ids=[];$id='';
        foreach(self::catalog() as $key=>$entry) if($entry['path']===$source) {$id=$key;break;}
        $xpath=new DOMXPath($dom);
        foreach($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $title=trim($heading->textContent);
            $slug=mb_strtolower($title,'UTF-8');$slug=preg_replace('/[^\pL\pN_\-\s]/u','',$slug);$slug=preg_replace('/\s+/u','-',trim($slug));
            if($slug==='')$slug='section';$anchor=$slug;$suffix=0;
            while(isset($ids[$anchor])) $anchor=$slug.'-'.(++$suffix);
            $ids[$anchor]=true;$heading->setAttribute('id',$anchor);
            $level=(int)substr($heading->nodeName,1);
            if($level===2 || $level===3) $outline[]=['title'=>$title,'url'=>$this->url($id,$anchor),'level'=>(string)$level];
        }
        foreach($xpath->query('//a[@href]') as $link) {
            $href=$link->getAttribute('href');$target=$this->resolveLink($href,$source);
            if($target===null) {$link->removeAttribute('href');$link->setAttribute('class','guide-source');$link->setAttribute('title','源码或未收录资料：'.$href);}
            else {$link->setAttribute('href',$target);if(preg_match('#\Ahttps?://#i',$target)) {$link->setAttribute('target','_blank');$link->setAttribute('rel','noopener noreferrer');}}
        }
        $html='';foreach($wrapper->childNodes as $node) $html.=$dom->saveHTML($node);
        return [$html,$outline];
    }
}
