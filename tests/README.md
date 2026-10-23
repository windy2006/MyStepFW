# tests 文件汇总与运行说明

整理日期：2026-10-23。按实际脚本核对用途、入口、依赖和数据影响。当前共 55 个测试／夹具文件：35 个 PHP、11 个 Python、8 个 JavaScript、1 个 HTML；另有本说明。文件名含 browser 不一定需要浏览器，fixture 也不一定是独立测试入口，见下表。

## 1. 先区分两个目录

- 网站根的 **tests/**：开发者运行的自动化回归及配套夹具，不是网站应用或公开测试 API。
- **app/test/**：可在网站访问的框架执行流程演示，使用网站自己的配置和上下文。文件职责见 [test 演示应用](../docs/development/app/README.md)，不能用它代替本目录回归。

本目录 PHP 脚本限定 CLI，HTTP 访问返回 404。`.DS_Store` 属于系统元数据，不列入测试清单。所有命令均从网站根目录执行，php 必须指向 PHP 7.4；Node.js 和 Python 3 用于相应脚本。

## 2. 功能目录分类

所有脚本和资源均已移到下列功能目录；入口文件名保留，旧的根目录脚本路径不再使用。

| 目录 | 用途 | 文件数 |
| --- | --- | ---: |
| [core/](core/) | 核心流程、路由、启动与路径 | 6 |
| [security/](security/) | 输入预算、认证、账户与安全桥接 | 6 |
| [utilities/](utilities/) | 公共工具、编码与资源 | 4 |
| [extensions/](extensions/) | API管理、通用扩展和XML Excel | 3 |
| [templates/](templates/) | 模板与缓存兼容 | 1 |
| [database/](database/) | 数据库及能力接口 | 2 |
| [cache/](cache/) | 缓存驱动与SQLSRV替身 | 2 |
| [session/](session/) | Session处理器及HTTP协议 | 2 |
| [integration/](integration/) | 机制与跨调用联动 | 2 |
| [dependencies/](dependencies/) | Composer和压缩语义 | 2 |
| [javascript/](javascript/) | 公共JS与真实浏览器夹具 | 4 |
| [applications/](applications/) | 应用逻辑、CMS链接及模块 | 4 |
| [plugins/](plugins/) | 插件运行链路、生命周期、接口、模块、升级、缓存后端及解包器 | 11 |
| [admin/](admin/) | 新旧后台集成、框架指南与Admin界面 | 5 |
| [site/](site/) | 实际部署站点的HTTP冒烟 | 1 |

各目录的关联夹具随功能存放；跨功能调用通过明确路径定位。根目录只保留此说明及系统目录元数据。

## 3. 环境与执行分组

| 分组 | 运行前提 | 数据及服务范围 |
| --- | --- | --- |
| 本地逻辑及文件测试 | PHP 7.4 和相应扩展／vendor；部分用例需创建子进程、符号链接和文件锁 | 合成数据、系统临时目录或独立 SQLite；按具体文件准备 GD、Zip、mbstring/iconv 等能力 |
| 可选 MySQL 模式 | 表中标注 --database 的脚本，另需已配置的 mysqli 连接 | 有的使用现有库中的连接级临时表，有的创建随机独立库；两类不可混为一谈 |
| 默认就连接 MySQL | account_workflow、password_confirmation_regression、app_database_regression、plugin_modules | 不等待 --database 开关；前三者用临时表，plugin_modules 的备份场景创建随机空库 |
| 自建 HTTP／SMTP 服务 | Python 3，可用回环端口，PHP 可执行文件；HTTPS 另需 OpenSSL | 在临时目录启动服务并自动请求和清理；HTTPS 夹具还会运行 MySQL 安全场景 |
| 浏览器交互夹具 | javascript_browser_server.py 启动后，用真实浏览器打开打印的两个地址 | 真实 DOM／事件、模拟表单和回环 Ajax；报告写入独立结果目录 |
| 实际站点冒烟 | http_smoke.py 依赖本机域名、ServBay、PHP 站点及受信 CA | 请求 mystep.com 等现有站点；不是临时站点或离线测试 |

SQLite、Memcached 和 SQLSRV 替身检查不代表相应真实服务器均已集成验证。随机 MySQL 库需要 CREATE／DROP DATABASE 权限；连接级临时表则按对应脚本要求准备。正常结束时由 finally／连接关闭清理；强制终止后按本次随机名称核对残留，不清理其他业务对象。

## 4. PHP 文件逐项说明

以下命令是主入口，内部 worker 参数由脚本自行传递。

### 框架基础与执行流程

| 文件 | 功能 | 运行方式 | 依赖／影响范围 |
| --- | --- | --- | --- |
| [core_regression.php](core/core_regression.php) | 基础缓存、SQL、路由、压缩、模板、上传及 Session 兼容 | `php tests/core/core_regression.php` | 默认临时文件；--database 连接已配置 MySQL，使用连接级临时表等隔离对象 |
| [router_regression.php](core/router_regression.php) | 三种 URL 输入、捕获、规则顺序、格式、并发登记与私有路径 | `php tests/core/router_regression.php` | 临时应用和路由文件，无业务数据库 |
| [flow_bootstrap.php](core/flow_bootstrap.php) | 配置缺失／初始化、安装分流及子目录启动 | `php tests/core/flow_bootstrap.php` | 临时 config/app/plugin/cache；FLOW_BASE 可切换 /sub/ |
| [execution_regression.php](core/execution_regression.php) | 实例、权限委派、钩子／模块、路由指纹、类索引和 OPcache | `php tests/core/execution_regression.php` | 临时应用与独立子进程；内部模式由主入口安排 |
| [path_regression.php](core/path_regression.php) | ROOT 相对路径、类映射纯数据解析、原子保存、跨工作目录加载 | `php tests/core/path_regression.php` | 临时网站根和类文件 |

### 安全、账户与通用工具

| 文件 | 功能 | 运行方式 | 依赖／影响范围 |
| --- | --- | --- | --- |
| [request_limits.php](security/request_limits.php) | 已解析输入累计预算、长度溢出、循环/深层数组及非法配置 | `php tests/security/request_limits.php` | CLI小型合成数据，不制造OOM、不修改站点配置 |
| [security_regression.php](security/security_regression.php) | 密码与签名令牌、认证、请求来源及 TLS 客户端边界 | `php tests/security/security_regression.php` | 默认临时文件；--database 使用 MySQL 临时表；TLS 子场景由 https_fixture.py 提供服务 |
| [account_workflow.php](security/account_workflow.php) | CMS 管理员与用户账户修改的真实模块流程 | `php tests/security/account_workflow.php` | 直接连接已配置 MySQL 的 mystep_cms；使用 workflow_* TEMPORARY 表，无 --database 开关 |
| [password_confirmation_regression.php](security/password_confirmation_regression.php) | CMS 后台管理员／用户密码确认及保留原密码行为 | `php tests/security/password_confirmation_regression.php` | 直接连接 mystep_cms；使用 confirm_* TEMPORARY 表和子进程 |
| [utility_regression.php](utilities/utility_regression.php) | 文件、请求、上传、字符串、异常、归档、图像、邮件、加密和字典 | `php tests/utilities/utility_regression.php` | 合成数据及临时目录；MIME 构建不投递外部邮件 |
| [core_improvements.php](utilities/core_improvements.php) | 附件定位、CSRF、Minify 指纹／并发、图片预算、归档 CRC／回滚及大包 | `php tests/utilities/core_improvements.php` | 临时文件；自动派生守卫、压缩和受限内存子进程 |
| [mechanism_regression.php](integration/mechanism_regression.php) | 路由、对象代理、配置类型／保存、数据库及缓存共用机制 | `php tests/integration/mechanism_regression.php` | 默认临时文件／SQLite；--database 建立并删除随机 mystep_review_* 库 |
| [api_management.php](extensions/api_management.php) | 框架API分组、多语种结构、专属域名、策略保存及旧配置迁移 | `php tests/extensions/api_management.php` | CLI临时配置，不登记或执行业务回调 |
| [extension_regression.php](extensions/extension_regression.php) | myApi、myTrait、myBase 与 XML Excel 生成 | `php tests/extensions/extension_regression.php` | 临时数据及受限内存的大工作簿子进程 |
| [charset_regression.php](utilities/charset_regression.php) | 严格／容错转换、BOM、编码推测、对象／JSON 和文件转换 | `php tests/utilities/charset_regression.php` | 合成字节及临时文件；自动验证禁用 mbstring 的回退 |
| [template_regression.php](templates/template_regression.php) | 变量隔离、标准块、旧 switch 语义、转义、缓存依赖和并发冷编译 | `php tests/templates/template_regression.php` | 临时模板及缓存；不执行业务查询 |

### 数据库、缓存、会话及依赖

| 文件 | 功能 | 运行方式 | 依赖／影响范围 |
| --- | --- | --- | --- |
| [database_regression.php](database/database_regression.php) | SQL 构造、参数化、事务、导入导出、simpleDB 并发及 MSSQL 调用约定 | `php tests/database/database_regression.php` | 默认 SQLite／临时文件／SQLSRV 替身；--database 建立并删除 mystep_db_review_* 库 |
| [interface_regression.php](database/interface_regression.php) | 独立自动加载、历史签名、可选能力接口、代理回调身份 | `php tests/database/interface_regression.php` | 默认隔离进程及 SQLite；--database 建立并删除 mystep_interface_review_* 库 |
| [cache_regression.php](cache/cache_regression.php) | V3／旧格式、精确命中、序列化预算、GC、锁、故障和并发计算 | `php tests/cache/cache_regression.php` | 默认私有文件／SQLite／替身；--database 建立并删除 mystep_cache_review_* 库 |
| [session_regression.php](session/session_regression.php) | 文件／MySQL／CMS 处理器、ID、锁、过期、GC、迁移及统计隔离 | `php tests/session/session_regression.php` | 默认私有文件／子进程；--database 建立并删除 mystep_session_review_* 库 |
| [integration_regression.php](integration/integration_regression.php) | 数据库与缓存挂接、清理入口、CMS TTL 调用及示例隔离 | `php tests/integration/integration_regression.php` | 默认临时目录／隔离替身；--database 建立并删除 mystep_integration_review_* 库 |
| [composer_regression.php](dependencies/composer_regression.php) | 锁文件／安装版本、Markdown、JS/CSS 压缩、旧 pack 参数和更新清单 | `php tests/dependencies/composer_regression.php` | 读取现有 vendor，压缩写入私有缓存；不执行 composer install/update |

### 应用、插件与管理后台

| 文件 | 功能 | 运行方式 | 依赖／影响范围 |
| --- | --- | --- | --- |
| [cms_link_regression.php](applications/cms_link_regression.php) | 真实 CMS 模板与链接辅助函数、筛选／分页、域名绑定和三种路由 | `php tests/applications/cms_link_regression.php` | 不连接数据库；CMS_LINK_* 选择组合；--fixtures 输出供 Node 使用的夹具 |
| [app_regression.php](applications/app_regression.php) | CMS 栏目／标签／公共 SQL 逻辑，Sample 公开配置及 test 分发 | `php tests/applications/app_regression.php` | 临时文件和替身，不连接业务数据库 |
| [app_database_regression.php](applications/app_database_regression.php) | CMS 查询、栏目、标签、统计、验证码、密码及锁定模块 | `php tests/applications/app_database_regression.php` | 直接使用已配置 MySQL 连接和随机 TEMPORARY 表；由父进程运行各场景 |
| [plugin_runtime.php](plugins/plugin_runtime.php) | 真实核心的活动顺序、绑定去重、前置脚本和停用路由/API | `php tests/plugins/plugin_runtime.php` | 临时注册表、应用及脚本，不修改网站插件状态 |
| [plugin_regression.php](plugins/plugin_regression.php) | 六个插件的公共逻辑、路径／配置、封禁、资源和状态边界 | `php tests/plugins/plugin_regression.php` | 临时插件目录／SQLite／替身，不执行正式插件升级或安装 |
| [plugin_interface.php](plugins/plugin_interface.php) | 46项：菜单、路由、优先脚本、原子写入、模板、绑定并发／稳定排序、通用插件布局与上下文恢复 | `php tests/plugins/plugin_interface.php` | 真实路由／模板引擎配合临时插件及控制器替身，含并发子进程 |
| [plugin_lifecycle.php](plugins/plugin_lifecycle.php) | 旧后台卸载失败、删除保护、非法排序、强制绑定、空标识打包及在线配置过滤 | `php tests/plugins/plugin_lifecycle.php` | 独立子进程包含真实旧后台模块；删除／写入对象仅在随机临时目录 |
| [plugin_modules.php](plugins/plugin_modules.php) | myBackup、badReq、frontend、manager 实际模块的鉴权、参数、配置来源与连接保留 | `php tests/plugins/plugin_modules.php` | 默认整组包含 MySQL：可用 MYSTEP_TEST_CONFIG 指定本机测试配置文件；创建随机 mystep_plugin_* 空库后删除，其他数据在临时插件目录 |
| [plugin_memcached.php](plugins/plugin_memcached.php) | Memcached 后端协议、精确值、清理及数据库缓存挂接 | `php -n tests/plugins/plugin_memcached.php` | 仅 Memcached 类替身；必须用 -n 隔离原生扩展，不连接真实服务 |
| [plugin_installer.php](plugins/plugin_installer.php) | manager独立解包器及真实辅助函数，18项：路径、损坏包、权限预检、严格umask、完整写入及源包保留 | `php tests/plugins/plugin_installer.php` | 以普通用户在临时目录测试；仅对隔离文件调整权限并恢复，不运行安装页面或初始化真实网站；不保证整站事务回滚 |
| [admin_regression.php](admin/admin_regression.php) | Admin 模块清单、鉴权、设置／语言、插件动作、全部缓存清理和示例资源 | `php tests/admin/admin_regression.php` | 复制应用到随机临时站点；配置、注册表、附件、缓存和会话均隔离 |
| [guide_regression.php](admin/guide_regression.php) | 12项：六部分顺序、旧标识兼容、全部文档与内链锚点、三种路由、安全Markdown及路径边界 | `php tests/admin/guide_regression.php` | 只在随机目录复制并渲染Markdown，不启动会话、不访问数据库或改正式资料 |
| [api_javascript.js](admin/api_javascript.js) | 9项：API行内展开、checkForm、AJAX、CSRF、版本同步、冲突与登录失效反馈 | `node tests/admin/api_javascript.js` | 真实脚本配合DOM和网络替身，不提交正式配置 |

### 供主测试调用的独立 PHP 夹具

| 文件 | 作用 | 调用及限制 |
| --- | --- | --- |
| [cache_sqlsrv_fixture.php](cache/cache_sqlsrv_fixture.php) | 缓存 MSSQL 驱动的 SQLSRV 参数／结果／错误释放协议替身 | 由 cache_regression、integration_regression 等调用；可用 `php tests/cache/cache_sqlsrv_fixture.php` 单独检查。在已加载原生 sqlsrv 扩展时跳过；不连接 SQL Server，不能替代实库测试 |

## 5. Python 文件逐项说明

| 文件 | 功能及运行方式 | 依赖／数据影响 |
| --- | --- | --- |
| [admin_http_fixture.py](admin/admin_http_fixture.py) | `python3 tests/admin/admin_http_fixture.py`；284次HTTP：真实 init/go、Admin 和旧后台、登录／退出、三种路由、文档分类／正文／非法参数、下载、插件、上传及缓存动作 | 随机临时站点、测试账户和 SQLite；包括测试 CAPTCHA 端点及写操作，但只出现在夹具站点，不向正式后台提交表单 |
| [execution_http_fixture.py](core/execution_http_fixture.py) | `python3 tests/core/execution_http_fixture.py`；输入预算、服务器内存策略、执行主干、API、CSRF、HEAD/304、BOM、gzip 和大文件 | 临时应用／配置／资源及回环 PHP 服务，不请求正式业务 |
| [extension_http_fixture.py](extensions/extension_http_fixture.py) | `python3 tests/extensions/extension_http_fixture.py`；API 内容类型、异常编码和 XML Excel 下载 | 回环 PHP 服务；临时输出，检查 UTF-8／GBK、文件名头部和字节长度 |
| [session_http_fixture.py](session/session_http_fixture.py) | `python3 tests/session/session_http_fixture.py`；Cookie 属性、会话保持／结束及后端失败 | 临时 sess_file／失败替身和回环 HTTP；仅模拟 HTTPS 标志以检查 Secure，不能据此证明 TLS 握手 |
| [utility_io_fixture.py](utilities/utility_io_fixture.py) | `python3 tests/utilities/utility_io_fixture.py`；SMTP 信封／Bcc／MIME、multipart 上传、大小／扩展／图片拒绝 | 自建回环 SMTP 与 PHP 服务，不投递真实邮箱；上传目标和邮件样本在临时目录 |
| [https_fixture.py](security/https_fixture.py) | `python3 tests/security/https_fixture.py`；生成临时 CA 和服务端证书，自建 HTTPS，再调用 security_regression.php --database | 需要 OpenSSL、PHP 和已配置 MySQL；CA 只在测试进程信任，不安装系统证书；会执行安全脚本中的数据库用例 |
| [http_smoke.py](site/http_smoke.py) | `python3 tests/site/http_smoke.py`；现有七个域名、路由、资源、私有路径、CORS、令牌和请求方法 | 使用实际站点配置／TLS，包含无文件上传及不存在附件的 POST 边界检查、测试 Cookie 和 files 下的临时符号链接；不执行真实插件启停、缓存清理或附件删除 |
| [javascript_browser_server.py](javascript/javascript_browser_server.py) | `python3 tests/javascript/javascript_browser_server.py`；提供真实浏览器检查页，服务持续运行直到 Ctrl+C | 绑定 127.0.0.1 的随机端口；--results-dir 指定结果目录，否则在系统临时目录；启动本身不执行浏览器用例 |

## 6. JavaScript 与 HTML 文件逐项说明

| 文件 | 类型及运行方式 | 覆盖范围 |
| --- | --- | --- |
| [javascript_regression.js](javascript/javascript_regression.js) | Node：`node tests/javascript/javascript_regression.js`；追加 --minified 验证压缩后执行 | 实际 global.js／checkForm.js、严格作用域、编码／哈希、配置等待、参数、事件及表单；默认不请求网络。压缩模式另调用 PHP |
| [security_transport.js](security/security_transport.js) | 由manager HTTP夹具调用 | 只允许环回HTTP，执行真实security.js预处理与XHR头合并，验证正确／错误／重复令牌的实际响应；不向正式站点发送测试令牌 |
| [security_browser.js](security/security_browser.js) | Node：`node tests/security/security_browser.js` | 实际 security.js 在 DOM／网络替身中的表单、jQuery与XHR叠加的请求头去重、XHR复用、fetch、旧链接及异步退出令牌行为；不需要真实浏览器 |
| [cms_link_browser.js](applications/cms_link_browser.js) | Node：`node tests/applications/cms_link_browser.js` | 调用 cms_link_regression.php --fixtures，执行真实 URL 辅助函数与 CMS 模板夹具，含首页 iframe 在异步配置前可用的 12 种路由／部署组合；不启动浏览器或网络 |
| [composer_javascript.js](dependencies/composer_javascript.js) | Node：`node tests/dependencies/composer_javascript.js` | 调用 composer_regression.php --minify，对照压缩前后语义及旧 pack 参数；需要 PHP 7.4 和 vendor |
| [plugin_javascript.js](plugins/plugin_javascript.js) | Node：`node tests/plugins/plugin_javascript.js` | 26项，执行myBackup、badReq跳页、manager和独立安装器真实脚本，检查表单、更新状态、权限反馈、错误处理、完成跳转及描述转义；不访问远端服务 |
| [manager_upgrade_http.py](plugins/manager_upgrade_http.py) | Python：`python3 tests/plugins/manager_upgrade_http.py` | 临时主服务器／客户端、PHP7.4、OpenSSL与受信本地HTTPS，39组／58次客户端HTTP请求；真实下载、ZIP上传、在线／离线更新及权限预检。需以普通用户运行；只对隔离文件临时调整权限并恢复。管理身份为替身，不连接正式数据库或更新源，不改正式代码、配置或清单；MYSTEP_PHP可指定PHP路径 |
| [manager_current_release_http.py](plugins/manager_current_release_http.py) | Python3.9+：`python3 tests/plugins/manager_current_release_http.py` | 本机当前版本与14个真实源码文件，复制运行环境后通过真实init／路由／管理员登录／CSRF／受信HTTPS升级；包含新旧后台更新网址保存、无缓存组件版本即时返回和失效缓存替换、真实安全脚本传输的令牌去重与相同服务器版本判断、通用插件布局、真实frontend保存、badReq操作返回、Referer匹配及Admin/myStep自定义模板，在线与断源后的离线安装均校验SHA-256并重新访问后台。无正式数据库／外网依赖，临时目录自动清理；MYSTEP_RELEASE_REPORT可指定JSON报告路径 |
| [manager_system_package_http.py](plugins/manager_system_package_http.py) | Python3.9+：`python3 tests/plugins/manager_system_package_http.py` | 复用当前源码夹具并增加系统包导出、缓存校验／损坏重建、打包开关／锁、安装权限提示及中止、独立安装、初始化、真实登录及安装后在线／离线升级。需普通用户运行权限用例；所有写入在临时目录，正式配置及数据库不参与 |
| [admin_javascript.js](admin/admin_javascript.js) | Node：`node tests/admin/admin_javascript.js` | 真实 Admin 脚本的表单确认、校验、行编辑、筛选、移动菜单及 ARIA；不请求业务服务 |
| [javascript_browser_fixture.js](javascript/javascript_browser_fixture.js) | 浏览器脚本，由配套 HTML 引入，不用 node 直接执行 | 真实 jQuery、HTML 原生约束、焦点／选区、密码显示切换、Ajax 和查询 GET 表单；将结果 POST 到回环 /report |
| [javascript_browser_fixture.html](javascript/javascript_browser_fixture.html) | 浏览器页面模板，由 javascript_browser_server.py 注入脚本并提供 | 页面包含结果区和测试表单；/ 为 global.js＋checkForm.js 整合模式，/?standalone 为独立 checkForm.js 模式。直接 file:// 打开不能获得配套根路径资源和报告服务 |

## 7. 常用运行顺序

### 只改公共 PHP 逻辑

先运行受影响主题，再检查相邻调用。例如修改模板：

```sh
php tests/templates/template_regression.php
php tests/core/execution_regression.php
```

修改数据库／缓存时先运行不带 --database 的逻辑检查，再按需选择真实 MySQL：

```sh
php tests/database/database_regression.php
php tests/database/interface_regression.php
php tests/cache/cache_regression.php
php tests/database/database_regression.php --database
php tests/database/interface_regression.php --database
php tests/cache/cache_regression.php --database
```

这些命令是选取示例，不代表每次文档或样式修改都必须运行整组测试。

### 修改前端或 Admin

```sh
node tests/javascript/javascript_regression.js
node tests/javascript/javascript_regression.js --minified
node tests/security/security_browser.js
node tests/admin/admin_javascript.js
php tests/admin/admin_regression.php
php tests/admin/guide_regression.php
python3 tests/admin/admin_http_fixture.py
```

Node 检查真实 DOM 不方便覆盖的浏览器行为时，再启动：

```sh
python3 tests/javascript/javascript_browser_server.py --results-dir /private/tmp/mystep-js-results
```

打开终端打印的 Integrated 和 Standalone 地址。页面结果区应有 passed、failed、rows；两种模式分别写出 browser-integrated.json 和 browser-standalone.json，结果目录另有 browser-url.txt。检查 failed 和失败项，不把“服务器已启动”当成用例已经通过。完成后 Ctrl+C 结束服务。

### 部署／路由变化

```sh
php tests/core/router_regression.php
php tests/core/flow_bootstrap.php
FLOW_BASE=/sub/ php tests/core/flow_bootstrap.php
php tests/core/path_regression.php
python3 tests/core/execution_http_fixture.py
```

实际站点 TLS、域名和 Web 服务规则另用 `python3 tests/site/http_smoke.py`；它有本机部署前提，不能与上述临时站点检查等同。

## 8. 环境变量与特殊参数

| 参数 | 使用位置及含义 |
| --- | --- |
| MYSTEP_PHP | 大多数 Python 夹具及会调用 PHP 的 Node 脚本选择解释器。部分脚本默认本机 ServBay PHP 路径，其他机器需显式设置 |
| MYSTEP_OPENSSL | https_fixture.py 的 OpenSSL 可执行文件 |
| MYSTEP_ROOT | core_regression、composer_regression 的网站根覆盖 |
| MYSTEP_SOURCE／MYSTEP_STAGE | database、cache、session、interface、mechanism、extension、charset、integration 等支持的来源／待检查文件根；各脚本读取范围不同，不是所有测试统一支持的选项 |
| MYSTEP_ADMIN_SOURCE／MYSTEP_ADMIN_STAGE | Admin PHP／HTTP 夹具的来源／待检查版本；admin_javascript 读取 MYSTEP_ADMIN_STAGE |
| MYSTEP_EXECUTION_SOURCE／MYSTEP_EXECUTION_LIB | execution HTTP 的来源根，以及 execution_regression 的覆盖库目录；不是相同开关 |
| MYSTEP_JS_SOURCE／MYSTEP_FORM_SOURCE | javascript_regression 指定 global.js／checkForm.js 源文件 |
| MYSTEP_INTEGRATION_FILTER | integration_regression 按测试名称筛选，用法以脚本中的匹配实现为准 |
| FLOW_BASE | flow_bootstrap 的 Web 前缀，如 /sub/ |
| CMS_LINK_BASE／CMS_LINK_BOUND／CMS_LINK_MODE | CMS 链接用例的部署前缀、绑定方式和路由模式 |
| MYSTEP_CA_FILE／MYSTEP_HTTP_RESULTS | http_smoke 的受信 CA 文件和 JSON 报告位置 |
| MYSTEP_ADMIN_HTTP_RESULTS／MYSTEP_EXECUTION_HTTP_RESULTS／MYSTEP_EXTENSION_HTTP_RESULTS | 相应 Python HTTP 夹具的 JSON 报告文件；输出目录由调用方准备 |
| --database | 只在明确支持的 PHP 主入口启用 MySQL 分支；部分其他测试默认已经连接数据库 |
| --minified | javascript_regression 的公开压缩执行比较模式 |
| --results-dir | javascript_browser_server 的报告目录 |

`--worker`、`--load`、`--cache-worker`、`--fallback`、`--large`、`--guard`、`--buffer`、`--power`、`--opcache`、`--minify` 等是特定入口的子进程／桥接模式，不是可以套用所有文件的选项；缺少父脚本准备的数据会失败。CACHE_REVIEW_JOB、SESSION_REVIEW_JOB、TLS_FIXTURE_* 及 HTTP 子服务的 MYSTEP_SESSION_SOURCE／LIB、MYSTEP_EXTENSION_LIB 等由相应父脚本设置，常规运行不手工构造。

不要直接按 *.php／*.js／*.py 全部循环执行：其中既有特殊解释器要求，也有内部夹具、浏览器页面和持续服务入口。本目录目前没有一个覆盖全部组合、自动完成真实浏览器交互的统一运行器。

## 9. 结果、清理与新增文件规则

- 大多数命令行主入口打印 PASS／FAIL 与汇总，并以非零退出码表示失败；协议替身可能 SKIP，SKIP 不能当作真实服务通过。
- 输出中有场景名／子场景时按当前结果解读，父子入口及同一组不同模式不要重复计入全项目总数。
- 正常执行后检查进程已结束、结果文件可读；中断数据库测试后按本次命名检查残留。测试产物保存在网站外的私有结果目录，不提交密码、会话或测试数据库内容。
- 测试目录分类调整后的验证范围和结果见本文末尾；早期 Admin 42／200／21 等记录只对应当时执行，不作为今后固定通过数。
- 新测试需提供用途、公开命令、依赖、临时数据范围、清理和失败方式，并在本表登记；必要的内部模式由主入口封装，不能让测试默认清空业务缓存或执行安装迁移。

数据库及缓存真实服务／替身边界见 [数据库](../docs/guide/03-modules.md)、[缓存](../docs/guide/03-modules.md)、[Session](../docs/guide/03-modules.md)。其他实现说明见 [框架文档目录](../docs/README.md)。`maintenance/upgrade-auth.php` 是独立迁移工具，不是普通测试入口；其执行会改变真实持久数据，不能加入自动回归循环。

## 10. 目录迁移验证（2026-10-18）

46 个测试／夹具已移入 15 个功能目录，逐文件更新网站根定位、跨目录子进程调用及浏览器资源路径；原根目录脚本入口已移除。运行命令使用上文新路径，框架与应用文档中的引用同步更新。

已完成：30 个 PHP、8 个 Python、7 个 JavaScript 的语法检查；43 条自动化回归命令通过，其中包含 JavaScript 压缩后执行比较、临时 HTTP/HTTPS/SMTP 服务和各默认数据库场景。浏览器测试服务另检查整合／独立页面、五项资源及两种报告写入，共 9 个 HTTP 检查通过。

上述结果未合并父子用例数字作为新的全项目断言总数。实站冒烟、真实浏览器的 DOM 交互断言及各可选 --database 组合仍按需要单独运行；本次浏览器服务检查只确认迁移后的资源和报告链路。测试源文件及原说明的迁移前快照保存在网站目录之外。

API 行内管理交互：`node tests/admin/api_javascript.js`，覆盖设置展开、checkForm、AJAX保存、版本同步和错误反馈；使用DOM及网络替身，不提交正式配置。

## 指南局部阅读与搜索

- `admin/guide_regression.php`：受信目录、正文渲染、跨文档链接、只读局部页面、中文／多关键词搜索和备份排除。
- `admin/admin_http_fixture.py`：在临时站点验证新旧后台及三种路由模式下的JSON正文、搜索、登录边界和错误返回。可设置`MYSTEP_ADMIN_BROWSER_READY`为临时ready文件路径，最多保留隔离站点300秒；删除ready文件后继续测试。
- `admin/guide_dom_fixture.php`：用实际myTemplate生成DOM测试输入，编译文件只写到测试进程的临时目录。
- `admin/guide_javascript.js`：通过开发环境的linkedom运行真实guide.js，验证目录DOM及状态保留、请求乱序、前进后退、搜索、标记和失败反馈；可加`--minified`验证实际压缩产物。只用于测试，不给网站增加运行依赖。DOM逻辑测试不等于真实浏览器的视觉验收。

运行JavaScript测试前，让Node可解析开发环境已有的linkedom，然后执行`node tests/admin/guide_javascript.js`。PHP解释器可通过`MYSTEP_PHP`指定为PHP7.4。
