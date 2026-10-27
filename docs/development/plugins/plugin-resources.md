# 插件资源

说明插件CSS、JavaScript的归属、登记方式与宿主页面的加载关系。

插件资源由自己明确登记，地址支持部署前缀，不能写固定 `/console/`、`/vendor/` 或本机域名。配置、语言异步就绪后再调用依赖它们的 JavaScript。写操作使用 POST／相应方法、CSRF 和业务授权；页面按钮不是服务端权限控制。

### 页面、资源与宿主的关系

普通index.php在宿主控制器上下文执行，`$this`是宿主，不是plugin_类。独立route.php只声明路由，实际业务函数放入lib.php或class.php明确加载；不能依赖普通index已经运行。

```php
// plugin/hello/index.php：资源位于自己的插件目录。
$this->addCSS(PLUGIN.'hello/asset/style.css');
$this->addJS(PLUGIN.'hello/asset/function.js');
```

后端只登记资源及受信数据。浏览器事件、AJAX和checkForm的具体JS放入自己的脚本，参见浏览器前端。设置管理页面优先调用showPluginPage，按宿主上下文选择适配模板；Referer仅可辅助选择外观，不能决定管理员权限。

[插件规范](README.md)
