$().ready(function() {
	
	$('.ishop-finder').each(function() {
		var finder = $(this),
			url    = finder.find('form').bind('submit', function() {
					finder.find('.ishop-finder-element:hidden').find('select,:text').attr('disabled', 'disabled');
				}).attr('action').replace(/\/$/, '_params/'),
			type = finder.find('select[name="type"]'),
			mnf  = finder.find('select[name="mnf"]'),
			tm   = finder.find('select[name="tm"]');

		function log(m) {
			window.console && window.console.log && window.console.log(m);
		}

		function update(name, val) {
			$.ajax({
				url      : url,
				type     : 'get',
				dataType : 'json',
				data     : { name : name, value : val },
				success  : function(data) {
					var l, adv = finder.hasClass('ishop-finder-advanced');
					
					if (data.error) {
						return log(data.error);
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
						finder.find('[el_itype]').each(function() {
							var t = $(this).attr('el_itype');
							if (adv || $(this).attr('rel') == 'normal') {
								if ($.inArray(parseInt(t), data.types) == -1) {
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
			
		$(this).find('.ishop-finder-view-switch').click(function(e) {
			e.preventDefault();
			finder.toggleClass('ishop-finder-normal').toggleClass('ishop-finder-advanced');
			$(this).children().toggle();
		}).end().find('.elem-type,.elem-mnf,.elem-tm').find('select').change(function() {
			update($(this).attr('name'), $(this).val());
		});
	});
});