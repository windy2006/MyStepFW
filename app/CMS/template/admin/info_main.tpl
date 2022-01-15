<div class="card">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b>服务器基本信息</b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <table class="table table-striped table-hover m-0">
            <tr>
                <td width="140">服务器语言环境</td>
                <td><?=r::svr("HTTP_ACCEPT_LANGUAGE")?></td>
            </tr>
            <tr>
                <td>服务器域名</td>
                <td><?=r::svr("SERVER_NAME")?></td>
            </tr>
            <tr>
                <td>服务器ip地址</td>
                <td><?=gethostbyname(r::svr("SERVER_NAME"))?></td>
            </tr>
            <tr>
                <td>服务器端口</td>
                <td><?=r::svr("SERVER_PORT")?></td>
            </tr>
            <tr>
                <td>服务器时间</td>
                <td><?=date("Y年m月d日 H:i:s",r::svr('REQUEST_TIME'))?></td>
            </tr>
            <tr>
                <td>服务器系统</td>
                <td><?=PHP_OS?></td>
            </tr>
            <tr>
                <td>服务器解译引擎</td>
                <td><?=r::svr("SERVER_SOFTWARE")?></td>
            </tr>
            <tr>
                <td>服务端通信协议</td>
                <td><?=r::svr("SERVER_PROTOCOL")?></td>
            </tr>
            <tr>
                <td>服务端剩余空间</td>
                <td><?=f::formatSize(disk_free_space("."))?></td>
            </tr>
            <tr>
                <td>系统当前用户名</td>
                <td><?=get_current_user()?></td>
            </tr>
            <tr>
                <td>网站存放路径</td>
                <td><?=ROOT?></td>
            </tr>
        </table>
    </div>
</div>