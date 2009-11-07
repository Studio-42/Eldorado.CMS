/**
 * Редактор
 *
 * @author  dio@eldorado-cms.ru
 **/
elRTE = function(target, opts, conf) {
	var self     = this;
	this.options = $.extend(true, {}, this.options, opts);
	this.browser = $.browser;
	
	this.editor    = $('<div />').addClass(this.options.cssClass);
	this.toolbar   = $('<div />').addClass('toolbar').appendTo(this.editor);
	this.iframe    = document.createElement('iframe');
	this.workzone  = $('<div />').addClass('workzone').appendTo(this.editor).append(this.iframe);
	this.statusbar = $('<div />').addClass('statusbar').appendTo(this.editor);
	this.tabsbar   = $('<div />').addClass('tabsbar').appendTo(this.editor);
	this.source    = $('<textarea />').appendTo(this.workzone).hide();
	
	this.target  = null;
	this.doc     = null;
	this.window  = null;
	
	this.utils     = new this.utils(this);
	this.dom       = new this.dom(this);
	this._i18n     = new eli18n({textdomain : 'rte', messages : { rte : this.i18Messages[this.options.lang] || {}} });	

	if (!target || !target.nodeName) {
		alert('elRTE: argument "target" is not DOM Element');
		return;
	}
	
	this.init = function() {
		this.options.height>0 && this.workzone.height(this.options.height);
		var src = this.filter(target.nodeName == 'TEXTAREA' ? $(target).val() : $(target).html(), true);
		this.source.val(src);
		this.source.attr('name', $(target).attr('name')||$(target).attr('id'));
		if (this.options.allowSource) {
			this.tabsbar.append($('<div />').text(self.i18n('Editor')).addClass('tab editor rounded-bottom-7 active'))
						.append($('<div />').text(self.i18n('Source')).addClass('tab source rounded-bottom-7'))
						.append($('<div />').addClass('clearfix'));
		}
		this.target = $(target).replaceWith(this.editor);
		this.window = this.iframe.contentWindow;
		this.doc    = this.iframe.contentWindow.document;
		
		html = '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		if (self.options.cssfiles.length) {
			$.each(self.options.cssfiles, function() {
				html += '<link rel="stylesheet" type="text/css" href="'+this+'" />';
			});
		}
		html = self.options.doctype+html+'</head><body>'+src+'</body></html>';
		this.doc.open();
		this.doc.write(html);
		this.doc.close();
		if(!this.doc.body.firstChild) {
			this.doc.body.appendChild(this.doc.createElement('br'));
		}
		if (this.browser.msie) {
			//this.source.attr('rows', parseInt(this.options.height/17));
			this.doc.body.contentEditable = true;
		} else {
			try { this.doc.designMode = "on"; } 
			catch(e) { }
			this.doc.execCommand('styleWithCSS', false, this.options.styleWithCSS);
		}
		 
		this.window.focus();
		this.selection = new this.selection(this);
		this.ui = new this.ui(this);
		this.editor.parents('form').eq(0).submit(function(e) {
			// e.preventDefault()
			self.log(self.source.css('display'))
			if (self.source.css('display') == 'none') {
				self.updateSource();
			}
			self.toolbar.find(':hidden').remove();
		});

		
		$(this.doc)
			.keydown(function(e) {
				if (self.browser.safari && e.keyCode == 13) {
			
					if (e.shiftKey || !self.dom.parent(self.selection.getNode(), /^(P|LI)$/)) {
						self.selection.insertNode(self.doc.createElement('br'))
						return false;
					}
				}
			})
			.bind('keyup mouseup', function(e) {
				if (e.type == 'mouseup' || e.ctrlKey || e.metaKey || (e.keyCode >= 8 && e.keyCode <= 13) || (e.keyCode>=32 && e.keyCode<= 40) || e.keyCode == 46 || (e.keyCode >=96 && e.keyCode <= 111)) {
					self.ui.update();
				}
			});
	}
	
	this.init();
	
	

}

/**
 * Return message translated to selected language
 *
 * @param  string  msg  message text in english
 * @return string
 **/
elRTE.prototype.i18n = function(msg) {
	return this._i18n.translate(msg);
}



/**
 * Display editor
 *
 * @return void
 **/
elRTE.prototype.open = function() {
	this.editor.show();
	this.target.hide();
}

/**
 * Hide editor and display elements on wich editor was created
 *
 * @return void
 **/
elRTE.prototype.close = function() {
	this.editor.hide();
	this.target.show();
}

elRTE.prototype.updateEditor = function() {
	$(this.doc.body).html( this.filter(this.source.val(), true) );
	this.window.focus();
	this.ui.update(true);
}

elRTE.prototype.updateSource = function() {
	this.source.val(this.filter($(this.doc.body).html()));
	
}

/**
 * Return edited text
 *
 * @return String
 **/
elRTE.prototype.val = function(val) {
	if (val) {
		$(this.doc.body).html( this.filter(val, true) );
	} else {
		this.updateSource();
		return this.source.val();
	}
}

/**
 * Submit form
 *
 * @return void
 **/
elRTE.prototype.save = function() {
	// this.updateSource();
	this.editor.parents('form').submit();
}


elRTE.prototype.filter = function(v, input) {
	var html = '';
	var node = $('<span />');
	if (!v.nodeType) {
		html = $.trim(v);
	} else {
		html = $.trim(v.nodeType == 1 ? $(v).html() : v.nodeValue);
	}
	var sw = this.options.stripWhiteSpace;
	$.each(this.filters.html, function() {
		html = this(html, sw);
	});
	
	node.html(html);

	if (input) {
		node.find('a').each(function() {
			if ($(this).attr('name')) {
				$(this).addClass('el-rte-anchor');
			}
		});
		
	} else {
		node.find('a.el-rte-anchor').each(function() {
			if ($.trim($(this).attr('class')) == 'el-rte-anchor') {
				$(this).removeAttr('class');
			} else {
				$(this).removeClass('el-rte-anchor');
			}
		});
	}

	$.each(this.filters.dom, function() {
		node = this(node);
	});
	return node.html();
}



elRTE.prototype.filters = {
	dom  : [
		function(n) { 
			n.find('[align]').not('tbody,tr').each(function() {
				var a = ($(this).attr('align')||'').toLowerCase();
				if ((this.nodeName != 'TD' && this.nodeName != 'TH') || a != 'left') {
					$(this).css('text-align', a).removeAttr('align');
				}
			})
			.end().end().find('[border],[bordercolor]').each(function() {
				var w = parseInt($(this).attr('border')) || 1;
				var c = $(this).attr('bordercolor') || '#000';
				$(this).css('border', w+'px solid '+c).removeAttr('border').removeAttr('bordercolor');
			})
			.end().find('[bgcolor]').each(function() {
				$(this).css('background-color', $(this).attr('bgcolor')).removeAttr('bgcolor');
			}).end().find('[background]').each(function() {
				$(this).css('background', 'url('+$(this).attr('background')+')' ).removeAttr('background');
			})
			.end().find('img[hspace],[vspace]').each(function() {
				var v = parseInt($(this).attr('vspace'))||0;
				var h = parseInt($(this).attr('hspace'))||0;
				if (v>0 || h>0) {
					$(this).css('margin', (v>0?v:0)+'px '+(h>0?h:0)+'px');
				}
				$(this).removeAttr('hspace').removeAttr('vspace');
			})
			.end().find('[clear]').each(function() {
				var c = ($(this).attr('clear')||"").toLowerCase();
				$(this).css('clear', c == 'all' ? 'both' : c);
			});
			
			if ($.browser.safari) {
				n.find('.Apple-style-span').removeClass('Apple-style-span');
			}
			
			return n;
		}
	],
	html : [
		function(html, stripWhiteSpace) { 
			var fsize = {
				1 : 'xx-small',
				2 : 'x-small',
				3 : 'small',
				4 : 'medium',
				5 : 'large',
				6 : 'x-large',
				7 : 'xx-large'
			}
			
			html = html.replace(/<font([^>]*)/i, function(str, attr) {
				var css = '';
				var m = attr.match(/size=('|")(\d)/i);
				if (m && m[2] && fsize[m[2]]) {
					css = 'font-size: '+fsize[m[2]]+'; ';
				}
				var m = attr.match(/face=('|")([a-z0-9\s,]+)/i);
				if (m && m[2]) {
					css += 'font-family: '+m[2];
				}
				return '<span'+(css ? ' style="'+css+'"' : '');
			})
			.replace(/<\/font/i, '</span')
			.replace(/<b(\s[^>]*)?>/i, '<strong$1>')
			.replace(/<\/b\s*>/i, '</strong>');
			
			//.replace(/^(<p[^>]*>(&nbsp;|&#160;|\s|\u00a0|)<\/p>[\r\n]*|<br \/>[\r\n]*)$/, '')
			if (stripWhiteSpace) {
				html = html.replace(/\r?\n(\s)*/mg, "\n");
			}
			return html 
		}
	]
}

elRTE.prototype.log = function(msg) {
	if (window.console && window.console.log) {
		window.console.log(msg);
	}
        
}

elRTE.prototype.i18Messages = {};

elRTE.prototype.options   = {
	doctype         : '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">',
	cssClass        : 'el-rte',
	height          : null,
	lang            : 'en',
	cssfiles        : [],
	toolbar         : 'normal',
	absoluteURLs    : true,
	allowSource     : true,
	stripWhiteSpace : false,
	styleWithCSS    : false,
	fmAllow         : true,
	fmOpen          : null,
	buttons         : {
		'save'                : 'Save',
		'copy'                : 'Copy',
		'cut'                 : 'Cut',
		'paste'               : 'Paste',
		'pastetext'           : 'Paste only text',
		'pasteformattext'     : 'Paste formatted text',
		'removeformat'        : 'Clean format', 
		'bold'                : 'Bold',
		'italic'              : 'Italic',
		'underline'           : 'Underline',
		'strikethrough'       : 'Strikethrough',
		'superscript'         : 'Superscript',
		'subscript'           : 'Subscript',
		'justifyleft'         : 'Align left',
		'justifyright'        : 'Ailgn right',
		'justifycenter'       : 'Align center',
		'justifyfull'         : 'Align full',
		'indent'              : 'Indent',
		'outdent'             : 'Outdent',
		'forecolor'           : 'Font color',
		'hilitecolor'         : 'Background color',
		'formatblock'         : 'Format',
		'fontsize'            : 'Font size',
		'fontname'            : 'Font',
		'insertorderedlist'   : 'Ordered list',
		'insertunorderedlist' : 'Unordered list',
		'horizontalrule'      : 'Horizontal rule',
		'blockquote'          : 'Blockquote',
		'div'                 : 'Block element (DIV)',
		'link'                : 'Link',
		'unlink'              : 'Delete link',
		'anchor'              : 'Bookmark',
		'image'               : 'Image',
		'table'               : 'Table',
		'tablerm'             : 'Delete table',
		'tableprops'          : 'Table properties',
		'tbcellprops'         : 'Table cell properties',
		'tbrowbefore'         : 'Insert row before',
		'tbrowafter'          : 'Insert row after',
		'tbrowrm'             : 'Delete row',
		'tbcolbefore'         : 'Insert column before',
		'tbcolafter'          : 'Insert column after',
		'tbcolrm'             : 'Delete column',
		'tbcellsmerge'        : 'Merge table cells',
		'tbcellsplit'         : 'Split table cell',
		'docstructure'        : 'Toggle display document structure',
		'fullscreen'          : 'Toggle full screen mode',
		'nbsp'                : 'Non breakable space',
		'stopfloat'           : 'Stop element floating'
	},
	panels      : {
		save         : ['save'],
		copypaste    : ['copy', 'cut', 'paste', 'pastetext', 'pasteformattext', 'removeformat', 'docstructure', 'elfinder'],
		undoredo     : ['undo', 'redo'],
		style        : ['bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript'],
		colors       : ['forecolor', 'hilitecolor'],
		alignment    : ['justifyleft', 'justifycenter', 'justifyright', 'justifyfull'],
		indent       : ['outdent', 'indent'],
		format       : ['formatblock', 'fontsize', 'fontname'],
		lists        : ['insertorderedlist', 'insertunorderedlist'],
		elements     : ['horizontalrule', 'blockquote', 'div', 'stopfloat', 'nbsp'],
		links        : ['link', 'unlink', 'anchor'],
		images       : ['image'],
		media        : ['image'],		
		tables       : ['table', 'tableprops', 'tablerm',  'tbrowbefore', 'tbrowafter', 'tbrowrm', 'tbcolbefore', 'tbcolafter', 'tbcolrm', 'tbcellprops', 'tbcellsmerge', 'tbcellsplit'],
		fullscreen   : ['fullscreen']
	},
	toolbars    : {
		test     : ['save', 'copypaste', 'undoredo', 'style', 'alignment', 'indent', 'format', 'lists', 'links', 'images', 'fullscreen'],
		tiny     : ['style'],
		compact  : ['save', 'undoredo', 'style', 'alignment', 'lists', 'links'],
		normal   : ['save', 'copypaste', 'undoredo', 'style', 'alignment', 'colors', 'indent', 'lists', 'links', 'elements', 'images'],
		complite : ['save', 'copypaste', 'undoredo', 'style', 'alignment', 'colors', 'format', 'indent', 'lists', 'links', 'elements', 'media'],
		maxi     : ['save', 'copypaste', 'undoredo', 'style', 'alignment', 'colors', 'format', 'indent', 'lists', 'links', 'elements', 'media', 'tables', 'fullscreen']
		
	},
	panelNames : {
		save      : 'Save',
		copypaste : 'Copy/Pase',
		undoredo  : 'Undo/Redo',
		style     : 'Text styles',
		colors    : 'Colors',
		alignment : 'Alignment',
		indent    : 'Indent/Outdent',
		format    : 'Text format',
		lists     : 'Lists',
		elements  : 'Misc elements',
		links     : 'Links',
		images    : 'Images',
		media     : 'Media',
		tables    : 'Tables'
	}
};


(function($) {

	$.fn.jqelrte = function(o) { 
		return this.each(function() {
			var rte = new elRTE(this, o);
		})
	}
	
})(jQuery);

elRTE.prototype.dom = function(rte) {
	this.rte = rte;
	var self = this;
	this.regExp = {
		textNodes         : /^(A|ABBR|ACRONYM|ADDRESS|B|BDO|BIG|BLOCKQUOTE|CAPTION|CENTER|CITE|CODE|DD|DEL|DFN|DIV|DT|EM|FIELDSET|FONT|H[1-6]|I|INS|KBD|LABEL|LEGEND|LI|MARQUEE|NOBR|NOEMBED|P|PRE|Q|SAMP|SMALL|SPAN|STRIKE|STRONG|SUB|SUP|TD|TH|TT|VAR)$/,
		textContainsNodes : /^(A|ABBR|ACRONYM|ADDRESS|B|BDO|BIG|BLOCKQUOTE|CAPTION|CENTER|CITE|CODE|DD|DEL|DFN|DIV|DL|DT|EM|FIELDSET|FONT|H[1-6]|I|INS|KBD|LABEL|LEGEND|LI|MARQUEE|NOBR|NOEMBED|OL|P|PRE|Q|SAMP|SMALL|SPAN|STRIKE|STRONG|SUB|SUP|TABLE|THEAD|TBODY|TFOOT|TD|TH|TR|TT|UL|VAR)$/,
		block             : /^(APPLET|BLOCKQUOTE|BR|CAPTION|CENTER|COL|COLGROUP|DD|DIV|DL|DT|H[1-6]|EMBED|FIELDSET|LI|MARQUEE|NOBR|OBJECT|OL|P|PRE|TABLE|THEAD|TBODY|TFOOT|TD|TH|TR|UL)$/,
		selectionBlock    : /^(APPLET|BLOCKQUOTE|BR|CAPTION|CENTER|COL|COLGROUP|DD|DIV|DL|DT|H[1-6]|EMBED|FIELDSET|LI|MARQUEE|NOBR|OBJECT|OL|P|PRE|TD|TH|TR|UL)$/,		
		header            : /^H[1-6]$/,
		formElement       : /^(FORM|INPUT|HIDDEN|TEXTAREA|SELECT|BUTTON)$/
		
	};
	
	/********************************************************/
	/*                      Утилиты                         */
	/********************************************************/	
	
	
	/**
	 * Возвращает body редактируемого документа
	 *
	 * @return Element
	 **/
	this.root = function() {
		return this.rte.body;
	}

	this.create = function(t) {
		return this.rte.doc.createElement(t);
	}

	/**
	 * Вовращает индекс элемента внутри родителя
	 *
	 * @param  Element n  нода
	 * @return integer
	 **/
	this.indexOf = function(n) {
		var ndx = 0;
		n = $(n);
		while ((n = n.prev()) && n.length) {
			ndx++;
		}
		return ndx;
	}
	
	/**
	 * Вовращает значение аттрибута в нижнем регистре (ох уж этот IE)
	 *
	 * @param  Element n  нода
	 * @param  String  attr имя аттрибута
	 * @return string
	 **/
	this.attr = function(n, attr) {
		if (n.nodeType == 1) {
			var v = $(n).attr(attr);
		} 
		return v ? v.toString().toLowerCase() : '';
	}
	
	/**
	 * Вовращает ближайший общий контейнер для 2-х эл-тов
	 *
	 * @param  Element n  нода1
	 * @param  Element n  нода2
	 * @return Element
	 **/
	this.findCommonAncestor = function(n1, n2) {
		if (!n1 || !n2) {
			return this.rte.log('dom.findCommonAncestor invalid arguments')
		}
		if (n1 == n2) {
			return n1;
		} else if (n1.nodeName == 'BODY' || n2.nodeName == 'BODY') {
			return this.rte.doc.body;
		}
		var p1 = $(n1).parents();
		var p2 = $(n2).parents();
		var l  = p2.length-1
		var c  = p2[l]
		for (var i = p1.length - 1; i >= 0; i--, l--){
			if (p1[i] == p2[l]) {
				c = p1[i]
			} else {
				break;
			}
		};
		return c;
	}
	/**
	 * Вовращает TRUE, если нода пустая
	 * пустой считаем ноды:
	 *  - текстовые эл-ты, содержащие пустую строку или тег br
	 *  - текстовые ноды с пустой строкой
	 *
	 * @param  DOMElement n  нода
	 * @return bool
	 **/
	this.isEmpty = function(n) {
		if (n.nodeType == 1) {
			return this.regExp.textNodes.test(n.nodeName) ? $.trim($(n).text()).length == 0 : false;
		} else if (n.nodeType == 3) {
			return /^(TABLE|THEAD|TFOOT|TBODY|TR|UL|OL|DL)$/.test(n.parentNode.nodeName)
				|| n.nodeValue == ''
				|| ($.trim(n.nodeValue).length== 0 && !(n.nextSibling && n.previousSibling && n.nextSibling.nodeType==1 && n.previousSibling.nodeType==1 && !this.regExp.block.test(n.nextSibling.nodeName) && !this.regExp.block.test(n.previousSibling.nodeName) ));
		}
		return true;
	}

	/********************************************************/
	/*                  Перемещение по DOM                  */
	/********************************************************/

	/**
	 * Вовращает следующую соседнюю ноду (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return DOMElement
	 **/
	this.next = function(n) {
		while (n.nextSibling && (n = n.nextSibling)) {
			if (n.nodeType == 1 || (n.nodeType == 3 && !this.isEmpty(n))) {
				return n;
			}
		}
		return null;
	}

	/**
	 * Вовращает предыдующую соседнюю ноду (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return DOMElement
	 **/
	this.prev = function(n) {
		while (n.previousSibling && (n = n.previousSibling)) {
			if (n.nodeType == 1 || (n.nodeType ==3 && !this.isEmpty(n))) {
				return n;
			}
		}
		return null;
	}

	this.isPrev = function(n, prev) {
		while ((n = this.prev(n))) {
			if (n == prev) {
				return true;
			}
		}
		return false
	}

	/**
	 * Вовращает все следующие соседнии ноды (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return Array
	 **/
	this.nextAll = function(n) {
		var ret = [];
		while ((n = this.next(n))) {
			ret.push(n);
		}
		return ret;
	}
	
	/**
	 * Вовращает все предыдующие соседнии ноды (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return Array
	 **/
	this.prevAll = function(n) {
		var ret = [];
		while ((n = this.prev(n))) {
			ret.push(n);
		}
		return ret;
	}
	
	/**
	 * Вовращает все следующие соседнии inline ноды (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return Array
	 **/
	this.toLineEnd = function(n) {
		var ret = [];
		while ((n = this.next(n)) && n.nodeName != 'BR' && n.nodeName != 'HR' && this.isInline(n)) {
			ret.push(n);
		}
		return ret;
	}
	
	/**
	 * Вовращает все предыдующие соседнии inline ноды (не включаются текстовые ноды не создающие значимые пробелы между инлайн элементами)
	 *
	 * @param  DOMElement n  нода
	 * @return Array
	 **/
	this.toLineStart = function(n) {
		var ret = [];
		while ((n = this.prev(n)) && n.nodeName != 'BR' && n.nodeName != 'HR' && this.isInline(n) ) {
			ret.unshift(n);
		}
		return ret;
	}
	
	/**
	 * Вовращает TRUE, если нода - первый непустой эл-т внутри родителя
	 *
	 * @param  Element n  нода
	 * @return bool
	 **/
	this.isFirstNotEmpty = function(n) {
		while ((n = this.prev(n))) {
			if (n.nodeType == 1 || (n.nodeType == 3 && $.trim(n.nodeValue)!='' ) ) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Вовращает TRUE, если нода - последний непустой эл-т внутри родителя
	 *
	 * @param  Element n  нода
	 * @return bool
	 **/
	this.isLastNotEmpty = function(n) {
		while ((n = this.next(n))) {
			if (!this.isEmpty(n)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Вовращает TRUE, если нода - единственный непустой эл-т внутри родителя
	 *
	 * @param  DOMElement n  нода
	 * @return bool
	 **/
	this.isOnlyNotEmpty = function(n) {
		return this.isFirstNotEmpty(n) && this.isLastNotEmpty(n);
	}
	
	/**
	 * Вовращает последний непустой дочерний эл-т ноды или FALSE
	 *
	 * @param  Element n  нода
	 * @return Element
	 **/
	this.findLastNotEmpty = function(n) {
		this.rte.log('findLastNotEmpty Who is here 0_o');
		if (n.nodeType == 1 && (l = n.lastChild)) {
			if (!this.isEmpty(l)) {
				return l;
			}
			while (l.previousSibling && (l = l.previousSibling)) {
				if (!this.isEmpty(l)) {
					return l;
				}
			}
		}
		return false;
	}
	
	/**
	 * Возвращает TRUE, если нода "inline" 
	 *
	 * @param  DOMElement n  нода
	 * @return bool
	 **/
	this.isInline = function(n) {
		if (n.nodeType == 3) {
			return true;
		} else if (n.nodeType == 1) {
			n = $(n);
			var d = n.css('display');
			var f = n.css('float');
			return d == 'inline' || d == 'inline-block' || f == 'left' || f == 'right';
		}
		return true;
	}
	
	
	/********************************************************/
	/*                  Поиск элементов                     */
	/********************************************************/
	
	/**
	 * Вовращает элемент(ы) отвечающие условиям поиска
	 *
	 * @param  DOMElement||Array  n       нода
	 * @param  RegExp||String     filter  фильтр условия поиска (RegExp или имя ключа this.regExp или *)
	 * @return DOMElement||Array
	 **/
	this.filter = function(n, filter) {
		
		filter = this.regExp[filter] || filter;
		if (!n.push) {
			return n.nodeName && filter.test(n.nodeName) ? n : null;
		}
		var ret = [];
		for (var i=0; i < n.length; i++) {
			if (n[i].nodeName && n[i].nodeName && filter.test(n[i].nodeName)) {
				ret.push(n[i]);
			}
		};
		return ret;
	}
	
	
	/**
	 * Вовращает массив родительских элементов, отвечающих условиям поиска
	 *
	 * @param  DOMElement      n  нода, родителей, которой ищем
	 * @param  RegExp||String  filter   фильтр условия поиска (RegExp или имя ключа this.regExp или *)
	 * @return Array
	 **/
	this.parents = function(n, filter) {
		var ret = [];
		filter = filter == '*' ? /.?/ : (this.regExp[filter] || filter);
			filter = this.regExp[filter] || filter;
			while (n && (n = n.parentNode) && n.nodeName != 'BODY' && n.nodeName != 'HTML') {
				if (filter.test(n.nodeName)) {
					ret.push(n);
				}
			}

		return ret;
	}
	
	/**
	 * Вовращает ближайший родительский эл-т, отвечающий условиям поиска
	 *
	 * @param  DOMElement     n  нода, родителя, которой ищем
	 * @param  RegExp||String f   фильтр условия поиска (RegExp или имя ключа this.regExp или *)
	 * @return DOMElement
	 **/
	this.parent = function(n, f) { 
		return this.parents(n, f)[0] || null; 
	}
	
	/**
	 * Вовращает или саму ноду или ее ближайшего родителя, если выполняются условия sf для самой ноды или pf для родителя
	 *
	 * @param  DOMElement     n  нода, родителя, которой ищем
	 * @param  RegExp||String sf   фильтр условия для самой ноды
	* @param  RegExp||String  pf   фильтр условия для родителя
	 * @return DOMElement
	 **/
	this.selfOrParent = function(n, sf, pf) {
		return this.filter(n, sf) || this.parent(n, pf||sf);
	}
	
	/**
	 * Вовращает родительскую ноду - ссылку
	 *
	 * @param  Element n  нода
	 * @return Element
	 **/
	this.selfOrParentLink = function(n) {
		n = this.selfOrParent(n, /^A$/);
		return n && n.href ? n : null;
	}

	/**
	 * Вовращает TRUE, если нода -  anchor
	 *
	 * @param  Element n  нода
	 * @return bool
	 **/
	this.selfOrParentAnchor = function(n) {
		n = this.selfOrParent(n, /^A$/);
		return n && !n.href && n.name ? n : null;
	}

	/**
	 * Вовращает массив дочерних ссылок
	 *
	 * @param  DOMElement n  нода
	 * @return Array
	 **/
	this.childLinks = function(n) {
		var res = [];
		$('a[href]', n).each(function() { res.push(this); });
		return res;
	}
	
	
	/********************************************************/
	/*                    Изменения DOM                     */
	/********************************************************/
	
	/**
	 * Оборачивает одну ноду другой
	 *
	 * @param  DOMElement n  оборачиваемая нода
	 * @param  DOMElement w  нода обертка или имя тега
	 * @return DOMElement
	 **/
	this.wrap = function(n, w) {
		n = n.length ? n : [n];
		w = w.nodeName ? w : this.create(w);
		w = n[0].parentNode.insertBefore(w, n[0]);
		$(n).each(function() {
			if (this!=w) {
				w.appendChild(this);
			}
		})
		return w;
	}
	
	/**
	 * Оборачивает все содержимое ноды
	 *
	 * @param  DOMElement n  оборачиваемая нода
	 * @param  DOMElement w  нода обертка или имя тега
	 * @return DOMElement
	 **/
	this.wrapContents = function(n, w) {
		w = w.nodeName ? w : this.create(w);
		for (var i=0; i < n.childNodes.length; i++) {
			w.appendChild(n.childNodes[i]);
		};
		n.appendChild(w);
		return w;
	}
	
	this.cleanNode = function(n) {

		if (n.nodeType != 1) {
			return;
		}
		if (/^(P|LI)$/.test(n.nodeName) && (l = this.findLastNotEmpty(n)) && l.nodeName == 'BR') {
			$(l).remove();
		}
		$n = $(n);
		$n.children().each(function() {
			this.cleanNode(this);
		});
		if (n.nodeName != 'BODY' && !/^(TABLE|TR|TD)$/.test(n) && this.isEmpty(n)) {
			return $n.remove();
		}
		if ($n.attr('style') === '') {
			$n.removeAttr('style');
		}
		if (this.rte.browser.safari && $n.hasClass('Apple-span')) {
			$n.removeClass('Apple-span');
		}
		if (n.nodeName == 'SPAN' && !$n.attr('style') && !$n.attr('class') && !$n.attr('id')) {
			$n.replaceWith($n.html());
		}
	}
	
	this.cleanChildNodes = function(n) {
		var cmd = this.cleanNode;
		$(n).children().each(function() { cmd(this); });
	}
	
	/********************************************************/
	/*                       Таблицы                        */
	/********************************************************/
	
	this.tableMatrix = function(n) {
		var mx = [];
		if (n && n.nodeName == 'TABLE') {
			var max = 0;
			function _pos(r) {
				for (var i=0; i<=max; i++) {
					if (!mx[r][i]) {
						return i;
					}
				};
			}
			
			$(n).find('tr').each(function(r) {
				if (!$.isArray(mx[r])) {
					mx[r] = [];
				}
				
				$(this).children('td,th').each(function() {
					var w = parseInt($(this).attr('colspan')||1);
					var h = parseInt($(this).attr('rowspan')||1);
					var i = _pos(r)
					for (var y=0; y<h; y++) {
						for (var x=0; x<w; x++) {
							var _y = r+y
							if (!$.isArray(mx[_y])) {
								mx[_y] = [];
							}
							var d = x==0 && y==0 ? this : (y==0 ? x : "-");
							mx[_y][i+x] = d;
						}
					};
					max= Math.max(max, mx[r].length);
				});
			});
		}
		return mx;
	}
	
	this.indexesOfCell = function(n, tbm) {
		for (var rnum=0; rnum < tbm.length; rnum++) {
			for (var cnum=0; cnum < tbm[rnum].length; cnum++) {
				if (tbm[rnum][cnum] == n) {
					return [rnum, cnum];
				}
				
			};
		};
	}
	
	this.fixTable = function(n) {
		if (n && n.nodeName == 'TABLE') {
			var tb = $(n);
			//tb.find('tr:empty').remove();
			var mx = this.tableMatrix(n);
			var x  = 0;
			$.each(mx, function() {
				x = Math.max(x, this.length);
			});
			if (x==0) {
				return tb.remove();
			}
			// for (var i=0; i<mx.length; i++) {
			// 	this.rte.log(mx[i]);
			// }
			
			for (var r=0; r<mx.length; r++) {
				var l = mx[r].length;
				//this.rte.log(r+' : '+l)
				
				if (l==0) {
					//this.rte.log('remove: '+tb.find('tr').eq(r))
					tb.find('tr').eq(r).remove();
//					tb.find('tr').eq(r).append('<td>remove</td>')
				} else if (l<x) {
					var cnt = x-l;
					var row = tb.find('tr').eq(r);
					for (i=0; i<cnt; i++) {
						row.append('<td>&nbsp;</td>');
					}
				}
			}
			
		}
	}
	
	this.tableColumn = function(n, ext, fix) {
		n      = this.selfOrParent(n, /^TD|TH$/);
		var tb = this.selfOrParent(n, /^TABLE$/);
		ret    = [];
		info   = {offset : [], delta : []};
		if (n && tb) {
			fix && this.fixTable(tb)
			var mx = this.tableMatrix(tb);
			var _s = false;
			var x;
			for (var r=0; r<mx.length; r++) {
				for (var _x=0; _x<mx[r].length; _x++) {
					if (mx[r][_x] == n) {
						x = _x;
						_s = true;
						break;
					}
				}
				if (_s) {
					break;
				}
			}
			
			// this.rte.log('matrix');
			// for (var i=0; i<mx.length; i++) {
			// 	this.rte.log(mx[i]);
			// }
			if (x>=0) {
				for(var r=0; r<mx.length; r++) {
					var tmp = mx[r][x]||null;
					if (tmp) {
						if (tmp.nodeName) {
							ret.push(tmp);
							if (ext) {
								info.delta.push(0);
								info.offset.push(x);
							}
						} else {
							var d = parseInt(tmp);
							if (!isNaN(d) && mx[r][x-d] && mx[r][x-d].nodeName) {
								ret.push(mx[r][x-d]);
								if (ext) {
									info.delta.push(d);
									info.offset.push(x);
								}
							}
						}
					}
				}
			}
		}
		return !ext ? ret : {column : ret, info : info};
	}


}


/**
 * @class selection  - объект для работы с текстовым выделением кроссбраузерно
 *
 * @param  elRTE  rte  объект-редактор
 **/
	

elRTE.prototype.selection = function(rte) {
	this.rte     = rte;
	var self     = this;
	this.w3cRange = null;
	var start, end, node, bm;
	
	$(this.rte.doc)
		.keyup(function(e) {
			if (e.ctrlKey || e.metaKey || (e.keyCode >= 8 && e.keyCode <= 13) || (e.keyCode>=32 && e.keyCode<= 40) || e.keyCode == 46 || (e.keyCode >=96 && e.keyCode <= 111)) {
				self.cleanCache();
			}
		})
		.mousedown(function(e) {
			if (e.target.nodeName == 'HTML') {
				start = self.rte.doc.body;
			} else {
				start = e.target;
			}
			end   = node = null;
		})
		.mouseup(function(e) {
			if (e.target.nodeName == 'HTML') {
				end = self.rte.doc.body;
			} else {
				end = e.target;
			}
			end  = e.target;
			node = null;
		}).click();
		
	/**
	 * возвращает selection
	 *
	 * @return  Selection
	 **/
	function selection() {
		return self.rte.window.getSelection ? self.rte.window.getSelection() : self.rte.window.document.selection;
	}
	
	/**
	 * Вспомогательная функция
	 * Возвращает самого верхнего родителя, отвечающего условию - текущая нода - его единственная непустая дочерняя нода
	 *
	 * @param   DOMElement  n нода, для которой ищем родителя
	 * @param   DOMElement  p если задана - нода, выше которой не поднимаемся
	 * @param   String      s строна поиска (left||right||null)
	 * @return  DOMElement
	 **/
	function realSelected(n, p, s) {
		while (n.nodeName != 'BODY' && n.parentNode && n.parentNode.nodeName != 'BODY' && (p ? n!== p && n.parentNode != p : 1) && ((s=='left' && self.rte.dom.isFirstNotEmpty(n)) || (s=='right' && self.rte.dom.isLastNotEmpty(n)) || (self.rte.dom.isFirstNotEmpty(n) && self.rte.dom.isLastNotEmpty(n))) ) {
			n = n.parentNode;
		}
		return n;
	}
	
	/**
	 * Возвращает TRUE, если выделение "схлопнуто"
	 *
	 * @return  bool
	 **/
	this.collapsed = function() {
		return this.getRangeAt().isCollapsed();
	}
	
	/**
	 * "Схлопывает" выделение 
	 *
	 * @param   bool  toStart  схлопнуть к начальной точке
	 * @return  void
	 **/
	this.collapse = function(toStart) {
		this.getRangeAt().collapse(toStart ? true : false);
	}
	
	/**
	 * Возвращает TextRange
	 * Для нормальных браузеров - нативный range
	 * для "самизнаетечего" - эмуляцию w3c range
	 *
	 * @return  range|w3cRange
	 **/
	this.getRangeAt = function(updateW3cRange) {
		if (this.rte.browser.msie) {
			if (!this.w3cRange) {
				this.w3cRange = new this.rte.w3cRange(this.rte);
			}
			updateW3cRange && this.w3cRange.update();
			return this.w3cRange;
		}
		
		var s = selection();
		var r = s.rangeCount > 0 ? s.getRangeAt(0) : this.rte.doc.createRange();
		r.getStart = function() {
			return this.startContainer.nodeType==1 
				? this.startContainer.childNodes[Math.min(this.startOffset, this.startContainer.childNodes.length-1)] 
				: this.startContainer;
		}
		
		r.getEnd = function() {
			return this.endContainer.nodeType==1 
				? this.endContainer.childNodes[ Math.min(this.startOffset == this.endOffset ? this.endOffset : this.endOffset-1, this.endContainer.childNodes.length-1)] 
				: this.endContainer;
		}
		r.isCollapsed = function() {
			return this.collapsed;
		}
		return r;
	}
	
	this.saveIERange = function() {
		if ($.browser.msie) {
			bm = this.getRangeAt().getBookmark();
		}
	}
	
	this.restoreIERange = function() {
		$.browser.msie && bm && this.getRangeAt().moveToBookmark(bm);
	}
	
	/**
	 * Выделяет ноды
	 *
	 * @param   DOMNode  s  нода начала выделения
	 * @param   DOMNode  e  нода конца выделения
	 * @return  selection
	 **/
	this.select = function(s, e) {
		e = e||s;
		var r = this.getRangeAt();
		r.setStartBefore(s);
		r.setEndAfter(e);
		if (this.rte.browser.msie) {
			r.select();
		} else {
			var s = selection();
			s.removeAllRanges();
			s.addRange(r);
		}
		return this.cleanCache();
	}
	
	/**
	 * Выделяет содержимое ноды
	 *
	 * @param   Element  n  нода
	 * @return  selection
	 **/
	this.selectContents = function(n) {
		var r = this.getRangeAt();
		if (n && n.nodeType == 1) {
			if (this.rte.browser.msie) {
				r.range();
				r.r.moveToElementText(n.parentNode);
				r.r.select();
			} else {
				try {
					r.selectNodeContents(n);
				} catch (e) {
					return this.rte.log('unable select node contents '+n);
				}
				var s = selection();
				s.removeAllRanges();
				s.addRange(r);
			}
		}
		return this;
	}
	
	/**
	 * Вставляет ноду в текущее выделение
	 *
	 * @param   Element  n  нода
	 * @return  selection
	 **/
	this.insertNode = function(n, collapse) {
		if (collapse && !this.collapsed()) {
			this.collapse();
		}

		if (this.rte.browser.msie) {
			var html = n.nodeType == 3 ? n.nodeValue : $(this.rte.dom.create('span')).append($(n)).html();
			 var r = this.getRangeAt();
			r.insertNode(html);
		} else {
			var r = this.getRangeAt();
			r.insertNode(n);
			r.setStartAfter(n);
			r.setEndAfter(n);
			var s = selection();
			s.removeAllRanges();
			s.addRange(r);
		}
		return this.cleanCache();
	}

	/**
	 * Вставляет html в текущее выделение
	 *
	 * @param   Element  n  нода
	 * @return  selection
	 **/
	this.insertHtml = function(html, collapse) {
		if (collapse && !this.collapsed()) {
			this.collapse();
		}
		
		if (this.rte.browser.msie) {
			this.getRangeAt().range().pasteHTML(html);
		} else {
			var n = $(this.rte.dom.create('span')).html(html||'').get(0);
			this.insertNode(n);
			var next = this.rte.dom.next(n);
			$(n).replaceWith($(n).html());
			if (next) {
				this.select(next).collapse(true);
			}
		}
		return this.cleanCache();
	}

	/**
	 * Вставляет ноду в текущее выделение
	 *
	 * @param   Element  n  нода
	 * @return  selection
	 **/
	this.insertText = function(text, collapse) {
		var n = this.rte.doc.createTextNode(text);
		return this.insertHtml(n.nodeValue);
	}

	/**
	 * Очищает кэш
	 *
	 * @return  selection
	 **/
	this.cleanCache = function() {
	//	this.rte.log('clean cache');
		start = end = node = null;
		return this;
	}

	
	/**
	 * Возвращает ноду начала выделения
	 *
	 * @return  DOMElement
	 **/
	this.getStart = function() {
		if (!start) {
			var r = this.getRangeAt();
			start = r.getStart();
		}
		return start;
	}
	
	/**
	 * Возвращает ноду конца выделения
	 *
	 * @return  DOMElement
	 **/
	this.getEnd = function() {
		if (!end) {
			var r = this.getRangeAt();
			end = r.getEnd();
		}
		return end;
	}

	/**
	 * Возвращает выбраную ноду (общий контейнер всех выбранных нод)
	 *
	 * @return  Element
	 **/
	this.getNode = function() {
		if (!node) {
			node = this.rte.dom.findCommonAncestor(this.getStart(), this.getEnd());
		}
		return node;
	}

	
	/**
	 * Возвращает массив выбранных нод
	 *
	 * @param   Object  o  параметры получения и обработки выбраных нод
	 * @return  Array
	 **/
	this.selected = function(o) {
		var opts = {
			collapsed : false,  // вернуть выделение, даже если оно схлопнуто
			blocks    : false,  // блочное выделение
			filter    : false,  // фильтр результатов
			wrap      : 'text', // что оборачиваем
			tag       : 'span'  // во что оборачиваем
		}
		opts = $.extend({}, opts, o);
		
		// блочное выделение - ищем блочную ноду, но не таблицу
		if (opts.blocks) {
			var n  = this.getNode();
			var _n = null; //this.rte.dom.selfOrParent(n, /^P$/);
			if (_n = this.rte.dom.selfOrParent(n, 'selectionBlock') ) {
				return [_n];
			} 
		}

		var sel    = this.selectedRaw(opts.collapsed, opts.blocks);
		var ret    = [];
		var buffer = [];
		var ndx    = null;
		// this.rte.log('selected')
		// this.rte.log(sel.nodes)

		// оборачиваем ноды в буффере
		function wrap() {
			
			function allowParagraph() {
				for (var i=0; i < buffer.length; i++) {
					if (buffer[i].nodeType == 1 && (self.rte.dom.selfOrParent(buffer[i], /^P$/) || $(buffer[i]).find('p').length>0)) {
						return false;
					}
				};
				return true;
			} 
			
			if (buffer.length>0) {
				var tag  = opts.tag == 'p' && !allowParagraph() ? 'div' : opts.tag;
				var n    = self.rte.dom.wrap(buffer, tag);
				ret[ndx] = n;
				ndx      = null;
				buffer   = [];
			}
		}
		
		// добавляем ноды в буффер
		function addToBuffer(n) {
			if (n.nodeType == 1) {
				if (/^(THEAD|TFOOT|TBODY|COL|COLGROUP|TR)$/.test(n.nodeName)) {
					$(n).find('td,th').each(function() {
						var tag = opts.tag == 'p' && $(this).find('p').length>0 ? 'div' : opts.tag;
						var n = self.rte.dom.wrapContents(this, tag);
						return ret.push(n);
					})
				} else if (/^(CAPTION|TD|TH|LI|DT|DD)$/.test(n.nodeName)) {
					var tag = opts.tag == 'p' && $(n).find('p').length>0 ? 'div' : opts.tag;
					var n = self.rte.dom.wrapContents(n, tag);
					return ret.push(n);
				} 
			} 
			var prev = buffer.length>0 ? buffer[buffer.length-1] : null;
			if (prev && prev != self.rte.dom.prev(n)) {
				wrap();
			}
			buffer.push(n); 
			if (ndx === null) {
				ndx = ret.length;
				ret.push('dummy'); // заглушка для оборачиваемых элементов
			}
		}
		
		if (sel.nodes.length>0) {
			
			for (var i=0; i < sel.nodes.length; i++) {
				var n = sel.nodes[i];
					// первую и посл текстовые ноды разрезаем, если необходимо
					 if (n.nodeType == 3 && (i==0 || i == sel.nodes.length-1) && $.trim(n.nodeValue).length>0) {
						if (i==0 && sel.so>0) {
							n = n.splitText(sel.so);
						}
						if (i == sel.nodes.length-1 && sel.eo>0) {
							n.splitText(i==0 && sel.so>0 ? sel.eo - sel.so : sel.eo);
						}
					}

					switch (opts.wrap) {
						// оборачиваем только текстовые ноды с br
						case 'text':
							if ((n.nodeType == 1 && n.nodeName == 'BR') || (n.nodeType == 3 && $.trim(n.nodeValue).length>0)) {
								addToBuffer(n);
							} else if (n.nodeType == 1) {
								ret.push(n);
							}
							break;
						// оборачиваем все инлайн элементы	
						case 'inline':
							if (this.rte.dom.isInline(n)) {
								addToBuffer(n);
							} else if (n.nodeType == 1) {
								
								ret.push(n);
							}
							break;
						// оборачиваем все	
						case 'all':
							if (n.nodeType == 1 || !this.rte.dom.isEmpty(n)) {
								addToBuffer(n);
							}
							break;
						// ничего не оборачиваем
						default:
							if (n.nodeType == 1 || !this.rte.dom.isEmpty(n)) {
								ret.push(n);
							}
					}
			};
			wrap();
		}
		// this.rte.log('buffer')
		// this.rte.log(buffer)
		// this.rte.log('ret')
		// this.rte.log(ret)		
		return opts.filter ? this.rte.dom.filter(ret, opts.filter) : ret;
	}

	this.dump = function(ca, s, e, so, eo) {
		var r = this.getRangeAt();
		this.rte.log('commonAncestorContainer');
		this.rte.log(ca || r.commonAncestorContainer);
		// this.rte.log('commonAncestorContainer childs num')
		// this/rte.log((ca||r.commonAncestorContainer).childNodes.length)
		this.rte.log('startContainer');
		this.rte.log(s || r.startContainer);
		this.rte.log('startOffset: '+(so>=0 ? so : r.startOffset));
		this.rte.log('endContainer');
		this.rte.log(e||r.endContainer);
		this.rte.log('endOffset: '+(eo>=0 ? eo : r.endOffset));
	}

	/**
	 * Возвращает массив выбранных нод, как есть
	 *
	 * @param   bool           возвращать если выделение схлопнуто
	 * @param   bool           "блочное" выделение (текстовые ноды включаются полностью, не зависимо от offset)
	 * @return  Array
	 **/
	this.selectedRaw = function(collapsed, blocks) {
		var res = {so : null, eo : null, nodes : []};
		var r   = this.getRangeAt(true);
		var ca  = r.commonAncestorContainer;
		var s, e;  // start & end nodes
		var sf  = false; // start node fully selected
		var ef  = false; // end node fully selected
		
		// возвращает true, если нода не текстовая или выделена полностью
		function isFullySelected(n, s, e) {
			if (n.nodeType == 3) {
				e = e>=0 ? e : n.nodeValue.length;
				return (s==0 && e==n.nodeValue.length) || $.trim(n.nodeValue).length == $.trim(n.nodeValue.substring(s, e)).length;
			} 
			return true;
		}
		
		// возвращает true, если нода пустая или в ней не выделено ни одного непробельного символа
		function isEmptySelected(n, s, e) {
			if (n.nodeType == 1) {
				return self.rte.dom.isEmpty(n);
			} else if (n.nodeType == 3) {
				return $.trim(n.nodeValue.substring(s||0, e>=0 ? e : n.nodeValue.length)).length == 0;
			} 
			return true;
		}
		
		
		//this.dump()
		// начальная нода
		if (r.startContainer.nodeType == 1) {
			if (r.startOffset<r.startContainer.childNodes.length) {
				s = r.startContainer.childNodes[r.startOffset];
				res.so = s.nodeType == 1 ? null : 0;
			} else {
				s = r.startContainer.childNodes[r.startOffset-1];
				res.so = s.nodeType == 1 ? null : s.nodeValue.length;
			}
		} else {
			s = r.startContainer;
			res.so = r.startOffset;
		} 
		
		// выделение схлопнуто
		if (r.collapsed) {
			if (collapsed) {
				//  блочное выделение
				if (blocks) {
					s = realSelected(s);
					if (!this.rte.dom.isEmpty(s) || (s = this.rte.dom.next(s))) {
						res.nodes = [s];
					} 
					
					// добавляем инлайн соседей 
					if (this.rte.dom.isInline(s)) {
						res.nodes = this.rte.dom.toLineStart(s).concat(res.nodes, this.rte.dom.toLineEnd(s));
					}
					
					// offset для текстовых нод
					if (res.nodes.length>0) {
						res.so = res.nodes[0].nodeType == 1 ? null : 0;
						res.eo = res.nodes[res.nodes.length-1].nodeType == 1 ? null : res.nodes[res.nodes.length-1].nodeValue.length;
					}
					
				} else if (!this.rte.dom.isEmpty(s)) {
					res.nodes = [s];
				}
				
			}
			return res;
		}
		
		// конечная нода
		if (r.endContainer.nodeType == 1) {
			e = r.endContainer.childNodes[r.endOffset-1];
			res.eo = e.nodeType == 1 ? null : e.nodeValue.length;
		} else {
			e = r.endContainer;
			res.eo = r.endOffset;
		} 
		// this.rte.log('select 1')
		//this.dump(ca, s, e, res.so, res.eo)
		
		// начальная нода выделена полностью - поднимаемся наверх по левой стороне
		if (s.nodeType == 1 || blocks || isFullySelected(s, res.so, s.nodeValue.length)) {
//			this.rte.log('start text node is fully selected')
			s = realSelected(s, ca, 'left');
			sf = true;
			res.so = s.nodeType == 1 ? null : 0;
		}
		// конечная нода выделена полностью - поднимаемся наверх по правой стороне
		if (e.nodeType == 1 || blocks || isFullySelected(e, 0,  res.eo)) {
//			this.rte.log('end text node is fully selected')
			e = realSelected(e, ca, 'right');
			ef = true;
			res.eo = e.nodeType == 1 ? null : e.nodeValue.length;
		}

		// блочное выделение - если ноды не элементы - поднимаемся к родителю, но ниже контейнера
		if (blocks) {
			if (s.nodeType != 1 && s.parentNode != ca && s.parentNode.nodeName != 'BODY') {
				s = s.parentNode;
				res.so = null;
			}
			if (e.nodeType != 1 && e.parentNode != ca && e.parentNode.nodeName != 'BODY') {
				e = e.parentNode;
				res.eo = null;
			}
		}

		// если контенер выделен полностью, поднимаемся наверх насколько можно
		if (s.parentNode == e.parentNode && s.parentNode.nodeName != 'BODY' && (sf && this.rte.dom.isFirstNotEmpty(s)) && (ef && this.rte.dom.isLastNotEmpty(e))) {
//			this.rte.log('common parent')
			s = e = s.parentNode;
			res.so = s.nodeType == 1 ? null : 0;
			res.eo = e.nodeType == 1 ? null : e.nodeValue.length;
		}
		// начальная нода == конечной ноде
		if (s == e) {
//			this.rte.log('start is end')
			if (!this.rte.dom.isEmpty(s)) {
				res.nodes.push(s);
			}
			return res;
		}
		 // this.rte.log('start 2')
		  //this.dump(ca, s, e, res.so, res.eo)
		
		// находим начальную и конечную точки - ноды из иерархии родителей начальной и конечно ноды, у которых родитель - контейнер
		var sp = s;
		while (sp.nodeName != 'BODY' && sp.parentNode !== ca && sp.parentNode.nodeName != 'BODY') {
			sp = sp.parentNode;
		}
		//this.rte.log(s.nodeName)
		// this.rte.log('start point')
		// this.rte.log(sp)
		
		var ep = e;
//		this.rte.log(ep)
		while (ep.nodeName != 'BODY' && ep.parentNode !== ca && ep.parentNode.nodeName != 'BODY') {
			this.rte.log(ep)
			ep = ep.parentNode;
		}
		// this.rte.log('end point')
		// this.rte.log(ep)
		
		
		//  если начальная нода не пустая - добавляем ее
		if (!isEmptySelected(s, res.so, s.nodeType==3 ? s.nodeValue.length : null)) {
			res.nodes.push(s);
		}
		// поднимаемся от начальной ноды до начальной точки
		var n = s;
		while (n !== sp) {
			var _n = n;
			while ((_n = this.rte.dom.next(_n))) {
					res.nodes.push(_n);
			}
			n = n.parentNode;
		}
		// от начальной точки до конечной точки
		n = sp;
		while ((n = this.rte.dom.next(n)) && n!= ep ) {
//			this.rte.log(n)
			res.nodes.push(n);
		}
		// поднимаемся от конечной ноды до конечной точки, результат переворачиваем
		var tmp = [];
		n = e;
		while (n !== ep) {
			var _n = n;
			while ((_n = this.rte.dom.prev(_n))) {
				tmp.push(_n);
			}
			n = n.parentNode;
		}
		if (tmp.length) {
			res.nodes = res.nodes.concat(tmp.reverse());
		}
		//  если конечная нода не пустая и != начальной - добавляем ее
		if (!isEmptySelected(e, 0, e.nodeType==3 ? res.eo : null)) {
			res.nodes.push(e);
		}
		
		if (blocks) {
			// добавляем инлайн соседей слева
			if (this.rte.dom.isInline(s)) {
				res.nodes = this.rte.dom.toLineStart(s).concat(res.nodes);
				res.so    = res.nodes[0].nodeType == 1 ? null : 0;
			}
			// добавляем инлайн соседей справа
			if (this.rte.dom.isInline(e)) {
				res.nodes = res.nodes.concat(this.rte.dom.toLineEnd(e));
				res.eo    = res.nodes[res.nodes.length-1].nodeType == 1 ? null : res.nodes[res.nodes.length-1].nodeValue.length;
			}
		}
		
		// все радуются! :)
		return res;
	}
	
}

/**
 * @class w3cRange  - эмуляция "правильного" range для неправильных браузеров
 *
 * @param  elRTE  rte  объект-редактор
 **/
elRTE.prototype.w3cRange = function(rte) {
	var self                     = this;
	this.rte                     = rte;
	this.r                       = null;
	this.collapsed               = true;
	this.startContainer          = null;
	this.endContainer            = null;
	this.startOffset             = 0;
	this.endOffset               = 0;
	this.commonAncestorContainer = null;
	
	this.range = function() {
		try { 
			this.r = this.rte.window.document.selection.createRange(); 
		} catch(e) { 
			this.r = this.rte.doc.body.createTextRange(); 
		}
		return this.r;
	}
	
	this.insertNode = function(html) {
		this.range();
		self.r.collapse(false)
		var r = self.r.duplicate();
		r.pasteHTML(html);
	}
	
	this.getBookmark = function() {
		this.range();
		if (this.r.item) {
			var n = this.r.item(0);
			this.r = this.rte.doc.body.createTextRange();
			this.r.moveToElementText(n);
		}
		return this.r.getBookmark();
	}
	
	this.moveToBookmark = function(bm) {
		this.rte.window.focus();
		this.range().moveToBookmark(bm);
		this.r.select();
	}
	
	/**
	 * Обновляет данные о выделенных нодах
	 *
	 * @return void
	 **/
	this.update = function() {

		function _findPos(start) {
			var marker = '\uFEFF';
			var ndx = offset = 0;
			var r = self.r.duplicate();
			r.collapse(start);
			var p = r.parentElement();
			if (!p || p.nodeName == 'HTML') {
				return {parent : self.rte.doc.body, ndx : ndx, offset : offset};
			}

			r.pasteHTML(marker);
			
			childs = p.childNodes;
			for (var i=0; i < childs.length; i++) {
				var n = childs[i];
				if (i>0 && (n.nodeType!==3 || childs[i-1].nodeType !==3)) {
					ndx++;
				}
				if (n.nodeType !== 3) {
					offset = 0;
				} else {
					var pos = n.nodeValue.indexOf(marker);
					if (pos !== -1) {
						offset += pos;
						break;
					}
					offset += n.nodeValue.length;
				}
			};
			r.moveStart('character', -1);
			r.text = '';
			return {parent : p, ndx : Math.min(ndx, p.childNodes.length-1), offset : offset};
		}

		this.range();
		this.startContainer = this.endContainer = null;

		if (this.r.item) {
			this.collapsed = false;
			var i = this.r.item(0);
			this.setStart(i.parentNode, this.rte.dom.indexOf(i));
			this.setEnd(i.parentNode, this.startOffset+1);
		} else {
			this.collapsed = this.r.boundingWidth == 0;
			var start = _findPos(true); 
			var end   = _findPos(false);
			
			start.parent.normalize();
			end.parent.normalize();
			start.ndx = Math.min(start.ndx, start.parent.childNodes.length-1);
			end.ndx = Math.min(end.ndx, end.parent.childNodes.length-1);
			if (start.parent.childNodes[start.ndx].nodeType && start.parent.childNodes[start.ndx].nodeType == 1) {
				this.setStart(start.parent, start.ndx);
			} else {
				this.setStart(start.parent.childNodes[start.ndx], start.offset);
			}
			if (end.parent.childNodes[end.ndx].nodeType && end.parent.childNodes[end.ndx].nodeType == 1) {
				this.setEnd(end.parent, end.ndx);
			} else {
				this.setEnd(end.parent.childNodes[end.ndx], end.offset);
			}
			// this.dump();
			this.select();
		}
		return this;
	}
	
	this.isCollapsed = function() {
		this.range();
		this.collapsed = this.r.item ? false : this.r.boundingWidth == 0;
		return this.collapsed;
	}
	
	/**
	 * "Схлопывает" выделение
	 *
	 * @param  bool  toStart - схлопывать выделение к началу или к концу
	 * @return void
	 **/
	this.collapse = function(toStart) {
		this.range();
		if (this.r.item) {
			var n = this.r.item(0);
			this.r = this.rte.doc.body.createTextRange();
			this.r.moveToElementText(n);
		}
		this.r.collapse(toStart);
		this.r.select();
		this.collapsed = true;
	}

	this.getStart = function() {
		this.range();
		if (this.r.item) {
			return this.r.item(0);
		}
		var r = this.r.duplicate();
		r.collapse(true);
		var s = r.parentElement();
		return s && s.nodeName == 'BODY' ? s.firstChild : s;
	}
	
	
	this.getEnd = function() {
		this.range();
		if (this.r.item) {
			return this.r.item(0);
		}
		var r = this.r.duplicate();
		r.collapse(false);
		var e = r.parentElement();
		return e && e.nodeName == 'BODY' ? e.lastChild : e;
	}

	
	/**
	 * Устанавливает начaло выделения на указаную ноду
	 *
	 * @param  Element  node    нода
	 * @param  Number   offset  отступ от начала ноды
	 * @return void
	 **/
	this.setStart = function(node, offset) {
		this.startContainer = node;
		this.startOffset    = offset;
		if (this.endContainer) {
			this.commonAncestorContainer = this.rte.dom.findCommonAncestor(this.startContainer, this.endContainer);
		}
	}
	
	/**
	 * Устанавливает конец выделения на указаную ноду
	 *
	 * @param  Element  node    нода
	 * @param  Number   offset  отступ от конца ноды
	 * @return void
	 **/
	this.setEnd = function(node, offset) {
		this.endContainer = node;
		this.endOffset    = offset;
		if (this.startContainer) {
			this.commonAncestorContainer = this.rte.dom.findCommonAncestor(this.startContainer, this.endContainer);
		}
	}
	
	/**
	 * Устанавливает начaло выделения перед указаной нодой
	 *
	 * @param  Element  node    нода
	 * @return void
	 **/
	this.setStartBefore = function(n) {
		if (n.parentNode) {
			this.setStart(n.parentNode, this.rte.dom.indexOf(n));
		}
	}
	
	/**
	 * Устанавливает начaло выделения после указаной ноды
	 *
	 * @param  Element  node    нода
	 * @return void
	 **/
	this.setStartAfter = function(n) {
		if (n.parentNode) {
			this.setStart(n.parentNode, this.rte.dom.indexOf(n)+1);
		}
	}
	
	/**
	 * Устанавливает конец выделения перед указаной нодой
	 *
	 * @param  Element  node    нода
	 * @return void
	 **/
	this.setEndBefore = function(n) {
		if (n.parentNode) {
			this.setEnd(n.parentNode, this.rte.dom.indexOf(n));
		}
	}
	
	/**
	 * Устанавливает конец выделения после указаной ноды
	 *
	 * @param  Element  node    нода
	 * @return void
	 **/
	this.setEndAfter = function(n) {
		if (n.parentNode) {
			this.setEnd(n.parentNode, this.rte.dom.indexOf(n)+1);
		}
	}
	
	/**
	 * Устанавливает новое выделение после изменений
	 *
	 * @return void
	 **/
	this.select = function() {
		// thanks tinymice authors
		function getPos(n, o) {
			if (n.nodeType != 3) {
				return -1;
			}
			var c   ='\uFEFF';
			var val = n.nodeValue;
			var r   = self.rte.doc.body.createTextRange();
			n.nodeValue = val.substring(0, o) + c + val.substring(o);
			r.moveToElementText(n.parentNode);
			r.findText(c);
			var p = Math.abs(r.moveStart('character', -0xFFFFF));
			n.nodeValue = val;
			return p;
		};
		
		this.r = this.rte.doc.body.createTextRange(); 
		var so = this.startOffset;
		var eo = this.endOffset;
		var s = this.startContainer.nodeType == 1 
			? this.startContainer.childNodes[Math.min(so, this.startContainer.childNodes.length - 1)]
			: this.startContainer;
		var e = this.endContainer.nodeType == 1 
			? this.endContainer.childNodes[Math.min(so == eo ? eo : eo - 1, this.endContainer.childNodes.length - 1)]
			: this.endContainer;

		if (this.collapsed) {
			if (s.nodeType == 3) {
				var p = getPos(s, so);
				this.r.move('character', p);
			} else {
				this.r.moveToElementText(s);
				this.r.collapse(true);
			}
		} else {
			var r  = this.rte.doc.body.createTextRange(); 
			var sp = getPos(s, so);
			var ep = getPos(e, eo);
			if (s.nodeType == 3) {
				this.r.move('character', sp);
			} else {
				this.r.moveToElementText(s);
			}
			if (e.nodeType == 3) {
				r.move('character', ep);
			} else {
				r.moveToElementText(e);
			}
			this.r.setEndPoint('EndToEnd', r);
		}
		
		try {
			this.r.select();
		} catch(e) {
			
		}
		if (r) {
			r = null;
		}
	}
	
	this.dump = function() {
		this.rte.log('collapsed: '+this.collapsed);
		//this.rte.log('commonAncestorContainer: '+this.commonAncestorContainer.nodeName||'#text')
		this.rte.log('startContainer: '+(this.startContainer ? this.startContainer.nodeName : 'non'));
		this.rte.log('startOffset: '+this.startOffset);
		this.rte.log('endContainer: '+(this.endContainer ? this.endContainer.nodeName : 'none'));
		this.rte.log('endOffset: '+this.endOffset);
	}
	
}

elRTE.prototype.utils = function(rte) {
	this.rte     = rte;
	this.url     = null;
	// domo arigato, Steave, http://blog.stevenlevithan.com/archives/parseuri
	this.reg     = /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/;
	this.baseURL = '';
	this.path    = '';
	var self     = this;
	
	this.rgb2hex = function(str) {
	    function hex(x)  {
	    	hexDigits = ["0", "1", "2", "3", "4", "5", "6", "7", "8","9", "a", "b", "c", "d", "e", "f"];
	        return !x  ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x% 16];
	    }
		var rgb = str.match(/\(([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})\)/); 
		return rgb ? "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]) : '';
	}
	
	this.toPixels = function(num) {
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
	
	// TODO: add parse rel path ../../etc
	this.absoluteURL = function(url) {
		!this.url && this._url();
		url = $.trim(url);
		if (!url) {
			return '';
		}
		// ссылки на якоря не переводим в абс
		if (url[0] == '#') {
			return url;
		}
		var u = this.parseURL(url);
		//this.rte.log(u)

		if (!u.host && !u.path && !u.anchor) {
			//this.rte.log('Invalid URL: '+url)
			return '';
		}
		if (!this.rte.options.absoluteURLs) { 
			return url;
		}
		if (u.protocol) {
			//this.rte.log('url already absolute: '+url);
			return url;
		}
		if (u.host && (u.host.indexOf('.')!=-1 || u.host == 'localhost')) {
			//this.rte.log('no protocol');
			return this.url.protocol+'://'+url;
		}
		if (url[0] == '/') {
			url = this.baseURL+url;
		} else {
			if (url.indexOf('./') == 0) {
				url = url.substring(2);
			}
			url = this.baseURL+this.path+url;
		}
		return url;
	}
	
	this.parseURL = function(url) {
		var u   = url.match(this.reg);
		var ret = {};
		$.each(["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"], function(i) {
			ret[this] = u[i];
		});
		if (!ret.host.match(/[a-z0-9]/i)) {
			ret.host = '';
		}
		return ret;
	}
	
	this.trimEventCallback = function(c) {
		c = c ? c.toString() : '';
		return $.trim(c.replace(/\r*\n/mg, '').replace(/^function\s*on[a-z]+\s*\(\s*event\s*\)\s*\{(.+)\}$/igm, '$1'));
	}
	
	this._url = function() {
		this.url     = this.parseURL(window.location.href);
		this.baseURL = this.url.protocol+'://'+(this.url.userInfo ?  parts.userInfo+'@' : '')+this.url.host+(this.url.port ? ':'+this.url.port : '');
		this.path    = !this.url.file ? this.url.path : this.url.path.substring(0, this.url.path.length - this.url.file.length);
	}
	
	
}

/**
 * @class User interface редактора
 *
 * @param  elRTE  rte объект-редактор
 * @param  function  updateEditor - callback при переключении между редактор м источником
 * @param  function  updateSource - callback при переключении между редактор м источником
 **/
elRTE.prototype.ui = function(rte) {
	var self      = this;
	this.rte      = rte;
	this._buttons = [];
	
	for (var i in this.buttons) {
		if (i != 'button') {
			this.buttons[i].prototype = this.buttons.button.prototype;
		}
	}
	
	// создаем панели и кнопки
	var toolbar = rte.options.toolbar && rte.options.toolbars[rte.options.toolbar] ? rte.options.toolbar : 'normal';
	var panels  = this.rte.options.toolbars[toolbar];
	for (var i in panels) {
		var name = panels[i];
		
		var panel = $('<ul />').addClass('panel-'.name).appendTo(this.rte.toolbar);
		if (i == 0) {
			panel.addClass('first');
		}
		for (var j in this.rte.options.panels[name]) {
			var n = this.rte.options.panels[name][j];
			var c = this.buttons[n] || this.buttons.button; 
			var b = new c(this.rte, n);
			panel.append(b.domElem);
			this._buttons.push(b);
		}
	}


	/**
	 * Переключает вид редактора между окном редактирования и исходника
	 **/
	this.rte.tabsbar.children('.tab').click(function(e) {
		if (!$(e.currentTarget).hasClass('active')) {
			self.rte.tabsbar.children('.tab').toggleClass('active');
			self.rte.workzone.children().toggle();
			if ($(e.currentTarget).hasClass('editor')) {
				self.rte.updateEditor();
			} else {
				self.rte.updateSource();
				$.each(self._buttons, function() {
					!this.active && this.domElem.addClass('disabled');
				});
				self.rte.source.focus();
			}
			
		}
	});

	this.update();
}

/**
 * Обновляет кнопки - вызывает метод update() для каждой кнопки
 *
 * @return void
 **/
elRTE.prototype.ui.prototype.update = function(cleanCache) {
	cleanCache && this.rte.selection.cleanCache();
	var n    = this.rte.selection.getNode();
	var p    = this.rte.dom.parents(n, '*');
	var path = '';
	if (p.length) {
		$.each(p.reverse(), function() {
			path += ' &raquo; '+ this.nodeName.toLowerCase();
		});
	}
	if (n.nodeType == 1 && n.nodeName != 'BODY') {
		path += ' &raquo; '+ n.nodeName.toLowerCase();
	}
	this.rte.statusbar.html(path)
	$.each(this._buttons, function() {
		this.update();
	});
	this.rte.window.focus();
}



elRTE.prototype.ui.prototype.buttons = {
	
	/**
	 * @class кнопка на toolbar редактора 
	 * реализует поведение по умолчанию и является родителей для других кнопок
	 *
	 * @param  elRTE  rte   объект-редактор
	 * @param  String name  название кнопки (команда исполняемая document.execCommand())
	 **/
	button : function(rte, name) {
		var self     = this;
		this.rte     = rte;
		this.active = false;
		this.name    = name;
		this.val     = null;
		this.domElem = $('<li />')
			.addClass(name+' rounded-3')
			.attr({name : name, title : this.rte.i18n(this.rte.options.buttons[name] || name), unselectable : 'on'})
			.hover(
				function() { $(this).addClass('hover'); },
				function() { $(this).removeClass('hover'); }
			)
			.click( function(e) {
				e.stopPropagation();
				e.preventDefault();
				if (!$(this).hasClass('disabled')) {
					self.command();
				}
			});
	}
		
}

/**
 * Обработчик нажатия на кнопку на тулбаре. Выполнение команды или открытие окна|меню и тд
 *
 * @return void
 **/
elRTE.prototype.ui.prototype.buttons.button.prototype.command = function() {
	try {
		this.rte.doc.execCommand(this.name, false, this.val);
	} catch(e) {
		this.rte.log('commands failed: '+this.name);
	}
	this.rte.ui.update(true);
}

/**
 * Обновляет состояние кнопки
 *
 * @return void
 **/
elRTE.prototype.ui.prototype.buttons.button.prototype.update = function() {
	try {
		if (!this.rte.doc.queryCommandEnabled(this.name)) {
			return this.domElem.addClass('disabled');
		} else {
			this.domElem.removeClass('disabled');
		}
	} catch (e) {
		return;
	}
	try {
		if (this.rte.doc.queryCommandState(this.name)) {
			this.domElem.addClass('active');
		} else {
			this.domElem.removeClass('active');
		}
	} catch (e) { }
}


/**
 * @class кнопка - Закладка (открывает диалоговое окно)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.anchor = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	this.input = $('<input type="text" />').attr('name', 'anchor').attr('size', '16')
	var self = this;
	
	this.command = function() {
		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); d.close(); self.set();  },
			dialog : {
				title : this.rte.i18n('Bookmark')
			}
		}

		this.anchor = this.rte.dom.selfOrParentAnchor(this.rte.selection.getEnd()) || rte.dom.create('a');
		!this.rte.selection.collapsed() && this.rte.selection.collapse(false);
//		this.rte.log(this.anchor)
		this.input.val($(this.anchor).addClass('el-rte-anchor').attr('name'));
		this.rte.selection.saveIERange();
		var d = new elDialogForm(opts);
		d.append([this.rte.i18n('Bookmark name'), this.input], null, true).open();
	}
	
	this.update = function() {
		var n = this.rte.selection.getNode();
		if (this.rte.dom.selfOrParentLink(n)) {
			this.domElem.addClass('disabled');
		} else if (this.rte.dom.selfOrParentAnchor(n)) {
			this.domElem.removeClass('disabled').addClass('active');
		} else {
			this.domElem.removeClass('disabled').removeClass('active');
		}
	}
	
	this.set = function() {
		var n = $.trim(this.input.val());


		this.rte.selection.restoreIERange();
		this.rte.log(this.anchor.parentNode)
		if (n) {
			if (!this.anchor.parentNode) {
				this.rte.selection.insertHtml('<a name="'+n+'" title="'+this.rte.i18n('Bookmark')+': '+n+'" class="el-rte-anchor"></a>');
			} else {
				this.anchor.name = n;
				this.anchor.title = this.rte.i18n('Bookmark')+': '+n;
			}
		} else if (this.anchor.parentNode) {
			this.anchor.parentNode.removeChild(this.anchor);
		}
	}
}

/**
 * @class кнопка - Цитата
 * Если выделение схлопнуто и находится внутри цитаты - она удаляется
 * Новые цитаты создаются только из несхлопнутого выделения
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.blockquote = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		var n, nodes;
		if (this.rte.selection.collapsed() && (n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^BLOCKQUOTE$/))) {
			$(n).replaceWith($(n).html());
		} else {
			nodes = this.rte.selection.selected({wrap : 'all', tag : 'blockquote'});
			nodes.length && this.rte.selection.select(nodes[0], nodes[nodes.length-1]);
		}
		this.rte.ui.update(true);
	}
	
	this.update = function() {
		if (this.rte.selection.collapsed()) {
			if (this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^BLOCKQUOTE$/)) {
				this.domElem.removeClass('disabled').addClass('active');
			} else {
				this.domElem.addClass('disabled').removeClass('active');
			}
		} else {
			this.domElem.removeClass('disabled').removeClass('active');
		}
	}
}

/**
 * @class кнопки "копировать/вырезать/вставить" 
 * в firefox показывает предложение нажать Ctl+c, в остальных - копирует
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.copy = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		
		if (this.rte.browser.mozilla) {
			try {
				this.rte.doc.execCommand(this.name, false, null);
			} catch (e) {
				var s = ' Ctl + C';
				if (this.name == 'cut') {
					s = ' Ctl + X';
				} else if (this.name == 'paste') {
					s = ' Ctl + V';
				}
				var opts = {
					dialog : {
						title   : this.rte.i18n('Warning'),
						buttons : { Ok : function() { $(this).dialog('close'); } }
					}
				}

				var d = new elDialogForm(opts);
				d.append(this.rte.i18n('This operation is disabled in your browser on security reason. Use shortcut instead.')+': '+s).open();
			}
		} else {
			this.constructor.prototype.command.call(this);
		}
	}
}

elRTE.prototype.ui.prototype.buttons.cut   = elRTE.prototype.ui.prototype.buttons.copy;
elRTE.prototype.ui.prototype.buttons.paste = elRTE.prototype.ui.prototype.buttons.copy;

/**
 * @class кнопка - DIV
 * Если выделение схлопнуто и находится внутри div'a - он удаляется
 * Новые div'ы создаются только из несхлопнутого выделения
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.div = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		var n, nodes;
		if (this.rte.selection.collapsed() && (n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^DIV$/))) {
			$(n).replaceWith($(n).html());
		} else {
			nodes = this.rte.selection.selected({wrap : 'all', tag : 'div'});
			nodes.length && this.rte.selection.select(nodes[0], nodes[nodes.length-1]);
		}
		this.rte.ui.update(true);
	}
	
	this.update = function() {
		if (this.rte.selection.collapsed()) {
			if (this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^DIV$/)) {
				this.domElem.removeClass('disabled').addClass('active');
			} else {
				this.domElem.addClass('disabled').removeClass('active');
			}
		} else {
			this.domElem.removeClass('disabled').removeClass('active');
		}
	}
}

/**
 * @class кнопка - Включение/выключение показа структуры документа
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.docstructure = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		this.domElem.toggleClass('active');
		$(this.rte.doc.body).toggleClass('el-rte-structure');
	}
	this.command();
	
	this.update = function() {	
		this.domElem.removeClass('disabled');
	}
}


/**
 * @class кнопка - DIV
 * Если выделение схлопнуто и находится внутри div'a - он удаляется
 * Новые div'ы создаются только из несхлопнутого выделения
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.elfinder = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.command = function() {
		
		self.rte.options.fmOpen( function(url) { self.rte.log(url) } );
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
	}
}

/**
 * @class меню - шрифт
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.fontname = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	var opts = {
		tpl      : '<span style="font-family:%val">%label</span>',
		select   : function(v) { self.set(v); },
		src      : {
			''                                              : this.rte.i18n('Font'),
			'andale mono,sans-serif'                        : 'Andale Mono',
			'arial,helvetica,sans-serif'                    : 'Arial',
			'arial black,gadget,sans-serif'                 : 'Arial Black',
			'book antiqua,palatino,sans-serif'              : 'Book Antiqua',
			'comic sans ms,cursive'                         : 'Comic Sans MS',
			'courier new,courier,monospace'                 : 'Courier New',
			'georgia,palatino,serif'                        : 'Georgia',
			'helvetica,sans-serif'                          : 'Helvetica',
			'impact,sans-serif'                             : 'Impact',
			'lucida console,monaco,monospace'               : 'Lucida console',
			'lucida sans unicode,lucida grande,sans-serif'  : 'Lucida grande',
			'tahoma,sans-serif'                             : 'Tahoma',
			'times new roman,times,serif'                   : 'Times New Roman',
			'trebuchet ms,lucida grande,verdana,sans-serif' : 'Trebuchet MS',
			'verdana,geneva,sans-serif'                     : 'Verdana'
		}
	}
	
	this.select = this.domElem.elSelect(opts);
	
	this.command = function() {
	}
	
	this.set = function(size) {
		var nodes = this.rte.selection.selected({filter : 'textContainsNodes'});
		$.each(nodes, function() {
			$this = /^(THEAD|TFOOT|TBODY|COL|COLGROUP|TR)$/.test(this.nodeName) ? $(this).find('td,th') : $(this);
			$(this).css('font-family', size).find('[style]').css('font-family', '');
		});
		this.rte.ui.update();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled'); 
		var n = this.rte.selection.getNode();
		if (n.nodeType != 1) {
			n = n.parentNode;
		}
		var v = $(n).css('font-family');
		v = v ? v.toString().toLowerCase().replace(/,\s+/g, ',').replace(/'|"/g, '') : '';
		this.select.val(opts.src[v] ? v : '');
	}
	
}

/**
 * @class меню - размер шрифта
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.fontsize = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	var opts = {
		labelTpl : '%label',
		tpl      : '<span style="font-size:%val;line-height:1.2em">%label</span>',
		select   : function(v) { self.set(v); },
		src      : {
			''         : this.rte.i18n('Font size'),
			'xx-small' : this.rte.i18n('Small (8pt)'), 
			'x-small'  : this.rte.i18n('Small (10px)'), 
			'small'    : this.rte.i18n('Small (12pt)'), 
			'medium'   : this.rte.i18n('Normal (14pt)'),
			'large'    : this.rte.i18n('Large (18pt)'),
			'x-large'  : this.rte.i18n('Large (24pt)'),
			'xx-large' : this.rte.i18n('Large (36pt)')
		}
	}
	
	this.select = this.domElem.elSelect(opts);
	
	this.command = function() {
		// this.rte.browser.msie && this.rte.selection.saveIERange();
	}
	
	this.set = function(size) {
		// this.rte.browser.msie && this.rte.selection.restoreIERange();
		var nodes = this.rte.selection.selected({filter : 'textContainsNodes'});
		$.each(nodes, function() {
			$this = /^(THEAD|TFOOT|TBODY|COL|COLGROUP|TR)$/.test(this.nodeName) ? $(this).find('td,th') : $(this);
			$this.css('font-size', size).find("[style]").css('font-size', '');
		});
		this.rte.ui.update();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		var n = this.rte.selection.getNode();
		this.select.val((m = this.rte.dom.attr(n, 'style').match(/font-size:\s*([^;]+)/i)) ? m[1] : '');
	}
}

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
		// this.rte.browser.msie && this.rte.selection.saveIERange();
	}
	
	this.set = function(c) {
		if (!this.rte.selection.collapsed()) {
			// this.rte.browser.msie && this.rte.selection.restoreIERange();
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

/**
 * @class меню - формат
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.formatblock = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);

	var cmd = this.rte.browser.msie 
		? function(v) { self.val = v; self.constructor.prototype.command.call(self); }
		: function(v) { self.ieCommand(v); } 
	var self = this;
	var opts = {
		labelTpl : '%label',
		tpls     : {'' : '%label'},
		select   : function(v) { self.formatBlock(v); },
		src      : {
			'span'    : this.rte.i18n('Format'),
			'h1'      : this.rte.i18n('Heading 1'),
			'h2'      : this.rte.i18n('Heading 2'),
			'h3'      : this.rte.i18n('Heading 3'),
			'h4'      : this.rte.i18n('Heading 4'),
			'h5'      : this.rte.i18n('Heading 5'),
			'h6'      : this.rte.i18n('Heading 6'),
			'p'       : this.rte.i18n('Paragraph'),
			'address' : this.rte.i18n('Address'),
			'pre'     : this.rte.i18n('Preformatted')
		}
	}

	this.select = this.domElem.elSelect(opts);
	
	this.command = function() {

	}
	
	this.formatBlock = function(v) {

		function format(n, tag) {
			
			function replaceChilds(p) {
				$(p).find('h1,h2,h3,h4,h5,h6,p,address,pre').each(function() {
					$(this).replaceWith($(this).html());
				});
			}
			
			if (/^(LI|DT|DD)$/.test(n.nodeName)) {
				replaceChilds(n);
				self.rte.dom.wrapContents(n, tag);
			} else if (/^(UL|OL|DL)$/.test(n.nodeName)) {
				var html = '';
				$(n).children().each(function() {
					replaceChilds(this);
					html += $(this).html();
				});
				$(n).replaceWith($(self.rte.dom.create(tag)).html(html||''));
				
			} else {
				replaceChilds(n);
				$(n).replaceWith( $(self.rte.dom.create(tag)).html($(n).html()));
			}
		}

		tag = v == 'span' ? '' : v.toUpperCase();
		var nodes = this.rte.selection.selected({
			collapsed : true,
			blocks    : true,
			wrap      : 'inline',
			tag       : 'span'
		});

		for (var i=0; i<nodes.length; i++) {
			var n = nodes[i];
			if (tag) {
				if (/^(TABLE|THEAD|TBODY|TFOOT|TR)$/.test(n.nodeName)) {
					$(n).find('td,th').each(function() { format(this, tag); });
				} else {
					format(n, tag);
				}
			} else {
				if (/^(H[1-6]|P|ADDRESS|PRE)$/.test(n.nodeName)) {
					$(n).replaceWith($(this.rte.dom.create('div')).html($(n).html()||''));
				}
			}
		};
		this.rte.ui.update();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		var n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(H[1-6]|P|ADDRESS|PRE)$/);
		this.select.val(n ? n.nodeName.toLowerCase() : 'span');
	}
}

/**
 * @class кнопка - переключение в полноэкранный и обратно
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.fullscreen = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	this.active  = true;
	this.parents = [];
	this.height  = 0;
	var self     = this;
	
	this.command = function() {
		
		if (this.rte.editor.hasClass('el-fullscreen')) {
			for (var i=0; i < this.parents.length; i++) {
				$(this.parents[i]).css('position', 'relative');
			};
			this.parents = [];
			this.rte.editor.removeClass('el-fullscreen');
			this.rte.workzone.height(this.height);
			this.domElem.removeClass('active');
		} else {
			this.parents = [];
			var p = this.rte.editor.parents().each(function() {
				
				if (this.nodeName != 'BODY' && this.name != 'HTML' && $(this).css('position') == 'relative') {
					self.parents.push(this);
					$(this).css('position', 'static');
				}
			});
			this.height = this.rte.workzone.height();
			this.rte.editor.addClass('el-fullscreen');
			var h = parseInt(this.rte.editor.height() - this.rte.toolbar.height() - this.rte.statusbar.height() - this.rte.tabsbar.height() - 17);
			h>0 && this.rte.workzone.height(h);
			this.domElem.addClass('active');
		}
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
	}
}

/**
 * @class меню -Горизональная линия (открывает диалоговое окно)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.horizontalrule = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.src = {
		width   : $('<input type="text" />').attr({'name' : 'width', 'size' : 4}).css('text-align', 'right'),
		wunit   : $('<select />').attr('name', 'wunit')
					.append($('<option />').val('%').text('%'))
					.append($('<option />').val('px').text('px'))
					.val('%'),
		height  : $('<input type="text" />').attr({'name' : 'height', 'size' : 4}).css('text-align', 'right'),
		bg      : $('<div />'),
		border  : $('<div />'),
		'class' : $('<input type="text" />').css('width', '100%'),
		style   : $('<input type="text" />').css('width', '100%')
	}
	
	this.command = function() {
		this.src.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
		
		var n   = this.rte.selection.getEnd();
		this.hr = n.nodeName == 'HR' ? $(n) : $(rte.doc.createElement('hr')).css({width : '100%', height : '1px'});
		this.src.border.elBorderSelect({styleHeight : 73, value : this.hr});
		
		var _w  = this.hr.css('width') || this.hr.attr('width');
		this.src.width.val(parseInt(_w) || 100);
		this.src.wunit.val(_w.indexOf('px') != -1 ? 'px' : '%');
		
		this.src.height.val( this.rte.utils.toPixels(this.hr.css('height') || this.hr.attr('height')) || 1) ;
		this.src.bg.val(this.rte.utils.rgb2hex(this.hr.css('background-color')) || '');
		this.src['class'].val(this.rte.dom.attr(this.hr, 'class'));
		this.src.style.val(this.rte.dom.attr(this.hr, 'style'));
		
		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); self.set(); d.close(); },
			dialog : {
				title : this.rte.i18n('Horizontal rule')
			}
		}

		var d = new elDialogForm(opts);
		d.append([this.rte.i18n('Width'),          $('<span />').append(this.src.width).append(this.src.wunit) ], null, true)
			.append([this.rte.i18n('Height'),      $('<span />').append(this.src.height).append(' px')], null, true)
			.append([this.rte.i18n('Border'),      this.src.border], null, true)
			.append([this.rte.i18n('Background'),  this.src.bg], null, true)
			.append([this.rte.i18n('Css class'),   this.src['class']], null, true)
			.append([this.rte.i18n('Css style'),   this.src.style], null, true)
			.open();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		if (this.rte.selection.getEnd().nodeName == 'HR') {
			this.domElem.addClass('active');
		} else {
			this.domElem.removeClass('active');
		}
	}
	
	this.set = function() {
		!this.hr.parentNode && this.rte.selection.insertNode(this.hr.get(0));
		var attr = {
			noshade : true,
			style   : this.src.style.val()
		}
		var b = this.src.border.val();
		var css = {
			width  : (parseInt(this.src.width.val()) || 100)+this.src.wunit.val(),
			height : parseInt(this.src.height.val()) || 1,
			'background-color' : this.src.bg.val(),
			border : b.width && b.style ? b.width+' '+b.style+' '+b.color : ''
		}

		this.hr.removeAttr('class')
			.removeAttr('style')
			.removeAttr('width')
			.removeAttr('height')
			.removeAttr('align')
			.attr(attr)
			.css(css);
		
		if (this.src['class'].val()) {
			this.hr.attr('class', this.src['class'].val());	
		}
	}
	
}

/**
 * @class кнопка - Закладка (открывает диалоговое окно)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.image = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.img = null
	this.init = function() {
		
		this.labels = {
			main   : 'Properies',
			link   : 'Link',
			adv    : 'Advanced',
			events : 'Events',
			id        : 'ID',
			'class'   : 'Css class',
			style     : 'Css style',
			longdesc  : 'Detail description URL',
			href    : 'URL',
			target  : 'Open in',
			title   : 'Title'
		}
		
		this.src = {
			main : {
				src    : $('<input type="text" />').css('width', '100%'),
				title  : $('<input type="text" />').css('width', '100%'),
				alt    : $('<input type="text" />').css('width', '100%'),
				width  : $('<input type="text" />').attr('size', 5).css('text-align', 'right'),
				height : $('<input type="text" />').attr('size', 5).css('text-align', 'right'),
				margin : $('<div />'),
				align  : $('<select />').css('width', '100%')
							.append($('<option />').val('').text(this.rte.i18n('Not set', 'dialogs')))
							.append($('<option />').val('left'       ).text(this.rte.i18n('Left')))
							.append($('<option />').val('right'      ).text(this.rte.i18n('Right')))
							.append($('<option />').val('top'        ).text(this.rte.i18n('Top')))
							.append($('<option />').val('text-top'   ).text(this.rte.i18n('Text top')))
							.append($('<option />').val('middle'     ).text(this.rte.i18n('middle')))
							.append($('<option />').val('baseline'   ).text(this.rte.i18n('Baseline')))
							.append($('<option />').val('bottom'     ).text(this.rte.i18n('Bottom')))
							.append($('<option />').val('text-bottom').text(this.rte.i18n('Text bottom'))),
				border : $('<div />')
			},

			adv : {
				id       : $('<input type="text" />').css('width', '100%'),
				'class'  : $('<input type="text" />').css('width', '100%'),
				style    : $('<input type="text" />').css('width', '100%'),
				longdesc : $('<input type="text" />').css('width', '100%')
			},

			// link : {
			// 	href  : $('<input type="text" />').css('width', '100%'),
			// 	title : $('<input type="text" />').css('width', '100%')
			// },
			
			events : {}
		}
		
		$.each(
			['onblur', 'onfocus', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmouseout', 'onmouseleave', 'onkeydown', 'onkeypress', 'onkeyup'], 
			function() {
				self.src.events[this] = $('<input type="text" />').css('width', '100%');
		});
		
		$.each(self.src, function() {
			for (var n in this) {
				this[n].attr('name', n);
			}
		});
		
	}
	
	this.command = function() {
		!this.src && this.init();
		this.rte.browser.msie && this.rte.selection.saveIERange();
		this.src.main.border.elBorderSelect({ change : function() { self.updateImg(); }, name : 'border' });
		this.src.main.margin.elPaddingInput({ type : 'margin' });

		this.cleanValues();
		this.src.main.src.val('');
		
		var n = this.rte.selection.getEnd();
		this.preview = null;
		this.prevImg = null;
		this.link    = null;
		if (n.nodeName == 'IMG') {
			this.img     = $(n);
		} else {
			this.img = $(this.rte.doc.createElement('img'));
			
		}
		
		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); self.set(); d.close(); },
			dialog : {
				width     : 510,
				position  : 'top',
				title     : this.rte.i18n('Image')
			}
		}
		var d = new elDialogForm(opts);
		
		if (this.rte.options.fmAllow && this.rte.options.fmOpen) {
			var src = $('<span />').append(this.src.main.src.css('width', '93%'))
					.append(
						$('<span />').addClass('ui-state-default ui-corner-all').css('float', 'right')
							.append($('<span />').addClass('ui-icon ui-icon-folder-open'))
								.click( function() {
									self.rte.options.fmOpen( function(url) { self.src.main.src.val(url).change() } );
								})
								.hover(function() {$(this).addClass('ui-state-hover')}, function() { $(this).removeClass('ui-state-hover')})
						);
		} else {
			var src = this.src.main.src;
		}
		
		d.tab('main', this.rte.i18n('Properies'))
			.append([this.rte.i18n('Image URL'), src],                 'main', true)
			.append([this.rte.i18n('Title'),     this.src.main.title], 'main', true)
			.append([this.rte.i18n('Alt text'),  this.src.main.alt],   'main', true)
			.append([this.rte.i18n('Size'), $('<span />').append(this.src.main.width).append(' x ').append(this.src.main.height).append(' px')], 'main', true)
			.append([this.rte.i18n('Alignment'), this.src.main.align],  'main', true)
			.append([this.rte.i18n('Margins'),   this.src.main.margin], 'main', true)
			.append([this.rte.i18n('Border'),    this.src.main.border], 'main', true)

		for (var tab in this.src) {
			if (tab != 'main') {
				d.tab(tab, this.rte.i18n(this.labels[tab]));
				for (var name in this.src[tab]) {
					var l = this.rte.i18n(this.labels[name] ? this.labels[name] : name);
					if (tab == 'events') {
						this.src[tab][name].val(this.rte.utils.trimEventCallback(this.img.attr(name)));
					} else if (tab == 'link') {
						if (this.link) {
							this.src[tab][name].val(name == 'href' ? this.rte.utils.absoluteURL(this.link.attr(name)) : this.link.attr(name));
						}
					} else {
						this.src[tab][name].val(this.img.attr(name)||'');
					}
					d.append([l, this.src[tab][name]], tab, true);
				}
			}
		};
				
		d.open();
		
		var fs = $('<fieldset />').append($('<legend />').text(this.rte.i18n('Preview')))
		d.append(fs, 'main');
		var frame = document.createElement('iframe');
		$(frame).attr('src', '#').addClass('el-rte-preview').appendTo(fs);

		html = this.rte.options.doctype+'<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body style="padding:0;margin:0;font-size:9px"> </body></html>';
		frame.contentWindow.document.open();
		frame.contentWindow.document.write(html);
		frame.contentWindow.document.close();
		this.frame = frame.contentWindow.document
		this.preview = $(frame.contentWindow.document.body)
		 				 .text('Proin elit arcu, rutrum commodo, vehicula tempus, commodo a, risus. Curabitur nec arcu. Donec sollicitudin mi sit amet mauris. Nam elementum quam ullamcorper ante. Etiam aliquet massa et lorem. Mauris dapibus lacus auctor risus. Aenean tempor ullamcorper leo. Vivamus sed magna quis ligula eleifend adipiscing. Duis orci. Aliquam sodales tortor vitae ipsum. Aliquam nulla. Duis aliquam molestie erat. Ut et mauris vel pede varius sollicitudin');
		
		if (this.img.attr('src')) {
			
			this.prevImg = $(this.frame.createElement('img'))
				.attr('src',  this.rte.utils.absoluteURL(this.img.attr('src')))
				
			this.prevImg.attr('width', this.img.attr('width'))
				.attr('height', this.img.attr('height'))
				.attr('title', this.img.attr('title')||'')
				.attr('alt', this.img.attr('alt')||'')
				.attr('style', this.img.attr('style')||'')
			for (var n in this.src.adv) {
				var a = this.img.attr(n);
				if (a) {
					this.prevImg.attr(n, a)
				}
			}	
				
			this.preview.prepend(this.prevImg);
			this.updateValues();
		}
		
		$.each(this.src, function() {
			$.each(this, function() {
				if (this === self.src.main.src) {
					this.bind('change', function() { self.updatePreview(); });
				} else if (this == self.src.main.width || this == self.src.main.height) {
					this.bind('change', function(e) {self.updateDimesions(e);});
				} else {
					this.bind('change', function() { self.updateImg(); });
				}
			});
		});
		
		// this.src.link.href.change(function() {
		// 	var $this = $(this);
		// 	$this.val(self.rte.utils.absoluteURL($this.val()));
		// });
		
	}
	

	
	/**
	 * Устанавливает значения полей формы из аттрибутов prevImg
	 * Вызывается после загрузки prevImg
	 *
	 **/
	this.updateValues = function() {
		
		var i = this.prevImg.get(0);
		
		this.origW = this.prevImg.attr('width'); 
		this.origH = this.prevImg.attr('height');
		
		this.src.main.src.val(this.rte.dom.attr(i, 'src'));
		this.src.main.title.val(this.rte.dom.attr(i, 'title'));		
		this.src.main.alt.val(this.rte.dom.attr(i, 'alt'));
		this.src.main.width.val(this.origW);
		this.src.main.height.val(this.origH);
		this.src.adv['class'].val(this.rte.dom.attr(i, 'class'));
		this.src.main.margin.val(this.prevImg)
		var f = this.prevImg.css('float');
		this.src.main.align.val(f == 'left' || f == 'right' ? f : (this.prevImg.css('vertical-align')||''));
		this.src.main.border.val(this.prevImg)
		this.src.adv.style.val(this.rte.dom.attr(i, 'style'));
	}
	
	/**
	 * Очищает поля формы
	 *
	 **/
	this.cleanValues = function() {
		$.each(this.src, function() {
			$.each(this, function() {
				var $this = $(this);
				if ($this.attr('name') != 'src') {
					$this.val('');
				}
			});
		});
	}
	
	/**
	 * Устанавливает аттрибуты prevImg из полей формы
	 *
	 **/
	this.updateImg = function() {
		this.prevImg.attr({
				style  : $.trim(this.src.adv.style.val()),
				title  : $.trim(this.src.main.title.val()),
				alt    : $.trim(this.src.main.alt.val()),
				width  : parseInt(this.src.main.width.val()),
				height : parseInt(this.src.main.height.val())
			});

		var a = this.src.main.align.val();
		var f = a == 'left' || a == 'right' ? a : '';
		
		var b = this.src.main.border.val(); 
		var m = this.src.main.margin.val();
		this.prevImg.css('float', f);
		this.prevImg.css('vertical-align', f ? '' : a);
		this.prevImg.css('border', $.trim(b.width+' '+b.style+' '+b.color));
		if (m.css) {
			this.prevImg.css('margin', m.css);
		} else {
			this.prevImg.css('margin-top', m.top);
			this.prevImg.css('margin-right', m.right);
			this.prevImg.css('margin-bottom', m.bottom);
			this.prevImg.css('margin-left', m.left);						
		}

		$.each([this.src.events, this.src.adv], function() {
			$.each(this, function() {
				var $this = $(this);
				var n = $this.attr('name');
				if (n != 'style') {
					var v = $.trim($this.val());
					if (v) {
						self.prevImg.attr(n, v);
					} else {
						self.prevImg.removeAttr(n);
					}
				}
			});
		});
		
	}
	
	/**
	 * Обновляет форму выбора изображения
	 *
	 **/
	this.updatePreview = function() {
		
		var imgsrc = this.prevImg ? this.prevImg.attr('src') : '';
		var src    = $.trim(this.src.main.src.val());
		if (!src || src !=imgsrc) { // new image or empty src
			if (this.prevImg) {
				this.prevImg.remove();
				this.prevImg = null;
			}
			this.cleanValues();
			if (src) {  // new image
				
				this.prevImg = $(this.frame.createElement('img'))
					.attr('src',  this.rte.utils.absoluteURL(src))
					.bind('load', function() {
						self.updateValues();
					})
				this.preview.prepend(this.prevImg);
				self.updateValues();
			}
		} else { // update existsed image
			this.updateImg();
		}
	}
	
	this.updateDimesions = function(e) {
		
		var w = parseInt(this.src.main.width.val())  || 0;
		var h = parseInt(this.src.main.height.val()) || 0;
//		this.rte.log(this.origH+' '+this.origW)
		if (w > 0 && h > 0) {
			if (e.currentTarget == this.src.main.width.get(0)) {
				
				this.src.main.height.val(parseInt(w*this.origH/this.origW));
			} else {
				this.src.main.width.val(parseInt(h*this.origW/this.origH));
			}	
		} else {
			this.src.main.width.val(this.origW);
			this.src.main.height.val(this.origH);			
		}

		this.updateImg();

	}
	
	this.set = function() {
		
		if (!this.prevImg || !this.prevImg.attr('width')) {
			this.img  && this.img.remove();
			this.link && this.rte.doc.execCommand('unlink', false, null);
		} else {
			if (!this.img.parents().length) {
				this.rte.browser.msie && this.rte.selection.restoreIERange();
				this.img = $(this.rte.doc.createElement('img'));
			}
			this.img.attr({
					src    : this.rte.utils.absoluteURL($.trim(this.src.main.src.val())),
					style  : $.trim(this.rte.dom.attr(this.prevImg.get(0), 'style')),
					title  : $.trim(this.src.main.title.val()),
					alt    : $.trim(this.src.main.alt.val()),
					width  : parseInt(this.src.main.width.val()),
					height : parseInt(this.src.main.height.val())
				});
				
			for (var _n in this.src.adv) {
				if (_n != 'style') {
					var val = this.src.adv[_n].val();
					if (val) {
						this.img.attr(_n, val);
					} else {
						this.img.removeAttr(_n)
					}
					
				}
			}
			for (var _n in this.src.events) {
				var val = this.src.events[_n].val();
				if (val) {
					this.img.attr(_n, val);
				} else {
					this.img.removeAttr(_n)
				}
			}
				
			if (!this.img.parents().length) {
				this.rte.selection.insertNode(this.img.get(0))
			}
			// поскольку картинки в разных документах - делаем копию prevImg в редакторе
			// this.prevImg = $(this.rte.doc.createElement('img'));
			// this.prevImg.attr('src', this.src.main.src.val());
			// this.updateImg();
			// this.img.replaceWith(this.prevImg);

			// Link
			// var href   = this.rte.utils.absoluteURL(this.src.link.href.val());
			// var title  = $.trim(this.src.link.title.val());
			// if (!href) {
			// 	if (this.link) {
			// 		this.link.replaceWith(this.prevImg);
			// 	}
			// } else {
			// 	if (this.link) {
			// 		this.link.attr('href', href).removeAttr('target').removeAttr('title');
			// 		title  && this.alink.attr('title', title);
			// 	} else {
			// 		this.link = $(this.rte.doc.createElement('a')).attr('href', href);
			// 		title  && this.link.attr('title', title);
			// 		this.prevImg.wrap(this.link);
			// 	}
			// }
		}
		this.rte.ui.update();
	}

	this.update = function() {
		this.domElem.removeClass('disabled');
		var n = this.rte.selection.getEnd();
		if (n.nodeName == 'IMG') {
			this.domElem.addClass('active');
		} else {
			this.domElem.removeClass('active');
		}
	}
	
}

/**
 * @class Увеличение отступа
 * списки - если выделен один элемент - увеличивается вложенность списка, в остальных случаях - padding у родительского ul|ol
 * Если таблица выделена полностью - ей добавляется margin, если частично - увеличивается padding для ячеек
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.indent = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.command = function() {
		var nodes = this.rte.selection.selected({collapsed : true, blocks : true, wrap : 'inline', tag : 'p'});

		function indent(n) {
			var css = /(IMG|HR|TABLE|EMBED|OBJECT)/.test(n.nodeName) ? 'margin-left' : 'padding-left';
			var val = self.rte.dom.attr(n, 'style').indexOf(css) != -1 ? parseInt($(n).css(css))||0 : 0;
			$(n).css(css, val+40+'px');
		}
		
		for (var i=0; i < nodes.length; i++) {
			if (/^(TABLE|THEAD|TFOOT|TBODY|COL|COLGROUP|TR)$/.test(nodes[i].nodeName)) {
				$(nodes[i]).find('td,th').each(function() {
					indent(this);
				});
			} else if (/^LI$/.test(nodes[i].nodeName)) {
				var n = $(nodes[i]);
				$(this.rte.dom.create(nodes[i].parentNode.nodeName))
					.append($(this.rte.dom.create('li')).html(n.html()||'')).appendTo(n.html('&nbsp;'));
			} else {
				indent(nodes[i]);
			}
		};
		this.rte.ui.update();
	}
	
	
	this.update = function() {
		this.domElem.removeClass('disabled');
	}

}


/**
 * @class кнопки - выравнивание
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.justifyleft = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);

	this.command = function() {
		this.constructor.prototype.command.call(this);

		var v = this.name == 'justifyfull' ? 'justify' : this.name.replace('justify', '');
		// в опере заменяем align на style
		// if (this.rte.browser.opera || this.rte.browser.msie) {
			$(this.rte.doc.body).find('[align]').each(function() {
				$(this).removeAttr('align').css('text-align', v);
			});
		// }
		// в фф убираем пустые дивы
		if (this.rte.browser.mozilla) {
			$(this.rte.doc.body).find("div[style]").each(function() {
				var $this = $(this);
				if ($this.attr('style') == 'text-align: '+v+';' && !$this.children().length && $.trim($this.text()).length == 0) {
					$this.remove();
				}
			});
		}
	}
}

elRTE.prototype.ui.prototype.buttons.justifycenter = elRTE.prototype.ui.prototype.buttons.justifyleft;
elRTE.prototype.ui.prototype.buttons.justifyright  = elRTE.prototype.ui.prototype.buttons.justifyleft;
elRTE.prototype.ui.prototype.buttons.justifyfull   = elRTE.prototype.ui.prototype.buttons.justifyleft;

/**
 * @class кнопка - Сcылка (открывает диалоговое окно)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.link = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	
	function init() {
		self.labels = {
			// target    : 'Target',
			id        : 'ID',
			'class'   : 'Css class',
			style     : 'Css style',
			dir       : 'Script direction',
			lang      : 'Language',
			charset   : 'Charset',
			type      : 'Target MIME type',
			rel       : 'Relationship page to target (rel)',
			rev       : 'Relationship target to page (rev)',
			tabindex  : 'Tab index',
			accesskey : 'Access key'

		}
		self.src = {
			main : {
				href   : $('<input type="text" />'),
				title  : $('<input type="text" />'),
				anchor : $('<select />').attr('name', 'anchor')//,
				// target : $('<select />')
				// 	.append($('<option />').text(self.rte.i18n('In this window')).val(''))
				// 	.append($('<option />').text(self.rte.i18n('In new window (_blank)')).val('_blank'))
				// 	.append($('<option />').text(self.rte.i18n('In new parent window (_parent)')).val('_parent'))
				// 	.append($('<option />').text(self.rte.i18n('In top frame (_top)')).val('_top'))
			},

			popup : {
				use        : $('<input type="checkbox />"'),
				url        : $('<input type="text" />'    ).val('http://'),
				name       : $('<input type="text" />'    ),
				width      : $('<input type="text" />'    ).attr({size : 6, title : self.rte.i18n('Width')} ).css('text-align', 'right'),
				height     : $('<input type="text" />'    ).attr({size : 6, title : self.rte.i18n('Height')}).css('text-align', 'right'),
				left       : $('<input type="text" />'    ).attr({size : 6, title : self.rte.i18n('Left')}  ).css('text-align', 'right'),
				top        : $('<input type="text" />'    ).attr({size : 6, title : self.rte.i18n('Top')}   ).css('text-align', 'right'),
				location   : $('<input type="checkbox" />'),				
				menubar    : $('<input type="checkbox" />'),
				toolbar    : $('<input type="checkbox" />'),
				scrollbars : $('<input type="checkbox" />'),
				status     : $('<input type="checkbox" />'),
				resizable  : $('<input type="checkbox" />'),
				dependent  : $('<input type="checkbox" />'),
				retfalse   : $('<input type="checkbox" />').attr('checked', true)
			},

			adv : {
				id        : $('<input type="text" />'),
				'class'   : $('<input type="text" />'),
				style     : $('<input type="text" />'),
				dir       : $('<select />')
							.append($('<option />').text(self.rte.i18n('Not set')).val(''))
							.append($('<option />').text(self.rte.i18n('Left to right')).val('ltr'))
							.append($('<option />').text(self.rte.i18n('Right to left')).val('rtl')),
				lang      : $('<input type="text" />'),
				charset   : $('<input type="text" />'),
				type      : $('<input type="text" />'),
				rel       : $('<input type="text" />'),
				rev       : $('<input type="text" />'),
				tabindex  : $('<input type="text" />'),
				accesskey : $('<input type="text" />')
			},
			events : {}
		}

		$.each(
			['onblur', 'onfocus', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmouseout', 'onmouseleave', 'onkeydown', 'onkeypress', 'onkeyup'], 
			function() {
				self.src.events[this] = $('<input type="text" />');
		});

		$.each(self.src, function() {
			for (var n in this) {
				this[n].attr('name', n);
				var t = this[n].attr('type');
				if (!t || (t == 'text'  && !this[n].attr('size')) ) {
					this[n].css('width', '100%');
				}
			}
		});
		
	}
	
	this.command = function() {
		!this.src && init();
		this.rte.browser.msie && this.rte.selection.saveIERange();
	
		
		var n = this.rte.selection.getNode();
		var l;
		if ((l = this.rte.dom.selfOrParentLink(n))) {
			this.link = l;
		} else if ((l = this.rte.dom.childLinks(n))) {
			this.link = l[0];
		}
		this.link = this.link ? $(this.link) : $(this.rte.doc.createElement('a'));

		this.updatePopup();
		
		this.src.main.anchor.empty();
		$('a[href!=""][name]', this.rte.doc).each(function() {
			var n = $(this).attr('name');
			self.src.main.anchor.append($('<option />').val(n).text(n));
		});
		if (this.src.main.anchor.children().length) {
			this.src.main.anchor.prepend($('<option />').val('').text(this.rte.i18n('Select bookmark')) );
		}
		
		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); self.set(); d.close(); },
			tabs : { show : function(e, ui) { if (ui.index==3) { self.updateOnclick(); } } },
			dialog : {
				width : 'auto',
				width : 430,
				title : this.rte.i18n('Link')
			}
		}
		var d = new elDialogForm(opts);
		
		var l = $('<div />')
			.append( $('<label />').append(this.src.popup.location).append(this.rte.i18n('Location bar')))
			.append( $('<label />').append(this.src.popup.menubar).append(this.rte.i18n('Menu bar')))
			.append( $('<label />').append(this.src.popup.toolbar).append(this.rte.i18n('Toolbar')))				
			.append( $('<label />').append(this.src.popup.scrollbars).append(this.rte.i18n('Scrollbars')));
		var r = $('<div />')
			.append( $('<label />').append(this.src.popup.status).append(this.rte.i18n('Status bar')))
			.append( $('<label />').append(this.src.popup.resizable).append(this.rte.i18n('Resizable')))
			.append( $('<label />').append(this.src.popup.dependent).append(this.rte.i18n('Depedent')))				
			.append( $('<label />').append(this.src.popup.retfalse).append(this.rte.i18n('Add return false')));
		
		d.tab('main', this.rte.i18n('Properies'))
			.tab('popup',  this.rte.i18n('Popup'))
			.tab('adv',    this.rte.i18n('Advanced'))
			.tab('events', this.rte.i18n('Events'))
			.append($('<label />').append(this.src.popup.use).append(this.rte.i18n('Open link in popup window')), 'popup')
			.separator('popup')
			.append([this.rte.i18n('URL'),  this.src.popup.url],  'popup', true)
			.append([this.rte.i18n('Window name'), this.src.popup.name], 'popup', true)
			.append([this.rte.i18n('Window size'), $('<span />').append(this.src.popup.width).append(' x ').append(this.src.popup.height).append(' px')], 'popup', true)
			.append([this.rte.i18n('Window position'), $('<span />').append(this.src.popup.left).append(' x ').append(this.src.popup.top).append(' px')], 'popup', true)				
			.separator('popup')
			.append([l, r], 'popup', true);

		var link = this.link.get(0);
		var href = this.rte.dom.attr(link, 'href');
		this.src.main.href.val(href).change(function() {
			$(this).val(self.rte.utils.absoluteURL($(this).val()));
		});
		
		if (this.rte.options.fmAllow && this.rte.options.fmOpen) {
			var s = $('<span />').append(this.src.main.href.css('width', '89%'))
				.append(
					$('<span />').addClass('ui-state-default ui-corner-all').css('float', 'right')
						.append($('<span />').addClass('ui-icon ui-icon-folder-open'))
							.click( function() {
								self.rte.options.fmOpen( function(url) { self.src.main.href.val(url).change(); } );
							})
							.hover(function() {$(this).addClass('ui-state-hover')}, function() { $(this).removeClass('ui-state-hover')})
					// $('<span />').addClass('ui-icon ui-icon-folder-open').css('float', 'right').click( function() {
					// 	self.rte.options.fmOpen( function(url) { self.src.main.href.val(url).change() } )
					// })
				);
			d.append([this.rte.i18n('Link URL'), s], 'main', true);
		} else {
			d.append([this.rte.i18n('Link URL'), this.src.main.href], 'main', true);
		}
		this.src.main.href.change();
		
		d.append([this.rte.i18n('Title'), this.src.main.title.val(this.rte.dom.attr(link, 'title'))], 'main', true);
		if (this.src.main.anchor.children().length) {
			d.append([this.rte.i18n('Bookmark'), this.src.main.anchor.val(href)], 'main', true)
		}

		for (var n in this.src.adv) {
			this.src.adv[n].val(this.rte.dom.attr(link, n));
			d.append([this.rte.i18n(this.labels[n] ? this.labels[n] : n), this.src.adv[n]], 'adv', true);
		}
		for (var n in this.src.events) {
			var v = this.rte.utils.trimEventCallback(this.rte.dom.attr(link, n));
			this.src.events[n].val(v);
			d.append([this.rte.i18n(this.labels[n] ? this.labels[n] : n), this.src.events[n]], 'events', true);
		}
		
		this.src.popup.use.change(function() {
			var c = $(this).attr('checked');
			$.each(self.src.popup, function() {
				if ($(this).attr('name') != 'use') {
					if (c) {
						$(this).removeAttr('disabled');
					} else {
						$(this).attr('disabled', true);
					}
				}
			})
		});
		this.src.popup.use.change();
		d.open();
	}
	
	this.update = function() {
		var n = this.rte.selection.getNode();
		if (this.rte.dom.selfOrParentAnchor(n)) {
			this.domElem.addClass('disabled');	
		} else if (this.rte.dom.selfOrParentLink(n) || this.rte.dom.childLinks(n).length) {
			this.domElem.removeClass('disabled').addClass('active');
		} else {
			this.domElem.removeClass('active');
			if (!this.rte.selection.collapsed() || (n.nodeType == 1 && /^(IMG|EMBED|OBJECT)$/.test(n.nodeName))) {
				this.domElem.removeClass('disabled');
			} else {
				this.domElem.addClass('disabled');
			}
		}
	}
	
	this.updatePopup = function() {
		var onclick = this.rte.dom.attr(this.link.get(0), 'onclick');
		onclick = onclick ? $.trim(onclick.toString()) : ''
		if ( onclick.length>0 && (m = onclick.match(/window.open\("([^"]+)",\s*"([^"]*)",\s*"([^"]*)"\s*.*\);\s*(return\s+false)?/))) {
			this.src.popup.use.attr('checked', 'on')
			this.src.popup.url.val(m[1]);
			this.src.popup.name.val(m[2]);

			if ( /location=yes/.test(m[3]) ) {
				this.src.popup.location.attr('checked', true);
			}
			if ( /menubar=yes/.test(m[3]) ) {
				this.src.popup.menubar.attr('checked', true);
			}
			if ( /toolbar=yes/.test(m[3]) ) {
				this.src.popup.toolbar.attr('checked', true);
			}
			if ( /scrollbars=yes/.test(m[3]) ) {
				this.src.popup.scrollbars.attr('checked', true);
			}
			if ( /status=yes/.test(m[3]) ) {
				this.src.popup.status.attr('checked', true);
			}
			if ( /resizable=yes/.test(m[3]) ) {
				this.src.popup.resizable.attr('checked', true);
			}
			if ( /dependent=yes/.test(m[3]) ) {
				this.src.popup.dependent.attr('checked', true);
			}
			if ((_m = m[3].match(/width=([^,]+)/))) {
				this.src.popup.width.val(_m[1]);
			}
			if ((_m = m[3].match(/height=([^,]+)/))) {
				this.src.popup.height.val(_m[1]);
			}
			if ((_m = m[3].match(/left=([^,]+)/))) {
				this.src.popup.left.val(_m[1]);
			}
			if ((_m = m[3].match(/top=([^,]+)/))) {
				this.src.popup.top.val(_m[1]);
			}
			if (m[4]) {
				this.src.popup.retfalse.attr('checked', true);
			}
		} else {
			$.each(this.src.popup, function() {
				var $this = $(this);
				if ($this.attr('type') == 'text') {
					$this.val($this.attr('name') == 'url' ? 'http://' : '');
				} else {
					if ($this.attr('name') == 'retfalse') {
						this.attr('checked', true);
					} else {
						$this.removeAttr('checked');
					}
				}
			});
		}
		
	}
	
	this.updateOnclick = function () {
		var url = this.src.popup.url.val();
		if (this.src.popup.use.attr('checked') && url) {
			var params = '';
			if (this.src.popup.location.attr('checked')) {
				params += 'location=yes,';
			}
			if (this.src.popup.menubar.attr('checked')) {
				params += 'menubar=yes,';
			}
			if (this.src.popup.toolbar.attr('checked')) {
				params += 'toolbar=yes,';
			}
			if (this.src.popup.scrollbars.attr('checked')) {
				params += 'scrollbars=yes,';
			}
			if (this.src.popup.status.attr('checked')) {
				params += 'status=yes,';
			}
			if (this.src.popup.resizable.attr('checked')) {
				params += 'resizable=yes,';
			}
			if (this.src.popup.dependent.attr('checked')) {
				params += 'dependent=yes,';
			}
			if (this.src.popup.width.val()) {
				params += 'width='+this.src.popup.width.val()+',';
			}
			if (this.src.popup.height.val()) {
				params += 'height='+this.src.popup.height.val()+',';
			}
			if (this.src.popup.left.val()) {
				params += 'left='+this.src.popup.left.val()+',';
			}
			if (this.src.popup.top.val()) {
				params += 'top='+this.src.popup.top.val()+',';
			}
			if (params.length>0) {
				params = params.substring(0, params.length-1)
			}
			var retfalse = this.src.popup.retfalse.attr('checked') ? 'return false;' : '';
			var onclick = 'window.open("'+url+'", "'+$.trim(this.src.popup.name.val())+'", "'+params+'");'+retfalse;
			this.src.events.onclick.val(onclick);
			if (!this.src.main.href.val()) {
				this.src.main.href.val('#');
			}
		} else {
			var v = this.src.events.onclick.val();
			v = v.replace(/window\.open\([^\)]+\)\s*;?\s*return\s*false\s*;?/i, '');
			this.src.events.onclick.val(v);
		}
	}
	
	this.set = function() {
		this.updateOnclick();
		this.rte.browser.msie && this.rte.selection.restoreIERange();
		var href = this.rte.utils.absoluteURL(this.src.main.href.val());
		if (!href) {
			this.link.parents().length && this.rte.doc.execCommand('unlink', false, null);
		} else {
			if (!this.link.parents().length) {
				
				var fakeURL = '#--el-editor---'+Math.random();
				var r =this.rte.doc.execCommand('createLink', false, fakeURL);
				// self.rte.log(r)
				this.link = $('a[href="'+fakeURL+'"]', this.rte.doc);
				this.link.each(function() {
					var $this = $(this);
					// удаляем ссылки вокруг пустых элементов
					if (!$.trim($this.html()) && !$.trim($this.text())) {
						$this.replaceWith($this.text()); //  сохраняем пробелы :)
					}
				});
			}
			this.src.main.href.val(href);
			for (var tab in this.src) {
				if (tab != 'popup') {
					for (var n in this.src[tab]) {
						if (n != 'anchors') {
							var v = $.trim(this.src[tab][n].val());
							if (v) {
								this.link.attr(n, v);
							} else {
								this.link.removeAttr(n);
							}
						}
					}
				}
			};
		}
		this.rte.ui.update(true);
	}
	
}

/**
 * @class кнопка - DIV
 * Если выделение схлопнуто и находится внутри div'a - он удаляется
 * Новые div'ы создаются только из несхлопнутого выделения
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.nbsp = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		this.rte.selection.insertHtml('&nbsp;', true);
		this.rte.window.focus();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
	}
}

/**
 * @class Уменьшение отступа
 * уменьшает padding/margin/самомнение ;)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 * @todo decrease lists nesting level!
 **/
elRTE.prototype.ui.prototype.buttons.outdent = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;

	this.command = function() {
		var v = this.find();
		if (v.node) {
			$(v.node).css(v.type, (v.val>40 ? v.val-40 : 0)+'px');
			this.rte.ui.update();
		}
	}
	
	this.find = function(n) {
		function checkNode(n) {
			var ret = {type : '', val : 0};
			var s;
			if ((s = self.rte.dom.attr(n, 'style'))) {
				ret.type = s.indexOf('padding-left') != -1
					? 'padding-left'
					: (s.indexOf('margin-left') != -1 ? 'margin-left' : '');
				ret.val = ret.type ? parseInt($(n).css(ret.type))||0 : 0;
			}
			return ret;
		}
		
		var n = this.rte.selection.getNode();
		var ret = checkNode(n);
		if (ret.val) {
			ret.node = n;
		} else {
			$.each(this.rte.dom.parents(n, '*'), function() {
				ret = checkNode(this);
				if (ret.val) {
					ret.node = this;
					return ret;
				}
			})
		}
		return ret;
	}
	
	this.update = function() {
		var v = this.find();
		if (v.node) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}

	
}

/**
 * @class кнопка "вставить форматированый  текст" 
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.pasteformattext = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	this.iframe = $(document.createElement('iframe')).addClass('el-rte-paste-input')
	this.doc    = null;
	var self    = this;
	
	this.command = function() {
		this.rte.browser.msie && this.rte.selection.saveIERange();
		var opts = {
			submit : function(e, d) {
				e.stopPropagation();
				e.preventDefault();
				self.paste();
				d.close()
			},
			dialog : {
				width : 500,
				title : this.rte.i18n('Paste formatted text')
			}
		}
		var d = new elDialogForm(opts);
		d.append(this.iframe).open();
		this.doc = this.iframe.get(0).contentWindow.document;
		html = this.rte.options.doctype
			+'<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		if (this.rte.options.cssfiles.length) {
			$.each(this.rte.options.cssfiles, function() {
				html += '<link rel="stylesheet" type="text/css" href="'+this+'" />';
			});
		}
		html += '</head><body>  </body></html>';	
		
		this.doc.open();
		this.doc.write(html);
		this.doc.close();
		if (!this.rte.browser.msie) {
			try { this.doc.designMode = "on"; } 
			catch(e) { }
		} else {
			this.doc.body.contentEditable = true;
		}
		this.iframe.get(0).contentWindow.focus();

	}
	
	this.paste = function() {
		var html = $.trim($(this.doc.body).html());
		if (html) {
			this.rte.browser.msie && this.rte.selection.restoreIERange();
			this.rte.selection.insertHtml(this.rte.filter(html), true);
			this.rte.ui.update(true);
		}
	}

	this.update = function() {
		this.domElem.removeClass('disabled');
	}
}

/**
 * @class кнопка "вставить только текст" 
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.pastetext = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	this.input = $('<textarea />').addClass('el-rte-paste-input');
	var self   = this;
	
	this.command = function() {
		this.rte.browser.msie && this.rte.selection.saveIERange();
		var opts = {
			submit : function(e, d) {
				e.stopPropagation();
				e.preventDefault();
				self.paste();
				d.close();
			},
			dialog : {
				width : 500,
				title : this.rte.i18n('Paste only text')
			}
		}
		var d = new elDialogForm(opts);
		d.append(this.input).open();
	}
	
	this.paste = function() {
		var txt = $.trim(this.input.val());
		if (txt) {
			this.rte.browser.msie && this.rte.selection.restoreIERange();
			this.rte.selection.insertText(txt.replace(/\r?\n/g, '<br />'), true);
			this.rte.ui.update(true);
		}
		this.input.val('');
	}

	this.update = function() {
		this.domElem.removeClass('disabled');
	}
	
}

/**
 * @class кнопка "сохранить" 
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.save = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	this.active = true;
	
	this.command = function() {
		this.rte.save();
	}
	
	this.update = function() { }
}

/**
 * @class кнопка - DIV с css clear=both (отключает плавание элементов)
 * Если выделение схлопнуто и находится внутри div'a с аттрибутом или css clear - он удаляется
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.stopfloat = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);

	this.find = function() {
		if (this.rte.selection.collapsed()) {
			var n = this.rte.dom.selfOrParent(this.rte.selection.getEnd(), /^DIV$/);
			if (n && (this.rte.dom.attr(n, 'clear') || $(n).css('clear') != 'none')) {
				return n;
			}
		}
	}
	
	this.command = function() {
		var n;
		if ((n = this.find())) {
			var n = $(n);
			if (!n.children().length && !$.trim(n.text()).length) {
				n.remove();
			} else {
				n.removeAttr('clear').css('clear', '');
			}
		} else {
			this.rte.selection.insertNode($(this.rte.dom.create('div')).css('clear', 'both').get(0), true);
		}
		this.rte.ui.update(true);
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		if (this.find()) {
			this.domElem.addClass('active');
		} else {
			this.domElem.removeClass('active');
		}
	}
}

/**
 * @class кнопка - Таблица (открывает диалоговое окно)
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.table = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.src = null;
	this.labels = null;
	
	function init() {
		self.labels = {
			main      : 'Properies',
			adv       : 'Advanced',
			events    : 'Events',
			id        : 'ID',
			'class'   : 'Css class',
			style     : 'Css style',
			dir       : 'Script direction',
			summary   : 'Summary',
			lang      : 'Language',
			href      : 'URL'
		}
		
		self.src = {
			main : {
				caption : $('<input type="text" />'),
				rows    : $('<input type="text" />').attr('size', 5).val(2),
				cols    : $('<input type="text" />').attr('size', 5).val(2),
				width   : $('<input type="text" />').attr('size', 5),
				wunit   : $('<select />')
							.append($('<option />').val('%').text('%'))
							.append($('<option />').val('px').text('px')),				
				height  : $('<input type="text" />').attr('size', 5),	
				hunit   : $('<select />')
							.append($('<option />').val('%').text('%'))
							.append($('<option />').val('px').text('px')),	
				align   : $('<select />')
							.append($('<option />').val('').text(self.rte.i18n('Not set')))
							.append($('<option />').val('left').text(self.rte.i18n('Left')))
							.append($('<option />').val('center').text(self.rte.i18n('Center')))	
							.append($('<option />').val('right').text(self.rte.i18n('Right'))),	
				spacing : $('<input type="text" />').attr('size', 5),	
				padding : $('<input type="text" />').attr('size', 5),
				border  : $('<div />'),
				// frame   : $('<select />')
				// 			.append($('<option />').val('void').text(self.rte.i18n('No')))
				// 			.append($('<option />').val('border').text(self.rte.i18n('Yes'))),
				rules   : $('<select />')
							.append($('<option />').val('none').text(self.rte.i18n('No')))
							.append($('<option />').val('all').text(self.rte.i18n('Cells')))
							.append($('<option />').val('groups').text(self.rte.i18n('Groups')))
							.append($('<option />').val('rows').text(self.rte.i18n('Rows')))
							.append($('<option />').val('cols').text(self.rte.i18n('Columns'))),
				margin  : $('<div />'),
				bg      : $('<div />'),
				bgimg   : $('<input type="text" />').css('width', '90%')
			},
			
			adv : {
				id        : $('<input type="text" />'),
				summary   : $('<input type="text" />'),
				'class'   : $('<input type="text" />'),
				style     : $('<input type="text" />'),
				dir       : $('<select />')
								.append($('<option />').text(self.rte.i18n('Not set')).val(''))
								.append($('<option />').text(self.rte.i18n('Left to right')).val('ltr'))
								.append($('<option />').text(self.rte.i18n('Right to left')).val('rtl')),
				lang      : $('<input type="text" />')
			},
			
			events : {}
		}
		
		$.each(self.src, function() {
			for (var n in this) {
				this[n].attr('name', n);
				var t = this[n].get(0).nodeName; 
				if (t == 'INPUT' && n != 'bgimg') {
					this[n].css(this[n].attr('size') ? {'text-align' : 'right'} : {width : '100%'});
				} else if (t == 'SELECT' && n!='wunit' && n!='hunit') {
					this[n].css('width', '100%');
				}
			}
		});
		
		$.each(
			['onblur', 'onfocus', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmouseout', 'onmouseleave', 'onkeydown', 'onkeypress', 'onkeyup'], 
			function() {
				self.src.events[this] = $('<input type="text" />').attr('name', this).css('width', '100%');
		});
		
		self.src.main.align.change(function() {
			var v = $(this).val();
			if (v == 'center') {
				self.src.main.margin.val({left : 'auto', right : 'auto'});
			} else {
				var m = self.src.main.margin.val();
				if (m.left == 'auto' && m.right == 'auto') {
					self.src.main.margin.val({left : '', right : ''});
				}
			}
		});
		
		self.src.main.bgimg.change(function() {
			var t = $(this);
			t.val(self.rte.utils.absoluteURL(t.val()));
		})
		
	}
	
	this.command = function() {
		var n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^TABLE$/);
		
		if (this.name == 'table') {
			this.table = $(this.rte.doc.createElement('table'));	
		} else {
			this.table = n ? $(n) : $(this.rte.doc.createElement('table'));					
		}
		
		!this.src && init();
		this.src.main.border.elBorderSelect({styleHeight : 117});
		this.src.main.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
		this.src.main.margin.elPaddingInput({ type : 'margin', value : this.table});
		
		if (this.table.parents().length) {
			this.src.main.rows.val('').attr('disabled', true);
			this.src.main.cols.val('').attr('disabled', true);
		} else {
			this.src.main.rows.val(2).removeAttr('disabled');
			this.src.main.cols.val(2).removeAttr('disabled');
		}
		
		var w = this.table.css('width') || this.table.attr('width');
		this.src.main.width.val(parseInt(w)||'');
		this.src.main.wunit.val(w.indexOf('px') != -1 ? 'px' : '%');
		
		var h = this.table.css('height') || this.table.attr('height');	
		this.src.main.height.val(parseInt(h)||'');
		this.src.main.hunit.val(h && h.indexOf('px') != -1 ? 'px' : '%');

		var f = this.table.css('float');
		this.src.main.align.val('');
		if (f == 'left' || f == 'right') {
			this.src.main.align.val(f);
		} else {
			var ml = this.table.css('margin-left');
			var mr = this.table.css('margin-right');
			if (ml == 'auto' && mr == 'auto') {
				this.src.main.align.val('center');
			}
		}

		this.src.main.border.val(this.table);
		//this.src.main.frame.val(this.table.attr('frame'));
		this.src.main.rules.val(this.rte.dom.attr(this.table.get(0), 'rules'));

		this.src.main.bg.val(this.table.css('background-color'));
		var bgimg = this.table.css('background-image').replace(/url\(([^\)]+)\)/i, "$1");
		this.src.main.bgimg.val(bgimg!='none' ? bgimg : '');

		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); self.set(); d.close(); },
			dialog : {
				width : 471,
				title : this.rte.i18n('Table')
			}
		}
		var d = new elDialogForm(opts);
		
		for (var tab in this.src) {
			d.tab(tab, this.rte.i18n(this.labels[tab]));
			if (tab == 'main') {
				var t1 = $('<table />')
					.append($('<tr />').append('<td>'+this.rte.i18n('Rows')+'</td>').append($('<td />').append(this.src.main.rows)))
					.append($('<tr />').append('<td>'+this.rte.i18n('Columns')+'</td>').append($('<td />').append(this.src.main.cols)));
				var t2 = $('<table />')
					.append($('<tr />').append('<td>'+this.rte.i18n('Width')+'</td>').append($('<td />').append(this.src.main.width).append(this.src.main.wunit)))
					.append($('<tr />').append('<td>'+this.rte.i18n('Height')+'</td>').append($('<td />').append(this.src.main.height).append(this.src.main.hunit)));
				var t3 = $('<table />')
					.append($('<tr />').append('<td>'+this.rte.i18n('Spacing')+'</td>').append($('<td />').append(this.src.main.spacing.val(this.table.attr('cellspacing')||''))))
					.append($('<tr />').append('<td>'+this.rte.i18n('Padding')+'</td>').append($('<td />').append(this.src.main.padding.val(this.table.attr('cellpadding')||''))));
				
				d.append([this.rte.i18n('Caption'), this.src.main.caption.val(this.table.find('caption').eq(0).text() || '')], 'main', true)
					.separator('main')
					.append([t1, t2, t3], 'main', true)
					.separator('main')
					.append([this.rte.i18n('Border'),        this.src.main.border], 'main', true)
					//.append([this.rte.i18n('Frame'),       this.src.main.frame], 'main', true)
					.append([this.rte.i18n('Inner borders'), this.src.main.rules], 'main', true)
					.append([this.rte.i18n('Alignment'),     this.src.main.align], 'main', true)
					.append([this.rte.i18n('Margins'),       this.src.main.margin], 'main', true)
					.append([this.rte.i18n('Background'),    $('<span />').append($('<span />').css({'float' : 'left', 'margin-right' : '3px'}).append(this.src.main.bg)).append(this.src.main.bgimg)], 'main', true)
			} else {
				for (var name in this.src[tab]) {
					var v = this.rte.dom.attr(this.table, name);
					if (tab == 'events') {
						v = this.rte.utils.trimEventCallback(v);
					} 
					d.append([this.rte.i18n(this.labels[name] ? this.labels[name] : name), this.src[tab][name].val(v)], tab, true);
				}
			}
		}
		
		d.open();
	}
	
	this.set = function() {
		
		if (!this.table.parents().length) {
			var r = parseInt(this.src.main.rows.val()) || 0;
			var c = parseInt(this.src.main.cols.val()) || 0;
			if (r<=0 || c<=0) {
				return;
			}
			
			var b = $(this.rte.doc.createElement('tbody')).appendTo(this.table);
			var tr     = $('<tr />');
			for (var i=0; i < c; i++) {
				tr.append($('<td />').html('&nbsp;'))
			};
			for (var i=0; i<r; i++) {
				b.append(tr.clone(true))
			};
			this.rte.selection.insertNode(this.table.get(0), true);
		} else {
			this.table.removeAttr('width')
				.removeAttr('height')
				.removeAttr('border')
				.removeAttr('align')
				.removeAttr('bordercolor')
				.removeAttr('bgcolor')
				.removeAttr('cellspacing')
				.removeAttr('cellpadding')
				.removeAttr('frame')
				.removeAttr('rules');
		}
		
		var cap = $.trim(this.src.main.caption.val());
		if (cap) {
			if (!this.table.children('caption').length) {
				this.table.prepend($('<caption />'));
			}
			this.table.children('caption').text(cap)
		} else {
			this.table.children('caption').remove();
		}
		
		for (var tab in this.src) {
			if (tab != 'main') {
				for (var n in this.src[tab]) {
					var v = $.trim(this.src[tab][n].val());
					if (v) {
						this.table.attr(n, v);
					} else {
						this.table.removeAttr(n);
					}
				}
			}
		}
		
		var spacing = parseInt(this.src.main.spacing.val());
		if (spacing>=0) {
			this.table.attr('cellspacing', spacing);
		} 
		
		var padding = parseInt(this.src.main.padding.val());
		if (padding>=0) {
			this.table.attr('cellpadding', padding);
		} 
		
		var r = this.src.main.rules.val();
		if (r) {
			this.table.attr('rules', r);
		}
		
//		this.table.attr('frame', this.src.main.frame.val());

		var w = parseInt(this.src.main.width.val()) || '';
		var h = parseInt(this.src.main.height.val()) || '';
		var i = $.trim(this.src.main.bgimg.val());
		var b = this.src.main.border.val();
		var css = {
			'width'            : w ? w+this.src.main.wunit.val() : '',
			'height'           : h ? h+this.src.main.hunit.val() : '',
			'background-color' : this.src.main.bg.val(),
			'background-image' : i ? 'url('+i+')' : '',
			'border'           : $.trim(b.width+' '+b.style+' '+b.color),
			'float'            : ''
		};
		
		
		var m = this.src.main.margin.val();

		if (m.css) {
			css.margin = m.css;
		} else {
			css['margin-top']    = m.top;
			css['margin-right']  = m.right;
			css['margin-bottom'] = m.bottom;
			css['margin-left']   = m.left;
		}
		
		var f = this.src.main.align.val();
		if ((f=='left' || f=='right') && this.table.css('margin-left')!='auto'  && this.table.css('margin-right')!='auto') {
			css.float = f
		}
		
		this.table.css(css);
	
		if (!this.table.attr('style')) {
			this.table.removeAttr('style');
		}
		
		this.rte.ui.update();
	}
	
	this.update = function() {
		this.domElem.removeClass('disabled');
		if (this.name == 'tableprops' && !this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^TABLE$/)) {
			this.domElem.addClass('disabled').removeClass('active');
		}
	}
	
}

elRTE.prototype.ui.prototype.buttons.tableprops = elRTE.prototype.ui.prototype.buttons.table;

/**
 * @class меню - Удаление таблицы
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tablerm = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		var t = this.rte.dom.parent(this.rte.selection.getNode(), /^TABLE$/);
		t && $(t).remove();
		this.rte.ui.update(true);
	}
	
	this.update = function() {
		if (this.rte.dom.parent(this.rte.selection.getNode(), /^TABLE$/)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

elRTE.prototype.ui.prototype.buttons.tbcellprops = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.src = null;
	this.labels = null;
	
	function init() {
		self.labels = {
			main    : 'Properies',
			adv     : 'Advanced',
			events  : 'Events',
			id      : 'ID',
			'class' : 'Css class',
			style   : 'Css style',
			dir     : 'Script direction',
			lang    : 'Language'
		}
		
		self.src = {
			main : {
				type    : $('<select />').css('width', '100%')
							.append($('<option />').val('td').text(self.rte.i18n('Data')))
							.append($('<option />').val('th').text(self.rte.i18n('Header'))),
				width   : $('<input type="text" />').attr('size', 4),
				wunit   : $('<select />')
							.append($('<option />').val('%').text('%'))
							.append($('<option />').val('px').text('px')),				
				height  : $('<input type="text" />').attr('size', 4),	
				hunit   : $('<select />')
							.append($('<option />').val('%').text('%'))
							.append($('<option />').val('px').text('px')),	
				align   : $('<select />').css('width', '100%')
							.append($('<option />').val('').text(self.rte.i18n('Not set')))
							.append($('<option />').val('left').text(self.rte.i18n('Left')))
							.append($('<option />').val('center').text(self.rte.i18n('Center')))	
							.append($('<option />').val('right').text(self.rte.i18n('Right')))
							.append($('<option />').val('justify').text(self.rte.i18n('Justify'))),	
				border  : $('<div />'),
				padding  : $('<div />'),
				bg      : $('<div />'),
				bgimg   : $('<input type="text" />').css('width', '90%'),
				apply   : $('<select />').css('width', '100%')
							.append($('<option />').val('').text(self.rte.i18n('Current cell')))
							.append($('<option />').val('row').text(self.rte.i18n('All cells in row')))
							.append($('<option />').val('column').text(self.rte.i18n('All cells in column')))	
							.append($('<option />').val('table').text(self.rte.i18n('All cells in table')))
			},
			
			adv : {
				id        : $('<input type="text" />'),
				'class'   : $('<input type="text" />'),
				style     : $('<input type="text" />'),
				dir       : $('<select />').css('width', '100%')
								.append($('<option />').text(self.rte.i18n('Not set')).val(''))
								.append($('<option />').text(self.rte.i18n('Left to right')).val('ltr'))
								.append($('<option />').text(self.rte.i18n('Right to left')).val('rtl')),
				lang      : $('<input type="text" />')
			},
			
			events : {}
		}
		
		$.each(self.src, function() {
			for (var n in this) {
				this[n].attr('name', n);
				if (this[n].attr('type') == 'text' && !this[n].attr('size') && n!='bgimg') {
					this[n].css('width', '100%')
				}
			}
		});
		
		$.each(
			['onblur', 'onfocus', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmouseout', 'onmouseleave', 'onkeydown', 'onkeypress', 'onkeyup'], 
			function() {
				self.src.events[this] = $('<input type="text" />').attr('name', this).css('width', '100%');
		});
		
	}
	
	this.command = function() {
		!this.src && init();
		this.cell = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/);
		if (!this.cell) {
			return;
		}
		this.src.main.type.val(this.cell.nodeName.toLowerCase());
		this.cell = $(this.cell);
		this.src.main.border.elBorderSelect({styleHeight : 117, value : this.cell});
		this.src.main.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
		this.src.main.padding.elPaddingInput({ value : this.cell});
		
		var w = this.cell.css('width') || this.cell.attr('width');
		this.src.main.width.val(parseInt(w)||'');
		this.src.main.wunit.val(w.indexOf('px') != -1 ? 'px' : '%');
		
		var h = this.cell.css('height') || this.cell.attr('height');	
		this.src.main.height.val(parseInt(h)||'');
		this.src.main.hunit.val(h.indexOf('px') != -1 ? 'px' : '%');
		
		this.src.main.align.val(this.cell.attr('align') || this.cell.css('text-align'));
		this.src.main.bg.val(this.cell.css('background-color'));
		var bgimg = this.cell.css('background-image');
		this.src.main.bgimg.val(bgimg && bgimg!='none' ? bgimg.replace(/url\(([^\)]+)\)/i, "$1") : '');
		this.src.main.apply.val('');
		
		var opts = {
			submit : function(e, d) { e.stopPropagation(); e.preventDefault(); self.set(); d.close(); },
			dialog : {
//				width : 471,
				width : 'auto',
				title : this.rte.i18n('Table cell properties')
			}
		}
		var d = new elDialogForm(opts);
		for (var tab in this.src) {
			d.tab(tab, this.rte.i18n(this.labels[tab]));
			
			if (tab == 'main') {
				d.append([this.rte.i18n('Width'),              $('<span />').append(this.src.main.width).append(this.src.main.wunit)],  'main', true)
					.append([this.rte.i18n('Height'),          $('<span />').append(this.src.main.height).append(this.src.main.hunit)], 'main', true)
					.append([this.rte.i18n('Table cell type'), this.src.main.type],    'main', true)
					.append([this.rte.i18n('Border'),          this.src.main.border],  'main', true)
					.append([this.rte.i18n('Alignment'),       this.src.main.align],   'main', true)
					.append([this.rte.i18n('Paddings'),        this.src.main.padding], 'main', true)
					.append([this.rte.i18n('Background'),      $('<span />').append($('<span />').css({'float' : 'left', 'margin-right' : '3px'}).append(this.src.main.bg)).append(this.src.main.bgimg)],  'main', true)
					.append([this.rte.i18n('Apply to'),        this.src.main.apply],   'main', true);
			} else {
				for (var name in this.src[tab]) {
					var v = this.cell.attr(name) || '';
					if (tab == 'events') {
						v = this.rte.utils.trimEventCallback(v);
					} 
					d.append([this.rte.i18n(this.labels[name] ? this.labels[name] : name), this.src[tab][name].val(v)], tab, true);
				}
			}
		}
		d.open()
	}
	
	this.set = function() {
		
		var target = this.cell;
		var apply  = this.src.main.apply.val();
		switch (this.src.main.apply.val()) {
			case 'row':
				target = this.cell.parent('tr').children('td,th');
				break;
				
			case 'column':
				target = $(this.rte.dom.tableColumn(this.cell.get(0)));
				break;
				
			case 'table':
				target = this.cell.parents('table').find('td,th');
				break;
		}

		for (var tab in this.src) {
			if (tab != 'main') {
				for (var n in this.src[tab]) {
					var v = $.trim(this.src[tab][n].val());
					if (v) {
						target.attr(n, v);
					} else {
						target.removeAttr(n);
					}
				}
			}
		}
		
		target.removeAttr('width')
			.removeAttr('height')
			.removeAttr('border')
			.removeAttr('align')
			.removeAttr('bordercolor')
			.removeAttr('bgcolor');
			
		var t = this.src.main.type.val();
		var w = parseInt(this.src.main.width.val()) || '';
		var h = parseInt(this.src.main.height.val()) || '';
		var i = $.trim(this.src.main.bgimg.val());
		var b = this.src.main.border.val();
		var css = {
			'width'            : w ? w+this.src.main.wunit.val() : '',
			'height'           : h ? h+this.src.main.hunit.val() : '',
			'background-color' : this.src.main.bg.val(),
			'background-image' : i ? 'url('+i+')' : '',
			'border'           : $.trim(b.width+' '+b.style+' '+b.color),
			'text-align'       : this.src.main.align.val() || ''
		};
		var p = this.src.main.padding.val();
		if (p.css) {
			css.padding = p.css;
		} else {
			css['padding-top']    = p.top;
			css['padding-right']  = p.right;
			css['padding-bottom'] = p.bottom;
			css['padding-left']   = p.left;
		}
		
		target = target.get();

		$.each(target, function() {
			var type = this.nodeName.toLowerCase();
			var $this = $(this);
			if (type != t) {
				
				var attr = {}
				for (var i in self.src.adv) {
					var v = $this.attr(i)
					if (v) {
						attr[i] = v.toString();
					}
				}
				for (var i in self.src.events) {
					var v = $this.attr(i)
					if (v) {
						attr[i] = v.toString();
					}
				}
				var colspan = $this.attr('colspan')||1;
				var rowspan = $this.attr('rowspan')||1;
				if (colspan>1) {
					attr.colspan = colspan;
				}
				if (rowspan>1) {
					attr.rowspan = rowspan;
				}
				
				$this.replaceWith($('<'+t+' />').html($this.html()).attr(attr).css(css) );
				
			} else {
				$this.css(css);
			}
		});

		this.rte.ui.update();
	}
	
	this.update = function() {
		if (this.rte.dom.parent(this.rte.selection.getNode(), /^TABLE$/)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
	
}

/**
 * @class меню - Склеивание колонок
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tbcellsmerge = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	
	function selectedCells() {
		var c1 = self.rte.dom.selfOrParent(self.rte.selection.getStart(), /^(TD|TH)$/);
		var c2 = self.rte.dom.selfOrParent(self.rte.selection.getEnd(), /^(TD|TH)$/);		
		if (c1 && c2 && c1!=c2 && $(c1).parents('table').get(0) == $(c2).parents('table').get(0)) {
			return [c1, c2];
		}
		return null;
	}
	
	this.command = function() {
		var cells = selectedCells();

		if (cells) {
			
			var _s  = this.rte.dom.indexOf($(cells[0]).parent('tr').get(0));
			var _e  = this.rte.dom.indexOf($(cells[1]).parent('tr').get(0));
			var ro  = Math.min(_s, _e); // row offset
			var rl  = Math.max(_s, _e) - ro + 1; // row length
			var _c1 = this.rte.dom.tableColumn(cells[0], true, true); 
			var _c2 = this.rte.dom.tableColumn(cells[1], true);
			var _i1 = $.inArray(cells[0], _c1.column); 
			var _i2 = $.inArray(cells[1], _c2.column);
			
			var colBegin = _c1.info.offset[_i1] < _c2.info.offset[_i2]  ? _c1 : _c2;
			var colEnd   = _c1.info.offset[_i1] >= _c2.info.offset[_i2] ? _c1 : _c2;
			var length   = 0;
			var target   = null;
			var html     = '';

			var rows = $($(cells[0]).parents('table').eq(0).find('tr').get().slice(ro, ro+rl))
				.each( function(i) {
					var _l = html.length;
					var accept = false;
					$(this).children('td,th').each(function() {
						var $this   = $(this);
						var inBegin = $.inArray(this, colBegin.column);
						var inEnd   = $.inArray(this, colEnd.column);
						
						if (inBegin!=-1 || inEnd!=-1) {
							accept = inBegin!=-1 && inEnd==-1;
							var len = parseInt($this.attr('colspan')||1)
							if (i == 0) {
								length += len;
							}
							
							if (inBegin!=-1 && i>0) {
								var delta = colBegin.info.delta[inBegin];
								if (delta>0) {
									if ($this.css('text-align') == 'left') {
										var cell = $this.clone(true);
										$this.html('&nbsp;');
									} else {
										var cell = $this.clone().html('&nbsp;');
									}
									cell.removeAttr('colspan').removeAttr('id').insertBefore(this);
									if (delta>1) {
										cell.attr('colspan', delta);
									}
								}
							}
							
							if (inEnd!=-1) {
								var delta = colEnd.info.delta[inEnd];
								if (len-delta>1) {
									var cp = len-delta-1;
									if ($this.css('text-align') == 'right') {
										var cell = $this.clone(true);
										$this.html('&nbsp;');
									} else {
										var cell = $this.clone().html('&nbsp;');
									}
									cell.removeAttr('colspan').removeAttr('id').insertAfter(this);
									if (cp>1) {
										cell.attr('colspan', cp);
									}
								}
							}
							if (!target) {
								target = $this;
							} else {
								html += $this.html();
								$this.remove();
							}
						} else if (accept) {
							if (i == 0) {
								length += parseInt($this.attr('colspan')||1);
							}
							html += $this.html();
							$this.remove();
							

						}
					})
					html += _l!=html.length ? '<br />' : '';
				});

			target.removeAttr('colspan').removeAttr('rowspan').html(target.html()+html)
			if (length>1) {
				target.attr('colspan', length);
			}
			if (rl>1) {
				target.attr('rowspan', rl);
			}
			// sometimes when merge cells with different rowspans we get "lost" cells in rows 
			// this add cells if needed
			this.rte.dom.fixTable($(cells[0]).parents('table').get(0));
		}
	}
	
	this.update = function() {
		if (selectedCells()) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

/**
 * @class кнопка - Разделение склееной ранее ячейки таблицы
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tbcellsplit = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	
	this.command = function() {
		var n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/);
		if (n) {
			var colspan = parseInt(this.rte.dom.attr(n, 'colspan'));
			var rowspan = parseInt(this.rte.dom.attr(n, 'rowspan'));
			if (colspan>1 || rowspan>1) {
				var cnum = colspan-1;
				var rnum = rowspan-1;
				var tb   = this.rte.dom.parent(n, /^TABLE$/);
				var tbm  = this.rte.dom.tableMatrix(tb);
				this.rte.log(tbm)
				
				// ячейки в текущем ряду
				if (cnum) {
					for (var i=0; i<cnum; i++) {
						$(this.rte.dom.create(n.nodeName)).text(' ').insertAfter(n);
					}
				}
				if (rnum) {
					var ndx  = this.rte.dom.indexesOfCell(n, tbm)
					var rndx = ndx[0];
					var cndx = ndx[1];
					// ячейки в следущих рядах
					for (var r=rndx+1; r < rndx+rnum+1; r++) {
						var cell;
						
						if (!tbm[r][cndx].nodeName) {
							if (tbm[r][cndx-1].nodeName) {
								cell = tbm[r][cndx-1];
							} else {
								for (var i=cndx-1; i>=0; i--) {
									if (tbm[r][i].nodeName) {
										cell =tbm[r][i];
										break;
									}
								}
							}
							if (cell) {
								for (var i=0; i<= cnum; i++) {
									$(this.rte.dom.create(cell.nodeName)).text(' ').insertAfter(cell);
								}
							}
						}
					};
				}
				$(n).removeAttr('colspan').removeAttr('rowspan');
				this.rte.dom.fixTable(tb);
			}
		}
		this.rte.ui.update(true);
	}
	
	this.update = function() {
		var n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/);
		if (n && (parseInt(this.rte.dom.attr(n, 'colspan'))>1 || parseInt(this.rte.dom.attr(n, 'rowspan'))>1)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

/**
 * @class меню - Новая колонка в таблице
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tbcolbefore = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	
	this.command = function() {
		var cells = this.rte.dom.tableColumn(this.rte.selection.getNode(), false, true);
		if (cells.length) {
			$.each(cells, function() {
				var $this = $(this);
				var cp = parseInt($this.attr('colspan')||1)
				if (cp >1) {
					$this.attr('colspan', cp+1);
				} else {
					var c = $this.clone().html('&nbsp;').removeAttr('colspan').removeAttr('width').removeAttr('id');
					if (self.name == 'tbcolbefore') {
						c.insertBefore(this);
					} else {
						c.insertAfter(this);
					}
				}
			});
			this.rte.ui.update();
		}
	}
	
	this.update = function() {
		if (this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

elRTE.prototype.ui.prototype.buttons.tbcolafter = elRTE.prototype.ui.prototype.buttons.tbcolbefore;

/**
 * @class меню - Удаление колонки их таблицы
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tbcolrm = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	
	this.command = function() {
		var n     = this.rte.selection.getNode();
		var c     = this.rte.dom.selfOrParent(n, /^(TD|TH)$/);
		var prev  = $(c).prev('td,th').get(0);
		var next  = $(c).next('td,th').get(0);			
		var tb    = this.rte.dom.parent(n, /^TABLE$/);
		var cells = this.rte.dom.tableColumn(n, false, true);

		if (cells.length) {
			$.each(cells, function() {
				var $this = $(this);
				var cp    = parseInt($this.attr('colspan')||1);
				if ( cp>1 ) {
					$this.attr('colspan', cp-1);
				} else {
					$this.remove();
				}
			});
			this.rte.dom.fixTable(tb);
			if (prev || next) {
				this.rte.selection.selectContents(prev ? prev : next).collapse(true);
			}
			this.rte.ui.update(true);
		}
	}
	
	this.update = function() {
		if (this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

/**
 * @class меню - Удаление ряда
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.tbrowrm = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);
	var self = this;
	this.command = function() {
		
		var n  = this.rte.selection.getNode();
		var c  = this.rte.dom.selfOrParent(n, /^(TD|TH)$/);
		var r  = this.rte.dom.selfOrParent(c, /^TR$/);
		var tb = this.rte.dom.selfOrParent(c, /^TABLE$/);
		var mx = this.rte.dom.tableMatrix(tb);
		
		if (c && r && mx.length) {
			if (mx.length==1) {
				$(tb).remove();
				return this.rte.ui.update();
			}
			var mdf = [];
			var ro  = $(r).prevAll('tr').length;
			
			function _find(x, y) {
				while (y>0) {
					y--;
					if (mx[y] && mx[y][x] && mx[y][x].nodeName) {
						return mx[y][x];
					}
				}
			}
			
			// move cell with rowspan>1 to next row
			function _move(cell, x) {
				y = ro+1;
				var sibling= null;
				if (mx[y]) {
					for (var _x=0; _x<x; _x++) {
						if (mx[y][_x] && mx[y][_x].nodeName) {
							sibling = mx[y][_x];
						}
					};
					
					cell = cell.remove();
					if (sibling) {
						cell.insertAfter(sibling)
					} else {
						cell.prependTo($(r).next('tr').eq(0))
					}
				}
			}
			
			function _cursorPos(column) {
				for (var i = 0; i<column.length; i++) {
					if (column[i] == c) {
						return i<column.length-1 ? column[i+1] : column[i-1];
					}
				}
			}
			
			for (var i=0; i<mx[ro].length; i++) {
				var cell = null;
				var move = false;
				if (mx[ro][i] && mx[ro][i].nodeName) {
					cell = mx[ro][i];
					move = true;
				} else if (mx[ro][i] == '-' && (cell = _find(i, ro))) {
					move = false;
				}
				if (cell) {
					cell = $(cell);
					var rowspan = parseInt(cell.attr('rowspan')||1);
					if (rowspan>1) {
						cell.attr('rowspan', rowspan-1);
						move && _move(cell, i, ro);
					} 
				}
			};
			
			var _c = _cursorPos(this.rte.dom.tableColumn(c));
			if (_c) {
				this.rte.selection.selectContents(_c).collapse(true);
			}

			$(r).remove();
		}
		this.rte.ui.update();
	}
	
	this.update = function() {
		if (this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^TR$/)) {
			this.domElem.removeClass('disabled');
		} else {
			this.domElem.addClass('disabled');
		}
	}
}

/**
 * @class кнопка - удаление ссылки
 *
 * @param  elRTE  rte   объект-редактор
 * @param  String name  название кнопки 
 **/
elRTE.prototype.ui.prototype.buttons.unlink = function(rte, name) {
	this.constructor.prototype.constructor.call(this, rte, name);

	this.command = function() {
		var n = this.rte.selection.getNode();
		var l, link;
		if ((l = this.rte.dom.selfOrParentLink(n))) {
			link = l;
		} else if ((l = this.rte.dom.childLinks(n))) {
			link = l[0];
		}
		if (link) {
			this.rte.selection.select(link);
			this.rte.doc.execCommand('unlink', false, null);
			this.rte.ui.update(true);
		}
		
	}
	
	this.update = function() {
		var n = this.rte.selection.getNode();
		if (this.rte.dom.selfOrParentLink(n) || this.rte.dom.childLinks(n).length) {
			this.domElem.removeClass('disabled').addClass('active');
		} else {
			this.domElem.removeClass('active').addClass('disabled');
		}
	}
}







