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