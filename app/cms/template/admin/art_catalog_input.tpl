<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-xs-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this, myChecker)">
            <div class="input-group mb-2" title="当前分类所属的子网站">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">所属网站</span>
                </div>
                <select name="web_id" onchange="setCata()" class="custom-select">
                    <!--loop:start key="website"-->
                    <option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
                    <!--loop:end-->
                </select>
            </div>
            <div class="input-group mb-2" title="当前栏目的父栏目">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">所属栏目</span>
                </div>
                <select name="pid" id="pid" onchange="changeCata(this.selectedIndex)" class="custom-select">
                    <option value="0">顶级栏目</option>
                    <!--loop:start key="catalog"-->
                    <option value="<!--catalog_cat_id-->" webid="<!--catalog_web_id-->"><!--catalog_name--></option>
                    <!--loop:end-->
                </select>
                <div class="input-group-append" style="display:<!--show_merge-->;">
                    <div class="input-group-text"><label class="m-0"><input type="checkbox" name="merge" value="1" />&nbsp;合并</label></div>
                </div>
            </div>
            <div class="input-group mb-2" title="用于显示的栏目名称">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">分类名称</span>
                </div>
                <input name="name" class="form-control" placeholder="用于显示的栏目名称" type="text" maxlength="30" value="<!--cat_name-->" need="" required autofocus />
                <input name="cat_id" type="hidden" value="<!--cat_cat_id-->" />
            </div>
            <div class="input-group mb-2" title="作为栏目网址路径的一部分，不能与其他分类索引相同">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">分类索引</span>
                </div>
                <input name="idx" class="form-control" placeholder="作为栏目网址路径的一部分，不能与其他分类索引相同" type="text" maxlength="20" value="<!--cat_idx-->" need="" />
            </div>
            <div class="input-group mb-2" title="改分类新闻所能使用的前缀，多个请用半角逗号间隔">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">子 分 类</span>
                </div>
                <input name="prefix" class="form-control" placeholder="改分类新闻所能使用的前缀，多个请用半角逗号间隔" type="text" maxlength="80" value="<!--cat_prefix-->" />
            </div>
            <div class="input-group mb-2" title="向搜索引擎告知当前栏目的关键词">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">关 键 字</span>
                </div>
                <input name="keyword" type="text" class="form-control" placeholder="向搜索引擎告知当前栏目的关键词" maxlength="150" value="<!--cat_keyword-->" need="" />
            </div>
            <div class="input-group mb-2" title="向搜索引擎描述当前栏目">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">分类描述</span>
                </div>
                <input name="comment" type="text" class="form-control" placeholder="向搜索引擎描述当前栏目" maxlength="120" value="<!--cat_comment-->" need="" />
            </div>
            <div class="input-group mb-2" title="用于标识栏目的图标">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">分类图示</span>
                </div>
                <input name="image" type="text"  class="form-control" placeholder="用于标识栏目的图标" maxlength="120" value="<!--cat_image-->" />
                <div class="input-group-append">
                    <button id="upload" class="btn btn-light btn-outline-secondary" type="button">上传</button>
                </div>
            </div>
            <div class="input-group mb-2" title="浏览当前分类文章需要达到的级别，0为不限制">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">阅读权限</span>
                </div>
                <input name="view_lvl_org" type="hidden" value="<!--cat_view_lvl-->" />
                <div class="form-control pt-2">
                    <input name="view_lvl" type="range" class="form-control-range custom-range" min="0" max="9" value="<!--cat_view_lvl-->" />
                </div>
            </div>
            <div class="input-group mb-2" title="根据栏目内容选择对应的目录页展示方式">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">显示模板</span>
                </div>
                <select name="type" onchange="showTpl(this.value)" class="custom-select">
                    <option value="0">标题列表</option>
                    <option value="1">图片展示</option>
                    <option value="2">图文混合</option>
                    <option value="3">自定义</option>
                </select>
            </div>
            <div id="tpl" class="p-0 mb-2 border">
                <textarea id="template" type="php" name="template" class="w-100"><!--cat_template--></textarea>
            </div>
            <div class="input-group mb-2" title="定义当前分类的显示位置">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">显示位置</span>
                </div>
                <input name="view_lvl_org" type="hidden" value="<!--cat_view_lvl-->" />
                <div class="form-control pt-1">
                    <!--loop:start key="positions"-->
                    <label><input type="checkbox" name="show[]" value="<!--positions_idx-->" /> <!--positions_name--></label>
                    <!--loop:end-->
                </div>
            </div>
            <div class="input-group mb-2" title="点击栏目将会直接跳转到相关网址">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">外部链接</span>
                </div>
                <input name="link" type="text" class="form-control" placeholder="点击栏目将会直接跳转到相关网址" maxlength="150" value="<!--cat_link-->" />
             </div>
            <div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
                <div class="float-right p-2 border-0">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
                    <button class="btn btn-primary btn-sm" type="button"  onClick="location.href='<!--back_url-->'"> 返 回 </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
jQuery.vendor('jquery.powerupload', {
    callback:function(){
        $('#upload').powerUpload({
            url: '<!--url_prefix-->api/myStep/upload',
            title: '请选择需要上传的图标文件',
            mode: 'browse',
            maxfiles: 1,
            maxfilesize: 8,
            errors: ["浏览器不支持", "一次只能上传1个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
            uploadFinished:function(i,file,result,timeDiff){
                if(result.error!=0) {
                    alert("上传失败！\n原因：" + result.message);
                } else {
                    $('#uploader').find(".modal-title > b").html("上传完成，请关闭本对话框！");
                    $('#uploader').on('hidden.bs.modal', function (e) {
                        $("input[name=image]").val('<!--url_prefix-->api/myStep/download/'+result.new_name.split('.').slice(0,2).join('.'))
                    });
                }
            }
        });
    }
});
function myChecker(the_form) {
    if(the_form.idx.value=="") the_form.idx.value = the_form.name.value;
    if(the_form.keyword.value=="") the_form.keyword.value = the_form.name.value;
    if(the_form.comment.value=="") the_form.comment.value = the_form.name.value;
    return true;
}
function changeCata(idx) {
	let web_id=$id("pid").options[idx].getAttribute("webid");
    let obj = $('select[name=web_id]').get(0);
	if(web_id!=null) {
		for(let i=0; i<obj.options.length; i++) {
			if(obj.options[i].value==web_id) {
                obj.selectedIndex = i;
				break;
			}
		}
	}
}
function setCata(){
	let webid = $('select[name=web_id]').val();
	$("#pid > option").hide();
	$("#pid > option:first").show();
	$("#pid > option[webid="+webid+"]").show();
    $("select[name=pid]").val('<!--cat_pid-->');
}
function showTpl() {
    let val = $("select[name=type]").val();
    if(val==3) {
        $("#tpl").show();
    } else {
        $("#tpl").hide();
    }
}
$(function(){
    let cat_show = <!--cat_show-->;
    let i = 1, n = 1;
    while(n <= cat_show) {
        if((n & cat_show) == n) $("input[name='show[]'][value='"+n+"']").prop("checked", true);
        n = Math.pow(2, i++);
    }
    $("select[name=type]").val('<!--cat_type-->');
	setCata();
    $.setCSS([
        '<!--path_root-->vendor/codemirror/lib/codemirror.css',
        '<!--path_root-->vendor/codemirror/addon/fold/foldgutter.css',
        '<!--path_root-->vendor/codemirror/addon/display/fullscreen.css'
    ]);
    $.setJS([
        "<!--path_root-->vendor/codemirror/lib/codemirror.js",
        "<!--path_root-->vendor/codemirror/addon/fold/foldcode.js",
        "<!--path_root-->vendor/codemirror/addon/fold/foldgutter.js",
        "<!--path_root-->vendor/codemirror/addon/fold/brace-fold.js",
        "<!--path_root-->vendor/codemirror/addon/fold/xml-fold.js",
        "<!--path_root-->vendor/codemirror/addon/fold/indent-fold.js",
        "<!--path_root-->vendor/codemirror/addon/fold/comment-fold.js",
        "<!--path_root-->vendor/codemirror/addon/display/fullscreen.js",
        "<!--path_root-->vendor/codemirror/mode/javascript/javascript.js",
        "<!--path_root-->vendor/codemirror/mode/xml/xml.js",
        "<!--path_root-->vendor/codemirror/mode/css/css.js",
        "<!--path_root-->vendor/codemirror/mode/htmlmixed/htmlmixed.js",
    ], true, function() {
        let editor = CodeMirror.fromTextArea($id("template"), {
            mode: "text/html",
            lineNumbers: true,
            lineWrapping: true,
            foldGutter: true,
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
        });
        editor.setOption("extraKeys", {
            // Tab键换成4个空格
            Tab: function(cm) {
                let spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
                cm.replaceSelection(spaces);
            },
            // Ctrl+F11键切换全屏
            "Ctrl-F11": function(cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            // Esc键退出全屏
            "Esc": function(cm) {
                if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
            },
            // 折叠、展开标签
            "Ctrl-Q": function(cm) {
                cm.foldCode(cm.getCursor());
            }
        });
        showTpl();
    });
    global.root_fix += 'article/catalog/';
});
</script>
