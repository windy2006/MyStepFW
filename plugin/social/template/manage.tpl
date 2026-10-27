<div class="card"><div class="card-header">普通会员第三方登录</div><div class="card-body">
    <p>会员应用：<!--app-->　运行状态：<!--enabled--></p><p>规范入口：<code><!--endpoint--></code></p>
    <p>请在插件设置中填写应用ID/密钥及微信场景，并在各平台后台登记以下精确回调地址；邮件沿用会员应用的SMTP设置。</p>
    <table class="table table-striped"><thead><tr><th>平台</th><th>回调地址</th></tr></thead><tbody><!--loop:start key="providers"--><tr><td><!--providers_name--></td><td><code><!--providers_callback--></code></td></tr><!--loop:end--></tbody></table>
    <p>首次登录必须补全昵称和邮箱并完成本站邮箱验证；同邮箱已有账号须先登录原账号再绑定。平台身份不会获得管理员权限。</p>
    <p>安装会为会员表补充字段、扩展邮箱长度，并将旧MyISAM会员表转换为InnoDB；请先备份数据库。卸载保留账号和身份映射，重新安装后可恢复使用。</p>
</div></div>
