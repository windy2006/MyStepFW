# 脚本加载

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 8. 脚本加载

应用的 `lib.php` 保存复用函数及必要类加载；`global.php` 处理页面共享数据；`index.php` 只负责受控分发与主／子模板装配。不要在每个模块重复引入核心库或 Composer。

框架收集公共资源、应用基础 `asset/style.css`／`function.js` 及风格资源，其他文件通过现有 `addCSS()`／`addJS()` 等明确登记。资源整合按当前内容依赖更新，业务不能靠浏览器缓存“刚好失效”来保证新脚本生效。

```php
// 运行时登记本应用资源；保存到config.php的路径仍用网站根相对形式。
$mystep->addCSS(APP.'Hello/asset/detail.css');
$mystep->addJS(APP.'Hello/asset/detail.js');
```

依赖配置／语言的 JavaScript 在相关就绪点执行；表单加载本地 checkForm。参数合法性规则参考[前端表单说明](../../frontend/javascript/frontend-forms.md)，不要在每个应用再实现不同含义的 `need`。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 后端登记与前端执行的分工

| 工作 | 放置位置 | 结果 |
| --- | --- | --- |
| 注册本应用复用PHP函数 | `lib.php` | 函数由明确require加载 |
| 准备页面共用菜单、标题 | `global.php` | 当前页面数据，不是公开全局JS对象 |
| 追加CSS或JS资源 | 当前应用核心／模块 | 由addCSS/addJS进入现有资源收集 |
| 操作DOM、校验表单、AJAX | `asset/*.js` | 浏览器在依赖和DOM就绪后执行 |

```php
// 应用模块内；仅在编辑页登记本地资源。
$mystep->addCSS(APP.'Hello/asset/edit.css');
$mystep->addJS(APP.'Hello/asset/edit.js');
```

不要将`ROOT`或服务器路径打印成浏览器资源URL，也不要在index.php中内联整套JS。资源缓存失效按文件依赖处理；前端示例见[脚本加载、依赖与就绪](../../frontend/javascript/frontend.md)和[checkForm](../../frontend/javascript/frontend-forms.md)。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
