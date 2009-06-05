<?
 /*****************************************************************
 * 
 * Бесплатный PHP скрипт подсчета статистики сайта WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/

 error_reporting  (E_ALL);//E_ERROR | E_PARSE);

 //include("./includes/config.php");
 include("./class/sql.class.php");
 include("./class/core.class.php");
 include("./class/graph.class.php");

 reset ($_GET);

 while (list($key, $value)=each($_GET)) {
	$$key=htmlspecialchars(trim(substr($value, 0, 255)));
	$GLOBALS[$key]=htmlspecialchars(trim(substr($value, 0, 255)));
 }

 // Ядро системы
 $core=&new core();
 $core->begin =  $begin== "" ? date("d-m-Y", time()) : $begin;
 $core->end = $end== "" ? date("d-m-Y", time()) : $end;
 $core->type = $type;
 $core->type_gr = $type_gr;
 $core->init();
 $GLOBALS['core'] = & $core;

//elPrintR($core);
 if (!count($core->graph_data_array)) {
	get_pigel();
	exit;
 }
 //elPrintR($GLOBALS['core']->graph_data_array);


 $scale = 1.18;
 $barmode = "side";
 $legend = true;

  switch ($core->type_gr) {
	case "b1":
		$gr_type = "bargraph";
	break;
	case "b2":
		$gr_type = "bargraph2";
		$legend = false;
		$scale = 1.16;
	break;
	case "b3":
		$gr_type = "bargraph";
		$barmode = "overwrite";
	break;
	case "l":
		$gr_type = "linepoints";
	break;
	case "a":
		$gr_type = "areagraph";
	break;
 }

 list($min_t, $max_t) = getminmax($core->graph_data_array);
 list($min, $max) = rounded($min_t, $max_t);

 if ($core->gbl_config['gr_3d']) { $gr_3d = true; } else { $gr_3d = false; }

 phpplot(array(
	"cubic"=>$gr_3d,
	"ttf"=> '/class/font/'.$core->gbl_config['ttffont'].'_bold',
	"yrange"=> array($min, $max, 10),
	//"ymarkset"=> array("-40", "-30", "-20", "-10", "0", "10", "20"),
	"title_textsize"=> 10,
	"zeroline"=> false,
	"zeroaxis"=> false,
	"yaxis_marksize"=> 8,
	"xaxis_marksize"=> 8,
	"xaxis_markttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"yaxis_markttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"yaxis_labelttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"yaxis_labelsize"=> 8,
	"legend_size"=> 8,
	"yaxis_labelpos"=> "center",
	"yaxis_labelshift"=> array(0,0),
	"xaxis_labelttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"xaxis_labelsize"=> 8,
	"xaxis_labelpos"=> "center",
	"xaxis_labeldegree"=> 0,
	"xaxis_labelshift"=> array(0,0),
	"box_showbox"=> true,
	"grid"=> true,
	"grid_xgrid" => false,
	"title_text"=> "",
	"yaxis_labeltext"=> "",
	"xaxis_labeltext"=> "",
	"lepos"=> array(50,30),
	"ledir"=> "y",
	"legend_type" => 10,
	"legend_width" => 5,
	"legend" => $legend,
	"size"=> array($core->gbl_config['gr_w'], $core->gbl_config['gr_h']),
	"box_scale"=> $scale,
	"bargraph_barmode"=> $barmode,
	"piegraph_showmark"=> true,
	"piegraph_showlabel"=> true,
	"piegraph_marksize"=> 8,
	"piegraph_markttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"piegraph_thickness"=> 20,
 ));

 
 phpdata($core->graph_data_array);

 
 
 
 phpdraw($gr_type,array(
 	"drawsets" => $core->graph_num_array,
	"showpoint"=> false,
	"linewidth"=> $core->gbl_config['linesh'],
	"filled_filled"=> true,
	"legend"=> $core->graph_legend_array,
	"barspacing"=> 8,
	"showvalue"=> false,
	"legendsize"=> 8,
	"legendttf"=> '/class/font/'.$core->gbl_config['ttffont'],
	"legendscale"=> 1
 ));
//echo 'OK - 2'; exit;
 phpshow();
 exit;

 function getminmax($array) {

	foreach ($array as $key => $val) {

		if ($key) {
			$temp_array[] = max($val);
			$temp_array[] = min($val);
		}
	}

	$max = max($temp_array);
	$min = min($temp_array);

	$max = (int)$max;
	$min = (int)$min;

	return array($min, $max);
 }

 function rounded($min, $max) {

	if ($min > 0 ) { 
		while (!is_int($min / 10)) { $min--; }
	} else {
		$min = 0;
	}

	if ($max > 10 ) { 
		while (!is_int($max / 10)) { $max++; }
	} else {
		$max = 10;
	}

	return array($min, $max);
 }

 function get_pigel() {

 	$im = ImageCreate(1,1);
	ImageColorAllocate($im, 255,255,255);
	ImagePNG($im);
	ImageDestroy($im);
	return false;
 }
?>