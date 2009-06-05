<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';

class elGCatalogItem extends elCatalogItem
{
	var $i2cTb     = '';
  var $ID        = 0;
  var $name      = '';
  var $announce  = '';
  var $content   = '';
  var $crTime    = 0;
  var $parents   = array();
  
	var $price     = 0;
	var $_objName  = 'Item';
	var $_sortVars = array('name', 'price, name', 'crtime DESC, name');

	//**************************************************************************************//
	// *******************************  PUBLIC METHODS  *********************************** //
	//**************************************************************************************//

	/**
   * Create edit item form object
   */
	function makeForm( $parents )
	{
		parent::makeForm( $parents );
		//$this->form->add( new elMultiSelectList('pids', m('Parent categories'), $this->_getParents(), $parents) );
		//$this->form->add( new elText('name', m('Name'), $this->name, array('style'=>'width:100%;')) );
		$this->form->add( new elText('price', m('Price'), $this->price, array('width'=>'20')) );
		$this->form->add( new elEditor('announce', m('Announce'), $this->announce, array('height'=>'350')) );
		$this->form->add( new elEditor('content', m('Content'), $this->content) );
		$this->form->add( new elDateSelector('crtime', m('Publish date'), $this->crTime) );
		$this->form->setRequired('pids[]');
		$this->form->setRequired('name');
	}


	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

	/**
   * Create attributes to members map
   * @ returns array
   */
	function _initMapping()
	{
		return array( 'id'       => 'ID',
									'name'     => 'name',
									'announce' => 'announce',
									'content'  => 'content',
									'price'    => 'price',
									'crtime'   => 'crTime'
									);
	}

}

?>