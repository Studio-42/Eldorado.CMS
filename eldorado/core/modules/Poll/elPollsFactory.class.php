<?php

class elPollsFactory
{
    var $pageID       = 0;
    var $_db          = null;
    var $_tb          = '';
    var $_tbVars      = '';
    var $_tbVote      = '';
    var $_maxPollVars = 10;
    var $_polls       = array();
    
    function elPollsFactory($pageID)
    {
        $this->pageID       = $pageID;
        $this->_db          = & elSingleton::getObj('elDb');
        $this->_tb          = 'el_poll_'.$this->pageID;
		$this->_tbVars      = 'el_poll_'.$this->pageID.'_vars';
		$this->_tbVote      = 'el_poll_'.$this->pageID.'_vote';
        $conf               = & elSingleton::getObj('elXmlConf');
        $this->_maxPollVars = (int)$conf->get('maxPollVarians', $this->pageID);
        $lastCheck = (int)$conf->get('lastCheck', $this->pageID);
        if ( !$lastCheck || time() - $lastCheck >= 86400 )
		{
			list($d, $m, $y) = explode('.', date('d.m.Y', time()));
			$lastCheck       = mktime(0,0,0,$m,$d,$y);
			$sql             = 'SELECT id FROM '.$this->_tb.' WHERE is_complete<>\'1\' AND end_ts<='.$lastCheck;
			$IDs             = $this->_db->queryToArray($sql, 'id', 'id');
			foreach ( $IDs as $ID )
			{
				$poll = &$this->create($ID);
				$poll->complete();
			}
			$conf->set('lastCheck', $lastCheck, $this->pageID);
			$conf->save();
		}
    }
    
    function &create($ID=0)
    {
        $poll = & elSingleton::getObj('elPoll', null, $this->_tb, $this->_tbVars, $this->_tbVote,  (int)$ID, $this->_maxPollVars);
		$poll->fetch();
		return $poll;
    }
    
    function getActive($all=false, $num=0)
    {
        return $this->_getCollection('is_complete!=\'1\' '.($all ? '' : ' AND begin_ts<'.time()), $num);
    }
    
    function getCompleted()
    {
        return $this->_getCollection('is_complete=\'1\'');
    }
    
    function countVotes( $admin=false )
    {
        $this->_db->query('SELECT id FROM '.$this->_tb.' WHERE is_complete<>\'1\''.(!$admin ? ' AND begin_ts<'.time() : ''));
        $aNum = $this->_db->numRows();
        $this->_db->query('SELECT id FROM '.$this->_tb.' WHERE is_complete=\'1\'');
        return array($aNum, $this->_db->numRows());
    }
    
    function _getCollection($where, $num=0)
    {
        $poll  = &$this->create(); 
        $polls = $poll->getCollection(null, 'begin_ts, end_ts, id', 0, $num, $where );
    
		if ($polls)
		{
			$sql = 'SELECT v.id, v.poll_id, v.name, v.vote_num, v.prc, IF(vt.sid IS NOT NULL, 1, 0) AS vote '
					.'FROM '.$this->_tbVars.' AS v LEFT JOIN  '.$this->_tbVote.' AS vt ON (v.id=vt.var_id AND vt.sid=\''.session_id().'\') '
					.'WHERE v.poll_id IN ('.implode(',', array_keys($polls)).') ORDER BY v.id';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord())
			{
				$polls[$r['poll_id']]->variants[$r['id']] = $r; //echo $r['vote'].'<br>';
				if ($r['vote'])
				{
					$polls[$r['poll_id']]->voted = true;
				}
			}
		}
        return $polls;
    }
}

?>