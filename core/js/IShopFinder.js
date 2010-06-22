$().ready(function() {
	
	$('.ishop-finder').each(function() {
		var finder = $(this),
			url = finder.find('form').bind('submit', function() {
				finder.find('.ishop-finder-element:hidden').find('select,:text').attr('disabled', 'disabled');
			}).attr('action').replace(/\/$/, '_params/'),
			type = finder.find('select[name="type"]'),
			mnf = finder.find('select[name="mnf"]'),
			tm = finder.find('select[name="tm"]')
			;
		window.console.log(mnf)
		function update(name, val) {
			window.console.log(url,name, val)
		}
			
		$(this).find('.ishop-finder-search-switch').click(function(e) {
			e.preventDefault();
			finder.toggleClass('ishop-search-normal').toggleClass('ishop-search-advanced');
			$(this).children().toggle();
		}).end().find('.ishop-type-type,.ishop-type-mnf,.ishop-type-tm').find('select').change(function() {
			update($(this).attr('name'), $(this).val());
		})
		

		
	})
})