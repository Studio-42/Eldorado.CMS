<?php

class elShEvent extends elMemberAttribute
{
	var $ID       = 0;
	var $name     = '';
	var $announce = '';
	var $content  = '';
	var $place    = '';
	var $begin    = 0;
	var $end      = 0;
	var $expParam = '';
	var $_objName = 'Event';

	function setObjName($name)
	{
		$this->_objName = $name;
	}
	
	function hasContent()
	{
		return (bool)$this->content;
	}

	function makeForm( $params )
	{
		parent::makeForm(); 
		$this->form->add( new elText('name', m('Name'),  $this->name) );
		$this->form->add( new elDateSelector('begin_ts', m('Begin'), $this->begin, null, 1, 2, $params['displayTime']) );
		$this->form->add( new elDateSelector('end_ts',   m('End'),   $this->end,   null, 1, 2, $params['displayTime']) );
		if ($params['displayPlace'])
		{
			$this->form->add( new elText('place', m('Place'), $this->place) );
		}
		$this->form->add( new elEditor('announce', m('Announce'),    $this->announce, array('rows'=>20)) );
		$this->form->add( new elEditor('content',  m('Description'), $this->content) );
		$this->form->add( new elText(  'export_param',    m('Export parameter'),    $this->expParam,    array('style'=>'width:100%')) );
		$this->form->setRequired('name');
		$this->form->setElementRule('begin_ts', 'noempty', false, null, m('Invalid date'));
	}
	
	
	function _initMapping()
	{
		return array('id'        => 'ID',
									'name'     => 'name',
									'announce' => 'announce',
									'content'  => 'content',
									'place'    => 'place',
									'begin_ts' => 'begin',
									'end_ts'   => 'end',
									'export_param' => 'expParam'
									);
	}
	
}

?>