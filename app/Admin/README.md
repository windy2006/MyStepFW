# Admin 应用

后台功能严格按 module 与 template 对应组织：`module/info/error.php` 对应 `template/default/info_error.tpl`，`module/function/api.php` 对应 `template/default/function_api.tpl`。页面读取、动作及模板赋值留在功能模块，HTML 留在模板。

根目录只保留应用核心 `Admin.class.php` 及框架约定文件。页面白名单与模块加载、版本校验和原子保存等公共辅助声明集中在既有 `lib.php`；不再单独保留 pages.php、服务类或存储类文件。核心类直接继承 myStep，保留原登录、权限、POST/CSRF 和模板风格机制。

- [通用 app 编写规范](../README.md)
- [应用实战](../../docs/development/app/README.md)
- [框架指南](../../docs/README.md)
