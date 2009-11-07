/**
 * @class выбор цвета для шрифта и фона
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.forecolor = function(rte, name) {
	var self = this;
	this.constructor.prototype.constructor.call(this, rte, name);
	var opts = {
		'class' : '',
		color   : this.defaultColor,
		update  : function(c) { self.indicator.css('background-color', c); },
		change  : function(c) { self.set(c) }
	}
	
	this.defaultColor = this.rte.utils.rgb2hex( $(this.rte.doc.body).css(this.name=='forecolor' ? 'color' : 'background-color') );
	this.picker       = this.domElem.elColorPicker(opts);
	this.indicator    = $('<div />').addClass('color-indicator').prependTo(this.domElem);
	
	this.command = function() {
		this.rte.browser.msie && this.rte.selection.saveIERange();
	}
	
	this.set = function(c) {
		if (!this.rte.selection.collapsed()) {
			this.rte.browser.msie && this.rte.selection.restoreIERange();
			var nodes = this.rte.selection.selected({collapse : false, wrap : 'text'});

			var css = this.name == 'forecolor' ? 'color' : 'background-color';			
			$.each(nodes, function() {
				if (/^(THEAD|TBODY|TFOOT|TR)$/.test(this.nodeName)) {
					$(this).find('td,th').each(function() {
						$(this).css(css, c).find('*').css(css, '');
					})
				} else {
					$(this).css(css, c).find('*').css(css, '');
				}
				
			});
			this.rte.ui.update(true);
			
		}

	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		
		var n = this.rte.selection.getNode();
		if (n.nodeType != 1) {
			n = n.parentNode;
		}
		var v = $(n).css(this.name == 'forecolor' ? 'color' : 'background-color');
		this.picker.val(v && v!='transparent' ? this.rte.utils.rgb2hex(v): this.defaultColor);
	}
}

elRTE.prototype.ui.prototype.buttons.hilitecolor = elRTE.prototype.ui.prototype.buttons.forecolor;