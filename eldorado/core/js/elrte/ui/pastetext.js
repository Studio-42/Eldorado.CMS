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
			this.rte.selection.insertText(txt.replace(/\r?\n/g, '<br />'), true);
			this.rte.ui.update(true);
		}
		this.input.val('');
	}

	this.update = function() {
		this.domElem.removeClass('disabled');
	}
	
}