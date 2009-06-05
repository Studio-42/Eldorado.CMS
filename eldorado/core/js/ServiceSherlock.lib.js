var req;
var pNode;
var content = new Array;
var contentState = 1;
locked = false

$().ready( function() {
	$('#search-ico').click( function(event) {
		var f = $('#search-form-top')
		event.stopPropagation()
		if (f.is(':hidden')) {
			f.show('slow');
			f.find('#search-str').val('').focus();
			f.click( function(e) { e.stopPropagation()} );
			$('body').click( function() { f.hide('slow'); $(this).unbind('click') } );
		} else {
			f.hide('slow');
		}
		return false;
	} );
	$('#search-str').keyup( _search );
} );

function _search(event) {
	if ( !locked && $(this).val().length>2) {
		$('#search-form-top').addClass('searchProgress')
		var url  = elBaseURL + '_xml_/__search__/' ; //alert(url)
		var data = {_form_ : 'search', sstr: $(this).val()}
		$.get(url, data, _searchResult)
		locked = true
	}
}

function _searchResult(data, status) {
	var c = $('#el-content')
	var r = $('#search-result')
	if ( !r.length) {
		var r = $('<div id="search-result" class="clearfix rounded-5"></div>'); 
	}
	r.empty()
	r.append( $('<div class="close"></div>').click( function() { r.hide('slow'); r.remove(); c.fadeIn('slow'); } ) )
	r.append( $('<h4></h4>').text('Результаты поиска "'+$(data).find('searchString').text()+'"')  )
	
	var records = $(data).find('record');
	if ( !records.length ) {
		r.append( 'Ничего не найдено.' )
	} else {
		var ul = $('<ul></ul>');
		records.each( function() {
			ul.append( $('<li><a href="'+$(this).children('url').text()+'">'+$(this).children('name').text()+'</a></li>') )
		} );
		r.append(ul);
	}
	c.fadeOut('slow')
	r.insertBefore(c);  r.hide();
	r.show('slow')
	locked = false
	$('#search-form-top').removeClass('searchProgress')
}

function doSearch( str, result )
{
	if ( !pNode )
	{
		if ( document.getElementById('searchResults') )
		{
			pNode = document.getElementById('searchResults');
		}
		else
		{
			pNode = document.getElementById('content');
		}
	}

	if ( content.length == 0 )
	{
		saveContent(); //alert(content);
	}

	if ( !result )
	{
		if ( str.length < 3 )
		{
			restoreContent();
		}
		else
		{
			cleanContent();
			rndSearchInProgress(str);
			url = elBaseURL + '_xml_/__search__/?_form_=search&sstr=' + encodeURIComponent(str);
  		loadXMLDoc(url);
		}
	}
	else
	{
		rndSearchResults(result);
	}
}


function saveContent()
{
	for ( var i=0; i<pNode.childNodes.length; i++ )
	{
		content[i] = pNode.childNodes[i].cloneNode(true);
	}
}

function cleanContent()
{
	if ( contentState != 0 )
	{
		while (pNode.childNodes.length>0)
		{
			pNode.removeChild(pNode.childNodes[0]);
		}
		contentState = 0;
	}
}

function restoreContent()
{
	if ( contentState != 1 )
	{
		cleanContent();
		for ( var i=0; i<content.length; i++ )
		{
			pNode.appendChild(content[i].cloneNode(true));
		}
		contentState = 1;
	}
}


function rndSearchInProgress(str)
{
	cleanContent();
	s = document.createElement('div');
	s.setAttribute('id', 'searchProgress');
	s.setAttribute('class', 'searchProgress');
	s.appendChild( document.createTextNode( searchProgress.replace("%s", str) ) );
	s.appendChild( document.createElement('br'));
	s.appendChild( document.createElement('br'));
	i = document.createElement('img');
	i.setAttribute('src', elBaseURL+'style/icons/lightbox/loading.gif');
	s.appendChild(i);
	pNode.appendChild(s);
	contentState = 2;
}


function rndSearchResults(result)
{
	cleanContent();
	contentState = 3;

	str    = result.getElementsByTagName('searchString')[0].firstChild.data;
	header = document.createElement('div');
	header.setAttribute('class', 'searchResultHeader');
	header.appendChild( document.createTextNode( resTitle + " \"" + str + "\"" ) );
	pNode.appendChild(header);

	records = result.getElementsByTagName('record');

	if (!records.length)
	{
		pNode.appendChild( document.createTextNode(noResMsg) );
		return;
	}

	list = document.createElement('ul');

	for (i=0; i<records.length; i++)
	{
		title = records[i].getElementsByTagName('name')[0].firstChild.data;
		url   = records[i].getElementsByTagName('url')[0].firstChild.data;

		li = document.createElement('li');
		li.setAttribute('class', 'searchResultList');
		link = document.createElement('a');
		link.setAttribute('href', url);
		link.appendChild(document.createTextNode(title));
		li.appendChild(link);
		list.appendChild(li);
	}
	d = document.createElement('div');
	d.appendChild(list);
	pNode.appendChild(d);
	//pNode.appendChild(list);
}
