<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <table class="table table-sm table-striped table-bordered border-0 table-hover my-md-3 bg-white
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
        <!--pages query='$query' count='$count' page='$page' size='$page_size'-->
    </nav>
</div>
<script type="application/javascript">
    $(function(){
        global.root_fix += 'info/count/';
    });
</script>