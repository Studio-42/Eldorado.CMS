(function($) {
	$.fn.elBBcodeEditor = function(opts) { 

		return $(this).each( function() {
			el.utils.langDomain('forum');

			var msgs = $.fn.elBBcodeEditor.msgs;
			var ta  = $(this).children('textarea').get(0)

			if ( typeof(document.all) != 'undefined')
			{
				$(ta).bind("select click keyup mouseup", function() {
					this.caretPos = document.selection.createRange().duplicate();
				});
			}
			
			$('select', $(this)).bind("change", function() {
				insert('[color='+$(this).val()+']', '[/color]');
				$(this).val('');
			} );
			
			$('.bb-button a', $(this)).bind("click", function() {
				var tag   = $(this).attr('meta');
				var open  = '['+tag+']';
				var close = '[/'+tag+']'
				if ( tag == 'url' || tag == 'img') {

					var url = prompt(  el.utils.translate(tag == 'url' ? 'Please, enter URL' : 'Please, enter image URL'), 'http://' );
					if (!url) return;
					open = tag=='url' ? '['+tag+'='+url+']' : '['+tag+']'+url;
				} else if (tag =='spoiler' ) {
					var title = prompt( el.utils.translate('Please, enter spoiler title') );
					if ( title ) open = '['+tag+'='+title+']'
				} else if (tag == 'list') {
					open  = '[list][li]';
					close = '[/li][/list]';
				} else if (tag == 'hr'){
					close = '';
				}
				insert(open, close);
				return false;
			} );
			
			$('.bb-smiley a', $(this)).bind("click", function() {
				insert($(this).attr('meta'), '');
				return false;
			} );
			
			function insert(open, close) {
				if ( ta.caretPos && ta.createTextRange )  // IE
				{ 
					var len = ta.caretPos.text.length;
					ta.caretPos.text = open+ta.caretPos.text+close;
					if ( !len ) {
						ta.caretPos.select();
					} else {
						ta.focus(ta.caretPos);
					}
				}
				else if (typeof(ta.selectionStart) != "undefined")
				{
					var head   = ta.value.substr(0, ta.selectionStart); 
					var sel    = ta.value.substr(ta.selectionStart, ta.selectionEnd-ta.selectionStart); 
					var tail   = ta.value.substr(ta.selectionEnd); 
					var scroll = ta.scrollTop;
					ta.value   = head+open+sel+close+tail;
					if (ta.setSelectionRange)
					{
						ta.setSelectionRange(head.length, head.length+sel.length+open.length+close.length)
					}
					ta.focus();
					ta.scrollTop = scroll;
				}
				else
				{
					ta.value += open+close;
				}
			}
		});
	}
})(jQuery);