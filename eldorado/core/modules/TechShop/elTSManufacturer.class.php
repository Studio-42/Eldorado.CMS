<?php

class elTSManufacturer extends elMemberAttribute
{
	var $tbi         = '';
	var $ID          = 0;
	var $name        = '';
	var $country     = '';
	var $announce    = '';
	var $descrip     = '';
	var $imgLogo     = '';
	var $imgMiniLogo = '';
	var $siteURL     = '';
	var $_objName    = 'Manufacturer';

	function isEmpty()
	{
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT COUNT(*) AS cnt FROM '.$this->tbi.' WHERE mnf_id=\''.$this->ID.'\'');
		$r = $db->nextRecord();
		return !$r['cnt'];
	}

	function makeForm()
	{
		parent::makeForm();

		$this->form->add( new elText('name',       m('Name'),     $this->getAttr('name')) );
		$this->form->add( new elText('country',    m('Country'),  $this->getAttr('country')) );
		$this->form->add( new elEditor('announce', m('Announce'), $this->getAttr('announce'), array('rows'=>24)));
		$this->form->add( new elEditor('descrip',  m('Description'), $this->getAttr('descrip')) );
		$this->form->add( new elText('url',        m('Site URL'), $this->getAttr('url')) );

		$this->form->setRequired('name');
		//$this->form->setElementRule('url', 'http_url', false);
	}

	function _initMapping()
	{
		$map = array(
			'id'       => 'ID',
			'name'     => 'name',
			'country'  => 'country',
			'announce' => 'announce',
			'descrip'  => 'descrip',
			'url'      => 'siteURL'
			);
		return $map;
	}

}

?>