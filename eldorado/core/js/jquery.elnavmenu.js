/*
 * jQuery navigation menu
 * Simple drop-down vertical or horizontal list-based menu.
 * Part of eldorado.cms - http://eldorado-cms.ru
 *
 * Copyright (c) 2009 Dmitry Levashov, dio@eldorado-cms.ru
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Usage:
 * $('#my-menu').elnavmenu()
 * $('#my-menu').elnavmenu( opts )
 * $('#my-menu') must be unordered nested list (ul)
 *
 * Options:
 * vertical - create vertical menu. Not nessesary. In most cases plugin detect menu orientation.
 * zIndex - z-index for menu top level
 * hoverClass - css class for rollover
 * hasChildClass - css class for elements with submenu
 * speed - expanding/collapsing speed (slow, fast, normal or time in milisec)
 * effect - how expand/collapse submenus. Variants - '' : show/hide, 'slide':slideDown/slideUp, 'fade':fadeIn/fadeOut, default is 'slide'
 * paddingX - negative padding on x-axis, move submenu in parent   
 * paddingX - negative padding on y-axis, move submenu in parent  
 * topPaddingX, topPaddingY - same as padding, but only for first level submenus in horizontal position
 *
 *
 */
(function($) {
	
	$.fn.elnavmenu = function(opts) {

		var opts = $.extend( $.fn.elnavmenu.defaults, opts );
		var expandEffect = 'show';
		var collapseEffect = 'hide';
		if (opts.effect == 'slide') {
			expandEffect = 'slideDown';
			collapseEffect = 'slideUp';
		} else if (opts.effect == 'fade') {
			expandEffect = 'fadeIn';
			collapseEffect = 'fadeOut';
		}

		var f = $(this).children('li').eq(0);
		var isVert = opts.vertical || (f.css('float')=='none' && f.css('display')!='inline' && f.css('display')!='inline-block')

		return $(this).each( function() {
			$(this).css('zIndex', opts.zIndex)
				.children('li').css('zIndex', opts.zIndex)
				.each( function() { prepare( $(this), true ); }) ;
		});
		
		function prepare( li, top ) {

			li.hover(
				function() { $(this).addClass(opts.hoverClass);  },
				function() { $(this).removeClass(opts.hoverClass);  }
				);
			var sub = li.children('ul');
			if ( sub.length ) {

				li.addClass( opts.hasChildsClass ).hover(
					function() { if (opts.onExpand)   { opts.onExpand(li) }  sub[expandEffect]( opts.speed ); },
					function() { if (opts.onCollapse) { opts.onCollapse(li) } sub[collapseEffect]( opts.speed ); }
					);
				sub.css('zIndex', parseInt(li.parents('ul').eq(0).css('zIndex'))+1 );

				if (top && !isVert) {
					sub.css('top', (li.innerHeight()-parseInt(opts.topPaddingY))+'px')
						.css('left', opts.topPaddingX+'px');
					var p = parseInt(li.css('paddingLeft'))
					//if (p) { sub.css('marginLeft', '-'+p+'px'); }
				} else {
					sub.css('left', (li.outerWidth() - parseInt(opts.paddingX))+'px')
						.css('top', parseInt(opts.paddingY)+'px')
				}
				
				if ( sub.children('li').length ) {
					sub.children('li').each( function() { prepare($(this), false)} );
				}
				sub.hide();
			}
		};
	};
	
	$.fn.elnavmenu.defaults = { 
		vertical       : false, 
		zIndex         : 100,
		hoverClass     : 'hover', 
		hasChildsClass : 'has-childs',
		speed          : 'slow',
		effect         : 'slide',
		onExpand       : '',
		onCollapse     : '',
		paddingX       : 5,
		paddingY       : 3,
		topPaddingX    : 3,
		topPaddingY    : 3		
		};

})(jQuery);