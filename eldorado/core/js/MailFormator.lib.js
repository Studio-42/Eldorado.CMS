function checkMFType()
{
	var ftype     = document.getElementById('ftype'); 
	var flabel    = document.getElementById('row_flabel');
	var fopts     = document.getElementById('row_fopts');
	var foptsCom  = document.getElementById('row_c_opts');
	var fvalidAll = document.getElementById('row_fvalid_all');
	var fvalidSel = document.getElementById('row_fvalid_sel');
	var ferror    = document.getElementById('row_ferror');
	var defValue = document.getElementById('row_fvalue');
	var comValue = document.getElementById('row_c_value');
	var fsize    = document.getElementById('row_fsize');

	//alert('Koko-ni des '+ foptsCom);
	var isComment = ftype.value == 'comment' || ftype.value == 'subtitle';
	var isSelect  = ftype.value == 'select' || ftype.value == 'checkbox' || ftype.value == 'radio' || ftype.value == 'date';
	
	defValue.style.display = '';
	comValue.style.display = '';
	fsize.style.display = 'none'; 
	if ( ftype.value == 'captcha' )
	{
		flabel.style.display = fopts.style.display = foptsCom.style.display = defValue.style.display = 
		comValue.style.display = fvalidAll.style.display = fvalidSel.style.display = ferror.style.display = 'none';
		return;
	}
	if ( isComment )
	{
		flabel.style.display = fopts.style.display = foptsCom.style.display = 
		fvalidAll.style.display = fvalidSel.style.display = ferror.style.display = 'none';
	}
	else if (ftype.value == 'file')
	{
		fsize.style.display = '';
		defValue.style.display = 'none';
		comValue.style.display = 'none';
		fopts.style.display = 'none';
		foptsCom.style.display = 'none';
		fvalidAll.style.display = 'none';
		fvalidSel.style.display = 'none';
		ferror.style.display = 'none';
	}
	else
	{
		flabel.style.display = '';
		if ( isSelect )
		{
			fopts.style.display = '';
			foptsCom.style.display = '';
			fvalidAll.style.display = 'none';
			fvalidSel.style.display = '';
			var fvalid = document.getElementById('fvalid_sel');
			ferror.style.display = fvalid.value == 'none' ? 'none' : '';
		}
		else
		{
			fopts.style.display = 'none';
			foptsCom.style.display = 'none';
			fvalidAll.style.display = '';
			fvalidSel.style.display = 'none';
			var fvalid = document.getElementById('fvalid_all');
			ferror.style.display = fvalid.value == 'none' ? 'none' : '';
		}
	} 
}

