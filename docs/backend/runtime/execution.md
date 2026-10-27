# 执行流程

## 本篇目标与调用场景

按当前源码的条件与顺序说明本阶段；特别标明哪些分支会提前结束，哪些变量和钩子尚未可用。

## 先判断请求属于哪条链

```text
公开文件：Web服务器直接返回，或go中的文件出口 → 结束
普通页面：init → go → 选择应用 → setPara/start → global → index → 模块 → show → end
显式页面：init → go → 路由命中 → setPara/start → 中间件 → 处理器 → 普通end或处理器自己的出口
页面模块：路由命中 → setPara/start → getModule → 主模板 → global → 选中模块 → show → end
HTTP API：路由命中 → API前置检查 → setPara/start → api → 登记的目标 → 编码输出 → exit
API预检：路由命中 → API前置检查 → 合法OPTIONS返回204 → 结束
```

以上名称表示调用阶段。`module/index.php`是否运行由应用入口决定；`start()`已在进入默认应用`index.php`之前执行。路由匹配不会自动让显式处理器先经过应用`index.php`。

## 公共启动时序

| 顺序 | 调用或文件 | 执行内容 | 开发时可依赖的状态 |
| --- | --- | --- | --- |
| 1 | 根`index.php` | 检查PHP版本，定义`ROOT/LIB/APP/CACHE/CONFIG/PLUGIN/STATICS/VENDOR/FILE`，加载myStep并调用init | 目录常量；尚无当前应用 |
| 2 | `myStep::init()` | 建立缓冲和类加载，执行已解析输入预算检查；需要时清理重建后的编译缓存 | 框架类可加载；没有常规会话和身份 |
| 3 | init后半段 | 定义`ROOT_WEB`，设置初始异常处理；缺少全局配置则进入初始化安装分支 | Web根路径和基础日志 |
| 4 | `myStep::go()` | 读取全局配置，准备存储及API全局设置，处理feed规范地址跳转 | `$ms_setting`是全局配置；尚未合并当前应用 |
| 5 | `config/priority/` | 先执行有效应用前置脚本，再按活动插件order降序执行插件前置脚本 | 可读取全局设置；不假定`PATH/$mystep`或登录身份存在 |
| 6 | go | 设置运行内存策略，解析Host、域名虚拟前缀及API专属域名，建立路由对象 | `$router`已有解析路径；应用信息仍可能被规则改写 |
| 7 | go文件分流 | 拒绝私有路径，校验并返回公共文件或缺失资源占位内容 | 命中文件分支时不会启动页面服务 |
| 8 | go动态分支 | 补齐Cookie作用域及站点URL，登记变量别名，刷新并加载路由表 | 初步`$info_app`可用；并非最终应用上下文 |
| 9 | 路由或默认承接 | 选定应用、合并应用配置并执行域名检查；显式规则还可合并入口覆盖配置 | 当前应用确定；尚未调用处理器 |
| 10 | `myStep::setPara()` | 检查关闭状态，定义`PATH`，创建核心对象、加载lib并执行preload，准备资源和模板参数 | 创建过程的先后条件见下表 |
| 11 | `$mystep->start()` | 准备服务、语言、Cookie、Session、CSRF和身份，再按条件初始化插件及start钩子 | 业务入口可使用准备完成的资源，并自行检查权限 |
| 12 | 页面／处理器 | 运行当前分支的入口或回调 | 具体页面、API和文件出口各自决定响应 |

`route.php`在路由汇总时提供声明，框架可以复用已编译的`config/route.php`。不要将每次请求必做的工作放在路由声明文件顶层。

## 应用对象建立的准确顺序

```text
setPara
  → 已有$mystep时直接返回
  → 按最终应用判断公共端点的会话策略
  → checkClose
  → PATH = 当前应用目录
  → 加载同名核心文件并选择myStep子类
  → new 当前核心：构造函数绑定$ms_setting、设置时区及超时
  → 应用lib.php（存在时）
  → preload（可调用时）
  → 收集公共／应用／风格资源
  → 建立$tpl_setting、$tpl_cache
  → start
```

`preload()`适合登记回调、准备控制器路由或按应用需求补充配置。此时默认的`setInstance()`、语言包、Session、登录恢复都尚未执行；应用自行提前建立的资源是该应用的明确行为，不是每个应用都有的保证。`preload()`内也不能假定框架尚未建立的`$tpl_setting`已经可用。

`lib.php`在核心实例创建之后加载。如果同名核心文件顶层依赖其他类或接口，核心文件须明确加载其依赖，不能等待之后的`lib.php`再补齐。核心类文件可以提前`require_once`应用lib，但声明文件不在顶层产生页面输出。

## start内部的准确顺序

1. 记录耗时与内存起点，设置类别名及完整错误处理。
2. 读取当前应用的`plugin.php`绑定，与活动插件注册表取交集，按order降序去重登记。此处只是登记，尚未执行插件`index.php`。
3. 未声明登录回调时设置框架默认login/logout/chg_psw。
4. 调用`setInstance()`，复用或创建`$cache`和`$db`。`db.auto`开启时才自动连接业务数据库；对象存在不代表连接成功或已选库。
5. 补充Cookie前缀、文件会话目录、请求时间及Host信息。
6. 先加载框架语言，再加载当前应用语言。
7. `myReq::init()`配置Cookie与Session，设置所选会话驱动并启动会话；不需会话的公共框架端点按其策略处理。
8. `mySecurity::guard()`校验操作方法和会话CSRF；随后`login()`恢复框架认证状态。
9. 合并功能忽略列表，清空页面start/end内容槽，准备当前URL前缀。
10. 父类start设置字符集、HTML基础响应头和输出缓冲。
11. 当前入口允许插件时，按登记顺序包含对应插件`index.php`；插件可使用宿主`$this`登记钩子、标签及资源。
12. 按登记顺序执行`start`钩子，返回当前请求分支。

框架管理员身份恢复不替代应用自身的用户认证。即使start已返回，处理器仍需检查当前用户是否有权执行该项业务。插件的`check/install/uninstall`不是普通页面的每请求阶段。

## 默认页面如何装配

请求`/Hello/`，没有显式规则接管且应用Hello存在时：

```text
go选择Hello → 合并Hello/config.php → 域名检查
→ setPara/start完成
→ 设置$m为剩余路径首段
→ require_once Hello/global.php（存在时）
→ require Hello/index.php
→ 由该index选择并包含自己的module
→ 模块为子模板赋值，render得到字符串
→ 将子页面放入主模板main
→ 返回go，存在$tpl时调用show
→ go调用end并结束
```

框架只负责读取应用`index.php`，并不自动给任意应用添加同名模块分派。`index.php`要明确选择模块与模板。默认入口已经交给外层show时，不再重复手工show，否则会输出两份页面。

## 显式规则和中间件

匹配先扫描原请求路径，未命中时再扫描带域名虚拟前缀的路径；每轮按规则登记顺序取首个命中。两轮都未命中才进入默认应用。

命中后更新`$info_app`、合并配置、检查域名，随后执行setPara/start，再调用中间件及最终处理器。因此“规则已匹配”和“处理器已执行”是两个时刻。

中间件返回严格`false`时停止后续处理器，路由仍视为已处理，不再回退到应用首页。中间件自行设置拒绝响应；若没有exit，外层仍可能继续普通end收尾。普通命名处理器不自动执行应用`global.php/index.php`，需要的数据在处理器覆盖的阶段准备。

`getModule()`是独立页面分发器：先建立主模板，再包含global，按当前风格和模块路径候选顺序选文件；模块设置了`$content`才赋入main并show，之后方法内end。它与业务应用自行写在index中的module分派分开使用。

## API与公共端点

API匹配后，启用状态、来源、正文预算与OPTIONS预检先于完整应用启动处理。合法预检可直接返回204；实际业务调用继续setPara/start，再由`myStep::api()`解析已登记范围、处理器、位置参数及格式。

实际API只调用登记目标，编码响应后exit，不经过普通HTML模板show和普通end。目标插件的独立API类按需要加载，不能把目标插件`index.php`当作必经初始化；宿主start是否初始化其绑定插件，另由宿主绑定和功能忽略规则决定。

API处理器应返回业务数据，不直接echo调试文字。该API出口会取出已产生的缓冲内容，存在内容时可能用它覆盖编码结果；声明文件、preload及钩子中的输出也会影响JSON。

## 页面生成与收尾

```text
render(子模板) → 返回字符串，不自动输出完整页面
show(主模板)
  → 框架页面变量
  → page_start中的root_web与CSS/JS资源
  → CSS/JS资源及page_end的DOM就绪队列入口
  → 模板渲染
  → page钩子：正文依次传递，回调返回修改后的正文
  → 当前会话的CSRF装饰 → 输出 → shown=true
end
  → 逆序end钩子 → 可选shutdown
  → 编辑器附加资源准备
  → 关闭数据库并统计耗时/内存
  → 清理db/cache全局引用
  → shown为true时按响应条件处理压缩
  → exit
```

`setFunction('start', ...)`登记的是PHP钩子，`setAddedContent('start', ...)`登记的是HTML内容槽。`page_start/page_end`的位置由模板决定；浏览器脚本是在浏览器收到HTML后执行，不与PHP钩子交错运行。

页面主动调用`checkCache($tpl)`且命中时，会加入当前会话装饰，输出后end；后续页面查询及重新渲染不再执行。模板编译缓存命中只复用编译结果，仍会继续当前渲染，不等于页面缓存命中。

## 代码应该放在哪个阶段

| 工作 | 推荐位置 | 不宜依赖的条件 |
| --- | --- | --- |
| 共享类、函数声明 | 应用lib.php／插件class.php | 不在文件顶层执行安装和业务写入 |
| 路由及API登记 | 应用或插件route.php | 不假定它每次动态请求都会重新执行 |
| 全局请求前置过滤 | 已安装的priority.php | 没有普通应用会话，不能检查尚未恢复的登录身份 |
| 为start/page/end登记回调 | preload或插件index.php | 业务模块才登记start钩子时，本次start阶段已经过去 |
| 登录后通用准备 | start钩子 | 仍需区分框架管理员和普通会员 |
| 页面查询、参数处理、模板赋值 | 对应模块／显式处理器 | 默认global和index不覆盖所有API及文件出口 |
| 多模块公用页面数据 | global.php，或由相关处理器显式调用的公共函数 | 不能用global保护所有路由 |
| 输出正文处理 | page钩子 | 返回正文；API和文件不保证执行它 |
| 请求局部资源释放 | 使用资源的try/finally | end不是所有分支都必经的finally |

## 变量何时可以使用

| 标识 | 开始可用的阶段 | 使用说明 |
| --- | --- | --- |
| ROOT等目录常量 | 根index | 磁盘位置；不要当浏览器URL |
| ROOT_WEB | init | 部署Web根前缀 |
| $ms_setting | go读取全局配置后 | 最终值还会随应用、入口及应用自定义合并而变化 |
| $router | go建立路由对象后 | 规则匹配前的信息属于初步解析 |
| $info_app | 动态分支及路由选择后 | 业务入口使用最终app/path/para/route |
| PATH、$mystep | setPara选定应用及建立实例后 | preload中已有这两项 |
| $tpl_setting、$tpl_cache | preload返回后 | 模板参数不等于模板对象已经创建 |
| $db、$cache | 默认setInstance返回后 | 可被应用提前建立；数据库连接由db.auto及显式调用决定 |
| 语言、Session、恢复后的认证状态 | start中对应步骤完成后 | 正常start钩子已处于该阶段之后 |
| $tpl、$tpl_sub、$content | 页面入口／模块显式创建后 | 是页面约定，不是所有请求都有的全局变量 |

## 按请求观察实际顺序

完整可操作的记录示例见[应用执行顺序](../../development/app/app-07.md)。先准备Hello应用，在preload登记三个钩子，再分别访问默认页面、命名路由和API；对照是否执行global、index、page及end。不要通过临时修改框架核心或在JSON响应里echo来跟踪调用。

## 提前结束与分支

| 分支 | 会话与身份 | 应用入口 | page/end钩子 |
| --- | --- | --- | --- |
| Web服务器直接返回文件 | 不进入PHP | 不执行 | 不执行 |
| go输出公共文件或拒绝私有路径 | 尚未准备 | 不执行 | 不执行 |
| 前置脚本主动结束 | 不保证存在 | 不执行 | 不执行 |
| 合法API预检OPTIONS | 尚未进入常规应用服务 | 不执行 | 不执行 |
| 普通API调用 | 按选中上下文及no_log策略 | 不自动执行global/index | API分支直接结束，不保证end |
| 普通页面 | start已完成 | 由选定应用执行 | show执行page，end执行收尾 |
| 页面缓存命中 | 按调用checkCache的位置 | 命中后的业务代码不执行 | 添加当前CSRF装饰后end；不重新运行page |
| 显式处理器重定向或exit | 取决于调用阶段 | 不自动补跑 | 不自动补跑 |

PHP的结束函数、异常处理及框架end钩子各有触发条件。需要释放的资源使用明确的try/finally或相应生命周期接口，不把end当作所有出口都会执行的finally。

## 各阶段详细用法

- [启动初始化](bootstrap.md)：类加载、输入预算、基础异常处理和安装分支。
- [请求调度](dispatch.md)：全局配置、前置脚本、域名及资源分流。
- [应用启动](application-start.md)：同名核心、配置覆盖、服务、会话和身份。
- [路由机制](../routing/routing.md)：规则编译、分组、命名处理器和参数。
- [响应输出](response-lifecycle.md)：render、show、缓冲、页面缓存及压缩。
- [钩子实例](hooks-example.md)：可运行的登记、调用及正文传递。

[命名规范](../../basics/09-naming.md) · [文档总览](../../README.md)
