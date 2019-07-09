<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 插件管理</b>
    </div>
    <div class="card-body p-0">
        <h3 class="text-center my-3">已安装插件</h3>
        <form method="post" class="form-inline" onsubmit="return checkForm(this)">
            <table class="table table-sm table-striped table-hover mb-4 border-bottom">
                <tr align="center" class="bg-secondary text-white">
                    <td width="40">排序</td>
                    <td>名称</td>
                    <td width="40">版本</td>
                    <td>描述</td>
                    <td width="160">操作</td>
                </tr>
<!--loop:start key="list_1"-->
                <tr align="center">
                    <td>
                        <input type="hidden" name="idx[]" value="<!--list_1_idx-->" />
                        <input class="py-0 px-2" type="text" name="order[]" value="<!--list_1_order-->" size="1" need="number" />
                    </td>
                    <td><!--list_1_name--></td>
                    <td><!--list_1_ver--></td>
                    <td><!--list_1_intro--></td>
                    <td class="btn-group">
                        <a class="btn btn-sm btn-info" href="manager/function/plugin/uninstall/?idx=<!--list_1_idx-->" onclick="return confirm('是否确认删除该插件？ 请按确定继续。')">卸载</a>
                        <a class="btn btn-sm btn-info" href="manager/function/plugin/active/?idx=<!--list_1_idx-->"><!--list_1_active--></a>
                        <a class="btn btn-sm btn-info" href="manager/function/plugin/setting/?idx=<!--list_1_idx-->">设置</a>
                        <a class="btn btn-sm btn-info" href="manager/function/plugin/pack/?idx=<!--list_1_idx-->">打包</a>
                    </td>
                </tr>
<!--loop:end-->
<!--if:start condition="judge" key="empty_1"-->
                <tr align="center">
                    <td colspan="5" class="p-3 font-md text-center">尚未安装任何插件</td>
                </tr>
<!--else-->
                <tr>
                    <td colspan="5" class="text-center py-3">
                        <button class="btn btn-primary btn-sm mr-3" type="submit"> 排 序 </button>
                        <button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
                    </td>
                </tr>
 <!--if:end-->
            </table>
        </form>
        <h3 class="text-center my-3">未安装插件</h3>
        <table class="table table-sm table-striped table-hover mb-4 border-bottom">
            <tr align="center" class="bg-secondary text-white">
                <td>名称</td>
                <td width="40">版本</td>
                <td>描述</td>
                <td width="140">操作</td>
            </tr>
<!--loop:start key="list_2"-->
            <tr align="center">
                <td><!--list_2_name--></td>
                <td><!--list_2_ver--></td>
                <td><!--list_2_intro--></td>
                <td align="center">
                    <a href="manager/function/plugin/view/?idx=<!--list_2_idx-->">安装</a>
                    <a href="manager/function/plugin/uninstall/?idx=<!--list_2_idx-->" onclick="return confirm('本功能主要用于清除安装未成功的插件残留，可能会造成一些错误记录！ 请按确定继续。')">清除</a>
                    <a href="manager/function/plugin/delete/?idx=<!--list_2_idx-->" onclick="return confirm('是否确认删除当前插件？')">删除</a>
                    <a href="manager/function/plugin/pack/?idx=<!--list_2_idx-->">打包</a>
                </td>
            </tr>
<!--loop:end-->
<!--if:start condition="judge" key="empty_2"-->
            <tr align="center">
                <td colspan="4" class="p-3 font-md text-center">未发现可安装的插件</td>
            </tr>
<!--if:end-->
            <tr align="center">
                <td colspan="4" class="p-3 font-md text-center">
                    <button class="btn btn-primary btn-sm mr-3" type="button" data-toggle="modal" data-target="#upload"> 上传插件 </button>
                </td>
            </tr>
            </tr>
        </table>
    </div>
</div>

<div class="modal fade" id="upload" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form name="upload" method="post" ACTION="manager/function/plugin/upload" ENCTYPE="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title"><span class="glyphicon glyphicon-upload"></span> 请选择需要上传的插件文件</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">插件</span>
                    </div>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" name="plugin" id="customFile" required />
                        <label class="custom-file-label" for="customFile">文件选取</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="MAX_FILE_SIZE" value="<!--max_size-->" />
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>  关闭 </button>
                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-open"></span>  上传 </button>
            </div>
            </form>
        </div>
    </div>
</div>