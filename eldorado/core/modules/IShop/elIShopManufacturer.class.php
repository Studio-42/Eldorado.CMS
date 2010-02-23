<?php

class elIShopManufacturer extends elDataMapping
{
  var $ID       = 0;
  var $name     = '';
  var $country  = '';
  var $content  = '';
  var $_objName = 'Manufacturer';
  var $tms      = array();

	function collection($obj=false, $assoc=false, $clause=null, $sort=null, $offset=0, $limit=0, $onlyFields=null) {
		$coll = parent::collection(true, true, $clause, 'name', $offset, $limit, $onlyFields);
		if (!empty($coll)) {
			$factory = & elSingleton::getObj('elIShopFactory');
		    $tm = $factory->getTm(0);
			$tms = $tm->collection(true, true, null, 'name');
			foreach ( $tms as $one )
			{
				if (!empty($coll[$one->mnfID]))
				{
					$coll[$one->mnfID]->tms[] = $one;
				}
			}
		}
		return $coll;
	}


  function _makeForm()
  {
    parent::_makeForm();

    $this->_form->add( new elText('name',      m('Name'),        $this->name) );
    $this->_form->add( new elText('country',   m('Country'),     $this->country) );
    $this->_form->add( new elEditor('content', m('Description'), $this->content) );
    $this->_form->setRequired('name');
  }

  function _initMapping()
  {
    return array('id' => 'ID', 'name' => 'name', 'country' => 'country', 'content' => 'content');
  }

}
?>