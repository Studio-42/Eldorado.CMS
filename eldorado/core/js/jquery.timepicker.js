/* jQuery timepicker
 * replaces a single text input with a set of pulldowns to select hour, minute in 24h format
 *
 * Copyright (c) 2007 Jason Huck/Core Five Creative (http://www.corefive.com/)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Version 1.0
 */

(function($){
	jQuery.fn.timepicker = function(b, e){
		this.each(function(){
			// get the ID and value of the current element
			var i = this.id;
			var v = $(this).val();
	
			// the options we need to generate
			var hrs = new Array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			var mins = new Array('00','05','10','15','20','25','30','35','40','45','50','55');
			
			// redefine hours if we have input (ie workhours)
			if(b < e){
				var hrs = new Array();
				var h = b;
				for(; h <= e; h++){
					if(h<10)
				 		hrs.push('0' + h);
					else
						hrs.push(h);
				}
			}
			
			var h = -1;
			var m = -1;

			// next comments about howto set time to current
			// default to the current time
			// var d = new Date;
			// var h = d.getHours();
			// var m = d.getMinutes();

			// override with current values if applicable
			if(v.length == 5){
				h = parseInt(v.substr(0,2), 10);
				m = parseInt(v.substr(3,2), 10);
			}
			
			// round minutes to nearest value in mins
			for(mn in mins){
				if(m <= parseInt(mins[mn])){
					m = parseInt(mins[mn]);
					break;
				}
			}
			
			// increment hour if we push minutes to next 00
			if(m > 55){
				m = 0;
				
				switch(h){
					case(23):
						h = 0;
						break;
						
					default:
						h += 1;
						break;
				}
			}
			
			// build the new DOM objects
			var output = '';
			
			output += '<select id="h_' + i + '" class="h timepicker">';
			output += '<option value="">&nbsp;</option>'; 
			for(hr in hrs){
				output += '<option value="' + hrs[hr] + '"';
				if(parseInt(hrs[hr], 10) == h) output += ' selected';
				output += '>' + hrs[hr] + '</option>';
			}
			output += '</select>';
			output += ' : ';
			output += '<select id="m_' + i + '" class="m timepicker">';
			output += '<option value="">&nbsp;</option>';			
			for(mn in mins){
				output += '<option value="' + mins[mn] + '"';
				if(parseInt(mins[mn], 10) == m) output += ' selected';
				output += '>' + mins[mn] + '</option>';
			}
			output += '</select>';				
		
			// hide original input and append new replacement inputs
			$(this)[0].style.display = 'none';
			$(this).after(output);
		});
		
		$('select.timepicker').change(function(){
			var i = this.id.substr(2);
			var h = $('#h_' + i).val();
			var m = $('#m_' + i).val();
			var v = h + ':' + m;
			$('#' + i).val(v);
		});
		
		return this;
	};
})(jQuery);
