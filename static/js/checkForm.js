/**************************************************
* Functions For Form Check                        *
* Author  : Windy_sk                              *
* Create  : 2003-05-03                            *
* Modified: 2004-1-9                              *
* Email   : windy2006@gmail.com                   *
* HomePage: www.mysteps.cn                        *
* Notice  : U Can Use & Modify it freely,         *
*           BUT PLEASE HOLD THIS ITEM.            *
*                                                 *
**************************************************/

function highlightIt(the_obj){
    var tag_name = the_obj.tagName.toLowerCase();
    (tag_name=="input") ? the_obj.select() : the_obj.focus();
    $(the_obj).addClass('is-invalid').one("keypress", function(){
        $(this).removeClass('is-invalid');
    }).one("change", function(){
        $(this).removeClass('is-invalid');
    });
    $(window).scrollTop($(the_obj).offset().top-100);
    return;
}

function checkForm(the_form, myChecker){
	var flag = false;
	if(typeof(myChecker)=="function") {
		flag = myChecker(the_form);
	} else {
		flag = true;
	}
	if(flag==false) return false;
	
	var obj_list = new Array();
	var the_obj = null;
	var the_value, the_need, the_len;
	var tag_name = "";
	obj_list = $(the_form).find("input,select,textarea");
	for(var i=0;i<obj_list.length;i++){
		the_obj = obj_list[i];
		tag_name = the_obj.tagName.toLowerCase();
		if(typeof(the_obj.getAttribute)=="undefined") continue;
		the_value = the_obj.value;
		the_value = (the_value==null?"":the_value.replace(/(^\s*)|(\s*$)/g,""));
		the_need = the_obj.getAttribute("need");
		the_len = the_obj.getAttribute("len");
		the_length = typeof(String.prototype.blen)=="undefined"?the_value.length:the_value.blen();
		if(the_len!=null) {
			if(the_len.match(/^(\d+)(\!)?$/)) {
				if(RegExp.$2=="!") {
					if(the_length != RegExp.$1) {
						alert(language.checkform_lenth_limit1_2.printf(RegExp.$1));
						highlightIt(the_obj);
						return false;
					}
				} else {
					if(the_length > the_len) {
						alert(language.checkform_lenth_limit1.printf(the_len));
						highlightIt(the_obj);
						return false;
					}
				}
			} else {
				the_len = the_len.split("-");
				if(the_len.length==2 && the_len[0].match(/^\d+$/) && the_len[1].match(/^\d+$/)) {
					if(the_length < the_len[0] || the_length > the_len[1]) {
						if(the_len[0]==the_len[1]) {
							alert(language.checkform_lenth_limit2.printf(the_len[0]));
						} else {
							alert(language.checkform_lenth_limit2.printf(the_len.join(" - ")));
						}
						highlightIt(the_obj);
						return false;
					}
				}
			}
		}
		if(the_need!=null) {
			if(the_need.search("_")>=0 && the_value=="") continue;
			the_need = the_need.toLowerCase().replace("_", "");
		}
		switch(the_need){
			case null:
				break;
			case "email":
				if(!/^[\w\-\.]+@([\w\-]+\.)+[a-z]{2,4}$/i.test(the_value)) {
					alert(language.checkform_err_email);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "url":
				//if(!/^(http|ftp):\/\/(\w+:\w+@)?([\w\-]+\.)+\w+(\/[\w\.\-]*)*(\?[\w&=%\-,]+)?#*$/ig.test(the_value)) {
				if(!/^(http|ftp):\/\/(\w+:\w+@)?([\w\-]+\.)+\w+(\:\d+)?.*/i.test(the_value)) {
					alert(language.checkform_err_url);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "digital":
				if(!/^\d+$/.test(the_value)) {
					alert(language.checkform_err_digital);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "number":
				if(!/^[\-+]?\d+(\.\d+)?$/.test(the_value)) {
					alert(language.checkform_err_number);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "alpha":
				if(!/^[a-z_\d]+$/i.test(the_value)) {
					alert(language.checkform_err_alpha);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "word":
				if(!/^[\w\s\-\(\)\.,]+$/i.test(the_value)) {
					alert(language.checkform_err_word);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "name":
				if(!/^[\w\-\u4e00-\u9FA5 \uf900-\uFA2D\(\)]+$/.test(the_value)) {
					alert(language.checkform_err_name);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "date":
				if(!/^\d{4}([\/\-])\d{1,2}\1\d{1,2}(\s\d{1,2}:\d{1,2}:\d{1,2})?$/.test(the_value)) {
					alert(language.checkform_err_date);
					highlightIt(the_obj);
					return false;
				} else {
					the_value = the_value.replace(/\s.+$/,"");
					var the_list = the_value.split(/[\-\/]/g);
					var cur_date = new Date((the_list[0]-0), (the_list[1]-1), (the_list[2]-0));
					if(cur_date.getDate()!=(the_list[2]-0) || cur_date.getMonth()!=(the_list[1]-1) ) {
						alert(language.checkform_err_date2);
						highlightIt(the_obj);
						return false;
					}
				}
				break;
			case "time":
				if(!/^\d{1,2}:\d{1,2}(:\d{1,2})?$/.test(the_value)) {
					alert(language.checkform_err_time);
					highlightIt(the_obj);
					return false;
				} else {
					var the_list = the_value.split(/:/g);
					if(parseInt(the_list[0])>23 || parseInt(the_list[1])>59) {
						alert(language.checkform_err_time2);
						highlightIt(the_obj);
						return false;
					} else {
						if(the_list.length==3) {
							if(parseInt(the_list[2])>59) {
								alert(language.checkform_err_time2);
								highlightIt(the_obj);
								return false;
							}
						}
					}
				}
				break;
			case "tel":
			case "fax":
				if(!/^([\d]{3,5}\-)?[\d]{6,11}(\-[\d]{1,5})?$/.test(the_value)) {
					alert(language.checkform_err_tel);
					highlightIt(the_obj);
					return false;
				}
				break;
			case "":
				if(the_value.replace(/\s/g,"").length==0) {
					alert(language.checkform_noempty);
					highlightIt(the_obj);
					return false;
				}
				break;
			default:
				try{
					var re=new RegExp(the_need,"i");
					if(!re.test(the_value)) {
						alert(language.checkform_err);
						highlightIt(the_obj);
						return false;
					}
				} catch(e){}
				break;
		}
	}
	return true;
}