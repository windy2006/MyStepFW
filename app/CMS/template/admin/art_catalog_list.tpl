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
    let web_id = '<!--web_id-->';
    if(web_id !== '1') {
        $('#web_id').val(web_id).parent().hide();
    }
    // 只有成功变更后携带明确标记才刷新左栏，普通打开不重建栏目树。
    if (new URLSearchParams(location.search).get('Refresh') === '1') {
        if (window.parent !== window && typeof window.parent.refreshSidebar === 'function') window.parent.refreshSidebar();
        history.replaceState(history.state, '', queryURL(location.href, {Refresh:null}));
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
    /** 过滤当前列表并将筛选带入编辑/排序及返回地址，不改变外层网站选择。
     * 参数说明：无显式参数；读取当前页面的web_id选择框。
     * @returns {void} 保留其他查询参数、路由首段和历史项附加状态。
     */
    function applyCatalogFilter() {
        const value = String($('#web_id').val() || '');
        $('#cat_list > tr').each(function () { $(this).toggle(value === '' || this.getAttribute('web_id') === value); });
        $('.card a[href]').each(function () { $(this).attr('href', queryURL(this.getAttribute('href'), {web_id:value})); });
        $('form[action]').each(function () { $(this).attr('action', queryURL(this.getAttribute('action'), {web_id:value})); });
        history.replaceState(history.state, '', queryURL(location.href, {web_id:value}));
    }
    // 未指定筛选时沿用左栏网站；显式“显示所有”用web_id=保留，不能当作缺省。
    if (web_id === '1' && !new URLSearchParams(location.search).has('web_id') && window.parent !== window) {
        try {
            const selector = window.parent.document.getElementById('web_id');
            if (selector && $('#web_id option').filter(function () { return this.value === selector.value; }).length) $('#web_id').val(selector.value);
        } catch (error) { /* 单独嵌入跨域页面时使用当前筛选，不读取外层窗口。 */ }
    }
    $('#web_id').on('change', applyCatalogFilter);
    applyCatalogFilter();
});
</script>