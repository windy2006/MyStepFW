<ul class="pagination pagination-sm justify-content-center">
    <!--if:start-->
    <li class="page-item"><a class="page-link" href="<!--link_first-->">首页</a></li>
    <li class="page-item"><a class="page-link" href="<!--link_prev-->">上页</a></li>
    <li class="page-item"><a class="page-link" href="<!--link_next-->">下页</a></li>
    <li class="page-item"><a class="page-link" href="<!--link_last-->">末页</a></li>
    <li>
        <div class="input-group input-group-sm mb-2 mr-sm-2">
            <input type="text" class="form-control text-center" name="jump" size="2" value="<!--page-->" />
            <div class="input-group-append">
                <button class="btn btn-light btn-outline-secondary" name="jump" type="button">跳页</button>
            </div>
        </div>
    </li>
    <li class="pt-1"> 【 共 <!--page_count--> 页，  <!--count--> 条记录 】</li>
    <!--else-->
    <li class="pt-1"> 【 共 <!--count--> 条记录 】</li>
    <!--if:end-->
</ul>
<script type="application/javascript">
    $(function(){
        $('input[name=jump]').keypress(function(e){
            if(e.keyCode===13) {
                location.href=global.root_fix+'?<!--query-->&page='+this.value;
            }
        });
        $('button[name=jump]').click(function(e){
            location.href=global.root_fix+'?<!--query-->&page='+$('input[name=jump]').val();
        });
    });
</script>