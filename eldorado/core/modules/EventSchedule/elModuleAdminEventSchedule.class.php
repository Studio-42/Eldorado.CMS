<?php

class elModuleAdminEventSchedule extends elModuleEventSchedule
{
	var $_mMapAdmin = array(
		'edit' => array('m'=>'editEvent', 'ico'=>'icoNew', 'l'=>'New event', 'g' => 'Actions'),
		'rm'   => array('m'=>'rmEvent') );

	/**
	 * Create or edit event
	 *
	 */
	function editEvent()
	{
		$event = &$this->_getEvent();
		$event->fetch();
		$params = array('displayTime'  => $this->_conf('displayTime'),
										'displayPlace' => $this->_conf('displayPlace'));
		if ( !$event->editAndSave($params) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $event->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation( EL_URL );
		}
	}

	/**
	 * Remove event if exists
	 *
	 */
	function rmEvent()
	{
		$event = &$this->_getEvent();
		if ( !$event->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array($event->getObjName(), $event->ID), EL_URL );
		}
		$event->delete();
		elMsgBox::put( vsprintf(m('Object %s "%s" deleted'), array($event->getObjName(), $event->getAttr('name'))) );
		elLocation( EL_URL );
	}

	/**
	 * Create configuration form
	 *
	 * @return object
	 */
	function &_makeConfForm()
	{
		$form = & parent::_makeConfForm();
		$form->add( new elSelect('displayTime', m('Display event date as'), $this->_conf('displayTime'),
															array(m('Only date'), m('Date and time'))) );
		$form->add( new elSelect('displayPlace', m('Display event place'), $this->_conf('displayPlace'), $GLOBALS['yn']) );							$form->add( new elSelect('displayLast', m('Display past events'), $this->_conf('displayLast'), $GLOBALS['yn']) );								$form->add( new elCData('c1', m('If You want to use default event name, left the following field empty')) );
		$form->add( new elText('eventName', m('Event name'), $this->_conf('eventName')) );

		return $form;
	}

}
?>