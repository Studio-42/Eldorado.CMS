$().ready( function() {
	$('.crosslinks-group .el-collapsed').click( function(e) {
		e.preventDefault();
		$(this).toggleClass('el-expanded');
		$(this).parent().siblings('ul').slideToggle('slow');
	});
});

function popUp(url, w, h)
{
	window.open(url, null, 'top=50,left=50,scrollbars=yes,resizable=yes,width='+(w+40)+',height='+(h+60));
	return false;
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

