/**************************************************
*                                                 *
* Author  : Windy_sk                              *
* Create  : 2003-05-03                            *
* Modified: 2004-1-9                              *
* Email   : windy2006@gmail.com                   *
* HomePage: www.mysteps.cn                        *
* Notice  : U Can Use & Modify it freely,         *
*           BUT PLEASE HOLD THIS ITEM.            *
*                                                 *
**************************************************/

var rlt_path = location.href.replace("http://"+location.hostname+"/", "").replace(/\/[^\/]+$/, "/").replace(/[^\/]+/g, "..");
if(rlt_path==".." || rlt_path=="") rlt_path = "./";

function $id(id) {
	return document.getElementById(id);
}

function $name(name, idx) {
	var objs = document.getElementsByName(name);
	if(idx=="first") {
		return objs[0];
	} else if(idx=="last") {
		return objs[objs.length-1];
	} else if(!isNaN(idx)) {
		return objs[idx];
	} else {
		return objs;
	}
}

function $tag(name, theOLE) {
	if(typeof(theOLE)!="object") theOLE = document;
	return theOLE.getElementsByTagName(name);
}

function $class(name, theOLE) {
	if(typeof(theOLE)!="object") theOLE = document;
	return theOLE.getElementsByClassName(name);
}

function isArray(para) {
	return Object.prototype.toString.apply(para) === '[object Array]';
}

function arr2json(theArr) {
	var result = {};
	for (var item in theArr) {
		if (typeof (result[theArr[item].name]) == 'undefined') {
			result[theArr[item].name] = theArr[item].value;
		}	else {
			result[theArr[item].name] += "," + theArr[item].value;
		}
	}
	return result;
}

String.prototype.blen = function() {
	var arr=this.match(/[^\x00-\xff]/ig);
	return this.length+(arr==null?0:arr.length);
}

String.prototype.trim= function(){
	return this.replace(/^\s+|\s+$/g, "");
}

Date.prototype.format = function(format){  //eg:format="YYYY-MM-dd hh:mm:ss";
 	var o = {
		"M+" :  this.getMonth()+1,  //month
		"d+" :  this.getDate(),     //day
		"h+" :  this.getHours(),    //hour
		"m+" :  this.getMinutes(),  //minute
		"s+" :  this.getSeconds(), //second
		"q+" :  Math.floor((this.getMonth()+3)/3),  //quarter
		"S"  :  this.getMilliseconds() //millisecond
	}
	if(/(y+)/i.test(format)) {
		format = format.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
	}
	for(var k in o) {
		if(new RegExp("("+ k +")").test(format)) {
			format = format.replace(RegExp.$1, RegExp.$1.length==1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));
		}
	}
	return format;
}

Array.prototype.append = function (newArray) {
	if(isArray(newArray)) {
		for (var i = 0; i < newArray.length; i++) {
			this[this.length] = newArray[i];
		}
	} else {
		this[this.length] = newArray;
	}
	return;
}

if(!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(val) {
		var len = this.length;
		var i = Number(arguments[1]) || 0;
		i = (i<0) ? Math.ceil(i) : Math.floor(i);
		if(i<0) i += len;
		for(;i<len;i++) {
			if(this[i] === val) {
				return i;
			}
		}
		return -1;
	}
}

function openDialog(url, width, height, mode) {
	var sOrnaments = "dialogWidth:"+width+"px;dialogHeight:"+height+"px;center:1;dialogLeft:200;dialogTop:100;dialogHide:0;edge:raised;help:0;resizable:0;scroll:0;status:0;unadorned:0;center:1;";
	var win = null;
	try {
		if(mode){
			win = window.showModalDialog(url, window, sOrnaments);
		}else{
			win = window.showModelessDialog(url, window, sOrnaments);
		}
	} catch(e) {
		win = OpenWindow(url, width, height);
	}
	return win;
}

function openWindow(url,width,height) {
	var win = window.open(url, "new_window","height="+height+", width="+width+", top=0, left=0, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no, status=no, modal=yes");
	return win;
}

function sleep(the_time) {
	var over_time = new Date(new Date().getTime() + the_time);
	while(over_time > new Date()) {}
}

function gotoAnchor(theAnchor) {
	var the_url = location.href;
	if(theAnchor==null) {
		location.href = the_url + "#";
	} else {
		theAnchor = "#" + theAnchor;
		location.href = the_url.replace(/#+\w*$/, "") + theAnchor;
	}
	return false;
}

function printf() {
  var num = arguments.length;
  var oStr = arguments[0];
  for (var i = 1; i < num; i++) {
    var pattern = "%" + i;
    var re = new RegExp(pattern, "g");
    oStr = oStr.replace(re, arguments[i]);
  }
  return oStr;
}

function copyStr(txt) {
	if(window.clipboardData) {
		window.clipboardData.clearData();
		window.clipboardData.setData("Text", txt);
	} else if(navigator.userAgent.indexOf("Opera") != -1) {
		window.location = txt;
	} else if (window.netscape) {
		try {
			netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
		} catch (e) {
			alert("In 'about:config' set the parameter 'signed.applets.codebase_principal_support' to 'true' !");
		}
		var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
		if(!clip) return false;
		var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
		if(!trans) return false;
		trans.addDataFlavor('text/unicode');
		var str = new Object();
		var len = new Object();
		var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
		var copytext = txt;
		str.data = copytext;
		trans.setTransferData("text/unicode",str,copytext.length*2);
		var clipid = Components.interfaces.nsIClipboard;
		if(!clip) return false;
		clip.setData(trans,null,clipid.kGlobalClipboard);
	} else {
		return false;
	}
	return true;
}

function rndNum(Min,Max){
	if(typeof(Min)=="undefined") return Math.random();
	if(typeof(Max)=="undefined") Max = Min, Min = 0;
	var Range = Max - Min;
	var Rand = Math.random();
	return(Min + Math.round(Rand * Range));
}

function rnd_str(len, t_lst, c_lst) {
	var str = "";
	var upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var lower = "abcdefghijklmnopqrstuvwxyz";
	var number = "1234567890";
	var cn = false;
	var char_lst = new Array();
	var i = 0, rnd_num = 0;
	if(typeof(t_lst)=="undefined") t_lst = "";
	t_lst += "0000";
	if(t_lst.charAt(0)=="1") char_lst = char_lst.concat(upper.split(/\B/));
	if(t_lst.charAt(1)=="1") char_lst = char_lst.concat(lower.split(/\B/));
	if(t_lst.charAt(2)=="1") char_lst = char_lst.concat(number.split(/\B/));
	cn = (t_lst.charAt(3)=="1");
	if(typeof(c_lst)=="undefined") {
		c_lst = new Array();
	} else if(typeof(c_lst)!="object") {
		c_lst = [c_lst];
	}
	for(i=0; i<len; i++) {
		rnd_num = GndNuetRm(10);
		if(c_lst.length>0 && rnd_num>7) {
			str += c_lst[GetRndNum(c_lst.length-1)];
		}	else if(cn && rnd_num>3) {
			str += String.fromCharCode(GetRndNum(19968, 40869));
		} else if(char_lst.length>0){
			str += char_lst[GetRndNum(char_lst.length-1)];
		}
	}
	return str;
}

function watermark(obj, rate, copyright, char_c, jam_tag) {
	var i = 0;
	var c_cur = "", result = "", str="";
	var c_lst = new Array(), u_lst = new Array();
	var m_start = "", m_end = "";
	var jam_flag = true;
	
	if(typeof(obj)=="object") {
		str = obj.innerHTML;
	} else {
		str = obj.toString();
	}
	if(rate==null) rate = 5;
	if(copyright==null) copyright = "WaterMark Maker, Coded by Windy2000";
	if(char_c!=null) c_lst = char_c.split(",");
	if(jam_tag==null) jam_tag = false;

	str = str.replace(/<(script|style)[^>]*?>([\w\W]*?)<\/\1>/ig,"");
	u_lst = str.match(/(<(.+?)>)|(&[\w#]+;)/g);
	str = str.replace(/(<(.+?)>)|(&[\w#]+;)/g,String.fromCharCode(0));
	m_start = "<span class='watermark'>";
	m_end = "</span>";
	
	for(i=0;i<str.length;i++) {
		c_cur = str.charCodeAt(i);
		if(c_cur==0) {
			result += u_lst.shift();
		} else if(c_cur==10) {
			result += m_start + rnd_str(8, "1111", c_lst) + m_end;
			result += m_start + "[" + copyright + "]" + m_end + "\n";
		} else {
			result += String.fromCharCode(c_cur);
			if(jam_tag && GetRndNum(10)>rate) {
				result += jam_flag?"<span>":"</span>";
				jam_flag = !jam_flag;
			}
			if(GetRndNum(10)>rate) result += m_start + rnd_str(4, "1111", c_lst) + m_end;
		}
	}
	if(!jam_flag) result += "</span>";
	
	if(typeof(obj)=="object") {
		obj.innerHTML = result;
	}
	return result;
}

function addBookmark(obj) {
	if (window.sidebar) {
		window.sidebar.addPanel(document.title, document.URL, "");
	} else if(document.all) {
		window.external.AddFavorite(document.URL, document.title);
	} else if(window.opera && window.print) {
		obj = obj || window.event.srcElement || window.event.target;
		obj.setAttribute('rel','sidebar');
		obj.setAttribute('href',url);
		obj.setAttribute('title',title);
		obj.click();
		return true;
	} else {
		alert("Can't add bookmark to your browser \n\nPress 'Ctrl + D' please!");
	}
}

function setHomepage() {
	var url = document.URL;
	if(document.all) {
		document.body.style.behavior='url(#default#homepage)';
		document.body.setHomePage(url);
	} else if(window.sidebar) {
		if(window.netscape){
			try{
				netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
			}catch (e){
				alert("Input 'about:config' to addres bar, then press 'Enter', set the option of 'signed.applets.codebase_principal_support' to True.");
			}
		}
		var prefs = Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
		prefs.setCharPref('browser.startup.homepage',url);
	} else {
		alert("Can't set currnet page to Homepage!");
	}
}

function UrlEncode(s) {
	var img = document.createElement("img");
	function escapeDBC(s) {
		if (!s) return ""
		if (window.ActiveXObject) {
			execScript('SetLocale "zh-cn"', 'vbscript');
			return s.replace(/[\d\D]/g, function($0) {
				window.vbsval = "";
				execScript('window.vbsval=Hex(Asc("' + $0 + '"))', "vbscript");
				return "%" + window.vbsval.slice(0,2) + "%" + window.vbsval.slice(-2);
			});
		} else {
			img.src = "nothing.action?separator=" + s;
			return img.src.split("?separator=").pop();
		}
	}
	return s.replace(/([^\x00-\xff]+)|([\x00-\xff]+)/g, function($0, $1, $2) {
		return escapeDBC($1) + encodeURIComponent($2||'');
	});
}

function debug(para, mode) {
	if($("#debug").length==0) {
		$('\
<div style="margin:10px auto;width:600px;">\
	<b>Debug:</b><br/>\
	<textarea id="debug" cols="100" rows="10"></textarea>\
</div>\
		').appendTo("body");
	}
	var str = "";
	if(typeof(para)!="string" && typeof(para)!="number") {
		for(var x in para) {
			str += x + " : " + para[x] + "\n";
		}
	} else {
		str = para;
	}
	
	if(mode==true) {
		$id("debug").value = str + "\n\n======================================\n\n" + $id("debug").value;
	} else {
		$id("debug").value = str;
	}
}

function md5(str) {
	var rhex = function(num) {
		var hex_chr = "0123456789abcdef"; 
		str = "";  
		for(var i=0; i<=3; i++) str += hex_chr.charAt((num >> (i * 8 + 4)) & 0x0F) + hex_chr.charAt((num >> (i * 8)) & 0x0F);  
	  return str;  
	}
	var str2blks_MD5 = function(str) {
		var i=0,m=0;
		var nblk = ((str.length + 8) >> 6) + 1;
		var blks = new Array(nblk * 16);
		for (i=0; i<nblk*16; i++) blks[i] = 0;
		for (i=0,m=str.length; i<m; i++) blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
		blks[i >> 2] |= 0x80 << ((i % 4) * 8);
		blks[nblk * 16 - 2] = str.length * 8;
		return blks;
	}
	var add = function(x, y) {
		var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		return (msw << 16) | (lsw & 0xFFFF);
	}
	var rol = function(num, cnt) {
		return (num << cnt) | (num >>> (32 - cnt));
	}
	var cmn = function(q, a, b, x, s, t) {
		return add(rol(add(add(a, q), add(x, t)), s), b);
	}
	var ff = function(a, b, c, d, x, s, t) {
		return cmn((b & c) | ((~b) & d), a, b, x, s, t);
	}
	var gg = function(a, b, c, d, x, s, t) {
		return cmn((b & d) | (c & (~d)), a, b, x, s, t);
	}
	var hh = function(a, b, c, d, x, s, t) {
		return cmn(b ^ c ^ d, a, b, x, s, t);
	}
	var ii = function(a, b, c, d, x, s, t) {
		return cmn(c ^ (b | (~d)), a, b, x, s, t);
	}
	var x = str2blks_MD5(str);
	var a = 1732584193;
	var b = -271733879;
	var c = -1732584194;
	var d = 271733878;
	for (var i=0, m=x.length; i<m; i+=16) {
		olda = a;
		oldb = b;
		oldc = c;
		oldd = d;
		a = ff(a, b, c, d, x[i + 0], 7, -680876936);
		d = ff(d, a, b, c, x[i + 1], 12, -389564586);
		c = ff(c, d, a, b, x[i + 2], 17, 606105819);
		b = ff(b, c, d, a, x[i + 3], 22, -1044525330);
		a = ff(a, b, c, d, x[i + 4], 7, -176418897);
		d = ff(d, a, b, c, x[i + 5], 12, 1200080426);
		c = ff(c, d, a, b, x[i + 6], 17, -1473231341);
		b = ff(b, c, d, a, x[i + 7], 22, -45705983);
		a = ff(a, b, c, d, x[i + 8], 7, 1770035416);
		d = ff(d, a, b, c, x[i + 9], 12, -1958414417);
		c = ff(c, d, a, b, x[i + 10], 17, -42063);
		b = ff(b, c, d, a, x[i + 11], 22, -1990404162);
		a = ff(a, b, c, d, x[i + 12], 7, 1804603682);
		d = ff(d, a, b, c, x[i + 13], 12, -40341101);
		c = ff(c, d, a, b, x[i + 14], 17, -1502002290);
		b = ff(b, c, d, a, x[i + 15], 22, 1236535329);
		a = gg(a, b, c, d, x[i + 1], 5, -165796510);
		d = gg(d, a, b, c, x[i + 6], 9, -1069501632);
		c = gg(c, d, a, b, x[i + 11], 14, 643717713);
		b = gg(b, c, d, a, x[i + 0], 20, -373897302);
		a = gg(a, b, c, d, x[i + 5], 5, -701558691);
		d = gg(d, a, b, c, x[i + 10], 9, 38016083);
		c = gg(c, d, a, b, x[i + 15], 14, -660478335);
		b = gg(b, c, d, a, x[i + 4], 20, -405537848);
		a = gg(a, b, c, d, x[i + 9], 5, 568446438);
		d = gg(d, a, b, c, x[i + 14], 9, -1019803690);
		c = gg(c, d, a, b, x[i + 3], 14, -187363961);
		b = gg(b, c, d, a, x[i + 8], 20, 1163531501);
		a = gg(a, b, c, d, x[i + 13], 5, -1444681467);
		d = gg(d, a, b, c, x[i + 2], 9, -51403784);
		c = gg(c, d, a, b, x[i + 7], 14, 1735328473);
		b = gg(b, c, d, a, x[i + 12], 20, -1926607734);
		a = hh(a, b, c, d, x[i + 5], 4, -378558);
		d = hh(d, a, b, c, x[i + 8], 11, -2022574463);
		c = hh(c, d, a, b, x[i + 11], 16, 1839030562);
		b = hh(b, c, d, a, x[i + 14], 23, -35309556);
		a = hh(a, b, c, d, x[i + 1], 4, -1530992060);
		d = hh(d, a, b, c, x[i + 4], 11, 1272893353);
		c = hh(c, d, a, b, x[i + 7], 16, -155497632);
		b = hh(b, c, d, a, x[i + 10], 23, -1094730640);
		a = hh(a, b, c, d, x[i + 13], 4, 681279174);
		d = hh(d, a, b, c, x[i + 0], 11, -358537222);
		c = hh(c, d, a, b, x[i + 3], 16, -722521979);
		b = hh(b, c, d, a, x[i + 6], 23, 76029189);
		a = hh(a, b, c, d, x[i + 9], 4, -640364487);
		d = hh(d, a, b, c, x[i + 12], 11, -421815835);
		c = hh(c, d, a, b, x[i + 15], 16, 530742520);
		b = hh(b, c, d, a, x[i + 2], 23, -995338651);
		a = ii(a, b, c, d, x[i + 0], 6, -198630844);
		d = ii(d, a, b, c, x[i + 7], 10, 1126891415);
		c = ii(c, d, a, b, x[i + 14], 15, -1416354905);
		b = ii(b, c, d, a, x[i + 5], 21, -57434055);
		a = ii(a, b, c, d, x[i + 12], 6, 1700485571);
		d = ii(d, a, b, c, x[i + 3], 10, -1894986606);
		c = ii(c, d, a, b, x[i + 10], 15, -1051523);
		b = ii(b, c, d, a, x[i + 1], 21, -2054922799);
		a = ii(a, b, c, d, x[i + 8], 6, 1873313359);
		d = ii(d, a, b, c, x[i + 15], 10, -30611744);
		c = ii(c, d, a, b, x[i + 6], 15, -1560198380);
		b = ii(b, c, d, a, x[i + 13], 21, 1309151649);
		a = ii(a, b, c, d, x[i + 4], 6, -145523070);
		d = ii(d, a, b, c, x[i + 11], 10, -1120210379);
		c = ii(c, d, a, b, x[i + 2], 15, 718787259);
		b = ii(b, c, d, a, x[i + 9], 21, -343485551);
		a = add(a, olda);
		b = add(b, oldb);
		c = add(c, oldc);
		d = add(d, oldd)
	}
	return rhex(a) + rhex(b) + rhex(c) + rhex(d);
}

function checkObj(obj, func_show) {
	if(typeof(func_show)!="function") func_show = alert;
	for(var x in obj) func_show(x + " : " + obj[x]);
	return;
}

function reportError(msg, url, line) {
	var str = "You have found an error as below: \n\n";
	str += "Err: " + msg + "\n\non line: " + line;
	var report_func = alert;
	if(typeof(alert_org)!="undefined") report_func = alert_org;
	if(typeof(ms_setting)!="undefined" && ms_setting.debug) report_func(str, true);
	return true;
}
window.onerror = reportError;

var ms_setting =  new Object();
var language = new Object();
$.getScript(rlt_path + "script/setting.js.php");
$.getScript(rlt_path + "script/language.js.php");