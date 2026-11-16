# 迈思CMS（CMS） <small>v1.3.0</small>

## 功能概述

提供栏目、文章、标签、链接、会员、管理员权限、多站点和前后台模板管理。内容站可使用列表、图文、图片或自定义栏目展示；文章使用中文标题地址，同站点重名时自动加后缀。普通会员可维护资料，启用并绑定social插件后可使用所配置的第三方登录。

本应用使用独立业务表和自己的CMS管理员。框架管理员在Admin或myStep后台管理框架参数；CMS内容、子站和会员在CMS后台管理。

## 安装与访问

使用框架当前运行环境：PHP >=8.5.12 且 <9。本文中的目录均从网站根计算，所需数据库、图片或存储扩展按具体功能准备。

1. 在已经安装的MyStepFW中放入完整`app/CMS/`，准备数据库及相应访问账号。
2. 新CMS未建立`app/CMS/config.php`时，访问CMS入口会进入安装流程。按页面设置数据库及管理员并完成初始化；已经有业务数据的站点直接使用现有配置，不重新安装。
3. 未绑定独立域名时使用框架生成的CMS应用链接；绑定CMS域名后，可以通过该域名访问前台。
4. 后台由`web.path_admin`决定，默认逻辑路径为`admin_cms/`。用安装时设置的CMS管理员登录；未登录访问管理功能会进入登录流程。
5. 在框架“参数设置”中选择CMS管理应用级配置；在CMS后台“子网站设置”中管理各子站参数和域名。

以下示例以已经进入CMS上下文为前提，地址自动适应域名绑定和框架路由模式：

```php
$home = \app\CMS\getURL('');
$profile = \app\CMS\getURL('user/profile');
$articles = \app\CMS\getURL($ms_setting->web->path_admin.'/article/content/');
```

## 使用方法

### 站点与栏目

先在CMS后台选择要维护的网站，再编辑其“内容分类”。填写栏目名称、路径索引、上级栏目、阅读等级、显示位置及展示模板；索引用于栏目地址，模板控制标题列表、图片、图文或自定义显示。栏目可独立设定阅读权限。

需要一个栏目直接对应一篇说明文章时，在文章录入页选择“独立栏目文章”，按表单要求建立对应栏目与文章；适用于关于我们、联系方式等单页。普通新闻列表使用已有栏目，不为列表内每篇文章都建立一个独立栏目。

### 文章录入

选择目标站点和栏目，填写标题、正文、摘要、标签及需要的有效期、阅读等级、图片和推送项目，再保存。上传和远程图片处理按当前设置及本次录入选项执行。

正文使用富文本编辑器。段落格式提供标题一至标题五，前台样式负责h1至h6字号。插入代码时使用代码块并选择语言；编辑器中突出代码区域，前台负责最终高亮。规范代码块示例：

```html
<pre class="highlight" data-lng="php"><code class="language-php">echo 'Hello';
</code></pre>
```

代码作为文本保留换行；需要展示HTML源码时应编码尖括号，例如`&lt;div&gt;`，不能把示例标签当作实际页面元素插入。

### 中文文章地址

同站点标题“框架入门”的首篇文章可使用`/框架入门`；重名文章追加其编号等后缀，例如`/框架入门-60`。改标题后，已经分配的旧地址仍归原文章并转向当前地址。不同子站分别分配，不依靠手工检查标题唯一性。

应用模板、列表和导航使用CMS链接函数，不手工拼接article/编号：

```php
// 需要当前CMS数据库、站点及栏目上下文；示例编号替换为实际文章。
$url = \app\CMS\getLink(['news_id'=>60, 'web_id'=>2, 'cat_id'=>70]);
$page2 = \app\CMS\queryURL($url, ['page'=>2]);
```

### 子站管理

子站有自己的名称、标识、域名、模板风格和文章表前缀。后台建立／编辑子站时同步维护其数据库记录及`website/config_标识.php`；前台根据Host选择站点。站点域名还需要在Web服务器和域名解析中生效，只填写后台字段不会配置服务器。

CMS共享表使用应用级`db.pre`，子站文章表使用子站的`db.pre`。不要让两个独立文章站意外共用一个文章表前缀，也不要只复制子站数据库记录却漏掉对应配置文件。

### 会员与权限

普通会员使用前台`user/`相关页面，个人资料入口为`user/profile`。CMS管理员从该入口进入CMS后台，不通过普通会员资料页修改管理员账号。CMS后台按管理员组群的功能和网站权限控制可操作范围。

要加入第三方登录，在框架后台安装、配置、启用social，并绑定CMS；平台账号、昵称、邮箱补充及本站邮箱验证按[插件说明](../../plugin/social/README.md)操作。后台“插件”动态入口只显示已启用且绑定当前应用、并允许当前管理员管理的功能；表单源码等高权限功能另要求框架管理员。

### 模板调用

默认业务脚本位于`module/`，HTML位于对应风格的`template/`。栏目和文章通常分别使用`catalog_*.tpl`、`article.tpl`；在模板中可调用CMS的news、catalog、link、tag、pages等标签。

```html
<!--news catalog="1" limit="5" template="simple"-->
```

把1换为实际栏目编号。标签的列表子模板决定每行HTML，limit决定本次取数数量，不直接将SQL或用户输入作为模板标签参数。

## 设置说明

应用级值保存在`app/CMS/config.php`，初始设置模板为`config_default.php`，后台字段描述在`config/`。子站值另存于`website/config_标识.php`；未覆盖的配置由应用和主框架继承。所有磁盘路径相对于网站根，以下账号、域名和编号均为示例。

### setting-gen-force_domain

**域名跳转**（`gen.force_domain`）

是否将请求定向到为应用绑定的域名。先配置并验证域名绑定、DNS和站点解析，再开启；未绑定域名时，不把此开关当成域名配置入口。

示例：

```text
true
```

### setting-web-title

**网站名称**（`web.title`）

CMS应用的基础标题。各子站可以覆盖自己的标题，文章页还会组合文章和栏目信息。

示例：

```text
内容管理系统
```

### setting-web-path_admin

**后台路径**（`web.path_admin`）

CMS自己的后台逻辑路径，不是框架管理后台的gen.path_admin。通过框架应用参数设置修改并保存，使入口配置和路由同步。

示例：

```text
admin_cms
```

### setting-db-auto

**自动连接**（`db.auto`）

CMS页面要读取栏目、文章和会员，通常开启自动连接。关闭后需要由自己的执行流程显式建立连接。

示例：

```text
true
```

### setting-db-type

**数据库类型**（`db.type`）

按页面提供的选项选择mysql或mssql，连接信息须与所选驱动匹配。完整内容站应使用已安装数据表所对应的数据库类型，不能只改这一项就切换数据。

示例：

```text
mysql
```

### setting-db-host

**服务器地址**（`db.host`）

PHP能够连接到的数据库主机。按驱动格式填写；MySQL可填写主机和端口，不填网页协议或管理工具网址。

示例：

```text
127.0.0.1:3306
```

### setting-db-user

**数据库用户**（`db.user`）

有权访问CMS数据库的账号；数据库账号与后台管理员账号无关。

示例：

```text
cmsuser
```

### setting-db-password

**数据库密码**（`db.password`）

上面数据库账号的口令。在后台按要求填写并确认；只修改其他参数时，两框留空可保留已保存口令。示例仅说明格式。

示例：

```text
ExampleOnly_2026
```

### setting-db-pconnect

**持久连接**（`db.pconnect`）

是否请求数据库驱动使用持久连接；不是字符集设置。是否实际复用连接还取决于驱动及PHP运行方式。通常先保持关闭。

示例：

```text
false
```

### setting-db-charset

**数据库编码**（`db.charset`）

按设置页选项选择utf-8、gbk或latin1，并与现有数据编码相符。通常选择utf-8，不通过此项把已存数据直接转换成另一种编码。

示例：

```text
utf-8
```

### setting-db-name

**所用数据库**（`db.name`）

已经创建、且数据库用户有权访问的数据库名称，不是数据表名或服务器地址。

示例：

```text
cmsdata
```

### setting-db-pre

**数据表前缀**（`db.pre`）

CMS共享业务表的前缀，必须与已安装表名一致。子站文章表前缀在子站参数中另设；安装后不随意改动此项。

示例：

```text
cms_
```

### setting-content-cat_pos

**栏目显示**（`content.cat_pos`）

以半角逗号分隔的栏目显示位置名称。栏目管理中据此生成勾选项；模板仍须读取对应显示标记才会展示。位置顺序影响存储标记，已有数据使用期间保持稳定。

示例：

```text
主导航,列表导航,自定义位置
```

### setting-content-push_pos

**推送位置**（`content.push_pos`）

文章可推送到的位置名称，以半角逗号分隔。需要与模板的筛选条件配合；勾选推送不等于自动创建页面区域。位置顺序保持稳定。

示例：

```text
首页,列表页,内容页
```

### setting-content-push_mode

**推送模式**（`content.push_mode`）

文章推送时可选的展示类型名称，以半角逗号分隔。模板按所选标记决定展示方式；调整名称时保留原有顺序。

示例：

```text
不推送,标题列表,幻灯图片
```

### setting-content-upload

**上传路径**（`content.upload`）

CMS内容配置中的路径项，值相对于网站根。通用上传接口实际还读取框架upload分组；本项本身不改变通用上传接口的保存目录。需要调整上传位置时同时查阅主框架上传设置及调用模块。

示例：

```text
files/
```

### setting-content-get_remote_img

**自动下载**（`content.get_remote_img`）

控制文章录入时“下载远程文件”选项的默认勾选状态。编辑文章时还可针对本次操作选择；不等于浏览每一篇文章时都重新下载图片。

示例：

```text
true
```

### setting-template-name

**调用模板**（`template.name`）

前台外层模板文件名，不带扩展名；栏目、文章等主体模板由模块选择。

示例：

```text
main
```

### setting-template-path

**模板路径**（`template.path`）

CMS模板根目录，相对于网站根。风格是此目录下的子目录，不在本项重复拼接风格名。

示例：

```text
app/CMS/template
```

### setting-template-style

**模板样式**（`template.style`）

前台风格子目录名，例如default或guide。子站可独立覆盖。CMS后台使用入口配置中的admin风格，不通过本项把所有前台改成后台样式。

示例：

```text
default
```

### setting-session-mode

**Session处理模式**（`session.mode`）

按设置页选择sess_mystep、sess_mysql或sess_file。前两种需相应数据库结构和连接，文件模式需可写的框架Session目录；按实际服务选择，不把存储模式当成会员登录开关。

示例：

```text
sess_mystep
```

### setting-list-txt

**标题列表**（`list.txt`）

文字列表每页数量，当前栏目、搜索、标签及多项后台列表读取本项。填写合理的正整数。

示例：

```text
20
```

### setting-list-img

**图片展示**（`list.img`）

供图片列表及自定义展示读取的数量参数。具体模板的news标签如明确指定limit，则以标签实际传入数量为准。

示例：

```text
24
```

### setting-list-mix

**图文混合**（`list.mix`）

供图文列表及自定义展示读取的数量参数。需要模板主动使用，设置值不会改写模板中写死的limit。

示例：

```text
10
```

### setting-expire-default

**默认时限**（`expire.default`）

页面缓存的默认过期时间，单位秒。没有按模块特别指定时使用；是否生成缓存还受框架页面缓存开关及具体模块控制。

示例：

```text
600
```

### setting-expire-index

**索引页时限**（`expire.index`）

首页相关页面缓存时长，单位秒；频繁更新的首页可设置较短时间。

示例：

```text
1800
```

### setting-expire-catalog

**列表页时限**（`expire.catalog`）

栏目页面及部分栏目数据缓存的时长，单位秒。内容更新后可通过后台清理对应缓存。

示例：

```text
3600
```

### setting-expire-tag

**标签页时限**（`expire.tag`）

标签页面及相关标签查询缓存时长，单位秒；与标签内容更新频率配合。

示例：

```text
86400
```

### setting-expire-article

**内容页时限**（`expire.article`）

文章页面缓存时长，单位秒。文章模块先检查记录和阅读权限，再使用相应页面缓存，不以长缓存替代权限校验。

示例：

```text
604800
```

### setting-watermark-thumb

**缩略图宽**（`watermark.thumb`）

图片附件处理时允许的最大宽度，单位像素。超过此宽度时按比例缩小；不修改文章富文本中作者设置的CSS尺寸。

示例：

```text
1024
```

### setting-watermark-mode

**水印模式**（`watermark.mode`）

在设置页分别勾选文章水印和图片水印。文章标记为1，图片标记为2；可都选或都不选。正文水印由文章模块处理，图片水印用于经过CMS附件处理入口的图片，不是给所有静态图片统一加水印。

示例：

```text
选择：图片水印
```

### setting-watermark-txt

**干扰文字**（`watermark.txt`）

提供给定制水印功能读取的文字参数。当前正文水印调用实际使用watermark.credit；需要改变正文版权文字时填写credit，不仅修改本项。

示例：

```text
示例站点
```

### setting-watermark-img

**图片水印**（`watermark.img`）

填写网站根相对水印图片路径，源文件必须存在，图片水印开关也须开启。当前附件入口先检查此项文件是否存在，普通文字不会在此入口自动变成图片水印。

示例：

```text
static/images/logo.png
```

### setting-watermark-position

**水印位置**（`watermark.position`）

选项对应位置编号：1右下、2右上、3左下、4左上、5左中、6右中、7中上、8中下、9正中。

示例：

```text
1
```

### setting-watermark-img_rate

**水印比例**（`watermark.img_rate`）

缩放水印图片使用的比例参数，数值越大，水印图通常越小。结合原图尺寸预览，不填零。

示例：

```text
4
```

### setting-watermark-txt_font

**水印字体**（`watermark.txt_font`）

文字水印渲染所用字体文件的网站根相对路径，文件须存在且支持所用文字。只使用图片水印时不会自动把图片转换为文字水印。

示例：

```text
static/fonts/font.ttc
```

### setting-watermark-txt_fontsize

**字体尺寸**（`watermark.txt_fontsize`）

传给文字水印渲染的字号参数。只有调用文字水印渲染时使用，不改变网页正文字号。

示例：

```text
12
```

### setting-watermark-txt_fontcolor

**字体颜色**（`watermark.txt_fontcolor`）

文字水印的颜色，按设置页要求填写六位十六进制HTML颜色。

示例：

```text
#FFFFFF
```

### setting-watermark-txt_bgcolor

**背景颜色**（`watermark.txt_bgcolor`）

文字水印背景色，按六位十六进制HTML颜色填写，不是文章页面背景色。

示例：

```text
#000000
```

### setting-watermark-alpha

**水印透明度**（`watermark.alpha`）

水印合成的0至100比例参数；在使用此参数的合成分支中，较大的值表示水印更明显。结合输出预览调整，不把它当作CSS的0至1透明度。

示例：

```text
50
```

### setting-watermark-credit

**版权文字**（`watermark.credit`）

文章水印实际使用的文字。在文章水印开关启用时参与正文水印处理；不是自动添加在页面底部的公开版权栏。

示例：

```text
版权所有：示例站点
```

## 子站设置

CMS后台的子站参数不是对`app/CMS/config.php`的整体替换，只覆盖当前子站的项目。

### site-gen-language

**网站语言**（`gen.language`）

当前子站的内容界面语言，填写存在的语言标识；默认语种可留为框架支持的default，中文示例为chs。

示例：

```text
chs
```

### site-gen-close

**关闭页面**（`gen.close`）

留空正常开放；非空时作为关闭提示内容、网站根相对说明文件或HTTP/HTTPS跳转地址。框架在非调试模式执行关闭判断。

示例：

```text
维护中，请稍后再试
```

### site-web-title

**网站名称**（`web.title`）

当前子站标题，覆盖CMS基础标题，不改变其他子站。

示例：

```text
产品中心
```

### site-web-keyword

**网站关键字**（`web.keyword`）

逗号分隔的网站关键字，供当前子站模板输出；不替代每篇文章自己的标签和关键词。

示例：

```text
产品,服务,技术
```

### site-web-description

**网站描述**（`web.description`）

当前子站的简短介绍；文章页可按正文摘要生成自己的描述。

示例：

```text
展示产品资料与服务信息
```

### site-template-style

**模板样式**（`template.style`）

当前子站使用的CMS前台风格子目录，例如default或guide，目录须位于CMS的template.path内。

示例：

```text
default
```

### site-db-pre

**数据表前缀**（`db.pre`）

当前子站文章表的前缀，例如product_news_show、product_news_detail对应product_。CMS共享用户、栏目等仍使用应用级前缀；前缀要与已建立的表一致。

示例：

```text
product_
```

### site-watermark-mode

**水印模式**（`watermark.mode`）

仅覆盖当前子站的文章／图片水印选择，含义与应用级watermark.mode相同；其他水印参数继承当前应用配置。

示例：

```text
选择：图片水印
```

## 目录与扩展

| 位置 | 用途 |
| --- | --- |
| `CMS.class.php` | 继承myStep，提供CMS登录、会员服务及站点初始化 |
| `config.php`／`config_default.php` | 当前应用配置与安装初始配置 |
| `config_后台入口.php` | 指定后台入口使用admin风格并关闭页面缓存 |
| `website/` | 各子站配置及子站设置字段说明 |
| `route.php` | CMS后台及已登记API声明 |
| `global.php`／`lib.php` | 公共栏目、站点上下文、数据、链接和模板标签辅助能力 |
| `index.php` | 选择前台模块、准备主模板和当前风格内容 |
| `module/admin/` | 栏目、文章、链接、站点、会员及权限管理 |
| `template/admin/` | 对应CMS后台模板 |
| `template/default/`／`template/guide/` | 内容站和手册站前台风格 |
| `template/风格/module/` | 当前风格可选的数据准备脚本 |
| `asset/` | 各风格资源及编辑器初始化 |
| `install/` | CMS初始化页面、模板和建表资源 |
| `rss.php`／`atom.php`／`sitemap.php` | 内容订阅及站点索引输出 |

扩展业务按module对应template组织，公共CMS函数放已有lib.php。不要将CMS专属查询写到网站根lib中。

## 常见问题

- **打开后台跳到登录页**：使用CMS管理员及当前后台路径；框架登录和普通会员登录各有自己的身份用途。
- **选择子站后数据不正确**：核对子站标识、域名、`website/config_标识.php`和文章表前缀是否一致。
- **图片不能从其他机器显示**：图片URL使用站点生成地址或可访问的HTTPS地址；不用localhost、开发机磁盘绝对路径或只在服务器本机可用的地址。
- **修改模板或文章仍显示旧内容**：清理对应模板／页面／数据缓存，核对是否编辑当前站点及风格。
- **启用图片水印后无变化**：确认经过CMS附件处理入口、水印源文件存在，且相关图片处理缓存已失效；直接静态文件不经过此流程。
- **改列表数量没有生效**：检查当前模板的news标签是否另写了limit；模板明确参数优先。

## 相关资料

- [应用编写规范](../README.md)
- [框架指南](../../docs/README.md)
- [路由规则](../../docs/backend/routing/routing.md)
- [模板使用](../../docs/backend/templates/templates.md)

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
