
function checkImgAction(v, fmURL)
{
  //alert(v); alert(fmURL);
  if (v == 1)
  {
    popUp(fmURL, 500, 400);
  }
  else if (v == 2)
  {
    SetUrl(0);
  }
}

function SetUrl(URL)
{

   var h    = document.getElementById('imgURL');
   var prev = document.getElementById('imgPrew');
   if (URL == 0)
   {
     h.value = '';
     var i = document.createTextNode('');
   }
   else
   {
     h.value = URL;
     var i = document.createElement('img');
     i.src=URL;
   }
   prev.replaceChild(i, prev.firstChild);
}


function checkAuthType()
{
  var aType = document.getElementById('isRemoteAuth').value; //alert(aType);
  //var host  = document.getElementById('host');
  var rHost = document.getElementById('row_host');
  //var db    = document.getElementById('db');
  var rDb   = document.getElementById('row_db');
  //var user  = document.getElementById('user');
  var rUser = document.getElementById('row_user');
  //var pass  = document.getElementById('pass');
  var rPass = document.getElementById('row_pass');
  var rSock = document.getElementById('row_sock');
//alert(rSock);
  if ( 1==aType )
  {
//    host.disabled = db.disabled = user.disabled = pass.disabled = '';
    rHost.style.display = rDb.style.display = rUser.style.display = rPass.style.display = rSock.style.display = '';
  }
  else
  {
  //  host.disabled = db.disabled = user.disabled = pass.disabled = 'on';
    rHost.style.display = rDb.style.display = rUser.style.display = rPass.style.display  = rSock.style.display ='none';
  }
}

function checkMailForm()
{
  var transport = document.getElementById('transport');
  if ('PHP' == transport.value )
  {
    document.getElementById('row_smtpHost').style.display = 'none';
    document.getElementById('row_smtpPort').style.display = 'none';
    document.getElementById('row_smtpAuth').style.display = 'none';
    document.getElementById('row_smtpUser').style.display = 'none';
    document.getElementById('row_smtpPass').style.display = 'none';
  }
  else
  {
    document.getElementById('row_smtpHost').style.display = '';
    document.getElementById('row_smtpPort').style.display = '';
    document.getElementById('row_smtpAuth').style.display = '';
    var auth = document.getElementById('smtpAuth');
    document.getElementById('row_smtpUser').style.display = auth.value == 1 ? '' : 'none';
    document.getElementById('row_smtpPass').style.display = auth.value == 1 ? '' : 'none';

  }
}
