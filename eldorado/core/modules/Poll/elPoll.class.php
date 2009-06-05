<?php

class elPoll extends elMemberAttribute
{
	var $ID         = 0;
	var $name       = '';
	var $descrip    = '';
	var $beginTs    = 0;
	var $endTs      = 0;
	var $isComplete = 0;
	var $variants   = array();
	var $votes      = 0;
	var $voted      = false;
	var $maxVars    = 10;  

	var $tbVars     = '';
	var $tbVote     = '';


	var $_objName = 'Poll';

	function elPoll( $attrs=null, $tb=null, $tbVars=null, $tbVote=null, $uniq=null, $maxVars=10 )
	{
		if ( $uniq )
		{
			$this->setUniqAttr($uniq); 
		}
		if ( is_array($attrs) )
		{
			$this->setAttrs($attrs);
		}
		$this->setTb($tb);
		$this->tbVars  = $tbVars;
		$this->tbVote  = $tbVote;
		$this->maxVars = $maxVars>2 ? (int)$maxVars : 10;
		$this->beginTs = time();
		$this->endTs   = time()+60*60*24*90;
	}

	function fetch()
	{
		if (parent::fetch())
		{
			$db  = & elSingleton::getObj('elDb');
			$sql = 'SELECT id, name, vote_num, prc FROM '.$this->tbVars.' WHERE poll_id=\''.$this->ID.'\' ORDER BY id';
			$this->variants = $db->queryToArray($sql, 'id'); //elPrintR($this->variants);
			if ( !$this->isComplete )
			{
				$sql = 'SELECT vote.sid FROM '.$this->tbVars.' AS var, '.$this->tbVote.' AS vote '
					  .'WHERE var.poll_id='.$this->ID.' AND vote.var_id=var.id AND vote.sid=\''.session_id().'\'';
				$db->query($sql);
				if ( $db->numRows() )
				{
					$this->voted = 1;
				}
			}
			return true;
		}
		return false;
	}

	function isVariantExists($vID)
	{
		return !empty($this->variants[$vID]);
	}

	
	function makeForm()
	{
		parent::makeForm();
		$this->form->add(new elText('name', m('Name'), $this->getAttr('name')));
		$this->form->add(new elTextArea('descrip', m('Description'), $this->getAttr('descrip')));
		
		reset($this->variants);
		$max = max($this->maxVars, sizeof($this->variants));
		
		for ($i=0; $i<$max; $i++)
		{
			list($id, $variant) = each($this->variants); //elPrintR($variant);
			$this->form->add( new elText('var['.$i.']', m('Answer').' '.($i+1), $id ? $variant['name'] : ''));
		}
		
		if ( !$this->ID || $this->beginTs > time() )
		{
			$this->form->add(new elDateSelector('begin_ts',	m('Start date'), $this->getAttr('begin_ts'), null, 0, 1));	
		}
		$this->form->add(new elDateSelector('end_ts',   m('Stop date'),  $this->getAttr('end_ts'),   null, 0, 2));
		$this->form->setRequired('name');
	}

	function save()
	{
		$data    = $this->form->getValue(); //elPrintR($data);
		$name    = mysql_real_escape_string($data['name']);
		$descrip = mysql_real_escape_string($data['descrip']);
		$beginTS = !$this->ID || $this->beginTs > time() ? intval($data['begin_ts']) : $this->beginTs;
		$endTS   = intval($data['end_ts']);
		$db      = & elSingleton::getObj('elDb');
		
		$sql = !$this->ID
			? 'INSERT INTO '.$this->tb.' (name, descrip, begin_ts, end_ts) VALUES ('
				.'\''.$name.'\', \''.$descrip.'\', '.$beginTS.', '.$endTS.')'
			: 'UPDATE '.$this->tb.' SET name=\''.$name.'\', descrip=\''.$descrip.'\', '
				.'begin_ts=\''.$beginTS.'\', end_ts=\''.$endTS.'\' '
				.'WHERE id=\''.$this->ID.'\'';
		//echo $sql;
				
		if (!$db->query( $sql ))
		{
			return elThrow(E_USER_WARNING, 'Can not write to DB');
		}
		if ( !$this->ID )
		{
			$this->ID = $db->insertID();
		}
		
		reset($this->variants);
		for ($i=0; $i<sizeof($data['var']); $i++)
		{
			list ($id, $oldVar) = each($this->variants);
			$newVar = mysql_real_escape_string($data['var'][$i]);
			if ( !empty($id) )
			{
				if ( !empty($newVar) )
				{
					$db->query('UPDATE '.$this->tbVars.' SET name=\''.$newVar.'\' WHERE id=\''.$id.'\'');
				}
				else
				{
					$db->query('DELETE FROM '.$this->tbVars.' WHERE id=\''.$id.'\'');
					$db->query('DELETE FROM '.$this->tbVote.' WHERE var_id=\''.$id.'\'');
					$db->optimizeTable($this->tbVars);
					$db->optimizeTable($this->tbVote);
				}
			}
			elseif ( !empty($newVar) )
			{
				$db->query('INSERT INTO '.$this->tbVars.' (poll_id, name) VALUES (\''.$this->ID.'\', \''.$newVar.'\')');
			}
		}
		return true;
	}

	function complete()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query('UPDATE '.$this->tb.' SET is_complete=\'1\' WHERE id='.$this->ID);
		$db->query('DELETE vt FROM '.$this->tbVars.' AS vr, '.$this->tbVote.' AS vt WHERE vr.poll_id=\''.$this->ID.'\' AND vt.var_id=vr.id');
		$db->optimizeTable( $this->tbVote);
	}

	function vote($varID)
	{
		if ( !$varID || empty($this->variants[$varID]) )
		{
			elThrow(E_USER_WARNING, 'Invalid poll choice', null, EL_URL);
		}
		if ( $this->isComplete  )
		{
			elThrow(E_USER_WARNING, 'Poll was completed, no more voting possible', null, EL_URL );
		}
		if ( $this->voted )
		{
			elThrow(E_USER_WARNING, 'You are already voted on this poll!', null, EL_URL);
		}
		$db = & elSingleton::getObj('elDb');
		$db->query('REPLACE INTO '.$this->tbVote.' (var_id, sid) VALUES (\''.$varID.'\', \''.session_id().'\')');
		$this->variants[$varID]['vote_num']++;
		$total = 0;
		foreach ($this->variants as $ID=>$variant)
		{
			$total += $this->variants[$ID]['vote_num'];
		}
		
		$db->query('LOCK TABLES '.$this->tbVars.' WRITE');
		foreach ($this->variants as $ID=>$variant)
		{
			$this->variants[$ID]['prc'] = 0 == $this->variants[$ID]['vote_num']
				? 0
				: round($this->variants[$ID]['vote_num']*100/$total);
			$sql = 'UPDATE '.$this->tbVars.' SET vote_num=\''.$this->variants[$ID]['vote_num'].'\', '
					.'prc=\''.$this->variants[$ID]['prc'].'\' WHERE id=\''.$ID.'\'';
			$db->query($sql);
		}
		$db->query('UNLOCK TABLES');
	}

	

	
	function delete()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query('DELETE FROM '.$this->tb.' WHERE id=\''.$this->ID.'\'');
		if ( !empty($this->variants) )
		{
			$db->query('DELETE vt FROM '.$this->tbVars.' AS vr, '.$this->tbVote.' AS vt WHERE vr.poll_id=\''.$this->ID.'\' AND vt.var_id=vr.id');
			$db->query('DELETE FROM '.$this->tbVars.' WHERE poll_id=\''.$this->ID.'\'');
		}
		$db->optimizeTable($this->tb);
		$db->optimizeTable($this->tbVars);
		$db->optimizeTable($this->tbVote);
	}
	function _initMapping()
 	{
    	return array(
			'id'          => 'ID',
			'name'        => 'name',
			'descrip'     => 'descrip',
			'begin_ts'    => 'beginTs',
			'end_ts'      => 'endTs',
			'is_complete' => 'isComplete'
		);
	}

	function _validForm()
	{
		$data = $this->form->getValue(); 
		if ( $data['end_ts'] - $data['begin_ts'] < 86400 )
		{
			return $this->form->pushError('end_ts', m('Stop date must be past start date') );
		}
//		echo date('d.m.y',time()).' '.date('d.m.y', $data['end_ts']);
		list($d,$m,$y) = explode('.', date('d.m.Y') );
		
		if ( mktime(0,0,0,$m,$d,$y) == $data['end_ts']  )
		{
//			return $this->form->pushError('end_ts', m('Stop date could not be today date or in the past') );
		}
		
		$qnt = 0;
		for ($i=0, $s=sizeof($data['var']); $i<$s; $i++)
		{
			if ( !empty($data['var'][$i]) )
			{
				$qnt++;
			}
		}
		
		return $qnt<2 ? $this->form->pushError('var[0]', m('Poll should have at least 2 non empty answer variants') ) : true;
	}
}
?>