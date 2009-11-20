<?php

/**
 * Draw FusionCharts
 *
 * @package elFusionChart
 * @version 0.5
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */
class elFusionChart
{
	var $colors = array(
		'058dc7', '50b432', 'ed561b', 'edef00',
		'24cbe5', '64e572', 'ff9655', 'fff263',
		'6af9c4', 'b2deff', 'ffc880', 'ffffa0',
		'cccccc', '999999', '666666', '333333',
		'cccccc', 'cccccc', 'cccccc', 'cccccc',
		'cccccc', 'cccccc', 'cccccc', 'cccccc'
		);
	var $_graphType = array(
		'line'   => 'Line2D',
		'pie'    => 'Pie2D',
		'column' => 'Column2D',
		'multiline'   => 'MSLine2D'
		);
	var $xml;
	var $width  = 480;
	var $height = 320;
	
	function __construct()
	{
		$this->xml = new elFusionChartXML;
		$this->setColors();
		elAddjs('FusionCharts.js',    EL_JS_CSS_FILE);
		elAddjs('FusionChartsDOM.js', EL_JS_CSS_FILE);
	}

	function elFusionChart()
	{
		$this->__construct();
	}

	/**
	 * Return ready to use html code with graph and info
	 *
	 * @param string  $type must be one from _graphType
	 * @param array   $data 
	 * @param integer $width 
	 * @param integer $height 
	 * @return string ready to use HTML code with graph
	 */
	function graph($type, $data, $width = null, $height = null, $param)
	{
		if (($width != null) and ($height != null))
		{
			$this->width  = $width;
			$this->height = $height;
		}
		$r  = '<fusioncharts swfPath="'.EL_BASE_URL.'/style/images/fusionchart/"'
		    . ' chartType="'.$this->_graphType[$type].'"'
		    . ' width="'.$this->width.'" height="'.$this->height.'">'."\n";
		$r .= "\t<data><!--[CDATA[\n";
		$r .= $this->xml->generateXML($type, $data, $param);
		$r .= "\t]]--></data>\n";
		$r .= "</fusioncharts>\n";
		return $r;
	}
	
	function setColors($colors)
	{
		if (isset($colors))
			$this->colors = $colors;
		$this->xml->colors = $this->colors;
		return true;
	}
}

/**
 * XML output for FusionCharts
 *
 * @package elFusionChartXML
 * @version 0.5
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */
class elFusionChartXML
{
	var $colors;	
	var $_defaultParam = array(
		'shadowThickness' => '6',
		'showShadow'      => '1',
		'shadowXShift'    => '1',
		'shadowYShift'    => '1',
		'shadowColor'     => '000000',
		'shadowAlpha'     => '50',
		'hoverCapSepChar' => ' - '
		);
	var $_myParam = array();
	
	function _generateParam($p = null)
	{
		$r = '';
		$param = array();
		foreach ($this->_defaultParam as $k => $v)
			$param[$k] = $v;
		if (is_array($p))
			foreach ($p as $k => $v)
				$param[$k] = $v;
		foreach ($this->_myParam as $k => $v)
			$param[$k] = $v;
		foreach ($param as $k => $v)
			$r .= $k."='".$v."' ";
		return $r;
	}
	
	function _generateSingleData($data, $type)
	{
		$c = 0;
		$r = '';
		$color = '';
		foreach ($data as $name => $value)
		{
			if ($this->colors != false)
				$color = " color='".$this->colors[$c]."'";
			list($dim, $title) = explode('=', $node['TITLE']);
			$r .= "\t<set".$color." name='".$name."' value='".$value."' />\n";
			$c++;
		}
		return $r;
	}

	// function _generateDataset($data, $type, $size)
	// {
	// 	$c = 0;
	// 	$r = '';
	// 	$anchor = ($size > 30 ? 0 : 1);
	// 	$line = 3;
	// 	if ($size > 100)
	// 		$line = 2;
	// 	foreach ($this->reportTypes[$type]['metrics'] as $metric)
	// 	{
	// 		$color = $this->metricColors[$c];
	// 		$r .= "\t<!-- ".$metric." -->\n";
	// 		$r .= "\t<dataset color='".$color."' showAnchors='".$anchor."' alpha='100' lineThickness='".$line."'>\n";
	// 		foreach ($data as $node)
	// 		{
	// 			$r .= "\t\t<set value='".$node[$metric]."' />\n";
	// 		}
	// 		$r .= "\t</dataset>\n";
	// 		$c++;
	// 	}			
	// 	return $r;
	// }

	// function _generateCategory($data, $size)
	// {
	// 	$c = '';
	// 	$e = 30;
	// 	$i = 0;
	// 	$skip = 0;
	// 	if ($size > $e)
	// 	{
	// 		$skip = round($size / $e);
	// 	}
	// 	foreach ($data as $node)
	// 	{
	// 		if ($i >= $skip)
	// 		{
	// 			$i = 0;
	// 			$show = true;
	// 		}
	// 		else
	// 		{
	// 			$show = false;
	// 		}
	// 		list($dim, $title) = explode('=', $node['TITLE']);
	// 		$c .= "\t\t<category name='".$title."' showName='".($show ? 1 : 0)."' />\n";
	// 		$i++;
	// 	}
	// 	$r = "\t<categories>\n".$c."\t</categories>\n";
	// 	return $r;
	// }
	
	// function _generateChartLine()
	// {
	// 	$param = array(
	// 		'shadowThickness' => '4',
	// 		'shadowAlpha'     => '20'
	// 		);
	// 	$graph = "<graph " . $this->_generateParam($param)
	// 	       . " formatNumberScale='0' decimalPrecision='0' showvalues='0' animation='1'"
	// 	       . " numdivlines='3' numVdivlines='0' lineThickness='5' rotateNames='1'>"
	// 	       . "\n";
	// 	return $graph;
	// }
	
	function _generateSingleGraph()
	{
		$param = array(
			'pieBorderThickness'    => '2',
			'pieBorderAlpha'        => '80',
			'showValues'            => '1',
			'showNames'             => '1',
			'decimalPrecision'      => '0',
			'formatNumber'          => '1',
			'formatNumberScale'     => '0',
			'showPercentageInLabel' => '1'
			);
		$graph = "<graph " . $this->_generateParam($param). " >\n";
		return $graph;
	}

	function generateXML($type, $data, $param)
	{
		$this->_myParam = $param;
		$r = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
		// $graphType = $this->reportTypes[$type]['chart'];
		$graphConf = '';
		switch ($type)
		{
			// case 'line2':
			// 	$size      = sizeof($data);
			// 	$graphConf = $this->_generateChartLine();
			// 	$category  = $this->_generateCategory($data, $size);
			// 	$dataset   = $this->_generateDataset( $data, $type, $size);
			// 	$r        .= $graphConf.$category.$dataset;
			// 	break;
			case 'line':
			case 'column':
			case 'pie':
				$graphConf = $this->_generateSingleGraph();
				$set       = $this->_generateSingleData($data, $type);
				$r        .= $graphConf.$set;
				break;
			default:
				break;
		}
		if (empty($graphConf))
			return false;
		
		$r .= "</graph>\n";
		
		return $r;
	}

}