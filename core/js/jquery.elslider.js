(function($) {
	
	/**
	 * @jQuery plugin - простой слайдер
	 * Для горизонтального слайдера слайды должны иметь одинаковую высоту,
	 * для вертикального - ширину
	 *
	 * @author Dmitry (dio) Levashov, dev@std42.ru
	 * @licence BSD Licence
	 **/
	
	$.fn.elslider = function(o) {
		
		var opts = $.extend({}, $.fn.elslider.defaults, o);

		return this.each(function() {
			var t          = $(this),
				slider     = t.children(),
				view       = t.children().children().eq(0),
				items      = view.children(),
				vert       = opts.position == 'vertical' || (items.eq(0).css('display') == 'block' && items.eq(0).css('float') == 'none'),
				w          = parseInt(items.eq(0).outerWidth()),
				h          = parseInt(items.eq(0).outerHeight()),
				prev       = $('<a href="#" class="elslider-prev"/>').hide().appendTo(t),
				next       = $('<a href="#" class="elslider-next"/>').hide().appendTo(t),
				itemSize   = vert ? h : w,
				sliderSize = opts.size*itemSize,
				viewSize   = items.length*itemSize,
				cssPos     = vert ? 'top' : 'left';

			if (vert) {
				t.height(sliderSize).width(w);
				// slider.height(sliderSize).width(w);
				view.height(viewSize).width(w);
				prev.add(next).css('left', parseInt((w-prev.outerWidth())/2));
			} else {
				// t.width(sliderSize).height(h);
				slider.width(sliderSize).height(h);
				t.width(sliderSize)
				view.width(viewSize).height(h);
				prev.add(next).css('top', parseInt((h-prev.outerHeight())/2));
				log(t.css('padding-right'))
			}
			t.hover(
				function() { prev.add(next).show(); },
				function() { prev.add(next).hide(); }
			);
			
			next.click(function(e) {
				e.stopPropagation();
				e.preventDefault();
				
				if (!view.is(':animated')) {
					var offset = parseInt(view.css(cssPos))||0,
						css    = {},
						time   = 0;

					if (viewSize+offset-sliderSize > 0) {
						offset     -= itemSize;
						time        = opts.time;
						css[cssPos] = offset-opts.delta+'px';
					} else {
						offset      = 0;
						time        = parseInt(items.length*opts.time/2);
						// alert(time)
						css[cssPos] = opts.delta+'px';
					}

					view.animate(css, opts.time, opts.delta ? function() { css[cssPos] = offset+'px'; view.animate(css, opts.deltaTime); } : function() {});
				}
			});
			
			prev.click(function(e) {
				e.stopPropagation();
				e.preventDefault();
				
				if (!view.is(':animated')) {
					var offset = parseInt(view.css(cssPos)),
						css    = {},
						time   = 0;
						
					if (offset<0) {
						offset     += itemSize;
						time = opts.time;
						css[cssPos] = offset+opts.delta+'px';
					} else {
						offset = -(viewSize-sliderSize);
						time = parseInt(items.length*opts.time/2);
						// alert(time)
						css[cssPos] = offset-opts.delta+'px';
					}
					view.animate(css, opts.time, opts.delta ? function() { css[cssPos] = offset+'px'; view.animate(css, opts.deltaTime); } : function() {});
					
				}
			});

		});
	}
	
	$.fn.elslider.defaults = {
		// ориентация слайдера (auto|vertical|horizontal)
		position  : 'auto',
		// сколько элементов показывать
		size      : 3,    
		//  время смены слайдов
		time      : 700, 
		//  доп сдвиг слайда в пикселях
		delta     : 12,
		//  время возврата слайда после доп сдвига
		deltaTime : 200
	}
	
	
	function log(m) {
		window.console && window.console.log && window.console.log(m)
	}
	
})(jQuery);