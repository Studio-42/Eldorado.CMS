<?php

class elRndGAStat extends elModuleRenderer
{
	var $_tpls = array(
		'visits'     => 'visits.html',
		'pageviews'  => 'pageviews.html',
		'source'     => 'source.html',
		'country'    => 'country.html',
		'keyword'    => 'keyword.html',
		'pagepath'   => 'pagepath.html',
		'browser'    => 'browser.html',
		'os'         => 'os.html',
		'medium'     => 'medium.html',
		'exit'       => 'exit.html',
		'landing'    => 'landing.html',
		
		'report'     => 'report.html',

		'chartLine'  => 'chartLine.html',
		'chartPie'   => 'chartPie.html',

		'dashboard'  => 'dashboard.html',
		'reportDates'=> 'date-select.html'
		);
	
	function rndReport($data, $type, $period, $chart, $legend, $reportName)
	{
		$this->_setFile('default');
		$this->_setFile('report', 'REPORT_FILE'); 
		$this->_te->assignVars('period', $period);
		$this->_te->assignVars('report_name', $reportName);
		$this->_rndDateSelect();
		$this->_setFile($type, 'REPORT');
		
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		elAddJs('FusionCharts.js', EL_JS_CSS_FILE);
				
		if (!empty($chart) && ($chart != ''))
		{
			if ($chart == 'line')
			{
				elAddJs(
'var GAwidth = $("#mc-content").width() - 20;
var chart = new FusionCharts("'.EL_BASE_URL.'/style/images/fusionchart/FCF_MSLine.swf", "ChartId", GAwidth, "250");
chart.setDataURL("'.EL_URL.'xmlchart/'.$type.'");
chart.render("chartLine");', EL_JS_SRC_ONREADY);
				
				$this->_setFile('chartLine', 'GASTAT_CHART');
				$this->_te->assignVars('chart_line', $type);
				$this->_te->assignBlockVars('LEGEND_LINE', array('gastat_legend' => $this->rndLegendVertical($legend)));
				$this->_te->parse('GASTAT_CHART');
			}
			elseif ($chart == 'pie')
			{
				elAddJs(
'var chart = new FusionCharts("'.EL_BASE_URL.'/style/images/fusionchart/FCF_Pie2D.swf", "ChartId", "270", "250");
chart.setDataURL("'.EL_URL.'xmlchart/'.$type.'");
chart.render("chartPie");', EL_JS_SRC_ONREADY);

				$this->_setFile('chartPie', 'GASTAT_CHART');
				$this->_te->assignVars('chart_pie', $type);
				$this->_te->assignBlockVars('LEGEND_PIE', array('gastat_legend' => $this->rndLegendVertical($legend)));
				$this->_te->parse('GASTAT_CHART');
			}
		}		

		for ($i=0, $s=sizeof($data); $i<$s; $i++)
		{
			if (!empty($data[$i]['ga:date']))
				$data[$i]['ga:date'] = date(EL_DATE_FORMAT, strtotime($data[$i]['ga:date']));
			$this->_te->assignBlockVars('GASTAT_NODE', $data[$i]);
		}
		
		$this->_te->parse('REPORT');
		$this->_te->parse('REPORT_FILE');
	}
	
	function rndDashboard($siteUsage, $blocks, $legend, $period, $reportName)
	{
		$this->_setFile('default');
		$this->_setFile('dashboard', 'REPORT_FILE');
		$this->_te->assignVars('period', $period);
		$this->_te->assignVars('report_name', $reportName);
		$this->_rndDateSelect();
		
		elAddJs('FusionCharts.js', EL_JS_CSS_FILE);
		
		elAddJs(
'var GAwidth = $("#mc-content").width() - 20;
var chart = new FusionCharts("'.EL_BASE_URL.'/style/images/fusionchart/FCF_MSLine.swf", "ChartId", GAwidth, "250");
chart.setDataURL("'.EL_URL.'xmlchart/db_visits");
chart.render("chartLine");', EL_JS_SRC_ONREADY);
		
		$this->_setFile('chartLine', 'VISIT_CHART');
		$this->_te->assignVars('chart_line', 'db_visits');
		$this->_te->assignVars('visit_legend', $this->rndLegendHorizontal($legend['db_visits']));
		
		elAddJs(
'var chart = new FusionCharts("'.EL_BASE_URL.'/style/images/fusionchart/FCF_Pie2D.swf", "ChartId", "270", "250");
chart.setDataURL("'.EL_URL.'xmlchart/medium");
chart.render("chartPie");', EL_JS_SRC_ONREADY);

		$this->_setFile('chartPie', 'CHART_MEDIUM');
		$this->_te->assignVars('chart_pie', 'medium');
		$this->_te->assignVars('medium_legend', $this->rndLegendVertical($legend['medium']));
		//elPrintR($legend);
		
		for ($i=0, $s=sizeof($blocks['db_pagepath']); $i<$s; $i++)
		{
			$this->_te->assignBlockVars('GASTAT_PAGEPATH', $blocks['db_pagepath'][$i]);
		}		
		for ($i=0, $s=sizeof($blocks['db_country']); $i<$s; $i++)
			$this->_te->assignBlockVars('GASTAT_COUNTRY', $blocks['db_country'][$i]);
			
		for ($i=0, $s=sizeof($blocks['db_keyword']); $i<$s; $i++)
		{
			$maxLen = 25;
			if (mb_strlen($blocks['db_keyword'][$i]['ga:keyword'], 'UTF-8') > $maxLen)
				$blocks['db_keyword'][$i]['ga:keyword'] = mb_substr($blocks['db_keyword'][$i]['ga:keyword'], 0, $maxLen, 'UTF-8') . '...';
			$this->_te->assignBlockVars('GASTAT_KEYWORD', $blocks['db_keyword'][$i]);
		}
		$this->_te->assignVars($siteUsage);
		
		$this->_te->parse('VISIT_CHART');
		$this->_te->parse('CHART_MEDIUM');
		$this->_te->parse('REPORT_FILE');
	}

	function rndLegendHorizontal($legend)
	{
		$l = '';
		$pixel = EL_BASE_URL . '/style/images/pixel.gif';
		foreach ($legend as $node) 
		{
			$l .= '<img width="10" height="10" class="rounded-5" style="margin-left: 25px; background-color: #'
			. $node['color'] . ';" src="' . $pixel . '" />&nbsp;' . $node['name'];
		}
		return '<div style="text-align: center;">' . $l . '</div>';
	}
	
	function rndLegendVertical($legend)
	{
		$l = '';
		$pixel = EL_BASE_URL . '/style/images/pixel.gif';
		foreach ($legend as $node) 
		{
			$l .= '<img width="10" height="10" class="rounded-5" style="background-color: #'
			. $node['color'] . ';" src="' . $pixel . '" />&nbsp;' . $node['name'] . '<br /><br />';
		}
		return '<div>' . $l . '</div>';
	}
	
	function _rndDateSelect()
	{
		foreach ($this->reportDates as $type=>$v)
		{
			$s = $type == $this->dateType ? ' selected="on"' : '';
			$this->_te->assignBlockVars('REPORT_DATE', array('type'=>$type, 'label'=>m($v['label']), 'selected'=>$s ));
		}
	}
	
}

?>