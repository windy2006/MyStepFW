/**
 * 框架调用：autocomplete(options|string)
 * 功能说明：选项初始化返回首个实例；字符串支持 destroy/clearCache/disable/enable/hide。onSelect(text,data,input) 保持原参数。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/**
 *  Ajax Autocomplete for jQuery, version 1.1.3
 *  (c) 2010 Tomas Kirda
 *
 *  Ajax Autocomplete for jQuery is freely distributable under the terms of an MIT-style license.
 *  For details, see the web site: http://www.devbridge.com/projects/autocomplete/jquery/
 *
 *  Last Review: 04/19/2010
 */

/*jslint onevar: true, evil: true, nomen: true, eqeqeq: true, bitwise: true, regexp: true, newcap: true, immed: true */
/*global window: true, document: true, clearInterval: true, setInterval: true, jQuery: true */

(function($) {

  let reEscape = new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g');

  function escapeText(value){return String(value).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function fnFormatResult(value, data, currentValue) {
    value=String(value);if(!currentValue)return escapeText(value);
    const pattern=new RegExp(currentValue.replace(reEscape,'\\$1'),'gi');
    let out='',last=0;value.replace(pattern,(match,index)=>{out+=escapeText(value.slice(last,index))+'<strong>'+escapeText(match)+'</strong>';last=index+match.length;return match;});
    return out+escapeText(value.slice(last));
  }

  function Autocomplete(el, options) {
    this.el = $(el);
    this.ns='.autocomplete'+($.data(document,'autocompleteSerial')||0);
    $.data(document,'autocompleteSerial',($.data(document,'autocompleteSerial')||0)+1);
    this.requestGeneration=0;this.pendingRequest=null;
    this.originalAutocomplete = el.getAttribute('autocomplete');
    this.el.attr('autocomplete', 'off');
    this.suggestions = [];
    this.data = [];
    this.badQueries = [];
    this.selectedIndex = -1;
    this.currentValue = this.el.val();
    this.intervalId = 0;
    this.cachedResponse = Object.create(null);
    this.onChangeInterval = null;
    this.ignoreValueChange = false;
    this.serviceUrl = options.serviceUrl;
    this.isLocal = false;
    this.options = {
      autoSubmit: false,
      minChars: 1,
      maxHeight: 300,
      deferRequestBy: 0,
      width: 0,
      highlight: true,
      params: {},
      fnFormatResult: fnFormatResult,
      delimiter: null,
      zIndex: 9999
    };
    this.initialize();
    this.setOptions(options);
  }

  $.fn.autocomplete = function(options) {
    if(!this.length)return undefined;
    if(typeof options==='string'){
      let result;const args=Array.prototype.slice.call(arguments,1);
      this.each(function(){const instance=$(this).data('autocomplete');if(!instance)return;if(!['destroy','dispose','clearCache','disable','enable','hide'].includes(options))throw new Error('Unknown autocomplete method: '+options);const value=instance[options].apply(instance,args);if(value!==undefined)result=value;});
      return result===undefined?this:result;
    }
    let first;
    this.each(function(){
      const el=$(this);let instance=el.data('autocomplete');
      if(instance){instance.hide();instance.currentValue=el.val();instance.setOptions(options||{});}
      else {instance=new Autocomplete(this,options||{});el.data('autocomplete',instance);}
      if(!first)first=instance;
    });return first;
  };

  Autocomplete.prototype = {
    killerFn: null,
    initialize: function() {
      let me, uid, autocompleteElId;
      me = this;
      uid = this.ns.slice(1);
      autocompleteElId = 'Autocomplete_' + uid;

      this.killerFn = function(e) {
        if ($(e.target).parents('.autocomplete').length === 0) {
          me.killSuggestions();
          me.disableKillerFn();
        }
      };

      if (!this.options.width) { this.options.width = this.el.width(); }
      this.mainContainerId = 'AutocompleteContainter_' + uid;

      $('<div id="' + this.mainContainerId + '" style="position:absolute;z-index:9999;"><div class="autocomplete-w1"><div class="autocomplete" id="' + autocompleteElId + '" style="display:none; width:300px;"></div></div></div>').appendTo('body');

      this.container = $('#' + autocompleteElId);
      this.fixPosition();
      if (window.opera) {
        this.el.on('keypress'+this.ns,function(e) { me.onKeyPress(e); });
      } else {
        this.el.on('keydown'+this.ns,function(e) { me.onKeyPress(e); });
      }
      this.el.on('input'+this.ns+' keyup'+this.ns,function(e) { if(!me.composing)me.onKeyUp(e); });
      this.el.on('compositionstart'+this.ns,()=>{me.composing=true;clearTimeout(me.onChangeInterval);me.requestGeneration++;if(me.pendingRequest)me.pendingRequest.abort();});
      this.el.on('compositionend'+this.ns,()=>{me.composing=false;me.onKeyUp({});});
      this.el.on('blur'+this.ns,function() { me.enableKillerFn(); });
      this.el.on('focus'+this.ns,function() { me.fixPosition(); });
      $(window).on('resize'+this.ns,()=>me.fixPosition());
    },

    setOptions: function(options){
      options=options||{};
      let o = this.options;
      clearTimeout(this.onChangeInterval);
      this.requestGeneration++;if(this.pendingRequest&&this.pendingRequest.abort)this.pendingRequest.abort();
      if(options.serviceUrl!==undefined)this.serviceUrl=options.serviceUrl;
      this.clearCache();$.extend(o, options);
      this.isLocal=!!o.lookup;
      if(o.lookup){
        this.isLocal = true;
        if($.isArray(o.lookup)){ o.lookup = { suggestions:o.lookup, data:[] }; }
      }
      $('#'+this.mainContainerId).css({ zIndex:o.zIndex });
      this.container.css({ maxHeight: o.maxHeight + 'px', width:o.width });
    },

    clearCache: function(){
      this.cachedResponse = Object.create(null);
      this.badQueries = [];
    },

    disable: function(){
      this.disabled = true;this.hide();this.requestGeneration++;if(this.pendingRequest&&this.pendingRequest.abort)this.pendingRequest.abort();
    },

    enable: function(){
      this.disabled = false;
    },

    fixPosition: function() {
      let offset = this.el.offset();if(!offset)return;
      $('#' + this.mainContainerId).css({ top: (offset.top + this.el.innerHeight()) + 'px', left: offset.left + 'px' });
    },

    enableKillerFn: function() {
      let me = this;
      $(document).off('click'+me.ns,me.killerFn).on('click'+me.ns,me.killerFn);
    },

    disableKillerFn: function() {
      let me = this;
      $(document).off('click'+me.ns,me.killerFn);
    },

    killSuggestions: function() {
      let me = this;
      this.stopKillSuggestions();
      this.intervalId = window.setInterval(function() { me.hide(); me.stopKillSuggestions(); }, 300);
    },

    stopKillSuggestions: function() {
      window.clearInterval(this.intervalId);
    },

    onKeyPress: function(e) {
      if (this.disabled || !this.enabled) { return; }
      // return will exit the function
      // and event will not be prevented
      switch (e.keyCode) {
        case 27: //KEY_ESC:
          this.el.val(this.currentValue);
          this.hide();
          break;
        case 9: //KEY_TAB:
        case 13: //KEY_RETURN:
          if (this.selectedIndex === -1) {
            this.hide();
            return;
          }
          this.select(this.selectedIndex);
          if(e.keyCode === 9){ return; }
          break;
        case 38: //KEY_UP:
          this.moveUp();
          break;
        case 40: //KEY_DOWN:
          this.moveDown();
          break;
        default:
          return;
      }
      e.stopImmediatePropagation();
      e.preventDefault();
    },

    onKeyUp: function(e) {
      if(this.disabled||this.disposed){ return; }
      switch (e.keyCode) {
        case 38: //KEY_UP:
        case 40: //KEY_DOWN:
          return;
      }
      clearTimeout(this.onChangeInterval);
      if (this.currentValue !== this.el.val()) {
        if (this.options.deferRequestBy > 0) {
          // Defer lookup in case when value changes very quickly:
          let me = this;
          this.onChangeInterval = setTimeout(function() { me.onValueChange(); }, this.options.deferRequestBy);
        } else {
          this.onValueChange();
        }
      }
    },

    onValueChange: function() {
      clearTimeout(this.onChangeInterval);
      this.requestGeneration++;if(this.pendingRequest&&this.pendingRequest.abort)this.pendingRequest.abort();
      this.currentValue = this.el.val();
      let q = this.getQuery(this.currentValue);
      this.selectedIndex = -1;
      if(this.disabled||this.disposed)return;
      if (q === '' || q.length < this.options.minChars) {
        this.hide();
      } else {
        this.getSuggestions(q);
      }
    },

    getQuery: function(val) {
      let d, arr;
      d = this.options.delimiter;
      if (!d) { return $.trim(val); }
      arr = val.split(d);
      return $.trim(arr[arr.length - 1]);
    },

    getSuggestionsLocal: function(q) {
      let ret, arr, len, val, i;
      arr = this.options.lookup;
      len = arr.suggestions.length;
      ret = { suggestions:[], data:[] };
      q = q.toLowerCase();
      for(i=0; i< len; i++){
        val = arr.suggestions[i];
        if(String(val).toLowerCase().indexOf(q) === 0){
          ret.suggestions.push(val);
          ret.data.push((arr.data||[])[i]);
        }
      }
      return ret;
    },

    getSuggestions: function(q) {
      let cr, me;
      cr = this.isLocal ? this.getSuggestionsLocal(q) : this.cachedResponse[q];
      if (cr && $.isArray(cr.suggestions)) {
        this.suggestions = cr.suggestions.map(String);
        this.data = cr.data;
        this.suggest();
      } else if (!this.isBadQuery(q)) {
        me = this;
        const generation=++me.requestGeneration;
        if(me.pendingRequest&&me.pendingRequest.abort)me.pendingRequest.abort();
        me.pendingRequest=$.post(this.serviceUrl,$.extend({},me.options.params,{query:q}),function(txt){if(generation===me.requestGeneration&&!me.disabled)me.processResponse(txt);},'text');
      }
    },

    isBadQuery: function(q) {
      let i = this.badQueries.length;
      while (i--) {
        if (q.indexOf(this.badQueries[i]) === 0) { return true; }
      }
      return false;
    },

    hide: function() {
      this.enabled = false;
      this.selectedIndex = -1;
      this.container.hide();
    },

    suggest: function() {
      if (this.disposed||this.disabled||this.suggestions.length === 0) {
        this.hide();
        return;
      }

      let me, len, div, f, v, i, s, mOver, mClick;
      me = this;
      len = this.suggestions.length;
      f = this.options.fnFormatResult;
      v = this.getQuery(this.currentValue);
      mOver = function(xi) { return function() { me.activate(xi); }; };
      mClick = function(xi) { return function() { me.select(xi); }; };
      this.container.hide().empty();
      for (i = 0; i < len; i++) {
        s = this.suggestions[i];
        div=$('<div>').toggleClass('selected',me.selectedIndex===i).attr('title',s).html(f(String(s),this.data[i],v));
        div.mouseover(mOver(i));
        div.click(mClick(i));
        this.container.append(div);
      }
      this.enabled = true;
      this.container.show();
    },

    processResponse: function(text) {
      let response;
      try {
        response = typeof text==='string'?JSON.parse(text):text;
      } catch (err) { return; }
      if(!response||typeof response!=='object'||!Array.isArray(response.suggestions)||typeof response.query!=='string')return;
      if (!$.isArray(response.data)) { response.data = []; }
      if(!this.options.noCache){
        this.cachedResponse[response.query] = response;
        if (response.suggestions.length === 0) { this.badQueries.push(response.query); }
      }
      if (response.query === this.getQuery(this.currentValue)) {
        this.suggestions = response.suggestions.map(String);
        this.data = response.data;
        this.suggest();
      }
    },

    activate: function(index) {
      let divs, activeItem;
      divs = this.container.children();
      // Clear previous selection:
      if (this.selectedIndex !== -1 && divs.length > this.selectedIndex) {
        $(divs.get(this.selectedIndex)).removeClass();
      }
      this.selectedIndex = index;
      if (this.selectedIndex !== -1 && divs.length > this.selectedIndex) {
        activeItem = divs.get(this.selectedIndex);
        $(activeItem).addClass('selected');
      }
      return activeItem;
    },

    deactivate: function(div, index) {
      div.className = '';
      if (this.selectedIndex === index) { this.selectedIndex = -1; }
    },

    select: function(i) {
      let selectedValue, f;
      selectedValue = this.suggestions[i];
      if (selectedValue !== undefined) {
        this.el.val(selectedValue);
        this.ignoreValueChange = false;
        this.hide();
        this.onSelect(i);
        if(this.options.autoSubmit){f=this.el.closest('form');if(f.length){const form=f[0];if(typeof form.requestSubmit==='function')form.requestSubmit();else HTMLFormElement.prototype.submit.call(form);}}
      }
    },

    moveUp: function() {
      if (this.selectedIndex === -1) { return; }
      if (this.selectedIndex === 0) {
        this.container.children().get(0).className = '';
        this.selectedIndex = -1;
        this.el.val(this.currentValue);
        return;
      }
      this.adjustScroll(this.selectedIndex - 1);
    },

    moveDown: function() {
      if (this.selectedIndex === (this.suggestions.length - 1)) { return; }
      this.adjustScroll(this.selectedIndex + 1);
    },

    adjustScroll: function(i) {
      let activeItem, offsetTop, upperBound, lowerBound;
      activeItem = this.activate(i);
      offsetTop = activeItem.offsetTop;
      upperBound = this.container.scrollTop();
      lowerBound = upperBound + this.options.maxHeight - 25;
      if (offsetTop < upperBound) {
        this.container.scrollTop(offsetTop);
      } else if (offsetTop > lowerBound) {
        this.container.scrollTop(offsetTop - this.options.maxHeight + 25);
      }
      this.el.val(this.getValue(this.suggestions[i]));
    },

    onSelect: function(i) {
      let me, fn, s, d;
      me = this;
      fn = me.options.onSelect;
      s = me.suggestions[i];
      d = me.data[i];
      me.el.val(me.getValue(s));me.currentValue=me.el.val();
      if ($.isFunction(fn)) { fn(s, d, me.el); }
    },

    destroy: function(){
      this.disposed=true;this.disable();clearTimeout(this.onChangeInterval);this.stopKillSuggestions();
      this.el.off(this.ns);$(window).off(this.ns);$(document).off(this.ns);
      $('#'+this.mainContainerId).remove();this.el.removeData('autocomplete');
      if(this.originalAutocomplete===null)this.el.removeAttr('autocomplete');else this.el.attr('autocomplete',this.originalAutocomplete);
    },
    dispose: function(){this.destroy();},

    getValue: function(value){
      let del, currVal, arr, me;
      me = this;
      del = me.options.delimiter;
      if (!del) { return value; }
      currVal = me.currentValue;
      arr = currVal.split(del);
      if (arr.length === 1) { return value; }
      return currVal.substr(0, currVal.length - arr[arr.length - 1].length) + value;
    }

  };
}(jQuery));