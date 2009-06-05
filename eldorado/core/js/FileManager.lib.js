function mkdirForm( )
{
  var h = document.getElementById('newName'); 
  var dName = prompt('Enter new directory name'); 
  if ( dName )
  {
    h.value = dName;
    h.form.action += 'mkdir/';
    h.form.submit();
  }
  return false;
}

function renameForm( hash, newName )
{
  var n = document.getElementById('newName'); 
	
  if ( newName )
  {
    n.value = newName;
    n.form.action += 'rename/'+hash+'/';
    n.form.submit();
  }
  return false;
}


function insertURL(URL)
{
  window.top.opener.SetUrl( URL ) ;
  window.top.close() ;
  window.top.opener.focus() ;
  return false;
}

