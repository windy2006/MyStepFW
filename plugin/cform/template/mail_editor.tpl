<section>
    <h2>确认邮件预览与发送</h2>
    <p>
        <a href="<!--cn_url|html-->">中文邮件</a>
        ·
        <a href="<!--en_url|html-->">English email</a>
        ·
        <a href="<!--back_url|html-->">返回记录</a>
    </p>
    <form method="post" action="<!--action|html-->">
        <!--csrf-->
        <input type="hidden" name="action" value="mail" />
        <input type="hidden" name="id" value="<!--id|html-->" />
        <input type="hidden" name="mail_language" value="<!--language|html-->" />
        <label>
            收件邮箱
            <input
                class="form-control"
                type="email"
                name="mail_to"
                value="<!--mail_to|html-->"
                required
            />
        </label>
        <label>
            邮件标题（活动名称）
            <input
                class="form-control"
                value="<!--subject|html-->"
                readonly
            />
        </label>
        <label>
            HTML正文（修改只用于本次邮件）
            <textarea
                class="form-control"
                name="mail_html"
                rows="16"
                maxlength="100000"
                spellcheck="false"
            ><!--html|html--></textarea>
        </label>
        <p>以下预览对应已生成草稿；修改后以正文编辑框中的内容发送。</p>
        <iframe
            title="邮件草稿预览"
            sandbox=""
            referrerpolicy="no-referrer"
            srcdoc="<!--html|html-->"
            style="width:100%;height:320px;border:1px solid #ddd"
        ></iframe>
        <button type="submit" class="btn btn-primary">发送确认邮件</button>
    </form>
</section>
