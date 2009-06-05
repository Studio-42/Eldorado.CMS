<?php

class elRndPoll extends elModuleRenderer
{
	function rndPolls($polls, $active=true, $link=false)
	{
		$this->_setFile();
		
		if ( !empty($polls) )
		{
			$this->_te->assignBlockVars($active ? 'APOLL_TITLE' : 'CPOLL_TITLE');
			if ( $link )
			{ 
				$this->_te->assignBlockVars($active ? 'TO_CPOLL_LINK' : 'TO_APOLL_LINK');
			}
		}
		$pnum = 0;
		foreach ($polls as $poll)
		{
			$vars = array('id'=>$poll->ID, 'name'=>$poll->name, 'descrip'=>nl2br($poll->descrip), 'pnum'=>++$pnum);
			
			$this->_te->assignBlockVars('POLL', $vars);
			if ($this->_conf('displayDates') == EL_POLL_BEGIN_DATE)
			{
				$this->_te->assignBlockVars('POLL.POLL_BEGIN', array('beginDate'=>date(EL_DATE_FORMAT, $poll->beginTs)), 1);
			}
			elseif ($this->_conf('displayDates') == EL_POLL_END_DATE)
			{
				$this->_te->assignBlockVars('POLL.POLL_END', array('endDate'=>date(EL_DATE_FORMAT, $poll->endTs)), 1);
			}
			elseif ($this->_conf('displayDates') == EL_POLL_ALL_DATE)
			{
				$this->_te->assignBlockVars('POLL.POLL_BEGIN', array('beginDate'=>date(EL_DATE_FORMAT, $poll->beginTs)), 1);
				$this->_te->assignBlockVars('POLL.POLL_END',   array('endDate'=>date(EL_DATE_FORMAT, $poll->endTs)),     1);
				$this->_te->assignBlockVars('POLL.POLL_DATE_DELIM', null, 1);
			}
			if ( $this->_admin )
			{
				$var = array('id'=>$poll->ID);
				$this->_te->assignBlockVars('POLL.POLL_ACTIONS', array('id'=>$poll->ID), 1);
				if ( !$poll->isComplete )
				{
					$this->_te->assignBlockVars('POLL.POLL_ACTIONS.POLL_EDIT', array('id'=>$poll->ID), 2);
				}
			}
			if ( $poll->isComplete )
			{
				$this->_te->assignBlockVars('POLL.POLL_CLOSED', null, 1);
				
			}


			if ($poll->isComplete || $poll->voted)
			{
				$vnum = 0;
				foreach ($poll->variants as $var)
				{
					$var['vnum'] = ++$vnum;
					$this->_te->assignBlockVars('POLL.POLL_COMPLETE.CPOLL_VAR', $var, 2);
					if ($var['vote_num'])
					{
						$this->_te->assignBlockVars('POLL.POLL_COMPLETE.CPOLL_VAR.CPOLL_METER', array('prc'=>$var['prc']), 3);
					}
				}
			}
			else
			{
				$this->_te->assignBlockVars('POLL.POLL_ACTIVE', array('pollID'=>$poll->ID), 1);
				$vnum = 0;
				foreach ($poll->variants as $var)
				{
					$var['vnum'] = ++$vnum;
					$this->_te->assignBlockVars('POLL.POLL_ACTIVE.APOLL_VAR', $var, 2);
				}
			}
		}
		
	}
}
?>