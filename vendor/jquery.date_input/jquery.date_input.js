/**
 * 框架调用：date_input(options|string)
 * 功能说明：返回原选择集；字符串支持 show/hide/destroy。日期写回原输入框并触发 change；重复传入选项重建日历。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/*
Date Input 1.2.1
Requires jQuery version: >= 1.2.6

Copyright (c) 2007-2008 Jonathan Leighton & Torchbox Ltd

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/

DateInput = (function($) {
	function DateInput(el, opts) {
		if (typeof(opts) != "object") opts = {};
		$.extend(this, DateInput.DEFAULT_OPTS, opts);

		this.input = $(el);
        this.ns='.dateInput'+($.data(document,'dateInputSerial')||0);
        $.data(document,'dateInputSerial',($.data(document,'dateInputSerial')||0)+1);
        this.start_of_week=((Number(this.start_of_week)||0)%7+7)%7;
		this.bindMethodsToObj("show", "hide", "hideIfClickOutside", "keydownHandler", "selectDate");

		this.build();
		this.selectDate();
		this.hide();
	}
	DateInput.DEFAULT_OPTS = {
		month_names: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
		short_month_names: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
		short_day_names: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
		start_of_week: 0
	};
	DateInput.prototype = {
		build: function() {
			let monthNav = $('<p class="month_nav">' +
				'<span class="button prev" title="[Page-Up]">&#171;</span>' +
				' <span class="month_name"></span> ' +
				'<span class="button next" title="[Page-Down]">&#187;</span>' +
				'</p>');
			this.monthNameSpan = $(".month_name", monthNav);
			$(".prev", monthNav).click(this.bindToObj(function() { this.moveMonthBy(-1); }));
			$(".next", monthNav).click(this.bindToObj(function() { this.moveMonthBy(1); }));

			let yearNav = $('<p class="year_nav">' +
				'<span class="button prev" title="[Ctrl+Page-Up]">&#171;</span>' +
				' <span class="year_name"></span> ' +
				'<span class="button next" title="[Ctrl+Page-Down]">&#187;</span>' +
				'</p>');
			this.yearNameSpan = $(".year_name", yearNav);
			$(".prev", yearNav).click(this.bindToObj(function() { this.moveMonthBy(-12); }));
			$(".next", yearNav).click(this.bindToObj(function() { this.moveMonthBy(12); }));

			let nav = $('<div class="main_nav"></div>').append(monthNav, yearNav);

			let tableShell = "<table><thead><tr>";
			$(this.adjustDays(this.short_day_names)).each(function() {
				tableShell += "<th>" + this + "</th>";
			});
			tableShell += "</tr></thead><tbody></tbody></table>";

			this.dateSelector = this.rootLayers = $('<div class="date_selector"></div>').append(nav, tableShell).appendTo('body');

			this.tbody = $("tbody", this.dateSelector);

			this.input.on('change'+this.ns,this.bindToObj(function() { this.selectDate(); }));
			this.selectDate();
		},

		selectMonth: function(date) {
			let newMonth = new Date(date.getFullYear(), date.getMonth(), 1);

			if (!this.currentMonth || !(this.currentMonth.getFullYear() === newMonth.getFullYear() &&
				this.currentMonth.getMonth() === newMonth.getMonth())) {

				this.currentMonth = newMonth;

				let rangeStart = this.rangeStart(date), rangeEnd = this.rangeEnd(date);
				let numDays = this.daysBetween(rangeStart, rangeEnd);
				let dayCells = "";

				for (let i = 0; i <= numDays; i++) {
					let currentDay = new Date(rangeStart.getFullYear(), rangeStart.getMonth(), rangeStart.getDate() + i, 12, 0);

					if (this.isFirstDayOfWeek(currentDay)) dayCells += "<tr>";

					if (currentDay.getMonth() === date.getMonth()) {
						dayCells += '<td class="selectable_day" date="' + this.dateToString(currentDay) + '">' + currentDay.getDate() + '</td>';
					} else {
						dayCells += '<td class="unselected_month" date="' + this.dateToString(currentDay) + '">' + currentDay.getDate() + '</td>';
					}

					if (this.isLastDayOfWeek(currentDay)) dayCells += "</tr>";
				}
				this.tbody.empty().append(dayCells);

				this.monthNameSpan.text(this.monthName(date));
				this.yearNameSpan.empty().append(this.currentMonth.getFullYear());

				$(".unselected_month", this.tbody).click(this.bindToObj(function(event) {
					this.changeInput($(event.target).attr("date"));
				}));

				$(".selectable_day", this.tbody).click(this.bindToObj(function(event) {
					this.changeInput($(event.target).attr("date"));
				}));

				$("td[date=" + this.dateToString(new Date()) + "]", this.tbody).addClass("today");

				$("td.selectable_day", this.tbody).mouseover(function() { $(this).addClass("hover") });
				$("td.selectable_day", this.tbody).mouseout(function() { $(this).removeClass("hover") });
			}

			$('.selected', this.tbody).removeClass("selected");
			$('td[date="' + this.selectedDateString + '"]', this.tbody).addClass("selected");
		},

		selectDate: function(date) {
			if (typeof(date) == "undefined") {
				date = this.stringToDate(this.input.val());
			}
			if (!date) date = new Date();

			this.selectedDate = date;
			this.selectedDateString = this.dateToString(this.selectedDate);
			this.selectMonth(this.selectedDate);
		},

		changeInput: function(dateString) {
			this.input.val(dateString).change();
			this.hide();
		},

		show: function() {
            if(this.input.prop('disabled'))return;
			this.rootLayers.css("display", "block");
			$([window,document.body]).off('click'+this.ns).on('click'+this.ns,this.hideIfClickOutside);
			this.input.off('focus'+this.ns,this.show);
			$(document.body).off('keydown'+this.ns).on('keydown'+this.ns,this.keydownHandler);
			this.setPosition();
            $(window).on('resize'+this.ns+' scroll'+this.ns,this.bindToObj(this.setPosition));
		},

		hide: function() {
			this.rootLayers.css("display", "none");
            $(window).off('resize'+this.ns+' scroll'+this.ns);
			$([window,document.body]).off('click'+this.ns,this.hideIfClickOutside);
			this.input.off('focus'+this.ns,this.show).on('focus'+this.ns,this.show);
			$(document.body).off('keydown'+this.ns,this.keydownHandler);
		},

		hideIfClickOutside: function(event) {
			if (event.target !== this.input[0] && !this.insideSelector(event)) {
				this.hide();
			}
		},

		insideSelector: function(event) {
			return this.dateSelector[0]===event.target||$.contains(this.dateSelector[0],event.target);

		},

		keydownHandler: function(event) {
			switch (event.keyCode) {
				case 9:
				case 27:
					this.hide();
					return;
				case 13:
					this.changeInput(this.selectedDateString);
					break;
				case 33:
					this.moveDateMonthBy(event.ctrlKey ? -12 : -1);
					break;
				case 34:
					this.moveDateMonthBy(event.ctrlKey ? 12 : 1);
					break;
				case 38:
					this.moveDateBy(-7);
					break;
				case 40:
					this.moveDateBy(7);
					break;
				case 37:
					this.moveDateBy(-1);
					break;
				case 39:
					this.moveDateBy(1);
					break;
				default:
					return;
			}
			event.preventDefault();
		},

		stringToDate: function(string) {
			let matches = String(string||'').match(/^(\d{4})-(\d{2})-(\d{2})[\s\d:]*$/);
			if (matches) {
				const y=Number(matches[1]),m=Number(matches[2])-1,d=Number(matches[3]);
                const date=new Date(0);date.setHours(0,0,0,0);date.setFullYear(y,m,d);
                return date.getFullYear()===y&&date.getMonth()===m&&date.getDate()===d?date:null;
			} else {
				return null;
			}
		},

		dateToString: function(date) {
			let month = (date.getMonth() + 1).toString();
			let dom = date.getDate().toString();
			if (month.length === 1) month = "0" + month;
			if (dom.length === 1) dom = "0" + dom;
			return date.getFullYear() + "-" + month + "-" + dom;
		},

		setPosition: function() {
			let offset = this.input.offset();
			this.rootLayers.css({
				top: offset.top + this.input.outerHeight(),
				left: offset.left,
				width: $(".date_selector").css("width")
			});
		},

		moveDateBy: function(amount) {
			let newDate = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), this.selectedDate.getDate() + amount);
			this.selectDate(newDate);
		},

        moveDateMonthBy: function(amount) {
            const y=this.selectedDate.getFullYear(),m=this.selectedDate.getMonth()+amount;
            const first=new Date(0);first.setFullYear(y,m,1);first.setHours(0,0,0,0);
            const end=new Date(first.getTime());end.setMonth(end.getMonth()+1,0);const last=end.getDate();
            first.setDate(Math.min(this.selectedDate.getDate(),last));this.selectDate(first);
        },

		moveMonthBy: function(amount) {
			let newMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + amount, this.currentMonth.getDate());
			this.selectMonth(newMonth);
		},

		monthName: function(date) {
			return this.month_names[date.getMonth()];
		},

		bindToObj: function(fn) {
			let self = this;
			return function() { return fn.apply(self, arguments) };
		},

		bindMethodsToObj: function() {
			for (let i = 0; i < arguments.length; i++) {
				this[arguments[i]] = this.bindToObj(this[arguments[i]]);
			}
		},

		indexFor: function(array, value) {
			for (let i = 0; i < array.length; i++) {
				if (value === array[i]) return i;
			}
		},

		monthNum: function(month_name) {
			return this.indexFor(this.month_names, month_name);
		},

		shortMonthNum: function(month_name) {
			return this.indexFor(this.short_month_names, month_name);
		},

		shortDayNum: function(day_name) {
			return this.indexFor(this.short_day_names, day_name);
		},

		daysBetween: function(start, end) {
			start = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
			end = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
			return (end - start) / 86400000;
		},

		changeDayTo: function(dayOfWeek, date, direction) {
			let difference = direction * (Math.abs(date.getDay() - dayOfWeek - (direction * 7)) % 7);
			return new Date(date.getFullYear(), date.getMonth(), date.getDate() + difference);
		},

		rangeStart: function(date) {
			return this.changeDayTo(this.start_of_week, new Date(date.getFullYear(), date.getMonth()), -1);
		},

		rangeEnd: function(date) {
			return this.changeDayTo((this.start_of_week + 6) % 7, new Date(date.getFullYear(), date.getMonth() + 1, 0), 1);
		},

		isFirstDayOfWeek: function(date) {
			return date.getDay() === this.start_of_week;
		},

		isLastDayOfWeek: function(date) {
			return date.getDay() === (this.start_of_week + 6) % 7;
		},

        destroy: function(){
            this.hide();this.input.off(this.ns).removeData('dateInput');
            $([window,document.body]).off(this.ns);this.rootLayers.remove();
        },

		adjustDays: function(days) {
			let newDays = [];
			for (let i = 0; i < days.length; i++) {
				newDays[i] = days[(i + this.start_of_week) % 7];
			}
			return newDays;
		}
	};

	$.fn.date_input = function(opts) {
		return this.each(function() {
            const input=$(this),instance=input.data('dateInput');
            if(typeof opts==='string'){
                if(!['destroy','show','hide'].includes(opts))throw new Error('Unknown date_input method: '+opts);
                if(instance)instance[opts]();return;
            }
            if(instance){instance.destroy();}
            input.data('dateInput',new DateInput(this,opts));
        });
	};

	$.date_input = {initialize: function(opts) {
			$("input.date_input").date_input(opts);
		}
	};

	return DateInput;
})(jQuery);

$(function() {
	if($.data(document,'dateInputLanguageTimer'))clearInterval($.data(document,'dateInputLanguageTimer'));
    let attempts=0;
    let setDateInput = setInterval(function() {
        if(typeof(language)==='undefined'){if(++attempts<30)return;clearInterval(setDateInput);$('input[need=date],input[need=date_]').prop('readonly',true).date_input();return;}
		jQuery.extend(DateInput.DEFAULT_OPTS, {
			month_names: [language.month_names_1, language.month_names_2, language.month_names_3, language.month_names_4, language.month_names_5, language.month_names_6, language.month_names_7, language.month_names_8, language.month_names_9, language.month_names_10, language.month_names_11, language.month_names_12],
			short_month_names: [language.short_month_names_1, language.short_month_names_2, language.short_month_names_3, language.short_month_names_4, language.short_month_names_5, language.short_month_names_6, language.short_month_names_7, language.short_month_names_8, language.short_month_names_9, language.short_month_names_10, language.short_month_names_11, language.short_month_names_12],
			short_day_names: [language.short_day_names_1, language.short_day_names_2, language.short_day_names_3, language.short_day_names_4, language.short_day_names_5, language.short_day_names_6, language.short_day_names_7]
		});
		$("input[need=date]").prop("readonly", true);
		$("input[need=date]").date_input();
		$("input[need=date_]").prop("readonly", true);
		$("input[need=date_]").date_input();
		clearInterval(setDateInput);
	}, 1000);
    $.data(document,'dateInputLanguageTimer',setDateInput);
});
