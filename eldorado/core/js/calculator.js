(function($) {

	$.fn.elplcalculator = function(o) {
		
		var opts = $.extend($.fn.elplcalculator.defaults, o);
		
		return this.each( function() {
			var form    = null;
			var dialog  = null;
			var formula = '';
			var unit    = '';
			var dtype   = '';

			function showResult(result) {
				res.show().children('.calc-result-msg').text(opts.resultMsg+' ').siblings('.calc-result').text(result+' '+unit);
				err.hide();
			}

			function showError(msg) {
				res.text('').hide();
				err.text(msg).show();
			}


			function calculate() {
				var _formula = formula;

				form.find(':text,select').each(function() {
					var name = $(this).attr('name'); 

					if (name) {
						var value = $(this).val();
						if ($(this).attr('type') == 'text') {
							value = $(this).hasClass('double') ? parseFloat(value) : parseInt(value);
						}
						var r = new RegExp("\\$"+name+"([^a-z0-9]*)", 'gi');
						_formula = _formula.replace(r, value+'$1');
					}
				})
				_formula = 'var result = '+(dtype=='double' ? 'parseFloat(' : 'parseInt(')+_formula+')';
				try {
					eval(_formula);
				} catch (e) {
					// window.console.log(e)
					showError(opts.errMsg);
				}

				if (!isNaN(result)) {
					showResult(result);
				} else {
					showError(opts.errMsg);
				}
			}

			
			function makeForm(data) {
				dialog.hideSpinner().option('title', data.name)
				dialog.option('buttons', {Cancel : function(){ dialog.close(); }, Ok : function() { dialog.form.submit() }});
				form = dialog.form;
				formula = data.formula;
				unit = data.unit;
				dtype = data.dtype;
				res = $('<div />').addClass('calc-result-place ui-state-highlight').hide().append($('<em />').addClass('calc-result-msg')).append($('<strong />').addClass('calc-result'));
				err = dialog.error;
				dialog.append(res);
				for (var i in data.vars) {
					if (data.vars[i].type == 'input') {
						var input = $('<input type="text" />').attr('name', data.vars[i].name).addClass('required number');
					} else {
						var input = $('<select />').attr('name', data.vars[i].name);
						for (var name in data.vars[i].variants) {
							input.append($('<option />').val(name).text(data.vars[i].variants[name]));
						}
					}
					dialog.append([data.vars[i].title, input], false, true);
				}
			}
			
			if (opts.type == 'dialog') {
				if (!opts.url) {
					opts.url = $(this).attr('href');
				}
				if (!opts.url) {
					return;
				}
				var url = $(this).attr('href');

				$(this).click(function(e) {
					e.stopPropagation();
					e.preventDefault();
					dialog = new elDialogForm({
						submit : null,
						validate : {
							submitHandler : calculate,
							errorPlacement : function(error, element) { error.insertBefore(element); }
							},
						spinner : opts.spinner,
						dialog : {
							position : 'top',
							width : 400,
							title : ''							
						}
					})
					
					dialog.showSpinner().open();
					$.ajax({
						type: "GET",
						url: url,
						dataType : 'json',
						success : function(data) { 
							if (data.error) {
								dialog.showError(data.error);
							} else {
								makeForm(data);
							}
						 },
						error : function(r, e) { dialog.showError(opts.loadErrMsg+": "+e); }

					});
				})

			} else {
				form = this.nodeName == 'FORM' ? $(this) : $(this).find('form').eq(0);
				if (!form) {
					return
				//	return window.console && window.console.log && window.console.log('form not found');
				}	
				var res     = form.find('div.calc-result-place').eq(0);
				var err     = form.find('div.calc-error').eq(0);
				var formula = form.find(':hidden[name="formula"]').val()+' ';
				var unit    = form.find(':hidden[name="unit"]').val();
				var dtype   = form.find(':hidden[name="dtype"]').val();	
				form.validate({
					submitHandler  : calculate,
					errorPlacement : function(error, element) { 
						var pl = $(element).siblings('.err'); 
						if (pl.length) { 
							error.insertAfter(pl); 
						} else {
							error.insertBefore(element); 	
						} 
					}
				})		
			}
		});
		
	}
	$.fn.elplcalculator.defaults = {
		type       : 'inline',
		url        : '',
		resultMsg  : 'Result:',
		errMsg     : 'Error! Probably invalid data was entered',
		loadErrMsg : 'Data transfer error',
		spinner    : 'Loading...'
	};
})(jQuery);

