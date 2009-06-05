<?
 /*****************************************************************
 * Класс построения графиков WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/

 $fontpath = realpath("./");
 if (!$fontpath) $fontpath = ".";
 
 $GLOBALS['fontpath'] = $fontpath;
 
 $RGB=array("white" => array(255, 255, 255),
 	"black" => array(100, 100, 100),
 	"gray" => array(0x7F, 0x7F, 0x7F),
 	"lgray" => array(0xBF, 0xBF, 0xBF),
 	"egray" => array(0xDD, 0xDD, 0xDD),
 	"bg" => array(247,247, 247),
	"col1" => array(255,200,0),
	"col2" => array(180,40,40),
	"col3" => array(200,200,200),
	"col4" => array(245,245,245),
	"col5" => array(120,140,170),
	"col6" => array(255,255,255),
	"col7" => array(250,240,200),
	"col8" => array(250,230,060),
	"col9" => array(230,135,135),
	"col10" => array(240,125,120),
	"col11" => array(250,230,50),
	"col12" => array(250,130,120),
	"col13" => array(170,190,220),
	"col14" => array(250,150,100),
	"col15" => array(30,140,160),
	"col16" => array(190,225,220),
	"col20" => array(240,240,240),
	"col21" => array(220,220,220),
	"col22" => array(180,180,180),
	); 
$GLOBALS['RGB'] = $RGB;
 
 function vhplot($a = '') { phpplot ($a); }
 function vhdata(&$a) { phpdata ($a); }
 function vhdraw($n, $a) { phpdraw($n, $a); }
 function vhshow($f = "") { phpshow ($f); }
 function vhshowgif($f = "") { phpshow($f, 'gif'); }
 function phpshowgif($f = "") { phpshow($f, 'gif'); }
 function vhshowpng($f = "") { phpshow($f, 'png'); }
 function vhshowjpg($f = "") { phpshow($f, 'jpg'); }

 function phpplot($a = "") {

	 $name="HZplot";
	 $GLOBALS["_globalsettings"]=array();
	 $GLOBALS["$name"]=&new PHPplot;
	 $GLOBALS["$name"]->start($a);
	 return $name;
 }

 function phpdata($a) {

	 global $HZplot;
	 $HZplot->datasets=$a; 
	 $HZplot->background= plotcolor($HZplot->background); 
 }

 function pregame() {

	 global $HZplot;

	 $_newbox=new Pplot_box;
	 $_newbox->layout();
	 if ($HZplot->options["box"] || $HZplot->cubic) {
		 $_newbox->show();
	 }
	 $HZplot->options["box"]=false;
	 $_newgrid=new Pplot_grid;
	 if ($HZplot->options["grid"]) {
		 $_newgrid->layout();
	 } else {
		 $_newgrid->layout(array("xgrid" => false, "ygrid" => false));
	 }
	 $_newgrid->show();
	 $HZplot->options["grid"]=false;
	 if ($HZplot->options["title"]) {
		 $_newtitle=new Pplot_title;
		 $_newtitle->layout();
		 $_newtitle->show();
		 $HZplot->options["title"]=false;
	 }
	 if ($HZplot->options["legend"]) {
		 $_newlegend=new Pplot_legend;
		 $_newlegend->layout();
	 }
	 $HZplot->pregame=true;
 }

 function phpdraw($n, $a) {

	 global $HZplot; 
	 
	 $name=md5(microtime());
	 eval ("\$GLOBALS[\"" . $name . "\"] = new Pplot_" . $n . ";");
	 
	 $GLOBALS["$name"]->layout($a);
	 
	 $HZplot->insert($name);
	 return $name;
 }

 function phpshow($f = "", $format = 'png') {

	 $GLOBALS["HZplot"]->format=$format;
	 $GLOBALS["HZplot"]->show($f);
 }

 function plotrgb($n) {

	 global $RGB;

	 if (isset($RGB["$n"])) {
		 return $RGB["$n"];
	 }
	 return false;
 }

 function aplus($a) {

	 $b=array();
	 if (is_array($a)) {
		 while (list(, $v)=each($a)) {
			 if (is_array($v)) {
				 while (list($k, $val)=each($v)) {
					 $b[$k]+=$val;
				 }
			 } else {
				 $b[]=$v;
			 }
		 }
		 return $b;
	 } else {
		 return $a;
	 }
 }

 function amerge($a) {

	 $b=array();
	 if (is_array($a)) {
		 while (list(, $v)=each($a)) {
			 if (is_array($v)) {
				 while (list(, $val)=each($v)) {
					 $b[]=$val;
				 }
			 } else {
				 $b[]=$v;
			 }
		 }
		 return $b;
	 } else {
		 return $a;
	 }
 }

 function stralign($a) {

	 $dot=0;
	 if (is_array($a)) {
		 while (list(, $v)=each($a)) {
			 $v = strrev($v);
			 if (($pos=strpos($v, '.')) >= 0) {
				 $dot=$pos > $dot ? $pos : $dot;
			 }
		 }
		 if ($dot == 0) {
			 return $a;
		 }
		 reset ($a);
		 if ($dot > 8) {
			 while (list($i, $v)=each($a)) {
				 $a[$i]=substr($v, 0, 4);
			 }
		 } else {
			 while (list($i, $v)=each($a)) {
				 if (strpos($v, '.') > 0) {
					 $v=$v . '0000000000000';
				 } else {
					 $v=$v . '.0000000000000';
				 }
				 $pos=strpos($v, '.');
				 $a[$i]=substr($v, 0, $pos + $dot + 1);
			 }
		 }
	 }
	 return $a;
 }

 function graphsettings(&$n, $a) {

	 while (list($key, $val)=each($a)) {
		 if (!is_object($n)) {
			 eval ("\$GLOBALS[\"" . $n . "\"]->" . $key . " = \$val ;");
		 } else {
			 eval ("\$n->" . $key . " = \$val ;");
		 }
	 }
 }

 function addprop($n, $s) {

	 $name="Pplot_" . $n;
	 if (empty($GLOBALS["_globalsettings"]["$name"])) {
		 $a=array();
	 } else {
		 $a=$GLOBALS["_globalsettings"]["$name"];
	 }
	 $c=array();
	 while (list($k, $v)=each($a)) {
		 $c["$k"]=$v;
	 }
	 while (list($k, $v)=each($s)) {
		 $c["$k"]=$v;
	 }
	 $GLOBALS["_globalsettings"]["$name"]=$c;
 }

 function plotcolor($n) {

	 global $HZplot;

	 if ($c=plotrgb($n)) {
		 $ix=ImageColorExact($HZplot->image, $c[0], $c[1], $c[2]);

		 if ($ix < 0) {
			 $ix=ImageColorAllocate($HZplot->image, $c[0], $c[1], $c[2]);
		 }
		 return $ix;
	 }
	 if (ereg("([0-9]{1,})", $n, $regs)) {
		 return $n;
	 }
	 return plotcolor("white");
 }

 function drawaxis() {

	 global $HZplot;

	 if ($HZplot->options["yaxis"]) {
		 $_newyaxis=new Pplot_yaxis;
		 if ($HZplot->yextend) {
			 graphsettings($_newyaxis, array("tick" => false, "cubic" => false, "mark" => true, "label" => true));
		 } else {
			 graphsettings($_newyaxis, array("mark" => true, "label" => true));
		 }
		 $_newyaxis->layout();
		 $_newyaxis->show();
	 }

	 if ($HZplot->options["xaxis"]) {
		 $_newxaxis=new Pplot_xaxis;

		 if (!$HZplot->zeroaxis) {
			 $HZplot->zeroline=false;
		 }
		 graphsettings($_newxaxis, array("mark" => true, "label" => true, "cubic" => false));
		 $_newxaxis->layout();
		 $_newxaxis->show();
	 }

	 if ($HZplot->options["y2axis"]) {
		 $_newy2axis=new Pplot_y2axis;
		 graphsettings($_newy2axis, array("mark" => true, "label" => true, "cubic" => false));
		 $_newy2axis->layout();
		 $_newy2axis->show();
	 }
 }

 function grange($a, $y2) {

	 global $HZplot;

	 $min=10000000.0;
	 $max=-10000000.0;
	 while (list(, $v)=each($a)) {
		 if ($v > $max) {
			 $max=$v;
		 }
		 if ($v < $min) {
			 $min=$v;
		 }
	 }
	 $max=$max > 0 ? $max : 0;
	 $min=$min > 0 ? 0 : $min;
	 $delta=$max - $min;
	 if ($delta == 0) {
		 $delta=1;
		 $max=$min + $delta;
	 }
	 $range=pow(10, floor(log10($delta)));
	 if ($min * $max == 0) {
		 if ($max > 0) {
			 for ($i=1; $i <= 10; ++$i) {
				 $num = $i * $range;
				 if ($num > $max) {
					 $max=$num;

					 $ticknum=$i;
					 break;
				 }
			 }
		 }
		 if ($min < 0) {
			 for ($i=1; $i <= 10; ++$i) {
				 $num = -$i * $range;
				 if ($num < $min) {
					 $min=$num;
					 $ticknum=$i;
					 break;
				 }
			 }
		 }
	 }
	 if ($min * $max < 0) {
		 $range=pow(10, floor(log10($delta)) - 1);
		 for ($i=5; $i <= 100; $i+=5) {
			 $num = $i * $range;
			 if ($num > $max) {
				 $max=$num;
				 $maxt=(int)($i / 5);
				 break;
			 }
		 }
		 for ($i=5; $i <= 100; $i+=5) {
			 $num = -$i * $range;
			 if ($num < $min) {
				 $min=$num;
				 $mint=(int)($i / 5);
				 break;
			 }
		 }
		 $ticknum=$mint + $maxt;
	 }

	 if ($y2 && $HZplot->yrange) {
		 $ymin=$HZplot->yrange[0];
		 $ymax=$HZplot->yrange[1];
		 $ytick=$HZplot->yrange[2];
		 $ydel=($ymax - $ymin) / $ytick;
		 $ymaxt=$ymax / $ydel;
		 $ymint=-$ymin / $ydel;
		 $y2min=$min;
		 $y2max=$max;
		 $ticknum=$ytick;
		 if ($ymin * $ymax < 0) {
			 if ($y2min * $y2max < 0) {
				 while ($ymaxt * $range < $y2max) {
					 $range=$range * 2;
				 }
				 while (-$ymint * $range > $y2min) {
					 $range=$range * 2;
				 }
				 $max=$ymaxt * $range;
				 $min=-$ymint * $range;
			 } else {
				 if ($y2max > 0) {
					 while ($ymaxt * $range < $y2max) {
						 $range=$range * 2;
					 }
					 $max=$ymaxt * $range;
					 $min=-$ymint * $range;
				 } else {
					 while (-$ymint * $range > $y2min) {
						 $range=$range * 2;
					 }
					 $max=$ymaxt * $range;
					 $min=-$ymint * $range;
				 }
			 }
		 } else {
			 if ($y2min * $y2max < 0) {
				 if ($ymax > 0) {
					 while ($ymaxt * $range < $y2max) {
						 $range=$range * 2;
					 }
					 $max=$ymaxt * $range;
					 $min=0;
				 } else {
					 while (-$ymint * $range > $y2min) {
						 $range=$range * 2;
					 }
					 $max=0;
					 $min=-$ymint * $range;
				 }
			 } else {
				 if ($ymax == 0) {
					 if ($y2max == 0) {
						 while (-$ymint * $range > $y2min) {
							 $range=$range * 2;
						 }
						 $min=-$ymint * $range;
					 } else {
						 $max=0;
						 $min=-1;
					 }
				 } else {
					 if ($y2max == 0) {
						 $max=1;
						 $min=0;
					 } else {
						 while ($ymaxt * $range < $y2max) {
							 $range=$range * 2;
						 }
						 $max=$ymaxt * $range;
					 }
				 }
			 }
		 }
	 }

	 return array($min, $max, $ticknum);
 }

 
 class PHPplot {

	 var $classname = "PHPplot";
	 var $image = false;
	 var $margin = array(0, 0, 0, 0);
	 var $children = array();
	 var $interlace = false;
	 var $size = array(400, 300);
	 var $ttf = false;
	 var $background = "white";
	 var $transparent = false;
	 var $options = array("box" => true, "grid" => true, "title" => true, "yaxis" => true, "xaxis" => true, "y2axis" => false, "legend" => true);
	 var $pregame = false;
	 var $datasets = array();
	 var $xmarkset = false;
	 var $ymarkset = false;
	 var $y2markset = false;
	 var $xrange = false;
	 var $yrange = false;
	 var $y2range = false;
	 var $xextend = true;
	 var $yextend = false;
	 var $cubic = false;
	 var $zeroline = true;
	 var $zero = true;
	 var $stack = false;
	 var $zeroaxis = false;
	 var $length = 0;
	 var $height = 0;
	 var $colorset = array("col1", "col2", "col3", "col4", "col5", "col6", "col7", "col8", "col9", "col10");
	 var $lepos = false;
	 var $ledir = false;
	 var $format = false;

 function start($param = "") {

	 $this->settings($param); 
	 $this->image=ImageCreate($this->size[0], $this->size[1]);
 }

 function insert($c) { $this->children[]=$c; }

 function settings($param) {

	 if (empty($param)) {
		 return;
	 }
	 while (list($key, $val)=each($param)) {
		 if (ereg("(.*)_(.*)", $key, $regs)) {
			 addprop("$regs[1]", array($regs[2] => $val));

			 $this->options["$regs[1]"]=true;
			 continue;
		 }

		 if (isset($this->options["$key"])) {
			 $this->options["$key"]=$val;
			 continue;
		 }
		 eval ("\$this->" . $key . " = \$val ;");
	 }

	 $this->options["y2axis"]=false;
 }

 function show($f = "") {

	 global $HZplot;

	 reset ($this->children);

	 while (list($key, $ch)=each($this->children)) {
		 $GLOBALS["$ch"]->show();
	 }

	 drawaxis();

	 if ($HZplot->transparent) {
		 ImageColorTransparent($HZplot->image, $HZplot->background);
	 }

	 ImageInterlace($HZplot->image, $HZplot->interlace);

	 if (!empty($f)) {
		 if (function_exists('imagepng') && $HZplot->format == 'png') {
			 ImagePNG($HZplot->image, $f);
		 } else if ($HZplot->format == 'gif') {
			 ImageGIF($HZplot->image, $f);
		 } else {
			 ImageJPEG($HZplot->image, $f, 95);
		 }
	 } else {
		 if (function_exists('imagepng') && $HZplot->format == 'png') {
			 Header ("Content-type: image/png");
			 ImagePNG ($HZplot->image);
		 } else if ($HZplot->format == 'gif') {
			 Header ("Content-type: image/gif");
			 ImageGIF ($HZplot->image);
		 } else {
			 Header ("Content-type: image/jpeg");
			 ImageJPEG($HZplot->image, "", 95);
		 }
	 }

	 ImageDestroy ($HZplot->image);
	 unset ($HZplot);
 }

 function parsedata($b, $y2 = "") {

	 global $HZplot;

	 $totset=count($b);
	 $a=$this->datasets;

	 if (!$this->xmarkset) {
		 $this->xmarkset=$a[0];
	 }

	 $nsets=count($a);
	 $tmp=$b[$totset - 1];
	 $y2set=$a["$tmp"];

	 if ($nsets < 2) {
		 die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка получения данных для графика");
	 }

	 $npnts=count($this->xmarkset);

	 for ($i=0; $i < $nsets; ++$i) {
		 if (count($a["$i"]) != $npnts) {
			 die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка получения данных для графика");
		 }
	 }

	 if (!$this->yrange) {
		 $yd=array();

		 if ($totset > 1) {
			 for ($i=0; $i < $totset - 1; ++$i) {
				 $yd[]=$a["$b[$i]"];
			 }

			 if (!$y2) {
				 $tmp=$b[$totset - 1];
				 $yd[]=$a["$tmp"];
			 }

			 if ($HZplot->stack) {
				 $yd=aplus($yd);
			 }
			 $yd=amerge($yd);
		 } else {
			 $yd=$a["$b[0]"];
		 }
		 if ($this->ymarkset) {
			 $tmp=$this->ymarkset;

			 $tmpc=count($tmp) - 1;
			 $this->yrange=array($tmp[0], $tmp[$tmpc], $tmpc);
		 } else {
			 $this->yrange=grange($yd, false);
		 }
	 }

	 if (!$this->ymarkset) {
		 $delty=($this->yrange[1] - $this->yrange[0]) / $this->yrange[2];

		 for ($i=0; $i <= $this->yrange[2]; ++$i) {
			 $num = $this->yrange[0] + $i * $delty;

			 if (log10(abs($num)) < -8) {
				 $num=0;
			 }
			 $this->ymarkset[$i]=$num;
		 }
		 $this->ymarkset=stralign($this->ymarkset);
	 }

	 if ($y2) {
		 if (empty($this->y2range)) {
			 if ($this->y2markset) {
				 $tmp=$this->y2markset;

				 $tmpc=count($tmp) - 1;
				 $this->y2range=array($tmp[0], $tmp[$tmpc], $tmpc);
			 } else {
				 $this->y2range=grange($y2set, true);
			 }
		 }
		 if (!$this->y2markset) {
			 $delty2=($this->y2range[1] - $this->y2range[0]) / $this->y2range[2];

			 for ($i=0; $i <= $this->y2range[2]; ++$i) {
				 $num2 = $this->y2range[0] + $i * $delty2;

				 if (log10(abs($num2)) < -8) {
					 $num2=0;
				 }
				 $this->y2markset[$i]=$num2;
			 }
			 $this->y2markset=stralign($this->y2markset);
		 }
	 }
 }
 }

 class Pplot_base {

	 var $classname = "Pplot_base";
	 var $text = "My Plot";
	 var $textcolor = "black";
	 var $textsize = 12;
	 var $textfont = 2;
	 var $color = "black";
	 var $pos = array(0, 0);
	 var $shift = array(0, 0);
	 var $scale = 1;
	 var $ttf = false;

 function position($a = "") {

	 global $HZplot;

	 $cname=$this->classname;

	 if (!empty($GLOBALS["_globalsettings"]["$cname"]) && is_array($GLOBALS["_globalsettings"]["$cname"])) {
		 $pa=$GLOBALS["_globalsettings"]["$cname"];
		 while (list($k, $v)=each($pa)) {
			 eval ("\$this->" . $k . " = \$v ;");
		 }
	 }

	 if (is_array($a)) {
		 while (list($k, $v)=each($a)) {
			 eval ("\$this->" . $k . " = \$v ;");
		 }
	 }

	 $this->textcolor=plotcolor($this->textcolor);
	 $this->color=plotcolor($this->color);
	 $this->pos[0]+=$this->shift[0];
	 $this->pos[1]+=$this->shift[1];
 }
 }

 class Pplot_text  extends Pplot_base {

	 var $classname = "Pplot_text";
	 var $border = false;
	 var $degree = 0;

 function geometry() {

	 global $fontpath, $HZplot;

	 if ($this->ttf || $HZplot->ttf) {
		 if (!$this->ttf) {
			 $this->ttf=$HZplot->ttf;
		 }

		 $font=$fontpath . $this->ttf . ".ttf";  
		 //$text = iconv("Windows-1251","UTF-8", $this->text);
		 //$text = iconv("UTF-8", "Windows-1251", $this->text);
		 //elDebug('conv ----- '.$text);
		 //elDebug('orig ----- '.$this->text);
		 $fsize=ImageTTFBBox($this->textsize, 0, $font, $this->text );
		 
		 $fw=$fsize[2] - $fsize[0]+4;
		 $fh=$fsize[1] - $fsize[7]-4;
	 } else {
		 $fh=ImageFontHeight($this->textfont); 
		 $fw=ImageFontWidth($this->textfont) * strlen($this->text);
	 }

	 if ($this->border) {
		 $fw+=5;
		 $fh+=5;
	 }

	 return array($fw, $fh);
 }

 function layout($a = "") {

	 $this->position($a);

	 $this->geometry();
 }

 function show() {

	 global $HZplot, $fontpath;

	 list($tfw, $tfh)=$this->geometry();
	 if ($this->ttf) {
		 $font=$fontpath . $this->ttf . ".ttf";
		 ImageTTFText($HZplot->image, $this->textsize, $this->degree, $this->pos[0], $this->pos[1], $this->textcolor, $font, $this->text);
		 $x1=$this->pos[0];
		 $y1=$this->pos[1] - $tfh;
		 $x2=$this->pos[0] + $tfw;
		 $y2=$this->pos[1];
	 } else {
		 if ($this->degree > 0) {
			 $this->pos[0]-=($tfh - 3);
			 ImageStringUp($HZplot->image, $this->textfont, $this->pos[0], $this->pos[1], $this->text, $this->textcolor);
			 $x1=$this->pos[0] - 3;
			 $y1=$this->pos[1] - $tfw + 3;
			 $x2=$x1 + $tfh;
			 $y2=$y1 + $tfw + 3;
		 } else {
			 $this->pos[1]-=($tfh - 3);
			 ImageString($HZplot->image, $this->textfont, $this->pos[0], $this->pos[1], $this->text, $this->textcolor);
			 $x1=$this->pos[0] - 3;
			 $y1=$this->pos[1] - 3;
			 $x2=$x1 + $tfw;
			 $y2=$y1 + $tfh;
		 }
	 }

	 if ($this->border) {
		 ImageRectangle($HZplot->image, $x1, $y1, $x2, $y2, $this->textcolor);
	 }
 }
 }

 class Pplot_title extends Pplot_text {

	 var $classname = "Pplot_title";
	 var $location = "topcenter";
	 var $textfont = 4;
	 var $textsize = 12;

 function layout($a = "") {

	 global $HZplot;
	 $this->position($a);
	 $pa=$GLOBALS["_globalsettings"]["Pplot_title"];
	 if (!empty($pa["color"])) {
		 $this->textcolor=$this->color;
	 }
	 if (!empty($pa["pos"])) {
		 $this->pos[0]+=$this->shift[0];
		 $this->pos[1]+=$this->shift[1];
	 } else {
		 list($tw, $th)=$this->geometry();
		 $len=$HZplot->size[0] - $HZplot->margin[0] - $HZplot->margin[1];
		 $this->pos[0]+=round($HZplot->margin[0] + $len / 2 - $tw / 2);
		 if ($this->location != "topcenter") {
			 $this->pos[1]+=round($HZplot->size[1] - $th / 2);
		 } else {
			 $this->pos[1]+=round($HZplot->margin[2] - 0.5 * $th);
			 if ($HZplot->cubic) {
				 $this->pos[0]+=6;
				 $this->pos[1]+=-6;
			 }
		 }
	 }
 }
 }

 class Pplot_line extends Pplot_base {

	 var $classname = "Pplot_line";
	 var $start = array(0, 0);
	 var $end = array(0, 0);
	 var $cubic = false;
	 var $dash = false;
	 var $fillcolor = false;

 function layout($a = "") {

	 $this->position($a);

	 $this->start[0]+=$this->shift[0];
	 $this->start[1]+=$this->shift[1];
	 $this->end[0]+=$this->shift[0];
	 $this->end[1]+=$this->shift[1];
	 $this->fillcolor=plotcolor($this->fillcolor);
 }

 function show() {

	 global $HZplot;

	 if ($this->cubic) {
		 $x1=$this->start[0];

		 $y1=$this->start[1];
		 $x2=$this->end[0];
		 $y2=$this->end[1];
		 $x3=$x2 + 6;
		 $y3=$y2 - 6;
		 $x4=$x1 + 6;
		 $y4=$y1 - 6;

		 if ($this->fillcolor) {
			 ImageFilledPolygon($HZplot->image, array($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4), 4, $this->fillcolor);
		 }
		 ImagePolygon($HZplot->image, array($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4), 4, $this->color);
	 } else {
		 if ($this->dash) {
			 ImageDashedLine($HZplot->image, $this->start[0], $this->start[1], $this->end[0], $this->end[1], $this->color);
		 } else {
			 ImageLine($HZplot->image, $this->start[0], $this->start[1], $this->end[0], $this->end[1], $this->color);
		 }
	 }
 }
 }

 class Pplot_point  extends Pplot_base {

	 var $classname = "Pplot_point";
	 var $pointtype = 0;
	 var $pointwidth = 8;
	 var $filled = false;
	 var $showvalue = false;
	 var $valuefont = 3;
	 var $valuecolor = "black";
	 var $valuesize = 10;
	 var $valuettf = false;
	 var $textshift = array(7, -5);

 function geometry() {

	 $psize=round($this->pointwidth * $this->scale);

	 return array($psize, $psize);
 }

 function layout($a = "") { $this->position($a); }

 function show() {

	 global $HZplot;

	 list($psize, )=$this->geometry();
	 $half=round($psize / 2);
	 $cx=$this->pos[0];
	 $cy=$this->pos[1];

	 switch ($this->pointtype % 6) {
		 case 0:
			 $x1=$cx - $half;
			 $y1=$cy - $half;
			 $x2=$cx + $half;
			 $y2=$cy + $half;
			 ImageRectangle($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
			 break;
		 case 1:
			 ImageArc($HZplot->image, $cx, $cy, $psize, $psize, 0, 360, $this->color);
			 break;
		 case 2:
			 $pts=array();
			 $pts[]=$cx - $half;
			 $pts[]=$cy + $half;
			 $pts[]=$cx + $half;
			 $pts[]=$cy + $half;
			 $pts[]=$cx;
			 $pts[]=$cy - $half;
			 ImagePolygon($HZplot->image, $pts, 3, $this->color);
			 break;
		 case 3:
			 $pts=array();
			 $pts[]=$cx + $half;
			 $pts[]=$cy;
			 $pts[]=$cx;
			 $pts[]=$cy - $half;
			 $pts[]=$cx - $half;
			 $pts[]=$cy;
			 $pts[]=$cx;
			 $pts[]=$cy + $half;
			 ImagePolygon($HZplot->image, $pts, 4, $this->color);
			 break;
		 case 4:
			 $x1=$cx - $half;
			 $y1=$cy - $half;
			 $x2=$cx + $half;
			 $y2=$cy + $half;
			 ImageLine($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
			 $x1=$cx - $half;
			 $y1=$cy + $half;
			 $x2=$cx + $half;
			 $y2=$cy - $half;
			 ImageLine($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
			 break;
		 case 5:
			 $x1=$cx - $half;
			 $y1=$cy;
			 $x2=$cx + $half;
			 $y2=$cy;
			 ImageLine($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
			 break;
	 }

	 if ($this->filled) {
		 ImageFillToBorder($HZplot->image, $cx, $cy, $this->color, $this->color);
	 }

	 if ($this->showvalue) {
		 $vt=new Pplot_text;

		 $vt->layout(array("textfont" => $this->valuefont, "textsize" => $this->valuesize, "shift" => $this->textshift, "textcolor" => $this->valuecolor, "text" => $this->text, "pos" => array($cx, $cy), "ttf" => $this->valuettf));
		 $vt->show();
	 }
 }
 }

 class Pplot_box  extends Pplot_base {

	 var $classname = "Pplot_box";
	 var $showbox = false;
	 var $boxsize = "medium";
	 var $xscale = 1.0;
	 var $yscale = 1.0;
	 var $shadow = false;
	 var $length;
	 var $height;

 function layout($a = "") {

	 global $HZplot;

	 $this->position($a);
	 $pa=$GLOBALS["_globalsettings"]["Pplot_box"];
	 $gifx=$HZplot->size[0];
	 $gify=$HZplot->size[1];

	 if ($this->boxsize == "small") {
		 $scale=0.5 * $this->scale;
	 }

	 if ($this->boxsize == "medium") {
		 $scale=0.75 * $this->scale;
	 }

	 if ($this->boxsize == "big") {
		 $scale=0.85 * $this->scale;
	 }

	 $this->length=round($gifx * $scale * $this->xscale);
	 $this->height=round($gify * $scale * $this->yscale);

	 if (!empty($pa["pos"])) {
		 $this->pos[0]+=$this->shift[0];
		 $this->pos[1]+=$this->shift[1];
	 } else {
		 $this->pos[0]+=round(($gifx - $this->length) / 2);
		 $this->pos[1]+=round(($gify - $this->height) / 2);
	 }

	 $HZplot->margin[0]=$this->pos[0];
	 $HZplot->margin[1]=$gifx - $this->pos[0] - $this->length;
	 $HZplot->margin[2]=$this->pos[1];
	 $HZplot->margin[3]=$gify - $this->pos[1] - $this->height;
	 $HZplot->length=$this->length;
	 $HZplot->height=$this->height;

	 if ($HZplot->zero) {
		 $z=$HZplot->yrange;
		 if (!empty($z)) {
			 if (($z[0] * $z[1] < 0) || ($z[1] == 0)) {
				 $deltay=$this->height / $z[2];

				 $deltav=($z[1] - $z[0]) / $z[2];
				 for ($i=1; $i <= $z[2]; ++$i) {
					 $num = $z[0] + $i * $deltav;
					 if (log10(abs($num)) < -8) {
						 $HZplot->zero=$i * $deltay;
						 break;
					 }
				 }
			 }
		 }
	 }
 }

 function show() {

	 global $HZplot;

	 $x1=$this->pos[0];
	 $y1=$this->pos[1];
	 $x2=$this->pos[0] + $this->length;
	 $y2=$this->pos[1] + $this->height;

	 if ($HZplot->cubic) {
		 $this->showbox=false;
	 }

	 if ($this->showbox && !$HZplot->cubic) {
		 if ($this->shadow) {
			 $shadowclr=plotcolor("egray");

			 $sx1=$x1 + 6;
			 $sy1=$y1 - 6;
			 $sx2=$sx1 + $this->length;
			 $sy2=$y1;
			 ImageFilledRectangle($HZplot->image, $sx1, $sy1, $sx2, $sy2, $shadowclr);
			 $sx1=$x2;
			 $sx2=$sx1 + 6;
			 $sy2=$sy1 + $this->height;
			 ImageFilledRectangle($HZplot->image, $sx1, $sy1, $sx2, $sy2, $shadowclr);
		 }
		 ImageRectangle($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
	 }

	 if ($HZplot->cubic) {
		 $sx1=$x1;

		 $sy1=$y1;
		 $ex1=$x1 + $this->length;
		 $ey1=$sy1;
		 $nl=new Pplot_line;
		 $nl->layout(array("start" => array($sx1, $sy1), "end" => array($ex1, $ey1), "shift" => array(6, -6), "color" => $this->color));
		 $nl->show();
		 unset ($nl);
		 $sx2=$x1;
		 $sy2=$y1 + $this->height;
		 $ex2=$x1 + $this->length;
		 $ey2=$sy2;
		 $nl=new Pplot_line;
		 $nl->layout(array("start" => array($sx2, $sy2), "end" => array($ex2, $ey2), "shift" => array(6, -6), "color" => $this->color));
		 $nl->show();

		 if ($HZplot->zero > 1) {
			 $sy2=$HZplot->size[1] - round($HZplot->zero) - $HZplot->margin[3];
			 $ey2=$sy2;
		 }

		 $sx3=$x1;
		 $sy3=$y1 + $this->height;
		 $ex3=$x1;
		 $ey3=$y1;
		 $nl=new Pplot_line;
		 $nl->layout(array("start" => array($sx3, $sy3), "end" => array($ex3, $ey3), "cubic" => true, "color" => $this->color));
		 $nl->show();
		 unset ($nl);
		 $sx4=$x1 + $this->length;
		 $sy4=$y1 + $this->height;
		 $ex4=$sx4;
		 $ey4=$y1;
		 $nl=new Pplot_line;
		 $nl->layout(array("start" => array($sx4, $sy4), "end" => array($ex4, $ey4), "shift" => array(6, -6), "color" => $this->color));
		 $nl->show();
		 unset ($nl);
	 }
 }
 }

 class Pplot_grid  extends Pplot_base {

	 var $classname = "Pplot_grid";
	 var $xgrid = true;
	 var $ygrid = true;
	 var $xdash = false;
	 var $ydash = false;
	 var $color = "lgray";
	 var $mxgrid = 1;
	 var $mygrid = 1;

 function layout($a = "") { $this->position($a); }

 function show() {

	 global $HZplot;

	 $x1=$HZplot->margin[0];
	 $x2=$HZplot->size[0] - $HZplot->margin[1];
	 $y1=$HZplot->margin[2];
	 $y2=$HZplot->size[1] - $HZplot->margin[3];

	 $length=$HZplot->length;
	 $height=$HZplot->height;

	 $cntx=count($HZplot->xmarkset) - 1;

	 if ($HZplot->xextend) {
		 $cntx+=2;
	 }

	 $cnty=count($HZplot->ymarkset) - 1;

	 if ($HZplot->yextend) {
		 $cnty+=2;
	 }

	 $mcntx=$cntx * $this->mxgrid;
	 $mcnty=$cnty * $this->mygrid;
	 $deltax=$length / $mcntx;
	 $deltay=$height / $mcnty;
	 $cshift=array(0, 0);

	 if ($HZplot->cubic) {
		 $cshift=array(6, -6);
	 }

	 $nyaxis=new Pplot_yaxis;
	 $nyaxis->layout();

	 if ($this->ygrid) {
		 for ($i=1; $i < $mcnty; ++$i) {
			 if (($i % $nyaxis->tickskip) != 0) {
				 continue;
			 }

			 $sx=$x1;
			 $sy=$y2 - round($i * $deltay);
			 $ex=$sx + $length;
			 $ey=$sy;
			 $nl=new Pplot_line;
			 $nl->layout(array("start" => array($sx, $sy), "end" => array($ex, $ey), "color" => $this->color, "dash" => $this->ydash, "shift" => $cshift));
			 $nl->show();
			 unset ($nl);
		 }
	 }

	 if ($HZplot->yaxis) {
		 $nyaxis->show();
	 }

	 unset ($nyaxis);

	 $naxis=new Pplot_xaxis;
	 $naxis->layout(array("tick" => false));

	 if ($this->xgrid) {
		 for ($i=1; $i < $mcntx; ++$i) {
			 if (($i % $naxis->tickskip) != 0) {
				 continue;
			 }

			 $sx=$x1 + round($i * $deltax);
			 $sy=$y2;
			 $ex=$sx;
			 $ey=$y1;
			 $nl=new Pplot_line;
			 $nl->layout(array("start" => array($sx, $sy), "end" => array($ex, $ey), "color" => $this->color, "dash" => $this->xdash, "shift" => $cshift));
			 $nl->show();
			 unset ($nl);
		 }
	 }

	 if ($HZplot->zeroline && $HZplot->zero > 1) {
		 $naxis->show();
	 } elseif ($HZplot->zeroline && $HZplot->zero <= 1) {
		 if ($HZplot->options["xaxis"]) {
			 $naxis->show();
		 }
	 }

	 unset ($naxis);

	 if (!$HZplot->zeroaxis) {
		 $_newxaxis=new Pplot_xaxis;

		 if (!$HZplot->zeroaxis) {
			 $HZplot->zeroline=false;
		 }

		 graphsettings($_newxaxis, array("mark" => true, "label" => true, "cubic" => $HZplot->cubic));
		 $_newxaxis->layout();

		 if ($HZplot->options["xaxis"]) {
			 $_newxaxis->show();
		 }
		 unset ($_newxaxis);
	 }
 }
 }

 class Pplot_axis  extends Pplot_base {

	 var $classname = "Pplot_axis";
	 var $axis = true;
	 var $tick = true;
	 var $ticklength = 4;
	 var $tickskip = 1;
	 var $mark = false;
	 var $markcolor = "black";
	 var $markfont = 2;
	 var $markttf = false;
	 var $marksize = 8;
	 var $markskip = 1;
	 var $markdegree = 0;
	 var $markshift = array(0, 0);
	 var $label = false;
	 var $labelfont = 3;
	 var $labeltext = "X axis";
	 var $labelcolor = "black";
	 var $labelsize = 8;
	 var $labelpos = "center";
	 var $labeldegree = "0";
	 var $labelshift = array(0, 0);
	 var $labelttf = false;
	 var $direction = "x";
	 var $fillcolor = "lgray";
	 var $cubic = true;
	 var $data;
	 var $start;
	 var $end;

 function layout($a = "") {

	 global $HZplot;

	 $this->position($a);
	 $this->start[0]+=$this->shift[0];
	 $this->start[1]+=$this->shift[1];
	 $this->end[0]+=$this->shift[0];
	 $this->end[1]+=$this->shift[1];
	 $this->color=plotcolor($this->color);
	 $this->textcolor=plotcolor($this->textcolor);
	 $this->fillcolor=plotcolor($this->fillcolor);

	 if (!$HZplot->cubic) {
		 $this->cubic=false;
	 }

	 $cname=$this->classname;
	 $pa=$GLOBALS["_globalsettings"]["$cname"];

	 if (!empty($pa["textcolor"])) {
		 $this->markcolor=$this->textcolor;
		 $this->labelcolor=$this->textcolor;
	 }

	 if ($HZplot->zeroaxis) {
		 $HZplot->zeroline=true;
	 }
 }

 function show() {

	 global $HZplot;

	 if ($this->direction == "x") {
		 if ($HZplot->zero > 1 && $HZplot->zeroline) {
			 $this->start[1]=$HZplot->size[1] - round($HZplot->zero) - $HZplot->margin[3];
			 $this->end[1]=$this->start[1];
		 }
	 }

	 $y2shift=array(0, 0);

	 if ($HZplot->cubic && $this->direction == "y2") {
		 $y2shift=array(6, -6);
	 }

	 if ($this->axis) {
		 $nl=new Pplot_line;

		 if ($this->direction == "y2") {
			 $nl->layout(array("start" => $this->start, "end" => $this->end, "color" => $this->color, "shift" => $y2shift));
		 } else {
			 $nl->layout(array("start" => $this->start, "end" => $this->end, "color" => $this->color, "cubic" => $this->cubic, "fillcolor" => $this->fillcolor));
		 }

		 $nl->show();
		 unset ($nl);
	 }

	 $x1=$HZplot->margin[0];
	 $x2=$HZplot->size[0] - $HZplot->margin[1];
	 $y1=$HZplot->margin[2];
	 $y2=$HZplot->size[1] - $HZplot->margin[3];
	 $length=$x2 - $x1;
	 $height=$y2 - $y1;
	 $cntx=count($HZplot->xmarkset) - 1;
	 $cnty=count($HZplot->ymarkset) - 1;

	 if (!$HZplot->y2markset) {
		 $yr2=$HZplot->yrange;
		 if (is_array($yr2)) {
			 $cnty2=$yr2[2];
		 } else {
			 $cnty2=1;
		 }
	 } else {
		 $cnty2=count($HZplot->y2markset) - 1;
	 }

	 if ($HZplot->xextend) {
		 $cntx+=2;
	 }

	 if ($HZplot->yextend) {
		 $cnty+=2;
	 }

	 $deltax=$length / $cntx;
	 $deltay=$height / $cnty;

	 if ($this->direction == "y2") {
		 $deltay2=$height / $cnty2;
	 }

	 if ($this->direction == "x") {
		 $cnt=$cntx;
		 $markset=$HZplot->xmarkset;
	 }

	 if ($this->direction == "y") {
		 $cnt=$cnty;
		 $markset=$HZplot->ymarkset;
	 }

	 if ($this->direction == "y2") {
		 $cnt=$cnty2;
		 $markset=$HZplot->y2markset;
	 }

	 for ($i=0; $i <= $cnt; ++$i) {
		 if ($this->direction == "x") {
			 $sx=$this->start[0] + round($i * $deltax);

			 $sy=$this->start[1];
			 $ex=$sx;
			 $ey=$sy - $this->ticklength;
		 } elseif ($this->direction == "y") {
			 $sx=$this->start[0];

			 $sy=$this->start[1] - round($i * $deltay);
			 $ex=$sx + $this->ticklength;
			 $ey=$sy;
		 } else {
			 $sx=$this->start[0];

			 $sy=$this->start[1] - round($i * $deltay2);
			 $ex=$sx - $this->ticklength;
			 $ey=$sy;
		 }

		 if ($this->direction == "x" && $HZplot->xextend) {
			 if ($i == 0 || $i == $cntx) {
				 continue;
			 }
		 }

		 if ($this->direction == "y" && $HZplot->yextend) {
			 if ($i == 0 || $i == $cnty) {
				 continue;
			 }
		 }

		 if ($this->tick) {
			 if (($i % $this->tickskip) != 0) {
				 continue;
			 }

			 if ($this->direction == "y2") {
				 $y2cubic=false;
			 } else {
				 $y2cubic=$this->cubic;
			 }

			 $nl=new Pplot_line;
			 $nl->layout(array("start" => array($sx, $sy), "end" => array($ex, $ey), "color" => $this->color, "shift" => $y2shift, "cubic" => $y2cubic));
			 $nl->show();
			 unset ($nl);
		 }
		 if ($this->mark) {
			 $mmfw=0;

			 if (($i % $this->markskip) != 0) {
				 continue;
			 }

			 $k=$i;

			 if ($this->direction == "x") {
				 if ($HZplot->xextend) {
					 $k=$i - 1;
					 if ($k < 0) {
						 continue;
					 }
				 }
			 }

			 if ($this->direction == "y") {
				 if ($HZplot->yextend) {
					 $k=$i - 1;
					 if ($k < 0) {
						 continue;
					 }
				 }
			 }

			 // Округление оси Y
			 //if ($this->direction == "y") {		
			 //	$markset[$k] = round($markset[$k]);
			 //}

			 // Ограницения по показам
			 if ($this->direction == "x") {		

				if (count($markset) > 12) {
					if ($k % 2) $markset[$k] = "";
				}
			 }

			 $nm=new Pplot_text;
			 $nm->layout(array("textfont" => $this->markfont, "textsize" => $this->marksize, "textcolor" => $this->markcolor, "degree" => $this->markdegree, "text" => $markset[$k], "ttf" => $this->markttf));
			 list($mfw, $mfh)=$nm->geometry();

			 if ($mfw > $mmfw) {
				 $mmfw=$mfw;
			 }

			 if ($this->direction == "x") {
				 $sx=$sx - round($mfw / 2);
				 $sy=$sy + $mfh+8;
			 } elseif ($this->direction == "y") {
				 $sx=$sx - $mfw - 2;
				 $sy=$sy + round($mfh / 2);
			 } else {
				 $sx=$sx + 4 + $y2shift[0];
				 $sy=$sy + round($mfh / 2) - 2 + $y2shift[1];
			 }

			 $nm->layout(array("pos" => array($sx, $sy), "shift" => $this->markshift));
			 $nm->show();
			 unset ($nm);
		 }
	 }

	 if ($this->label) {
		 $nlabel=new Pplot_text;

		 $nlabel->layout(array("textfont" => $this->labelfont, "textsize" => $this->labelsize, "shift" => $this->labelshift, "text" => $this->labeltext, "ttf" => $this->labelttf, "degree" => $this->labeldegree, "pos" => array(50, 50), "textcolor" => $this->labelcolor));
		 list($lfw, $lfh)=$nlabel->geometry();

		 if ($this->direction == "x") {
			 if ($this->labelpos == "center") {
				 $lx=$x1 + round(($length - $lfw) / 2);
			 } else {
				 $lx=$this->end[0] - $lfw;
			 }
			 $ly=$this->start[1] + $lfh + $mfh;
		 } elseif ($this->direction == "y") {
			 if ($this->labelpos == "center") {
				 $ly=$this->start[1] - round(($height - $lfw) / 2);
			 } else {
				 $ly=$this->start[1] - $height + $lfw;
			 }
			 $lx=$this->start[0] - $lfh - $mmfw;
		 } else {
			 if ($this->labelpos == "center") {
				 $ly=$this->start[1] - round(($height - $lfw) / 2) + $y2shift[1];
			 } else {
				 $ly=$this->start[1] - $height + $lfw + $y2shift[1];
			 }
			 $lx=$this->start[0] + $lfh + $mmfw + $y2shift[0] + 4;
		 }

		 $nlabel->layout(array("pos" => array($lx, $ly)));
		 $nlabel->show();
		 unset ($nlabel);
	 }
 }
 }

 class Pplot_xaxis  extends Pplot_axis {

	 var $classname = "Pplot_xaxis";

 function Pplot_xaxis() {

	 global $HZplot;

	 $this->start[0]=$HZplot->margin[0];
	 $this->start[1]=$HZplot->size[1] - $HZplot->margin[3];
	 $this->end[0]=$HZplot->size[0] - $HZplot->margin[1];
	 $this->end[1]=$this->start[1];
 }
 }

 class Pplot_yaxis  extends Pplot_axis {

	 var $classname = "Pplot_yaxis";
	 var $direction = "y";
	 var $labeltext = "Y axis";
	 var $labeldegree = "90";
	 var $fillcolor = "white";

 function Pplot_yaxis() {

	 global $HZplot;

	 $this->start[0]=$HZplot->margin[0];
	 $this->start[1]=$HZplot->size[1] - $HZplot->margin[3];
	 $this->end[0]=$HZplot->margin[0];
	 $this->end[1]=$HZplot->margin[2];
 }
 }

 class Pplot_y2axis  extends Pplot_axis {

	 var $classname = "Pplot_y2axis";
	 var $direction = "y2";
	 var $labeltext = "Y2 axis";
	 var $labeldegree = "90";

 function Pplot_y2axis() {

	 global $HZplot;

	 $this->start[0]=$HZplot->size[0] - $HZplot->margin[1];
	 $this->start[1]=$HZplot->size[1] - $HZplot->margin[3];
	 $this->end[0]=$this->start[0];
	 $this->end[1]=$HZplot->margin[2];
 }
 }

 class Pplot_bar  extends Pplot_base {

	 var $classname = "Pplot_bar";
	 var $length = 50;
	 var $height = 50;
	 var $border = true;
	 var $negative = false;
	 var $bordercolor = "black";

 function Pplot_bar($s, $l, $h, $c, $bc = "") {

	 $this->position(array("pos" => $s, "length" => $l, "height" => $h, "color" => $c));

	 $this->bordercolor=plotcolor($this->bordercolor);

	 if (!empty($bc)) {
		 $this->bordercolor=plotcolor($bc);
	 }
 }

 function show() {

	 global $HZplot;

	 if ($HZplot->cubic) {
		 $this->border=true;
	 }

	 $x1=$this->pos[0];
	 $y1=$this->pos[1];
	 $x2=$x1 + $this->length;
	 $y2=$y1 - $this->height;
	 $x3=$x2;
	 $y3=$y1;
	 $x4=$x1;
	 $y4=$y2;
	 ImageFilledPolygon($HZplot->image, array($x1, $y1, $x3, $y3, $x2, $y2, $x4, $y4), 4, $this->color);

	 if ($this->border) {
		 ImagePolygon($HZplot->image, array($x1, $y1, $x3, $y3, $x2, $y2, $x4, $y4), 4, $this->bordercolor);
	 }

	 if ($HZplot->cubic) {
		 $nc=new Pplot_line;
		 if (!$this->negative) {
			 $nc->layout(array("start" => array($x3, $y3), "end" => array($x2, $y2), "cubic" => true, "color" => $this->bordercolor, "fillcolor" => $this->color));

			 $nc->show();
			 unset ($nc);
			 $nc=new Pplot_line;
			 $nc->layout(array("start" => array($x4, $y4), "end" => array($x2, $y2), "cubic" => true, "color" => $this->bordercolor, "fillcolor" => $this->color));
			 $nc->show();
			 unset ($nc);
		 } else {
			 ImageFilledPolygon($HZplot->image, array($x2, $y2, $x3, $y3, $x3 + 6, $y3 - 6, $x2 + 6, $y2), 4, $this->color);
			 ImagePolygon($HZplot->image, array($x2, $y2, $x3, $y3, $x3 + 6, $y3 - 6, $x2 + 6, $y2), 4, $this->bordercolor);
		 }
	 }
 }
 }

 class Pplot_legend  extends Pplot_base {

	 var $classname = "Pplot_legend";
	 var $type = 10;
	 var $width = 10;
	 var $filled = false;

 function layout($a = "") {

	 global $HZplot;

	 $this->position($a);

	 if (!$HZplot->lepos) {
		 $HZplot->lepos[0]=0 + $this->shift[0];

		 $HZplot->lepos[1]=0 + $this->shift[1];

		 if ($HZplot->ledir == "x") {
			 $nly=$HZplot->size[1] - $HZplot->margin[3];

			 list($nlw, $nlh)=$this->geometry();
			 $nly+=$nlh;
			 $nlx=$HZplot->margin[0] + 0.5 * $HZplot->length - 0.5 * (60 * (count($HZplot->datasets) - 1));
		 } else {
			 $nlx=$HZplot->size[0] - $HZplot->margin[1] + 20;

			 $nly=$HZplot->margin[2];
			 $HZplot->ledir="y";
		 }

		 $HZplot->lepos[0]+=round($nlx);
		 $HZplot->lepos[1]+=$nly;
	 }
 }

 function geometry() {

	 $psize=$this->scale * $this->width;
	 $nlt=new Pplot_text;
	 $nlt->layout(array("text" => $this->text, "textfont" => $this->textfont, "textsize" => $this->textsize, "ttf" => $this->ttf));
	 list($nltw, $nlth)=$nlt->geometry();
	 unset ($nlt);
	 return array(($nltw + $psize + 5), $nlth);
 }

 function show() {

	 global $HZplot;

	 if (!$HZplot->options["legend"]) {
		 return;
	 }

	 $psize=$this->width * $this->scale;

	 if ($this->type < 10) {
		 $np=new Pplot_point;

		 $np->layout(array("pos" => $HZplot->lepos, "pointtype" => $this->type, "pointwidth" => $this->width, "scale" => $this->scale, "color" => $this->color, "filled" => $this->filled));
		 $np->show();
		 unset ($np);
		 $ty=$HZplot->lepos[1] + round($psize / 2);
		 $tx=$HZplot->lepos[0] + round($psize / 2) + 5;
	 } else {
		 $cx=$HZplot->lepos[0];

		 $cy=$HZplot->lepos[1];
		 $x1=$cx - round($psize / 2);
		 $y1=$cy - round($psize / 2);
		 $x2=$cx + round($psize / 2);
		 $y2=$cy + round($psize / 2);
		 $border=plotcolor("black");
		 ImageFilledRectangle($HZplot->image, $x1, $y1, $x2, $y2, $this->color);
		 ImageRectangle($HZplot->image, $x1, $y1, $x2, $y2, $border);
	 }

	 $ty=$HZplot->lepos[1] + round($psize / 2);
	 $tx=$HZplot->lepos[0] + round($psize / 2) + 5;
	 $npt=new Pplot_text;
	 $npt->layout(array("pos" => array($tx, $ty), "text" => $this->text, "textfont" => $this->textfont, "textsize" => $this->textsize, "textcolor" => $this->textcolor, "ttf" => $this->ttf));
	 $npt->show();
	 unset ($npt);

	 list($nnw, $nnh)=$this->geometry();

	 if ($HZplot->ledir == "x") {
		 $HZplot->lepos[0]+=$nnw + 10;
	 } else {
		 $HZplot->lepos[1]+=$nnh + 5;
	 }
 }
 }

 class Pplot_bargraph  extends Pplot_base {

	 var $classname = "Pplot_bargraph";
	 var $barmode = "side";
	 var $barspacing = 0;
	 var $drawsets = array(1);
	 var $y2axis = false;
	 var $showvalue = false;
	 var $valuefont = 3;
	 var $valuecolor = "black";
	 var $valuesize = 8;
	 var $valuettf = false;
	 var $legend = false;
	 var $legendfont = 3;
	 var $legendsize = 8;
	 var $legendttf = false;
	 var $legendscale = 1;
	 var $legendtype = 8;
	 var $legendfontclr = "black";

 function layout($a = "") {

	 global $HZplot;

	 $HZplot->xextend=true;

	 $gpa=$GLOBALS["_globalsettings"]["Pplot_legend"];

	 if (!empty($gpa["textcolor"])) {
		 $this->legendfontclr=$gpa["textcolor"];
	 }

	 if (!empty($gpa["textfont"])) {
		 $this->legendfont=$gpa["textfont"];
	 }

	 if (!empty($gpa["scale"])) {
		 $this->legendscale=$gpa["scale"];
	 }

	 if (!empty($gpa["type"])) {
		 $this->legendtype=$gpa["type"];
	 }

	 $this->position($a);

	 if ($this->barmode == "stack") {
		 $HZplot->stack=true;
	 }

	 $y2=false;

	 if ($this->y2axis) {
		 $HZplot->options["y2axis"]=true;
		 $y2=true;
	 }

	 $HZplot->parsedata($this->drawsets, $y2);

	 if (!$HZplot->pregame) {
		 pregame();
	 }
 }

 function show() {

	 global $HZplot;

	 $sets=count($this->drawsets);
	 $points=count($HZplot->xmarkset);

	 if ($HZplot->xextend) {
		 $points+=2;
	 }

	 $length=$HZplot->length;
	 $height=$HZplot->height;
	 $delta=$length / ($points - 1);
	 $width=round(($delta - $this->barspacing) / $sets);

	 if ($this->barmode == "overwrite" || $this->barmode == "stack") {
		 $width=round($delta) - $this->barspacing;
		 $this->showvalue=false;
	 }

	 $sx=$HZplot->margin[0];
	 $sy=$HZplot->size[1] - $HZplot->margin[3];

	 if ($HZplot->zero > 1) {
		 $sy-=round($HZplot->zero);
	 }

	 $numclr=count($HZplot->colorset);

	 $drawstart=0;
	 $drawend=$points;

	 if ($HZplot->xextend) {
		 $sx=$HZplot->margin[0] + round($length / ($points - 1));

		 $drawstart=0;
		 $drawend=$points - 2;
	 }

	 $ycnt=count($HZplot->ymarkset) - 1;
	 $ymin=$HZplot->ymarkset[0];
	 $ymax=$HZplot->ymarkset[$ycnt];

	 if ($this->y2axis) {
		 $y2cnt=count($HZplot->y2markset) - 1;

		 $y2min=$HZplot->y2markset[0];
		 $y2max=$HZplot->y2markset[$y2cnt];
	 }

	 for ($i=$drawstart; $i < $drawend; ++$i) {
		 $stackh = 0;

		 reset ($this->drawsets);
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $shift=($this->barmode == "side") ? $width : 0;
			 $bx=$sx + round($i * $delta) + round(($this->barspacing - $delta) / 2) + $j * $shift;
			 $bvalue=$HZplot->datasets[$dset][$i];

			 if ($this->y2axis && $j == ($sets - 1)) {
				 $h=round(($bvalue - $y2min) * $height / ($y2max - $y2min));
			 } else {
				 $h=round(($bvalue - $ymin) * $height / ($ymax - $ymin));
			 }

			 $by=$sy;

			 if ($h < 0) {
				 $by=$by + abs($h);
			 }

			 if ($this->barmode == "stack") {
				 $by=$by - $stackh;
			 }

			 $stackh+=$h;

			 if ($this->shift) {
				 $bx+=$this->shift[0];
				 $by+=$this->shift[1];
			 }

			 $abar=new Pplot_bar(array($bx, $by), $width, abs($h), $color);

			 if ($h < 0) {
				 $abar->negative=true;
			 }

			 $abar->show();
			 if ($this->showvalue) {
				 $nvl=new Pplot_text;

				 $nvl->layout(array("text" => $bvalue, "textcolor" => $this->valuecolor, "textfont" => $this->valuefont, "textsize" => $this->valuesize, "ttf" => $this->valuettf));
				 list($nvlw, $nvlh)=$nvl->geometry();

				 if ($h >= 0) {
					 $svy=$by - $h - 5;
					 if ($HZplot->cubic) {
						 $svy-=6;
					 }
				 } else {
					 $svy=$by + $nvlh;
					 if ($HZplot->cubic) {
						 $svy+=6;
					 }
				 }

				 $svx=$bx + round(($width - $nvlw) / 2);

				 if ($HZplot->cubic) {
					 $svx+=6;
				 }

				 $nvl->layout(array("pos" => array($svx, $svy)));
				 $nvl->show();
				 unset ($nvl);
			 }
		 }
	 }

	 reset ($this->drawsets);

	 if (!empty($this->legend)) {
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $nl=new Pplot_legend;
			 $nl->layout(array("text" => $this->legend[$j], "color" => $color, "textfont" => $this->legendfont, "textcolor" => $this->legendfontclr, "ttf" => $this->legendttf, "textsize" => $this->legendsize, "scale" => $this->legendscale, "filled" => true, "type" => $this->legendtype));
			 $nl->show();
			 unset ($nl);
		 }
	 }
 }
 }

 class Pplot_linepoints  extends Pplot_base {

	 var $classname = "Pplot_linepoints";
	 var $filled = true;
	 var $showline = true;
	 var $showpoint = true;
	 var $linewidth = 1;
	 var $drawsets = array(1);
	 var $y2axis = false;
	 var $showvalue = false;
	 var $valuefont = 3;
	 var $valuecolor = "black";
	 var $valuesize = 8;
	 var $valuettf = false;
	 var $valueshift = array(5, -4);
	 var $legend = false;
	 var $legendfont = 3;
	 var $legendsize = 10;
	 var $legendttf = false;
	 var $legendscale = 1;
	 var $legendtype = false;
	 var $legendfontclr = "black";

 function layout($a = "") {

	 global $HZplot;

	 $this->cubic=$HZplot->cubic;

	 $gpa=$GLOBALS["_globalsettings"]["Pplot_legend"];

	 if (!empty($gpa["textcolor"])) {
		 $this->legendfontclr=$gpa["textcolor"];
	 }

	 if (!empty($gpa["textfont"])) {
		 $this->legendfont=$gpa["textfont"];
	 }

	 if (!empty($gpa["scale"])) {
		 $this->legendscale=$gpa["scale"];
	 }

	 $this->position($a);

	 $y2=false;

	 if ($this->y2axis) {
		 $HZplot->options["y2axis"]=true;
		 $y2=true;
	 }

	 $HZplot->parsedata($this->drawsets, $y2);

	 if (!$HZplot->pregame) {
		 pregame();
	 }
 }

 function show() {

	 global $HZplot;

	 $sets=count($this->drawsets);
	 $points=count($HZplot->xmarkset);

	 if ($HZplot->xextend) {
		 $points+=2;
	 }

	 $length=$HZplot->length;
	 $height=$HZplot->height;
	 $delta=$length / ($points - 1);

	 $sx=$HZplot->margin[0];
	 $sy=$HZplot->size[1] - $HZplot->margin[3];

	 if ($HZplot->zero > 1) {
		 $sy-=round($HZplot->zero);
	 }

	 $numclr=count($HZplot->colorset);

	 $drawstart=0;
	 $drawend=$points;

	 if ($HZplot->xextend) {
		 $sx=$HZplot->margin[0] + round($length / ($points - 1));

		 $drawstart=0;
		 $drawend=$points - 2;
	 }

	 $ycnt=count($HZplot->ymarkset) - 1;
	 $ymin=$HZplot->ymarkset[0];
	 $ymax=$HZplot->ymarkset[$ycnt];

	 if ($this->y2axis) {
		 $y2cnt=count($HZplot->y2markset) - 1;

		 $y2min=$HZplot->y2markset[0];
		 $y2max=$HZplot->y2markset[$y2cnt];
	 }

	 for ($i=$drawstart; $i < $drawend; ++$i) {
		 reset ($this->drawsets);
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $bx=$sx + round($i * $delta);
			 $bvalue=$HZplot->datasets[$dset][$i];

			 if ($this->y2axis && $j == ($sets - 1)) {
				 $h=round(($bvalue - $y2min) * $height / ($y2max - $y2min));
			 } else {
				 $h=round(($bvalue - $ymin) * $height / ($ymax - $ymin));
			 }

			 $by=$sy - $h;

			 $apoint=new Pplot_point;
			 $ptsxy[$j][$i]=array($bx, $by);

			 if ($this->showpoint) {
				 $apoint->layout(array("color" => $color, "pointtype" => $dset, "scale" => $this->scale, "pos" => array($bx, $by), "showvalue" => $this->showvalue, "text" => $bvalue, "filled" => $this->filled, "valuefont" => $this->valuefont, "valuecolor" => $color, "valuesize" => $this->valuesize, "valuettf" => $this->valuettf, "textshift" => $this->valueshift));
				 $apoint->show();
			 }
			 unset ($apoint);
		 }
	 }

	 if ($this->showline) {
		 reset ($this->drawsets);
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $color=plotcolor($color);
			 for ($i=$drawstart; $i < $drawend - 1; ++$i) {
				 $lstart = $ptsxy[$j][$i];

				 $lend=$ptsxy[$j][$i + 1];
				 if ($this->cubic && !$this->showpoint) {
					 $_nc=new Pplot_line;

					 $_nc->layout(array("start" => $lstart, "end" => $lend, "color" => plotcolor("black"), "cubic" => true, "fillcolor" => $color));
					 $_nc->show();
					 unset ($_nc);
				 } else {
					 if ($this->linewidth == 1) {
						 ImageLine($HZplot->image, $lstart[0], $lstart[1], $lend[0], $lend[1], $color);
					 } else {
						 $l1=$ptsxy[$j][$i];

						 $l2=$ptsxy[$j][$i + 1];
						 $lx1=$l1[0];
						 $ly1=$l1[1];
						 $lx2=$l2[0];
						 $ly2=$l2[1];
						 $lx3=$lx2;
						 $ly3=$ly2 - $this->linewidth;
						 $lx4=$lx1;
						 $ly4=$ly1 - $this->linewidth;
						 ImageFilledPolygon($HZplot->image, array($lx1, $ly1, $lx2, $ly2, $lx3, $ly3, $lx4, $ly4), 4, $color);
					 }
				 }
			 }
		 }
	 }

	 reset ($this->drawsets);

	 if (!empty($this->legend)) {
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $nl=new Pplot_legend;

			 if (!$this->showpoint) {
				 $thetype=$this->legendtype ? $this->legendtype : 10;
			 } else {
				 $thetype=$this->legendtype ? $this->legendtype : $dset;
			 }

			 $nl->layout(array("text" => $this->legend[$j], "color" => $color, "textfont" => $this->legendfont, "textcolor" => $this->legendfontclr, "ttf" => $this->legendttf, "textsize" => $this->legendsize, "scale" => $this->legendscale, "filled" => $this->filled, "type" => $thetype));
			 $nl->show();
			 unset ($nl);
		 }
	 }
 }
 }

 class Pplot_areagraph  extends Pplot_linepoints {

	 var $classname = "Pplot_area";
	 var $areamode = "overwrite";

 function show() {

	 global $HZplot;

	 $sets=count($this->drawsets);
	 $points=count($HZplot->xmarkset);

	 if ($HZplot->xextend) {
		 $points+=2;
	 }

	 $length=$HZplot->length;
	 $height=$HZplot->height;
	 $delta=$length / ($points - 1);

	 $sx=$HZplot->margin[0];
	 $sy=$HZplot->size[1] - $HZplot->margin[3];

	 if ($HZplot->zero > 1) {
		 $sy-=round($HZplot->zero);
	 }

	 $numclr=count($HZplot->colorset);

	 $drawstart=0;
	 $drawend=$points;

	 if ($HZplot->xextend) {
		 $sx=$HZplot->margin[0] + round($length / ($points - 1));

		 $drawstart=0;
		 $drawend=$points - 2;
	 }

	 $ycnt=count($HZplot->ymarkset) - 1;
	 $ymin=$HZplot->ymarkset[0];
	 $ymax=$HZplot->ymarkset[$ycnt];

	 if ($this->y2axis) {
		 $y2cnt=count($HZplot->y2markset) - 1;

		 $y2min=$HZplot->y2markset[0];
		 $y2max=$HZplot->y2markset[$y2cnt];
	 }

	 for ($i=$drawstart; $i < $drawend; ++$i) {
		 reset ($this->drawsets);
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $bx=$sx + round($i * $delta);
			 $bvalue=$HZplot->datasets[$dset][$i];

			 if ($this->y2axis && $j == ($sets - 1)) {
				 $h=round(($bvalue - $y2min) * $height / ($y2max - $y2min));
			 } else {
				 $h=round(($bvalue - $ymin) * $height / ($ymax - $ymin));
			 }

			 $by=$sy - $h;

			 $apoint=new Pplot_point;
			 $ptsxy[$j][$i]=array($bx, $by);
			 unset ($apoint);
		 }
	 }

	 reset ($this->drawsets);
	 $cshift=0;

	 while (list($j, $dset)=each($this->drawsets)) {
		 $nc = ($dset - 1) % $numclr;

		 $color=$HZplot->colorset[$nc];
		 $color=plotcolor($color);

		 if ($HZplot->cubic) {
			 $ds=count($HZplot->datasets) - 2;

			 $ds=$ds == 0 ? 1 : $ds;
			 $cshift=round(6 / $ds);
		 }

		 $areapts[]=$sx + 6 - ($dset - 1) * $cshift;
		 $areapts[]=$sy - 6 + ($dset - 1) * $cshift;

		 for ($i=$drawstart; $i < $drawend; ++$i) {
			 $areapts[] = $ptsxy[$j][$i][0] + 6 - ($dset - 1) * $cshift;
			 $areapts[]=$ptsxy[$j][$i][1] - 6 + ($dset - 1) * $cshift;
		 }

		 $myct=count($HZplot->xmarkset);
		 $areapts[]=$ptsxy[$j][$myct - 1][0] + 6 - ($dset - 1) * $cshift;
		 $areapts[]=$sy - 6 + ($dset - 1) * $cshift;
		 ImageFilledPolygon($HZplot->image, $areapts, $myct + 2, $color);
		 unset ($areapts);
	 }

	 reset ($this->drawsets);

	 if (!empty($this->legend)) {
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;

			 $color=$HZplot->colorset[$nc];
			 $nl=new Pplot_legend;
			 $nl->layout(array("text" => $this->legend[$j], "color" => $color, "textcolor" => $this->legendfontclr, "textfont" => $this->legendfont, "ttf" => $this->legendttf, "textsize" => $this->legendsize, "scale" => $this->legendscale, "filled" => $this->filled, "type" => 10));
			 $nl->show();
			 unset ($nl);
		 }
	 }
 }
 }
 
 class Pplot_piegraph extends Pplot_bargraph {

	 var $classname = "Pplot_piegraph";
	 var $showmark = true;
	 var $showlabel = true;
	 var $markfont = 3;
	 var $markcolor = "black";
	 var $marksize = 8;
	 var $markshift = array(0, 0);
	 var $markttf = false;
	 var $labelfont = 3;
	 var $labelcolor = "black";
	 var $labelsize = 8;
	 var $labelshift = array(0, 0);
	 var $labelttf = false;
	 var $start =90;
	 var $pos = array(0, 0);
	 var $monocolor = false;
	 var $cubic = false;
	 var $scale = 1.1;
	 var $radius = false;
	 var $ratio = 0.55;
	 var $thickness = 12;
	 var $bordercolor = "lgray";

 function layout($a = "") {

	 global $HZplot;

	 $gpa=$GLOBALS["_globalsettings"]["Pplot_legend"];

	 if (!empty($gpa["textcolor"])) {
		 $this->legendfontclr=$gpa["textcolor"];
	 }

	 if (!empty($gpa["textfont"])) {
		 $this->legendfont=$gpa["textfont"];
	 }

	 if (!empty($gpa["scale"])) {
		 $this->legendscale=$gpa["scale"];
	 }

	 if (!empty($gpa["type"])) {
		 $this->legendtype=$gpa["type"];
	 }

	 $_newbox=new Pplot_box;
	 $_newbox->layout();

	 $mpa=$GLOBALS["_globalsettings"]["Pplot_piegraph"];

	 if (empty($mpa["pos"])) {
		 $this->pos[0]=round($HZplot->size[0] / 2);
		 $this->pos[1]=round($HZplot->size[1] / 2);
	 }

	 if (empty($mpa["radius"])) {
		 $gifx=$HZplot->length;

		 $gify=$HZplot->height;
		 $d=$gifx > $gify ? $gify : $gifx;
		 $this->radius=round($d * 0.50 * $this->scale);
	 }

	 $this->position($a);

	 $HZplot->parsedata($this->drawsets);

	 if (!$HZplot->pregame) {
		 if ($HZplot->cubic) {
			 $this->cubic=true;
		 }

		 $HZplot->options["xaxis"]=false;
		 $HZplot->options["yaxis"]=false;
		 $HZplot->options["y2axis"]=false;
		 $HZplot->options["grid"]=false;
		 $HZplot->options["box"]=false;

		 if ($HZplot->options["title"]) {
			 $_newtitle=new Pplot_title;

			 $_newtitle->layout();
			 list($ntw, $nth)=$_newtitle->geometry();
			 $a=$this->cubic ? $this->radius * $this->ratio : $this->radius;
			 $b=$this->cubic ? $this->thickness : 0;

			 if ($_newtitle->location == "topcenter") {
				 $this->pos[1]+=10;
				 $_newtitle->pos[1]=$this->pos[1] - $a - 25;
			 } else {
				 $this->pos[1]-=10;
				 $_newtitle->pos[1]=$this->pos[1] + $a + 23 + $b + $nth;
			 }

			 $_newtitle->show();
			 $HZplot->options["title"]=false;
		 }
		 if ($HZplot->options["legend"]) {
			 $_newlegend=new Pplot_legend;
			 $_newlegend->layout();
		 }
	 }
 }

 function show() {

	 global $HZplot;

	 $markset=$HZplot->xmarkset;
	 $theset=$this->drawsets[0];
	 $dataset=$HZplot->datasets[$theset];
	 $slices=count($HZplot->xmarkset);

	 $sum=0.0;

	 for ($i=0; $i < $slices; ++$i) {
		 $sum+=abs($dataset[$i]);
	 }

	 reset ($dataset);

	 if ($sum <= 0) {
		 $sum=1;
	 }

	 for ($i=0; $i < $slices; ++$i) {
		 $dataset[$i]=round(360 * abs($dataset[$i]) / $sum);
	 }

	 # ready to go
	 $cx=$this->pos[0];
	 $cy=$this->pos[1];
	 $bc=plotcolor($this->bordercolor);
	 $numclr=count($HZplot->colorset);
	 $start=$this->start;
	 reset ($dataset);

	 if (!$this->cubic) {
		 $r=$this->radius;

		 ImageArc($HZplot->image, $cx, $cy, $r * 2, $r * 2, 0, 360, $bc);
		 for ($i=0; $i < $slices; ++$i) {
			 $col = $HZplot->colorset[$i % $numclr];

			 $col=plotcolor($col);
			 $startx=$cx + round($r * cos($start * pi() / 180));
			 $starty=$cy - round($r * sin($start * pi() / 180));
			 $end=$start + $dataset[$i];
			 $centerx=$cx + round(0.7 * $r * cos(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $centery=$cy - round(0.7 * $r * sin(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $outerx=$cx + round(1.25 * $r * cos(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $outery=$cy - round(1.15 * $r * sin(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 ImageLine($HZplot->image, $cx, $cy, $startx, $starty, $bc);
			 imagefilltoborder($HZplot->image, $centerx, $centery, $bc, $col);
			 if ($this->showmark) {
				 $thisstr=round($dataset[$i] * 10 / 36). '%';

				 $vt=new Pplot_text;
				 $vt->layout(array("textfont" => $this->markfont, "textsize" => $this->marksize, "shift" => $this->markshift, "textcolor" => $this->markcolor, "text" => $thisstr, "pos" => array(10, 10), "ttf" => $this->markttf));
				 list($vtw, $vth)=$vt->geometry();
				 $fx=$centerx - round(0.5 * $vtw);
				 $fy=$centery + round(0.5 * $vth);
				 $vt->layout(array("pos" => array($fx, $fy)));
				 $vt->show();
				 unset ($vt);
			 }
			 if ($this->showlabel) {
				 $thisstr=$HZplot->xmarkset[$i];

				 $vt=new Pplot_text;
				 $vt->layout(array("textfont" => $this->labelfont, "textsize" => $this->labelsize, "shift" => $this->labelshift, "textcolor" => $this->labelcolor, "text" => $thisstr, "pos" => array(10, 10), "ttf" => $this->labelttf));
				 list($vtw, $vth)=$vt->geometry();
				 $fx=$outerx - round(0.5 * $vtw);
				 $fy=$outery + round(0.5 * $vth);
				 $vt->layout(array("pos" => array($fx, $fy)));
				 $vt->show();
				 unset ($vt);
			 }
			 $start=$end;
		 }
	 } else {
		 $a=$this->radius;

		 $b=round($a * $this->ratio);
		 $dx=$cx;
		 $dy=$cy + $this->thickness;

		 ImageArc($HZplot->image, $cx, $cy, $a * 2, $b * 2, 180, 360, $bc);
		 ImageArc($HZplot->image, $dx, $dy, $a * 2, $b * 2, 0, 180, $bc);
		 ImageLine($HZplot->image, $cx + $a, $cy, $dx + $a, $dy, $bc);
		 ImageLine($HZplot->image, $cx - $a, $cy, $dx - $a, $dy, $bc);

		 for ($i=0; $i < $slices; ++$i) {
			 $col = $HZplot->colorset[$i % $numclr];

			 $col=plotcolor($col);
			 $startx=$cx + round($a * cos($start * pi() / 180));
			 $starty=$cy - round($b * sin($start * pi() / 180));
			 $end=$start + $dataset[$i];
			 $centerx=$cx + round(0.7 * $a * cos(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $centery=$cy - round(0.7 * $b * sin(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $outerx=$cx + round(1.35 * $a * cos(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 $outery=$cy - round(1.25 * $b * sin(($start + 0.5 * $dataset[$i]) * pi() / 180));
			 ImageLine($HZplot->image, $cx, $cy, $startx, $starty, $bc);

			 if ($starty > $cy) {
				 ImageLine($HZplot->image, $startx, $starty, $startx, $starty + $this->thickness, $bc);
			 }

			 imagefilltoborder($HZplot->image, $centerx, $centery, $bc, $col);

			 if ($this->showmark) {
				 $thisstr=round($dataset[$i] * 10 / 36). '%';

				 $vt=new Pplot_text;
				 $vt->layout(array("textfont" => $this->markfont, "textsize" => $this->marksize, "shift" => $this->markshift, "textcolor" => $this->markcolor, "text" => $thisstr, "pos" => array(10, 10), "ttf" => $this->markttf));
				 list($vtw, $vth)=$vt->geometry();
				 $fx=$centerx - round(0.5 * $vtw);
				 $fy=$centery + round(0.5 * $vth);
				 $vt->layout(array("pos" => array($fx, $fy)));
				 $vt->show();
				 unset ($vt);
			 }

			 if ($this->showlabel) {
				 $thisstr=$HZplot->xmarkset[$i];

				 $vt=new Pplot_text;
				 $vt->layout(array("textfont" => $this->labelfont, "textsize" => $this->labelsize, "shift" => $this->labelshift, "textcolor" => $this->labelcolor, "text" => $thisstr, "pos" => array(10, 10), "ttf" => $this->labelttf));
				 list($vtw, $vth)=$vt->geometry();
				 $fx=$outerx - round(0.5 * $vtw);
				 $fy=$outery + round(0.5 * $vth);
				 $vt->layout(array("pos" => array($fx, $fy)));
				 $vt->show();
				 unset ($vt);
			 }
			 $start=$end;
		 }
		 ImageArc($HZplot->image, $cx, $cy, $a * 2, $b * 2, 0, 180, $bc);
	 }
 }
 }

 class Pplot_bargraph2 extends Pplot_bargraph {

	 var $classname = "Pplot_bargraph2";

 function layout($a = "") {

	 global $HZplot;

	 $gpa=$GLOBALS["_globalsettings"]["Pplot_legend"];

	 if (!empty($gpa["textcolor"])) {
		 $this->legendfontclr=$gpa["textcolor"];
	 }

	 if (!empty($gpa["textfont"])) {
		 $this->legendfont=$gpa["textfont"];
	 }

	 if (!empty($gpa["scale"])) {
		 $this->legendscale=$gpa["scale"];
	 }

	 if (!empty($gpa["type"])) {
		 $this->legendtype=$gpa["type"];
	 }

	 if (!empty($HZplot->xmarkset)) {
		 $barxmarkset=$HZplot->xmarkset;
		 $HZplot->xmarkset=false;
	 }

	 if (!empty($HZplot->ymarkset)) {
		 $barymarkset=$HZplot->ymarkset;
		 $HZplot->ymarkset=false;
	 }

	 if (!empty($HZplot->yrange)) {
		 $HZplot->xrange=$HZplot->yrange;
	 }

	 $this->position($a);

	 if ($this->barmode == "stack") {
		 $HZplot->stack=true;
	 }

	 $HZplot->parsedata($this->drawsets, false);
	 $xset=$HZplot->xmarkset;
	 $HZplot->xmarkset=$HZplot->ymarkset;
	 $HZplot->ymarkset=$xset;

	 if ($barymarkset) {
		 $HZplot->ymarkset=$barymarkset;
	 }

	 if ($barxmarkset) {
		 $HZplot->xmarkset=$barxmarkset;
	 }

	 $HZplot->xextend=false;
	 $HZplot->yextend=true;

	 if (!$HZplot->pregame) {
		 pregame();
	 }
 }

 function show() {

	 global $HZplot;

	 $sets=count($this->drawsets);
	 $points=count($HZplot->ymarkset);

	 if ($HZplot->yextend) {
		 $points+=2;
	 }

	 $length=$HZplot->length;
	 $height=$HZplot->height;
	 $delta=$height / ($points - 1);
	 $width=round(($delta - $this->barspacing) / $sets);

	 if ($this->barmode == "overwrite" || $this->barmode == "stack") {
		 $width=round($delta) - $this->barspacing;
		 $this->showvalue=false;
	 }

	 $sx=$HZplot->margin[0];
	 $sy=$HZplot->size[1] - $HZplot->margin[3];
	 $numclr=count($HZplot->colorset);
	 $drawstart=0;
	 $drawend=$points;
	 if ($HZplot->yextend) {
		 $sy=$sy - round($height / ($points - 1));
		 $drawstart=0;
		 $drawend=$points - 2;
	 }

	 $xcnt=count($HZplot->xmarkset) - 1;
	 $xmin=$HZplot->xmarkset[0];
	 $xmax=$HZplot->xmarkset[$xcnt];
	 for ($i=$drawstart; $i < $drawend; ++$i) {
		 $stackh = 0;
		 reset ($this->drawsets);
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;
			 $color=$HZplot->colorset[$nc];
			 $shift=($this->barmode == "side") ? -$width : 0;
			 $bx=$sx;
			 $by=$sy - round($i * $delta) - round(($this->barspacing - $delta) / 2) + $j * $shift;
			 $bvalue=$HZplot->datasets[$dset][$i];
			 $h=round($bvalue * $length / ($xmax - $xmin));
			 if ($h < 0) {
				 $h=abs($h);
			 }
			 if ($this->barmode == "stack") {
				 $bx=$bx + $stackh;
			 }
			 $stackh+=$h;
			 if ($this->shift) {
				 $bx+=$this->shift[0];
				 $by+=$this->shift[1];
			 }
			 $abar=new Pplot_bar(array($bx, $by), abs($h), $width, $color);
			 $abar->show();
		 }
	 }
	 reset ($this->drawsets);

	 if (!empty($this->legend)) {
		 while (list($j, $dset)=each($this->drawsets)) {
			 $nc = ($dset - 1) % $numclr;
			 $color=$HZplot->colorset[$nc];
			 $nl=new Pplot_legend;
			 $nl->layout(array("text" => $this->legend[$j], "color" => $color, "textfont" => $this->legendfont, "textcolor" => $this->legendfontclr, "ttf" => $this->legendttf, "textsize" => $this->legendsize, "scale" => $this->legendscale, "filled" => true, "type" => $this->legendtype));
			 $nl->show();
			 unset ($nl);
		 }
	 }
 }
 }
?>