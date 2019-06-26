/**************************************************
*                                                 *
* Author  : Windy_sk                              *
* Create  : 2012-02-03                            *
* Email   : windy2006@gmail.com                   *
* HomePage: www.mysteps.cn                        *
* Notice  : U Can Use & Modify it freely,         *
*           BUT PLEASE HOLD THIS ITEM.            *
*                                                 *
**************************************************/

$(function(){
	var obj_title = $(".title");
	if(obj_title.length) {
		var new_title = obj_title.clone().css({"position":"fixed","top":"0px","left":"0px","width":"100%","z-index":"99"}).prependTo("#page_main");
	}
	var obj_addnew = $(".addnew");
	if(obj_addnew.length) {
		var top = 3;
		obj_addnew.css({"position":"fixed", "top":top, "z-index":999});
	}
});

function add_color(obj_select, theColor){
	if(obj_select.tagName.toLowerCase() != "select") return;
	var color_list = new Array('', 'black','dimgray','red','orange','pink','yellow','blue','green');
	var curIndex = 0;
	var selIndex = 0;
	obj_select.innerHTML = "";
	for(var i=0; i<color_list.length; i++){
		curIndex = obj_select.options.length;
		obj_select.options[curIndex] = new Option(color_list[i], color_list[i]);
		obj_select.options[curIndex].style.backgroundColor = color_list[i];
		obj_select.options[curIndex].style.color = color_list[i];
		if(color_list[i]==theColor) selIndex = curIndex;
	}
	obj_select.selectedIndex = selIndex;
}

function title_change() {
	var obj = $id('title');
	var theProfix = $id("profix").value;
	obj.focus();
	obj.value = obj.value.replace(/\[.+?\]/g, "");
	if(theProfix.length>1) {
		obj.value = "[" + theProfix + "]" + obj.value;
	}
	return;
}

function profix_changed(cat_id) {
	if(cat_id==null || cat_id=="") {
		$id("profix").disabled = true;
		return;
	}
	if(typeof(cat_id)=="undefined") cat_id = $id("cat_id").value;
	var profix = $id("profix");
	var the_list = new Array();
	var i = 0;
	profix.innerHTML = "";
	profix.options.add(new Option("无前缀", "", 0, 0));
	if(cat_sub_list[cat_id].length>0) {
		profix.disabled = false;
		the_list = cat_sub_list[cat_id].split(",");
		for(i=0; i<the_list.length; i++) {
			profix.options.add(new Option(the_list[i], the_list[i], 0, 0));
		}
	} else {
		profix.disabled = true;
	}
	return;
}

function setMultiCata() {
	if(document.forms[0].multi_cata.value.length>0) {
		var objs = $("#popupLayer_newsCatalog input[name='multi_cata']");
		var theList = "," + document.forms[0].multi_cata.value;
		for(var i=0, m=objs.length; i<m; i++) {
			if(theList.indexOf(","+objs[i].value)!=-1) objs[i].checked = true;
		}
	}
}

function putMultiCata() {
	var objs = $("#popupLayer_newsCatalog input[name='multi_cata']");
	var theList = "";
	for(var i=0, m=objs.length; i<m; i++) {
		if(objs[i].checked) theList += objs[i].value;
	}
	document.forms[0].multi_cata.value = theList;
	$.closePopupLayer();
}

function setIframe(idx) {
	if($id("popupLayer_"+idx)) {
		var theFrame = $("#popupLayer_"+idx).find("iframe");
		var theWidth = $("#popupLayer_"+idx).width()-20;
		var theHeight = theFrame.contents().find("body")[0].scrollHeight;
		theFrame.height(theHeight);
		if(theHeight>600) {
			theHeight = 600;
			$("#popupLayer_"+idx+"_content").css("overflow-y","scroll");
		}
		$("#popupLayer_"+idx).height($("#popupLayer_"+idx+"_title").height()+theHeight+16);
		$("#popupLayer_"+idx+"_content").height(theHeight - 16);
		$("#popupLayer_"+idx).width(theWidth+20);
		$("#popupLayer_"+idx+"_content").width(theWidth);
		$.setPopupLayersPosition();
	}
}

function insertImg() {
	if($id("image").value!="") {
		attach_add('<br /><img src="'+$id("image").value+'" /><br />');
	}
}

function putImage(obj) {
	$id("keyword").value = obj.title;
	$id("image").value = obj.getAttribute("src");
	$.closePopupLayer();
}

function attach_add(str) {
	tinyMCE.execCommand("mceInsertContent", false, str);
}

function attach_edit() {
	showPop('attach','附件管理','url','attachment.php?method=edit&news_id='+news_id+'&attach_list='+document.forms[0].attach_list.value, 600, 200);
	return;
}

function attach_mine() {
	showPop('attach_mine','我的附件','url','attachment.php?method=mine', 600, 200);
	return;
}

function attach_remove(aid) {
	var content, re;
	content = tinyMCE.activeEditor.getContent();
	re = new RegExp("<a id\\=\"att_"+aid+".+?<\\/a>", "ig");
	content = content.replace(re, "");
	re = new RegExp("(<br \\/>)*<img.+?files\\?"+aid+".+?>(<br \\/>)*", "ig");
	content = content.replace(re, "");
	re = new RegExp("<p>[\s\r\n]*<\\/p>", "ig");
	content = content.replace(re, "");
	re = new RegExp("<div>[\s\r\n]*<\\/div>", "ig");
	content = content.replace(re, "");
	tinyMCE.activeEditor.setContent(content);
	return;
}

function tinyMCE_init(the_id, new_setting) {
	if(typeof(tinymce_setting)=="undefined") {
		setTimeout(function(){tinyMCE_init(the_id);}, 1000);
		return;
	}
	if(typeof(new_setting)=="object") {
		for(var x in new_setting) tinymce_setting[x] = new_setting[x];
	}
	$('#'+the_id).tinymce(tinymce_setting);
	if(typeof(upload_limit)!="undefined") powerUpload_init(the_id);
	ed_id = the_id;
	return;
}

function powerUpload_init(the_id){
	if(typeof(tinyMCE)=="undefined" || typeof(tinyMCE.get(the_id))=="undefined") {
		setTimeout(function(){powerUpload_init(the_id);}, 1000);
		return;
	}
	$("head").append($('<link id="css_powerupload" rel="stylesheet" href="'+rlt_path+'script/jquery.powerupload.css" type="text/css" media="screen" />'));
	$("<div>").attr("id", "info_upload").html('<div class="info"></div>').css("display", "none").appendTo("body");
	$("#css_powerupload").attr("href", rlt_path+"script/jquery.powerupload.css");
	
	if(typeof(upload_limit)=="undefined") upload_limit = 10;
	$(tinyMCE.getInstanceById(the_id).getBody()).powerUpload({
		maxfiles: 30,
		maxfilesize: upload_limit,
		url: 'upload.php',
		
		error: function(err, file, index) {
			switch(err) {
				case 'BrowserNotSupported':
					alert('您的浏览器不支持拖拽上传！');
					break;
				case 'TooManyFiles':
					alert('每次最多只能上传 30 个文件');
					break;
				case 'FileTooLarge':
					alert('文件 ' + file.name+' 过大，只能上传小于 ' + upload_limit + 'MB 的文件！');
					break;
				default:
					break;
			}
		},
		
		uploadStarted:function(i, file, len){
			var file_info = $('<div class="file_info"><div class="file_name"></div><div class="progressHolder"><div class="progress"></div></div></div>');
			file_info.find(".file_name").html(file.name);
			showPop('info_upload','文件上传','id','info_upload',300);
			$("#popupLayer_info_upload_content").addClass("info_upload");
			file_info.appendTo("#popupLayer_info_upload_content");
			if($("#popupLayer_info_upload > .button").length==0)	$('<div class="button"></div>').css({"text-align":"center","margin-bottom":"10px"}).append($("<button>").html("关闭").attr("onclick", "$.closePopupLayer_now();")).appendTo("#popupLayer_info_upload");
			$.setPopupLayersPosition();

			var reader = new FileReader();
			reader.readAsDataURL(file);
			$.data(file,file_info);
			return;
		},
		
		uploadFinished:function(i,file,result,timeDiff){
			$.data(file).find(".progress").css('width','100%');
			if(result.error!=0) {
				$.data(file).attr("title", "上传失败！\n原因：" + result.message)
				$.data(file).find(".file_name").css("color", "#990000");
				$.data(file).find(".progress").css({"background-color":"#990000","color":"#ffffff"}).html("上传失败：" + result.message);
				return;
			}
			var content = tinyMCE.get(the_id).getContent();
			var re = new RegExp("file:///.+?"+file.name, "g");
			if(content.match(re)) {
				content = content.replace(re, web_url + '/files/?' + result.att_id);
				tinyMCE.get(the_id).setContent(content);
			} else {
				var html = "";
				if(file.type.match(/^image\//)) {
					html = '&nbsp;<br /><a id="att_' + result.att_id + '" href="' + web_url + '/files/show.htm?' + result.att_id + '" target="_blank"><img src="' + web_url + '/files/?' + result.att_id + '" alt="' + file.name + '" /></a><br />&nbsp;';
				} else {
					html = '&nbsp;<br /><a id="att_' + result.att_id + '" href="' + web_url + '/files/?' + result.att_id + '" target="_blank">' + file.name + '</a><br />&nbsp;';
				}
				tinyMCE.execCommand("mceInsertContent", false, html);
			}
			document.forms[0].attach_list.value += result.att_id+'|';
		},
		
		progressUpdated: function(i, file, progress) {
			$.data(file).find('.progress').width(progress);
		},
		
		speedUpdated: function(index, file, speed) {
			$.data(file).find(".progress").html(Math.round(speed) + "KB/S");
		},
		
		paste: function(e) {
			var items = e.clipboardData.items;
			for(var i=0,m=items.length;i<m;i++) {
				if(items[i]['type'] == "text/html") {
					items[i].getAsString(function(str){
						var img_list = str.match(/<img[\w\W]+?src\="file.+?"/ig);
						if(img_list==null) return;
						var img_local = new Array();
						for(var i=0,m=img_list.length;i<m;i++) {
							if(img_list[i].match(/"file\:\/\/\/(.+?)"/)) {
								img_local.push(RegExp.$1.replace(/\\/g, "/"));
							}
						}
						if(img_local.length>0) {
							var dir = img_local[0].substring(0, img_local[0].lastIndexOf("/"));
							alert("发现存在如下本地文件，请手动上传：\n\n" + img_local.join("\n") + "\n \n请在资源管理器中打开 <a href=\"file:///"+dir+"\" target=\"_blank\">"+dir+"</a> 目录，\n 并将相关文件直接拖拽至编辑器内即可与对应文件自动关联！");
						}
						return;
					});
				}
			}
			return true;
		},
		
		allDone: function() {
			$("#popupLayer_info_upload").find(".info").html("上传完成！");
		}
	});
}

var tinymce_setting = {
		// Location of TinyMCE script
		script_url : '/script/tinymce/tiny_mce.js',

		// General options
		language : "cn",
		theme : "advanced",
		plugins : "quote,bbscode,source_code,style,table,advlink,advimage,subtitle,pagebreak,inlinepopups,preview,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,insertdatetime,visualchars,nonbreaking,xhtmlxtras,template",

		// Theme options
		theme_advanced_buttons1 : "fullscreen,preview,|,undo,redo,removeformat,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,fontsizeselect,|,forecolor,backcolor,|,tablecontrols",
		theme_advanced_buttons2 : "pagebreak,Subtitle,upload,|,hr,styleprops,sub,sup,|,cut,copy,paste,pastetext,pasteword,bbscode,source_code,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,image,media,showImage,|,insertdate,charmap,|,change,format,code",
		theme_advanced_buttons3 : "",
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : false,
		
		// Custom settings
		remove_script_host: false,
		convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
		preformatted : false,
		remove_linebreaks : false,
		apply_source_formatting : true,
		convert_fonts_to_spans : true,
		verify_html : true,
		paste_auto_cleanup_on_paste : true,
		dialog_type : "modal",
		relative_urls : true,
		invalid_elements : "script",
		extended_valid_elements : "form[action|method|name],"+
															"textarea[class|type|title|name|rows|cols],"+
															"input[type|name|value|checked|src|alt|size|maxlength],"+
															"button[name|value|type],"+
															"select[name|size|multiple|onchange],"+
															"iframe[src|frameborder=0|width|height|align|scrolling|name],"+
															"center,"+
															"script[charset|defer|language|src|type]",
		forced_root_block : "p",
		flash_wmode : "transparent",
		flash_quality : "high",
		flash_menu : "false",

		// Example content CSS (should be your site CSS)
		content_css : "../images/editor.css",
		entity_encoding : "raw",
		add_unload_trigger : false,

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",

		// Custom Functions
		handle_event_callback : function(e) {
			if(e.ctrlKey && e.keyCode==13) {
				if(checkForm(document.forms[0], checkForm_append)) document.forms[0].submit();
			}
		},
		oninit : function() {
			var content = tinyMCE.activeEditor.getContent();
			content = content.replace(/mce\:script/g, "script");
			content = content.replace(/_mce_src/g, "src");
			tinyMCE.activeEditor.setContent(content);
		},

		setup : function(ed) {
			ed.addButton('upload', {
				title : '附件上传',
				image : 'images/file.gif',
				onclick : function() {
					showPop('upload','附件上传','url','attachment.php?method=add',560, 150);
				}
			});
			ed.addButton('change', {
				title : 'Div/P 模式切换',
				image : 'images/div.png',
				onclick : function() {
					var content = tinyMCE.activeEditor.getContent();
					if(content.indexOf("<div")==-1) {
						content = content.replace(/<p(.*?)>([\w\W]+?)<\/p>/ig, "<div$1>$2</div>");
					} else {
						content = content.replace(/<div(.*?)>([\w\W]+?)<\/div>/ig, "<p$1>$2</p>");
					}
					tinyMCE.activeEditor.setContent(content);
				}
			});
			ed.addButton('format', {
				title : '代码清理',
				image : 'images/format.png',
				onclick : function() {
					var content = tinyMCE.activeEditor.getContent();
					if(content.indexOf("<div")==-1) {
						content = content.replace(/(<br(\s\/)?>)+/ig, "</p><p>");
						content = content.replace(/<p(.*?)>[\xa0\r\n\s\u3000]+/ig, "<p$1>");
						content = content.replace(/<\/p><p/g, "<\/p>\n<p");
					} else {
						content = content.replace(/(<br(\s\/)?>)+/ig, "</div><div>");
						content = content.replace(/<div(.*?)>[\xa0\r\n\s\u3000]+/ig, "<div$1>");
						content = content.replace(/<\/div><div/g, "<\/div>\n<div");
					}
					content = content.replace(/mso\-[^;]+?;/ig, "");
					content = content.replace(/[\xa0]/g, "");
					content = content.replace(/<\/td>/g, "&nbsp;</td>");
					while(content.search(/<(\w+)[^>]*><\!\-\- pagebreak \-\-\><\/\1>[\r\n\s]*/)!=-1) content = content.replace(/<(\w+)[^>]*><\!\-\- pagebreak \-\-\><\/\1>[\r\n\s]*/g, "<!-- pagebreak -->");
					while(content.search(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/)!=-1) content = content.replace(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/g, "");
					while(content.search(/<\/(\w+)><\1([^>]*)>/g)!=-1) content = content.replace(/<\/(\w+)><\1([^>]*)>/g, "");
					content = content.replace(/  /g, String.fromCharCode(160)+" ");
					tinyMCE.activeEditor.setContent(content);
				}
			});
			ed.addButton('showImage', {
				title : '图片展示',
				image : 'images/show.png',
				onclick : function() {
					var theContent = ed.selection.getContent();
					var result = "";
					if(theContent.length<10) return;
					var img_list = theContent.match(/<img.+?src=('|")?.+?\1.*?>/ig);
					if(img_list == null) return;
					for(var i=0,m=img_list.length;i<m;i++) {
						if(img_list[i].match(/src=('|")?(.+?)\1/i)) {
							theContent = theContent.replace(img_list[i], "");
							result += '<img src="' + RegExp.$2 + '" />';
						}
					}
					ed.execCommand('mceReplaceContent', false, theContent);
					theContent = ed.getContent().match(/<div id\="ms_showImage">.+?<\/div>/);
					if(theContent!=null) {
						result = theContent[0].replace("</div>", result + "</div>");
						theContent = ed.getContent().replace(theContent[0], "") + result;
					} else {
						theContent = ed.getContent() + '\n<div id="ms_showImage">' + result + '</div>';
					}
					theContent = theContent.replace(/<(\w+)(.*?)>[\xa0\r\n\s\u3000]+<\/\1>[\r\n]*/ig, "");
					ed.setContent(theContent);
				}
			});
			ed.onKeyDown.add(function(ed, e) {
				function doit(mode) {
					var lines = ed.dom.select(mode);
					if(lines.length==0) return;
					var cur_margin = 0;
					var if_select = false;
					var if_done = false;
					var text_start = ed.selection.getStart().outerHTML;
					var text_end = ed.selection.getEnd().outerHTML;
					var element_tmp = null;
					if(ed.selection.getStart().tagName.toLowerCase()!=mode) {
						element_tmp = ed.selection.getStart();
						while(element_tmp.tagName.toLowerCase()!=mode && element_tmp.tagName.toLowerCase()!="body") element_tmp = element_tmp.parentElement;
						if(element_tmp.tagName.toLowerCase()=="body") return;
						text_start = element_tmp.outerHTML;
					}
					if(ed.selection.getEnd().tagName.toLowerCase()!=mode && element_tmp.tagName.toLowerCase()!="body") {
						element_tmp = ed.selection.getEnd();
						while(element_tmp.tagName.toLowerCase()!=mode && element_tmp.tagName.toLowerCase()!="body") element_tmp = element_tmp.parentElement;
						if(element_tmp.tagName.toLowerCase()=="body") return;
						text_end = element_tmp.outerHTML;
					}
					for(var i=0,m=lines.length;i<m;i++) {
						if(if_select==false && lines[i].outerHTML==text_start) if_select = true;
						if(if_select==true && lines[i].outerHTML==text_end) if_done = true;
						if(if_select) {
							cur_margin = parseInt(lines[i].style.marginLeft);
							if(isNaN(cur_margin)) cur_margin = 0;
							cur_margin = cur_margin + (e.keyCode==9?8:-8);
							if(cur_margin<0) cur_margin = 0;
							lines[i].style.marginLeft = cur_margin;
						}
						if(if_done) break;
					}
					return;
				}
				var if_prevent = false;
				if(e.keyCode==9 || e.keyCode==8) {
					if(ed.selection.isCollapsed()) {
						if(e.keyCode==9) {
							tinyMCE.execCommand("mceInsertContent", false, "&nbsp;&nbsp;&nbsp;&nbsp;");
							if_prevent = true;
						}
					} else {
						doit("p");
						doit("div");
						if_prevent = true;
					}
				}
				if(if_prevent) e.preventDefault();
			});
			ed.onDblClick.add(function(ed, e) {
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
		},

		// Replace values for the template plugin
		template_replace_values : {
			username : "mystep",
			staffid : "31415926"
		}
	};