jQuery.validator.addMethod("regexp", function(value, element, param) {
	return param.test(value); 
}, "No white space please");

jQuery.validator.addMethod("alfanum", function(value, element) {
	return this.optional(element) || /^[a-z0-9_]+$/i.test(value);
}, "Please enter only latin letters, numbers or underscores.");

jQuery.validator.addMethod("letters", function(value, element) {
	return this.optional(element) || /^[a-z]+$/i.test(value);
}, "Please enter only latin letters.");

jQuery.validator.addMethod("nowhitespace", function(value, element) {
	return this.optional(element) || /^\S+$/i.test(value);
}, "No white space please");
jQuery.validator.addMethod(
	"dateITA",
	function(value, element) {
		var check = false;
		var re = /^\d{1,2}\/\d{1,2}\/\d{4}$/;
		if( re.test(value)){
			var adata = value.split('/');
			var gg = parseInt(adata[0],10);
			var mm = parseInt(adata[1],10);
			var aaaa = parseInt(adata[2],10);
			var xdata = new Date(aaaa,mm-1,gg);
			if ( ( xdata.getFullYear() == aaaa ) && ( xdata.getMonth () == mm - 1 ) && ( xdata.getDate() == gg ) )
				check = true;
			else
				check = false;
		} else
			check = false;
		return this.optional(element) || check;
	}, 
	"Please enter a correct date"
);
jQuery.validator.addMethod("alfanumi18", function(value, element) {
	return this.optional(element) || /^[^\s!@#\$%\^&*\(\)\-+=\[\]\{\}\/<>'"\?\.,;:]+$/i.test(value);
}, "Please enter only letters, numbers or underscores.");

jQuery.validator.addMethod("lettersi18", function(value, element) {
	return this.optional(element) || /^[^\d\s!@#\$%\^&*\(\)\-+=\[\]\{\}\/<>"'\?\.,;:]+$/i.test(value);
}, "Please enter only latin letters.");

