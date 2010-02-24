<?php

class elRndEventSchedule extends elModuleRenderer
{
	var $rndParams = array();
	var $_tpls     = array('one'=>'event.html');

	function render( $events, $new=true )
	{
		$this->_setFile();
		$this->_te->assignVars('eventName', $this->rndParams['eventName']);
		if ( $this->rndParams['displayPlace'] )
		{
			$this->_te->assignBlockVars('EVENT_HEAD_PLACE', null);
		}
		if ( $new && $this->rndParams['displayLast'])
		{
			$this->_te->assignBlockVars( 'EVENT_LAST', null);
		}
		elseif ( !$new )
		{
			$this->_te->assignBlockVars( 'EVENT_COMING', null);
		}
		if ( empty($events) )
		{
			return;
		}
		$dateFormat = $this->rndParams['displayTime'] 
			? EL_DATETIME_FORMAT 
			: EL_DATE_FORMAT;
		$i = 0;
		foreach ( $events as $one )
		{
			$cssClass = $i++%2 ? 'strip-ev' : 'strip-odd';
			$data = array('ID'     => $one->getUniqAttr(),
						'name'     => $one->getAttr('name'),
						'begin'    => date($dateFormat, $one->getAttr('begin_ts')),
						'end'      => date($dateFormat, $one->getAttr('end_ts')),
						'cssClass' => $cssClass
						);
			$this->_te->assignBlockVars('EVENT', $data);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('EVENT.ADMIN', array('ID' => $data['ID']), 1);
			}
			if ( false != ($ann = $one->getAttr('announce')) )
			{
				$this->_te->assignBlockVars('EVENT.EV_ANNOUNCE', array('announce' => $ann), 1);
			}
			if ( $one->hasContent() )
			{
				$this->_te->assignBlockVars('EVENT.EV_DETAILS', array('ID' => $one->getUniqAttr()), 1);
			}
			if ( $this->rndParams['displayPlace'] )
			{
				$this->_te->assignBlockVars('EVENT.EV_PLACE', array('place' => $one->getAttr('place'), 'cssClass' => $cssClass), 1);
			}
		}
		
	}

	function rndOne( $event )
	{
		$this->_setFile('one');
		$dateFormat = $this->rndParams['displayTime'] 
			? EL_DATETIME_FORMAT 
			: EL_DATE_FORMAT;
		$data = array(
					'name'    => $event->getAttr('name'),
					'begin'   => date($dateFormat, $event->getAttr('begin_ts')),
					'end'     => date($dateFormat, $event->getAttr('end_ts')),
					'content' => $event->getAttr('content')
					);
		$this->_te->assignVars( $data );
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('EVENT_ADMIN', array('ID' => $event->getUniqAttr()));
		}
		if ( $this->rndParams['displayPlace'] )
		{
			$this->_te->assignBlockVars('EV_PLACE', array('place'=>$event->getAttr('place')));
		}
	}
}
?>