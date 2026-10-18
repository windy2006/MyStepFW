# Admin 应用开发与维护

整理日期：2026-10-18。目标运行环境：PHP 7.4。合并原开发文档和应用内 README，以最新模块结构、设置及界面行为为准。

## 应用结构

Admin遵循 [应用编写规范](../../app/README.md)：核心类位于app/Admin/Admin.class.php，直接继承myStep；辅助实现、功能模块、模板和资源都在应用目录。入口仍是 `/Admin/`，原 `/console/` 保留。PHP 7.4可运行，无新增Composer或前端依赖。

| 文件／目录 | 职责 |
| --- | --- |
| `app/Admin/Admin.class.php` | 同名核心类；认证、分发、校验、URL、模板装配、异常、审计与PRG |
| `app/Admin/lib.php` | 明确加载本应用辅助类 |
| `app/Admin/AdminService.class.php` | 受信模块加载器及跨模块辅助方法；不集中保存页面业务 |
| `app/Admin/AdminStore.class.php` | 应用文件版本、锁和原子发布 |
| `app/Admin/module/` | 21个功能模块，分别保存实际视图和动作回调 |
| `app/Admin/pages.php` | 路径到module、view、actions和菜单分组的声明 |
| `app/Admin/index.php`、`route.php` | 默认及显式路由入口 |
| `app/Admin/setting_expand.php` | 保存关联预检、菜单更新和CMS入口覆盖配置迁移 |
| `app/Admin/template/default/` | 各功能对应模板及共享组件 |
| `app/Admin/asset/` | 应用自己的响应式样式和脚本 |
| `app/Admin/config.php`、`config/`、`language/`、`plugin.php` | 当前配置、编辑说明、语言及插件绑定 |

公用lib中的myAdmin、myAdminStore已移除，不保留应用专属兼容壳。原后台类加载设置改用myClassLoader::save，两个后台共用类规则校验、版本比较和原子保存，旧后台不依赖Admin应用。其他公用框架类仍按其原有职责使用。

## 执行流程与安全边界

1. 框架识别Admin、合并配置、加载同名核心类；preload只装配受信页面清单和服务，不提前执行业务。
2. 框架建立服务、会话及认证上下文，按配置收集资源。
3. route中的Admin::dispatch或默认index进入同一个handle；未登录统一显示原有登录表单，受保护页面检查用户名与sysop。
4. pages中的module和回调名均来自服务端固定声明。AdminService按需加载module脚本，检查结果为回调数组，未登记方法拒绝调用。
5. GET／HEAD执行对应视图；POST只接受已登记动作和校验字段。框架负责CSRF，插件、包和设置关联操作使用对应互斥锁。
6. 模块调用Admin::view创建myTemplate、赋值和循环，返回子模板HTML；动作返回提示／重定向描述或文件下载描述。
7. Admin::page组装导航与主模板，沿用框架show的资源、安全装饰和输出流程；成功写入通常303跳转，避免刷新重复提交。

输入错误422、版本冲突409、未知页面404、不支持方法405、未预期错误500。配置和插件方法直接被PHP调用时不会因此自动完成HTTP鉴权与CSRF，不能另行公开为未经保护的接口。

## 模块与模板对应

主映射见本文“当前模块与模板目录”。模块保持info、setting、function分组，与info_*、setting_*、function_*模板对应；guide、samples、references各自对应同名模板。插件安装与设置共用plugin_setting模块及两个用途模板。

模块返回具名闭包数组。闭包由AdminService的方法作用域加载，因此 `$this` 是当前服务，可使用 `$this->ui` 调用Admin核心和私有公共辅助方法。它们是真正的业务实现，不是从模块再转调一个巨型业务类。

新增页面的步骤：建立module和对应tpl，在pages登记固定module／view及必要actions，补齐字段校验、权限、资源和语言；新增模块文件会按需加载，无须扩展公用类目录。

例如在pages登记工具页面：

```php
'tools/example' => [
    'title' => '工具示例',
    'group' => '工具',
    'module' => 'tools/example',
    'view' => 'example',
],
```

对应module/tools/example.php：

```php
<?php
/** 功能说明：工具示例读取页面；由受信模块加载器调用。 */
return [
    /**
     * 功能说明：向对应模板提供示例数据。
     * 参数说明：无显式参数。
     * @return string 已渲染的功能模板
     */
    'example' => function() {
        return $this->ui->view('tools_example', ['message' => '示例内容']);
    },
];
```

对应template/default/tools_example.tpl：

```html
<section class="adm-panel"><h2>工具示例</h2><p><!--message|html--></p></section>
```

这只是新增功能的示例，未向正式应用增加该工具页面。普通新应用仍以自己的同名类继承myStep，不以Admin作为另一层公用框架基类。

## 设置保存及关联文件

设置模块先校验密码确认和配置结构，调用本应用setting_expand准备关联操作，再按原版本原子保存配置并执行关联更新。

- 全局或myStep后台入口变化时更新旧菜单链接。
- CMS后台入口变化时同步config_入口名.php，迁移前检查目标冲突和文件版本。
- 关联操作失败时，只有当前配置仍是本次写出的版本才尝试回退，避免覆盖并发请求的新内容。
- 框架依据配置指纹自动重建路由。保存过程没有直接包含依赖旧后台PATH／局部变量的其他应用setting_expand脚本；本应用明确实现上述等效关联。

文件写入保留乐观锁、旁路锁及PHP语法预检。跨应用文件写入与插件钩子仍不是完整数据库事务，不应假定任意第三方安装钩子可以自动回滚。

## 与原后台功能对应

| 原后台功能 | 新后台路径/操作 | 实现说明 |
| --- | --- | --- |
| 基本信息 | `/Admin/` | 框架、服务器及运行环境概览 |
| 数据库信息及浏览 | `db` | 实际表白名单，只读浏览，分页50行 |
| PHP信息、phpinfo | `php`、`phpinfo` | 管理员保护的环境信息 |
| 错误日志 | `error` | 显示末尾最多2MiB；POST清空、GET完整下载（保留旧POST下载入口） |
| 全局及应用参数设置 | `setting?target=...` | 原有myConfig结构、字段类型及密码确认；内容版本冲突检测 |
| 类加载设置 | `setting/class` | 可增删行，扩展名检查，PHP数组形式的特别指定，目录为根相对路径，保留首个核心类目录 |
| 类别名 | `setting/alias` | 白名单类，重复和已占用名称检查 |
| 方法说明及方法别名 | `setting/detail?class=...` | 反射公开非魔术方法和PHPDoc，验证映射目标 |
| 域名绑定 | `setting/domain` | 完整映射编辑，可增删行 |
| 代码测试 | `setting/execute` | GET只查看示例源码，POST显式执行，输出保留上限1MiB |
| 应用清单、设置／语言入口、路由、插件列表 | `function/app` | 语法预检、版本检测；独立保存插件路由来源 |
| 应用包上传 | `function/app`上传表单 | `app_标识.app`包；只发布不存在的项目 |
| 缓存管理 | `function/cache` | 登记类别容量和POST清理；数据缓存调用框架清理接口 |
| 插件清单、启停、排序 | `function/plugin` | 共用simpleDB注册表，排序在单个排他锁内完成 |
| 插件检查、安装、设置、应用绑定 | `function/plugin/view`、`function/plugin/setting` | 执行真实插件接口，安装前检查条件并保留强制应用绑定 |
| 插件卸载、删除 | `function/plugin`操作表单 | 卸载调用插件钩子，删除仅允许未安装项目 |
| 插件包导出 | `function/plugin`打包 | 使用副本清理运行配置，原插件目录不受打包修改 |
| 插件包上传 | `function/plugin`上传表单 | `plugin_标识.plugin`；受控暂存、完整预检、发布 |
| 附件管理 | `function/attachment` | 年/月浏览；区分日志附件与未记录附件，精确删除 |
| 语言编辑/新增 | `function/language` | 支持应用和语种选择，保留未修改键，可另存新语种 |
| 框架指南 | `guide` | 按主题读取现有Markdown，分类目录、章节定位、跨文档跳转，Parsedown安全模式 |
| 功能示例 | `samples?sample=...` | 在新布局复用原示例模板和依赖资料 |
| 应用示例、功能类文档 | `references` | 进入原Sample、Document应用 |
| 插件增加的导航 | 侧栏“扩展” | 从原后台菜单的插件分组同步链接，仍进入相应插件自己的页面 |
| 初次安装初始化 | 框架统一入口 | 继续使用缺少全局配置时的安装流程，无第二套初始化数据 |


## 模板、资源与保留行为

模板继续使用path/style/name、assign、setLoop和setIf，支持自定义风格及default回退。HTML布局留在tpl，选项按钮、当前项标记、原生phpinfo样式、空日志禁用按钮和行编辑器行为保留。

资源沿用框架的 addJS、模板 page_start/page_end 和应用 asset 加载机制；基础 jQuery、global.js、checkForm.js 从网站本地提供，应用不新增 CDN、外部字体或额外表单校验库。功能示例按既有路由进入原示例应用，相关第三方组件继续由原应用及现有 vendor 管理。

客户端表单提交统一调用本地 `checkForm(form)`，处理 need、len、密码确认及原生 required/min/max/pattern 约束，不在 Admin 脚本重复编写格式校验器。校验脚本缺失时阻止提交并提示刷新；手写必填字段及通用 field 控件明确输出 need，配置字段继续由 myConfig::build 按原结构生成。浏览器自身的原生交互检查保留。规则详见[JavaScript 功能与 need 规则](../framework/javascript.md#need-格式规则)。服务端继续保留动作参数、权限和 CSRF 校验。

850px及以下采用固定顶栏和可收起下拉导航，Esc和点击外部关闭。原有登录、退出、日志下载、共享配置、插件注册表、附件和语言编辑沿用原有入口及数据。

## 验证

`tests/admin/admin_regression.php`验证结构、模块回调、延迟加载、配置关联及共享服务；`tests/admin/admin_http_fixture.py`验证真实临时PHP站点、新旧后台、鉴权、CSRF、下载和三种路由；`tests/core/path_regression.php`验证类规则原子保存和路径；`tests/admin/admin_javascript.js`验证原有交互。业务写入仅在隔离测试目录进行。

## 当前模块与模板目录

| 模块 | 对应模板 |
| --- | --- |
| `module/info/main.php` | `info_main.tpl` |
| `module/info/db.php` | `info_db.tpl`，以及数据库单元格组件 |
| `module/info/php.php`、`phpinfo.php`、`error.php` | `info_php.tpl`、`info_phpinfo.tpl`、`info_error.tpl` |
| `module/setting/main.php` | `setting_main.tpl`、配置字段组件 |
| `module/setting/class.php`、`domain.php`、`alias.php`、`detail.php`、`execute.php` | 相应的 `setting_*.tpl` |
| `module/function/app.php`、`cache.php`、`plugin.php` | 相应的 `function_*.tpl` |
| `module/function/plugin_setting.php` | `function_plugin_view.tpl`、`function_plugin_setting.tpl` |
| `module/function/attachment.php`、`language.php` | `function_attachment.tpl`、`function_language.tpl` |
| `module/guide.php` | `guide.tpl`、`guide_group.tpl`；共用myStep应用内的只读文档阅读器 |
| `module/samples.php`、`references.php` | 相应的同名模板 |
| `module/manager.php` | `manager.tpl`，保留旧`/Admin/manager/`兼容入口 |
| `plugin_layout.php` → `Admin::pluginLayout()` | 通用`plugin.tpl`，包装任意标准插件管理页，外层使用Admin当前模板path/style |

## 近期界面及功能约定

- 参数设置使用 config_section／config_item 模板提供独立分组、子设置所属路径及斑马纹；别名、语言和表格保持一致的行区分。
- 域名绑定只显示实际保存行，不自动附加空行；可从空表通过模板新增行。
- 应用管理在标题旁提供预选目标的“设置”“语言管理”，不可编辑时显示禁用原因；语言默认选择依次为 default、当前语言、首个可用文件。
- 插件启停按钮显示当前状态的相反动作；运行顺序表按数值从大到小展示。普通页面实际插件初始化顺序的现有边界见 [插件规范](../../plugin/README.md)。
- 缓存“全部清理”覆盖七类缓存和当前数据驱动；包含会话，操作前提示可能需要重新登录，保留管理锁及审计目录。
- 新旧后台图片展示、图片墙、延迟加载共用 myStep 应用内六张图片，来源见 [SOURCES](../../app/myStep/asset/samples/SOURCES.md)。
- 850px 及以下使用固定顶栏和可收起菜单；phpinfo 保留限定容器内的原生样式；空日志的下载及清空按钮禁用。

维护入口仍通过 pages.php 的 module 固定映射定位实现。完整规范见 [应用编写规范](../../app/README.md)，验证命令及隔离范围见 [测试说明](../../tests/README.md)。

## 插件公共流程同步（2026-10-18）

AdminService::bindPlugin委托插件公共setPluginApps，不再另写一套应用清单改写；安装前也检查元信息声明的强制应用。运行顺序委托setPluginOrder，与旧后台保持稳定顺序和无效输入保留语义；启停用同一次simpleDB排他读改写，避免并发丢失切换。独立插件API的主类由框架按需加载，Admin无需为了调用API绑定其页面初始化脚本。

具体函数、参数及限制见[插件规范](../../plugin/README.md#15-生命周期一致性与独立-api)。多文件I/O及插件生命周期仍没有全局事务回滚。

packPlugin也委托公共buildPluginPackage，与旧后台使用相同的临时副本、命名、敏感配置过滤和下载清理规则。


## 通用插件后台布局（2026-10-18）

侧栏扩展继续链接到插件原路由。所有使用默认`showPluginPage()`的管理页从Admin进入时，复用同一个`plugin_layout.php`及`template/default/plugin.tpl`，无需逐个登记Admin模块。主体来自插件自身模板，外层导航、响应式菜单和本地资源来自Admin。自定义风格可同时覆盖`main.tpl`与`plugin.tpl`，继续使用原模板配置及default回退。

首次GET只在原地址补`_plugin_app=Admin`；刷新、表单、分页和操作返回保留标记，POST直接处理，继续由原路由校验方法及CSRF。空Referer且无标记默认myStep，不记忆上次选择。`Admin::page()`和公共适配器共用`layout()`，适配期间不重新启动框架、会话或数据库，结束后恢复模板及应用上下文。原`/Admin/manager/`入口继续兼容。协议、变量与示例见[插件规范第18节](../../plugin/README.md#18-插件后台通用布局适配)。


## 框架指南文档中心（2026-10-18）

`guide?doc=文档标识`从固定目录选择正文，默认显示docs/README.md，缺少文档目录的精简安装可回退readme.md。按主题区分规范、开发教程、核心机制、前端、维护／测试及具体项目资料；文档在各自原目录维护，管理后台不保存重复副本。

阅读器放在框架核心应用`app/myStep/MyStepGuide.class.php`，Admin模块显式加载，展示继续使用自身模板path/style及default回退；共用本地guide.css，仅影响指南区域。已登记文档之间的相对链接转换为当前后台地址，章节锚点兼容中文并避开固顶栏；代码块和宽表支持横向滚动，窄屏目录可收起。仅读取固定清单中的Markdown，拒绝任意路径、数组参数和越界链接，不直接提供PHP源码；不存在的资料返回404并提供目录入口。
