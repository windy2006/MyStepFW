<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
        <a class="btn btn-primary btn-sm float-right py-0 border" href="add">添加分类</a>
    </div>
    <div class="form-inline mt-5 mx-auto">
        <div class="input-group my-3 mx-auto px-2">
            <select class="custom-select" id="web_id">
                <option value="">显示所有</option>
                <!--loop:start key="website"-->
                <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                <!--loop:end-->
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <form class="col-xs-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 col-xl-6 offset-xl-3 p-0" method="post" action="order" onsubmit="return checkForm(this)">
        <table class="table table-sm table-striped table-bordered table-hover my-md-3 bg-white">
            <thead class="thead-light">
                <tr class="text-center">
                    <th width="40">排序</th>
                    <th>所属网站</th>
                    <th>栏目名称</th>
                    <th width="92">操作</th>
                </tr>
            </thead>
            <tbody id="cat_list">
<!--loop:start key="record" time="10"-->
                <tr title="<!--record_comment-->" data-layer="<!--record_layer-->" data-expand="true" web_id="<!--record_web_id-->">
                    <td>
                        <input name="cat_id[]" type="hidden" value="<!--record_cat_id-->" />
                        <input name="cat_layer[]" type="hidden" value="<!--record_layer-->" />
                        <input class="text-center" name="cat_order[]" type="text" value="<!--record_order-->" size="2" need="digital" />
                    </td>
                    <td><!--record_web_name--></td>
                    <td align="left"><!--record_name--></td>
                    <td>
                        <div class="btn-group">
                            <a class="btn btn-sm btn-info" href="edit/<!--record_cat_id-->">修改</a>
                            <a class="btn btn-sm btn-info" href="delete/<!--record_cat_id-->" onclick="return confirm('该操作将删除当前类别及其子栏目的所有信息！\\\\n\\\\n请按确定继续。')">删除</a>
                        </div>
                    </td>
                </tr>
<!--loop:end-->
            </tbody>
            <tfoot class="position-fixed bg-white border-top w-100" style="right:0;bottom:0">
                <tr class="float-right">
                    <td class="border-0" colspan="4">
                        <button class="btn btn-primary btn-sm mr-3" type="Submit"> 确 认 </button>
                        <button class="btn btn-primary btn-sm" type="reset"> 重 置 </button>
                    </td>
                </tr>
            </tfoot>
        </table>
        </form>
    </div>
</div>
<script type="application/javascript">
$(function(){
    let refer = '<!--back_url-->';
    if(refer.match(/catalog\/(\w+)/) || location.search.indexOf('Refresh')) {
        if(RegExp.$1!=='list' && typeof window.parent.getList !== 'undefined') {
            $.getJSON('<!--url_prefix-->api/CMS/get/news_cat', function(data){
                let web_id = '<!--web_id-->';
                let obj = $(window.parent.document.getElementById('sidebar'));
                if(typeof data.err==='undefined') {
                    obj.empty();
                    obj.append(window.parent.getList(data).removeClass('sub-menu'));
                    setURL(global.root_fix.replace('article/catalog/', ''), obj);
                    window.parent.setLink();
                    obj.find('.collapse').addClass('show');
                    obj.find('.menu-arrow').removeClass('collapsed').attr('aria-expanded', true);
                    window.parent.$('#web_id').val(web_id).trigger('change');
                } else {
                    alert(data.err);
                }
            });
        }
    }
    global.root_fix += 'article/catalog/';
    $('tr[data-layer]').click(function(){
        let obj = $(this);
        let layer = parseInt(obj.attr('data-layer'));
        let expand = (obj.attr('data-expand')==='true');
        let obj_next = obj;
        let flag = false;
        while(true) {
            obj_next = obj_next.next();
            if(obj_next.length===0) break;
            if(obj_next.attr('data-layer') <= layer) break;
            if(expand) {
                obj_next.hide();
            } else {
                obj_next.show();
            }
            obj_next.find('span').remove();
            obj_next.attr('data-expand', 'true');
            flag = true;
        }
        let td = $(obj.find('td')[2]);
        td.find('span').remove();
        if(expand) {
            if(flag) $('<span class="glyphicon glyphicon-plus-sign ml-3"></span>').appendTo(td);
            obj.attr('data-expand', 'false');
        } else {
            obj.attr('data-expand', 'true');
        }

        obj.attr('data-expand', (expand?'false':'true'));
    });
    $('#web_id').change(function(){
        let val = this.value;
        if(val==='') {
            $('#cat_list > tr').show();
        } else {
            $('#cat_list > tr').hide();
            $('#cat_list > tr[web_id='+val+']').show();
        }
    });
});
</script>