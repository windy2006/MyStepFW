<div class="row my-4"><div class="col-lg-8 offset-lg-2"><div class="card">
    <div class="card-header"><h3 class="m-0"><!--title--></h3></div><div class="card-body">
    <!--if:start key="error"--><div class="alert alert-danger" role="alert"><!--error--></div><!--if:end-->
    <!--if:start key="notice"--><div class="alert alert-info" role="status"><!--notice--></div><!--if:end-->
    <!--if:start key="complete"-->
    <p>请补全昵称和邮箱，并完成本站邮件验证。不会仅凭相同邮箱合并已有账号。</p>
    <form method="post" action="<!--complete_url-->" onsubmit="return checkForm(this)">
        <input type="hidden" name="_ms_csrf" value="<!--csrf-->"><input type="hidden" name="social_csrf" value="<!--csrf-->">
        <label for="social-name">昵称</label><input id="social-name" class="form-control mb-3" name="display_name" value="<!--display_name-->" maxlength="60" required <!--readonly-->>
        <label for="social-email">邮箱</label><input id="social-email" class="form-control mb-3" name="email" type="email" value="<!--email-->" maxlength="254" need="email" required <!--readonly-->>
        <button type="submit" class="btn btn-primary">发送验证邮件</button>
    </form>
    <!--if:end-->
    <!--if:start key="wait"--><p>验证邮箱：<!--email--></p><p>邮件短时有效，请在发起登录的同一浏览器中打开。未收到时，可稍后重新进入资料补全页发送。</p><a class="btn btn-outline-primary" href="<!--complete_url-->">返回资料补全</a><!--if:end-->
    <!--if:start key="verify"-->
    <p>确认后将完成本站邮箱验证与第三方身份关联，并登录普通会员。</p>
    <div id="social-mail-status" role="alert"></div>
    <form id="social-mail-confirm" method="post" action="<!--verify_url-->">
        <input type="hidden" name="_ms_csrf" value="<!--csrf-->"><input type="hidden" name="social_csrf" value="<!--csrf-->">
        <input type="hidden" name="pending_id" value=""><input type="hidden" name="mail_token" value=""><button id="social-mail-submit" type="submit" class="btn btn-primary" disabled>确认邮箱并继续</button>
    </form>
    <noscript><p>请启用JavaScript后重新打开邮件链接。</p></noscript><script src="<!--verify_script-->" defer></script>
    <!--if:end-->
    <!--if:start key="connections"-->
    <h4>已关联平台</h4><div class="table-responsive"><table class="table table-striped"><thead><tr><th>平台</th><th>关联时间</th><th>操作</th></tr></thead><tbody>
        <!--loop:start key="bindings"--><tr><td><!--bindings_provider--></td><td><!--bindings_created--></td><td><form method="post" action="<!--bindings_unlink_url-->" class="m-0"><input type="hidden" name="_ms_csrf" value="<!--csrf-->"><input type="hidden" name="social_csrf" value="<!--csrf-->"><input type="hidden" name="identity_key" value="<!--bindings_key-->"><button class="btn btn-sm btn-outline-danger" type="submit">解除关联</button></form></td></tr><!--loop:end-->
    </tbody></table></div><!--bind_buttons-->
    <!--if:end-->
    <!--if:start key="complete"--><form method="post" action="<!--cancel_url-->" class="mt-3"><input type="hidden" name="_ms_csrf" value="<!--csrf-->"><input type="hidden" name="social_csrf" value="<!--csrf-->"><button class="btn btn-outline-secondary" type="submit">取消本次关联</button></form><!--if:end-->
    </div></div></div></div><script src="<!--checkform_script-->"></script>
