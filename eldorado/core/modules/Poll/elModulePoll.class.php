<?php

define('EL_POLL_NO_DATE',    0);
define('EL_POLL_BEGIN_DATE', 1);
define('EL_POLL_END_DATE',   2);
define('EL_POLL_ALL_DATE',   3);

class elModulePoll extends elModule
{
	var $_tb     = '';
	var $_tbVars = '';
	var $_tbVote = '';
	var $_mMap   = array( 'vote' => array('m'=>'vote') );
	var $_conf   = array(
						 'maxPollVarians' => 10,
						 'displayDates'   => EL_POLL_NO_DATE
						 );

	/**
	 * На первой стр показывает открытые голосования
	 * и ссылку на стр завершенных голосований.
	 * Если нет открытых голосований - завершенные.
	 * На стр завершенных голосований показывает их,
	 * если есть активные, и ссылку на первую стр.
	 * Если нет активных голосований,
	 * перебрасывет на первую стр. 
	 */
	function defaultMethod()
	{

		$this->_initRenderer();
		list($aNum, $cNum) = $this->_factory->countVotes($this->_aMode > EL_READ);

		//echo "$aNum $cNum";
		if ( 'completed' != $this->_arg() )
		{
			if ( $aNum )
			{ // показать активные голосования
				$this->_rnd->rndPolls( $this->_factory->getActive($this->_aMode > EL_READ), true, $cNum);  
			}
			else
			{// активных нет - показать завершенные
				$this->_rnd->rndPolls( $this->_factory->getCompleted(), false ); 
			}
		}
		else
		{
			if (!$cNum)
			{
				elThrow(E_USER_WARNING, 'There are no one completed polls was found', null, EL_URL);
			}
			elseif ( !$aNum )
			{
				elLocation(EL_URL);
			}
			$this->_rnd->rndPolls( $this->_factory->getCompleted(), false, $aNum ); 
		}
	}

	/**
	 * Голосовать
	 */	
	function vote()
	{
		$poll = &$this->_factory->create($this->_arg() );
		$vID  = (int)$_POST['choice']; 
		if ( !$poll->ID )
		{
			elThrow( E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array(m('Poll'), $this->_arg()), EL_URL);
		}
		$poll->vote( $vID );
		elMsgBox::put( m('Thank you, Your vote considered') );
		elLocation( EL_URL );
	}
	
	// Private
	
	
	
	function _onInit()
	{
		$this->_tb     = 'el_poll_'.$this->pageID;
		$this->_tbVars = 'el_poll_'.$this->pageID.'_vars';
		$this->_tbVote = 'el_poll_'.$this->pageID.'_vote';
		$this->_factory= &elSingleton::getObj('elPollsFactory', $this->pageID);
	}
}

?>