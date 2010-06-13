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

			var $this = $(this),
				viewport     = $this.children(':first'),
				slider       = viewport.children(':first'),
				items        = slider.children(),
				l            = items.length,
				h            = items.eq(0).outerHeight(),
				w            = items.eq(0).outerWidth(),
				vert         = opts.position == 'vertical' || (items.eq(0).css('display') == 'block' && items.eq(0).css('float') == 'none'),
				viewportSize = opts.size*(vert ? h : w),
				itemSize     = vert ? h : w,
				sliderSize   = l*itemSize,
				step         = itemSize*(opts.stepOneItem ? 1 : opts.size),
				prev         = $('<div class="prev"/>'),
				next         = $('<div class="next"/>'),
				pos          = vert ? 'top' : 'left'
				;
				

				
			$this.add(viewport).width(vert ? w : viewportSize).height(vert ? viewportSize : h);
			slider[vert ? 'height' : 'width'](sliderSize).css(pos, 0)
			prev.add(next).hide().appendTo(this).css(vert ? 'width' : 'height', '100%');

			if (l>opts.size) {
				
				if (opts.showArrows) {
					prev.add(next).show();
				} else {
					$this.hover( function() { prev.add(next).show() }, function() { prev.add(next).hide() });
				}

				next.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					
					if (!slider.is(':animated')) {
						var offset  = parseInt(slider.css(pos)),
							dif     = sliderSize+offset-viewportSize,
							_offset = dif > 0 ? (dif>=step ? offset-step : offset-dif) : 0,
							delta   = parseInt(opts.delta),
							css     = {},
							f       = delta!=0 ? function() { css[pos] = _offset+'px'; slider.animate(css, opts.deltaTime, 'linear') } : function() { };

						css[pos] = (_offset == 0 ? _offset-delta : _offset+delta) +'px';
						slider.animate(css, opts.time, 'linear', f);
					}
				});
				
				prev.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					
					if (!slider.is(':animated')) {
						var offset  = parseInt(slider.css(pos)),
							_offset = offset < 0 ? (-offset>=step ? offset+step : 0) : -(sliderSize-viewportSize),
							delta   = parseInt(opts.delta),
							css     = {},
							f       = delta!=0 ? function() { css[pos] = _offset+'px'; slider.animate(css, opts.deltaTime, 'linear') } : function() { };						;
						
						css[pos] = (_offset == viewportSize-sliderSize ? _offset+delta : _offset-delta) +'px';
						slider.animate(css, opts.time, 'linear', f);
					}
				});
			}
		});
	}
	
	
	$.fn.elslider.defaults = {
		// ориентация слайдера (auto|vertical|horizontal)
		position  : 'auto',
		// сколько элементов показывать
		size      : 3,    
		//  время смены слайдов
		time      : 400, 
		//  доп сдвиг слайда в пикселях
		delta     : 30,
		//  время возврата слайда после доп сдвига
		deltaTime : 450,
		//  prev/next arrows always visible
		showArrows : false,
		// move by one item per step
		stepOneItem : true
	}
	
	function log(m) {
		window.console && window.console.log && window.console.log(m)
	}

})(jQuery);