<?php

class elGlossary extends elMemberAttribute
{
	var $ID       = 0;
	var $word     = '';
	var $descr    = '';
	var $url      = '';
	var $tb       = '';
	var $_objName = 'Word';

	function makeForm()
	{
		parent::makeForm();
		$this->form->add(new elText('word', m('Word'), $this->getAttr('word')));
		$this->form->add(new elTextArea('descr', m('Description'), $this->getAttr('descr')));
		$this->form->add(new elText('url', m('URL'), $this->getAttr('url')));
	}

	function getABCs()
	{
		$ABCs = array();
		$db = & elSingleton::getObj('elDb');
		$sql = 'select distinct upper(substr(word, 1, 1)) letter from ' . $this->tb
			. ' where upper(substr(word, 1, 1)) regexp "[A-Z]" order by letter';
		$db->query($sql);
		while ($row = $db->nextRecord()) {
			$row['url_letter'] = $row['letter'];
			$ABCs[0][] = $row;
		}
		$sql = 'select distinct upper(substr(word, 1, 1)) letter from ' . $this->tb
			. ' where not upper(substr(word, 1, 1)) regexp "[A-Z]" order by letter';
		$db->query($sql);
		while ($row = $db->nextRecord()) {
			$row['url_letter'] = urlencode($row['letter']);
			$ABCs[1][] = $row;
		}
		return $ABCs;
	}

	function firstLetter() {
		$db = & elSingleton::getObj('elDb');
		$sql = 'select min(substr(word, 1, 1)) letter from ' . $this->tb;
		$letter = $db->queryToArray($sql, 'letter', 'letter');
		return array_pop($letter);
	}

	function _initMapping()
 	{
    	return array(
			'id'=>'ID',
			'word'=>'word',
			'descr' => 'descr',
			'url' => 'url'
		);
	}
}
?>