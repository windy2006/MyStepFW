$.vendor('highlight',{callback:()=>highlight(1, 'vs2015')});
$(function(){
    let anchor_list = $("#content a[id^=p]");
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
        let offset_top = 300;
        let the_top = $("html").scrollTop();
        $("#cat_list a").removeClass('active');
        for(let i=anchor_list.length-1;i>=0;i--) {
            if(the_top+offset_top>=$(anchor_list.get(i)).offset().top) {
                $("#cat_list a[href='#"+anchor_list.get(i).id+"']").addClass('active');
                break;
            }
        }
    });
});
$('html').bind('keydown',function(e){
    let obj = null;
    if(e.altKey && e.keyCode===39) { // ALT + right
        obj = $('#article_next');
        if(obj.length>0) {
            location.href = obj.attr('href');
        }
    } else if(e.altKey && e.keyCode===37) { // ALT + left
        obj = $('#article_prev');
        if(obj.length>0) {
            location.href = obj.attr('href');
        }
    } else if(e.altKey && e.keyCode===38) { // ALT + up
        gotoAnchor();
    } else if(e.altKey && e.keyCode===40) { // ALT + down
        $('html').animate({scrollTop: document.body.scrollHeight}, 1000);
    } else if (e.keyCode===38) { // up
        $('html').animate({scrollTop: "-="+$(window).height()/1.5}, 1000);
    } else if (e.keyCode===40) { // down
        $('html').animate({scrollTop: "+="+$(window).height()/1.5}, 1000);
    }
});

if(global.mobile) {
    let touch_start, touch_end;
    let obj = null;
    document.addEventListener('touchstart', function (e) {
        touch_start = e.touches[0].pageX;
    });
    document.addEventListener('touchmove', function (e) {
        touch_end = e.touches[0].pageX;
        if(touch_start - touch_end > 20){
            obj = $('#article_next');
            if(obj.length>0) {
                location.href = obj.attr('href');
            }
        }else if(touch_start - touch_end < -20){
            obj = $('#article_prev');
            if(obj.length>0) {
                location.href = obj.attr('href');
            }
        }
    });
}