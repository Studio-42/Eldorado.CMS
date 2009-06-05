
function checkNavType(v)
{
  var mp = document.getElementById('row_mainMenuPos');
  var sp = document.getElementById('row_subMenuPos');
  var si = document.getElementById('row_subMenuUseIcons');
  var p  = document.getElementById('row_subMenuDisplParent');

  mp.style.display = v == 2 ? 'none' : '';
  sp.style.display = v != 2 ? 'none' : '';
  si.style.display = v != 2 ? 'none' : '';
  checkSubMenuParam(v, document.getElementById('subMenuPos').value );
}

function checkSubMenuParam(v, pos)
{
   var p  = document.getElementById('row_subMenuDisplParent');
   if ( v == 2 )
  {
    p.style.display = pos != 't' ? '' : 'none';
  }
  else
  {
    p.style.display = 'none';
  }
}

function getElementsByClass(searchClass,node,tag) {
 var classElements = new Array();
 if ( node == null )
  node = document;
 if ( tag == null )
  tag = '*';
 var els = node.getElementsByTagName(tag);
 var elsLen = els.length;

 var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
 for (i = 0, j = 0; i < elsLen; i++) {
   if ( pattern.test(els[i].className) ) {
   classElements[j] = els[i];
   j++;
  }
 }
 return classElements;
}


function initMetaTree()
{
  var expanders = getElementsByClass('expander', document, 'div');
  //alert(expanders.length);
  for (i=0; i<expanders.length; i++)
  {
    var img = expanders[i].getElementsByTagName('img')[0]; //alert(img);
    if (typeof img.addEventListener != 'undefined')
      img.addEventListener('click',  elNCMetaControl, false);
    else if (typeof img.attachEvent != 'undefined')
      img.attachEvent('onclick', elNCMetaControl); 
  }
  
  var metas = getElementsByClass('meta', document, 'div');
  //alert(metas.length);
  for (i=0; i<metas.length; i++)
  {
    var img = metas[i].getElementsByTagName('img')[0]; //alert(img);
    if (typeof img.addEventListener != 'undefined')
      img.addEventListener('click',  elNCMetaControl, false);
    else if (typeof img.attachEvent != 'undefined')
      img.attachEvent('onclick', elNCMetaControl);
  }
}


function elNCExpanderStyle(ID)
{
  exp = document.getElementById('exp_'+ID); //alert(ID+' '+exp);
  if ( exp )
  {
    exp.className = exp.className == 'expander' ? 'collapser' : 'expander';
  }
}


function elNCMetaControl(evt, result)
{
  if ( evt )
  {
    var evtTarget = (window.event) ? window.event.srcElement : evt.target;
    var pNode     = evtTarget.parentNode.parentNode;
    if (!pNode)
    {
      return alert('ERROR: Big BADA BUM! Parent node was found!');
    }
    
    // хто здесь? 0_o
    if ( evtTarget.parentNode.className == 'meta' )
    {
      //alert('meta');
      var url    = elURL + '_xml_/meta/meta/'+pNode.id+'/'; //alert(url);
      var chNode = document.getElementById('meta_'+pNode.id);
    }
    else
    {
      //alert('tree');
      var url    = elURL + '_xml_/meta/'+pNode.id+'/'; //alert(url);
      var chNode = document.getElementById('child_'+pNode.id);
    }
    
    if ( !chNode )
    {
      //alert(url);
      loadXMLDoc(url);  
    }
    else
    {
      chNode.style.display = (chNode.style.display=='none') ? '' : 'none';
      if ( evtTarget.parentNode.className != 'meta' )
      {
        elNCExpanderStyle(pNode.id);
      }
    }
  }
  else
  {
    var err = result.getElementsByTagName('error'); 
    if ( err.length >0 )
    {
      return alert('ERROR: '+err[0].firstChild.data);
    }
    var parentID = result.getElementsByTagName('parentID')[0].firstChild.data; //alert('parentID:'+parentID);
    var pNode    = document.getElementById(parentID); //alert('pNode:'+pNode);
    if (!pNode)
    {
      return alert('ERROR: Big BADA BUM! Parent node was found! 0_0');
    }
    var arg = result.getElementsByTagName('arg')[0].firstChild.data; 
    if ( arg == 'meta' )
    {
      return elNCMetaCreateForm(pNode, result);
    }
    else
    {
      elNCExpanderStyle(pNode.id);
      return elNCMetaCreateTree(pNode, result);
    }
  }
}

function elNCMetaCreateTree(pNode, result)
{
  var chNode   = document.createElement('div'); //alert('chNode:'+chNode);
  chNode.setAttribute('id', 'child_'+pNode.id);
  chNode.className = 'chNode';
  var img = document.createElement('img');
  img.setAttribute('src', elBaseURL+'style/images/pixel.gif');
  img.style.width  = '9px';
  img.style.height = '9px';
  
  var nodes = result.getElementsByTagName('node');
  for ( i=0; i<nodes.length; i++ )
  {
    var ID        = nodes[i].getElementsByTagName('pid')[0].firstChild.data+'_'+nodes[i].getElementsByTagName('cid')[0].firstChild.data+'_'+nodes[i].getElementsByTagName('iid')[0].firstChild.data;
    var name      = nodes[i].getElementsByTagName('name')[0].firstChild.data; //alert(name);
    var hasChilds = nodes[i].getElementsByTagName('has_childs')[0].firstChild.data;
    var node      = document.createElement('div');
    node.setAttribute('id', ID);
    node.className = 'node';
    
    // create expander button
    var exp = document.createElement('div');
    exp.setAttribute('id', 'exp_'+ID);
    var sw  = img.cloneNode(true);
    sw.style.width = "12px";
    if ( hasChilds > 0 )
    {
      exp.className = 'expander';
      elAttachEvent(sw, elNCMetaControl);
    }
    else
    {
      exp.className = 'dummy';
    }
    exp.appendChild(sw);
    
    // create meta button
    var meta = document.createElement('div');
    meta.className = 'meta';
    var sw = img.cloneNode(true);
    elAttachEvent(sw, elNCMetaControl);
    meta.appendChild(sw);
    
    node.appendChild(exp);
    node.appendChild(meta);
    node.appendChild(document.createTextNode(name));
    chNode.appendChild(node);
  }
  
  pNode.appendChild(chNode);
}

function elAttachEvent(obj, func)
{
  if (typeof obj.addEventListener != 'undefined')
    obj.addEventListener('click',  func, false);
  else if (typeof obj.attachEvent != 'undefined')
    obj.attachEvent('onclick', func);
}



function elNCMetaCreateForm(pNode, result)
{
  var chID   = 'meta_'+pNode.id;
  var chNode = document.getElementById(chID);
  if ( chNode )
  {
    //alert('exists: '+chNode);
    chNode.parentNode.removeChild(chNode);
  }

  var chNode = document.createElement('div');
  chNode.setAttribute('id', chID);
  chNode.className = 'metaCont';
  
  var form = document.createElement('form');
  form.setAttribute('method', 'POST');
  form.setAttribute('action', elURL + '_xml_/meta/meta_edit/'+pNode.id+'/');
  
  var tb         = document.createElement('table');
  tb.className   = 'form-tb';
  //tb.setAttribute('border', '1');
  var tbody      = document.createElement('tbody');
  var txt        = document.createElement('input');
  txt.setAttribute('type', 'text');
  txt.setAttribute('size', '25');
  var ta         = document.createElement('textarea');
  ta.setAttribute('rows', '5');
  ta.setAttribute('cols', '45');
  ta.style.width = '100%';
  
  // table header
  var row        = document.createElement('tr');
  var cell       = document.createElement('td');
  cell.setAttribute('colSpan', '2');
  cell.className = 'formLabel';
  cell.appendChild( document.createTextNode('Meta tags') );
  row.appendChild(cell);
  tbody.appendChild(row);
  // message if exists
  var msg = result.getElementsByTagName('message')[0];
  if ( msg )
  {
    var row  = document.createElement('tr');
    var cell = document.createElement('td');
    cell.setAttribute('colSpan', '2');
    cell.className = 'form-errors';
    cell.appendChild( document.createTextNode(msg.firstChild.data) );
    row.appendChild(cell);
    tbody.appendChild(row);
  }
  
  
  var recs     = result.getElementsByTagName('node');
  
  for ( i=0; i<recs.length; i++ )
  {
    var name = recs[i].getElementsByTagName('name')[0].firstChild.data; //alert(name);
    if ( recs[i].getElementsByTagName('content')[0].firstChild )
    {
      var cont = recs[i].getElementsByTagName('content')[0].firstChild.data;  
    }
    else
    {
      var cont = '';  
    }
    
    if (name == 'title')
    {
      elName  = document.createTextNode(name);
      elValue = txt.cloneNode(true);
      elValue.setAttribute('size', '45');
      elValue.setAttribute('name', 'title');
      elValue.setAttribute('value', cont);
    }
    else
    {
      elName = txt.cloneNode(true);
      elName.setAttribute('name', 'metaName[]');
      elName.setAttribute('value', name);
      
      elValue = ta.cloneNode(true);
      elValue.setAttribute('name', 'metaValue[]');
      elValue.appendChild( document.createTextNode(cont) );
    }
    var cell = document.createElement('td');
    var row  = document.createElement('tr');
    cell.appendChild(elName);
    row.appendChild(cell);
    var cell = document.createElement('td');
    cell.appendChild(elValue);
    row.appendChild(cell);
    
    tbody.appendChild(row);
  }
  
  // new meta row
  elName = txt.cloneNode(true);
  elName.setAttribute('name', 'metaName[]');
  elName.setAttribute('value', '');
  elValue = ta.cloneNode(true);
  elValue.setAttribute('name', 'metaValue[]');
  elValue.appendChild( document.createTextNode("") );
  var cell = document.createElement('td');
  var row  = document.createElement('tr');
  cell.appendChild(elName);
  row.appendChild(cell);
  var cell = document.createElement('td');
  cell.appendChild(elValue);
  row.appendChild(cell);
  
  tbody.appendChild(row);
  // table footer
  var row  = document.createElement('tr');
  var cell = document.createElement('td');
  cell.setAttribute('colSpan', '2');
  cell.className = 'formFooter';
  button = document.createElement('button');
  button.setAttribute('type', 'button');
  button.setAttribute('class', 'form-submit');
  button.appendChild(document.createTextNode(msgSave));
  elAttachEvent(button, elNCSubmitForm);
  cell.appendChild( button );
  row.appendChild(cell);
  tbody.appendChild(row);
  
  tb.appendChild(tbody);
  form.appendChild(tb);
  chNode.appendChild( form );
  var treeNode = document.getElementById('child_'+pNode.id); //alert('CHIILD:'+chNode);
  if ( !treeNode )
  {
    pNode.appendChild(chNode);  
  }
  else
  {
    pNode.insertBefore(chNode, treeNode);
  }
}


function elNCSubmitForm(evt)
{
  var evtTarget = (window.event) ? window.event.srcElement : evt.target;
  
  var f = evtTarget.parentNode.parentNode.parentNode.parentNode.parentNode;
  //alert(f.action); alert(f.elements.length); //return false;
  
  var hash = {};
  for (var k=0; k<f.elements.length; k++)
  {
    //alert(f.elements[k]+": "+f.elements[k].type+" : "+f.elements[k].name);
    //alert(f.elements[k]+" :"+k);
    if (f.elements[k].type == 'text' && f.elements[k].name == 'title')
    {
      //alert('title: '+f.elements[k].value);
      hash['title'] = f.elements[k].value;
    }
    else
    {
      if (f.elements[k].type == 'text')
      {
        //alert(f.elements[k].value);
        //if (f.elements[k].value != "")
        //{
          var key = f.elements[k].value;  
        //}
        
      }
      if (f.elements[k].type == 'textarea')
      {
        //alert(f.elements[k].value);
        if ( key != "" )
        {
          hash[key] = f.elements[k].value;
          key = "";
        }
        
      }
    }
  }
  
  
  //var d = urlEncodeData(hash); alert(d);
  loadXMLDoc(f.action, "POST", hash);  
  return false;
}

// insertBefore
