var cnt = 0;

function SetUrl(URL)
{
   updatePreview(URL);
}

function updatePreview(URL)
{
   var h    = document.getElementById('imgURL');
   var hs   = document.getElementById('imgURLSave');
   var prev = document.getElementById('imgPrew');
   if (URL == 0)
   {
      h.value = hs.value;
      var i = document.createElement('img');
      i.src=h.value;
   }
   else if (URL == 1)
   {
     h.value = '';
     i = document.createTextNode('');
   }
   else
   {
      h.value = hs.value = URL;
      var i = document.createElement('img');
      i.src=URL;
   }
   prev.replaceChild(i, prev.firstChild);
}

function checkFtList(gid)
{
	row = document.getElementById('fts_'+gid);
	sw  = document.getElementById('switch_'+gid);
	if (row)
	{
		if ( row.style.display == "" )
		{
			row.style.display       = "none";
			sw.firstChild.nodeValue = '[+]';
		}
		else
		{
			row.style.display       = "";
			sw.firstChild.nodeValue = '[-]';
		}
	}
	return false;
}

function openModelsList(id)
{
	var mList  = document.getElementById( 'mList_'+id );
	var swLink = document.getElementById( 'switchLink_'+id );
	var swImg  = document.getElementById( 'switchImg_'+id );


	if (!mList.style.display)
	{
		mList.style.display    = 'none';
		swLink.replaceChild(document.createTextNode(switchOpenLabel), swLink.firstChild);
		if (swImg)
		{
		  swImg.className = switchOpenClass;
		}
	}
	else
	{
	  mList.style.display    = '';
	  swLink.replaceChild(document.createTextNode(switchCloseLabel), swLink.firstChild);
	  if (swImg)
		{
		  swImg.className = switchCloseClass;
		}
	}
	return false;
}

function onSelectItem(isChecked, itemID)
{
   cnt += isChecked == true ? 1 : -1;
   var button1 = document.getElementById( 'compareButton' );
   var button2 = document.getElementById( 'compareButton_'+itemID );
   if (button1)
   {
    button1.style.display = cnt>0 ? '' : 'none';
   }
   if (button2)
   {
    button2.style.display = cnt>0 ? '' : 'none';
   }
}

function checkTechShopConfForm(v)
{
  document.getElementById('row_pricePrec').style.display = v>0 ? '' : 'none';
  document.getElementById('row_priceDownl').style.display = v>0 ? '' : 'none';
}

function checkTechShopMNavForm()
{
  sw    = document.getElementById('pos');
  pages = document.getElementById('row_pids[]');
  view  = document.getElementById('row_view');
  title = document.getElementById('row_title');
  pages.style.display = view.style.display = title.style.display = sw.value!=0 ? '' : 'none';
}