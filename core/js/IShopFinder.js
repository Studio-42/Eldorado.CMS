$().ready(function() {
	
	$('.ishop-finder').each(function() {
		var finder = $(this),
			url    = finder.find('form').bind('submit', function() {
					finder.find('.ishop-finder-element:hidden').find('select,:text').attr('disabled', 'disabled');
				}).attr('action').replace(/\/$/, '_params/'),
			type = finder.find('select[name="type"]'),
			mnf  = finder.find('select[name="mnf"]'),
			tm   = finder.find('select[name="tm"]');

		function update(name, val) {

			$.ajax({
				url      : url,
				type     : 'get',
				dataType : 'json',
				data     : { name : name, value : val },
				success  : function(data) {
					var l, adv = finder.hasClass('ishop-search-advanced');
					
					if (data.error) {
						return window.console && window.console.log && window.console.log(data.error);
					}
					
					if (data.mnf && mnf.length) {
						l = data.mnf.length;
						mnf.empty();
						while (l--) {
							mnf.prepend('<option value="'+data.mnf[l].id+'">'+data.mnf[l].name+'</option>')
						}
						mnf.val('0');
						if (!mnf.children().length) {
							mnf.parents('.ishop-finder-element').hide();
						}
					}
					
					if (data.tm && tm.length) {
						l = data.tm.length;
						tm.empty();
						while (l--) {
							tm.prepend('<option value="'+data.tm[l].id+'">'+data.tm[l].name+'</option>')
						}
						tm.val('0');
						if (!tm.children().length) {
							tm.parents('.ishop-finder-element').hide();
						}
					}
					
					data.mnfID && mnf.length && mnf.val(data.mnfID);
					
					if (data.types) {
						finder.find('[el-itype]').each(function() {
							var t = $(this).attr('el-itype');
							if (adv || $(this).attr('rel') == 'normal') {
								if ($.inArray(t, data.types) == -1) {
									$(this).parents('.ishop-finder-element').hide();
								} else {
									$(this).parents('.ishop-finder-element').show();
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