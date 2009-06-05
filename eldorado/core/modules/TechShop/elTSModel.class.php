<?php

class elTSModel extends elMemberAttribute
{
	var $tb         = '';
	var $ftg        = '';
	var $ft         = '';
	var $ft2m       = '';
	var $ID         = 0;
	var $iID        = 0;
	var $code       = '';
	var $name       = '';
	var $descrip    = '';
	var $price      = 0;
	var $img        = '';
	var $appToTitle = 0;
	var $_objName   = 'Model';

	function makeForm($itemName)
	{
		parent::makeForm();

		$this->form->add( new elCData2('item',      m('Item'),  $itemName) );
		$this->form->add( new elText('code',        m('Code'),  $this->getAttr('code')) );
		$this->form->add( new elText('name',        m('Name'),  $this->getAttr('name')) );
		$this->form->add( new elText('price',       m('Price'), $this->getAttr('price')) );
		$this->form->add( new elEditor('descrip',   m('Description'), $this->getAttr('descrip'), array('rows'=>50)) );
	}


	function _initMapping()
	{
		$map = array(
			'id'        => 'ID',
			'i_id'      => 'iID',
			'code'      => 'code',
			'name'      => 'name',
			'descrip'   => 'descrip',
			'img'       => 'img',
			'price'     => 'price'
			);
		return $map;
	}
	
	
	
}

?>