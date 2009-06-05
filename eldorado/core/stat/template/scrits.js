function log_out() {

	if (confirm('Вы действительно хотите выйти?')) {
		document.execCommand('ClearAuthenticationCache');
		return true;
	} else {
		return false;
	}
}

function get_period() {

	begin  = document.period.bd.value + "-" + document.period.bm.value + "-" + document.period.by.value;
	end  = document.period.ed.value + "-" + document.period.em.value + "-" + document.period.ey.value;
	type  = document.period.type.value;
	type_gr  = document.period.type_gr.value;

	window.location = 'index.php?begin='+begin+'&end='+end+'&type='+type+'&type_gr='+type_gr;

	return false;
}