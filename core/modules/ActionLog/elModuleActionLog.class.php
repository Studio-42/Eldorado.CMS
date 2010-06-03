<?php

/**
* Show actions made on site and mail them based on config params
* 
* @package ActionLog
* @version 0.9
* @author Troex Nevelin <troex@fury.scancode.ru>
*/
class elModuleActionLog extends elModule
{
	var $_mMap = array(
		// 'report'      => array('m' => 'report'),
		);
	var $_conf = array(
		'reportEmail'   => '',
		'reportPeriod'  => 0,
		'reportType'    => 'modify',
		'reportLast'    => 10,
		'reportNext'    => 0,
		'recordsOnPage' => 25
		);

	function __construct()
	{
		# code...
	}

	function elModuleActionLog()
	{
		$this->__construct();
	}

	function defaultMethod()
	{
		// pager
		$pCurrent = (int)$this->_arg();
		$pCurrent = ($pCurrent < 1 ? 1 : $pCurrent);
		$pTotal   = ceil($this->_count() / $this->_conf['recordsOnPage']);
		$pager    = array($pCurrent, $pTotal);
		
		$rec = $this->_getRecords();
		$this->_initRenderer();
		$this->_rnd->rndActionLogList($rec, $pager);
	}

	function report($last, $next, $type, $period, $email)
	{
		if (($next > time()) or ($period < 1))
			return false;

		$m = '';
		$rec = $this->_getRecords($last);
		foreach ($rec as $r)
		{
			if ($type == 'modify')
			{
				if (($r['action'] != 'new') and ($r['action'] != 'edit'))
					continue;
			}
			$m .= sprintf('#%s "%s" %s "%s" %s', $r['id'], $r['module'],
				m('Object'), m($r['object']), m($r['action'])
			);
			if (!empty($r['value']))
				$m .= sprintf(" (%s: %s)", m('Value'), $r['value']);
			if (!empty($r['link']))
				$m .= "\n" . EL_BASE_URL.$r['link'];
			$m .= "\n\n";
		}
		$emails  = & elSingleton::getObj('elEmailsCollection');
		$from    = $emails->getDefault();
		$to      = $email;
		$subject = sprintf(m('Changes made on site %s since %s'), EL_BASE_URL, date(EL_DATE_FORMAT, $last));

		if (empty($to) or empty($from) or empty($m))
			return false;

		$postman = & elSingleton::getObj('elPostman');
		$postman->newMail($from, $to, $subject, $m);
		if (!$postman->deliver())
		{
			elDebug($postman->error);
			return false;
		}

		return true;
	}

	function _getRecords($last = null)
	{
		$where = false;
		$offset = 0;
		$limit = $this->_conf['recordsOnPage'];
		
		if ($this->_arg(0))
			$offset = ((int)$this->_arg(0) -1 ) * $this->_conf['recordsOnPage'];

		if ($last > 0)
		{
			$where  = 'time > '.(int)$last;
			$offset = false;
			$limit  = false;
		}
		
		$nav  = & elSingleton::getObj('elNavigator');
		$user = & elSingleton::getObj('elUser');
		$rec  = & elSingleton::getObj('elActionRecord');
		$ret  = $rec->collection(false, false, $where, 'time DESC', $offset, $limit);
		$records = array();
		foreach ($ret as $r)
		{
			$user->idAttr($r['uid']);
			$user->fetch();
			$r['user']   = $user->getFullName(true);
			$r['module'] = $nav->getPageName($r['mid']);
			array_push($records, $r);
		}
		return $records;
	}

	function _count()
	{
		$rec  = & elSingleton::getObj('elActionRecord');
		return $rec->count();
	}

	function &_makeConfForm()
	{
		$reportPeriod = array(
			'0'  => m('Never'),
			'1'  => m('Everyday'),
			'3'  => m('Every 3 days'),
			'7'  => m('Weekly'),
			'30' => m('Monthly')
			);
		$reportType = array(
			'full'   => m('Full'),
			'modify' => m('Content modify')
			);
		
		$form = &parent::_makeConfForm();
		$form->add(new elText('reportEmail',    m('Email reports'),  $this->_conf('reportEmail')));
		$form->add(new elSelect('reportType',   m('Report type'),    $this->_conf('reportType'),   $reportType));
		$form->add(new elSelect('reportPeriod', m('Reports period'), $this->_conf('reportPeriod'), $reportPeriod));
		$form->setElementRule('reportEmail', 'email', true);
		return $form;
	}

}
