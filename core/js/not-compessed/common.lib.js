function popUp(url, w, h)
{
	window.open(url, null, 'top=50,left=50,scrollbars=yes,resizable=yes,width='+(w+40)+',height='+(h+60));
	return false;
}



$().ready( function() {
	
	var searchLocked = false;

	function _search(event) {
		if ( !searchLocked && $(this).val().length>2) {
			$('#search-form-top').addClass('searchProgress');
			var url  = elBaseURL + '_xml_/__search__/' ; 
			var data = {_form_ : 'search', sstr: $(this).val()};

			searchLocked = true;

			$.ajax({
				url      : elBaseURL+'__search__/html/',
				dataType : 'html',
				data     : data,
				error    : function(h, t, e) { searchLocked = false; $('#search-form-top').removeClass('searchProgress'); },
				success  : function(data) { 
					var c = $('#main #el-content').fadeOut();
					var r = $('#main #search-result');
					$('#search-form-top').removeClass('searchProgress');

					if (!r.length) {
						var r = $('<div />')
							.attr('id', 'search-result')
							.addClass('clearfix rounded-5')
							.insertBefore(c)
							.append($('<div />').addClass('close').click(function(e) {
								r.fadeOut().children().eq(1).remove();
								c.fadeIn();
								$('#search-str').val('');
							}));
					} else {
						r.children().eq(1).remove();
					}
					r.append(data).fadeIn();
					searchLocked = false;
				}
			});
		}
	}
	
	
	$('.crosslinks-group .el-collapsed').click( function(e) {
		e.preventDefault();
		$(this).toggleClass('el-expanded');
		$(this).parent().siblings('ul').slideToggle('slow');
	});
	
	$('#search-icon').click( function(e) {
		var f = $('#search-form-top');
		e.stopPropagation();
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
	
	
	$(':text[name="elLogin"]').parents('form').submit(function(e) {
		e.stopPropagation();
		e.preventDefault();

		var data = {
			login : $(this).find(':text[name="elLogin"]').val(),
			pwd   : $(this).find(':password[name="elPass"]').val(),
			ajax  : 1
		},
		spinner = $(this).prevAll('.spinner'), 
		error   = $(this).prevAll('.warn'),
		url     = $(this).find(':text[name="url"]').length ? $(this).find(':text[name="url"]').val() : window.location.href,
		self    = $(this);
		
		if (!spinner.length) {
			spinner = $('<div class="spinner">{m('Please wait')}...</div>').insertBefore(this);
		}
		spinner.show();
		error.hide();
		
		$(this).hide()
		
		$.ajax({
			url      : elURL+"__auth__/",
			type     : 'post',
			dataType : 'json',
			data     : data,
			success  : function(data) {
				if (data.error) {
					if (!error.length) {
						error = $('<div class="warn"/>').insertBefore(self);
					}
					spinner.hide();
					self.show();
					error.show().text(data.error);
				} else {
					window.location.href = url;
				}
			}
		})
		
	});
	
	
});
