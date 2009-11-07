var locked = false;

$().ready( function() {
	$('#search-ico').click( function(event) {
		var f = $('#search-form-top');
		event.stopPropagation();
		if (f.is(':hidden')) {
			f.show('slow');
			f.find('#search-str').val('').focus();
			f.click( function(e) { e.stopPropagation()} );
			$('body').click( function() { f.hide('slow'); $(this).unbind('click') } );
		} else {
			f.hide('slow');
		}
		return false;
	} );
	$('#search-str').keyup( _search );
} );

function _search(event) {
	if ( !locked && $(this).val().length>2) {
		$('#search-form-top').addClass('searchProgress');
		var url  = elBaseURL + '_xml_/__search__/' ; 
		var data = {_form_ : 'search', sstr: $(this).val()};

		locked = true;
		
		$.ajax({
			url      : elBaseURL+'__search__/html/',
			dataType : 'html',
			data     : data,
			error    : function(h, t, e) { locked = false; },
			success  : function(data) { 
				var c = $('#main #el-content').fadeOut();
				var r = $('#main #search-result');
				if (!r.length) {
					var r = $('<div />')
						.attr('id', 'search-result')
						.addClass('clearfix rounded-5')
						.insertBefore(c)
						.append($('<div />').addClass('close').click(function(e) {
							r.fadeOut().children().eq(1).remove();
							c.fadeIn();
						}));
				} else {
					r.children().eq(1).remove();
				}
				r.append(data).fadeIn();
				locked = false;
			}
		});
	}
}
