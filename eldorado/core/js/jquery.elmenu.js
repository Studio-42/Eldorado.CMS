(function($) {
	
	$.fn.elmenu = function(o) {
		
		var opts = $.extend($.fn.elmenu.defaults, o);
		
		return this.each( function() {

			$this = $(this); 
			$width = $this.children('li:first').outerWidth(); 
			
			if ( opts.drag ) {
				if ($.fn.draggable) $this.draggable( {  opacity:0.7, scroll:false, appendTo: 'body'} )
			}
			
			$this.find('li:not(.drag-handle)').hover(
				function() {$(this).addClass(opts.hoverClass)},
				function() {$(this).removeClass(opts.hoverClass)}				
				);
				
			$this.children('li:has(>ul)').each( function() {
				$width+= $(this).outerWidth()
				$(this).children('ul').css('top', $(this).outerHeight()+'px').css('opacity', opts.opacity).hide();
				$(this).children('ul').children('li:not([oclick])').click( function() {
					window.location.href=$(this).children('a').attr('href')
				});
				
			}).click(  function() { 
				
				var submenu = $(this).children('ul:first')
				
				if ( submenu.is(':hidden') ) {
					submenu.slideDown('slow')
					$(this).parent('ul').children('li:has(>ul)').hover( show, hide)
				} else {
					submenu.slideUp('slow');
					$(this).parent('ul').children('li:has(>ul)')
						.unbind('mouseenter', show).unbind('mouseleave', hide)
				}
			});
			
			$this.mouseleave( function() {
				$(this).children('li:has(>ul)')
					.unbind('mouseenter', show).unbind('mouseleave', hide)
					.children('ul:visible').hide('slow')
			} );
			
			$this.children('li').each( function() {
				//$width+= $(this).outerWidth()
			});
			
			if ( jQuery.support.noCloneEvent ) {
				$this.css('width', $this.outerWidth())
				//$this.css('width', ($width<4000 ? $width : $width-4238)+'px');
			} else {
				$this.css('padding-top', 0).css('padding-bottom', 0)
			}
		});
	};
	
	$.fn.elmenu.defaults = {
		hoverClass : 'a-menu-hover',
		opacity:0.9,
		drag : true
	};
	
	function show(e) { 
		$(e.target).children('ul').slideDown('slow') 
		$(e.target).siblings('li:has(>ul:visible)').children('ul').slideUp('slow')
	}
	function hide(e) { 
		$(e.target).children('ul').slideUp('slow');
		//$(e.target).parent('ul').children('li:has(>ul:visible)').children('ul').hide('slow')
	}
	
	function debug($obj) {
	    if (window.console && window.console.log)
	      window.console.log($obj);
	  };
})(jQuery);