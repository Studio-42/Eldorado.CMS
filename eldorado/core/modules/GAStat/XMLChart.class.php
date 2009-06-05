<?php

// TODO adaptive size for Charts here and in render

class XMLChart
{
	var $reportTypes;
	var $metricColors;
	var $metricNames;
	
	function generateChartColors()
	{
		$c = '';
		foreach ($this->metricColors as $color)
			$c .= '<color>' . $color . '</color>';
		$c = '<series_color>' . $c . '</series_color>';
		return $c;
	}
	
	function generateChartData($data, $type)
	{
		$ret = '<chart_data>';
		
		$ret .= '<row><null/>';
		for ($i = 0; $i < sizeof($data); $i++)
		{ 
			$dim = $this->reportTypes[$type]['dimensions'];
			if ($dim == 'ga:date') // convert date on the fly
			{
				$data[$i][$dim] = date(EL_DATE_FORMAT, strtotime($data[$i][$dim]));
				$ret .= '<string>' . $data[$i][$dim] . '</string>';
			}
			else
				$ret .= '<string>' . $data[$i][$dim] . '</string>';
		}
		$ret .= '</row>';
		
		foreach ($this->reportTypes[$type]['metrics'] as $metric)
		{
			$ret .= '<row><string>' . $metric . '</string>';
			if ($this->reportTypes[$type]['chart'] == 'line')
				foreach ($data as $node) 
					$ret .= '<number label="' . $node[$metric] . ' ' . $this->metricNames[$metric] . '\r'
						. $node[$this->reportTypes[$type]['dimensions']] . '">'
						. $node[$metric] . '</number>';
			else
				foreach ($data as $node) 
					$ret .= '<number>' . $node[$metric] . '</number>';
			
			$ret .= '</row>';
		}
		
		$ret .= '</chart_data>';
		
		return $ret;
	}
	
	function generateChartLine($size)
	{
		$skip = round($size / 6); // 6 - howmany dates it graphs
		return "<chart_pref line_thickness='4' point_size='0' />"
			. "<chart_rect x='35' y='5' width='680' height='125' />"
			. "<chart_grid_h thickness='1' color='000000' type='solid' />"
			. "<chart_grid_v thickness='0' color='000000' />"
			. "<chart_guide horizontal='false' vertical='false' radius='4' fill_alpha='0' line_color='000000' line_alpha='100' line_thickness='3' />"
			. "<chart_label position='cursor' />"
			. "<legend size='10' bold='false' layout='hide' />"
			. "<axis_category skip='" . $skip . "' size='10' bold='false' />"
			. "<axis_value steps='2' size='10' />"
			. "<chart_transition type='slide_right' delay='0' duration='1' />";
			// . "<update url='http://localhost/~troex/scancode/cc/gstat/visits/' delay='5' />"
	}
	
	function generateChartPie()
	{
		return "<chart_pref />"
			. "<chart_rect x='35' y='25' height='150'/>"
			. "<chart_grid_h thickness='0' color='000000' />"
			. "<chart_grid_v thickness='0' color='000000' />"
			. "<chart_label position='outside' size='10' decimals='1' as_percentage='true' />"
			. "<legend size='10' bold='false' x='220' layout='hide' />"
			. "<chart_transition type='scale' delay='0' duration='0.5' />";
			// . "<series_explode><number>2</number><number>2</number><number>2</number></series_explode>";
			// . "<update url='http://localhost/~troex/scancode/cc/gstat/visits/' delay='5' />"
	}
	
	function generateChart($data, $type)
	{
		$chartData = $this->generateChartData($data, $type);
		$chartType = $this->reportTypes[$type]['chart'];
		
		switch ($chartType)
		{
			case 'line':
				$chartConf = $this->generateChartLine(sizeof($data));
				break;
			case 'pie':
				$chartConf = $this->generateChartPie();
				break;
			default:
				$chartConf = '';
				break;
		}
		// check $chartConf
		if (empty($chartConf))
			return false;
		
		$colors = $this->generateChartColors();
		// $chartConf .= "<chart_transition type='slide_right' delay='1' duration='1' />";
		$chartConf .= '<chart_type>' . $chartType . '</chart_type>';
		$chart = '<chart>' . $chartConf . $colors . $chartData . '</chart>';
		
		return $chart;
	}
}

?>