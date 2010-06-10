(function($) {
	
	$.fn.elscarousel = function(o) {
		
		return this.each(function() {

			var self     = this, margin, width=0, viewWidth;
			$this        = $(this);
			this.content = $this.find('.content');
			this.view    = $this.find('.view');
			this.items   = $this.find('.item');
			this._prev   = $this.find('.prev').hide();
			this._next   = $this.find('.next').hide();
			this.margin  = !$.browser.msie ? 0 : 0;
			this.steps   = 0;
			this.step    = 0;
			this.visible = 0;
			this.delta   = 12;
			
			this.view.hover(
				function() { self._prev.add(self._next).fadeIn('fast'); },
				function() { self._prev.add(self._next).fadeOut('fast'); }
				)
			
			var opts = {
				horiz : {
					marginPrev : 'margin-left',
					marginNext : 'margin-right',
					viewSize   : 'innerWidth',
					itemSize   : 'outerWidth',
					itemSetSize   : 'width',
					position   : 'left'
				},
				vert : {
					marginPrev : 'margin-top',
					marginNext : 'margin-bottom',
					viewSize   : 'innerHeight',
					itemSize   : 'outerHeight',
					itemSetSize   : 'height',
					position   : 'top'
				}
			}
			
			// log(this)
			
			this.opts = this.items.eq(0).css('display').match(/^inline/i) || this.items.eq(0).css('float').match(/^(left|right)/i) 
				? opts.horiz
				: opts.vert;
			
			// log(this.opts)

			this.margin += this.items.eq(0)[this.opts.itemSize](true) - this.items.eq(0)[this.opts.itemSize]()
			
			log(this.margin)
			
			var viewSize = this.view[this.opts.viewSize]()
			var cntSize = 0;
			// log(viewSize)
			this.items.each(function() {
				var size = $(this)[self.opts.itemSize](true);
				
				if (cntSize+size <= viewSize) {
					self.visible++;
					cntSize += size;
				} else if (cntSize+size-margin <= viewSize) {
					self.visible++;
					self.view[self.opts.viewSize]((cntSize += size));
				} else {
					!self.steps && self.view[self.opts.itemSetSize](cntSize);
					cntSize += size;
					self.steps++;
				}
			});
			
			this._next.click(function() {
				var pos = 0, item, tmp, css1, css2;
				if (self.step<self.steps) {
					item = self.items.eq(self.visible+self.step);
					pos = parseInt(self.content.css(self.opts.position))-item[self.opts.itemSize](true);
					self.step++;
				} else {
					self.step = 0;
				}

				tmp = pos < 0 ? pos - self.delta : self.delta;
				css1 = self.opts.position == 'left' 
					? { left : tmp+'px'}
					: { top  : tmp+'px'};
				css2 = self.opts.position == 'left' 
					? { left : pos+'px'}
					: { top  : pos+'px'};
			
				self.content.animate(css1, 700, function() { $(this).animate(css2, 500); });
			});
			
			this._prev.click(function() {
				var pos = 0, item, delta = self.delta;
				if (self.step>0) {
					item = self.items.eq(--self.step);
					log(item)
					pos  = parseInt(self.content.css(self.opts.position))+item[self.opts.itemSize](true);
				} else {
					self.step = self.steps;
					for (i=self.visible; i<self.items.length; i++) {
						pos -= self.items.eq(i)[self.opts.itemSize](true);
					}
					delta = -delta;
				}
				log(pos)
				css1 = self.opts.position == 'left' 
					? { left : (pos+delta)+'px'}
					: { top  : (pos+delta)+'px'};
				css2 = self.opts.position == 'left' 
					? { left : pos+'px'}
					: { top  : pos+'px'};
					
				self.content.animate(css1, 700, function() {
					$(this).animate(css2, 500);
				});
			})
			
		})
	}
	
	function log(m) {
		window.console && window.console.log && window.console.log(m)
	}
	
})(jQuery);
