/**************************************************
*																								  *
* Author	: Windy_sk															*
* Create	: 2003-05-03														*
* Modified: 2007-12-9														  *
* Email   : windy2006@gmail.com                   *
* HomePage: www.mysteps.cn                        *
* Notice	: U Can Use & Modify it freely,				  *
*					 BUT PLEASE HOLD THIS ITEM.							*
*																								  *
**************************************************/

function setSlide() {
	$(".slide").each(function(){
		var items = null;
		var temp_num = "";
		obj = $(this);
		obj.parent().css("position", "relative");
		items = obj.find("DL");
		if(items.length>0) {
			temp_num = "";
			for(var j=0, m=items.length; j<m; j++){
				temp_num += "<li>"+(j+1)+"</li>";
			}
			temp_num = "<ul>"+temp_num+"</ul>";
			$("<div/>").addClass("slide-num").html(temp_num).appendTo(obj);
		}
		$("<div/>").addClass("slide-show").appendTo(obj);
		obj.slide = function(idx) {
			var max = $(this).find("dl").length;
			if(idx>=max) idx = 0;
			$(this).find("DL").hide();
			var item = $(this).find("DL").eq(idx);
			$(this).find(".slide-show").html(item.find("dt").html());
			$(this).find(".slide-num li").removeClass("selected");
			$(this).find(".slide-num li").eq(idx).addClass("selected");
			item.animate({opacity: 'toggle'}, 2000);
			var self = this;
			$(this).data("timeout", setTimeout(function(){self.slide(idx+1)}, 5000));
		}
		obj.find("li").click(function(){
			clearTimeout(obj.data("timeout"));
			obj.slide(parseInt(this.innerHTML)-1);
		});
		obj.slide(0);
	});
}

function setSwitch() {
	$(".box > .title > span").mouseover(function(){
		if(this.className=="highlight") return;
		var theParent = $(this).parent().parent();
		var theObjs = theParent.find(".title > span");
		theObjs.removeClass("highlight");
		$(this).addClass("highlight");
		theParent.find(".content").hide();
		for(var i=theObjs.length-1; i>=0; i--) {
			if(theObjs.eq(i).hasClass("highlight")) {
				theParent.find(".content").eq(i).show();
				break;
			}
		}
		$(this).parent().find(".more").find("a").attr("href", "list.php?cat="+$(this).text());
	});
}

function setList() {
	if($(".catList").length==0) return;
	$(".catList > li:has(ul)").bind('click', function(e){
		if(e.target.tagName.toLowerCase()!="li") return true;
		$(this).children().filter("ul").slideToggle(500);
		if(e && e.preventDefault) {
			e.preventDefault();
		} else {
			window.event.returnValue = false;
		}
		return false;
	});
}

function loadingShow(info) {
	if($("#bar_loading").length==0) {
		var cssText = "display:none;position:absolute;top:0px;left:0px;z-index:999;color:#333333;font-size:24px;font-weight:bold;line-height:48px;border:1px #999999 solid;padding:20px 30px 10px 30px;background-color:#eeeeee;text-align:center;";
		$("<div>").attr("id", "bar_loading").cssText(cssText).append($('<img src="'+rlt_path+'images/loading.gif" style="width:400px;height:10px">')).append("<br />").append($("<span>")).appendTo("body");
	}
	if($("#screenLocker").length>0) {
		$("#screenLocker").remove();
		$("#bar_loading").hide();
	} else {
		$("<div id='screenLocker'><!-- --></div>").css({
					position: "absolute",
					background: "#333333",
					left: "0",
					top: "0",
					opacity: "0.8",
					display: "none"
				}).appendTo("body");
		
		$('#screenLocker').height($(window).height() + $(window).scrollTop());
		$('#screenLocker').width($(document.body).outerWidth(true) + $(window).scrollLeft());
		$('#screenLocker').fadeIn();
		
		var theTop = ($(document.body).height() - $("#bar_loading").height())/2 + $(document.body).scrollTop();
		var theLeft = ($(document.body).width() - $("#bar_loading").width())/2 + $(document.body).scrollLeft();
		$("#bar_loading > img").attr("href", rlt_path+"images/loading.gif");
		$("#bar_loading").css({"opacity":"0.7", "top":theTop, "left":theLeft});
		if(info==null) info = language.ajax_sending;
		$("#bar_loading > span").html(info);
		$("#bar_loading").show();
	}
	return true;
}

function showSubMenu(theObj) {
	$(theObj).find("a[catid]").each(function(){
		var cur_obj = $(this);
		var cat_id = cur_obj.attr("catid");
		$.post("/ajax.php?func=subcat&return=json", {'id':cat_id}, function(result) {
			if(result!=null) {
				var listObj = $("<ul>").attr("id", "subcat_"+cat_id).addClass("subcat").css("opacity", "0.8");
				for(var i=0,m=result.length;i<m;i++) {
					listObj.append($('<li><a href="'+result[i].link+'">'+result[i].name+'</a></li>'));
				}
				cur_obj.after(listObj);
				cur_obj.parent().hover(function(){
					var pos = cur_obj.position();
					cur_obj.addClass("highlight");
					$(this).children("ul").css({"top":(pos.top+$(this).height()),"left":pos.left}).show();
				},function(){
					cur_obj.removeClass("highlight");
					$(this).children("ul").hide();
				});
			}
		}, 'json');
		
	});
}

function anchorShow(position,offset_top){
	if(position==null) position = {"bottom":"0px","right":"0px"};
	if(offset_top==null) offset_top = 0;
	$("<div>").attr("id", "anchor_show").html('<div>Top</div><div></div><div>Close</div>').css({"position":"fixed","width":"150px","cursor":"pointer"}).css(position).appendTo("body");
	$("#anchor_show > div").css({"background-color":"#7d0a2c","padding":"5px","border":"1px #fff solid","text-align":"center","color":"#fff"});
	$("#anchor_show > div:eq(1)").css({"background-color":"#222","padding":"0px"});
	$("#anchor_show > div:eq(0)").click(function(){
		var theURL = location.href;
		theURL = theURL.replace(/#.*$/, "");
		location.href = theURL + "#";
	});
	$("#anchor_show > div:eq(2)").click(function(){
		$("#anchor_show > div:eq(1)").slideToggle("fast",function(){
			var theObj = $(this).next();
			if(theObj.text().toLowerCase()=="open") {
				theObj.text("Close");
			} else {
				theObj.text("Open");
			}
		});
	});
	var anchor_list = $("a[name^=subtitle_]");
	var anchor_cur = null;
	for(var i=0,m=anchor_list.length;i<m;i++) {
		anchor_cur = $(anchor_list.get(i));
		$("<p>").attr("idx",i).html(anchor_cur.parent().text()).click(function(){
			location.href = location.href.replace(/#.*/, "")+"#subtitle_"+$(this).attr("idx");
		}).css({"border-bottom":"#333 1px solid","color":"#fff","padding":"5px"}).hover(
			function() {
				$(this).css("background-color","#ff0052");
			},
			function() {
				if($(this).attr("highlight")=="1") return;
				$(this).css("background-color","");
			}
		).appendTo("#anchor_show > div:eq(1)");
	}
	
	$(window).scroll(function(){
		var the_top = $("#anchor_show").position().top;
		$("#anchor_show > div:eq(1) > p").css("background-color","").attr("highlight", "0");
		for(var i=anchor_list.length-1;i>=0;i--) {
			if(the_top+offset_top>=$(anchor_list.get(i)).position().top) {
				$("#anchor_show > div:eq(1) > p:eq("+i+")").css("background-color","#ff0052").attr("highlight", "1");
				break;
			}
		}
	});
	$(window).scrollTop(1);
}

$(function() {
	setSlide();
	setSwitch();
	setList();
	showSubMenu($("#top_nav"));
});