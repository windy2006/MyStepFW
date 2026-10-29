/**
 * 框架调用：showPop / confirm / prompt
 * 功能说明：showPop(name,title,type,content,width,height,no_close)；确认及输入兼容全局函数名称和函数对象，普通原生重载保持同步返回。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
if(!jQuery._myStepPopupLoaded){jQuery._myStepPopupLoaded=true;
/**
 * jmpopups
 * Copyright (c) 2009 Otavio Avila (http://otavioavila.com)
 * Licensed under GNU Lesser General Public License
 * 
 * @docs http://jmpopups.googlecode.com/
 * @version 0.5.1
 *
 * Enhanced by Windy2000
 * 
 */
(function($) {
	let openedPopups = [];
	let popupLayerScreenLocker = false;
	let focusableElement = [];
	let setupJqueryMPopups = {
		screenLockerBackground: "#000",
		screenLockerOpacity: "0.5"
	};
	function topNoClose(){return !!openedPopups[openedPopups.length-1]?.no_close;}

	$.setupJMPopups = function(settings) {
		setupJqueryMPopups = jQuery.extend(setupJqueryMPopups, settings);
		return this;
	}

	$.openPopupLayer = function(settings) {
        settings=settings||{};
        if(typeof settings.name!=='string'||!/^[-\w]+$/.test(settings.name))throw new Error('Popup name must contain letters, numbers, hyphens or underscores.');
		if (!checkIfItExists(settings.name)) {
            settings = jQuery.extend({
                title:"",
				width: "auto",
				height: "auto",
				parameters: {},
				type: "text",
				content: "",
				success: function() {},
				error: function() {},
				beforeClose: function() {},
				afterClose: function() {},
				reloadSuccess: null,
				cache: false,
				no_close: false
			}, settings);
			
			loadPopupLayerContent(settings, true);
			return this;
		}
	}
	
	$.closePopupLayer = function(name) {
		if (name) {
			for (let i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name === name) {
					let thisPopup = openedPopups[i];
					openedPopups.splice(i,1)
					thisPopup.beforeClose();$(document).off('.popupDrag_'+name);
					$("#popupLayer_" + name).fadeOut('slow', function(){
						$(this).remove();
                        if(thisPopup.focusBefore&&thisPopup.focusBefore.isConnected)thisPopup.focusBefore.focus();
						if (focusableElement.length > 0) {
							$(focusableElement[focusableElement.length-1]).focus();
						}
						thisPopup.afterClose();
						hideScreenLocker(name);
					});
					break;
				}
			}
		} else {
			if (openedPopups.length > 0) {
				$.closePopupLayer(openedPopups[openedPopups.length-1].name);
			}
		}
		return this;
	}
	
	$.closePopupLayer_now = function(name) {
		if (name) {
			for (let i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name === name) {
					let thisPopup = openedPopups[i];
					openedPopups.splice(i,1)
					$("#popupLayer_" + name).remove();
                    $(document).off('.popupDrag_'+name);
                    const index=focusableElement.findIndex(item=>item&&item.id==='popupLayer_'+name);if(index!==-1)focusableElement.splice(index,1);
                    hideScreenLocker(name);
					break;
				}
			}
		} else {
			if (openedPopups.length > 0) {
				$.closePopupLayer_now(openedPopups[openedPopups.length-1].name);
			}
		}
		return this;
	}
	
	$.reloadPopupLayer = function(name, callback) {
		if (name) {
			for (let i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name === name) {
					if (callback) {
						openedPopups[i].reloadSuccess = callback;
					}
					loadPopupLayerContent(openedPopups[i], false);
					break;
				}
			}
		} else {
			if (openedPopups.length > 0) {
				$.reloadPopupLayer(openedPopups[openedPopups.length-1].name);
			}
		}
		
		return this;
	}
	
	$.setPopupLayersPosition = function() {
		setPopupLayersPosition();
	}

	function setScreenLockerSize() {
		if (popupLayerScreenLocker) {
			$('#popupLayerScreenLocker').height($(window).height() + $(window).scrollTop());
			$('#popupLayerScreenLocker').width($(document.body).outerWidth(true) + $(window).scrollLeft());
		}
	}
	
	function checkIfItExists(name) {
		if (name) {
			for (let i = 0; i < openedPopups.length; i++) {
				if (openedPopups[i].name === name) {
					return true;
				}
			}
		}
		return false;
	}
	
	function showScreenLocker() {
		if ($("#popupLayerScreenLocker").length) {
			if (openedPopups.length === 1) {
				popupLayerScreenLocker = true;
				setScreenLockerSize();
				$('#popupLayerScreenLocker').fadeIn();
			}
			$('#popupLayerScreenLocker').css("z-index",parseInt(openedPopups.length === 1 ? 400000 : $("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 1);
		} else {
			$("body").append("<div id='popupLayerScreenLocker'><!-- --></div>");
			$("#popupLayerScreenLocker").css({
				position: "absolute",
				background: setupJqueryMPopups.screenLockerBackground,
				left: "0",
				top: "0",
				opacity: setupJqueryMPopups.screenLockerOpacity,
				display: "none"
			});
			showScreenLocker();
			$("#popupLayerScreenLocker").click(function() {
					//$.closePopupLayer();
			});
		}
	}
	
	function hideScreenLocker(popupName) {
		if (openedPopups.length === 0) {
			$('#popupLayerScreenLocker').fadeOut();
		} else {
			$('#popupLayerScreenLocker').css("z-index",parseInt($("#popupLayer_" + openedPopups[openedPopups.length - 1].name).css("z-index")) - 1);
		}
	}
	
	function setPopupLayersPosition(popupElement, animate) {
		if (popupElement) {
			let leftPosition=0, topPosition=0;
			if (popupElement.width() < $(window).width()) {
				leftPosition = (document.documentElement.offsetWidth - popupElement.width()) / 2;
			} else {
				leftPosition = $(window).scrollLeft() + 5;
			}
			if (popupElement.height() < $(window).height()) {
				topPosition = $(window).scrollTop() + ($(window).height() - popupElement.height()) / 2;
			} else {
				topPosition = $(window).scrollTop() + 5;
			}
			let positions = {
				left: leftPosition + "px",
				top: topPosition + "px"
			};
			if (!animate) {
				popupElement.css(positions);
			} else {
				popupElement.animate(positions, 50);
			}						
			setScreenLockerSize();
		} else {
			for (let i = 0; i < openedPopups.length; i++) {
				setPopupLayersPosition($("#popupLayer_" + openedPopups[i].name), true);
			}
		}
	}

	function showPopupLayerContent(popupObject, newElement, data) {
		let idElement = "popupLayer_" + popupObject.name;
		let zIndex = 999;
		if (newElement) {
			showScreenLocker();
			$("body").append("<div class='popshow' id='" + idElement + "'><!-- --></div>");
			let theTitle = $("<div/>").attr("id", idElement+"_title").addClass("title").html(popupObject.title);
			let theContent = $("<div/>").attr("id", idElement+"_content").addClass("content").html(data).css({"width":"100%", "height":"auto", "overflow":"hidden"});
			let theClose = "";
			if(!popupObject.no_close) theClose = $("<span id='"+idElement+"_close'>X</span>").css({"position":"absolute","right":"4px","top":"4px","font-weight":"bold","cursor":"pointer","font-size":"14px","padding":"0px 3px 0px 3px", "color":"#fff", "border":"1px solid #fff", "background-color":"#679cc6"}).appendTo(theTitle);
			data = theTitle.prop('outerHTML') + theContent.prop('outerHTML');
			zIndex = parseInt(openedPopups.length === 1 ? 500000 : $("#popupLayer_" + openedPopups[openedPopups.length - 2].name).css("z-index")) + 2;
		}	else {
			zIndex = $("#" + idElement).css("z-index");
		}
		let popupElement = $("#" + idElement);
		popupElement.css({
			"background-color": "#fff",
			display: "none",
			width: popupObject.width === "auto" ? "" : popupObject.width + "px",
			position: "absolute",
			"z-index": zIndex
		});
        if(newElement)popupElement.html(data);
        else $("#"+idElement+"_content").html(data);
		$("#"+idElement+"_close").click(function(){$.closePopupLayer(popupObject.name);});
		$("#"+idElement+"_close").hover(
				function(){
					$(this).css({"color":"#666", "border":"1px solid #666", "background-color":"#9acff9"});
				},
				function(){
					$(this).css({"color":"#fff", "border":"1px solid #fff", "background-color":"#679cc6"});
				}
			);
		$("#"+idElement+"_title").drag();
		
		if (newElement) {
			popupElement.fadeIn();
		} else {
			popupElement.show();
		}
		$(focusableElement[focusableElement.length-1]).focus();

		popupObject.success();
		if (popupObject.reloadSuccess) {
			popupObject.reloadSuccess();
			popupObject.reloadSuccess = null;
		}

        $("#"+idElement+"_content").height(popupObject.height);
        $("#"+idElement+"_content").css("overflow", "hidden");

		setPopupLayersPosition(popupElement);
	}
	
	function loadPopupLayerContent(popupObject, newElement) {
		if (newElement) {
            popupObject.focusBefore=document.activeElement;
			openedPopups.push(popupObject);
		}
		let data = "";
		switch(popupObject.type) {
			case "url":
				data = $('<iframe>').attr({src:popupObject.content,width:'100%',height:'100%',frameborder:0,scrolling:'auto'}).prop('outerHTML');
				break;
			case "img":
				data = $('<img>').attr({src:popupObject.content,width:'100%',alt:''}).prop('outerHTML');
				break;
			case "id":
				data = $(document.getElementById(popupObject.content)).html();
				break;
			default:
				data = popupObject.content;
				break;
		}
		showPopupLayerContent(popupObject, newElement, data);
	}
	
	$(window).resize(function(){
		setScreenLockerSize();
		setPopupLayersPosition();
	});
	
	
	$(window).scroll(function(){
		setPopupLayersPosition();
	});

	$(document).keydown(function(e){
		if (e.keyCode === 27 && !topNoClose()) {
			$.closePopupLayer();
		}
	});
})(jQuery);


jQuery.fn.drag = function(){
	return this.each(function(){
		let draging = false;
		let startLeft,startTop;
		let startX,startY;
		let drag_obj = $(this),ns='.popupDrag_'+drag_obj.parent().attr('id').replace(/^popupLayer_/,'');
        $(document).off(ns);drag_obj.off(ns);
		drag_obj.css('cursor','move');
		$(document).on('mousemove'+ns,function(event){
			let theObj = drag_obj.parent();
			if(theObj.is(":hidden")) return;
			if(draging !== true) return;
			let top_min = $(window).scrollTop();
			let left_min = $(window).scrollLeft();
			let top_max = Math.max(top_min,$(window).height() - theObj.height() + top_min - 2);
			let left_max = Math.max(left_min,$(window).width() - theObj.width() + left_min - 2);
			let deltaX = event.clientX - startX;
			let deltaY = event.clientY - startY;
			let left = startLeft + deltaX;
			let top = startTop + deltaY;
			if(top<top_min) top = top_min;
			if(top>top_max) top = top_max
			if(left<left_min) left = left_min
			if(left>left_max) left = left_max
			theObj.css({'left':left+'px', 'top':top+'px'});
			window.getSelection ? window.getSelection().removeAllRanges() : document.selection.empty();
		}).on('mouseup'+ns,function(event){
			draging = false;
		})
		drag_obj.on('mousedown'+ns,function(event){
			let offset = $(this).offset();
			startLeft = offset.left;
			startTop = offset.top;
			startX = event.clientX;
			startY = event.clientY;
			draging = true;
		});
	});
}

function showPop(name, title, type, content, width, height, no_close) {
	if(no_close==null) no_close = false;
	$.openPopupLayer({
		'name': name,
		'title': title,
		'type': type,
		'content': content,
		'width': width,
		'height': height,
		'no_close' : no_close
	});
}

// Resolve both legacy callback names and ordinary function values without inline JS.
function popupCallback(value) {
    if(typeof value==='function')return value;
    if(typeof value!=='string'||!/^[$\w]+(?:\.[$\w]+)*$/.test(value))return null;
    let context=window;const parts=value.split('.'),name=parts.pop();
    for(const key of parts){if(!context)return null;context=context[key];}
    return context&&typeof context[name]==='function'?context[name].bind(context):null;
}
function popupInfo(info,nl){return nl===false?String(info):String(info).replace(/[\r\n]+/g,'<br />');}
function popupDialog(title,info,buttons){
    $.closePopupLayer_now('info_show');
    $('#info_show > .info').html(info);
    const area=$('#info_show > .button').empty();
    buttons.forEach(button=>$('<button type="button">').text(button.label).appendTo(area));
    const size=setDialog();showPop('info_show',title,'id','info_show',size[0],size[1]);
    $('#popupLayer_info_show_content').addClass('info_show').find('.button > button').each(function(i){$(this).on('click',buttons[i].click);});
}
window.alert_org = window.alert;
window.alert = function(info,nl){
    if(info==null||String(info)==='')return;
    if(!$('#info_show').length)return window.alert_org(String(info));
    const label=window.language&&window.language.close||'关闭';
    popupDialog('MyStep - Alert',popupInfo(info,nl),[{label,click:()=>$.closePopupLayer_now('info_show')}]);
};
window.confirm_org = window.confirm;
window.confirm = function(info,func,btn,title,nl){
    if(info==null)info='';const callback=popupCallback(func);
    if(!callback||!$('#info_show').length)return window.confirm_org(String(info));
    const labels=btn||[' Yes ',' No '];
    popupDialog(title||'MyStep - Confirm',popupInfo(info,nl),labels.map((label,i)=>({label,click:()=>{$.closePopupLayer_now('info_show');callback(i);}})));
};
window.prompt_org = window.prompt;
window.prompt = function(info,func,value,title,btn,nl){
    const callback=popupCallback(func);
    if(!callback||!$('#info_show').length)return window.prompt_org(String(info??''),value===undefined?func:value);
    const labels=btn||[' Confirm ',' Cancel ',' Reset '],initial=String(value??'');
    popupDialog(title||'MyStep',popupInfo(info??'',nl)+'<br /><br /><input type="text" style="width:95%;" /><br /><br />',[
        {label:labels[0],click:()=>{const result=$('#popupLayer_info_show_content input').first().val();$.closePopupLayer_now('info_show');callback(result);}},
        {label:labels[1],click:()=>$.closePopupLayer('info_show')},
        {label:labels[2],click:()=>$('#popupLayer_info_show_content input').val(initial).trigger('select')}
    ]);
    $('#popupLayer_info_show_content input').val(initial).prop('defaultValue',initial).trigger('focus');
};

function setDialog() {
    $("#info_show").show();
    $("#info_show > .info").css({"overflow-x":"hidden","overflow-y":"auto","width":"auto","height":"auto"});
    let the_width = $("#info_show").width();
    let the_height = $("#info_show").height();
    if(the_width<300) the_width = 300;
    if(the_height<50) the_height = 50;
    if(the_width>600) {
        the_width = 600;
        $("#info_show > .info").css({"overflow-x":"scroll","width":the_width});
    }
    if(the_height>400) {
        the_height = 400;
        the_width += 24;
        $("#info_show > .info").css({"overflow-y":"scroll","width":the_width+10,"height":(the_height-40)});
    }
    $("#info_show").hide();
    return [the_width+20, the_height]
}

$(function(){
	$.setupJMPopups({
		screenLockerBackground: "#000",
		screenLockerOpacity: "0.4"
	});
	$("<div>").attr("id", "info_show").addClass("info_show").html('<div class="info"></div><div class="button"></div>').appendTo("body");
});
}
