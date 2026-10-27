# 插件页面

说明showPluginPage与setPluginTemplate的页面装配和主体模板使用。

后台页面复用 `showPluginPage()`：进行框架管理员检查，加载插件模板及可选模块，再与宿主布局组合。`setPluginTemplate()` 等辅助函数负责模板装配，不表示调用者天然有写入权限。功能模块可通过 `$tpl_sub` 赋值，或按约定提供 `$content`。

后台插件页使用 `showPluginPage($idx, $page)`，先验证登录，再加载 `template/$page.tpl` 及可选的 `module/$page.php`；省略页名时使用插件目录名。模块可通过 `$tpl_sub` 赋值，或设置 `$content` 直接提供主体内容。

若需将插件内容嵌入当前应用，可调用：

```php
list($outer, $inner) = setPluginTemplate('hellonote', 'hellonote', false);
$inner->assign('message', 'Hello');
$outer->assign('main', $mystep->render($inner));
$mystep->show($outer);
```

第三个参数 `false` 复用应用的全局 `$tpl_setting`/`$tpl_cache` 创建外层模板，调用后不修改这两个全局变量；默认 `true` 使用框架后台 `main.tpl`。内容子模板始终关闭页面缓存，防止覆盖外层的整页缓存；外层启用缓存时仍需自行按身份、语言等设置隔离维度。

[插件规范](README.md)
