<div class="row mt-3">
    <div class="col-md-6 offset-md-3 col-sm-8 offset-sm-2">
        <div class="card">
            <div class="card-header p-2 bg-info">
                <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-blackboard"></span> <!--lng_page_login--></h5>
            </div>
            <div class="card-body">
                <form method="post" class="p-3" onsubmit="return checkForm(this)">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="glyphicon glyphicon-user"></span> &nbsp; <!--lng_login_username--></div>
                        </div>
                        <input name="username" type="text" class="form-control" placeholder="<!--lng_login_username-->" need="" autofocus />
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="glyphicon glyphicon-asterisk"></span> &nbsp; <!--lng_login_password--></div>
                        </div>
                        <input name="password" type="password" class="form-control" placeholder="<!--lng_login_password-->" need="" />
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="glyphicon glyphicon-calendar"></span> &nbsp; 保存期</div>
                        </div>
                        <select name="expire" class="custom-select">
                            <option value="86400">1天</option>
                            <option value="604800">1周</option>
                            <option value="2592000">1月</option>
                            <option value="31536000">1年</option>
                        </select>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="glyphicon glyphicon-edit"></span> &nbsp; <!--lng_login_captcha--></div>
                        </div>
                        <input name="captcha" type="text" class="form-control" placeholder="<!--lng_login_captcha_msg-->" need="" />
                        <div class="input-group-append">
                            <img id="captcha" src="captcha/&ms_app=CMS&" height="33" keep-url />
                        </div>
                        <div class="input-group-append">
                            <span class="input-group-text"><a href="#" title="<!--lng_login_refresh-->" onclick="document.getElementById('captcha').src+=(new Date().getTime());return false;"><span class="glyphicon glyphicon-refresh"></span></a></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <button class="btn btn-info" type="submit"><!--lng_link_confirm--></button>&emsp;
                        <button class="btn btn-warning" type="reset"><!--lng_link_reset--></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript" src="static/js/checkForm.js"></script>