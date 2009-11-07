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
