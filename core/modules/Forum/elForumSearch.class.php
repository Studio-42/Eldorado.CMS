<?php

/**
* Forum Search
*
* @author Troex Nevelin <troex@fury.scancode.ru>
* TODO: design, remove * from search sql
*/
class elForumSearch
{
	var $_searchPeriod = array(
		'0'  => 'All time',
		'1'  => 'Day',
		'7'  => 'Week',
		'30' => 'Month',
		'365'=> 'Year'
		);
	var $_resultsOnPage = 10;
	var $_messageLength = 256;
	
	function makeForm($cats)
	{
		// localize
		foreach ($this->_searchPeriod as $k => $v)
			$this->_searchPeriod[$k] = m($v);
		
		$myCats = array();
		foreach ($cats as $c)
		{
			$myCats[$c['id']] = str_repeat('&nbsp;', $c['level'] * 3).$c['name'];
		}

		$form = & elSingleton::getObj('elForm', 'MYforumSearch', '', EL_URL.'search/#MYforumSearch');
		$form->label = m('Search');
		$form->setRenderer(elSingleton::getObj('elTplGridFormRenderer'), 2);		
		
		$left  = & new elFormContainer('left_cont',  m('Left'), array('style' => 'width: 500px; background: #eeeeee; padding: 8px;', 'class' => 'rounded-7'));
		$left->setTpl('label', m('Category'));
		$left->add(new elCData('div_1_b', '<div class="rounded-7 multiselect-opts" style="padding: 3px; background: white; height: 150px; overflow : auto;">'));
		$left->add(new elCheckBoxesGroup('cats', '', array('0' => '1'), $myCats));
		$left->add(new elCData('div_1_e', '</div>'));
		$left->add(new elCheckBox('recursive', '<label for="recursive">' . m('Search in sub forums? ') . '</label>', 'yes', array('checked' => 'on', 'style' => 'margin-top: 8px;')));

		$right  = & new elFormContainer('right_cont', m('Right'));
		$right->setTpl('label', '');
		$right->add(new elText(    'search', '', '', array('style' => 'font-size: x-large;', 'size' => '20')));
		$right->add(new elCData(   'br_1', '<br />'));
		$right->add(new elCheckBox('flexible', '<label for="flexible">' . m('Use flexible search ') . '</label>', 'yes', array('style' => 'margin-top: 8px;')));
		$right->add(new elCData(   'hr_1', '<hr />'));
		$right->add(new elCheckBox('inc_subj', '<label for="inc_subj">' . m('Search in subjects ') . '</label>', 'yes', array('style' => 'margin-top: 5px;')));
		$right->add(new elCData(   'hr_2', '<hr />'));
		$right->add(new elSelect(  'period', '<label for="period">' . m('Search messages for ') . '</label>', '', $this->_searchPeriod));
		$right->add(new elCData(   'hr_3', '<hr />'));
		$right->add(new elSubmit(  'search_submit', '', m('Search'), array('class' => 'submit', 'style' => 'margin-top: 8px;')));

		$form->add($left);
		$form->add($right);
		$form->setElementRule('search', 'minlength', true, 3);
		$form->setElementRule('cats[]', 'required');
		$form->setRequired('cats[]');

		return $form;
	}
	
	function findPost($id)
	{
		$id = (int)$id;
		if ($id < 1)
			return false;

		$db  = & elSingleton::getObj('elDb');
		$sql = 'SELECT id, cat_id, topic_id, subject FROM el_forum_post WHERE id='.$id;
      	$db->query($sql);
		$r = $db->nextRecord();
		if (empty($r))
			return false;

		$sql = "SELECT COUNT(id) as position FROM el_forum_post WHERE id<=$id AND topic_id=".$r['topic_id'];
      	$db->query($sql);
		$c = $db->nextRecord();
		$pos = floor($c['position'] / $this->_resultsOnPage) + 1;

		$url = EL_URL.'topic/'.$r['cat_id'].'/'.$r['topic_id'].'/'.$pos.'/#'.$r['id'];
		return $url;
	}
	
	function search($data)
	{	
		$results = array();
		$where = array();

		if ($mb = function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding('UTF-8');
			mb_regex_encoding('UTF-8');
		}

		// find categories in which to search
		$c = $data['cats'];
		if ((isset($c[0]) and ($c[0] == 1)) or empty($c))
			true;
		else
		{
			if (isset($data['recursive']) and ($data['recursive'] == 'yes'))
				foreach ($this->_cats as $cat)
					if (in_array($cat['pid'], $c))
						array_push($c, $cat['id']);

			$w = 'p.cat_id IN ('.implode(', ', $c).')';
			array_push($where, $w);
		}

		// search only in topic
		$t = isset($data['topic']) ? $data['topic'] : 0;
		if ($t > 0)
		{
			$w = 'p.topic_id = '.$t;
			array_push($where, $w);			
		}
		
		// search period
		$p = isset($data['period']) ? $data['period'] : 0;
		if ($p > 0)
		{
			$w = 'p.crtime >= '.(time() - ($p * 86400));
			array_push($where, $w);
		}

		// next page results
		$n = isset($data['next']) ? $data['next'] : 0;
		if (($n > 0) and ($t == 0))
			$limit = 'LIMIT '.($n * $this->_resultsOnPage).', '.$this->_resultsOnPage;
		else
			$limit = 'LIMIT '.$this->_resultsOnPage;

		$s = $data['search'];

		$f = isset($data['flexible']) ? $data['flexible'] : flase;
		if ($f == 'yes')
		{
			// Plan B
			$s = str_replace(' ', '|', $s);
		}

		// search string
		$w = 'UPPER(p.message) REGEXP UPPER("'.$s.'")';
		if (isset($data['inc_subj']) and ($data['inc_subj'] == 'yes'))
			$w = '('.$w.' OR UPPER(p.subject) REGEXP UPPER("'.$s.'"))';
		array_push($where, $w);

		$db  = & elSingleton::getObj('elDb');
      	// $sql = 'SELECT id, cat_id, topic_id, subject FROM el_forum_post '
      	$sql  = 'SELECT p.*, c.name AS category ';
		$sql .= 'FROM el_forum_post AS p, el_forum_cat AS c ';
		$sql .= 'WHERE p.cat_id = c.id AND ';
		$sql .= implode(' AND ', $where).' ';
		$sql .= $limit;
		$db->query($sql);

		while ($r = $db->nextRecord())
		{
			$subj  = $r['subject'];
			$mesg  = $r['message'];
			$score = 0;

			if ($mb)
			{
				// PHP4 doesn't have MB case-insensitive functions :(
				$subj = mb_strtolower($subj);
				$mesg = mb_strtolower($mesg);
				$s    = mb_strtolower($s);
				
				if ($f)
				{
					// magic scores for *flexible* search
					if (mb_ereg("($s)", $subj) != false)
						$score++;
					
					mb_ereg_search_init($mesg, "($s)");
					while(mb_ereg_search())
					{
						$lastPos = mb_ereg_search_getpos();						
						mb_ereg_search_setpos(mb_ereg_search_getpos() + 1);
						
						$score++;
						if ($score >= 9)
							break;

						// some strange bug? if setpos == length
						if ($lastPos > mb_ereg_search_getpos())
							break;
					}
				}
				else
				{
					// score if subject matches
					if (mb_strpos($subj, $s) != false)
						$score++;

					// score if search string repeats twice in message
					$pos = mb_strpos($mesg, $s);
					if (mb_strpos($mesg, $s, ($pos + 1)))
						$score++;
				}
				$r['score'] = $score;
			}
			else
			{
				// MySQL works fine with encdings, hack :)
				$score = mysql_fetch_row(mysql_query("SELECT UPPER('$subj') REGEXP UPPER('$s')"));
				$r['score'] = $score[0];
			}

			$m = $r['message'];
			$m = str_replace('[', '<', $m);
			$m = str_replace(']', '>', $m);
			$m = strip_tags($m);
			// again magic around with encodings
			if ($mb)
			{
				$m = mb_substr($m, 0, $this->_messageLength);
				if (mb_strlen($m) == $this->_messageLength)
					$m .= '&hellip;';
			}
			else
			{
				$m = mysql_fetch_row(mysql_query("SELECT SUBSTRING('$m', 1, ".$this->_messageLength.")"));
				$m = $m[0].'&hellip;';
			}
			$r['ico'] = '<img style="margin: 5px; float: left;" src="'.EL_BASE_URL.'/style/images/forum/posts/'.$r['ico'].'" />';
			$r['posturl'] = EL_URL.'search/findPost/'.$r['id'];
			$r['message'] = $m;
			array_push($results, $r);
		}

		// sort results based on score
		$sorted = array();
		foreach (array_reverse($results) as $r)
		{
			$s = $r['score'];
			$i = $r['id'];
			$sorted[$s.'_'.$i] = $r;
		}
		krsort($sorted);

		return $sorted;
	}
}
