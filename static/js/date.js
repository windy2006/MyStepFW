function CalConv() {
	var FIRSTYEAR = 1998;
	var LASTYEAR = 2031;
	var today = new Date();
	var SolarYear = today.getFullYear();
	var SolarMonth = today.getMonth() + 1;
	var SolarDate = today.getDate();
	var Weekday = today.getDay();
	var LunarCal = [
		new tagLunarCal( 27, 5, 3, 43, 1, 0, 0, 1, 0, 0, 1, 1, 0, 1, 1, 0, 1 ),
		new tagLunarCal( 46, 0, 4, 48, 1, 0, 0, 1, 0, 0, 1, 0, 1, 1, 1, 0, 1 ), /* 88 */
		new tagLunarCal( 35, 0, 5, 53, 1, 1, 0, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1 ), /* 89 */
		new tagLunarCal( 23, 4, 0, 59, 1, 1, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 42, 0, 1, 4, 1, 1, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 31, 0, 2, 9, 1, 1, 0, 1, 1, 0, 1, 0, 0, 1, 0, 1, 0 ),
		new tagLunarCal( 21, 2, 3, 14, 0, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0, 1 ), /* 93 */
		new tagLunarCal( 39, 0, 5, 20, 0, 1, 0, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 28, 7, 6, 25, 1, 0, 1, 0, 1, 0, 1, 0, 1, 1, 0, 1, 1 ),
		new tagLunarCal( 48, 0, 0, 30, 0, 0, 1, 0, 0, 1, 0, 1, 1, 1, 0, 1, 1 ),
		new tagLunarCal( 37, 0, 1, 35, 1, 0, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1, 1 ), /* 97 */
		new tagLunarCal( 25, 5, 3, 41, 1, 1, 0, 0, 1, 0, 0, 1, 0, 1, 0, 1, 1 ),
		new tagLunarCal( 44, 0, 4, 46, 1, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 1 ),
		new tagLunarCal( 33, 0, 5, 51, 1, 0, 1, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 22, 4, 6, 56, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0 ), /* 101 */
		new tagLunarCal( 40, 0, 1, 2, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0 ),
		new tagLunarCal( 30, 9, 2, 7, 0, 1, 0, 1, 0, 1, 0, 1, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 49, 0, 3, 12, 0, 1, 0, 0, 1, 0, 1, 1, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 38, 0, 4, 17, 1, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1, 1, 0 ), /* 105 */
		new tagLunarCal( 27, 6, 6, 23, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 1, 1 ),
		new tagLunarCal( 46, 0, 0, 28, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 1, 0 ),
		new tagLunarCal( 35, 0, 1, 33, 0, 1, 1, 0, 1, 0, 0, 1, 0, 0, 1, 1, 0 ),
		new tagLunarCal( 24, 4, 2, 38, 0, 1, 1, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1 ), /* 109 */
		new tagLunarCal( 42, 0, 4, 44, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 31, 0, 5, 49, 1, 0, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0 ),
		new tagLunarCal( 21, 2, 6, 54, 0, 1, 0, 1, 0, 1, 0, 1, 1, 0, 1, 0, 1 ),
		new tagLunarCal( 40, 0, 0, 59, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1, 1, 0, 1 ), /* 113 */
		new tagLunarCal( 28, 6, 2, 5, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 1, 1, 0 ),
		new tagLunarCal( 47, 0, 3, 10, 1, 0, 1, 0, 0, 1, 0, 0, 1, 1, 1, 0, 1 ),
		new tagLunarCal( 36, 0, 4, 15, 1, 1, 0, 1, 0, 0, 1, 0, 0, 1, 1, 0, 1 ),
		new tagLunarCal( 25, 5, 5, 20, 1, 1, 1, 0, 1, 0, 0, 1, 0, 0, 1, 1, 0 ), /* 117 */
		new tagLunarCal( 43, 0, 0, 26, 1, 1, 0, 1, 0, 1, 0, 1, 0, 0, 1, 0, 1 ),
		new tagLunarCal( 32, 0, 1, 31, 1, 1, 0, 1, 1, 0, 1, 0, 1, 0, 1, 0, 0 ),
		new tagLunarCal( 22, 3, 2, 36, 0, 1, 1, 0, 1, 0, 1, 1, 0, 1, 0, 1, 0 )
	];
	/* 民国年月日 Codes by / */
	SolarCal = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];
	SolarDays = [ 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365, 396, 0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335, 366, 397 ];
	
	if ( SolarYear <= FIRSTYEAR || SolarYear > LASTYEAR ) return 1;
	sm = SolarMonth - 1;
	if ( sm < 0 || sm > 11 ) return 2;
	leap = GetLeap( SolarYear );
	if ( sm == 1 ) d = leap + 28;
	else d = SolarCal[sm];
	if ( SolarDate < 1 || SolarDate > d ) return 3;
	y = SolarYear - FIRSTYEAR;
	acc = SolarDays[ leap*14 + sm ] + SolarDate;
	kc = acc + LunarCal[y].BaseKanChih;
	Kan = kc % 10;
	Chih = kc % 12;
	
	Age = kc % 60;
	if ( Age < 22 ) Age = 22 - Age;
	else Age = 82 - Age;
	
	if ( acc <= LunarCal[y].BaseDays ) {
		y--;
		LunarYear = SolarYear - 1;
		leap = GetLeap( LunarYear );
		sm += 12;
		acc = SolarDays[leap*14 + sm] + SolarDate;
	} else {
		LunarYear = SolarYear;
	}
	l1 = LunarCal[y].BaseDays;
	for ( i=0; i<13; i++ ) {
		l2 = l1 + LunarCal[y].MonthDays[i] + 29;
		if ( acc <= l2 ) break;
		l1 = l2;
	}
	LunarMonth = i + 1;
	LunarDate = acc - l1;
	im = LunarCal[y].Intercalation;
	if ( im != 0 && LunarMonth > im ) {
		LunarMonth--;
		//if ( LunarMonth == im ) LunarMonth = -im;
	}
	if ( LunarMonth > 12 ) LunarMonth -= 12;
	today=new Date();
	function initArray(){
	this.length=initArray.arguments.length
	for(var i=0;i<this.length;i++)
	this[i+1]=initArray.arguments[i] }
	var d=new initArray("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
	document.write("", SolarYear,"年",today.getMonth()+1,"月",today.getDate(),"日&nbsp;",d[today.getDay()+1],"");
	months = ["一","二","三","四","五","六","七","八","九","十","十一","十二"];
	days = ["初一","初二","初三","初四","初五","初六","初七","初八","初九","初十","十一","十二","十三","十四","十五","十六","十七","十八","十九","二十","廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十"];
	document.write( "&nbsp;农历"+CnEra(LunarYear-1864)+"年"+months[LunarMonth-1]+"月" + days[LunarDate-1] + " （"+SolarTerm(today)+"）");
	return 0; 
}
function GetLeap( year ) {
	/* 是否有闰年, 0 平年, 1 闰年 */
	if ( year % 400 == 0 )	return 1;
	else if ( year % 100 == 0 )	return 0;
	else if ( year % 4 == 0 )	return 1;
	else	return 0;
}
function tagLunarCal( d, i, w, k, m1, m2, m3, m4, m5, m6, m7, m8, m9, m10, m11, m12, m13) {
	this.BaseDays = d; 
	this.Intercalation = i; /* 0代表此年沒有闰月 */
	this.BaseWeekday = w; /* 民国1月1日星期減 1 */
	this.BaseKanChih = k; /* 民国1月1日干支序号减 1 */
	this.MonthDays = [ m1, m2, m3, m4, m5, m6, m7, m8, m9, m10, m11, m12, m13 ]; /* 此農曆年每月之大小, 0==小月(29日), 1==大月(30日) */
}
function CnEra(YYYY){
	var Tiangan=new Array("甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
	//var Dizhi=new Array("子(鼠)","丑(牛)","寅(虎)","卯(兔)","辰(龙)","巳(蛇)",
	                    //"午(马)","未(羊)","申(猴)","酉(鸡)","戌(狗)","亥(猪)");
	var Dizhi=new Array("子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥");
	return Tiangan[YYYY%10]+Dizhi[YYYY%12];
}
function SolarTerm(DateGL){
	var SolarTermStr=new Array(
				"小寒","大寒","立春","雨水","惊蛰","春分",
				"清明","谷雨","立夏","小满","芒种","夏至",
				"小暑","大暑","立秋","处暑","白露","秋分",
				"寒露","霜降","立冬","小雪","大雪","冬至");
	var DifferenceInMonth=new Array(
				1272060,1275495,1281180,1289445,1299225,1310355,
				1321560,1333035,1342770,1350855,1356420,1359045,
				1358580,1355055,1348695,1340040,1329630,1318455,
				1306935,1297380,1286865,1277730,1274550,1271556);
	var DifferenceInYear=31556926;
	var BeginTime=new Date(1901/1/1);
	BeginTime.setTime(947120460000);
	for(;DateGL.getYear()<BeginTime.getYear();){
			BeginTime.setTime(BeginTime.getTime()-DifferenceInYear*1000);
	}
	for(;DateGL.getYear()>BeginTime.getYear();){
			BeginTime.setTime(BeginTime.getTime()+DifferenceInYear*1000);
	}
	for(var M=0;DateGL.getMonth()>BeginTime.getMonth();M++){
			BeginTime.setTime(BeginTime.getTime()+DifferenceInMonth[M]*1000);
	}
	if(DateGL.getDate()>BeginTime.getDate()){
			BeginTime.setTime(BeginTime.getTime()+DifferenceInMonth[M]*1000);
			M++;
	}
	if(DateGL.getDate()>BeginTime.getDate()){
			BeginTime.setTime(BeginTime.getTime()+DifferenceInMonth[M]*1000);
			M==23?M=0:M++;
	}
	var JQ;
	if(DateGL.getDate()==BeginTime.getDate()){
		JQ="今天是<b>"+SolarTermStr[M] + "</b>";
	}else if(DateGL.getDate()==BeginTime.getDate()-1){
		JQ="明天是<b>"+SolarTermStr[M] + "</b>";
	}else if(DateGL.getDate()==BeginTime.getDate()-2){
		JQ="后天是<b>"+SolarTermStr[M] + "</b>";
	}else{
		JQ=""
		if(DateGL.getMonth()==BeginTime.getMonth()){
				JQ+="本月";
		}else{
			JQ+="下月";
		}
		JQ+=BeginTime.getDate()+"日"+"<b>"+SolarTermStr[M]+"</b>";
	}
	return JQ;
}
CalConv();