<?php

class elModuleEventSchedule extends elModule
{
	var $_mMap = array(
										'details' => array('m'=>'eventDetails'),
										'last'    => array('m'=>'lastEvents', 'l2'=>'Last events')
										);

	var $_conf = array( 'displayTime'  => 0,
											'displayLast'  => 1,
											'displayPlace' => 0,
											'eventName'    => '');


/**
 * Display current events list
 *
 */
	function defaultMethod()
	{
		$event = & $this->_getEvent();
		$this->_initRenderer();
		$today = mktime(0,0,0,date('m'), date('d'), date('Y'));
		$this->_rnd->render( $event->getCollection(null, 'begin_ts', 0, 0, 'end_ts>='.$today) );
	}

	/**
	 * Display past events list
	 *
	 */
	function lastEvents()
	{
		$event = & $this->_getEvent();
		$this->_initRenderer();
		$today = mktime(0,0,0,date('m'), date('d'), date('Y'));
		$this->_rnd->render( $event->getCollection(null, 'begin_ts', 0, 0, 'end_ts<'.$today) , false );
	}

	/**
	 * Display one event detailed description
	 *
	 */
	function eventDetails()
	{
		$event = & $this->_getEvent();
		if ( !$event->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',	array($event->getObjName(), $event->ID), EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->rndOne( $event );
		$mt = &elSingleton::getObj('elMetaTagsCollection'); 
		$mt->init($this->pageID, 0, $event->ID);
	}

	/**
	 * return elShEvent object
	 *
	 * @return object
	 */
	function &_getEvent()
	{
		$event = & elSingleton::getObj('elShEvent');
		$event->tb = 'el_event_'.$this->pageID;
		$event->setUniqAttr( (int)$this->_arg() );
		$event->setObjName($this->_getEventName());
		return $event;
	}

	/**
	 * Return user defined event name or translated default name "Event"
	 *
	 * @return string
	 */
	function _getEventName()
	{
		$name = $this->_conf('eventName');
		return $name ? $name : m('Event');
	}

	/**
	 * Disable past events display ability according module config 
	 *
	 */
	function _onInit()
	{
		if ( !$this->_conf('displayLast'))
		{
			unset($this->_mMap['last']);
		}
	}

	/**
	 * Set render params
	 *
	 */
	function _initRenderer()
	{
		parent::_initRenderer();
		$this->_rnd->rndParams = array('displayTime'  => $this->_conf('displayTime'),
																	 'displayLast'  => $this->_conf('displayLast'),
																	 'displayPlace' => $this->_conf('displayPlace'),
																	 'eventName'    => $this->_getEventName()
																		);
	}

}

?>