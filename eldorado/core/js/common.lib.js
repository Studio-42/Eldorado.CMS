
function popUp(url, w, h)
{
	window.open(url, null, 'top=50,left=50,scrollbars=yes,resizable=yes,width='+(w+40)+',height='+(h+60));
	return false;
}

function switchView(ID)
{
	el = document.getElementById(ID); //alert(el); return false;
	if (el)
	{
		el.style.display = el.style.display == '' ? 'none' : '';
	}
	return false;
}

function initMenu( isVertical )
{
	DynarchMenu.setup( 'menu', { electric: true, lazy: true, scrolling: true, vertical: isVertical } );
	document.getElementById('menu-placeholder').style.display = 'none';
}


var req;

function loadXMLDoc(url, HTTPMethod, data)
{
	if (HTTPMethod != "GET" && HTTPMethod != "POST")
	{
		HTTPMethod = "GET";
	}
	// branch for native XMLHttpRequest object
  if(window.XMLHttpRequest)
  {
  	try
  	{
			req = new XMLHttpRequest();
    }
    catch(e)
    {
			req = false;
    }
    // branch for IE/Windows ActiveX version
  }
  else if(window.ActiveXObject)
  {
   	try
   	{
    	req = new ActiveXObject("Msxml2.XMLHTTP");
    }
    catch(e)
    {
     	try
     	{
      	req = new ActiveXObject("Microsoft.XMLHTTP");
      }
      catch(e)
      {
      	req = false;
      }
		}
  }

	if(req)
	{

		req.onreadystatechange = processReqChange;
		req.open(HTTPMethod, url, true);
		req.setRequestHeader("Cookie", "aaaaaaaa");
		req.setRequestHeader("Cookie", document.cookie);
		//req.send("");
        if (HTTPMethod == "POST" && data)
        {//alert('1');
            //if (req.setRequestHeader) 
                req.setRequestHeader("Content-Type","application/x-www-form-urlencoded"); 
              //  alert('2');
            req.send(urlEncodeData(data));
            //alert('3');
        }
        else
        {
          req.send("");  
        }
	}
}


function processReqChange()
{
	// only if req shows "complete"
  if (req.readyState == 4)
  {
  	// only if "OK"
    if (req.status == 200)
    {
      //alert(req.responseText);
      //alert(req.getAllResponseHeaders());
    	// ...processing statements go here...
   		method = req.responseXML.getElementsByTagName('method')[0].firstChild.data; 
   		result = req.responseXML.getElementsByTagName('result')[0]; //alert(result);
   		eval(method+'(\'\', result);');
     }
     else
     {
	     alert("There was a problem retrieving  the XML data:\n" + req.statusText);
     }
  }
}


function urlEncodeData(data)
{
    var query = [];
    if (data instanceof Object) {
        for (var k in data) {
            query.push(encodeURIComponent(k) + "=" + encodeURIComponent(data[k]));
        }
        return query.join('&');
    } else {
        return encodeURIComponent(data);
    }
}


function elInArray(needle, haystack)
{
  for (i=0; i<haystack.length; i++)
  {
    if (needle == haystack[i])
    {
      return true;
    }

  }
  return false;
}


function elSetElementPos(elID, parentID, posHoriz, posVert, offsetX, offsetY)
{
	var el     = document.getElementById(elID); //alert(el);
	var parent = document.getElementById(parentID); //alert(parent);
	if ( !el || !parent )
	{
		return;
	}
	posLeft = posHoriz == 'left' ? offsetX : parent.offsetWidth - offsetX; 
	posTop = posVert == 'top' ? offsetY : parent.offsetHeight - offsetY;
	
	if (el.style.position != 'absolute')
	{
		el.style.position = 'absolute';
	}
	el.style.top = posTop;
	el.style.left = posLeft; 
	if (el.style.display != 'block')
	{
		el.style.display = 'block';
	}
}



