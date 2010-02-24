<?php

class elLicense extends elMemberAttribute
{
	var $ID     = 0;
	var $key    = '';
	var $url    = '';
	var $expire = 0;

	var $_objName = 'License';
	var $tb       = 'el_license';

	function getUrl( $key )
	{
	  $db = & elSingleton::getObj('elDb');
    $sql = 'SELECT url FROM '.$this->tb.' WHERE lkey=\''.mysql_escape_string($key).'\' AND expire>UNIX_TIMESTAMP(NOW())';

    $db->query( $sql );
    if ( 1 <> $db->numRows() )
    {
      return false;
    }
    $r = $db->nextRecord();
    return $r['url'];
	}

	function makeForm()
	{
		parent::makeForm();
		$this->form->add(new elText('lkey', m('License key'), $this->key));
		$this->form->add(new elText('url',  m('Site URL'),    $this->url));
		$this->form->add(new elDateSelector('expire', m('Expire date'), $this->expire));
		$this->form->setRequired('lkey');
		$this->form->setElementRule('url',  'http_url');
	}

	function _initMapping()
 	{
    	return array(
			'id'     => 'ID',
			'lkey'    => 'key',
			'url'    => 'url',
			'expire' => 'expire'
		);
	}
}

?>