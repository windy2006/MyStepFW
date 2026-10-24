<?php
/**
 * 文件：tests/applications/document_regression.php
 * 功能说明：执行Document新增样例及真实索引、方法说明，验证顶层类覆盖和演示数据隔离。
 * 方法目录：documentCheck、documentTest、documentPage（参数说明见各函数PHPDoc）。
 * 使用说明：PHP 7.4 CLI运行；仅创建随机临时项目，不连接业务数据库、不发网络请求。
 */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = getenv('MYSTEP_DOCUMENT_SOURCE') ?: dirname(__DIR__, 2);
$scratch = sys_get_temp_dir().'/mystep-document-'.bin2hex(random_bytes(8));
mkdir($scratch, 0700);
define('ROOT', $scratch.'/');
define('LIB', $source.'/lib/');
define('APP', ROOT.'app/');
define('PATH', APP.'Document/');
define('CACHE', ROOT.'cache/');
define('CONFIG', ROOT.'config/');
define('VENDOR', $source.'/vendor/');
define('ROOT_WEB', '/sub/');
foreach([PATH.'module', CACHE.'tmp', CACHE.'compiled', CONFIG] as $dir) mkdir($dir, 0700, true);
symlink(rtrim(LIB, '/'), ROOT.'lib');
symlink($source.'/app/Document/template', PATH.'template');
foreach(glob($source.'/app/Document/module/*.php') as $file) copy($file, PATH.'module/'.basename($file));
copy($source.'/app/Document/index.php', PATH.'index.php');
spl_autoload_register(function($name) {
    foreach([LIB.$name.'.class.php', LIB.'database/'.$name.'.class.php', LIB.'cache/'.$name.'.class.php'] as $file) {
        if(is_file($file)) { require_once $file; return; }
    }
});
require_once LIB.'function.php';
class_alias('myFile', 'f');
require_once $source.'/config/config_default.php';
file_put_contents(CONFIG.'config.php', '<?php return '.var_export($setting, true).';');
$ms_setting = new myConfig(CONFIG.'config.php');
$ms_setting->router->mode = 'rewrite';
$_SERVER += ['HTTP_HOST'=>'document.fixture', 'REQUEST_METHOD'=>'GET', 'REQUEST_URI'=>'/sub/Document/', 'DOCUMENT_ROOT'=>ROOT, 'REQUEST_TIME'=>time()];
$_SESSION = ['_ms_csrf'=>str_repeat('a', 64), 'document_sentinel'=>'unchanged'];
$db = new stdClass();
$mystep = new class($ms_setting) {
    /** @var myConfig 示例页面使用的内存配置 */
    public $setting;
    /**
     * 功能说明：为真实Document入口提供设置对象。
     * @param myConfig $setting 临时项目配置
     * @return void
     */
    public function __construct($setting) { $this->setting = $setting; }
};
set_error_handler(function($severity, $message, $file, $line) {
    if(error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$passed = $failed = 0;
ob_start(); // 页面示例会设置响应类型，CLI测试也保留输出缓冲以便检查。

/**
 * 功能说明：断言真实运行结果，不满足时抛出测试错误。
 * @param bool $condition 是否通过
 * @param string $message 失败说明
 * @return void
 * @throws RuntimeException 断言失败
 */
function documentCheck($condition, $message) {
    if(!$condition) throw new RuntimeException($message);
}
/**
 * 功能说明：运行一个独立检查并累计结果。
 * @param string $name 用例名称
 * @param callable $callback 用例实现
 * @return void
 */
function documentTest($name, $callback) {
    global $passed, $failed;
    try { $callback(); $passed++; echo 'PASS '.$name."\n"; }
    catch(Throwable $error) { $failed++; echo 'FAIL '.$name.': '.$error->getMessage()."\n"; }
}
/**
 * 功能说明：通过实际入口渲染列表、样例或方法说明，保留调用方输出缓冲。
 * @param string $name 样例名，空串为索引
 * @param string $mode show或detail，默认show
 * @return string 实际模板HTML
 */
function documentPage($name, $mode = 'show') {
    global $mystep, $ms_setting, $db;
    $info_app = ['app'=>'Document', 'path'=>$name === '' ? [] : [$name, $mode]];
    $tpl_setting = ['name'=>'main', 'path'=>PATH.'template/', 'style'=>'', 'path_compile'=>CACHE.'compiled/'];
    ob_start();
    try {
        include PATH.'index.php';
        return $tpl->assign('charset', 'UTF-8')->assign('path_root', ROOT_WEB)->render('', false);
    } finally { ob_end_clean(); }
}

try {
    $examples = ['myApi','myArchive','myAttachment','myAuth','myBase','myClassLoader','myDb','myEncrypt','myPath','myProxy','mySecurity','myTrait'];
    $checks = [
        'myApi'=>['原始结果（整数）','XML响应','MYSTEP'],
        'myArchive'=>['统一发布成功','MyStep 暂存发布示例'],
        'myAttachment'=>['示例文档.txt','删除后不再找到'],
        'myAuth'=>['example-user','不同用途被拒绝'],
        'myBase'=>['构造时初始化','手动延迟初始化'],
        'myClassLoader'=>['interface_plugin','拒绝过期版本覆盖'],
        'myDb'=>['MyStep 示例','回滚后记录数'],
        'myEncrypt'=>['字符串往返一致','文件明文恢复一致'],
        'myPath'=>['app/Document/template/','拒绝越过网站根目录'],
        'myProxy'=>['欢迎，开发者','通过引用增加'],
        'mySecurity'=>['正文超限的检查结果','413'],
        'myTrait'=>['MyStep 框架','你好，开发者'],
    ];
    documentTest('every top-level framework class or trait has a sample entry', function()use($source) {
        foreach(glob($source.'/lib/*.class.php') as $file) {
            $name = basename($file, '.class.php');
            documentCheck(is_file(PATH.'module/'.$name.'.php'), 'Missing sample: '.$name);
        }
    });
    foreach($examples as $name) {
        documentTest($name.' sample executes, escapes source, and leaves shared data intact', function()use($name, $checks) {
            global $db;
            $beforeDb = $db; $beforeSession = $_SESSION;
            $beforeConfig = hash_file('sha256', CONFIG.'config.php');
            $html = documentPage($name);
            documentCheck(strpos($html, '<html') !== false && strpos($html, '</html>') !== false, 'Incomplete HTML');
            documentCheck(strpos($html, 'brush:php') !== false, 'Missing highlighted source');
            documentCheck(strpos($html, htmlspecialchars(file_get_contents(PATH.'module/'.$name.'.php'))) !== false, 'Source was not escaped intact');
            $result = explode('<h5>运行结果：</h5>', $html, 2)[1] ?? '';
            foreach($checks[$name] as $text) documentCheck(strpos($result, $text) !== false, 'Missing actual result: '.$text);
            $expectedTrue = [
                'myArchive'=>['拒绝越界成员','拒绝重复成员','统一发布成功'],
                'myAttachment'=>['索引登记成功','存储标识匹配','附件及索引记录删除成功','删除后不再找到'],
                'myAuth'=>['摘要使用v2前缀','正确密码','历史摘要仍可校验','用途及签名正确','不同用途被拒绝'],
                'myClassLoader'=>['拒绝过期版本覆盖'],
                'myDb'=>['支持参数化','支持受管事务','异常已回滚'],
                'myEncrypt'=>['字符串往返一致','错误密钥被拒绝','文件明文恢复一致','错误密钥保留文件'],
                'myPath'=>['根目录以斜杠结束','拒绝越过网站根目录'],
                'mySecurity'=>['当前会话令牌有效','非法令牌被拒绝'],
            ];
            foreach($expectedTrue[$name] ?? [] as $label) documentCheck(strpos($result, '['.$label.'] =&gt; 1') !== false, 'Unexpected result for '.$label);
            if($name === 'myDb') documentCheck(strpos($result, '[回滚后记录数] =&gt; 1') !== false, 'Rollback did not preserve the first row');

            documentCheck($db === $beforeDb && $_SESSION === $beforeSession, 'Shared DB/session changed');
            documentCheck(hash_file('sha256', CONFIG.'config.php') === $beforeConfig, 'Project configuration changed');
            documentCheck(glob(CACHE.'tmp/document-*') === [], 'Demo directory was not cleaned');
            documentCheck(!is_file(ROOT.'outside.txt'), 'Path escaped its root');
        });
        documentTest($name.' method page includes every documented reflected method', function()use($name) {
            $html = documentPage($name, 'detail');
            $reflection = new ReflectionClass($name);
            documentCheck(strpos($html, $name) !== false, 'Missing class title');
            foreach($reflection->getMethods() as $method) {
                documentCheck((bool)$method->getDocComment(), 'Undocumented method: '.$method->getName());
                documentCheck(strpos($html, '<b>'.$method->getName().'</b>') !== false, 'Missing method: '.$method->getName());
            }
            documentCheck(strpos($html, '@return') !== false, 'Missing return documentation');
        });
    }
    documentTest('index lists every new class and the trait with both entry links', function()use($examples) {
        $html = documentPage('');
        foreach($examples as $name) {
            documentCheck(strpos($html, '<option>'.$name.'</option>') !== false, 'Missing option: '.$name);
            documentCheck(strpos($html, 'href="'.$name.'"') !== false, 'Missing sample link: '.$name);
            documentCheck(strpos($html, 'href="'.$name.'/detail"') !== false, 'Missing method link: '.$name);
        }
    });
} finally {
    restore_error_handler();
    myFile::del($scratch);
}
echo "Passed: $passed; Failed: $failed\n";
ob_end_flush();
exit($failed ? 1 : 0);
