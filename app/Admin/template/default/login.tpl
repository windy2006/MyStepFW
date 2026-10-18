<section class="adm-panel">
<h2>欢迎回来</h2>
<p class="adm-error" role="alert"><!--error|html--></p>
<form method="post" action="<!--login_url|html-->">
<input type="hidden" name="_action" value="login">
<label class="adm-field"><span>管理员</span><input name="username" autocomplete="username" need="" required></label>
<label class="adm-field"><span>密码</span><input name="password" type="password" autocomplete="current-password" need="" required></label>
<label class="adm-field"><span>验证码</span><input name="captcha" autocomplete="off" need="" required></label>
<img class="adm-captcha" src="<!--captcha_url|html-->" alt="验证码" title="点击刷新" data-captcha>
<label class="adm-field"><span>记住登录</span><select name="expire"><option value="0">不记住</option><option value="1">一天</option><option value="7">一周</option><option value="30">一个月</option><option value="365">一年</option></select></label>
<button class="adm-button" type="submit">登录</button>
</form>
</section>
