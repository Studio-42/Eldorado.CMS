<?php
/**
 * XML output for FusionCharts
 *
 * @package elModuleGAStat
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */
class XMLChart
{
	var $reportTypes;
	var $metricColors;
	var $metricNames;
		
	function generateDataset($data, $type, $size)
	{
		$c = 0;
		$r = '';
		$anchor = ($size > 30 ? 0 : 1);
		$line = 3;
		if ($size > 100)
			$line = 2;
		foreach ($this->reportTypes[$type]['metrics'] as $metric)
		{
			$color = $this->metricColors[$c];
			$r .= "\t<!-- ".$metric." -->\n";
			$r .= "\t<dataset color='".$color."' showAnchors='".$anchor."' alpha='100' lineThickness='".$line."'>\n";
			foreach ($data as $node)
			{
				$r .= "\t\t<set value='".$node[$metric]."' />\n";
			}
			$r .= "\t</dataset>\n";
			$c++;
		}			
		return $r;
	}
	
	function generateSet($data, $type)
	{
		$c = 0;
		$r = '';
		$metric = $this->reportTypes[$type]['metrics'][0];
		foreach ($data as $node)
		{
			$color = $this->metricColors[$c];
			list($dim, $title) = explode('=', $node['TITLE']);
			$r .= "\t<set name='".$title."' value='".$node[$metric]."' color='".$color."' />\n";
			$c++;
		}
		return $r;
	}

	function generateCategory($data, $size)
	{
		$c = '';
		$e = 30;
		$i = 0;
		$skip = 0;
		if ($size > $e)
		{
			$skip = round($size / $e);
		}
		foreach ($data as $node)
		{
			if ($i >= $skip)
			{
				$i = 0;
				$show = true;
			}
			else
			{
				$show = false;
			}
			list($dim, $title) = explode('=', $node['TITLE']);
			if ($dim == 'ga:date') // convert date on the fly
				$title = date(EL_DATE_FORMAT, strtotime($title));
			$c .= "\t\t<category name='".$title."' showName='".($show ? 1 : 0)."' />\n";
			$i++;
		}
		$r = "\t<categories>\n".$c."\t</categories>\n";
		return $r;
	}
	
	function generateChartLine()
	{
		$graph = "<graph"
		       . " shadowThickness='4' showShadow='1' shadowXShift='1' shadowYShift='1' shadowColor='000000' shadowAlpha='20'"
		       . " hoverCapBgColor='FFECAA' hoverCapBorderColor='F47E00'"
		       . " formatNumberScale='0' decimalPrecision='0' showvalues='0' animation='1'"
		       . " numdivlines='3' numVdivlines='0' lineThickness='5' rotateNames='1'>\n";
		return $graph;
	}
	
	function generateChartPie()
	{
		$graph = "<graph"
		       . " shadowThickness='6' showShadow='1' shadowXShift='1' shadowYShift='1' shadowColor='000000' shadowAlpha='50'"
		       . " pieBorderThickness='2' pieBorderAlpha='80' showValues='1' showNames='0' decimalPrecision='0' showPercentageInLabel='1'>\n";
		return $graph;
	}

	function generateChart($data, $type)
	{
		$r = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
		$graphType = $this->reportTypes[$type]['chart'];
		switch ($graphType)
		{
			case 'line':
				$size      = sizeof($data);
				$graphConf = $this->generateChartLine();
				$category  = $this->generateCategory($data, $size);
				$dataset   = $this->generateDataset( $data, $type, $size);
				$r        .= $graphConf.$category.$dataset;
				break;
			case 'pie':
				$graphConf = $this->generateChartPie();
				$set       = $this->generateSet($data, $type);
				$r        .= $graphConf.$set;
				break;
			default:
				$graphConf = '';
				break;
		}
		if (empty($graphConf))
			return false;
		
		$r .= "</graph>\n";
		
		return $r;
	}

}

?>