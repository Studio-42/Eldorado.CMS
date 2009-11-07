
(function($) {
	
	$.fn.elmenu = function(o) {
		var options = $.extend(true, {}, $.fn.elmenu.defaults, o);
		var show = 'show';
		var hide = 'hide';
		if ($.browser.opera) {
			show = 'fadeIn';
			hide = 'fadeOut';
		} else if (options.effect == 'slide') {
			show = 'slideDown';
			hide = 'slideUp';
		} else if (options.effect == 'fade') {
			show = 'fadeIn';
			hide = 'fadeOut';
		};
		
		return this.each(function() {
			var self = this;

			$(this)
				.css('z-index', options.zIndex||10)
				.children('li:first').addClass('first')
				.end()
				.children('li:last').addClass('last')
				.end()
				.find('ul').each(function() {
					$(this).children('li:first').addClass('first').end().children('li:last').addClass('last');
				})
				.end()
				.find('li').each(function() {
					var li = $(this);
					var ul = li.children('ul');
					
					li.hover(
						function() { li.addClass(options.hoverClass) },
						function() { li.removeClass(options.hoverClass) }
					).children('a').click(function() {
						if (li.parent('ul').get(0) != self) {
							li.trigger('mouseout');
						}
					});
					var top, left;
					if (ul.length) {
						if (options.orientation == 'horizontal' && li.parent('ul').get(0) == self) {
							left = 0;
							top  = parseInt(li.height())-options.deltaY;
						} else {
							left = parseInt(li.width())-options.deltaX;
							top  = options.deltaY;
						}
						ul.css({left : left+'px',	top : top+'px'}).hide();
						li.addClass(options.hasChildsClass);
						
						if (options.open == 'click' && li.parent('ul').get(0) == self) {
							li.click(function() {
								if (ul.css('display') != 'none') {
									ul[hide](options.speed);
								} else {
									ul[show](options.speed);
								}
							})
							.hover(
								function() {  },
								function() { ul[hide](options.speed); }
							);
						} else {
							li.hover(
								function() { ul[show](options.speed); },
								function() { ul[hide](options.speed); }
							);
						}
					} 
				});
				
				if (options.draggable) {

					var w = 0;
					$(this).children().each(function() {
						w+= $(this).outerWidth();
					});

					$(this).css('width', w+'px').draggable().bind('dragstop', function(e, ui) {
						$.cookie('el-menu-left', parseInt($(self).offset().left), options.cookie);
						$.cookie('el-menu-top',  parseInt($(self).offset().top),  options.cookie);
					});
					
					var top  = $.cookie('el-menu-top');
					var left = $.cookie('el-menu-left');
					var win  = parseInt($(document.body).width());
					if (top !== null && top>0 && top< parseInt($(document.body).height())) {
						$(this).css('top', parseInt(top)+'px');
					}
					if (left !== null && left <= win-w) {
						$(this).css('left', parseInt(left)+'px');
					}
				}
		});
	};
	
	
	function log(m) {
		window.console && window.console.log && window.console.log(m);

	};
	
	$.fn.elmenu.defaults = { 
		orientation    : 'vertical', 
		zIndex         : 100,
		hoverClass     : 'hover', 
		hasChildsClass : 'has-childs',
		openOn         : 'hover',
		speed          : 'slow',
		effect         : '',
		deltaX         : 5,
		deltaY         : 7,
		draggable      : false,
		cookie         : { 'expires' : 1, 'path' : '/'}
		}
})(jQuery);
