<div class="card w-100 mb-5 mb-sm-2">
	<div id="receiver" class="card-body py-5 text-center" style="height:200px;">
		<div style="font-size:32px;">上传组件初始化中。。。</div>
	</div>
	<div id="list_file" class="card-body py-2 d-none">
		已上传文件：
	</div>
</div>
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
<script language="JavaScript">
    jQuery.vendor('jquery.jmpopups', {add_css:true});
    jQuery.vendor('jquery.powerupload', {
		add_css:true,
		callback:function(){
            checkNrun('powerUpload_init', ['receiver', 5, 10]);
            //powerUpload_init('receiver', 5, 10);
    	}
    });
    function powerUpload_init(the_id, limit_count, limit_size){
        if(typeof(limit_count)=="undefined") limit_count = 5;
        if(typeof(limit_size)=="undefined") limit_size = 10;
        $("#"+the_id+" div").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
        $("#"+the_id).powerUpload({
            maxfiles: limit_count,
            maxfilesize: limit_size,
            url: setting.path_root+'index.php?upload',

            error: function(err, file, index) {
                switch(err) {
                    case 'BrowserNotSupported':
                        alert('您的浏览器不支持拖拽上传！');
                        break;
                    case 'TooManyFiles':
                        alert('每次最多只能上传 '+limit_count+' 个文件');
                        break;
                    case 'FileTooLarge':
                        alert('文件 ' + file.name+' 过大，只能上传小于 ' + limit_size + 'MB 的文件！');
                        break;
                    default:
                        break;
                }
            },

            dragOver: function(){
                $("#"+the_id+" div").text('松开鼠标以上传！');
            },

            dragLeave: function(){
                $("#"+the_id+" div").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
            },

            uploadStarted:function(i, file, len){
                var file_info = $('<div class="file_info"><div class="file_name"></div><div class="progressHolder"><div class="progress"></div></div></div>');
                file_info.find(".file_name").html(file.name);
                showPop('info_upload','文件上传','id','info_upload',300);
                $("#popupLayer_info_upload_content").addClass("info_upload");
                file_info.appendTo("#popupLayer_info_upload_content");
                if($("#popupLayer_info_upload > .button").length==0) $('<div class="button"></div>').css({"text-align":"center","margin-bottom":"10px"}).append($("<button>").html("关闭").attr("onclick", "$.closePopupLayer_now();")).appendTo("#popupLayer_info_upload");
                $.setPopupLayersPosition();

                var reader = new FileReader();
                reader.readAsDataURL(file);
                $.data(file,file_info);
                return;
            },

            uploadFinished:function(i,file,result,timeDiff){
                $("#"+the_id+" div").html('请将需上传的文件拖拽至此框内，<br />亦可在截图后点击此筐粘贴。');
                $.data(file).find(".progress").css('width','100%');
                if(result.error!=0) {
                    $.data(file).attr("title", "上传失败！\n原因：" + result.message);
                    $.data(file).find(".file_name").css("color", "#990000");
                    $.data(file).find(".progress").css({"background-color":"#990000","color":"#ffffff"}).html("上传失败：" + result.message);
                    return;
                } else {
                    $('#list_file').removeClass('d-none');
					var obj = $('<a href="download/'+result.new_name.split('.').slice(0,2).join('.')+'">'+file.name+'<span>X</span></a> ');
					obj.appendTo('#list_file');
					obj.find('span').click(function(e){
						e.preventDefault();
						$.get(setting.path_root+'index.php?remove_ul/'+result.new_name.split('.').slice(0,2).join('.'), function(data){
							if(data.statusCode==1) {
                                alert('文件已删除！');
                                if(obj.parent().find('a').length==1) obj.parent().addClass('d-none');
                                obj.remove();
							} else {
                                alert('文件删除失败！');
							}
						}, 'json');
						return false;
					});
				}
            },

            progressUpdated: function(i, file, progress) {
                $.data(file).find('.progress').width(progress);
            },

            speedUpdated: function(index, file, speed) {
                $.data(file).find(".progress").html(Math.round(speed) + "KB/S");
            },

            allDone: function() {
                $("#popupLayer_info_upload").find(".info").html("上传完成！");
            }
        });
    }
</script>