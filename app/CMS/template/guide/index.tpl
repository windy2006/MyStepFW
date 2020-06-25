<div id="main" class="border-left">
    <div class="mb-3 h-100">
        <div class="title border-bottom py-3">迈思框架（MyStep Framework）指南</div>
        <div class="w-100 h-100">
            <div id="content" class="py-3 h-100">
                <!--content-->
            </div>
            <div id="side_cat" class="py-4 h-100">
                <div id="cat_list" class="position-sticky">
                    <div class="side-bar">
                        <span class="fa fa-circle start"></span>
                        <span class="fa fa-circle end"></span>
                    </div>
                </div>
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
    for(let i=0,m=anchor_list.length;i<m;i++) {
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

    $('#cat_list a').on('click', function(){
        gotoAnchor($(this).attr('href'));
        return false;
    });
    $("#cat_list a:first").addClass('active');
    $(window).scroll(function(){
        let offset_top = 220;
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