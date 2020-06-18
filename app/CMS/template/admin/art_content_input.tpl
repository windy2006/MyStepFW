<div class="card border-bottom-0 bg-transparent mb-5">
	<div class="card-header bg-info text-white position-fixed w-100 title">
		<i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
	</div>
	<div class="card-body p-0 table-responsive mt-5">
		<form class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 px-0 py-2" method="post" action="<!--method-->_ok" onsubmit="return checkForm(this, myChecker)">
			<div class="input-group mb-2" title="当前文章所属的子网站">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">所属网站</span>
				</div>
				<select name="web_id" class="custom-select">
					<!--loop:start key="website"-->
					<option value="<!--website_web_id-->" <!--website_selected-->><!--website_name--></option>
					<!--loop:end-->
				</select>
			</div>
			<div class="input-group mb-2" title="请选择当前文章所属的类别">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">所属栏目</span>
				</div>
				<input type="hidden" name="news_id" value="<!--record_news_id-->" />
				<input type="hidden" name="pages" value="<!--record_pages-->" />
				<input type="hidden" name="back_url" value="<!--back_url-->" />
				<select name="cat_id" class="custom-select">
					<option value="">请选择</option>
					<!--loop:start key="catalog"-->
					<option value="<!--catalog_cat_id-->" web_id="<!--catalog_web_id-->" view_lvl="<!--catalog_view_lvl-->" <!--catalog_selected-->><!--catalog_name--></option>
					<!--loop:end-->
				</select>
				<div class="input-group-append">
					<button id="publish" class="btn btn-light btn-outline-secondary" type="button" data-toggle="modal" data-target="#multiCata">多栏目发布</button>
				</div>
			</div>
			<div class="input-group mb-2" title="当前文章名">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">文章标题</span>
				</div>
				<input name="subject" type="text" id="subject" class="form-control scaled" value="<!--record_subject-->" maxlength="100" required />
				<div class="input-group-append rounded">
					<select id="prefix" class="custom-select rounded-0 border-right-0">
						<option>无前缀</option>
					</select>
					<div class="input-group-text bg-white py-0 pt-1">
						<label><input type="checkbox" name="style[]" value="b" /> 粗体</label> &nbsp; &nbsp;
						<label><input type="checkbox" name="style[]" value="i" /> 斜体</label> &nbsp; &nbsp;
						<label class="mb-1"><input type="color" name="style[]" value="#ff0000" /></label>
					</div>
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="用于搜索相关资讯，多个关键字用逗号分隔">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">关 键 字</span>
				</div>
				<input id="keyword" name="tag" class="form-control" type="text" value="<!--record_tag-->" maxlength="100" need="" />
			</div>
			<div class="input-group mb-2 cat_ext" title="文章来源">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">作者出处</span>
				</div>
				<input name="original" type="text" class="form-control" maxlength="80" value="<!--record_original-->" />
			</div>
			<div class="input-group mb-2 cat_ext" title="点击文章标题所链接到的网址">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">跳转网址</span>
				</div>
				<input id="link" name="link" type="text" class="form-control" maxlength="150" value="<!--record_link-->" />
			</div>
			<div class="input-group mb-2" title="文章标题图形显示">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">文章图示</span>
				</div>
				<input id="image" name="image" class="form-control" type="text" maxlength="150" value="<!--record_image-->" />
				<div class="input-group-append">
					<button id="upload" class="btn btn-light btn-outline-secondary" type="button" data-title="请选择需要上传的图示文件">上传</button>
					<button class="btn btn-light btn-outline-secondary" type="button" name="image">插入</button>
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="浏览当前文章需要达到的级别，0为不限制">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">阅读权限</span>
				</div>
				<div class="form-control pt-0">
					<input name="view_lvl" type="range" class="form-control-range custom-range mt-2" min="0" max="9" value="<!--record_view_lvl-->" need="digital_" />
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="文章排序，序号越大越靠前，可达到文章置顶效果">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">列表排序</span>
				</div>
				<div class="form-control pt-0">
					<input name="order" type="range" class="form-control-range custom-range mt-2" min="0" max="9" value="<!--record_order-->" need="digital_" />
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="文章发布后将在此时间正式显示">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">激活日期</span>
				</div>
				<input name="active" class="form-control" type="date" value="<!--record_active-->" need="date_" />
			</div>
			<div class="input-group mb-2 cat_ext" title="超过此时间的文章将不在列表中显示，可留空">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">过期日期</span>
				</div>
				<input name="expire" class="form-control" type="date" value="<!--record_expire-->" need="date_" />
			</div>
			<div class="input-group mb-2 cat_ext" title="文章以何种方式推送">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">推送模式</span>
				</div>
				<div class="form-control">
					<!--loop:start key="push_mode"-->
					<label><input type="radio" name="setop_mode" value="<!--push_mode_idx-->" /> <!--push_mode_name--></label> &nbsp; &nbsp;
					<!--loop:end-->
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="文章推送到哪里，可复选">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">推送位置</span>
				</div>
				<div class="form-control">
					<!--loop:start key="push_pos"-->
					<label><input type="checkbox" name="setop[]" value="<!--push_pos_idx-->" /> <!--push_pos_name--></label> &nbsp; &nbsp;
					<!--loop:end-->
				</div>
			</div>
			<div class="input-group mb-2 cat_ext" title="限120字">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">文章描述</span>
				</div>
				<textarea name="describe" class="form-control" need=""><!--record_describe--></textarea>
			</div>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text item-name">主要内容</span>
				</div>
				<div class="form-control">
					<label><input name="get_remote_file" type="checkbox" value="1" <!--get_remote_file--> /> 自动复制外网图片到本地 &nbsp; &nbsp; &nbsp; &nbsp;</label>
					<label><input name="independent_article" type="checkbox" value="1" /> 独立栏目文章 &nbsp; &nbsp; &nbsp; &nbsp;</label>
					<label><input type="checkbox" id="show_cat" /> 更多选项</label>
				</div>
			</div>
			<div class="input-group mb-2">
				<textarea id="content" name="content" class="form-control" style="height:300px;"><!--record_content--></textarea>
			</div>
			<div class="input-group mb-2">
				<div class="form-control border-0">
					提示：双击图片可自动设置为标题图片，双击链接可自动设置为跳转链接！
				</div>
			</div>
			<div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
				<div class="float-right p-2 border-0">
					<input type="hidden" name="multi_cata" value="">
					<button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
					<button class="btn btn-primary btn-sm mr-3" type="reset"> 重 置 </button>
					<button class="btn btn-primary btn-sm" type="button" onClick="location.href='<!--back_url-->'"> 返 回 </button>
				</div>
			</div>
		</form>
	</div>
</div>
<div id="multiCata" class="modal fade">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">多栏目发布</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!--loop:start key="catalog"-->
                <label><input type="checkbox" name="multi_cata" value="<!--catalog_cat_id-->," /> <!--catalog_name--> </label><br />
                <!--loop:end-->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"> 关闭 </button>
                <button type="submit" class="btn btn-primary" name="put_catalog"> 确认 </button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
let news_id = "<!--news_id-->";
let cat_sub_list = [];
<!--loop:start key="cat_sub"-->
cat_sub_list['<!--cat_sub_cat_id-->'] = "<!--cat_sub_prefix-->";
<!--loop:end-->
$('.cat_ext').hide();
function myChecker(theForm) {
    if(theForm.describe.value==="") getDescribe();
    if(theForm.link.value!=="" && $id("content").value==="") {
        $id("content").value = theForm.link.value;
    }
    if(theForm.cat_id.value==='' && !theForm.independent_article.checked) {
    	alert("请选择文章所属栏目！");
		theForm.cat_id.focus();
    	return false;
	}
    let flag = true;
    let theLen = theForm.describe.value.blen();
    if(theLen>240) {
        alert(printf("当前描述长度为 %1 字节，请限制在 %2 字节内！", theLen, 240));
        flag = false;
    }
	theLen = $("#keyword").val().blen();
	if(theLen>50 || theLen===0) {
		$("#keyword").val($('#subject').val());
	}
    return flag;
}
function getKeyword() {
	if($("#keyword").val().length>2) return;
	let str = $("#subject").val();
	if(str.length>2) {
        $.get("/api/myStep/segment/"+str, function(data){
            $("#keyword").val(data);
        }, "text");
    }
    return;
}
function getDescribe() {
	let obj = $("textarea[name=describe]");
	if(obj.val().length>10) return;
	let content = tinyMCE.get("content").getBody().innerText.replace(/^.+?1[\r\n]+/, "").replace(/[　“”]/g,"").split("。");
	let result = "";
	for(let i=0,m=content.length;i<m;i++) {
		content[i] = content[i].replace(/[\r\n]+/g,"");
		if(content[i].length==0) continue;
		result += content[i]+"。";
		if(result.blen()>160) break;
	}
	if(result.blen()>230) {
		while(result.blen()>230) {
			result = result.substr(0, result.length-4);
		}
		result += "……";
	}
    obj.val(result);
	return;
}
function setPrefix(cat_id) {
    if(cat_id==null || typeof(cat_id)=="undefined") cat_id = $("#select[name=cat_id]").value;
	let prefix = $id("prefix");
    if(cat_id=="") {
		prefix.selectedIndex = 0;
		prefix.disabled = true;
        return;
    }
    let the_list = new Array();
    prefix.innerHTML = "";
    prefix.options.add(new Option("无前缀", "", false, false));
    if(typeof(cat_sub_list[cat_id])!=='undefined' && cat_sub_list[cat_id].length>0) {
        prefix.disabled = false;
        the_list = cat_sub_list[cat_id].split(",");
        for(let i=0, m=the_list.length; i<m; i++) {
            prefix.options.add(new Option(the_list[i], the_list[i], false, false));
        }
    } else {
        prefix.disabled = true;
    }
    return;
}
$(function(){
	$('#show_cat').click(function(){
		$('.cat_ext').slideToggle();
	});
	$('#prefix').change(function(){
		let obj = $id('subject');
		let prefix = $id("prefix").value;
		obj.focus();
		obj.value = obj.value.replace(/\[.+?\]/g, "");
		if(prefix.length>1) {
			obj.value = "[" + prefix + "]" + obj.value;
		}
		return;
	});
    $('#cat_id').change(function(){
        setPrefix(this.value);
        $('select[name=web_id]').val(this.options[this.selectedIndex].getAttribute('web_id'));
        $('input[name=view_lvl]').val(this.options[this.selectedIndex].getAttribute('view_lvl'));
	});
    $('select[name=web_id]').change(function(){
		let val = this.value;
		let objs = $('select[name=cat_id]');
		objs.find('option').hide();
		objs.find('option[web_id='+val+']').show();
	});
	$("#subject").blur(getKeyword);
	$("#keyword").focus(getKeyword);
	$("textarea[name=describe]").focus(getDescribe);
	$("button[name=image]").click(function(){
		if($id("image").value!="") {
			insertContent('<br /><img src="'+$id("image").value+'" /><br />');
		}
	})
	$('button[name=put_catalog]').click(function() {
		let objs = $("#multiCata input[name='multi_cata']");
		let theList = "";
		for(let i=0, m=objs.length; i<m; i++) {
			if(objs[i].checked) theList += objs[i].value;
		}
		document.forms[0].multi_cata.value = theList;
		$("#multiCata").modal("hide");
	});
	$('#multiCata').on('show.bs.modal', function(e){
		if(document.forms[0].multi_cata.value.length>0) {
			let objs = $("#multiCata input[name='multi_cata']");
			let theList = "," + document.forms[0].multi_cata.value;
			objs.prop('checked', false);
			for(let i=0, m=objs.length; i<m; i++) {
				if(theList.indexOf(","+objs[i].value)!=-1) objs[i].checked = true;
			}
		}
        $('[title]').tooltip('hide');
    });
	setPrefix();
    let style = '<!--record_style-->'.split(',');
    for(let i=0,m=style.length;i<m;i++) {
        switch(style[i].substr(0,1).toLocaleLowerCase()) {
            case 'i':
                $('input[type=checkbox][value=i]').prop('checked', true);
                break;
            case 'b':
                $('input[type=checkbox][value=b]').prop('checked', true);
                break;
            default:
                $('input[type=color]').val(style[i]);
        }
    }
    let setop = '<!--record_setop-->';
    if(setop==='0') {
		$("input[name=setop_mode][value=0]").prop("checked", true);
	} else {
		let i=1,n=1;
		while(n <= 32) {
			if((n & setop) == n) $("input[name='setop[]'][value='"+n+"']").prop("checked", true);
			n = Math.pow(2, i++);
		}
		i = 1;
		n = 32;
		while(n <= setop) {
			n += i++*32;
			if(n > setop) {
				$("input[name=setop_mode][value='"+(i-1)+"']").prop("checked", true);
			}
		}
	}
    if('<!--method-->'==='edit') {
		$('select[name=web_id]').prop('disabled', true).parent().hide();
		$('input[name=independent_article]').parent().remove();
	}
	$('select[name=web_id]').trigger('change');
	global.root_fix += 'article/content/';
});
let setting_tinymce_ext = {
	setup : function(editor) {
		editor.addButton('subtitle', {
			title : '分页标题设置',
			image : '<!--path_root-->static/images/subtitle.gif',
			onclick : function() {
				let sel = editor.selection.getContent();
				let str = editor.selection.getContent({format : 'text'});
				if(str.length>0 && !/^(<(\w+)>)?<span class=\"mceSubtitle\">(.+)<\/span>(<\/\2>)?$/i.test(sel)) {
					str = '<span class="mceSubtitle">'+str+'</span>';
				}
				editor.execCommand('mceInsertContent',false,str);
			}
		});
		editor.on('click', function(e) {
			e = e.target;
			if (e.nodeName === 'SPAN' && editor.dom.hasClass(e, "mceSubtitle")) {
				editor.selection.select(e);
			}
		});
		editor.on('dblclick', function(e) {
			e = e.target;
			if(e.nodeName === 'IMG' && $id("image")!=null) {
				if(confirm("是否将 "+e.src+" 设定为新闻标题图?")) {
					$id("image").value = e.src;
				}
			} else if(e.nodeName === 'A' && $id("link")!=null) {
				if(confirm("是否将 "+e.href+" 设定为跳转网址?")) {
					$id("link").value = e.href;
				}
			}
		});
	}
};
let setting_tinymce_btn = 'pagebreak subtitle highlight';
</script>
<script type="application/javascript" src="vendor/tinymce/tinymce.min.js"></script>
<script type="application/javascript" src="app/CMS/asset/admin/tinymce_init.js"></script>