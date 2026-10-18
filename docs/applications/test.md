# test 演示应用的文件汇总与执行说明

整理日期：2026-10-18。`app/test` 是运行中的演示应用，展示核心继承、加载顺序、模块／模板和链式路由；根目录 `tests` 才是自动化回归，见 [测试运行说明](../../tests/README.md)。

## 顶层结构与脚本

| 文件／目录 | 当前作用 |
| --- | --- |
| [info.php](../../app/test/info.php) | 返回 name、app=test、版本和说明，供应用发现及展示 |
| [test.class.php](../../app/test/test.class.php) | class test extends myStep；维护 the_order 演示计数，提供 login、logout、preload、shutdown。类文件、preload、shutdown 输出执行顺序，preload 改标题，logout 结束会话 |
| [config.php](../../app/test/config.php) | 覆盖页面标题、前端 js.info 参数和 template.path=app/test/template；其他未声明项目仍继承全局配置 |
| [lib.php](../../app/test/lib.php) | namespace app\test 的函数和常量；加载时输出顺序，提供 test、api、logCheck、route、f1、f2、f3 |
| [global.php](../../app/test/global.php) | 输出公共准备阶段的顺序并调用 app\test\logCheck；当前 logCheck 固定返回 true，只演示检查入口，不是实际登录权限控制 |
| [index.php](../../app/test/index.php) | 默认入口建立主模板；模块及同名子模板都存在才采用，其他情况回退 index；模块已有 content 时保留，否则渲染子模板 |
| [route.php](../../app/test/route.php) | 声明 Camel 自定义格式、t1／t2／t3 三种路由及 api 分组方法 |
| [plugin.php](../../app/test/plugin.php) | 当前数组只有空项，没有因此启用插件；新代码无绑定时使用空数组即可 |
| config/ | 当前配置的控件类型及字段说明；不是另一份运行值 |
| module/ | 四个功能脚本：index、list、detail、t2 |
| template/ | main 主布局及 index／list／detail 子模板 |
| asset/ | 当前应用基础 style.css 与 function.js，由框架按资源规则收集 |

本目录没有独立 language/、安装器或数据库建表流程；`.DS_Store` 是系统元数据。应用内 README 只提供指向本文的入口。

## lib.php 函数目录

| 声明 | 功能及调用关系 |
| --- | --- |
| const test_var | 应用命名空间的示例常量 |
| test() | 输出简单演示文字 |
| api() | 返回含数字键、关联键和子数组的数据；只清理当前可用输出缓冲层，由框架 API 入口格式化 |
| logCheck() | 返回 true 的演示检查函数，供 global.php 调用 |
| route() | t1 路由的直接处理器，输出提示文字，不经过默认 index 组装 |
| f1($p)、f2($p) | 链式路由的前置处理器，打印步骤并返回字符串；返回 false 时框架会停止后续链路 |
| f3($p) | 最后处理器，显示实际收到的路径及前置返回值；通过 func_get_args 演示参数约定 |

应用函数的命名空间与框架全局类分别处理；演示输出用于观察流程，不是新业务应用应复制的初始化副作用。

## 模块和模板对应

| 模块 | 对应模板 | 展示方式 |
| --- | --- | --- |
| [module/index.php](../../app/test/module/index.php) | [template/index.tpl](../../app/test/template/index.tpl) | 模块设置 content 说明默认回退；默认 index 保留该内容，不再自动覆盖为子模板 HTML。独立首页模板另保留链接和卡片示例 |
| [module/list.php](../../app/test/module/list.php) | [template/list.tpl](../../app/test/template/list.tpl) | 生成五项 list 循环，详情链接通过 myStep::setURL 生成；入口渲染子模板 |
| [module/detail.php](../../app/test/module/detail.php) | [template/detail.tpl](../../app/test/template/detail.tpl) | 赋入 record_title／record_content；当前内容为固定标题和图片片段，没有根据末段编号查询真实文章 |
| [module/t2.php](../../app/test/module/t2.php) | [template/main.tpl](../../app/test/template/main.tpl) | getModule 路由分支输出步骤并设置 content，演示交由外层 main 布局显示，无独立 t2.tpl |

[template/main.tpl](../../app/test/template/main.tpl) 保留 main、page_start、page_end，承接功能内容及框架资源。模板中的相对主页／列表链接、API 和 t1/t2/t3 示例由各自 HTML 声明，不与 PHP 脚本路径简单等同。

## 配置说明和资源

| 文件 | 作用 |
| --- | --- |
| [config/construction.php](../../app/test/config/construction.php) | 字段类型表，声明 web.title、js.info 等文本及 db.auto 单选；结构中 db.auto 不等于当前 config.php 已覆盖它 |
| [config/default.php](../../app/test/config/default.php) | 默认字段说明及控件定义，当前提供 web.title 和 js.info；这里不是 config_default.php 安装初始值 |
| [asset/style.css](../../app/test/asset/style.css) | 浅绿色背景、页头、卡片及列间距 |
| [asset/function.js](../../app/test/asset/function.js) | 当前仅保留被注释的 alert 演示，无生效的业务交互 |

## 入口和调用链

以下是 rewrite 模式的逻辑地址，实际使用框架 URL 机制处理子目录、绑定和其他路由模式。

| 逻辑入口 | 当前用途 |
| --- | --- |
| /test/ | 默认应用入口，观察类加载、公共准备、index、模块及结束阶段输出 |
| /test/list | 列表模块与同名模板 |
| /test/detail/1 | 详情演示；末段 1 当前不驱动数据库查询 |
| /t1/ | 自定义直接回调 app\test\route |
| /t2/ | 框架 myStep::getModule 分发，选择 module/t2.php 并交回主模板 |
| /t3/UpperCamelCase/anyword | Camel 格式捕获与 f1 → f2 → f3 链式执行，最后处理器收到框架组织的参数和前置结果 |
| /api/test/api/json | 返回 lib.php 的固定数据；输出格式可按框架 API 约定切换 |

route.php 是路由声明源，可能只在重建注册表时执行；访问 t1、t2、t3 和 API 不保证走默认 index。比较输出应以 [框架执行流程](../framework/execution.md) 的实际分支为准，不假定所有脚本每次都执行。

## 现有演示边界

- 该应用使用站点自己的全局配置、Session、缓存和框架资源；浏览应用不等于在临时站点运行自动化测试，未覆盖的 db.auto 等项目仍继承全局值。
- 类文件和公共函数文件会直接输出顺序，logCheck 无权限限制；新业务应用使用 [app 编写规范](../../app/README.md) 组织初始化和授权。
- 目前 detail 内容以及首页模板仍引用 via.placeholder.com 外部图片；这与 myStep 后台已本地化的六张示例图不是同一批资源。本次仅说明，不改演示代码或素材。
- 未知模块回退和模块已有 content 的处理由 app_regression.php 覆盖；路由、模板及 API 的通用机制由对应 tests 专项验证，不用页面上的“显示正常”替代这些断言。
