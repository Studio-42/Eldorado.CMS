$().ready(function() {
	
	$('.ishop-finder').each(function() {
		var finder = $(this),
			url    = finder.find('form').bind('submit', function() {
					finder.find('.ishop-finder-element:hidden').find('select,:text').attr('disabled', 'disabled');
				}).attr('action').replace(/\/$/, '_params/'),
			type = finder.find('select[name="type"]'),
			mnf  = finder.find('select[name="mnf"]'),
			tm   = finder.find('select[name="tm"]')
			;
		window.console.log(mnf)
		function update(name, val) {
			window.console.log(url,name, val)
			$.ajax({
				url : url,
				type : 'get',
				dataType : 'json',
				data : { name : name, value : val },
				success : function(data) {
					window.console.log(data)
					if (data.error) {
						return window.console && window.console.log && window.console.log(data.error);
					}
					
					if (data.tm && tm.length) {
						var l = data.tm.length;
						tm.empty();
						while (l--) {
							tm.prepend('<option value="'+data.tm[l].id+'">'+data.tm[l].name+'</option>')
						}
						tm.val('0');
					}
					
					data.mnfID && mnf.length && mnf.val(data.mnfID);
					
					if (data.props) {
						var adv = finder.hasClass('ishop-search-advanced');
						finder.find('select[name^="props-"]').each(function() {
							window.console.log(this)
							var id = parseInt($(this).attr('name').replace(/^props\-/, ''));
							window.console.log(parseInt(id))
							
							if (adv || $(this).attr('rel') == 'advanced') {
								if ($.inArray(id, data.props)) {
									$(this).parents('.ishop-finder-element').show();
								} else {
									$(this).parents('.ishop-finder-element').hide();
								}
							}
						});
					}
				}
			})
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