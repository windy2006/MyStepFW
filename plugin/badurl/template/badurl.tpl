<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-list-alt"></span> 恶意请求查看</b>
    </div>
    <div class="form-inline pt-3 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            【<a href="badurl?m=reset">重置数据</a>】
        </div>
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select class="custom-select" id="mode">
                <option value="">详细列表</option>
                <option value="ip">按IP汇总</option>
                <option value="url">按网址汇总</option>
            </select>
        </div>
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <input type="text" name="keyword" class="input-group-prepend" value="<!--keyword-->" placeholder="请输入查询关键字" />
            <div class="input-group-append">
                <button class="btn btn-light btn-outline-secondary" name="keyword" type="button">检索</button>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered border-0 table-hover my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th width="120"><a class="text-dark" href="badurl?mode=<!--mode-->&keyword=<!--keyword-->&order=ip&&order_type=<!--order_type-->">IP</a></th>
                <th width="100"><a class="text-dark" href="badurl?mode=<!--mode-->&keyword=<!--keyword-->&order=url&&order_type=<!--order_type-->">调用脚本</a></th>
                <th width="100"><a class="text-dark" href="badurl?mode=<!--mode-->&keyword=<!--keyword-->&order=cnt&&order_type=<!--order_type-->">出现次数</a></th>
                <th width="160"><a class="text-dark" href="badurl?mode=<!--mode-->&keyword=<!--keyword-->&order=req&order_type=<!--order_type-->">调用时间</a></th>
                <th width="80">操作</th>
            </tr>
            </thead>
            <tbody>
<!--loop:start key="record" time="20"-->
            <tr class="no-wrap" title="<!--record_ua-->">
                <td><a type="ip" href="http://ip-api.com/json/<!--record_ip-->?lang=zh-CN" target="_blank"><!--record_ip--></a></td>
                <td><a href="https://www.baidu.com/s?wd=<!--record_url-->" title="<!--record_qry-->" target="_blank"><!--record_url2--></a></td>
                <td align="center"><!--record_cnt--></a></td>
                <td><!--record_req--></td>
                <td align="center">
                    <a href="###" type="<!--record_mode-->" data-ip="<!--record_ip-->">屏蔽</a>
                    <a href="badurl?m=del&ip=<!--record_ip-->" onclick="return confirm('是否确认删除此 IP 所有记录？')">删除</a>
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
<script type="text/javascript">
$(function(){
    $('#mode').val('<!--mode-->').change(function(){
        location.href = "badurl?mode="+this.value+"&keyword=<!--keyword-->";
    });
    $('input[name=keyword]').keypress(function(e){
        if(e.keyCode===13) {
            location.href=global.root_fix+'?mode=<!--mode-->&keyword='+this.value;
        }
    });
    $('button[name=keyword]').click(function(e){
        location.href=global.root_fix+'?keyword='+$('input[name=keyword]').val();
    });
    $.ajaxSetup({
        crossDomain: false,
        xhrFields: {
            withCredentials: false
        }
    });
    $("a[type=ip]").each(function(){
        if(this.innerHTML.indexOf(',')>0) {
            let ips = this.innerHTML.split(/[, ]+/);
            this.outerHTML = '<a type="ip" href="http://ip-api.com/json/'+ips[0]+'?lang=zh-CN" target="_blank">'+ips[0]+'</a>, <a type="ip" href="http://ip-api.com/json/'+ips[1]+'?lang=zh-CN" target="_blank">'+ips[1]+'</a>';
        }
    });
    $("a[type=ip]").click(function() {
        let obj = $(this);
        let result = obj.attr('info');
        if(result==undefined) {
            $.get(this.href, function(data){
                let result = "";
                for(let x in data) {
                    if(data[x]=='') continue;
                    result += x + " : " + data[x] + "\n";
                }
                obj.attr('info', result);
                alert(result);
            }, 'json');
        } else {
            alert(result);
        }
        return false;
    });
    $("a[type=ban]").click(function(){
        let ip = $(this).attr('data-ip');
        let msg = prompt('请输入反馈给被屏蔽者的内容（网址或信息）：', '<!--msg-->');
        if(msg!==null) {
            location.href = 'badurl?m=ban&ip='+ip+'&msg='+msg;
        }
        return false;
    });
    $("a[type=unban]").html('解除').click(function(){
        let ip = $(this).attr('data-ip');
        location.href = 'badurl?m=unban&ip='+ip;
        return false;
    });
});
</script>