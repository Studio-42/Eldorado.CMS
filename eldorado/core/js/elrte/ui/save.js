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

