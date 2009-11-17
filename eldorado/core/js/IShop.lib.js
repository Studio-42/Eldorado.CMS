
function checkOrderDepend(itemID, propID, propValue)
{
  // url = elURL + '_xml_/depend/'+itemID+'/'+propID+'/'+propValue+'/'; 
  // loadXMLDoc(url);
	$.ajax({
		url      : elURL + '_xml_/depend/'+itemID+'/'+propID+'/'+propValue+'/',
		dataType : 'xml',
		success  : function(data) {
			updateProps('', data)
		}
	});
}

function updateProps( str, result )
{
  props = result.getElementsByTagName('property'); 
  if (!props.length)
  {
    return;
  }

  for (i=0; i<props.length; i++)
  {
    vals = new Array();
    vsrc = props[i].getElementsByTagName('value'); 
    for (j=0; j<vsrc.length; j++)
    {
      vals.push(vsrc[j].firstChild.data)
    }
    id = props[i].getElementsByTagName('id')[0].firstChild.data;
    id = 'prop_'+id; 
    select = document.getElementById(id);
    len = select.options.length
    for (j=0; j<len; j++)
    {
      if ( $.inArray(select.options[j].value, vals) )
      {
        select.options[j].removeAttribute('disabled');
      }
      else
      {
        select.options[j].setAttribute('disabled', 'on');
      }

    }
  }
}



function reloadSearchForm()
{
  select = document.getElementById('isSearchGroup'); 
  if ( select.value )
  {
	$.ajax({
		url      : elURL + '_xml_/search_form/'+select.value+'/',
		dataType : 'xml',
		success  : function(data) {
			updateSearchForm('', data)
		}
	});
  }
}

function updateSearchForm( str, result )
{

  colNum = result.getElementsByTagName('colNum')[0].firstChild.data; 
  colCnt = 1;
  
  div    = document.getElementById('sd'); 
  tbOld  = div.getElementsByTagName('table')[0];
  tb     = document.createElement('table');
  tb.setAttribute('class', 'form-tb');
  tb.setAttribute('cellspacing', '0');
  tb.setAttribute('style', 'width:100%');
  tbd = document.createElement('tbody');
  row = document.createElement('tr');
  
  elements = result.getElementsByTagName('element'); 
  
  for (i=0; i<elements.length; i++)
  {
    el    = getElement( elements[i] ); 
    elDiv = document.createElement('div');
    elDiv.appendChild(el);
    
    td = document.createElement('td');
    td.setAttribute('style', 'vertical-align:bottom');
    label = elements[i].getElementsByTagName('label')[0].firstChild;
    td.appendChild( document.createTextNode( label ? label.data : " ") );
    td.appendChild( elDiv );
    row.appendChild(td);
    colCnt++;
    if (colCnt > colNum)
    {
      tbd.appendChild( row );
      row    = document.createElement('tr');
      colCnt = 1;
    }
  }
  tbd.appendChild( row );
  tb.appendChild( tbd );
  div.replaceChild(tb, tbOld);
}

function getElement( raw )
{
  type = raw.getElementsByTagName('type')[0].firstChild.data;;
  name = raw.getElementsByTagName('name')[0].firstChild.data;
  div2 = document.createElement('div');
  
  if ( 'text' == type )
  {
    el = document.createElement('input');
    el.setAttribute('type', 'text');
    el.setAttribute('name', name);
    el.setAttribute('size', 14);
    return el;
  }
  
  selID = raw.getElementsByTagName('selected')[0].firstChild.data;
  if ( 'select' == type )
  {
    el = document.createElement('select');
    el.setAttribute('name', name);
    el.setAttribute('id', name);
    dict = raw.getElementsByTagName('dict');
    for ( j=0; j<dict.length; j++)
    {
      value = dict[j].getElementsByTagName('value')[0].firstChild.data;
      l     = dict[j].getElementsByTagName('label')[0].firstChild; 
      opt   = document.createElement('option');
      opt.setAttribute('value', value);
      opt.appendChild( document.createTextNode(l ? l.data : '') );
      if ( selID>0 && selID == value )
      {
        opt.setAttribute('selected', 'on');
        if (typeof el.addEventListener != 'undefined')
          el.addEventListener('change', reloadSearchForm, false);
        else if (typeof el.attachEvent != 'undefined')
          el.attachEvent('onchange', reloadSearchForm);
      }

      el.appendChild(opt);
    }
    return el;
  }
  
  if ( 'opt-select' == type )
  {
    el = document.createElement('select');
    el.setAttribute('name', name);
    dict = raw.getElementsByTagName('dict');
    for ( j=0; j<dict.length; j++)
    {
      l = dict[j].getElementsByTagName('label')[0].firstChild; 
      
      subdict = dict[j].getElementsByTagName('subdict'); 
      if ( subdict.length == 0 )
      {
        value = dict[j].getElementsByTagName('value')[0].firstChild.data;
        opt = document.createElement('option');
        opt.setAttribute('value', value);
        opt.appendChild( document.createTextNode(l ? l.data : '') );
        el.appendChild(opt);
      }
      else
      {
        optgroup = document.createElement('optgroup');
        optgroup.setAttribute('label', l ? l.data : '');
        for ( k=0; k<subdict.length; k++ )
        {
          v = subdict[k].getElementsByTagName('value')[0].firstChild.data;
          l = subdict[k].getElementsByTagName('label')[0].firstChild;
          opt = document.createElement('option');
          opt.setAttribute('value', v);
          opt.appendChild( document.createTextNode(l ? l.data : '') );
          optgroup.appendChild(opt);
        }
        
        el.appendChild(optgroup);
      }
    }
  }
  else
  {
    el = document.createTextNode('');
  }
  
  return el;
}

function checkISPropFormAdmin()
{
  var type = document.getElementById('type').value; 
  for (i=1; i<=4; i++)
  {
    row = document.getElementById('row_values'+i);
    if ( row )
    {
      row.style.display = i==type ? '' : 'none';
    }
  }
}