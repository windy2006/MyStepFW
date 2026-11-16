# 表单构建系统（cform） <small>v2.3.0</small>

## 功能概述

提供可视化中英文表单设计、在线填写、附件收集、记录管理、CSV导入导出及确认邮件。每个表单可设置自己的页面模板和业务扩展。

运行于 MyStepFW PHP8 主线，要求 PHP >=8.5.12 且 <9，以及 mysqli、mbstring、fileinfo。使用框架现有的数据库、路由、会话、CSRF、模板、checkForm 和后台布局，不增加 Composer 或 npm 运行依赖。

## 安装与设置

1. 将整个目录放到 `plugin/cform/`，保留目录名及 `plugin_cform` 类名。
2. 在框架后台插件管理中安装并启用。插件读取网站根配置中的 MySQL 连接，独立建立 `cf8_forms`、`cf8_entries`，要求 InnoDB 和 utf8mb4；不会改 CMS 业务表。
3. 安装前在 `config.php` 确定 `table_prefix`，安装后不要更改。`upload_bytes` 是单附件字节上限，默认5 MiB、最多20 MiB；`submit_interval` 是同一会话、同一表单的提交间隔秒数，默认300。
4. 通过后台扩展菜单进入；Admin 与 myStep 后台使用同一套模块与模板，并保留各自布局。
5. 需要宿主模板标签或模块时，在插件关联中选择相应应用。独立路由不要求绑定 CMS。

重复安装会核对数据表与分页组合索引。卸载只移除菜单、路由及优先脚本登记，保留记录和附件；重新安装可继续使用。安装中的文件登记与建表不是一个事务，登记失败时已建立的数据表保留以便重试。

## 表单设计

设置中英文名称、活动状态、截止日期及公开列表开关。截止日期通过日期控件选择，仅保存 `YYYY-MM-DD`；留空不限，选定日期当天仍可填写，按框架时区在当日23:59:59结束。不存在的日期不会自动顺延。

字段设计器采用“列表＋浮动层编辑”：列表显示顺序、索引、名称、类型和说明；点击“添加项目”或某行“编辑”后，在浮动层设置单个字段。支持上移、下移、删除及 JSON 导入／导出。字段名使用小写字母开头的英文标识，后续可含数字或下划线，最长40字符；不使用 id、mid、action、revision、request_key 等保留名称。已有记录的字段名应保持稳定，改名相当于新增字段，不会删除原有数据。

| 设置 | 用法 |
| --- | --- |
| 类型 | text、textarea、radio、select、checkbox、file |
| 标题／说明 | 分别设置中文与英文内容 |
| 格式 | email、digital、number、date、mobile、url；空值为普通文本，后缀 `_` 表示可选格式 |
| 必填 | 服务器独立验证，纯空白文字不能满足必填 |
| 最大长度 | 普通文字默认255字符，长文字默认4000，上限20000 |
| 选项 | 中文和英文按位置对应，两种语言各自不能重复或留空；记录统一存中文值 |
| 默认值 | 对应语言的选项文字；多选通常以逗号分隔，若整段本身是一项则优先按单项匹配 |
| 仅管理员编辑 | 不出现在普通填写页、公开列表或提交者回执；对应附件也仅管理员可下载 |
| 可检索 | 管理记录页的关键词查询只匹配勾选的字段 |
| 公开列表展示 | 仅在表单公开列表开关开启时生效；附件及管理员字段始终不公开 |

浮动层中的“确定”只更新当前列表，“取消”、关闭按钮或 Esc 不改动字段；最终点击“保存表单”才写入站点。字段编辑使用独立草稿，不会因取消而留下半成品。

“导入／导出”在独立浮动层显示当前 JSON，可复制导出或粘贴定义后点击“应用字段定义”。先检查结构再替换列表，失败或取消保留已有设计。填写说明和确认邮件位于折叠区域。保存时服务器再次校验。表单版本发生变化时，变更前打开的设计页及提交请求会提示重新打开。

## 填写与记录

| 逻辑路由 | 用途 |
| --- | --- |
| `cform/manage/` | 管理表单、记录、CSV 和邮件 |
| `cform/submit/编号` | 填写；`?lng=en` 使用英文 |
| `cform/list/编号` | 公开列表；`?page=2` 翻页 |
| `cform/print/编号?id=记录ID` | 当前提交会话或管理员查看回执 |
| `cform/file/编号?id=记录ID&field=字段名` | 授权下载附件 |

实际链接由框架生成，支持子目录及三种路由模式，不手工拼接根域名。普通 CMS 会员不视为框架管理员。

提交凭证有效2小时；同一有效凭证重复提交返回同一记录，跨会话不能复用。校验失败会返回表单并保留文字与选项，附件需要重新选择。回执归属基于会话，清除 Cookie 或换浏览器后不能仅凭编号读取记录，由管理员协助查询。

附件使用随机文件名保存在私有 `config/custom_form_uploads/`，下载强制使用附件模式。文件扩展名须与实际 MIME 类型匹配，支持 PDF、文字、CSV、常见图片、Office 文件及 ZIP。移除字段、删除记录和卸载不会自动删除附件，整理空间前应先备份并核对记录引用。

## CSV 导入与导出

导出为 UTF-8 CSV，包含字段英文名表头；内容作为数据导出，对可能触发表格公式的文字增加前缀。多选使用 JSON 数组保存，例如 `["含,逗号的选项","其他"]`，避免选项本身含逗号时拆错。

导入最大5 MiB、5000行；首行为字段英文名。多选推荐使用上述 JSON 数组，使用字段索引与当前字段类型填写数据，导入会生成新的记录编号和时间。附件路径不能通过 CSV 伪造；必填附件需人工补录。先完整校验再事务写入，任一行失败不会部分入库。

## 确认邮件

在当前后台应用配置 SMTP 与有效发件邮箱（from 或 user）。中英文确认邮件模板是发送给用户的正文格式，标题固定使用当前语言的活动名称（英文名称留空时使用中文名称）。记录列表中的“确认邮件”进入草稿预览页；可调整本次收件人和正文后再发送，不覆盖表单模板，标题仅供查看。默认使用记录的 email 字段。展开正文上限1 MiB，单次草稿编辑上限100000字符。

记录的状态分为“未发送”“发送中/待核对”“已发送”。同时点击或并发请求只允许一个请求领取发送权；SMTP 确认接收后标记已发送，SMTP调用前的配置、准备或校验失败恢复未发送；进入SMTP后的异常保持“发送中／待核对”，避免不确定投递后的重复发送。网络中断可能使实际投递状态不确定，SMTP 接收也不等于最终送达。

进程异常留下“发送中/待核对”时，管理员先检查投递结果，再点击“重置发送状态”；确认未投递后再重发。修改记录不会自动重置已发送状态。

## 模块与标签

宿主初始化后可用 cf_submit、cf_list 模块，以及 cf_submit、cf_list URL 辅助入口。公开列表标签示例：

```html
<!--cf_list mid="1" limit="10" lng="cn"-->
```

标签读取当前允许公开的字段，并使用该表单的对应语言列表子块。不接受任意 SQL 或 condition/order 参数。

## 每个表单的页面模板

设计器中的“页面模板与功能扩展”按页面切换源码编辑，不影响字段列表。统一模板位于 `template/defaults/`，每个表单可覆盖以下12项：

| 标识 | 页面 |
| --- | --- |
| `cf_submit_cn` / `cf_submit_en` | 中英文前台填写 |
| `cf_print_cn` / `cf_print_en` | 中英文打印／回执 |
| `cf_list_cn` / `cf_list_en` | 中英文公开信息列表页面 |
| `block_cf_list_cn` / `block_cf_list_en` | 中英文列表子块，完整列表和cf_list标签共用 |
| `mail_cn` / `mail_en` | 中英文HTML确认邮件 |
| `edit_data` | 后台添加／编辑记录 |
| `list_data` | 后台记录列表、操作工具和分页 |

编辑框直接显示当前生效源码，未自定义时显示统一默认。空白或默认源码原样提交均保持继承（换行差异不会产生覆盖）；“恢复继承默认”会把默认源码重新放回编辑框。修改为不同源码后才保存为单表单覆盖。按“保存表单”后生效。覆盖与字段定义在同一修订中保存，其他表单不受影响；统一默认文件的变化不会覆盖已定制源码。

模板使用框架 `myTemplate` 标签、循环和可信PHP。模板和扩展仅供框架管理员编辑，具有服务器代码权限，与安装应用代码相同；PHP模板通过 `$cf` 取得本次上下文。运行副本写入私有 `config/custom_form_runtime/`，按源码散列隔离，结果不做公共页面缓存；不要把该目录当成手动编辑入口。

常用变量：

- 填写／记录编辑：`title`、`action`、`hidden`、`fields`、`submit`、`back_url`、`back_label`；`field_name`是姓名字段完整区块，`control_name`是单独控件。`field`循环提供key/title/comment/control。
- 列表页：`title`、`count`、`block`、`pagination`。子块及后台列表的`record`循环提供允许展示的字段和id/created_at/cells；如`record_name`。`headers`和`rows`是默认表格HTML；后台另有`tools`、`message`。
- 打印和邮件：`title`、`id`、`created_at`、`rows`以及`record_字段索引`。邮件另有`greeting`、`confirmation`。
- `$cf['form']`为当前表单；`$cf['vars']`为展示变量；按页面另有language/admin等上下文。不要在公共模板主动访问未公开的业务数据。

普通数据使用`|html`转义，已经生成的控件、隐藏字段、表格HTML和子块直接插入。例如定制填写页：

```html
<h1><!--title|html--></h1>
<form method="post" enctype="multipart/form-data" action="<!--action|html-->">
  <!--hidden-->
  <div class="my-layout"><!--field_name--><!--field_email--></div>
  <button type="submit"><!--submit|html--></button>
</form>
```

必须保留hidden（含CSRF和提交凭证）、action及需要填写的控件；遗漏必填字段仍会被服务端拒绝。公共模板生成的控件不会包含管理员字段，公共列表循环只含明确公开且非管理员、非附件的字段。

自定义列表子块可直接选择字段，不必输出默认表格：

```html
<ul>
<!--loop:start key="record"-->
  <li><!--record_id|html-->：<!--record_name|html--></li>
<!--loop:end-->
</ul>
```

双语邮件模板就是确认邮件正文，使用相同变量与HTML标签，邮件标题由活动名称生成。填写说明直接写在中英文前台报名模板中，不再提供独立的填写说明设置。

## 表单功能扩展

每个表单可以在不同业务阶段运行自己的PHP闭包。统一默认源码为`extension-default.txt`，留空或原样提交默认源码时继承；单表单覆盖不会影响其他表单。

事件按明确用途分为准备、输入整理、业务校验、事务内写入、提交成功和展示变量调整。邮件预览与实际发送分开；列表有逐条记录事件，输入阶段在字段验证前，事务内事件可与关联写入一起回滚，成功通知不会撤销已经完成的操作。完整名称、上下文和调用时机见[表单扩展事件](EVENTS.md)。

```php
return static function(string $event, array &$context): void {
    // cform-events:2
    switch ($event) {
        case 'submission_input':
            $context['input']['email'] = trim($context['input']['email'] ?? '');
            break;

        case 'submission_validate':
            if (trim($context['data']['company'] ?? '') === '') {
                throw new DomainException('请填写公司名称');
            }
            break;

        case 'submission_persisted':
            // 编号已取得、事务尚未提交，可通过同一store处理关联数据。
            break;

        case 'submission_saved':
            // 已提交，适合通知；不要把发信等副作用放到展示事件中。
            break;
    }
};
```

正常编辑页也有相应扩展事件。错误代码阻断页面时，使用“修复功能扩展”入口：该通道不执行扩展，只在管理员、CSRF和修订保护下更新源码。编写扩展时使用默认示例中的事件名称。

### 源码高亮编辑

复用框架现有 `vendor/codemirror/`，展开模板设置时按需加载本地资源。页面模板使用HTML／CSS／JavaScript／PHP混合模式，扩展使用不带PHP标签的纯PHP模式；提供行号、当前行高亮、括号匹配和Tab缩进。每个页面保持自己的编辑草稿，切换不会覆盖内容；编辑器变更与提交前会同步原始表单字段。资源加载失败时保留文本框编辑与保存能力。编辑资源来自网站本地。

## 应用后台管理

1. 在框架插件管理中启用 cform，并在“生效应用”中勾选需要引用的应用，如 CMS。
2. CMS组群设置中勾选“插件：表单管理”；拥有“全部权限”的管理员可直接使用。
3. 在CMS后台的“插件 → 表单管理”中新建／设计表单、管理提交记录、导入导出、发送确认邮件、打印及下载附件。保存、分页及返回均沿用CMS后台会话。

应用管理员可以修改名称、日期、字段及开放状态。页面模板与PHP功能扩展只允许框架管理员编辑，应用管理员的页面不显示源码编辑区；手工提交源码或访问修复入口同样会被拒绝。编辑基本信息会保留该表单已有的模板和扩展。

各后台入口管理同一组表单；应用绑定决定插件执行及管理入口范围，不另建一份业务数据。取消应用绑定或撤销组群授权后，该应用中的菜单及直接管理入口同时失效；框架总后台保留已启用cform的管理链接，无需额外绑定myStep或Admin。停用插件后所有管理入口均停止开放。其他应用按照[动态后台入口](../README.md#19-应用后台动态入口)提供一次通用接入即可。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-table_prefix

**独立表前缀**（`table_prefix`）

表单数据表的独立前缀，安装前确定；安装完成后修改不会迁移已有表或记录。使用字母开头的字母、数字、下划线。

示例：

```text
cf_
```

### setting-upload_bytes

**附件字节限制**（`upload_bytes`）

单个附件字节上限，1至20971520。还受PHP上传及请求正文上限约束；5242880表示5 MiB。

示例：

```text
5242880
```

### setting-submit_interval

**提交间隔秒数**（`submit_interval`）

同一会话提交同一表单的最短间隔，0至86400秒。用于避免连续重复提交；设置0表示不额外等待，仍检查CSRF及提交凭证。

示例：

```text
5
```

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-name

**中文名称**（`name`）

活动或表单名称，同时作为中文确认邮件标题。

示例：

```text
开发者报名
```

### field-name_en

**英文名称**（`name_en`）

用于英文页面和邮件标题，未填写时回退中文名称。

示例：

```text
Developer registration
```

### field-expire

**截止日期**（`expire`）

YYYY-MM-DD，选定当天23:59:59前仍可填写；留空不限。

示例：

```text
2026-12-31
```

### field-notes

**备注**（`notes`）

后台维护用途，不是前台填写说明；前台介绍写在报名模板中。

示例：

```text
年度活动
```

### field-active

**开放填写**（`active`）

关闭后不再接受前台新提交，后台仍可管理记录。

示例：

```text
勾选
```

### field-public_list

**公开提交列表**（`public_list`）

打开公开名单入口；仍只展示单独设为公开的普通字段，管理员字段和附件始终不公开。

示例：

```text
不勾选
```

### field-field_key

**字段索引**（`field_key`）

小写字母开头、由小写字母数字下划线组成的内部字段名，最多40位，不能使用系统保留名称；改名会被视为新字段，原记录的原有值仍保留。

示例：

```text
email
```

### field-type

**控件类型**（`type`）

text单行、textarea多行、radio单选、checkbox多选、select选单、file附件。

示例：

```text
text
```

### field-title

**中文字段名**（`title`）

填写页面显示的字段标题。

示例：

```text
邮箱
```

### field-title_en

**英文字段名**（`title_en`）

英文页面字段标题。

示例：

```text
Email
```

### field-comment

**中文字段说明**（`comment`）

字段旁的填写提示。

示例：

```text
请输入常用邮箱
```

### field-comment_en

**英文字段说明**（`comment_en`）

英文填写提示。

示例：

```text
Enter your email
```

### field-value_cn

**中文选项**（`value_cn`）

单选、多选、下拉使用，每行一项。

示例：

```text
线下参加
线上参加
```

### field-value_en

**英文选项**（`value_en`）

可留空；填写时与中文按行对应。

示例：

```text
Onsite
Online
```

### field-format

**数据格式**（`format`）

可选email、url、digital、number、date、mobile；空为任意格式，仍进行字段类型与长度检查。

示例：

```text
email
```

### field-length

**长度限制**（`length`）

允许的字段长度，界面范围1至20000；留空采用系统默认。

示例：

```text
254
```

### field-default

**中文默认值**（`default`）

新填写时的初始值，选项类型按字段选项设置。

示例：

```text
示例公司
```

### field-default_en

**英文默认值**（`default_en`）

英文填写页使用的初始值。

示例：

```text
Example company
```

### field-required

**必填项**（`required`）

开启后空值不能通过前后台的字段检查。

示例：

```text
勾选
```

### field-search

**用于检索**（`search`）

在后台记录检索中包含该字段。

示例：

```text
勾选
```

### field-manager

**仅管理员编辑**（`manager`）

字段仅供后台维护，前台填写不可编辑，公开列表不展示。

示例：

```text
不勾选
```

### field-public

**公开展示**（`public`）

该字段可用于公开列表；需要同时开启表单的公开提交列表，管理员字段和附件不公开。

示例：

```text
不勾选
```

### field-fields_json

**字段导入导出**（`fields_json`）

以字段索引为键的JSON对象用于复制定义，导入后检查并保存表单；不用于导入报名记录。

示例：

```text
{"name":{"type":"text","title":"姓名","required":true}}
```

### field-extension

**PHP功能扩展**（`extension`）

仅框架管理员可改。返回接受$event及引用$context的闭包，按业务阶段处理；详见表单功能扩展及EVENTS.md。

示例：

```text
return static function(string $event, array &$context): void {};
```

### field-mail_to

**邮件收件人**（`mail_to`）

本次确认邮件的目标邮箱；邮件标题来自表单名称，正文来自对应语言邮件模板。

示例：

```text
reader@example.com
```

### field-mail_html

**邮件正文**（`mail_html`）

本次发送的HTML邮件内容；持久默认正文请修改mail_cn或mail_en模板。

示例：

```text
<p>报名已确认。</p>
```

### field-templates-cf_submit_cn

**中文填写页模板**（`templates-cf_submit_cn`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-cf_submit_en

**英文填写页模板**（`templates-cf_submit_en`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-cf_print_cn

**中文打印页模板**（`templates-cf_print_cn`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-cf_print_en

**英文打印页模板**（`templates-cf_print_en`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-cf_list_cn

**中文公开列表模板**（`templates-cf_list_cn`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-cf_list_en

**英文公开列表模板**（`templates-cf_list_en`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-block_cf_list_cn

**中文列表子块模板**（`templates-block_cf_list_cn`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-block_cf_list_en

**英文列表子块模板**（`templates-block_cf_list_en`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-mail_cn

**中文确认邮件模板**（`templates-mail_cn`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-mail_en

**英文确认邮件模板**（`templates-mail_en`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-edit_data

**后台记录编辑模板**（`templates-edit_data`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-templates-list_data

**后台记录列表模板**（`templates-list_data`）

仅框架管理员可改。空白或默认源码原样提交使用继承；修改后只影响当前表单。保留该页所需的action、hidden、字段及循环占位，普通数据使用|html转义。

示例：

```text
<!--title|html-->
```

### field-template-selector

**选择页面或功能**（`template-selector`）

切换当前编辑的页面模板或PHP功能扩展；各页草稿分别保留，最后保存表单才应用。

示例：

```text
中文填写页
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
