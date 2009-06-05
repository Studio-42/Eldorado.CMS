


function loadTpl(hash, result)
{
  if (!result)
  {
    document.getElementById('teLoad').style.display = '';
    loadXMLDoc( elURL + '_xml_/' + hash + '/' );
  }
  else
  {
    document.getElementById('teLoad').style.display = 'none';

    e = result.getElementsByTagName('error');
    if (e[0])
    {
      displayTEError(1, e[0].firstChild.data);
      return false;
    }

    c        = result.getElementsByTagName('content');
    content  = c[0].firstChild.data;
    fileName = result.getElementsByTagName('fileName')[0].firstChild.data;
    url      = result.getElementsByTagName('url')[0].firstChild.data;


    displayTEZone(1, url, fileName, content)
    //f = document.getElementById('tplName');alert(f);
    //f.firstChild.data = fileName;
  }

  return false;
}

function displayTEError(d, msg)
{
  err    = document.getElementById('teError');
  errMsg = document.getElementById('teErrMsg');
  if (d>0)
  {
    document.getElementById('teList').style.display = 'none';

    errMsg.appendChild( document.createTextNode(msg) );
    err.style.display = '';
  }
  else
  {
    errMsg.removeChild( errMsg.firstChild );
    err.style.display = 'none';
    document.getElementById('teList').style.display = '';
  }
}

function displayTEZone(d, url, fileName, content)
{
  teZone = document.getElementById('teEditZone');
  teList = document.getElementById('teList');
  form   = document.getElementById('teForm');
  ta     = document.getElementById('teTA');
  tn     = document.getElementById('teTpl');

  if (d>0)
  {
    teList.style.display = 'none';
    teZone.style.display = '';
    form.setAttribute('action', url);
    tn.appendChild(document.createTextNode(fileName));
    ta.value = content;
    ta.setAttribute('rows', 40);
    ta.style.width = '100%';
    ta.focus();
  }
  else
  {
    teZone.style.display = 'none';
    teList.style.display = '';
    tn.removeChild( tn.firstChild );
    ta.value = '';
  }
}

function dirCtr(name)
{
  d = document.getElementById('d_'+name);
  d.style.display = d.style.display == '' ? 'none' : '';
  return false;
}

function newTpl(dirHash, dirName)
{
  teList = document.getElementById('teList');
  f = document.getElementById('teNewTpl');
  dn = document.getElementById('teNewTplDirName');
  dh = document.getElementById('teNewTplDirHash');
  tn = document.getElementById('teTplName');

  teList.style.display = 'none';
  if (dn.firstChild)
  {
    dn.removeChild(dn.firstChild);
  }
  dn.appendChild(document.createTextNode(dirName));
  dh.value = dirHash;
  f.style.display = '';
  tn.focus();
  return false;
}