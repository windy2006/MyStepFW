<div id="main" class="border-left">
    <div class="mb-3 h-75">
        <div class="title"><!--record_subject--></div>
        <nav aria-label="breadcrumb" class="w-100">
            <ol class="breadcrumb">
                <!--loop:start key="cat_list"-->
                <li class="breadcrumb-item" aria-current="page"><!--cat_list_name--></li>
                <!--loop:end-->
            </ol>
        </nav>
        <div class="w-100 h-100">
            <div id="content" class="py-3">
                <div class="mb-3 font-weight-bold font-md <!--multi_page-->">
                    文章分页：
                    <select onchange="location.href=this.value">
                        <!--loop:start key="sub_title"-->
                        <option value="<!--sub_title_link-->" <!--sub_title_selected-->><!--sub_title_name--></option>
                        <!--loop:end-->
                    </select>
                </div>
                <div>
                    <!--record_content-->
                </div>
            </div>
            <div id="side_cat" class="pt-4">
                <div id="cat_list" class="position-sticky">
                    <div class="side-bar">
                        <span class="fa fa-circle start"></span>
                        <span class="fa fa-circle end"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <nav class="w-100 mb-3 mx-auto <!--multi_page-->">
            <ul class="pagination pagination-sm justify-content-center">
                <!--loop:start key="page"-->
                <li class="page-item <!--page_active-->"><a class="page-link" href="<!--page_link-->"><!--page_no--></a></li>
                <!--loop:end-->
            </ul>
        </nav>
        <div class="row">
            <div class="col">
                上一篇：<!--news_next id='$id' mode='prev'-->
            </div>
            <div class="col text-right">
                下一篇：<!--news_next id='$id'-->
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$.vendor('highlight',{callback:()=>highlight(1, 'vs2015')});
$(function(){
    let anchor_list = $("a[id^=p]");
    let cat_html = '<ul>';
    let cur_lvl = 1;
    let m = anchor_list.length;
    if(m===0) {
        $('#side_cat').hide();
        $('#content').css('width', '100%');
        return;
    }
    for(let i=0;i<m;i++) {
        let obj = $(anchor_list.get(i));
        let cur_idx = obj.attr('id').replace('p', '').split('.');
        obj.addClass('anchor').attr('href', '#'+obj.attr('id'));
        obj.parent().addClass('sub_title').addClass('level_'+cur_idx.length);
        if(cur_idx.length > cur_lvl) {
            cat_html += '<ul>';
        } else if(cur_idx.length < cur_lvl) {
            cat_html += '</li></ul></li>';
        } else {
            if(i>0) cat_html += '</li>';
        }
        cur_lvl = cur_idx.length;
        cat_html += '<li><a href="#'+obj.attr('id')+'">'+('&emsp;').repeat(cur_lvl)+cur_idx.join('.')+'&emsp;'+obj.parent().text()+'</a>';
    }
    cat_html += ('</li></ul>').repeat(cur_lvl-1);
    cat_html += '</li></ul>';
    $('#cat_list').html($('#cat_list').html()+cat_html);
    $("#cat_list a:first").addClass('active');
    $(window).scroll(function(){
        let offset_top = 200;
        let the_top = $("html").scrollTop();
        $("#cat_list a").removeClass('active');
        for(let i=anchor_list.length-1;i>=0;i--) {
            if(the_top+offset_top>=$(anchor_list.get(i)).offset().top) {
                $("#cat_list a[href='#"+anchor_list.get(i).id+"']").addClass('active');
                break;
            }
        }
    });
})
</script>