<style>
    #list_file a {
        display:inline-block;
        border: 1px #999 solid;
        background-color: #ddd;
        padding:2px 5px;
        text-decoration: none;
        margin-right:5px;
    }
    #list_file a span {
        font-size:8px;
        background-color: #bbb;
        margin-left:5px;
        border:1px #999 solid;
        padding:0px 2px;
        color:#333
    }
    #list_file a span:hover {
        color:#fff;
    }
    #list_file a:hover {
        background-color: #eee;
    }
</style>
<div class="card w-100 mb-5 mb-sm-2">
    <div id="receiver" class="card-body py-5 text-center" style="height:200px;font-size:24px;padding-top:40%">
        上传组件初始化中。。。
    </div>
    <div id="list_file" class="card-body py-2 d-none">
        已上传文件：
    </div>
</div>
<script type="application/javascript">
    jQuery.vendor('jquery.powerupload', {
        callback:function(){
            $("#receiver").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
            $('#receiver').powerUpload({
                url: '<!--url_prefix-->api/myStep/upload',
                title: '文件上传',
                mode: 'drop',
                max_files: 5,
                max_file_size: 32,
                errors: ["浏览器不支持", "一次只能上传5个文件", "每个文件必须小于32MB", "未设置上传目标", "请至少选择一个文件"],

                uploadFinished:function(i,file,result,timeDiff){
                    let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
                    if(result.error!==0) {
                        obj.html(obj.html()+' - upload failed! ('+result.message+')');
                    } else {
                        $('#list_file').removeClass('d-none');
                        obj.html(obj.html()+' - uploaded!');
                        obj = $('<a href="<!--url_prefix-->api/myStep/download/'+result.new_name.split('.').slice(0,2).join('.')+'">'+file.name+'<span>X</span></a> ');
                        obj.appendTo('#list_file');
                        obj.find('span').click(function(e){
                            e.preventDefault();
                            if(confirm('是否确认删除 "'+$(this).parent().text().replace(/X$/, '')+'" ？')) {
                                $.get('<!--url_prefix-->api/myStep/remove/'+result.new_name.split('.').slice(0,2).join('.'), function(data, status){
                                    if(data.error==='0' && status==='success') {
                                        if(obj.parent().find('a').length==1) obj.parent().addClass('d-none');
                                        obj.remove();
                                    } else {
                                        alert(data.error);
                                    }
                                }, 'json');
                            }
                            return false;
                        });
                    }
                },

                allDone:function(){
                    $("#receiver").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
                    $('#uploader').find(".modal-title > b").html("全部文件上传完成，请关闭本对话框！");
                },

                dragEnter: function(){
                    $("#receiver").text('松开鼠标以上传！');
                },

                dragLeave: function(){
                    $("#receiver").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
                },
            });
        }
    });
</script>