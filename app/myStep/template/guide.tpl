<div class="mb-5 mb-sm-2">
    <ul id="idx_list" class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link active" href="#all">全部显示</a>
        </li>
    </ul>
    <div id="container" class="p-0"></div>
</div>
<script language="JavaScript">
let detail = <!--detail-->;
$(function(){
    let obj, obj_sub;
    let i,m,x;
    for(i=0,m=detail.length;i<m;i++) {
        $('<li class="nav-item">\n' +
            '  <a class="nav-link" href="#section_'+i+'">'+detail[i].section+'</a>\n' +
            '</li>').appendTo('#idx_list');
        obj = $('<div class="card mb-3" id="section_'+i+'"><div class="card-header bg-secondary text-white"><span class="fa fa-book"></span> '+detail[i].section+'</div></div>');
        if(typeof(detail[i].describe)!=='undefined') {
            obj.append('<div class="card-body bg-light">'+detail[i].describe+'</div>');
        }
        obj_sub = $('<table class="table table-sm table-hover m-0"></table>');
        if(typeof detail[i].detail.length==='undefined') {
            for(x in detail[i].detail) {
                $('<tr class="table-secondary">\n' +
                    '  <td class="pl-4"><span class="fa fa-sticky-note-o"></span> '+x+'</td>\n' +
                    '</tr><tr>\n' +
                    '  <td class="pl-5">'+detail[i].detail[x].replace(/[\r\n]+$/, '')+'</td>\n' +
                    '</tr>').appendTo(obj_sub);
            }
            obj.append(obj_sub);
        }
        $('#container').append(obj);
    }
    $('#idx_list a').click(function(e){
        e.preventDefault();
        let idx = this.href.replace(/^.+#/, '#');
        $('#idx_list a').removeClass('active');
        $(this).addClass('active');
        if(idx=='#all') {
            $('#container').find('div.card').show(200);
        } else {
            $('#container').find('div.card').hide();
            $(idx).show(200);
        }
        resizeMain();
        return false;
    });
})
</script>