
function checkISPropFormAdmin()
{
  var type = document.getElementById('type').value; //alert(type);
  //var fields = new Array('', 'values1', 'values2', 'values3', 'values4'); //alert(fields.length);
  for (i=1; i<=4; i++)
  {
    row = document.getElementById('row_values'+i);
    if ( row )
    {
      row.style.display = i==type ? '' : 'none';
    }
  }
}

function elISITypeTbControl(ID)
{
  var tb = document.getElementById('type_tb_'+ID); //alert(tb);
  var sw = document.getElementById('switch_'+ID);
  if ( tb.style.display == 'none' )
  {
    tb.style.display = '';
    sw.firstChild.nodeValue = '[-]';
  }
  else
  {
    tb.style.display = 'none';
    sw.firstChild.nodeValue = '[+]';
  }
  return false;
}

