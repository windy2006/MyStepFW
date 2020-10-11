<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <table class="table table-sm table-striped table-bordered border-0 table-hover font-sm my-md-3 bg-white
                        col-xs-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <thead class="thead-light">
            <tr class="text-center no-wrap">
                <th><a class="text-dark" href="?order=date&order_type=<!--order_type-->">统计日期</a></th>
                <th><a class="text-dark" href="?order=pv&order_type=<!--order_type-->">访问页数</a></th>
                <th><a class="text-dark" href="?order=iv&order_type=<!--order_type-->">访问人数</a></th>
                <th><a class="text-dark" href="?order=online&order_type=<!--order_type-->">最大在线人数</a></th>
            </tr>
            </thead>
            <tbody>
            <!--loop:start key="record" time="20"-->
            <tr class="text-center no-wrap">
                <td><!--record_date--></td>
                <td><!--record_pv--></a></td>
                <td><!--record_iv--></td>
                <td><!--record_online--></td>
            </tr>
            <!--loop:end-->
            </tbody>
        </table>
    </div>
    <nav>
        <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item"><a class="page-link" href="<!--link_first-->">首页</a></li>
            <li class="page-item"><a class="page-link" href="<!--link_prev-->">上页</a></li>
            <li class="page-item"><a class="page-link" href="<!--link_next-->">下页</a></li>
            <li class="page-item"><a class="page-link" href="<!--link_last-->">末页</a></li>
            <li>
                <div class="input-group input-group-sm mb-2 mr-sm-2">
                    <input type="text" class="form-control" name="jump" size="2" value="<!--page_current-->" style="text-align:center" />
                    <div class="input-group-append">
                        <button class="btn btn-light btn-outline-secondary" name="jump" type="button">跳页</button>
                    </div>
                </div>
            </li>
            <li class="pl-4 pt-1"> 【 共 <!--page_count--> 页，  <!--record_count--> 条记录 】</li>
        </ul>
    </nav>
</div>
<script type="application/javascript">
    $(function(){
        $('input[name=jump]').keypress(function(e){
            if(e.keyCode==13) {
                location.href=global.root_fix+'?order=<!--order-->&order_type=<!--order_type_org-->&page='+this.value;
            }
        });
        $('button[name=jump]').click(function(e){
            location.href=global.root_fix+'?order=<!--order-->&order_type=<!--order_type_org-->&page='+$('input[name=jump]').val();
        });
        global.root_fix += 'info/count/';
    });
</script>