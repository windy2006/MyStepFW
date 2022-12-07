<style>
body {background-image: url('/static/images/background.jpg');}
</style>
<div class="p-5 text-center">
    <img src="/static/images/logo.png" keep-url />
</div>
<div class="container">
    <div class="row">&nbsp;</div>
    <div class="row">
        <div class="col-md-6 offset-md-3 col-sm-8 offset-sm-2">
            <div class="card mb-3">
                <div class="card-header p-2 bg-info">
                    <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-blackboard"></span> <!--lng_page_login--></h5>
                </div>
                <div class="card-body">
                    <form action="<!--path_admin-->login" method="post" onsubmit="return checkForm(this)">
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-user"></span> &nbsp; <!--lng_login_username--></div>
                            </div>
                            <input name="username" type="text" class="form-control" placeholder="<!--lng_login_username-->" need="" />
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-asterisk"></span> &nbsp; <!--lng_login_password--></div>
                            </div>
                            <input name="password" type="password" class="form-control" placeholder="<!--lng_login_password-->" need="" />
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-edit"></span> &nbsp; <!--lng_login_captcha--></div>
                            </div>
                            <input name="captcha" type="text" class="form-control" placeholder="<!--lng_login_captcha_msg-->" need="" />
                            <div class="input-group-append">
                                <img id="captcha" src="captcha/&core=CMS&" height="33" keep-url />
                            </div>
                            <div class="input-group-append">
                                <span class="input-group-text"><a href="#" id="refresh" title="<!--lng_login_refresh-->"><span class="glyphicon glyphicon-refresh"></span></a></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <button class="btn btn-info" type="submit"><!--lng_link_confirm--></button>&emsp;
                            <button class="btn btn-info" type="reset"><!--lng_link_reset--></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript" src="/static/js/checkForm.js"></script>
<script type="application/javascript">
if(self!=top) top.location.href = location.href;
$('#refresh').click(function(){
    document.getElementById('captcha').src+=(new Date().getTime());
    return false;
});
$(function(){
    $('#refresh').click();
});
</script>