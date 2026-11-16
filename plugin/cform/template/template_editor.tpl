<details
    class="cf-section cf-more"
    id="cf-template-settings"
    data-cf-editor-base="<!--editor_base|html-->"
>
    <summary>页面模板与功能扩展</summary>
    <p>
        编辑框直接显示当前生效源码。默认内容或空白提交均继承统一默认；修改为不同内容后只覆盖当前表单。点击“保存表单”后生效。
    </p>
    <label for="cf-template-select">
        选择页面或功能
        <select class="form-control" id="cf-template-select">
            <!--options-->
        </select>
    </label>
    <p>
        普通数据请使用
        <code>&lt;!--record_name|html--&gt;</code>
        等转义标签；生成的控件、隐藏字段和子块按示例直接插入。支持框架循环与受信 PHP；PHP模板通过
        <code>$cf</code>
        读取上下文。
    </p>
    <p id="cf-editor-status" role="status">
        展开后加载本地代码编辑器，支持语法高亮、行号和括号匹配。
    </p>
    <p>
        填写说明直接写入中英文报名模板；中英文确认邮件模板用于发送给用户的邮件正文，
        邮件标题固定为对应语言的活动名称。
    </p>
    <!--panels-->
    <p>
        填写页须保留
        action、hidden（含安全令牌）和需要提交的字段控件；列表只提供允许公开的字段。功能扩展按当前表单的操作阶段运行；错误脚本阻断页面时，请使用“修复功能扩展”入口。
    </p>
</details>
