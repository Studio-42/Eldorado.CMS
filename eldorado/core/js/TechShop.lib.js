
$().ready(function() {
	var log = function(m) {
		window.console && window.console.log && window.console.log(m)
	}
	// fake price list creation
	var dialog = function(data) {
		var tb = $('<table><tr><th>'+data.head.name+'</th><th>'+data.head.price+'</th><th></th></tr></table>'),
			f = $('<form method="GET" action="'+data.url+'"><input type="hidden" name="save" value="1" /></form>'),
			tpl = '<tr><td class="name"><input type="text" name="name[]" value="{name}" /></td><td class="price"><input type="text" name="price[]" value="{price}" /></td><td class="rm"><div class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-trash"/></div></td></tr>',
			add = $('<div class="new-row"><div class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-circle-plus"/></div>'+data.head.item+'</div>')
				.click(function() {
					tb.append(tpl.replace(/\{(name|price)\}/g, ''));
				}),
			d = $('<div class="mod-tshop-dialog"/>').append(f.append(tb).append(add));
		
		$('.mod-tshop-dialog .rm div').live('click', function(e) {
			$(this).parents('tr').remove();
		});
		
		
		if (data.price.length) {
			for (var i=0; i < data.price.length; i++) {
				tb.append(tpl.replace('{name}', data.price[i].name).replace('{price}', data.price[i].price));
			};
		} else {
			add.click();
		}
		d.dialog({
			title   : data.title,
			width   : 500,
			modal   : true,
			buttons : {
				Cancel : function() { $(this).dialog('close'); },
				Ok : function() { f.submit(); }
			}
		})
		
		f.ajaxForm({
			dataType : 'json',
			error : function() { alert('Error!'); d.dialog('close'); },
			beforeSubmit : function() {
				f.add(add).hide();
				d.append('<div class="mod-tshop-spinner rounded-5"/>').dialog('option', 'buttons', null);
			},
			success : function(data) {
				function close() {
					d.dialog('close');
					window.location.reload();
				}
				$('.mod-tshop-spinner', d).remove();
				d.append('<p class="'+(data.result ? 'mod-tshop-dialog-result' : 'mod-tshop-dialog-error')+'">'+data.msg+'</p>').dialog('option', 'buttons', {Ok : function() { close() }});
				setTimeout(function() { d.is(':visible') && close() }, 2000);
			}
		});
	}
	
	$('a.item-price').click(function(e) {
		e.preventDefault();
		var url = $(this).attr('href');
		$.ajax({
			url : url,
			dataType : 'json',
			error : function(e) { alert('Error!'); },
			success : function(data) {
				data.url = url;
				dialog(data);
			}
		})
	});
	
	// set model image
	$('#ishop-sel-img').click(function(e) {
		e.preventDefault();
		$('<div />').elfinder({
			url  : elBaseURL+'__finder__/', 
			lang : el_lang||'en', 
			editorCallback : function(url) { $('#imgURL').val(url).trigger('change');}, 
			dialog : { width : 750, modal : true}});
	});
	$('#ishop-rm-img').click(function(e) {
		e.preventDefault();
		$('#imgURL').val('').parents('form').submit();
	});
	$('#imgURL').bind('change', function() {
		var p = $('#ishop-sel-prev').empty();
		if (this.value) {
			var pw = p.width();
			var img = $('<img />').attr('src', this.value).load(function() {
				var w = parseInt($(this).css('width'));
				if (w>=pw) $(this).css('width', (pw-10)+'px')
			})
			p.append(img);
		}
	}).trigger('change');
	
});
