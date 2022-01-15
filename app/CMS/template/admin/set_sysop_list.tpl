<div class="card border-bottom-0 bg-transparent">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加管理员</a>
    </div>
    <div class="form-inline mt-5 pt-3 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select class="input-group-prepend custom-select" name="group_id" onchange="location.href='?group_id='+this.options[this.selectedIndex].value">
                <option value="">所有群组</option>
                <!--loop:start key="sys_group"-->
                <option value="<!--sys_group_group_id-->" <!--sys_group_selected-->><!--sys_group_name--></option>
                <!--loop:end-->
            </select>
            <input type="text" name="keyword" class="form-control" value="<!--keyword-->" placeholder="请输入查询关键字" />
            <div class="input-group-append">
                <button class="btn btn-light btn-outline-secondary" name="keyword" type="button">检索</button>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2 p-0">
        <table class="table table-sm table-striped table-bordered table-hover my-md-3 bg-white">
            <thead class="thead-light">
            <tr class="text-center">
                <th width="40"><a class="text-dark" href="?keyword=<!--keyword-->&group_id=<!--group_id-->&order_type=<!--order_type-->">编号</a></th>
                <th><a class="text-dark" href="?keyword=<!--keyword-->&group_id=<!--group_id-->&order=username&order_type=<!--order_type-->">用户名称</a></th>
                <th><a class="text-dark" href="?keyword=<!--keyword-->&group_id=<!--group_id-->&order=group_id&order_type=<!--order_type-->">用户组</a></th>
                <th><a class="text-dark" href="?keyword=<!--keyword-->&group_id=<!--group_id-->&order=email&order_type=<!--order_type-->">电子邮件</a></th>
                <th width="92">操作</th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="15"-->
            <tr align="center">
                <td><!--record_id--></td>
                <td><!--record_username--></td>
                <td><!--record_group_name--></td>
                <td><a href="mailto:<!--record_email-->"><!--record_email--></a></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" href="edit/<!--record_id-->">修改</a>
                        <a class="btn btn-sm btn-info" href="delete/<!--record_id-->" onclick="return confirm('是否确认删除？')">删除</a>
                    </div>
                </td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
    <nav>
        <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
    </nav>
</div>
<script type="application/javascript">
    $(function(){
        $('select[name=group_id]').change(function(){
            location.href=global.root_fix+'?group_id='+this.options[this.selectedIndex].value;
        });
        $('input[name=keyword]').keypress(function(e){
            if(e.keyCode==13) {
                location.href=global.root_fix+'?group_id=<!--group_id-->&keyword='+this.value;
            }
        });
        $('button[name=keyword]').click(function(e){
            location.href=global.root_fix+'?group_id=<!--group_id-->&keyword='+$('input[name=keyword]').val();
        });
        $('button[name=show_all]').click(function(e){
            location.href = global.root_fix;
        });
        global.root_fix += 'setting/sysop/';
    });
</script>