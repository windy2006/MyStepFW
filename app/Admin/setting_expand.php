<?php
/**
 * 文件：app/Admin/setting_expand.php
 * 功能说明：准备设置保存后的关联处理，保持共享配置与原后台菜单、CMS入口配置一致。
 * 方法目录：设置扩展准备回调，以及返回的保存后执行回调。
 * 使用说明：由module/setting/main.php显式加载，先预检、保存当前配置，再执行返回回调。
 * 兼容说明：不直接包含依赖旧后台局部变量和PATH的其他应用脚本；在本应用实现对应关联。
 */
/**
 * 功能说明：验证入口变化，捕获关联文件版本并返回保存后的更新动作。
 * @param string $target 设置目标；空串为全局配置
 * @param array $before 保存前的配置值
 * @param array $data 待保存的配置值
 * @return Closure 无参数关联更新回调，成功时无返回值
 * @throws InvalidArgumentException 入口名无效、目标冲突或关联文件不安全
 */
return function($target, array $before, array $data) {
    $menuChange = $entryChange = null;
    if($target === '' || $target === 'myStep') {
        $old = $before['gen']['path_admin']??null;
        $new = $data['gen']['path_admin']??null;
        if($old !== null && $new !== null && $old !== $new) {
            $file = APP.'myStep/menu.json';
            $version = AdminStore::version($file);
            $menu = json_decode(myFile::getLocal($file), true);
            $walk = function(&$value) use (&$walk, $old, $new) {
                if(!is_array($value))return;
                foreach($value as $key => &$part) {
                    if($key === 'link' && is_string($part) && myRouter::hasPrefix('/'.$part, '/'.$old))$part = $new.substr($part, strlen($old));
                    elseif(is_array($part))$walk($part);
                }
            };
            $walk($menu);
            if(is_array($menu))$menuChange = [$file, json_encode($menu, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $version];
        }
    }
    if($target === 'CMS') {
        $old = $before['web']['path_admin']??null;
        $new = $data['web']['path_admin']??null;
        if($old !== null && $new !== null && $old !== $new) {
            if(!is_string($old) || !is_string($new) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $old) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $new))throw new InvalidArgumentException('CMS后台入口须为单段字母、数字或下划线标识。');
            $source = APP.'CMS/config_'.$old.'.php';
            $destination = APP.'CMS/config_'.$new.'.php';
            if(is_link($source) || is_link($destination))throw new InvalidArgumentException('入口配置不能是符号链接。');
            if(is_file($source)) {
                if(file_exists($destination))throw new InvalidArgumentException('新的CMS入口配置已存在。');
                $entryChange = [$source, $destination, AdminStore::version($source)];
            }
        }
    }
    /**
     * 功能说明：原子保存关联菜单，或在源文件锁内迁移CMS入口覆盖配置。
     * 参数说明：无显式参数，使用准备阶段捕获的受信路径和内容版本。
     * @return void 完成关联更新；路由由框架根据配置指纹自动重建
     * @throws RuntimeException 关联文件冲突或迁移失败
     */
    return function() use ($menuChange, $entryChange) {
        if($menuChange)AdminStore::save($menuChange[0], $menuChange[1], $menuChange[2]);
        if($entryChange) {
            list($source, $destination, $version) = $entryChange;
            AdminStore::locked($source.'.lock', function() use ($source, $destination, $version) {
                clearstatcache();
                if(is_link($source) || file_exists($destination) || is_link($destination) || !hash_equals(AdminStore::version($source), $version))throw new RuntimeException('CMS入口配置已改变，请重新加载。', 409);
                if(!rename($source, $destination))throw new RuntimeException('无法迁移CMS入口配置。');
                if(function_exists('opcache_invalidate')) {
                    opcache_invalidate($source, true);
                    opcache_invalidate($destination, true);
                }
            });
        }
    };
};
