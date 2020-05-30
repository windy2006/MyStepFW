<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-wrenchn"></span> 类方法查看及别名设置</b>
    </div>
    <div class="card-body p-0">
        <form method="post">
            <div class="card border-0">
                <div class="card-header">
                    <h2>Class: <!--name--> <a href="Document/<!--name-->" target="_blank" style="display:<!--display-->">【查看实例】</a></h2>
                </div>

                <table class="table table-sm table-striped table-hover mb-2 border-bottom">
                    <tr class="bg-info text-center font-weight-bold">
                        <td width="120">方法名</td>
                        <td>调用别名</td>
                        <td width="120">方法名</td>
                        <td>调用别名</td>
                    </tr>
                    <tr data-toggle="tooltip" data-placement="top" title="可在代码中直接通过别名调用对应方法，本方法需要通过mystep类声明实例才能自动生效，否则请通过regAlias方法注册别名（框架类均可），如改变可能会导致已有程序无法正常执行，请谨慎！">
                        <!--loop:start key="method"-->
                        <td class="text-right align-middle"><!--method_name--></td>
                        <td><input type="text" class="form-control" name="<!--method_name-->" value="<!--method_alias-->" /></td>
                        <!--method_tr-->
                        <!--loop:end-->
                        <!--dummy-->
                    </tr>
                </table>

                <div class="p-3 border-0 text-right">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="reset"> 复 位 </button>
                    <button class="btn btn-primary btn-sm" type="reset" onclick="history.go(-1)"> 返 回 </button>
                </div>

                <div class="card-body border-top">
                    <pre class="card-text"><!--doc--></pre>
                </div>
            </div>

            <table class="table table-striped border-0">
                <tbody class="border-bottom">
                <!--loop:start key="item"-->
                <tr>
                    <th width="20"><!--item_no--></th>
                    <td class="pb-0"><b><!--item_name--></b><pre><!--item_doc--></pre></td>
                </tr>
                <!--loop:end-->
                </tbody>
            </table>
        </form>
    </div>
</div>