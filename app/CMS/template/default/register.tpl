<div class="row mt-3 mb-5">
    <div class="col-md-6 offset-md-3 col-sm-8 offset-sm-2">
        <div class="card">
            <div class="card-header p-2 bg-info">
                <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-info-sign"></span> 请填写以下信息：</h5>
            </div>
            <div class="card-body">
                <form method="post" class="p-3" onsubmit="return checkForm(this)">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">登录名称</span>
                        </div>
                        <input type="text" name="username" class="form-control" placeholder="用户名" required autofocus>
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">登录密码</span>
                        </div>
                        <input type="password" name="password" id="pwd" class="form-control" placeholder="密码" requireds>
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">确认密码</span>
                        </div>
                        <input type="password" name="password_r" id="pwd_r" class="form-control" placeholder="确认密码" required>
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">电子邮件</span>
                        </div>
                        <input type="email" name="email" class="form-control" placeholder="电子邮件" need="email" required>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text">&nbsp; <!--lng_login_captcha--> &nbsp;</div>
                        </div>
                        <input name="captcha" type="text" class="form-control" placeholder="<!--lng_login_captcha_msg-->" need="" />
                        <div class="input-group-append">
                            <img id="captcha" src="captcha/&ms_app=cms&" height="33" keep-url />
                        </div>
                        <div class="input-group-append">
                            <span class="input-group-text"><a href="#" title="<!--lng_login_refresh-->" onclick="document.getElementById('captcha').src+=(new Date().getTime());return false;"><span class="glyphicon glyphicon-refresh"></span></a></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <button class="btn btn-info" type="submit"> 注 册 </button>&emsp;
                        <button class="btn btn-warning" type="reset"> 清 空 </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript" src="static/js/checkForm.js"></script>