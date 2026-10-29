/**
 * 框架调用：jRange(options|method[,value])
 * 功能说明：选项初始化返回原选择集；getter 返回字符串。setValue 一次写回最终值；destroy 释放文档监听和生成界面。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/*jshint multistr:true, curly: false */
/*global jQuery:false, define: false */
/**
 * jRange - Awesome range control
 *
 * Written by
 * ----------
 * Nitin Hayaran (nitinhayaran@gmail.com)
 *
 * Licensed under the MIT (MIT-LICENSE.txt).
 *
 * @author Nitin Hayaran
 * @version 0.1-RELEASE
 *
 * Dependencies
 * ------------
 * jQuery (http://jquery.com)
 *
 **/
;
(function($, window, document, undefined) {
	'use strict';

	let jRange = function() {
		return this.init.apply(this, arguments);
	};
	jRange.prototype = {
		defaults: {
			onstatechange: function() {},
			ondragend: function() {},
			onbarclicked: function() {},
			isRange: false,
			showLabels: true,
			showScale: true,
			step: 1,
			format: '%s',
			theme: 'theme-green',
			width: 300,
			disable: false,
			snap: false
		},
		template: '<div class="slider-container">\
			<div class="back-bar">\
                <div class="selected-bar"></div>\
                <div class="pointer low"></div><div class="pointer-label low">123456</div>\
                <div class="pointer high"></div><div class="pointer-label high">456789</div>\
                <div class="clickable-dummy"></div>\
            </div>\
            <div class="scale"></div>\
		</div>',
		init: function(node, options) {
			this.options=$.extend({from:0,to:100},this.defaults,options);
            this.options.from=Number(this.options.from);this.options.to=Number(this.options.to);this.options.step=Number(this.options.step);
            if(!Number.isFinite(this.options.from)||!Number.isFinite(this.options.to)||this.options.to<=this.options.from||!Number.isFinite(this.options.step)||this.options.step<=0)throw new RangeError('jRange 范围与步长无效');
            this.ns='.slider'+($.data(document,'rangeSerial')||0);$.data(document,'rangeSerial',($.data(document,'rangeSerial')||0)+1);
			this.inputNode     = $(node);
            this.originalStyle=node.getAttribute('style');
			this.options.value = this.inputNode.val() || (this.options.isRange ? this.options.from + ',' + this.options.from : '' + this.options.from);
			this.domNode       = $(this.template);
			this.domNode.addClass(this.options.theme);
			this.inputNode.after(this.domNode);
			this.domNode.on('change', this.onChange);
			this.pointers      = $('.pointer', this.domNode);
			this.lowPointer    = this.pointers.first();
			this.highPointer   = this.pointers.last();
			this.labels        = $('.pointer-label', this.domNode);
			this.lowLabel      = this.labels.first();
			this.highLabel     = this.labels.last();
			this.scale         = $('.scale', this.domNode);
			this.bar           = $('.selected-bar', this.domNode);
			this.clickableBar  = this.domNode.find('.clickable-dummy');
			this.interval      = this.options.to - this.options.from;
			this.render();
		},
		render: function() {
			// Check if inputNode is visible, and have some width, so that we can set slider width accordingly.
			if (this.inputNode.width() === 0 && !this.options.width) {
				console.log('jRange : no width found, returning');
				return;
			} else {
				this.options.width = this.options.width || this.inputNode.width();
				this.domNode.width(this.options.width);
				this.inputNode.hide();
			}

			if (this.isSingle()) {
				this.lowPointer.hide();
				this.lowLabel.hide();
			}
			if (!this.options.showLabels) {
				this.labels.hide();
			}
			this.attachEvents();
			if (this.options.showScale) {
				this.renderScale();
			}
			this.setValue(this.options.value);
		},
		isSingle: function() {
			if (typeof(this.options.value) === 'number') {
				return true;
			}
			return (!(this.options.value.indexOf(',') !== -1 || this.options.isRange));
		},
		attachEvents: function() {
			this.clickableBar.click($.proxy(this.barClicked, this));
			this.pointers.on('mousedown touchstart', $.proxy(this.onDragStart, this));
			this.pointers.bind('dragstart', function(event) {
				event.preventDefault();
			});
		},
		onDragStart: function(e) {
			if ( this.options.disable || (e.type === 'mousedown' && e.which !== 1)) {
				return;
			}
			e.stopPropagation();
			e.preventDefault();
			let pointer = $(e.target);
			this.pointers.removeClass('last-active');
			pointer.addClass('focused last-active');
			this[(pointer.hasClass('low') ? 'low' : 'high') + 'Label'].addClass('focused');
			$(document).on('mousemove'+this.ns+' touchmove'+this.ns, $.proxy(this.onDrag, this, pointer));
			$(document).on('mouseup'+this.ns+' touchend'+this.ns+' touchcancel'+this.ns, $.proxy(this.onDragEnd, this));
		},
		onDrag: function(pointer, e) {
			e.stopPropagation();
			e.preventDefault();

			if (e.originalEvent&&e.originalEvent.touches && e.originalEvent.touches.length) {
				e = e.originalEvent.touches[0];
			} else if (e.originalEvent&&e.originalEvent.changedTouches && e.originalEvent.changedTouches.length) {
				e = e.originalEvent.changedTouches[0];
			}

			let position = (e.pageX!==undefined?e.pageX:e.clientX+window.pageXOffset) - this.domNode.offset().left;
			this.domNode.trigger('change', [this, pointer, position]);
		},
		onDragEnd: function(e) {
			this.pointers.removeClass('focused')
				.trigger('rangeslideend');
			this.labels.removeClass('focused');
			$(document).off(this.ns);
			this.options.ondragend.call(this, this.options.value);
		},
		barClicked: function(e) {
			if(this.options.disable) return;
			let x = e.pageX - this.clickableBar.offset().left;
			if (this.isSingle())
				this.setPosition(this.pointers.last(), x, true, true);
			else {
				let firstLeft      	= Math.abs(parseFloat(this.pointers.first().css('left'), 10)),
					firstHalfWidth 	= this.pointers.first().width() / 2,
					lastLeft        = Math.abs(parseFloat(this.pointers.last().css('left'), 10)),
					lastHalfWidth  	= this.pointers.first().width() / 2,
					leftSide        = Math.abs(firstLeft - x + firstHalfWidth),
					rightSide       = Math.abs(lastLeft - x + lastHalfWidth),
					pointer;

				if(leftSide === rightSide) {
					pointer = x < firstLeft ? this.pointers.first() : this.pointers.last();
				} else {
					pointer = leftSide < rightSide ? this.pointers.first() : this.pointers.last();
				}
				this.setPosition(pointer, x, true, true);
			}
			this.options.onbarclicked.call(this, this.options.value);
		},
		onChange: function(e, self, pointer, position) {
			let min, max;
			min = 0;
			max = self.domNode.width();

			if (!self.isSingle()) {
				min = pointer.hasClass('high') ? parseFloat(self.lowPointer.css("left")) + (self.lowPointer.width() / 2) : 0;
				max = pointer.hasClass('low') ? parseFloat(self.highPointer.css("left")) + (self.highPointer.width() / 2) : self.domNode.width();
			}

			let value = Math.min(Math.max(position, min), max);
			self.setPosition(pointer, value, true);
		},
		setPosition: function(pointer, position, isPx, animate) {
			let leftPos, rightPos,
				lowPos = parseFloat(this.lowPointer.css("left")),
				highPos = parseFloat(this.highPointer.css("left")) || 0,
				circleWidth = this.highPointer.width() / 2;
			if (!isPx) {
				position = this.prcToPx(position);
			}
			if(this.options.snap){
				let expPos = this.correctPositionForSnap(position);
				if(expPos === -1){
					return;
				}else{
					position = expPos;
				}
			}
			if (pointer[0] === this.highPointer[0]) {
				highPos = Math.round(position - circleWidth);
			} else {
				lowPos = Math.round(position - circleWidth);
			}
			pointer[animate ? 'animate' : 'css']({
				'left': Math.round(position - circleWidth)
			});
			if (this.isSingle()) {
				leftPos = 0;
			} else {
				leftPos = lowPos + circleWidth;
				rightPos = highPos + circleWidth;
			}
			let w = Math.round(highPos + circleWidth - leftPos);
			this.bar[animate ? 'animate' : 'css']({
				'width': Math.abs(w),
				'left': (w>0) ? leftPos : leftPos + w
			});
			this.showPointerValue(pointer, position, animate);
			this.isReadonly();
		},
		correctPositionForSnap: function(position){
			let currentValue = this.positionToValue(position) - this.options.from;
			let diff = this.domNode.width() / (this.interval / this.options.step),
				expectedPosition = (currentValue / this.options.step) * diff;
			if( position <= expectedPosition + diff / 2 && position >= expectedPosition - diff / 2){
				return expectedPosition;
			}else{
				return -1;
			}
		},
		// will be called from outside
		setValue: function(value) {
			let values=String(value).split(',');if(values.length>2)throw new RangeError('jRange 仅接受一个数值或两个区间端点');if(!values.every(v=>v.trim()!==''&&Number.isFinite(Number(v))))throw new RangeError('jRange 数值无效');
            if(this.options.isRange&&values.length===1)values.push(values[0]);
            if(values.length===2&&Number(values[0])>Number(values[1]))values.reverse();
			values[0] = Math.min(Math.max(values[0], this.options.from), this.options.to) + '';
			if (values.length > 1){
				values[1] = Math.min(Math.max(values[1], this.options.from), this.options.to) + '';
			}
            this.settingValue=true;
			this.options.value=values.join(',');
			let prc = this.valuesToPrc(values.length === 2 ? values : [0, values[0]]);
			if (this.isSingle()) {
				this.setPosition(this.highPointer, prc[1]);
			} else {
				this.setPosition(this.lowPointer, prc[0]);
				this.setPosition(this.highPointer, prc[1]);
			}
            this.settingValue=false;this.commitInputValue();
		},
		renderScale: function() {
            const scale=this.options.scale||[this.options.from,this.options.to];
            this.scale.empty();
            scale.forEach((value,i)=>{const item=$('<span>').css('left',(scale.length<2?0:i*100/(scale.length-1))+'%').appendTo(this.scale);if(value!=='|')$('<ins>').text(value).appendTo(item);});

			$('ins', this.scale).each(function() {
				$(this).css({
					marginLeft: -$(this).outerWidth() / 2
				});
			});
		},
		getBarWidth: function() {
			let values = this.options.value.split(',');
			if (values.length > 1) {
				return parseFloat(values[1]) - parseFloat(values[0]);
			} else {
				return parseFloat(values[0]);
			}
		},
		showPointerValue: function(pointer, position, animate) {
			let label = $('.pointer-label', this.domNode)[pointer.hasClass('low') ? 'first' : 'last']();
			let text;
			let value = this.positionToValue(position);
			// Is it higer or lower than it should be?

			if ($.isFunction(this.options.format)) {
				let type = this.isSingle() ? undefined : (pointer.hasClass('low') ? 'low' : 'high');
				text = this.options.format(value, type);
			} else {
				text = this.options.format.replace('%s', value);
			}

			let width = label.html(text).width(),
				left = position - width / 2;
			left = Math.min(Math.max(left, 0), this.domNode.width() - width);
			label[animate ? 'animate' : 'css']({
				left: left
			});
			this.setInputValue(pointer, value);
		},
		valuesToPrc: function(values) {
			let lowPrc = ((parseFloat(values[0]) - parseFloat(this.options.from)) * 100 / this.interval),
				highPrc = ((parseFloat(values[1]) - parseFloat(this.options.from)) * 100 / this.interval);
			return [lowPrc, highPrc];
		},
		prcToPx: function(prc) {
			return (this.domNode.width() * prc) / 100;
		},
		isDecimal: function() {
			return ((this.options.value + this.options.from + this.options.to).indexOf(".") !== -1);
		},
        positionToValue: function(pos) {
            const from=this.options.from,to=this.options.to,step=this.options.step;
            let value=from+(pos/this.domNode.width())*this.interval;
            value=Math.max(from,Math.min(to,from+Math.round((value-from)/step)*step));
            const precision=number=>{const [base,exponent='0']=String(number).toLowerCase().split('e');return Math.max(0,(base.split('.')[1]||'').length-Number(exponent));};
            const decimal=this.isDecimal()||precision(step)>0;
            const places=Math.min(12,Math.max(decimal?2:0,precision(step),precision(from),precision(to)));
            return decimal?value.toFixed(places):Math.round(value);
        },

		setInputValue: function(pointer, v) {
			// if(!isChanged) return;
			if (this.isSingle()) {
				this.options.value = v.toString();
			} else {
				let values = this.options.value.split(',');
				if (pointer.hasClass('low')) {
					this.options.value = v + ',' + values[1];
				} else {
					this.options.value = values[0] + ',' + v;
				}
			}
            if(!this.settingValue)this.commitInputValue();
        },
        commitInputValue:function(){
            if(this.inputNode.val()!==this.options.value){
                this.inputNode.val(this.options.value).trigger('change');
                this.options.onstatechange.call(this,this.options.value);
            }
        },
        destroy:function(){
            $(document).off(this.ns);$(window).off(this.ns);this.domNode.find('*').addBack().stop(true,false);this.domNode.remove();
            if(this.originalStyle===null)this.inputNode.removeAttr('style');else this.inputNode.attr('style',this.originalStyle);
            this.inputNode.removeData('plugin_jRange');
        },

		getValue: function() {
			return this.options.value;
		},
		getOptions: function() {
			return this.options;
		},
		getRange: function() {
			return this.options.from + "," + this.options.to;
		},
		isReadonly: function(){
			this.domNode.toggleClass('slider-readonly', this.options.disable);
		},
		disable: function(){
			this.options.disable = true;
			this.isReadonly();
		},
		enable: function(){
			this.options.disable = false;
			this.isReadonly();
		},
		toggleDisable: function(){
			this.options.disable = !this.options.disable;
			this.isReadonly();
		},
        updateRange: function(range,value){
            const values=String(range).split(',').map(Number);
            if(values.length!==2||!values.every(Number.isFinite)||values[1]<=values[0])throw new RangeError('jRange 范围无效');
            this.options.from=values[0];this.options.to=values[1];this.interval=values[1]-values[0];
            if(this.options.showScale)this.renderScale();
            this.setValue(value!==undefined?value:this.getValue());
        }

	};

	let pluginName = 'jRange';
	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function(option) {
		let args = arguments,
			result;

		this.each(function() {
			let $this = $(this),
				data = $.data(this, 'plugin_' + pluginName),
				options = typeof option === 'object' && option;
            if(!data&&typeof option==='string'){if(option==='destroy')return;throw new Error('jRange 尚未初始化');}
			if (!data) {
				$this.data('plugin_' + pluginName, (data = new jRange(this, options)));
				$(window).on('resize'+data.ns,function() {
					data.setValue(data.getValue());
				}); // Update slider position when window is resized to keep it in sync with scale
			}
			// if first argument is a string, call silimarly named function
			// this gives flexibility to call functions of the plugin e.g.
			//   - $('.dial').plugin('destroy');
			//   - $('.dial').plugin('render', $('.new-child'));
            if(data&&options&&$this.data('plugin_'+pluginName)===data&&data._configured){
                const next=$.extend({},data.options,options);next.from=Number(next.from);next.to=Number(next.to);next.step=Number(next.step);
                if(!Number.isFinite(next.from)||!Number.isFinite(next.to)||next.to<=next.from||!Number.isFinite(next.step)||next.step<=0)throw new RangeError('jRange 范围与步长无效');
                $.extend(data.options,next);data.domNode.css('width',data.options.width);data.updateRange(data.options.from+','+data.options.to,options.value!==undefined?options.value:data.getValue());
            }
            data._configured=true;
            if (typeof option === 'string') {
				if(option.charAt(0)==='_'||typeof data[option]!=='function')throw new Error('Unknown jRange method: '+option);
                result = data[option].apply(data, Array.prototype.slice.call(args, 1));
			}
		});

		// To enable plugin returns values
		return result===undefined?this:result;
	};

})(jQuery, window, document);
