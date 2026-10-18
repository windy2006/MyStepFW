<?php
/*******************************************
*                                          *
* Name    : Project Path Tools (new)       *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/myPath.class.php
 * 功能说明：网站根相对路径规范化工具
 *   在可迁移的根相对配置与运行时绝对文件路径之间转换，不依赖当前工作目录。
 *
 * 方法目录：
 *   public static myPath::root() — 读取入口定义的项目根目录
 *   public static myPath::absolute($path) — 将网站根相对路径解析为运行时绝对路径
 *   public static myPath::relative($path) — 将项目内路径规范为可迁移的根相对路径
 *   public static myPath::settings(array $data, $app = 'myStep') — 规范框架配置中的本地路径
 *
 * 使用说明：
 *   1. 配置保存前调用 relative()；使用文件系统 API 时调用 absolute()。
 *   2. 根目录优先使用入口定义的 ROOT，独立使用类库时回退到 lib 的上级目录。
 *   3. settings() 规范模板、文件会话及本地数据库路径；URL 和数据库服务器地址不属于本地路径。
 *   4. 特别指定的类文件先与所属类目录组合，再转换为根相对路径或运行时绝对路径。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：分离项目根相对配置与运行时绝对路径，兼容项目内历史绝对路径。
 *
 * 本类负责路径表达和边界规范化，不检查文件访问权限。
 * 对外文件操作仍须使用 myFile 的路径边界及文件类型检查。
 *
 * 方法目录：参见本文件顶部。
 */
class myPath {
    /**
     * 功能说明：读取入口定义的项目根目录，独立使用类库时回退到类库的上级目录。
     *
     * 参数说明：无显式参数。
     *
     * @return string 以/结尾的绝对项目根，不取DOCUMENT_ROOT或当前工作目录
     */
    public static function root() {
        return rtrim(str_replace('\\', '/', defined('ROOT') ? ROOT : dirname(__DIR__)), '/').'/';
    }
    /**
     * 功能说明：将相对网站根的设置转换为运行时绝对路径。
     *
     * @param string $path 项目根相对路径；兼容历史绝对路径，运行时绝对路径原样规范分隔符
     * @return string 绝对路径，不依赖getcwd；目录尾部/按输入保留
     * @throws InvalidArgumentException 路径不是字符串、含控制字符或 URL/流包装器
     */
    public static function absolute($path) {
        if(!is_string($path) || preg_match('/[\x00-\x1f\x7f]/', $path) || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) throw new InvalidArgumentException('无效的项目路径。');
        $path = str_replace('\\', '/', $path);
        // 绝对路径只供兼容已存在的运行时API；配置保存必须再经过relative验证。
        if(preg_match('#^(?:/|[a-z]:/)#i', $path)) return $path;
        return self::root().self::relative($path);
    }
    /**
     * 功能说明：消除./和目录内的..，把项目内绝对路径转换为网站根相对路径。
     *
     * @param string $path 相对路径或项目内历史绝对路径；根目录可写.或空串
     * @return string 不以/开头的根相对路径；目录尾部/按输入保留
     * @throws InvalidArgumentException 控制字符、协议、绝对路径越界或..越过网站根
     */
    public static function relative($path) {
        if(!is_string($path) || preg_match('/[\x00-\x1f\x7f]/', $path) || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) throw new InvalidArgumentException('路径必须相对于网站根目录。');
        $path = str_replace('\\', '/', $path);
        $root = self::root();
        if(preg_match('#^(?:/|[a-z]:/)#i', $path)) {
            if($path === rtrim($root, '/')) return '';
            if(strpos($path, $root) !== 0) {
                $real = realpath($path); $realRoot = realpath($root);
                if($real === false || $realRoot === false) throw new InvalidArgumentException('路径不能位于网站根目录之外。');
                $real = str_replace('\\', '/', $real); $root = rtrim(str_replace('\\', '/', $realRoot), '/').'/';
                if($real === rtrim($root, '/')) return '';
                if(strpos($real, $root) !== 0) throw new InvalidArgumentException('路径不能位于网站根目录之外。');
                $path = $real.(substr($path, -1) === '/'?'/':'');
            }
            $path = substr($path, strlen($root));
        }
        if(strpos($path, ':') !== false) throw new InvalidArgumentException('路径不能包含盘符或协议。');
        $directory = substr($path, -1) === '/';
        $parts = [];
        foreach(explode('/', $path) as $part) {
            if($part === '' || $part === '.') continue;
            if($part === '..') {
                if(!$parts) throw new InvalidArgumentException('路径不能越过网站根目录。');
                array_pop($parts);
            } else $parts[] = $part;
        }
        return implode('/', $parts).($directory && $parts ? '/' : '');
    }
    /**
     * 功能说明：将框架模板、文件会话及本地数据库路径规范为根相对配置。
     *
     * @param array $data 配置数组，其他字段原样保留
     * @param string $app 当前应用目录名，默认myStep；仅用于兼容旧template路径
     * @return array 路径规范后的配置；旧template/...转换为app/当前应用/template/...
     * @throws InvalidArgumentException 路径越界或类型无效
     */
    public static function settings(array $data, $app = 'myStep') {
        if(isset($data['template']['path'])) {
            $path = $data['template']['path'];
            if(is_string($path) && preg_match('#^(?:\./)?template(?:/|$)#', $path)) $path = 'app/'.$app.'/'.preg_replace('#^\./#', '', $path);
            $data['template']['path'] = self::relative($path);
        }
        if(isset($data['session']['path']) && !empty($data['session']['path'])) $data['session']['path'] = self::relative($data['session']['path']);
        if(isset($data['db']['host']) && in_array(strtolower($data['db']['type'] ?? ''), ['sqlite'], true) && $data['db']['host'] !== ':memory:') $data['db']['host'] = self::relative($data['db']['host']);
        return $data;
    }
}
