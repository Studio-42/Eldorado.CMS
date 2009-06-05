
function containerConf( )
{
  var v = document.getElementById('goFirstChild').value > 0 ? 'none' : '';
  document.getElementById('row_displayDescrip').style.display = v;
  document.getElementById('row_deep').style.display = v;
  document.getElementById('row_showIcons').style.display = v;
}