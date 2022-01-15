<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 插件设置 - “<!--name-->”</b>
    </div>
    <div class="card-body p-0 table-responsive">
        <form method="post" class="form-inline" onsubmit="return checkForm(this)">
            <table class="table table-striped table-hover m-0">
                <tr>
                    <td style="line-height:22px;">
                        <!--setting-->
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <input type="hidden" value="<!--idx-->" name="idx" />
                        <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                        <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                        <button class="btn btn-primary btn-sm mr-3" type="button" onClick="history.go(-1)" > 返 回 </button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>