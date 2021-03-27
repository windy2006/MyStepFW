<div class="card border-bottom-0 bg-transparent pt-3">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add?cat_id=<!--cat_id-->&web_id=<!--web_id-->">添加文章</a>
    </div>
    <div class="form-inline mt-5 mx-auto">
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select class="custom-select" id="web_id">
                <!--loop:start key="website"-->
                <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                <!--loop:end-->
            </select>
        </div>
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <select class="form-control" id="catalog">
                <option value="">全部文章</option>
                <!--loop:start key="catalog"-->
                <option value="<!--catalog_cat_id-->" <!--catalog_selected-->><!--catalog_name--></option>
                <!--loop:end-->
            </select>
        </div>
        <div class="input-group mb-2 mb-md-0 mx-auto px-2">
            <input type="text" name="keyword" class="form-control" value="<!--keyword-->" placeholder="请输入查询关键字" />
            <div class="input-group-append">
                <button class="btn btn-light btn-outline-secondary" name="keyword" type="button">检索</button>
                <button class="btn btn-light btn-outline-secondary" name="show_all" type="button">重置</button>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-bordered table-hover font-sm my-md-3 bg-white">
            <thead class="thead-light">
            <tr class="text-center">
                <th width="40"><a class="text-dark" href="?keyword=<!--keyword-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->&order_type=<!--order_type-->">编号</a></th>
                <th class="d-none d-sm-table-cell" width="100"><a class="text-dark" href="?keyword=<!--keyword-->&order=cat_id&order_type=<!--order_type-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->">栏目</a></th>
                <th><a class="text-dark" href="?keyword=<!--keyword-->&order=subject&order_type=<!--order_type-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->">文章标题</a></th>
                <th class="d-none d-md-table-cell" width="80"><a class="text-dark" href="?keyword=<!--keyword-->&order=add_user&order_type=<!--order_type-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->">录入人</a></th>
                <th class="d-none d-md-table-cell" width="160"><a class="text-dark" href="?keyword=<!--keyword-->&order=add_date&order_type=<!--order_type-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->">录入时间</a></th>
                <th width="150"><a class="text-dark" href="?keyword=<!--keyword-->&order=setop&order_type=<!--order_type-->&cat_id=<!--cat_id-->&web_id=<!--web_id-->">相关操作</a></th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="20"-->
            <tr align="center">
                <td><!--record_news_id--></td>
                <td class="d-none d-sm-table-cell"><a class="badge badge-warning" href="?cat_id=<!--record_cat_id-->&web_id=<!--record_web_id-->" /><!--record_cat_name--></a></td>
                <td align="left"><a class="text-dark" href="<!--record_link-->" target="_blank"><!--record_subject--></a></td>
                <td class="d-none d-md-table-cell"><!--record_add_user--></td>
                <td class="d-none d-md-table-cell"><!--record_add_date--></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-info" href="?method=unlock&web_id=<!--record_web_id-->&id=<!--record_news_id-->">解锁</a>
                        <a class="btn btn-sm btn-info" href="?method=edit&web_id=<!--record_web_id-->&id=<!--record_news_id-->">修改</a>
                        <a class="btn btn-sm btn-info" href="?method=delete&web_id=<!--record_web_id-->&id=<!--record_news_id-->" onclick="return confirm('是否确定要删除该文章？')">删除</a>
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
    let web_id = '<!--web_id_site-->';
    if(web_id !== '1') {
        $('#web_id').val(web_id).parent().hide();
    }
    $('#web_id').change(function(){
        location.href=global.root_fix+'?web_id='+this.value;
    });
    $('#catalog').change(function(){
        location.href=global.root_fix+'?cat_id='+this.value;
    });
    $('input[name=keyword]').keypress(function(e){
        if(e.keyCode==13) {
            location.href=global.root_fix+'?web_id=<!--web_id-->&keyword='+this.value;
        }
    });
    $('button[name=keyword]').click(function(e){
        location.href=global.root_fix+'?web_id=<!--web_id-->&keyword='+$('input[name=keyword]').val();
    });
    $('button[name=show_all]').click(function(e){
        location.href = global.root_fix;
    });
    if(typeof window.parent.getList !== 'undefined') {
        $.getJSON('<!--url_prefix-->api/CMS/get/news_cat', function(data){
            let obj = $(window.parent.document.getElementById('sidebar'));
            if(typeof data.err==='undefined') {
                obj.empty();
                obj.append(window.parent.getList(data).removeClass('sub-menu'));
                setURL(global.root_fix.replace('article/catalog/', ''), obj);
                window.parent.setLink();
                obj.find('.collapse').addClass('show');
                obj.find('.menu-arrow').removeClass('collapsed').attr('aria-expanded', true);
                if(web_id!=='1') window.parent.$('#web_id').val(web_id).trigger('change');
            } else {
                alert(data.err);
            }
        });
    }
    global.root_fix += 'article/content/';
});
</script>