function checkNavForm()
{
	pos = document.getElementById('pos'); //alert(pos.value);
	t = document.getElementById('row_title');
	d = document.getElementById('row_deep');
	a = document.getElementById('all');
	ra = document.getElementById('row_all');
	c = document.getElementById('row_c1');
	p = document.getElementById('row_pIDs[]');
	if (pos.value == 0)
	{
		//alert('nai');
		t.style.display = d.style.display = ra.style.display = c.style.display = p.style.display = 'none';
	}
	else
	{
		t.style.display = d.style.display = ra.style.display = '';
		if (a.value == 1)
		{
			c.style.display = p.style.display =  'none';
		}
		else
		{
			c.style.display = p.style.display =  '';
		}
	}
}

