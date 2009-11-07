
function eli18n(o) {
	
	var domain = null;
	
	this.load = function(msgs) {

		if (typeof(msgs) == 'object') {
			for (var i in msgs) {
				var d = msgs[i];
				if (typeof(d) == 'object') {
					if (!this.messages[i]) { this.messages[i] = {};	}
					if (!domain) { domain = i; }
					for (var k in d) { this.messages[i][k] = d[k]; };
				}
			}
		}
	}
	
	this.textdomain = function(d) {
		if (d && this.messages[d]) { domain = d; }
		return d;
	}
	
	this.translate = function(msg, d) {
		if (!d || !this.messages[d]) { d = domain; }
		return this.messages[domain] && this.messages[domain][msg] ? this.messages[domain][msg] : msg;
	}
	
	this.format = function(msg, data, translate, d) {
		msg = translate ? this.translate(msg, d) : msg;
		if (typeof(data) == 'object') {
			for (var i in data) {
				var v = translate ? this.translate(data[i]) : data[i];
				msg = msg.replace('%'+i, v);
			}
		}
		return msg;
	}
	
	o && o.messages   && this.load(o.messages);
	o && o.textdomain && this.textdomain(o.textdomain);
	
	
}

eli18n.prototype.messages = {};


/**
 * @class elDialogForm
 * Wraper for jquery.ui.dialog and jquery.ui.tabs
 *  Create form in dialog. You can decorate it as you wish - with tabs or/and tables
 *
 * Usage:
 *   var d = new elDialogForm(opts)
 *   d.append(['Field name: ', $('<input type="text" name="f1" />')])
 *		.separator()
 *		.append(['Another field name: ', $('<input type="text" name="f2" />')])
 *      .open()
 * will create dialog with pair text field separated by horizontal rule
 * Calling append() with 2 additional arguments ( d.append([..], null, true)) 
 *  - will create table in dialog and put text inputs and labels in table cells
 *
 * Dialog with tabs:
 *   var d = new elDialogForm(opts)
 *   d.tab('first', 'First tab label)
 * 	  .tab('second', 'Second tab label)
 *    .append(['Field name: ', $('<input type="text" name="f1" />')], 'first', true)  - add label and input to first tab in table (table will create automagicaly)
 *    .append(['Field name 2: ', $('<input type="text" name="f2" />')], 'second', true)  - same in secon tab
 *
 * Options:
 *   class     - css class for dialog
 *   submit    - form submit event callback. Accept 2 args - event and this object
 *   ajaxForm  - arguments for ajaxForm, if needed (dont forget include jquery.form.js)
 *   tabs      - arguments for ui.tabs
 *   dialog    - arguments for ui.dialog
 *   name      - hidden text field in wich selected value will saved
 *
 * Notice!
 * When close dialog, it will destroing insead of dialog('close'). Reason - strange bug with tabs in dialog on secondary opening. 
 *
 * @author:    Dmitry Levashov (dio) dio@std42.ru
 * @copyright: Studio 42, http://www.std42.ru
 * @license:   BSD license
 *
 * Free for use in any projects
 *
 **/

function elDialogForm(o) {
	var self = this;
	
	var defaults = {
		'class'   : 'el-dialogform',
		submit    : function(e, d) { window.console && window.console.log && window.console.log('submit called'); d.close(); },
		form      : { action : window.location.href,	method : 'post'	},
		ajaxForm  : null,
		validate  : null,
		spinner   : 'Loading',
		tabs      : { active: 0 },
		tabPrefix : 'el-df-tab-',
		dialog    : {
			title     : 'dialog',
			autoOpen  : false,
			modal     : true,
			resizable : false,
			buttons  : {
				Cancel : function() { self.close(); },
				Ok     : function() { self.form.trigger('submit'); }
			}
		}
	};

	this.opts = $.extend(true, defaults, o, {dialog : { autoOpen : false, close : function() { self.close(); } }});
	if (o && o.dialog && o.dialog.buttons && typeof(o.dialog.buttons) == 'object') {
		this.opts.dialog.buttons = o.dialog.buttons;
	}
	this.ul     = null;
	this.tabs   = {};
	this._table = null;
	this.dialog = $('<div />').addClass(this.opts['class']).dialog(this.opts.dialog);
	this.message = $('<div class="el-dialogform-message rounded-5" />').hide().appendTo(this.dialog);
	this.error   = $('<div class="el-dialogform-error rounded-5" />').hide().appendTo(this.dialog);
	this.spinner = $('<div class="spinner" />').hide().appendTo(this.dialog);
	this.content = $('<div class="el-dialogform-content" />').appendTo(this.dialog)
	this.form   = $('<form />').attr(this.opts.form).appendTo(this.content);

	if (this.opts.submit) {
		this.form.bind('submit', function(e) { self.opts.submit(e, self) })
	}
	if (this.opts.ajaxForm && $.fn.ajaxForm) {
		this.form.ajaxForm(this.opts.ajaxForm);
	}
	if (this.opts.validate) {
		this.form.validate(this.opts.validate);
	}
	
	this.option = function(name, value) {
		return this.dialog.dialog('option', name, value)
	}
	
	this.showError = function(msg, hideContent) {
		this.hideMessage();
		this.hideSpinner();
		this.error.html(msg).show();
		hideContent && this.content.hide();
		return this;
	}
	
	this.hideError= function() {
		this.error.text('').hide();
		this.content.show();
		return this;		
	}
	
	this.showSpinner = function(txt) {
		this.error.hide();
		this.message.hide();
		this.content.hide();
		this.spinner.text(txt||this.opts.spinner).show();
		this.option('buttons', {});
		return this;		
	}
	
	this.hideSpinner = function() {
		this.content.show();
		this.spinner.hide();
		return this;		
	}
	
	this.showMessage = function(txt, hideContent) {
		this.hideError();
		this.hideSpinner();
		this.message.html(txt||'').show();
		hideContent && this.content.hide();
		return this;
	}
	
	this.hideMessage = function() {
		this.message.hide();
		this.content.show();
		return this;		
	}
	
	/**
	 * Create new tab
	 * @param string id    - tab id
	 * @param string title - tab name
	 * @return elDialogForm	
	**/
	this.tab = function(id, title) {
		id = this.opts.tabPrefix+id;
		
		if (!this.ul) {
			this.ul = $('<ul />').prependTo(this.form);
		}
		$('<li />').append($('<a />').attr('href', '#'+id).html(title)).appendTo(this.ul);
		this.tabs[id] = {tab : $('<div />').attr('id', id).addClass('tab').appendTo(this.form), table : null};
		return this;
	}
	
	/**
	 * Create new table
	 * @param string id  tab id, if set - table will create in tab, otherwise - in dialog
	 * @return elDialogForm	
	**/
	this.table = function(id) {
		id = id && id.indexOf(this.opts.tabPrefix) == -1 ? this.opts.tabPrefix+id : id;
		if (id && this.tabs && this.tabs[id]) {
			this.tabs[id].table = $('<table />').appendTo(this.tabs[id].tab);
		} else {
			this._table = $('<table />').appendTo(this.form); 
		}
		return this;
	}
	
	/**
	 * Append html, dom nodes or jQuery objects to dialog or tab
	 * @param array|object|string  data object(s) to append to dialog
	 * @param string               tid  tab id, if adding to tab
	 * @param bool                 t    if true - data will added in table (creating automagicaly)
	 * @return elDialogForm	
	**/
	this.append = function(data, tid, t) {
		tid = tid ? 'el-df-tab-'+tid : '';

		if (!data) {
			return this;
		}
		
		if (tid && this.tabs[tid]) {
			if (t) {
				!this.tabs[tid].table && this.table(tid);
				var tr = $('<tr />').appendTo(this.tabs[tid].table);
				if (!$.isArray(data)) {
					tr.append($('<td />').append(data));
				} else {
					for (var i=0; i < data.length; i++) {
						tr.append($('<td />').append(data[i]));
					};
				}
			} else {
				if (!$.isArray(data)) {
					this.tabs[tid].tab.append(data)
				} else {
					for (var i=0; i < data.length; i++) {
						this.tabs[tid].tab.append(data[i]);
					};
				}
			}
			
		} else {
			if (!t) {
				if (!$.isArray(data)) {
					this.form.append(data);
				} else {
					for (var i=0; i < data.length; i++) {
						this.form.append(data[i]);
					};
				}
			} else {
				if (!this._table) {
					this.table();
				}
				var tr = $('<tr />').appendTo(this._table);
				if (!$.isArray(data)) {
					tr.append($('<td />').append(data));
				} else {
					for (var i=0; i < data.length; i++) {
						tr.append($('<td />').append(data[i]));
					};
				}
			}
		}
		return this;
	}
	
	/**
	 * Append separator (div class="separator") to dialog or tab
	 * @param  string tid  tab id, if adding to tab
	 * @return elDialogForm	
	**/
	this.separator = function(tid) {
		tid = 'el-df-tab-'+tid;
		if (this.tabs && this.tabs[tid]) {
			this.tabs[tid].tab.append($('<div />').addClass('separator'));
			this.tabs[tid].table && this.table(tid);
		} else {
			this.form.append($('<div />').addClass('separator'));
		}
		return this;
	}
	
	/**
	 * Open dialog window
	 * @return elDialogForm	
	**/
	this.open = function() {
		this.ul && this.form.tabs(this.opts.tabs);
		this.form.find(':text').keyup(function(e) {
			if (e.keyCode == 13) {
				self.form.submit();
			}
		});

		this.dialog.dialog('open');
		this.form.find(':text').eq(0).focus();
		return this;
	}
	
	/**
	 * Close dialog window and destroy content
	 * @return void	
	**/
	this.close = function() {
		this.dialog.dialog('destroy').remove();
	}
	
}



(function($) {
	
	$.fn.elBorderSelect = function(o) {
		
		var $self = this;
		var self  = this.eq(0);
		var opts  = $.extend({}, $.fn.elBorderSelect.defaults, o);
		var width = $('<input type="text" />')
			.attr({'name' : opts.name+'[width]', size : 3}).css('text-align', 'right')
			.change(function() { $self.change(); });
		
		var color = $('<div />').css('position', 'relative')
			.elColorPicker({
				'class'         : 'el-colorpicker ui-icon ui-icon-pencil',
				name            : opts.name+'[color]', 
				palettePosition : 'outer',
				change          : function() { $self.change(); }
			});
		
		
		var style = $('<div />').elSelect({
			tpl    : '<div style="border-bottom:4px %val #000;width:100%;margin:7px 0"> </div>',
			tpls   : { '' : '%label'},
			maxHeight : opts.styleHeight || null,
			select : function() { $self.change(); },
			src    : {
				''       : 'none',
				solid    : 'solid',
				dashed   : 'dashed',
				dotted   : 'dotted',
				'double' : 'double',
				groove   : 'groove',
				ridge    : 'ridge',
				inset    : 'inset',
				outset   : 'outset'
			}
		});
		
		self.empty()
			.addClass(opts['class'])
			.attr('name', opts.name||'')
			.append(
				$('<table />').attr('cellspacing', 0).append(
					$('<tr />')
						.append($('<td />').append(width).append(' px'))
						.append($('<td />').append(style))
						.append($('<td />').append(color))
				)
			);
		
		function rgb2hex(str) {
		    function hex(x)  {
		    	hexDigits = ["0", "1", "2", "3", "4", "5", "6", "7", "8","9", "a", "b", "c", "d", "e", "f"];
		        return !x  ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x% 16];
		    }
			var rgb = str.match(/\(([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})\)/); 
			return rgb ? "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]) : '';
		}
		
		function toPixels(num) {
			var m = num.match(/([0-9]+\.?[0-9]*)\s*(px|pt|em|%)/);
			if (m) {
				num  = m[1];
				unit = m[2];
			} 
			if (num[0] == '.') {
				num = '0'+num;
			}
			num = parseFloat(num);

			if (isNaN(num)) {
				return '';
			}
			var base = parseInt($(document.body).css('font-size')) || 16;
			switch (unit) {
				case 'em': return parseInt(num*base);
				case 'pt': return parseInt(num*base/12);
				case '%' : return parseInt(num*base/100);
			}
			return num;
		}
		
		this.change = function() {
			opts.change && opts.change(this.val());
		}
		
		this.val = function(v) {
			if (!v && v !== '') {
				var w = parseInt(width.val());
				return {width : !isNaN(w) ? w+'px' : '', style : style.val(), color : color.val()};
			} else {
				var m, w, s, c, b = '';
				if (v.nodeName || v.css) {
					if (!v.css) {
						v = $(v);					
					}
					var b = v.css('border')
					if ((b = v.css('border'))) {
						w = s = c = b;
					} else {
						w = v.css('border-width');
						s = v.css('border-style');
						c = v.css('border-color');
					}

				} else {
					w = v.width||'';
					s = v.style||'';
					c = v.color||'';
				}

				width.val(toPixels(w));
				var m = s.match(/(solid|dashed|dotted|double|groove|ridge|inset|outset)/i);
				style.val(m ? m[1] : '');
				color.val(rgb2hex(c));
				return this;
			}
		}
		
		this.val(opts.value);
		return this;
	}
	
	$.fn.elBorderSelect.defaults = {
		name      : 'el-borderselect',
		'class'   : 'el-borderselect',
		value     : {},
		change    : null
	}
	
})(jQuery);

/**
 * elColorPicker JQuery plugin
 * Create drop-down colors palette
 *
 * Usage:
 * $(selector).elColorPicker(opts)
 *
 * set color after init:
 * var c = $(selector).elColorPicker(opts)
 * c.val('#ffff99)
 *
 * Get selected color:
 * var color = c.val();
 *
 * Notice!
 *   Palette created only after first click on element (lazzy loading)
 *
 * Options:
 *   colors - colors array (by default display 256 web safe colors)
 *   color  - current (selected) color
 *   class - css class for display "button" (element on wich plugin was called)
 *   paletteClass - css class for colors palette
 *   palettePosition - string indicate where palette will created:
 *      'inner' - palette will attach to element (acceptable in most cases)
 *      'outer' - palette will attach to document.body. 
 *                Use, when create color picker inside element with overflow == 'hidden', for example in ui.dialog
 *   update - function wich update button view on select color (by default set selected color as background)
 *   change - callback, called when color was selected (by default write color to console.log)
 *   name   - hidden text field in wich selected color value will saved
 *
 * @author:    Dmitry Levashov (dio) dio@std42.ru
 * @copyright: Studio 42 http://std42.ru
 * @license:   BSD license
 *
 * Free for use in any projects
 *
 **/
(function($) {

	$.fn.elColorPicker = function(o) {
		var self     = this;
		var opts     = $.extend({}, $.fn.elColorPicker.defaults, o);
		this.hidden  = $('<input type="hidden" />').attr('name', opts.name).val(opts.color||'').appendTo(this);
		this.palette = null;
		this.preview = null;
		this.input   = null;

		function setColor(c) {
			self.val(c);
			opts.change && opts.change(self.val());
			self.palette.slideUp();
		}

		function init() {
			self.palette  = $('<div />').addClass(opts.paletteClass);
			for (var i=0; i < opts.colors.length; i++) {
				$('<div />')
					.addClass('color')
					.css('background-color', opts.colors[i])
					.attr('title', opts.colors[i])
					.appendTo(self.palette)
					.mouseenter(function() {
						var v = $(this).attr('title');
						self.input.val(v);
						self.preview.css('background-color', v);
					})
					.click(function(e) {
						e.stopPropagation(); 
						setColor($(this).attr('title'));
					});
			};
			self.input = $('<input type="text" />')
				.attr('size', 8)
				.click(function(e) {
					e.stopPropagation();
				})
				.keydown(function(e) {
					if (e.ctrlKey || e.metaKey) {
						return true;
					}
					var k = e.keyCode;
					// on esc - close palette
					if (k == 27) {
						return self.mouseleave();
					}
					// allow input only hex color value
					if (k!=8 && k != 13 && k!=46 && k!=37 && k != 39 && (k<48 || k>57) && (k<65 || k > 70)) {
						return false;
					}
					var c = $(this).val();
					if (c.length == 7) {
						if (k == 13) {
							e.stopPropagation();
							e.preventDefault();
							setColor(c);
							self.palette.slideUp();
						}
						if (e.keyCode != 8 && e.keyCode != 46 && k!=37 && k != 39) {
							return false;
						}
					}
				})
				.keyup(function(e) {
					var c = $(this).val();
					c.length == 7 && /^#[0-9abcdef]{6}$/i.test(c) && self.val(c);
				});
			self.preview = $('<div />')
				.addClass('preview')
				.click(function(e) {
					e.stopPropagation();
					setColor(self.input.val());
				});
			
			self.palette.append($('<div />').addClass('clearfix'))
				.append($('<div />').addClass('panel').append(self.input).append(self.preview));
			
			if (opts.palettePosition == 'outer') {
				self.palette.hide()
					.appendTo(self.parents('body').eq(0))
					.mouseleave(function() {
						$(this).slideUp();
						self.val(self.val());
					})
				self.mouseleave(function(e) {
					if (e.relatedTarget != self.palette.get(0)) {
						self.palette.slideUp();
						self.val(self.val());
					}
				})
			} else {
				self.append(self.palette.hide())
					.mouseleave(function(e) {
						self.palette.slideUp();
						self.val(self.val());
					});
			}
			self.val(self.val());
		}
		
		this.empty().addClass(opts['class'])
			.css({'position' : 'relative', 'background-color' : opts.color||''})
		.click(function(e) { 
			if (!self.hasClass('disabled')) {
				!self.palette && init();
				if (opts.palettePosition == 'outer' && self.palette.css('display') == 'none') {
					var o = $(this).offset();
					var w = self.palette.width();
					var l = self.parents('body').width() - o.left >= w ? o.left : o.left + $(this).outerWidth() - w;
					self.palette.css({left : l+'px', top : o.top+$(this).height()+1+'px'});
				}
				self.palette.slideToggle();
				
			}
		});
		
		this.val = function(v) {
			if (!v && v!=='') {
				return this.hidden.val();
			} else {
				this.hidden.val(v);
				if (opts.update) {
					opts.update(this.hidden.val());
				} else {
					this.css('background-color', v);
				}
				
				if (self.palette) {
					self.preview.css('background-color', v);
					self.input.val(v);
				}
			}
			return this;
		}
		
		return this;
	}

	$.fn.elColorPicker.defaults = {
		'class'         : 'el-colorpicker',
		paletteClass    : 'el-palette',
		palettePosition : 'inner',
		name            : 'color',
		color           : '',
		update          : null,
		change          : function(c) { window.console && window.console.log && window.console.log(c) },
		colors          : [
			'#ffffff', '#cccccc', '#999999', '#666666', '#333333', '#000000', 
			'#ffcccc', '#cc9999', '#996666', '#663333', '#330000', 
			'#ff9999', '#cc6666', '#cc3333', '#993333', '#660000', 
			'#ff6666', '#ff3333', '#ff0000', '#cc0000', '#990000',
			'#ff9966', '#ff6633', '#ff3300', '#cc3300', '#993300',
			'#ffcc99', '#cc9966', '#cc6633', '#996633', '#663300',
			'#ff9933', '#ff6600', '#ff9900', '#cc6600', '#cc9933',
			'#ffcc66', '#ffcc33', '#ffcc00', '#cc9900', '#996600',
			'#ffffcc', '#cccc99', '#999966', '#666633', '#333300',
			'#ffff99', '#cccc66', '#cccc33', '#999933', '#666600',
			'#ffff66', '#ffff33', '#ffff00', '#cccc00', '#999900',
			'#ccff66', '#ccff33', '#ccff00', '#99cc00', '#669900',
			'#ccff99', '#99cc66', '#99cc33', '#669933', '#336600',
			'#99ff33', '#99ff00', '#66ff00', '#66cc00', '#66cc33',
			'#99ff66', '#66ff33', '#33ff00', '#33cc00', '#339900',
			'#ccffcc', '#99cc99', '#669966', '#336633', '#003300',
			'#99ff99', '#66cc66', '#33cc33', '#339933', '#006600',
			'#66ff66', '#33ff33', '#00ff00', '#00cc00', '#009900',
			'#66ff99', '#33ff66', '#00ff33', '#00cc33', '#009933',			
			'#99ffcc', '#66cc99', '#33cc66', '#339966', '#006633',						
			'#33ff99', '#00ff66', '#00ff99', '#00cc66', '#33cc99',						
			'#66ffcc', '#33ffcc', '#00ffcc', '#00cc99', '#009966',						
			'#ccffff', '#99cccc', '#669999', '#336666', '#003333',						
			'#99ffff', '#66cccc', '#33cccc', '#339999', '#006666',						
			'#66cccc', '#33ffff', '#00ffff', '#00cccc', '#009999',						
			'#66ccff', '#33ccff', '#00ccff', '#0099cc', '#006699',																		
			'#99ccff', '#6699cc', '#3399cc', '#336699', '#003366',						
			'#3399ff', '#0099ff', '#0066ff', '#066ccc', '#3366cc',																		
			'#6699ff', '#3366ff', '#0033ff', '#0033cc', '#003399',						
			'#ccccff', '#9999cc', '#666699', '#333366', '#000033',																		
			'#9999ff', '#6666cc', '#3333cc', '#333399', '#000066',																		
			'#6666ff', '#3333ff', '#0000ff', '#0000cc', '#009999',																		
			'#9966ff', '#6633ff', '#3300ff', '#3300cc', '#330099',																		
			'#cc99ff', '#9966cc', '#6633cc', '#663399', '#330066',
			'#9933ff', '#6600ff', '#9900ff', '#6600cc', '#9933cc',			
			'#cc66ff', '#cc33ff', '#cc00ff', '#9900cc', '#660099',
			'#ffccff', '#cc99cc', '#996699', '#663366', '#330033',			
			'#ff99ff', '#cc66cc', '#cc33cc', '#993399', '#660066',
			'#ff66ff', '#ff33ff', '#ff00ff', '#cc00cc', '#990099',			
			'#ff66cc', '#ff33cc', '#ff00cc', '#cc0099', '#990066',
			'#ff99cc', '#cc6699', '#cc3399', '#993366', '#660033',			
			'#ff3399', '#ff0099', '#ff0066', '#cc0066', '#cc3366',
			'#ff6699', '#ff3366', '#ff0033', '#cc0033', '#990033'		
			]
	};

})(jQuery);

(function($) {
	
	var selectors = {};
	var menu = document.createElement('div');
	$.elcontextmenu = function(options, context, callback) {
		context = context||document.body;
		$(menu).hide().addClass('el-contextmenu').appendTo(document.body);
		for (name in options) {
			selectors[name] = options[name];
			$(name, context).bind(window.opera?'click':'contextmenu', showmenu);
		}
		menu.callback = callback;
	};
	
	function showmenu(event) {
		event.stopPropagation();
		reset();
		if (window.opera && !event.ctrlKey) { 
			return;  
		} else {
	      $(document.body).mousedown(function(event){ reset(); });
	    }
		for (name in selectors) {
			if ($.inArray(event.currentTarget, $(name)) > -1) {
				if (menu.callback) {
					menu.callback(event);
				}
				variants = selectors[name]
				$(variants).each( function() {
					if (!this.label) {
						$('<div />').addClass('delim').appendTo(menu);
					} else {
						var action = this.action
						$('<div></div>').html(this.label).mousedown(function(clickEvent) { 
							clickEvent.stopPropagation();
							reset();
							if (typeof(action) == 'function') {
								action(event.currentTarget);
							}
						})
						.hover( 
							function() { $(this).addClass('el-contextmenu-hover'); },
							function() { $(this).removeClass('el-contextmenu-hover'); }
						)
						.appendTo(menu);
					}
				})
			}
		}
		
		var size = {
	      'height' : $(window).height(),
	      'width'  : $(window).width(),
	      'sT'     : $(window).scrollTop(),
	      'cW'     : $(menu).width(),
	      'cH'     : $(menu).height()
	    };
		$(menu).css({
				'left' : ((event.clientX + size.cW) > size.width ? ( event.clientX - size.cW) : event.clientX),
				'top'  : ((event.clientY + size.cH) > size.height && event.clientY > size.cH ? (event.clientY + size.sT - size.cH) : event.clientY + size.sT)
			}).show();
	    return false;
	}
	
	function reset(event){ $(menu).hide().empty(); }
	
})(jQuery);

(function($) {
	
	$.fn.elPaddingInput = function(o) {
		var self = this;
		var opts = $.extend({}, $.fn.elPaddingInput.defaults, {name : this.attr('name')}, o);
		this.regexps = {
			main   : new RegExp(opts.type == 'padding' ? 'padding\s*:\s*([^;"]+)'        : 'margin\s*:\s*([^;"]+)',       'im'),
			left   : new RegExp(opts.type == 'padding' ? 'padding-left\s*:\s*([^;"]+)'   : 'margin-left\s*:\s*([^;"]+)',  'im'),
			top    : new RegExp(opts.type == 'padding' ? 'padding-top\s*:\s*([^;"]+)'    : 'margin-top\s*:\s*([^;"]+)',    'im'),
			right  : new RegExp(opts.type == 'padding' ? 'padding-right\s*:\s*([^;"]+)'  : 'margin-right\s*:\s*([^;"]+)',  'im'),
			bottom : new RegExp(opts.type == 'padding' ? 'padding-bottom\s*:\s*([^;"]+)' : 'margin-bottom\s*:\s*([^;"]+)', 'im')
		};
			
		$.each(['left', 'top', 'right', 'bottom'], function() {
			self[this] = $('<input type="text" />')
				.attr('size', 3)
				.css('text-align', 'right')
				.bind('change', function() { $(this).val(parseNum($(this).val())); change(); })
				.attr('name', opts.name+'['+this+']');
		});
		$.each(['uleft', 'utop', 'uright', 'ubottom'], function() {
			self[this] = $('<select />')
				.append('<option value="px">px</option>')
				.append('<option value="em">em</option>')
				.append('<option value="pt">pt</option>')
				.bind('change', function() { change(); })
				.attr('name', opts.name+'['+this+']');
			if (opts.percents) {
				self[this].append('<option value="%">%</option>');
			}
		});
		
		this.empty().addClass(opts['class'])
			.append(this.left).append(this.uleft).append(' x ')
			.append(this.top).append(this.utop).append(' x ')
			.append(this.right).append(this.uright).append(' x ')
			.append(this.bottom).append(this.ubottom);
			
		this.val = function(v) {
			if (!v && v!=='') {
				var l = parseNum(this.left.val());
				var t = parseNum(this.top.val());
				var r = parseNum(this.right.val());
				var b = parseNum(this.bottom.val());
				var ret = {
					left   : l=='auto' || l==0 ? l : (l!=='' ? l+this.uleft.val()   : ''), 
					top    : t=='auto' || t==0 ? t : (t!=='' ? t+this.utop.val()    : ''),
					right  : r=='auto' || r==0 ? r : (r!=='' ? r+this.uright.val()  : ''),
					bottom : b=='auto' || b==0 ? b : (b!=='' ? b+this.ubottom.val() : ''),
					css    : ''
				};
				if (ret.left!=='' && ret.right!=='' && ret.top!=='' && ret.bottom!=='') {
					if (ret.left == ret.right && ret.top == ret.bottom) {
						ret.css = ret.top+' '+ret.left;
					} else{
						ret.css = ret.top+' '+ret.right+' '+ret.bottom+' '+ret.left;
					}
				}
				
				return ret;
			} else {
				
				if (v.nodeName || v.css) {
					if (!v.css) {
						v = $(v);
					}
					var val   = {left : '', top : '', right: '', bottom : ''};
					var style = (v.attr('style')||'').toLowerCase();

					if (style) {
						style   = $.trim(style);
						var m = style.match(this.regexps.main);
						if (m) {
							var tmp    = $.trim(m[1]).replace(/\s+/g, ' ').split(' ', 4);
							val.top    = tmp[0];
							val.right  = tmp[1] && tmp[1]!=='' ? tmp[1] : val.top;
							val.bottom = tmp[2] && tmp[2]!=='' ? tmp[2] : val.top;
							val.left   = tmp[3] && tmp[3]!=='' ? tmp[3] : val.right;
						} else {
							$.each(['left', 'top', 'right', 'bottom'], function() {
								var name = this.toString();
								m = style.match(self.regexps[name]);
								if (m) {
									val[name] = m[1];
								}
							});
						}
					}
					var v = val;
				} 

				$.each(['left', 'top', 'right', 'bottom'], function() {
					var name = this.toString();
					if (typeof(v[name]) != 'undefined' && v[name] !== null) {
						v[name] = v[name].toString();
						var _v = parseNum(v[name]);
						self[name].val(_v);
						var m = v[name].match(/(px|em|pt|%)/i);
						self['u'+name].val(m ? m[1] : 'px');
					}
				});
				return this;
			}
		}
			
		function parseNum(num) {
			num = $.trim(num.toString());
			if (num[0] == '.') { 
				num = '0'+num;
			}
			n = parseFloat(num);
			return !isNaN(n) ? n : (num == 'auto' ? num : '');
		}
			
		function change() {
			opts.change && opts.change(self);
		}
		
		this.val(opts.value);
		
		return this;
	}
	
	$.fn.elPaddingInput.defaults = {
		name     : 'el-paddinginput',
		'class'  : 'el-paddinginput',
		type     : 'padding',
		value    : {},
		percents : true,
		change   : null
	}
	
})(jQuery);

/**
 * elSelect JQuery plugin
 * Replacement for select input
 * Allow to put any html and css decoration in drop-down list
 *
 * Usage:
 *   $(selector).elSelect(opts)
 *
 * set value after init:
 *   var c = $(selector).elSelect(opts)
 *   c.setValue('some value')
 *
 * Get selected value:
 *   var val = c.getValue();
 *
 * Notice!
 *   1. When called on multiply elements, elSelect create drop-down list only for fist element
 *   2. Elements list created only after first click on element (lazzy loading)
 *
 * Options:
 *   src       - object with pairs value:label to create drop-down list 
 *   value     - current (selected) value
 *   class     - css class for display "button" (element on wich plugin was called)
 *   listClass - css class for drop down elements list
 *   select    - callback, called when value was selected (by default write value to console.log)
 *   name      - hidden text field in wich selected value will saved
 *   maxHeight - elements list max height (if height greater - scroll will appear)
 *   tpl       - template for element in list (contains 2 vars: %var - for src key, %label - for src[val] )
 *   labelTpl  - template for label (current selected element) (contains 2 vars: %var - for src key, %label - for src[val] )
 *
 * @author:    Dmitry Levashov (dio) dio@std42.ru
 * @copyright: Studio 42, http://www.std42.ru
 * @license:   BSD license
 *
 * Free for use in any projects
 *
 **/
(function($) {
	
	$.fn.elSelect = function(o) {
		var $self    = this;
		var self     = this.eq(0);
		var opts     = $.extend({}, $.fn.elSelect.defaults, o);
		var hidden   = $('<input type="hidden" />').attr('name', opts.name);
		var label    = $('<label />').attr({unselectable : 'on'});
		var list     = null;
		var ieWidth  = null;

		if (self.get(0).nodeName == 'SELECT') {
			opts.src = {};
			self.children('option').each(function() {
				opts.src[$(this).val()] = $(this).text();
			});
			opts.value = self.val();
			opts.name = self.attr('name');
			self.replaceWith((self = $('<div />')));
		}
		
		if (!opts.value || !opts.src[opts.val]) {
			opts.value = null;
			var i = 0;
			for (var v in opts.src) {
				if (i++ == 0) {
					opts.value = v;
				}
			}
		}

		this.val = function(v) {
			if (!v && v!=='') {
				return hidden.val();
			} else {
				if (opts.src[v]) {
					hidden.val(v);
					updateLabel(v);
					if (list) {
						list.children().each(function() {
							if ($(this).attr('name') == v) {
								$(this).addClass('active');
							} else {
								$(this).removeClass('active');
							}
						});
					}
				}
				return this;
			}
		}
	
		// update label content
		function updateLabel(v) {
			var tpl = opts.labelTpl || opts.tpls[v] || opts.tpl;
			label.html(tpl.replace(/%val/g, v).replace(/%label/, opts.src[v])).children().attr({unselectable : 'on'});
		}
		
		// init "select"
		self.empty().addClass(opts['class']).attr({unselectable : 'on'})
			.append(hidden)
			.append(label)
			.hover(
				function() { $(this).addClass('hover') },
				function() { $(this).removeClass('hover') }
			)
			.click(function(e) {
				!list && init();
				list.slideToggle();
				// stupid ie inherit width from parent
				if ($.browser.msie && !ieWidth) { 
					list.children().each(function() {
						ieWidth = Math.max(ieWidth, $(this).width());
					});
					if (ieWidth > list.width()) {
						list.width(ieWidth+40);
					}
				}
			});
			
		this.val(opts.value);
	
		// create drop-down list
		function init() {
			// not ul because of ie is stupid with mouseleave in it :(
			list = $('<div />')
				.addClass(opts.listClass)
				.hide()
				.appendTo(self.mouseleave(function(e) { list.slideUp(); }));

			for (var v in opts.src) {
				var tpl = opts.tpls[v] || opts.tpl; 
				$('<div />')
					.attr('name', v)
					.append( $(tpl.replace(/%val/g, v).replace(/%label/g, opts.src[v])).attr({unselectable : 'on'}) )
					.appendTo(list)
					.hover(
						function() { $(this).addClass('hover') },
						function() { $(this).removeClass('hover') }
					)
					.click(function(e) {
						e.stopPropagation();
						e.preventDefault();
						
						var v = $(this).attr('name');
						$self.val(v);
						opts.select(v);
						list.slideUp();
					});
			};
			
			var w = self.outerWidth();
			if (list.width() < w) {
				list.width(w);
			}
			
			var h = list.height();
			if (opts.maxHeight>0 && h>opts.maxHeight) {
				list.height(opts.maxHeight);
			}
			
			$self.val(hidden.val());
		}
		
		return this;
	}
	
	$.fn.elSelect.defaults = {
		name      : 'el-select',
		'class'   : 'el-select',
		listClass : 'list',
		labelTpl  : null,
		tpl       : '<%val>%label</%val>',
		tpls      : {},
		value     : null,
		src       : {},
		select    : function(v) {  window.console &&  window.console.log && window.console.log('selected: '+v); },
		maxHeight : 310
	}
	
})(jQuery);

(function($) {

	$.fn.eldirtree = function(o) {
		if (!options) {
			var options = o && o.constructor == Object
				? $.extend({}, $.fn.eldirtree.defaults, o)
				: $.fn.eldirtree.defaults;
		}

		return this.each(function() {
			var self = this;
			
			if (!this.loaded) {
				this.loaded = true;
				var root = $(this).children('li');
				if (options.loadCollapsed) {
					root.find(root.length > 1 ? 'ul' : 'ul li ul').hide();
				}
				root.find('a').click(function(e) {
					e.stopPropagation();
					e.preventDefault();
					root.find('a').removeClass('selected');
					$(this).addClass('selected');
					if (root.length>1 || $(this).parent('li').parent('ul').get(0) != self) {
						$(this).parent('li').children('ul').toggle();
					}
					options.callback($(this));
				})
				.bind('expand', function(e) {
					$(this).parents('ul').show();
					root.find('a').removeClass('selected');
					$(this).addClass('selected').parent('li').children('ul').show();
					
				});
				root.eq(0).children('a').addClass('selected');
			}

		});
	}

	$.fn.eldirtree.defaults = {
		loadCollapsed  : true,
		callback  : function() {}	
		};


})(jQuery);

