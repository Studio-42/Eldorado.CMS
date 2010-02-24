<?php

// TODO delete all cache on webPropertyId change
/**
 * Google Analytics Statistics Module
 *
 * @package elModuleGAStat
 * @version 2.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */
class elModuleGAStat extends elModule
{
	var $_mMap = array(
		'report'     => array('m'=>'viewReport'),
		'xmlchart'   => array('m'=>'getXMLChart'),
		'dashboard'  => array('m'=>'viewDashboard'),
		'set_period' => array('m' => 'setReportPeriod')  
		);
	
	var $_conf = array(
		'account'       => '',
		'passwd'        => '',
		'webPropertyId' => '',
		'profileId'     => ''
		);
	
	var $_reportTypes = array(
		'visits'      => array(
			'dimensions' => 'ga:date',
			'metrics'    => array('ga:visits'),
			'sort'       => 'ga:date',
			'chart'      => 'line',
			'title'      => 'Visits'
			),
		'pageviews'   => array(
			'dimensions' => 'ga:date',
			'metrics'    => array('ga:pageviews'),
			'sort'       => 'ga:date',
			'chart'      => 'line',
			'title'      => 'Page Views'
				),
		'country'     => array(
			'dimensions' => 'ga:country',
			'metrics'    => array('ga:pageviews', 'ga:visits'),
			'sort'       => '-ga:visits',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Countries'
			),
		'source'      => array(
			'dimensions' => 'ga:source',
			'metrics'    => array('ga:visits'),
			'sort'       => '-ga:visits',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Traffic Sources'
			),
		'keyword'     => array(
			'dimensions' => 'ga:keyword',
			'metrics'    => array('ga:visits'),
			'sort'       => '-ga:visits',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Keywords',
			'start-index'=> '2'
			),
		'pagepath'    => array(
			'dimensions' => 'ga:pagePath',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Path'
			),
		'medium'      => array(
			'dimensions' => 'ga:medium',
			'metrics'    => array('ga:visits'),
			'sort'       => '-ga:visits',
			'chart'      => 'pie',
			'max'        => 3,
			'title'      => 'Medium'
			),
		'browser'     => array(
			'dimensions' => 'ga:browser',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Browsers'
			),
		'os'          => array(
			'dimensions' => 'ga:operatingSystem',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Operation Systems'
			),
		'exit'        => array(
			'dimensions' => 'ga:exitPagePath',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Exit Pages'
			),
		'landing'        => array(
			'dimensions' => 'ga:landingPagePath',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'chart'      => 'pie',
			'max'        => 'auto',
			'title'      => 'Landing Pages'
			),
		// Dashboard special
		'dashboard'   => array(
			'dimensions' => 'ga:date',
			'metrics'    => array('ga:visits', 'ga:pageviews', 'ga:timeOnSite', 'ga:newVisits', 'ga:bounces'),
			'sort'       => 'ga:date',
			'title'      => 'Dashboard'
			),
		'db_visits'   => array(
			'dimensions' => 'ga:date',
			'metrics'    => array('ga:visits', 'ga:newVisits', 'ga:bounces'),
			'sort'       => 'ga:date',
			'chart'      => 'line'
			),
		'db_pagepath' => array(
			'dimensions' => 'ga:pagePath',
			'metrics'    => array('ga:pageviews'),
			'sort'       => '-ga:pageviews',
			'max-results'=> 5
			),
		'db_country'  => array(
			'dimensions' => 'ga:country',
			'metrics'    => array('ga:visits'),
			'sort'       => '-ga:visits',
			'max-results'=> 5
			),
		'db_keyword'  => array(
			'dimensions'  => 'ga:keyword',
			'metrics'     => array('ga:visits'),
			'sort'        => '-ga:visits',
			'max-results' => 5,
			'start-index' => '2'
			)
		);
	
	var $reportDates = array(
		'1w' => array('label'=>'Last week',     'start'=>'-1 week',  'end'=>'yesterday'),
		'1m' => array('label'=>'Last month',    'start'=>'-1 month', 'end'=>'yesterday'),				
		'3m' => array('label'=>'Last 3 months', 'start'=>'-3 month', 'end'=>'yesterday'),
		'6m' => array('label'=>'Last 6 months', 'start'=>'-6 month', 'end'=>'yesterday'),
		'1y' => array('label'=>'Last year',     'start'=>'-1 year',  'end'=>'yesterday'),
		'2y' => array('label'=>'Last 2 years',  'start'=>'-2 year',  'end'=>'yesterday')
		// '3y' => array('label'=>'Last 3 years',  'start'=>'-3 year',  'end'=>'yesterday')
		// 'a'  => array('label'=>'All time',      'start'=>'-5 year',  'end'=>'yesterday')		
		
		);
	
	var $_metricNames = array(
		'ga:visits'     => 'Visits',
		'ga:pageviews'  => 'Page Views',
		'ga:timeOnSite' => 'Avg. Time on Site',
		'ga:newVisits'  => 'New Visits',
		'ga:bounces'    => 'Bounce'
		);
		
	var $_metricColors = array(
		'058dc7', '50b432', 'ed561b', 'edef00', '24cbe5',
		'64e572', 'ff9655', 'fff263', '6af9c4', 'b2deff',
		'cccccc', 'cccccc', 'cccccc', 'cccccc', 'cccccc',
		'cccccc', 'cccccc', 'cccccc', 'cccccc', 'cccccc',
		'cccccc', 'cccccc', 'cccccc', 'cccccc', 'cccccc'
		);
	
	var $_analytics = null;
	var $_period = array();
	var $_periodHuman;
	var $dateType = 'm';
	var $_sharedRndMembers = array('reportDates', 'dateType');

	function __construct()
	{ // check CURL module and version
		if (!function_exists('curl_init'))
		{
			elThrow(E_USER_WARNING, 'PHP module CURL not installed', null, EL_BASE_URL);
		}
		else
		{
			$curl = curl_version();
			if (is_array($curl)) // looks like PHP5
				$curl = implode(' ', array('libcurl/'.$curl['version'], $curl['ssl_version'], 'zlib/'.$curl['libz_version']));
			// else PHP4
			elDebug('CURL: '.$curl);
			if (!preg_match('/OpenSSL/', $curl))
				elThrow(E_USER_WARNING, 'PHP module CURL do not support SSL (curl_version: '.$curl.')', null, EL_BASE_URL);
		}
	}

	function elModuleGAStat()
	{
		$this->__construct();
	}

	function defaultMethod()
	{
		elLocation(EL_URL . 'dashboard');
	}

	function viewDashboard()
	{
		$blocks = array();
		$legend = array();
		
		foreach (array('db_keyword', 'db_country', 'db_pagepath') as $type)
		{
			if (false == ($data = $this->_analytics->get($this->_makeURL($type))))
				return elThrow(E_USER_WARNING, 'GA failed on %s: %s', array($type, $this->_analytics->error));
			$blocks[$type] = $data;
		}
		
		$type = 'dashboard';
		if (false == ($dashboard = $this->_analytics->get($this->_makeURL($type))))
			return elThrow(E_USER_WARNING, 'GA failed on dashboard: %s', $this->_analytics->error);
		$newData = $this->sumStructMetrics($dashboard, $type);
		$siteUsage = array(
			'visits'         => number_format($newData['ga:visits']),
			'pageviews'      => number_format($newData['ga:pageviews']),
			'visits_pv_r'    => sprintf('%.2f', ($newData['ga:pageviews'] / $newData['ga:visits'])),
			'avg_time'       => gmdate('H:i:s', round($newData['ga:timeOnSite'] / $newData['ga:visits'])),
			'new_visits_p'   => sprintf('%.2f%%', ($newData['ga:newVisits'] / $newData['ga:visits'] * 100)),
			'bounces_p'      => sprintf('%.2f%%', ($newData['ga:bounces'] / $newData['ga:visits'] * 100))
			);
		
		$type = 'db_visits';
		$legend[$type] = $this->generateMetricLegend($type);

		$type = 'medium';
		if (false == ($medium = $this->_analytics->get($this->_makeURL($type))))
			return elThrow(E_USER_WARNING, 'GA failed on medium: %s', $this->_analytics->error);		
		$medium = $this->maxDimensionsData($medium, $type, 3);
		$legend[$type] = $this->generateDataLegend($medium, $type);

		$this->_initRenderer();
		$this->_rnd->rndDashboard($siteUsage, $blocks, $legend, $this->_periodHuman, m($this->_reportTypes['dashboard']['title']));
	}
	
	function viewReport()
	{
		$type = $this->_arg();
		$chart = false;
		
		if (empty($this->_reportTypes[$type]))
			elThrow(E_USER_WARNING, 'unknown report type', null, EL_URL);
	
		if (false == ($data = $this->_analytics->get($this->_makeURL($type))))
			return elThrow(E_USER_WARNING, 'GA failed on get: %s', $this->_analytics->error, EL_URL);
	
		if (!empty($this->_reportTypes[$type]['chart']))
			$chart = $this->_reportTypes[$type]['chart'];
				
		if ($chart == 'line')
			$legend = $this->generateMetricLegend($type);
		elseif ($chart == 'pie')
		{
			if (!empty($this->_reportTypes[$type]['max']))
			{
				$newData = $this->maxDimensionsData($data, $type, $this->_reportTypes[$type]['max']);
				$legend = $this->generateDataLegend($newData, $type);
			}
			else
			{
				$legend = $this->generateDataLegend($data, $type);
			}
		}
	
		$this->_initRenderer();
		$this->_rnd->rndReport($data, $type, $this->_periodHuman, $chart, $legend, m($this->_reportTypes[$type]['title']) );
	}
		
	// returns only $max first positions of data + all other data summed as dimension = '(other)'
	function maxDimensionsData($data, $type, $max)
	{
		if ($max == 'auto')
		{
			$max = 0;
			$newData = array();

			$sum = $this->getDataSum($data, $type);

			for ($i = 0; $i < sizeof($data); $i++)
			{ 
				$n = $data[$i][$this->_reportTypes[$type]['metrics'][0]];
				if (($n / $sum) >= (2 / 100))
				{
					$newData[$i] = $data[$i];
					$max++;
				}
				else
					break;

				if ($max >= 10)
					break;
			}
			
		}
		elseif (($max == 0) || empty($max) || (sizeof($data) <= $max))
			return $data;
		else
			for ($i = 0; $i < $max; $i++)	// copy unchanged data
				$newData[$i] = $data[$i];
						
		// street magics goes next
		$other = array();
		$other[$this->_reportTypes[$type]['dimensions']] = '(other)';
		for ($i = $max; $i < sizeof($data); $i++)
			foreach ($this->_reportTypes[$type]['metrics'] as $metric)
				$other[$metric] += $data[$i][$metric];

		array_push($newData, $other);

		return $newData;
	}
	
	// converts first metric to percent, this is for use with pie
	// not used anymore as we now show percent in in chart.swf
	function convertDataToPercent($data, $type)
	{
		$sum = $this->getDataSum($data, $type);
		for ($i = 0; $i < sizeof($data); $i++)
			$data[$i][$this->_reportTypes[$type]['metrics'][0]] = $data[$i][$this->_reportTypes[$type]['metrics'][0]] / $sum * 100;

		return $data;
	}
	
	// get sum for first metric
	function getDataSum($data, $type)
	{
		$sum = 0;
		foreach ($data as $node)
			$sum += $node[$this->_reportTypes[$type]['metrics'][0]];
		return $sum;
	}
	
	function generateMetricLegend($type)
	{
		$legend = array();
		$i = 0;
		foreach ($this->_reportTypes[$type]['metrics'] as $metric)
		{
			$legend[$metric] = array(
				'name'  => m($this->_metricNames[$metric]),
				'color' => $this->_metricColors[$i]
				);
			$i++;
		}
		return $legend;
	}
	
	function generateDataLegend($data, $type)
	{
		$legend = array();
		$i = 0;
		foreach ($data as $node)
		{
			$legend[$node[$this->_reportTypes[$type]['dimensions']]] = array(
				'name'  => m($node[$this->_reportTypes[$type]['dimensions']]),
				'color' => $this->_metricColors[$i]
				);
			$i++;
		}
		return $legend;
	}
	
	function getXMLChart()
	{
		// init XMLChart
		$xmlchart = & elSingleton::getObj('XMLChart');
		$xmlchart->reportTypes  = $this->_reportTypes;
		$xmlchart->metricColors = $this->_metricColors;
		$xmlchart->metricNames  = $this->_metricNames;
		
		$type = $this->_arg();
		if (empty($this->_reportTypes[$type]))
			elThrow(E_USER_WARNING, 'unknown report type', null, EL_URL);
	
		if (false == ($data = $this->_analytics->get($this->_makeURL($type))))
			return elThrow(E_USER_WARNING, 'GA failed on get: %s', $this->_analytics->error, EL_URL);
		
		if (($this->_reportTypes[$type]['chart'] == 'pie') && ($this->_reportTypes[$type]['max']))
			$data = $this->maxDimensionsData($data, $type, $this->_reportTypes[$type]['max']);
		
		// if ($this->_reportTypes[$type]['chart'] == 'pie')		// now used in chart.swf
		// 	$data = $this->convertDataToPercent($data, $type);

		$xml = $xmlchart->generateChart($data, $type);
		
		header('Cache-Control: no-cache,no-store,must-revalidate');
		header('Content-Type: application/xml');
		print $xml;
		exit;
	}
	
	function sumStructMetrics($data, $type)
	{
		$newData = array();
		foreach ($this->_reportTypes[$type]['metrics'] as $metric)
		{
			$sum = 0;
			foreach ($data as $node) {
				 $sum += $node[$metric];
			}
			$newData[$metric] = $sum;
		}
		return $newData;
	}
	
	function configure()
	{
		if ( EL_FULL <> $this->_aMode )
		{
			elThrow(E_USER_WARNING, 'Operation not allowed', '', EL_URL);
		}

		$form = & $this->_makeConfForm();
		if ( $form->isSubmitAndValid() && null != ($newConf = $this->_validConfForm( $form)) )
		{
			$newConf['profileId'] = $this->_conf('webPropertyId') != $newConf['webPropertyId'] ? '' : $this->_conf('profileId');
			$this->_updateConf( $newConf );
			elMsgBox::put( m('Configuration was saved') );
			elLocation( EL_WM_URL.$this->_smPath );
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $form->toHtml() );
	}


	function setReportPeriod()
	{
		if ( !empty($_POST['report_period']) && !empty($this->reportDates[$_POST['report_period']]) )
		{
			$ats    = & elSingleton::getObj('elATS');
			$user   = & $ats->getUser();
			$period = array(
				date('Y-m-d', strtotime($this->reportDates[$_POST['report_period']]['start'])),
				date('Y-m-d', strtotime($this->reportDates[$_POST['report_period']]['end']))	
			);
			$user->setPref('ga-period', $period);
			$user->setPref('ga-period-type', trim($_POST['report_period']));
			
		}
		elLocation(EL_URL);
	}

	function _makeURL($reportType)
	{
		$report = $this->_reportTypes[$reportType];
		$url  = 'https://www.google.com/analytics/feeds/data?';
		$url .= 'ids=ga:'.$this->_conf('profileId').'&';
		$url .= 'start-date='.$this->_period[0].'&';
		$url .= 'end-date='.$this->_period[1].'&';	
		$url .= 'dimensions='.$report['dimensions'].'&';
		$url .= 'metrics='.implode(',', $report['metrics']).'&';
		$url .= 'sort='.$report['sort'];
		if (!empty($report['max-results']))
			$url .= '&max-results='.$report['max-results'];
		if (!empty($report['start-index']))
			$url .= '&start-index='.$report['start-index'];
		$url .= '&prettyprint=true';
		elDebug('GA_makeURL:' . $url);
		return $url;
	}
	
	function _auth()
	{
		if ( empty($this->_conf['account']) )
		{
			elThrow(E_USER_WARNING, 'Config is empty');
			if (!$this->_analytics->auth())
			{
				elThrow(E_USER_WARNING, 'Auth failed');
			}
		}
		return $this->_analytics->auth() ? true : elThrow(E_USER_WARNING, 'Auth failed');
	}
	
	function _onInit()
	{
		$ats    = & elSingleton::getObj('elATS');
		$user   = & $ats->getUser();
		$period = $user->getPref('ga-period');
		$dateType = $user->getPref('ga-period-type');
		if (!empty($this->reportDates[$dateType]) && is_array($period) && sizeof($period) == 2 
		&& preg_match('/\d{4}\-\d{2}\-\d{2}/i', $period[0])  && preg_match('/\d{4}\-\d{2}\-\d{2}/i', $period[1]))
		{
			$this->_period = $period;
			$this->dateType = $dateType;
		}
		else
		{
			$this->_period = array( date('Y-m-d', strtotime('-1 month')),  date('Y-m-d', strtotime('yesterday')));
		}
		$this->_periodHuman = date(EL_DATE_FORMAT, strtotime($this->_period[0]))
			. ' - ' . date(EL_DATE_FORMAT, strtotime($this->_period[1]));
		
		$this->_analytics = & elSingleton::getObj('elGoogleAnalytics', $this->_conf('account'), $this->_conf('passwd') );
		
		
		if ( empty($this->_conf['account']) || empty($this->_conf['passwd']) || empty($this->_conf['webPropertyId']) )
		{
			return elThrow(E_USER_WARNING, 'Config is invalid');
		}
		
		if ( empty($this->_conf['profileId']) )
		{
			if (!$this->_auth())
			{
				return elThrow(E_USER_WARNING, 'Could not get profileId');
			}
			
			if (false == ($data = $this->_analytics->getAccounts()))
			{
				return elThrow(E_USER_WARNING, 'Could not get profileId in GA');
			}
			foreach ($data as $v)
			{
				if ($v['ga:webPropertyId'] == $this->_conf('webPropertyId'))
				{
					$newConf = $this->_conf;
					$newConf['profileId'] = $v['ga:profileId'];
					$this->_updateConf($newConf);
					break;
				}
			}
		}
	}
	
	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();
		$form->add( new elText('account', m('Google account'), $this->_conf('account')) );
		$form->add( new elPassword('passwd', m('Google password')) );		
		$form->add( new elText('webPropertyId', m('webPropertyId'), $this->_conf('webPropertyId')) );
		return $form;
	}
	
}

?>