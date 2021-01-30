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
	let popupLayer_noClose = false;

	$.setupJMPopups = function(settings) {
		setupJqueryMPopups = jQuery.extend(setupJqueryMPopups, settings);
		return this;
	}

	$.openPopupLayer = function(settings) {
		if (typeof(settings.name) != "undefined" && !checkIfItExists(settings.name)) {
            settings = jQuery.extend({
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
			popupLayer_noClose = settings.no_close;
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
					thisPopup.beforeClose();
					$("#popupLayer_" + name).fadeOut('slow', function(){
						$("#popupLayer_" + name).remove();
						focusableElement.pop();
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
					$('#popupLayerScreenLocker').hide();
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
			if(popupLayer_noClose===false) theClose = $("<span id='"+idElement+"_close'>X</span>").css({"position":"absolute","right":"4px","top":"4px","font-weight":"bold","cursor":"pointer","font-size":"14px","padding":"0px 3px 0px 3px", "color":"#fff", "border":"1px solid #fff", "background-color":"#679cc6"}).appendTo(theTitle);
			data = theTitle.outerHTML() + theContent.outerHTML();
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
		popupElement.html(data);
		$("#"+idElement+"_close").click(function(){$.closePopupLayer();});
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
			openedPopups.push(popupObject);
		}
		let data = "";
		switch(popupObject.type) {
			case "url":
				data = '<iframe src="'+popupObject.content+'" width="100%" height="100%" marginwidth="0" marginheight="0" hspace="0" vspace="0" frameborder="0" scrolling="auto" ALLOWTRANSPARENCY="true"></iframe>';
				break;
			case "img":
				data = "<img src="+popupObject.content+" width='100%' alt='' />";
				break;
			case "id":
				data = $("#" + popupObject.content).html();
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
		if (e.keyCode === 27 && popupLayer_noClose===false) {
			$.closePopupLayer();
		}
	});
})(jQuery);


jQuery.fn.drag = function(){
	return this.each(function(){
		let draging = false;
		let startLeft,startTop;
		let startX,startY;
		let drag_obj = $(this);
		drag_obj.css('cursor','move');
		$(document).mousemove(function(event){
			let theObj = drag_obj.parent();
			if(theObj.is(":hidden")) return;
			if(draging !== true) return;
			let top_min = $(window).scrollTop();
			let left_min = $(window).scrollLeft();
			let top_max = $(window).height() - theObj.height() + top_min - 2;
			let left_max = $(window).width() - theObj.width() + left_min - 2;
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
		}).mouseup(function(event){
			draging = false;
		})
		drag_obj.mousedown(function(event){
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

window.alert_org = window.alert;
window.alert = function(info, nl) {
    if(info.length===0) return;
    if($("#info_show").length===0) {
        alert_org(info);
        return;
    }
    let func = "void";
    let btn = [language.close];
    if(nl!==false) nl = true;
    confirm(info, func, btn, "MyStep - Alert", nl);
}

window.confirm_org = window.confirm;
window.confirm = function(info, func, btn, title, nl) {
    if(typeof(info)!="string") info = info.toString();
    if(info.length===0) return;
    if(func==null || $("#info_show").length===0) return confirm_org(info);
    if(title==null) title = "MyStep - Confirm";
    if(btn==null) btn = [" Yes ", " No "];
    if(nl!==false) nl = true;
    if(nl===true) info = info.replace(/[\r\n]+/g,"<br />");
    $("#info_show > .info").html(info);
    $("#info_show > .button").html("");
    for(let i=0,m=btn.length;i<m;i++) {
        $("#info_show > .button").append($("<button>").html(btn[i]).attr("onclick", "$.closePopupLayer_now();"+func+"("+i+");"));
    }
    let theSize = setDialog();
    showPop('info_show', title, 'id', 'info_show', theSize[0], theSize[1]);
    $("#popupLayer_info_show_content").addClass("info_show");
}

window.prompt_org = window.prompt;
window.prompt = function(info, func, value, title, btn, nl) {
    if(typeof(info)!=="string") info = info.toString();
    if(info.length===0) return;
    if(func==null || $("#info_show").length===0) return prompt_org(info, value);
    if(nl!==false) nl = true;
    if(nl===true) info = info.replace(/[\r\n]+/g,"<br />");
    if(value===null) value = "";
    if(title===null) title = "MyStep";
    if(btn===null) btn = [" Confirm ", " Cancel ", " Reset "];
    info += '<br /><br /><input type="text" style="width:95%;" value="' + value + '" /><br /><br />';
    $("#info_show > .info").html(info);
    $("#info_show > .button").html("");
    $("#info_show > .button").append($("<button>").html(btn[0]).attr("onclick", "let theValue=$('#popupLayer_info_show_content').find('input').first().val();$.closePopupLayer_now();"+func+"(theValue);"));
    $("#info_show > .button").append($("<button>").html(btn[1]).attr("onclick", "$.closePopupLayer();"));
    $("#info_show > .button").append($("<button>").html(btn[2]).attr("onclick", "$('#popupLayer_info_show_content').find('input').val($('#popupLayer_info_show_content').find('input').get(0).defaultValue);$('#popupLayer_info_show_content').find('input').get(0).select()"));
    let theSize = setDialog();
    showPop('info_show', title, 'id', 'info_show', theSize[0], theSize[1]);
    $("#popupLayer_info_show_content").addClass("info_show");
}

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