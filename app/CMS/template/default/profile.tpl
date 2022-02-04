<div class="row mt-3 mb-5">
    <div class="col-md-6 offset-md-3 col-sm-8 offset-sm-2">
        <div class="card">
            <div class="card-header p-2 bg-info">
                <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-info-sign"></span> 当前用户信息：</h5>
            </div>
            <div class="card-body">
                <form method="post" class="p-3" onsubmit="return checkForm(this)">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">登录名称</span>
                        </div>
                        <input type="hidden" value="<!--user_id-->" name="user_id">
                        <input type="text" value="<!--username-->" name="username" class="form-control" placeholder="用户名" readonly required autofocus>
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">登录密码</span>
                        </div>
                        <input type="password" name="password" id="pwd" class="form-control" placeholder="不修改请留空">
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">密码确认</span>
                        </div>
                        <input type="password" name="password" id="pwd_r" class="form-control" placeholder="如有修改请与前项保持一致">
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">电子邮件</span>
                        </div>
                        <input type="email" value="<!--email-->"  name="email" class="form-control" placeholder="电子邮件" need="email" required>
                    </div>
                    <div class="text-right">
                        <button class="btn btn-info" type="submit"> 修 改 </button>&emsp;
                        <button class="btn btn-warning" type="reset"> 清 空 </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript" src="/static/js/checkForm.js"></script>