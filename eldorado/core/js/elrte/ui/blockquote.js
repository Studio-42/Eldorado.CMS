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