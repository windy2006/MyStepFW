# 表单扩展事件

扩展属于当前表单，在相应操作阶段执行，不依赖旧CMS的全局method或ext_func。默认源码列出本文件全部事件，每个case只表示一个阶段。所有入口先完成活动状态、管理员、记录归属、CSRF、提交凭证等适用检查，才进入业务扩展；被入口检查拒绝的请求不执行扩展。

## 阶段与用途

| 阶段 | 插入位置与适合用途 |
| --- | --- |
| prepare | 已读取定义、记录或查询参数，但尚未生成控件／正文；准备默认值、规范化展示数据 |
| input | 已完成请求和上传检查，尚未验证字段；整理原始字段、补齐服务端默认值 |
| validate | 基础验证已经通过，尚未写入；业务规则、跨字段检查、计算标准值；修改后再次严格验证 |
| persisted | SQL已执行且新编号可用，事务尚未提交；同一store连接上的关联写入可一起提交或回滚 |
| created／updated／saved／deleted／completed／sent等成功事件 | 相应提交或生成操作成功后；通知、联动；报错记日志并提示，不撤销已完成操作 |
| render | 展示变量已经生成、模板尚未渲染；调整布局变量；逐条数据变换应使用item或prepare |
| operation_failed | 已授权业务操作失败、错误响应生成前；读取operation、stage、error定位原因；同一次异常链只通知一次 |

不是每个操作都机械设置所有阶段。例如文件下载只在权限、令牌、路径和文件存在检查后提供一次验证事件；导出完成表示文件生成完毕，不表示浏览器已经收到文件。上传文件是提交或记录保存的一部分，没有第二套重复的上传写入事件。

字段新增、编辑、排序、模板切换等浏览器草稿动作不向服务器发送请求，不运行PHP；最终保存表单时在form_create_validate或form_update_validate中统一检查字段和模板变更。

## 上下文和修改规则

```php
return static function(string $event, array &$context): void {
    // cform-events:2
    switch ($event) {
        case 'entry_create_input':
            $context['input']['email'] = trim($context['input']['email'] ?? '');
            break;

        case 'entry_create_persisted':
            // $context['id']已有编号，可通过plugin_cform::store()处理关联数据。
            // 必须使用同一store连接才能参与当前事务；不要自行commit或执行DDL。
            break;

        case 'entry_created':
            // 此时事务已经提交，适合通知或调用外部服务。
            break;
    }
};
```

- `form`是当前表单；仅表单定义的prepare和validate事件允许调整该对象，调整后重新验证。id和revision由服务器固定，不能用上下文更换目标表单。
- `input`是当前操作允许接收的原始字段，按`language`保留提交语言；控制参数、未知字段不作为业务字段使用。
- `data`在写入validate阶段是标准中文值；前台不能借扩展上下文把客户端的管理员字段或附件令牌写入记录。附件仍使用已校验的上传结果。
- 页面prepare阶段的`data`用于生成控件；校验失败回显时`submitted`为true，此时不要无条件覆盖用户输入。
- `previous`在定义修改或记录写入阶段提供旧值，可用于比较修改内容。定义prepare阶段直接读取当前`form`。
- `query`支持page、size、keyword、search_fields。后台size为1至1000，公开页和子块为1至100；搜索字段限制在对应允许范围，目标form_id始终固定，不解释任意SQL。
- 列表item事件每条记录调用一次，修改`data`后才生成单元格。公开数据在事件前后都按公开字段投影；管理员字段和附件不会通过公开循环输出。
- `vars`、`loops`是已生成的模板变量与循环；默认模板可能使用已拼好的rows或fields，因此逐条值调整使用item／prepare阶段最直接。
- `id`、`admin`、`language`、`source`等路由信息只读。admin表示后台展示／处理通道，不等于任意调用场景中的访问者身份。
- `operation`描述当前业务操作；`event`保留真实的新事件名。`operation_failed`另有stage、error（class/message）以及适用的id、row_index等诊断信息。
- `persisted`和成功阶段的上下文数据只读，不会因修改数组而再次保存；关联业务通过同一store进行参数绑定写入。成功通知内可调用外部服务，但不能依靠抛异常撤销提交。
- 不要echo、var_dump、exit或自行发送响应头。展示内容通过vars／模板调整，业务拒绝抛DomainException；直接输出会被捕获并拒绝，避免破坏JSON、CSV和下载响应。

## 频度、事务和邮件

单次正常操作只进入相应阶段一次；item／row按实际返回或解析的记录逐条执行。前台同一凭证重放不会重新运行input、validate、persisted、saved；重定向后的打印和普通刷新会正常运行展示阶段。不要在展示阶段发送确认邮件等副作用。

写入服务在persisted阶段提供相同数据库连接的事务。该阶段抛异常会撤销核心写入与同连接上的关联写入；新上传文件随失败写入清理。import_validate在完整批次写入前运行，import_persisted在全部行插入后、外层事务提交前运行；不会同时触发后台entry_created，避免同一行走两套新增通知。

表单列表没有选定单一表单：form_list_item按各表单自己的扩展运行；最后的form_list_render只运行统一默认扩展，并操作整页vars。新建表单尚无已保存扩展，其准备／渲染阶段使用默认扩展；提交的新脚本通过语法验证后，在本次form_create_validate阶段起生效。

mail_preview_prepare／mail_preview_render只用于预览。实际发送先原子领取发送权、检查SMTP配置，再进入mail_send_prepare；邮件内容生成后执行mail_send_validate，最终检查收件人、正文和固定活动标题后才调用SMTP。SMTP接受后，发送标记和同连接联动在mail_sent_persisted事务中处理；提交后才通知mail_sent。

配置、准备或最终验证失败且尚未进入SMTP时，恢复未发送状态。进入SMTP后的异常或SMTP接受后的状态联动失败保留“发送中／待核对”，控制器不会因该异常自动重发。operation_failed上下文提供delivery_attempted和delivery_accepted，需核对投递结果后再重置。确认邮件标题始终是当前语言活动名称，扩展不能覆盖。

成功通知报错只写入私有`config/custom_form_events.log`并产生管理提示，不把成功伪装成失败；一次异常链的失败通知也只运行一次。嵌套子块维护自己的表单上下文，不污染外层失败归属。

## 完整事件清单

| 事件 | 位置／频度 | 可调整内容 |
| --- | --- | --- |
| `form_list_item` | 每个表单生成管理列表行之前 | vars |
| `form_list_render` | 表单管理列表整页渲染前，仅使用默认扩展 | vars |
| `form_create_prepare` | 新建表单设计页：默认定义生成后、控件生成前 | form |
| `form_create_render` | 新建表单设计页：展示变量生成后 | vars |
| `form_update_prepare` | 编辑表单设计页：定义读取后、控件生成前 | form |
| `form_update_render` | 编辑表单设计页：展示变量生成后 | vars |
| `form_create_validate` | 新增表单：基础定义验证后、入库前 | form |
| `form_update_validate` | 修改表单：修订核对和基础验证后、入库前 | form; previous为原定义 |
| `form_create_persisted` | 新增表单已写入、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `form_created` | 新增表单已入库并取得编号 | 仅通知 |
| `form_update_persisted` | 表单修改已写入、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `form_updated` | 修改表单已入库并更新修订号 | 仅通知 |
| `form_delete_validate` | 表单停用前 | 抛出异常可阻止 |
| `form_delete_persisted` | 停用标记已写入、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `form_deleted` | 表单已停用，报名记录仍保留 | 仅通知 |
| `submission_prepare` | 前台填写页：初始数据准备后、生成控件前 | data |
| `submission_render` | 前台填写页：展示变量生成后 | vars; loops |
| `submission_input` | 前台提交：凭证核对及上传检查后、字段验证前 | input |
| `submission_validate` | 前台提交：标准中文值验证后、入库前 | data |
| `submission_persisted` | 前台新提交已写入并取得编号、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `submission_saved` | 前台新提交已入库并登记幂等结果 | 仅通知，重放不触发 |
| `entry_create_prepare` | 后台新增记录：默认数据准备后、生成控件前 | data |
| `entry_create_render` | 后台新增记录：展示变量生成后 | vars; loops |
| `entry_update_prepare` | 后台编辑记录：记录读取后、生成控件前 | data |
| `entry_update_render` | 后台编辑记录：展示变量生成后 | vars; loops |
| `entry_create_input` | 后台新增记录：上传检查后、字段验证前 | input |
| `entry_update_input` | 后台修改记录：旧记录读取及上传检查后、字段验证前 | input |
| `entry_create_validate` | 后台新增记录：标准中文值验证后、入库前 | data |
| `entry_update_validate` | 后台修改记录：标准中文值验证后、入库前 | data; previous为原值 |
| `entry_create_persisted` | 后台新增记录已写入并取得编号、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `entry_created` | 后台新增记录已入库并取得编号 | 仅通知 |
| `entry_update_persisted` | 后台记录修改已写入、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `entry_updated` | 后台记录修改已入库 | 仅通知 |
| `entry_list_query` | 后台记录列表：查询执行前 | query |
| `entry_list_item` | 后台记录列表：每条记录生成单元格前 | data |
| `entry_list_render` | 后台记录列表：整页展示变量生成后 | vars; loops |
| `entry_delete_validate` | 记录归属核对后、删除前 | 抛出异常可阻止 |
| `entry_delete_persisted` | 记录删除已执行、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `entry_deleted` | 记录已删除 | 仅通知 |
| `public_list_query` | 公开列表或子块：公开权限核对后、查询前 | query |
| `public_list_item` | 公开列表或子块：每条记录投影后、生成单元格前 | data，仅公开字段 |
| `public_block_render` | 公开列表子块：展示变量生成后 | vars; loops |
| `public_list_render` | 公开列表页面：子块完成后、整页渲染前 | vars; loops |
| `print_prepare` | 回执归属核对后、生成打印内容前 | data |
| `print_render` | 打印页展示变量生成后 | vars |
| `file_download_validate` | 附件权限、令牌及文件存在检查后，发送响应前 | 抛出异常可阻止 |
| `import_prepare` | CSV上传与表头读取后，列名验证前 | columns |
| `import_row_input` | 每行CSV拆列和多选解析后，字段验证前 | input |
| `import_row_validate` | 每行CSV字段验证后，加入批次前 | data |
| `import_validate` | 完整批次准备后，事务写入前 | rows |
| `import_persisted` | 完整导入批次已写入、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `import_completed` | 整批导入事务成功后 | 仅通知，含ids |
| `export_prepare` | CSV生成前，尚未发送下载响应头 | columns; query |
| `export_row` | 每条导出记录编码为CSV之前 | data |
| `export_completed` | CSV完整生成后、发送响应前，不代表客户端已接收 | 仅通知 |
| `mail_preview_prepare` | 邮件预览：记录读取后、模板生成前 | data; recipient |
| `mail_preview_render` | 邮件预览：草稿生成后、预览页渲染前 | mail.html; recipient |
| `mail_send_prepare` | 发送权领取和SMTP配置验证后、邮件生成前 | data; recipient; input.mail_html |
| `mail_send_validate` | 实际邮件生成后、最终验证和SMTP调用前 | mail.html; recipient |
| `mail_sent_persisted` | SMTP已确认、发送标记已写入但尚未提交 | 同一store事务联动，抛出异常保留待核对状态 |
| `mail_sent` | SMTP确认成功且记录已标记发送后 | 仅通知 |
| `mail_reset_validate` | 核对发送中状态后，重置发送状态前 | 抛出异常可阻止 |
| `mail_reset_persisted` | 发送状态已重置、事务提交前 | 同一store事务联动，抛出异常回滚 |
| `mail_reset` | 发送状态已恢复为未发送 | 仅通知 |
| `operation_failed` | 已授权操作失败后，错误响应生成前 | 仅通知，含operation/error |

## 与旧扩展的关系

旧CMS状态按职责可迁移为以下阶段，不能直接复制旧全局变量代码：

| 旧状态 | 新阶段 |
| --- | --- |
| add / edit | form_create_prepare/render；form_update_prepare/render |
| list | form_list_item（按表单）；form_list_render（默认扩展整页） |
| add_ok / edit_ok | form_create_validate/persisted、form_created；form_update_validate/persisted、form_updated |
| edit_data | entry_update_prepare/render |
| list_data | entry_list_query/item/render；现在明确提供逐条记录阶段 |
| add_data_ok / edit_data_ok | entry_create_input/validate/persisted、entry_created；entry_update_input/validate/persisted、entry_updated |
| confirm | mail_preview_prepare/render |
| mail | mail_send_prepare/validate、mail_sent_persisted、mail_sent |
| export | export_prepare、export_row、export_completed |
| delete | entry_delete_validate/persisted、entry_deleted；或form_delete_validate/persisted、form_deleted |
| registration（按报名职责） | submission_input/validate/persisted、submission_saved；旧源码中该状态仅发现预留分支 |

以上斜线是阶段组合简写，实际case必须使用完整事件清单中的名称。

上一轮cform使用的render_form、render_list、render_mail、before_submit等字面事件名会保留兼容：每个适用阶段只分派旧别名一次，不同时再调用新名；不改写已保存源码。旧render_mail仍可能在预览和发送分别运行，建议迁移到新事件以明确分离副作用。兼容的是上一轮cform事件，不是旧CMS的ext_func函数。

默认示例带`// cform-events:2`，明确采用新事件。旧脚本自动识别常见字面事件名；动态构造名称或逐步迁移的代码，可用`// cform-events:1`明确旧模式，迁移完成再改为2。兼容模式未映射的新阶段不会额外调用旧脚本。

## 扩展修复入口

正常设计器会执行相应扩展。脚本阻断页面时，使用“修复功能扩展”或错误页的“跳过扩展并修复源码”。这个维护通道刻意不执行任何扩展，仍须管理员、CSRF和修订核验，只允许更新扩展源码；其余随表单提交的字段不会被保存。它是修复保障，不是遗漏的业务事件。
