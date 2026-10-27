<div class="my-3 border-top pt-3">
    <p class="text-muted">第三方会员登录 / 账号关联</p>
    <div class="d-flex flex-wrap">
        <!--loop:start key="providers"-->
        <form method="post" action="<!--providers_url-->" class="mr-2 mb-2">
            <input type="hidden" name="_ms_csrf" value="<!--csrf-->"><input type="hidden" name="social_csrf" value="<!--csrf-->">
            <input type="hidden" name="intent" value="<!--intent-->"><button type="submit" class="btn btn-outline-primary"><!--providers_label--></button>
        </form>
        <!--loop:end-->
    </div>
    <!--if:start key="bind"--><a href="<!--connections_url-->">管理已关联的平台</a><!--if:end-->
</div>
