# CMS：内容管理系统

## 定位和适用场景

文章、栏目、链接、会员、多站点、前后台模板与业务管理。其表结构和专属配置不是所有框架应用的必选项。

## 当前目录及主要文件

源码位于`app/CMS/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
app/CMS/
├── CMS.class.php
├── asset/
├── atom.php
├── config.php
├── config/
├── config_admin_cms.php
├── config_default.php
├── global.php
├── index.php
├── info.php
├── install/
├── language/
├── lib.php
├── module/
├── plugin.php
├── plugin_addon.php
├── route.php
├── rss.php
├── setting_expand.php
├── sitemap.php
├── template/
├── website/
```

## 入口与执行过程

CMS.class.php与lib.php建立本应用能力；route.php选择前台／admin_cms入口，module中按业务功能选择对应template。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 按已登记路由访问应用入口，并选择对应功能。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 用户名入口和个人资料

前台用户名链接将`getUserInfo()`返回的`profile_path`与当前页面的`url_prefix_app`组合，避免专属API域名或API路由上下文改变CMS链接前缀。普通会员进入`user/profile`，未登录访客在该入口看到登录页；CMS管理员进入配置中的`web.path_admin`管理入口。管理员直接访问`user/profile`地址时也转入CMS后台，POST使用303转为GET，不通过前台会员表修改管理员账号。`getProfilePath()`只选择应用内路径，页面跳转再通过`getURL()`适应绑定域名、子目录及三种路由模式。

## 边界与维护

模块与模板按功能对应。应用实例和数据作用域独立；此项目的专属目录、表结构或页面清单不自动成为其他应用的通用要求。

[框架指南总览](../../README.md)

## 标准会员与第三方登录

CMS实现interface_user，由CMS::userProvider提供普通会员服务；social插件启用并绑定CMS后，在登录、注册和资料页显示平台入口。未安装插件不会自动改表。详细字段、本站邮箱验证和已有账号关联见[普通会员服务](../../backend/security/members.md)。

## “迈思框架使用手册”子站的实际结构

这个手册使用CMS的多站点体系：主站与手册共用CMS核心、栏目和后台，手册有自己的文章表与guide风格。它不是另一个PHP框架入口，也不需要把内容代码放入根lib。

| 位置 | 职责 |
| --- | --- |
| `cms_website` | 站点标识、名称与域名，手册当前标识为guide |
| `cms_news_cat` | 各站栏目树，通过web_id区分；手册栏目与文章编号继续保留 |
| `guide_news_show` | 手册文章元信息、栏目、阅读等级、生效期和文章路径索引 |
| `guide_news_detail` | 文章分页正文，与news_id/page对应 |
| `guide_news_tag` | 标签统计数据 |
| `website/config_guide.php` | 当前手册的标题、guide风格与guide_文章表前缀 |
| `template/guide/module/index.php`、`index.tpl` | 手册首页的目录数据准备及HTML |
| `template/guide/module/article.php`、`article.tpl` | 文章标题及栏目顺序的前后篇；正文仍由CMS article模块读取 |
| `asset/guide/function.js`、`style.css` | 浏览器目录、高亮、键盘交互与响应式样式 |

前台请求先由框架选定CMS，CMS::preload按Host选择站点并合并website配置。普通页面仍由CMS/global.php准备栏目和公共数据，CMS/index.php选择module/article.php等业务模块，再加载当前风格对应的template/guide/module脚本，最后组合guide主模板。后台编辑仍使用同一套CMS文章、栏目与子站管理功能，不需要维护另一套文章控制器。

公共服务表前缀和子站文章表前缀要分开：cms_用于共享栏目、用户、链接等；guide_用于手册文章。后台按所选站点读取它的website配置；数据库里有子站记录但缺少该文件时，会出现表前缀缺失等错误。删除废弃子站应先备份，检查专用表和共享表关联，同时失效站点与栏目缓存。

手册目录按栏目树顺序组织，文章数字ID不再决定前后篇顺序。`app\CMS\guideContents()`只选择当前站点、当前阅读等级可见且已生效的文章，首页与文章导航复用同一结果；不会把其他站点或未发布内容放进手册目录。长文目录直接读取正文标题，代码示例保持文本，高亮使用已有本地组件；带base标签的页面，章节链接保留当前路径与查询串再追加锚点。

CMS手册通过数据库栏目与文章组织；后台指南通过docs/catalog.json加载分类Markdown。根据所用入口编辑对应数据或文档文件。

## 唯一中文文章地址

从CMS 1.2.0起，规范地址按站点独立分配，标题未重名时直接使用标题，例如`/框架入门`。同站点第二篇同名文章使用`/框架入门-60`（60为该文章编号）；其他站点可以继续使用自己的`/框架入门`。编号只在重名时出现。如果该后缀也已被占用，则继续追加序号。空白及路径、查询分隔符统一为连字符；纯数字、应用入口、已有模块名等保留地址会添加`文章-`前缀。

共享表`cms_news_alias`按web_id与alias_hash唯一保存归属，`current_news_id`唯一标识该文章的当前地址。文章标题改为“框架基础”后，旧地址仍归原文章，访问时301跳转到新地址；删除文章后也不会把旧地址分配给另一篇文章。原`article/编号`、`article/栏目/编号`及已登记的旧标题入口继续解析，完成有效期、阅读权限检查后再跳转。

模板列表、手册前后篇、订阅和独立栏目统一调用`app\CMS\getLink()`。分页使用相同中文地址追加`?page=2`，head输出对应分页的绝对canonical地址。输出到HTML属性时仍须转义；路径中的中文以UTF-8百分号编码传输，浏览器通常显示中文。

```php
// 前提：CMS上下文已初始化，$db、$S、$web_info及栏目数据可用。
$url = \app\CMS\getLink(['news_id'=>60, 'web_id'=>2, 'cat_id'=>70]);
$page2 = \app\CMS\queryURL($url, ['page'=>2]);
// 发布成功、取得文章编号后分配；浏览请求不会建表或自动写入别名。
$alias = \app\CMS\saveArticleAlias(60, 2, '框架入门');
```

使用CMS::upgradeArticleAliases()为已录入文章建立规范地址索引。执行前备份数据库，确认各子站配置及文章表完整；执行后刷新目录和页面缓存。
