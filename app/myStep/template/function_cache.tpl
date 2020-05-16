<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 缓存管理</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
            <table class="table table-sm table-striped table-hover mb-4 border-bottom">
                <tr>
                    <td class="align-middle" width="100"><b>数据缓存：</b></td>
                    <td>数据库查询缓存，默认生效期为10分钟，可随时清除<br /><span class="text-secondary font-sm">（当前容量：<!--data-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="256"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>页面缓存：</b></td>
                    <td>网站动态页面缓存，默认生效期通过程序设置，可随时清除<br /><span class="text-secondary font-sm">（当前容量：<!--data-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="512"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>脚本缓存：</b></td>
                    <td>各应用所用到样式表及JS脚本缓存<br /><span class="text-secondary font-sm">（当前容量：<!--script-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="1"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>模版缓存：</b></td>
                    <td>系统各页面模版的编译文件，清除后相关模版会自动再次编译。<br /><span class="text-secondary font-sm">（当前容量：<!--template-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="2"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>语言缓存：</b></td>
                    <td>供客户端脚本调用的语言包缓存<br /><span class="text-secondary font-sm">（当前容量：<!--language-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="4"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>OP缓存：</b></td>
                    <td>OPcache缓存文件（开启方法请自行检索，开启后框架可自行调用无需额外设置）<br /><span class="text-secondary font-sm">（当前容量：<!--op-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="8"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>网站会话：</b></td>
                    <td>如果session模式为file，就会产生相关缓存。<br /><span class="text-secondary font-sm">（当前容量：<!--session-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="16"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>临时文件：</b></td>
                    <td>网站临时文件存放目录，可随时清除<br /><span class="text-secondary font-sm">（当前容量：<!--tmp-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="32"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>应用缓存：</b></td>
                    <td>应用程序（APP）缓存文件存放目录，可随时清除<br /><span class="text-secondary font-sm">（当前容量：<!--app-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="64"> 清除 </button></td>
                </tr>
                <tr>
                    <td class="align-middle" width="100"><b>设置缓存：</b></td>
                    <td>供客户端脚本调用的设置信息，可随时清除<br /><span class="text-secondary font-sm">（当前容量：<!--setting-->）</span></td>
                    <td class="align-middle" width="65"><button class="btn btn-primary btn-sm mr-3" type="button" value="128"> 清除 </button></td>
                </tr>
            </table>
            <div class="pb-3 border-0 text-right">
                <button class="btn btn-primary btn-sm mr-3" type="button" value="65535"> 清除所有 </button>
            </div>
        </form>
    </div>
</div>
<script type="application/javascript">
$(function(){
    $('button').click(function(){
        loadingShow("缓存清理中，请耐心等待本信息消失！");
        location.href = 'manager/function/cache/?id='+this.value;
    });
});
</script>