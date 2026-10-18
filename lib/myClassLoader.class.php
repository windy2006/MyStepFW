<?php
/*******************************************
*                                          *
* Name    : Class Registry Tools (new)     *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/myClassLoader.class.php
 * 功能说明：类加载规则校验与配置管理工具
 *   统一类加载配置的读取、相对路径转换、数组文本解析及带版本检查的原子保存。
 *
 * 方法目录：
 *   public static myClassLoader::load($file) — 读取并在锁内迁移旧类目录，保留自定义规则
 *   public static myClassLoader::save($file, array $rules, $version) — 校验并在版本锁内原子保存类加载规则
 *   public static myClassLoader::mapping($text) — 安全解析特别指定的PHP数组或历史JSON文本
 *   public static myClassLoader::normalize(array $rules, $check = false) — 规范根相对目录及目录相对文件映射
 *
 * 使用说明：
 *   1. 启动时使用 load() 读取规则，配置保存前通过 normalize() 校验路径和映射。
 *   2. mapping() 解析字符串映射的 PHP 数组字面量或历史 JSON，不执行输入中的 PHP 代码。
 *   3. 配置文件保存为 return array(...)；path 相对于网站根，idx 值相对于该规则的 path。
 *   4. save() 接收读取时的 SHA-256 内容版本；实际类注册由 myController::regClass() 执行。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：统一框架启动与配置编辑使用的类加载规则语义。
 *
 * 第一条规则必须指向核心 lib 目录，特别指定映射相对于所属规则目录。
 * 解析配置文本不执行用户提供的代码；load() 仍会包含受信的配置 PHP 文件。
 * 规则发布使用旁路锁和原子替换，save() 同时检查内容版本，避免覆盖并发编辑。
 *
 * 方法目录：参见本文件顶部。
 */
class myClassLoader {
    /**
     * 功能说明：读取启动类索引，兼容迁移项目内历史绝对目录；损坏索引重建默认规则。
     *
     * @param string $file 受信类配置PHP文件，通常CONFIG.class.php
     * @return array 含rules规则列表和reset标志；仅reset为true时需重建相关路由/模板缓存
     * @throws RuntimeException 迁移锁或原子发布失败
     */
    public static function load($file) {
        $read = function() use ($file) {
            $raw = [];
            if(is_file($file)) {
                if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
                try { $raw = (static function($path) { return include $path; })($file); } catch(ParseError $e) { $raw = []; }
            }
            try { $rules = is_array($raw)?self::normalize($raw):[]; } catch(InvalidArgumentException $e) { $rules = []; }
            $reset = !$rules || realpath(myPath::absolute($rules[0]['path'])) !== realpath(LIB);
            if($reset) $rules = [
                ['path'=>'lib/', 'ext'=>'.php,.class.php', 'idx'=>['interface_plugin'=>'../plugin/interface_plugin.class.php']],
                ['path'=>'lib/database/', 'ext'=>'.php,.class.php', 'idx'=>[]]
            ];
            return [$raw, $rules, $reset];
        };
        list($raw, $rules, $reset) = $read();
        if($raw !== $rules) {
            $lock = fopen($file.'.lock', 'c');
            if(!$lock) throw new RuntimeException('Cannot lock class registry');
            if(!flock($lock, LOCK_EX)) { fclose($lock); throw new RuntimeException('Cannot lock class registry'); }
            $tmp = '';
            try {
                // 与后台保存共用旁路锁，并重新读取，避免迁移覆盖并发编辑。
                list($raw, $rules, $reset) = $read();
                if($raw !== $rules) {
                    $tmp = $file.'.'.bin2hex(random_bytes(8)).'.tmp';
                    $content = "<?php\nreturn ".var_export($rules, true).";\n";
                    if(file_put_contents($tmp, $content, LOCK_EX) !== strlen($content)) throw new RuntimeException('Cannot save class registry');
                    if(is_file($file)) chmod($tmp, fileperms($file)&0777);
                    if(!rename($tmp, $file)) throw new RuntimeException('Cannot publish class registry');
                    if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
                }
            } finally {
                if($tmp !== '' && is_file($tmp)) unlink($tmp);
                flock($lock, LOCK_UN); fclose($lock);
            }
        }
        return ['rules'=>$rules, 'reset'=>$reset];
    }

    /**
     * 功能说明：校验类加载规则，并在共享旁路锁内比较原版本后原子保存。
     *
     * @param string $file 网站根内的受信类配置文件路径
     * @param array $rules 目录、扩展名及特别指定规则，保存前按网站根规范化
     * @param string $version 浏览时的SHA256内容版本；文件不存在时为missing
     * @return bool 保存成功返回true
     * @throws InvalidArgumentException 规则、版本或配置目标无效
     * @throws RuntimeException 版本冲突（code=409）或配置发布失败
     */
    public static function save($file, array $rules, $version) {
        if(!is_string($file) || !is_string($version))throw new InvalidArgumentException('类配置路径或版本无效。');
        $file = myPath::absolute(myPath::relative($file));
        $rules = self::normalize($rules, true);
        if(is_link($file))throw new InvalidArgumentException('不能写入符号链接。');
        if(!is_dir(dirname($file)) && !mkdir(dirname($file), 0777, true))throw new RuntimeException('无法创建类配置目录。');
        $lock = fopen($file.'.lock', 'c');
        if(!$lock)throw new RuntimeException('无法打开类配置锁。');
        $tmp = '';
        try {
            if(!flock($lock, LOCK_EX))throw new RuntimeException('无法锁定类配置。');
            clearstatcache(true, $file);
            if(!hash_equals(is_file($file)?hash_file('sha256', $file):'missing', $version))throw new RuntimeException('保存冲突：类配置已改变，请重新加载。', 409);
            $content = "<?php\nreturn ".var_export($rules, true).";\n";
            $tmp = $file.'.'.bin2hex(random_bytes(8)).'.tmp';
            if(file_put_contents($tmp, $content, LOCK_EX) !== strlen($content))throw new RuntimeException('类配置写入不完整。');
            if(is_file($file))chmod($tmp, fileperms($file)&0777);
            if(!rename($tmp, $file))throw new RuntimeException('无法发布类配置。');
            if(function_exists('opcache_invalidate'))opcache_invalidate($file, true);
            return true;
        } finally {
            if($tmp !== '' && is_file($tmp))unlink($tmp);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * 功能说明：解析特别指定文本，只接受PHP数组字面量；兼容旧新版JSON对象。
     *
     * @param string $text array('类名'=>'相对文件')、等价[]语法或历史JSON；空串视为空数组
     * @return array 类名到相对文件路径；不执行函数、变量、include或其他PHP代码
     * @throws InvalidArgumentException 语法无效或不是字符串映射
     */
    public static function mapping($text) {
        if(!is_string($text)) throw new InvalidArgumentException('特别指定必须为PHP数组文本。');
        $text = trim($text);
        if($text === '') return [];
        if($text[0] === '{') {
            $out = json_decode($text, true);
            if(!is_array($out) || json_last_error() !== JSON_ERROR_NONE) throw new InvalidArgumentException('特别指定的JSON格式无效。');
        } else {
            $tokens = [];
            foreach(token_get_all('<?php '.$text) as $token) {
                if(is_array($token) && in_array($token[0], [T_OPEN_TAG,T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true)) continue;
                $tokens[] = $token;
            }
            $i = 0;
            if(isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] === T_ARRAY) {
                $i++;
                if(($tokens[$i++] ?? null) !== '(') throw new InvalidArgumentException('特别指定须为array(...)。');
                $end = ')';
            } elseif(($tokens[$i++] ?? null) === '[') $end = ']';
            else throw new InvalidArgumentException('特别指定须为PHP数组。');
            $string = function() use (&$tokens, &$i) {
                $token = $tokens[$i++] ?? null;
                if(!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) throw new InvalidArgumentException('特别指定只允许带引号的类名和文件路径。');
                $quote = $token[1][0]; $value = substr($token[1], 1, -1);
                if($quote === "'") return str_replace(["\\'",'\\\\'], ["'",'\\'], $value);
                return preg_replace_callback('/\\\\(?:[nrtvef\\\\$\"]|[0-7]{1,3}|x[0-9a-f]{1,2})/i', function($m) { return stripcslashes($m[0]); }, $value);
            };
            $out = [];
            while(($tokens[$i] ?? null) !== $end) {
                $key = $string(); $arrow = $tokens[$i++] ?? null;
                if(!is_array($arrow) || $arrow[0] !== T_DOUBLE_ARROW) throw new InvalidArgumentException('特别指定需要类名=>文件路径。');
                $out[$key] = $string();
                if(($tokens[$i] ?? null) === $end) break;
                if(($tokens[$i++] ?? null) !== ',') throw new InvalidArgumentException('特别指定的数组项目须以逗号分隔。');
            }
            $i++;
            if(($tokens[$i] ?? null) === ';') $i++;
            if($i !== count($tokens)) throw new InvalidArgumentException('特别指定不能包含可执行代码。');
        }
        foreach($out as $name => $file) {
            if(!is_string($name) || !preg_match('/\A[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(?:\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*\z/D', $name) || !is_string($file)) throw new InvalidArgumentException('特别指定必须为类名到文件路径的数组。');
        }
        return $out;
    }
    /**
     * 功能说明：统一类目录、扩展名和特别指定路径，持久化时只保留相对路径。
     *
     * @param array $rules 含path/ext/idx的规则列表，idx为已解析数组
     * @param bool $check true时检查目录存在、非符号链接并保留首条核心目录；默认false供启动兼容转换
     * @return array 数字索引规则列表；path相对于ROOT，idx值相对于该path并可使用../访问项目内其他目录
     * @throws InvalidArgumentException 配置类型、扩展名、目录或特别指定越界
     */
    public static function normalize(array $rules, $check = false) {
        $out = [];
        foreach($rules as $row) {
            if(!is_array($row) || !is_string($row['path'] ?? null) || !is_string($row['ext'] ?? null) || !is_array($row['idx'] ?? [])) throw new InvalidArgumentException('类加载规则无效。');
            if(trim($row['path']) === '') continue;
            $path = rtrim(myPath::relative(trim($row['path'])), '/');
            $path = $path === ''?'./':$path.'/';
            $ext = trim($row['ext']);
            if(!preg_match('/\A\.[a-zA-Z.]+(?:,\.[a-zA-Z.]+)*\z/D', $ext)) throw new InvalidArgumentException('载入类型须为逗号分隔的扩展名。');
            if($check && (!is_dir(myPath::absolute($path)) || (is_link(rtrim(myPath::absolute($path), '/')) && realpath(myPath::absolute($path)) !== realpath(LIB)))) throw new InvalidArgumentException('所在目录不存在或为符号链接。');
            $idx = self::mapping(var_export($row['idx'] ?? [], true));
            foreach($idx as &$file) {
                $file = str_replace('\\', '/', $file);
                if($file === '' || preg_match('#^(?:/|[a-z]:)#i', $file)) throw new InvalidArgumentException('特别指定的文件必须相对于所在目录。');
                myPath::relative($path.$file);
            }
            unset($file);
            $out[] = ['path'=>$path, 'ext'=>$ext, 'idx'=>$idx];
        }
        if($check && (!$out || realpath(myPath::absolute($out[0]['path'])) !== realpath(LIB))) throw new InvalidArgumentException('第一条规则必须保留核心类库目录lib/。');
        return $out;
    }
}
