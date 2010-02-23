<?php

class elTSManufacturer extends elDataMapping
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

	/**
	 * Return true if this manufacturers has not items
	 *
	 * @return boolen
	 **/
	function isEmpty()
	{
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT COUNT(*) AS cnt FROM '.$this->tbi.' WHERE mnf_id=\''.$this->ID.'\'');
		$r = $db->nextRecord();
		return !$r['cnt'];
	}

	/**
	 * Create form for editing object
	 *
	 * @param  array  $params
	 * @return void
	 **/
	function _makeForm($params=null)
	{
		parent::_makeForm();

		$this->_form->add( new elText('name',       m('Name'),        $this->name) );
		$this->_form->add( new elText('country',    m('Country'),     $this->country) );
		$this->_form->add( new elText('url',        m('Site URL'),    $this->siteURL) );
		$this->_form->add( new elEditor('announce', m('Announce'),    $this->announce, array('rows'=>24)));
		$this->_form->add( new elEditor('descrip',  m('Description'), $this->descrip) );
		

		$this->_form->setRequired('name');
		$this->_form->setElementRule('url', 'http_url', false);
	}

	/**
	 * Data mapping
	 *
	 * @return array
	 **/
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