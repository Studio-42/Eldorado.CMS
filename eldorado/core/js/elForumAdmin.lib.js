$().ready(
	function() { //alert('ready'); 
		$('select#gids').change( function() {
			//alert( $(this).parents('table').find('tr:has(:checkbox)').length)
			alert('change')
			var gid=$(this).val();
			$(this).parents('table').find('tr:has(:checkbox)').each( function() {
				if ( $(':checkbox#uid\\['+gid+'\\]\\[0\\]', this).length ) {
					$(this).show();
				} else {
					$(this).hide()
				}
			});
		});
		$('select#gids').trigger('change')
	}
	);