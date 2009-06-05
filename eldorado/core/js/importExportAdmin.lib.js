
function checkImportForm()
{
	var im = document.getElementById('import').value; 
	document.getElementById('row_importURL').style.display = im>0 ? '' : 'none';
	document.getElementById('row_c1').style.display = im>0 ? '' : 'none';
	document.getElementById('row_importParam').style.display = im>0 ? '' : 'none';
	var curl = document.getElementById('row_useCurl');
	if (curl)
	{
	  document.getElementById('row_useCurl').style.display = im>0 ? '' : 'none';
	}
	document.getElementById('row_importKEY').style.display = im>0 ? '' : 'none';
	document.getElementById('row_importPeriod').style.display = im>0 ? '' : 'none';
	document.getElementById('row_i-d').style.display = im>0 ? '' : 'none';
}

function checkExportForm()
{
	var ex = document.getElementById('export').value; 
	document.getElementById('row_exportKEY').style.display = ex>0 ? '' : 'none';
}