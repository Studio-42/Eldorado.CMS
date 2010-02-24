<?php

class elTSModel extends elDataMapping
{
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
	var $features   = array();
	var $appToTitle = 0;
	var $_objName   = 'Model';

	/**
	 * Remove model and related data
	 *
	 * @return void
	 **/
	function delete() {
		return parent::delete(array($this->tbft2m => 'm_id'));
	}
	
	/**
	 * Create new model for item
	 *
	 * @param  string  $itemName 
	 * @return void
	 **/
	function _makeForm($itemName)
	{
		parent::_makeForm();

		$this->_form->add( new elCData2('item',      m('Item'),  $itemName) );
		$this->_form->add( new elText('code',        m('Code'),  $this->code) );
		$this->_form->add( new elText('name',        m('Name'),  $this->name) );
		$this->_form->add( new elText('price',       m('Price'), $this->price) );
		$this->_form->add( new elEditor('descrip',   m('Description'), $this->descrip, array('rows'=>50)) );
	}

	/**
	 * Data mapping
	 *
	 * @return array
	 **/
	function _initMapping()
	{
		return array(
			'id'        => 'ID',
			'i_id'      => 'iID',
			'code'      => 'code',
			'name'      => 'name',
			'descrip'   => 'descrip',
			'img'       => 'img',
			'price'     => 'price'
			);
	}
	
}

?>