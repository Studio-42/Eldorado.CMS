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

