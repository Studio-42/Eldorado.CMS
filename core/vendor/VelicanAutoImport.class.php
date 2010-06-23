<?php
/**
 * Import data from Lexus to IShop
 *
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */

// TODO tm from model

//chdir('../');

include_once dirname(__FILE__).'/../console.php';
require_once dirname(__FILE__).'/XMLParseIntoStruct.class.php';

class IShopImportLexus
{
	var $cars      = array();
	var $props     = array();

	var $prop_bind = array(
		'AREA'             => '13',
		'MNFCDATE'         => '4',
		'BODYTYPE'         => '1',
		'ENGINETYPE'       => '5',
		'TRANSMISSIONTYPE' => '8',
		'CHASSISTYPE'      => '9'
	);
	var $attr_bind = array(
		'PRICEUSD'         => '16',
		'MILEAGEKM'        => '2',
		'ENGINEPOWER'      => '6',
		'ENGINECUBATURE'   => '7',
		'COLOR'            => '3',
		'INTERNALS'        => '10',
		'COMPLECTATION'    => '11',
		'DESCRIPTION'      => '12',
		'INFO1'            => '14'
	);

	var $itype_id  = '1';

	var $shop_id   = '2';
	var $tb_prop_value;
	var $tb_mnf;
	var $tb_item;
	var $tb_p2i;
	var $tb_i2c;
	var $tb_tm;
	var $tb_gal;

	var $photo_path = './storage';

	var $db;

	function __construct()
	{
		$this->tb_prop_value = 'el_ishop_'.$this->shop_id.'_prop_value';
		$this->tb_mnf        = 'el_ishop_'.$this->shop_id.'_mnf';
		$this->tb_item       = 'el_ishop_'.$this->shop_id.'_item';
		$this->tb_p2i        = 'el_ishop_'.$this->shop_id.'_p2i';
		$this->tb_i2c        = 'el_ishop_'.$this->shop_id.'_i2c';
		$this->tb_tm         = 'el_ishop_'.$this->shop_id.'_tm';
		$this->tb_gal        = 'el_ishop_'.$this->shop_id.'_gallery';

		$this->db            = & elSingleton::getObj('elDb');
	}

	function _parseXML($file)
	{
		$parser = new XMLParseIntoStruct;

		$xml = file_get_contents($file);
		if (!$parser->parse($xml))
		{
			return false;
		}

		$struct = $parser->getResult();
		if (!(
			isset($struct[0]['name']) and
			isset($struct[0]['child']) and
			($struct[0]['name'] == 'TRADEINEXPORT')
		))
		{
			return false;
		}

		//$this->struct = $struct[0]['child'];
		return $struct[0]['child'];
	}

	function getCars($file = null)
	{
		echo "Parsing $file";
		$struct = $this->_parseXML($file);
		if ($struct == false)
		{
			echo ", no objects found skipping\n";
			return false;
		}
		echo ", objects found: ".count($struct)."\n";
		//var_dump($struct);
		foreach ($struct as $cars)
		{
			if ($cars['name'] == 'CAR')
			{
				$car = array();
				foreach ($cars['child'] as $a)
				{
					$k = $a['name'];
					$v = isset($a['content']) ? $a['content'] : '';

					if ($k == 'MNFCDATE')
						$v = date('Y', strtotime($v));
					elseif (in_array($k, array('PRICERUB', 'PRICEUSD'))) //, 'ENGINECUBATURE'
					{
						$v = str_replace(',', '.', $v); // convert to PHP numbers
						$v = str_replace(' ', 'a', $v);
						$v = sprintf('%.2f', $v);
					}
					elseif ($k == 'SPECIALOFFER')
					{
						$v = ($v == 'Да' ? 1 : 0);
					}
					elseif ($k == 'COMPLECTATION')
					{
						$comp = array();
						foreach (explode(',', $v) as $c)
						{
							$c = trim($c);
							if (!empty($c))
							{
								array_push($comp, $c);
							}
						}
						if (!empty($comp))
						{
							$v = '';
							foreach ($comp as $c)
							{
								$v .= '<li>'.$c.';</li>'."\n";
							}
							$v = '<ul class="velican-complectation">'.$v.'</ul>';
						}
						//var_dump($comp);
					}
					elseif ($k == 'ENGINECUBATURE')
					{
						$v .= ' см<sup>3</sup>';
					}
					elseif ($k == 'MILEAGEKM')
					{
						$v .= ' км';
					}
					elseif ($k == 'ENGINEPOWER')
					{
						$v .= ' л.с.';
					}

					$v = trim($v);
					$v = str_replace("'", '&#39', $v); // dirty magic with quotes
					if (($k == 'MODEL') || ($k == 'BRAND'))
					{
						if (empty($v))
						{
							$v = 'NONAME';
						}
					}
					
					if (($k == 'PHOTOS') && (!empty($a['child'])))
					{
						$photos = array();
						foreach ($a['child'] as $p)
						{
							array_push($photos, $p['content']);
						}
						$v = $photos;
					}

					$car[$k] = $v;
				}
				$car['INFO1'] = $car['ENGINECUBATURE'].' / '.$car['ENGINEPOWER'];
				array_push($this->cars, $car);
			}
		}
		return true;
	}

	function getProps()
	{
		$props = array();
		$valid_props = array_keys($this->prop_bind);
		foreach ($this->cars as $car)
		{
			foreach ($car as $p => $v)
			{
				if (!(in_array($p, $valid_props)))
					continue;

				if (!(isset($props[$p])))
					$props[$p] = array();

				if (!empty($v))
					array_push($props[$p], $v);
			}
		}

		// get only uniqe props
		foreach ($props as $p => $v)
			$this->props[$p] = array_unique($v);
	}

	function loadProps()
	{
		// load props from database
		$this->db->query("SELECT p_id, value FROM ".$this->tb_prop_value
			." WHERE p_id IN (".implode(', ', array_values($this->prop_bind)).")");

		$props_flip = array_flip($this->prop_bind);
		$props_db = array();
		while ($p = $this->db->nextRecord())
		{
			$prop = $props_flip[$p['p_id']];
			if (!(isset($props_db[$prop])))
				$props_db[$prop] = array();

			array_push($props_db[$prop], $p['value']);
		}

		// find in database props not present in xml
		$props_unused_in_db = array();
		foreach ($props_db as $p => $a)
		{
			foreach ($a as $v)
			{
				if (!(in_array($v, $this->props[$p])))
				{
					if (!(isset($props_unused_in_db[$p])))
					{
						$props_unused_in_db[$p] = array();
					}
					array_push($props_unused_in_db[$p], $v);
				}
			}
		}
		// delete not used values
		foreach ($props_unused_in_db as $p => $a)
		{
			$p_id = $this->prop_bind[$p];
			foreach ($a as $v)
			{
				$sql = "DELETE FROM ".$this->tb_prop_value." WHERE p_id='".$p_id."' AND value='".$v."' LIMIT 1";
				$this->db->query($sql);
			}
		}

		// find in xml props not present in xml
		$props_not_in_db = array();
		foreach ($this->props as $p => $a)
		{
			foreach ($a as $v)
			{
				if (!(in_array($v, $props_db[$p])))
				{
					if (!(isset($props_not_in_db[$p])))
					{
						$props_not_in_db[$p] = array();
					}
					array_push($props_not_in_db[$p], $v);
				}
			}
		}
		// insert new values
		foreach ($props_not_in_db as $p => $a)
		{
			$p_id = $this->prop_bind[$p];
			foreach ($a as $v)
			{
				$sql = "INSERT INTO ".$this->tb_prop_value." (p_id, value) VALUES ('".$p_id."', '".$v."')";
				//echo $sql."\n";
				$this->db->query($sql);
			}
		}
	}

	function loadMnf()
	{
		// load from xml
		$mnf_xml = array();
		foreach ($this->cars as $car)
		{
			$m = $car['BRAND'];
			if (!(in_array($m, $mnf_xml)))
			{
				array_push($mnf_xml, $m);
				$this->db->query("SELECT id FROM ".$this->tb_mnf." WHERE UPPER(name)=UPPER('".$m."') LIMIT 1");
				if ($this->db->numRows() == 1) // skip if exist
				{
					continue;
				}
				// insert new one into db
				$logo = preg_replace('/(?![a-z])./', '_', strtolower($m)); // black-black regexp
				//print $logo;
				$logo = '/storage/mnf/'.$logo.'.gif';
				$sql = "INSERT INTO ".$this->tb_mnf." (name, logo) VALUES ('".$m."', '".$logo."')";
				$this->db->query($sql);
			}
		}
	}

	/**
	 * Load TradeMarks to DB from XML
	 *
	 * @return void
	 **/
	function loadTM()
	{
		foreach ($this->cars as $car)
		{
			$mnf_id = $this->_getMnfByName($car['BRAND']);
			if (!$mnf_id)
			{
				echo "problems loading mnf, skipping\n";
				continue;
			}
			$this->db->query("SELECT UPPER(tm.name) AS tm FROM ".$this->tb_tm." AS tm WHERE tm.mnf_id=".$mnf_id." AND UPPER(tm.name)='".$car['MODEL']."' LIMIT 1");
			if ($this->db->numRows() == 1)
			{
				continue;
			}
			$sql = "INSERT INTO ".$this->tb_tm." (mnf_id, name) VALUES ($mnf_id, '".$car['MODEL']."')";
			$this->db->query($sql);
		}
	}


	// delete old
	// construct new auto
	// get mnf id
	// insert into item
	// insert into i2c
	// set attrib binding
	// and proceed by prop
	// get prop ids
	// insert into p2i
	function loadCars()
	{
		// 1. delete old props and attribs
		//$sql = "TRUNCATE TABLE ".$this->tb_item;
		//$this->db->query($sql);
		$sql = "TRUNCATE TABLE ".$this->tb_p2i;
		$this->db->query($sql);
		$sql = "TRUNCATE TABLE ".$this->tb_i2c;
		$this->db->query($sql);

		// 1.1. find car diffs (old and new)
		$d_cars = array();
		$sql = "SELECT code FROM ".$this->tb_item;
		$this->db->query($sql);
		while ($m = $this->db->nextRecord())
		{
			array_push($d_cars, $m['code']);
		}
		print "Cars in XML: ".count($this->cars)."\n";
		print "Cars in DB:  ".count($d_cars)."\n";

		$i_cars = array();
		foreach ($this->cars as $car)
		{
			array_push($i_cars, $car['CARID']);
		}
		//print_r($i_cars);
		$old_cars = array_diff($d_cars, $i_cars);
		$new_cars = array_diff($i_cars, $d_cars);
		// 1.2. delete old cars from db
		if (!empty($old_cars))
		{
			echo "* remove old cars: ".implode(", ", $old_cars)."\n";
			$sql = "DELETE FROM ".$this->tb_item." WHERE code IN ('".implode("', '", $old_cars)."')";
			$this->db->query($sql);
		}

		// 2. new or update car
		foreach ($this->cars as $car)
		{
			$i_id = '';
			echo "* $car[BRAND] => $car[MODEL]";
			// 2.1 new item
			$mnf_id = $this->_getMnfByName($car['BRAND']);
			$tm_id  = $this->_getTMByName($mnf_id, $car['MODEL']);
			if (($tm_id < 1) or ($mnf_id < 1))
			{
				echo "  ^^^ something wrong, skipping\n";				
			}
			echo " $mnf_id:$tm_id ";
			if (in_array($car['CARID'], $new_cars))
			{
				$sql = "INSERT INTO ".$this->tb_item." (type_id, mnf_id, tm_id, code, name, price, special, crtime, mtime) VALUES (%d, %d, '%s', '%s', '%s', '%.2f', '%d', %d, %d)";
				$sql = sprintf($sql, $this->itype_id, $mnf_id, $tm_id, $car['CARID'], $car['MODEL'], $car['PRICERUB'], $car['SPECIALOFFER'], time(), time());
				$this->db->query($sql);
				$i_id = $this->db->insertID();
				echo " (insert) $i_id\n";
			}
			else
			{
				$sql = "SELECT id FROM ".$this->tb_item." WHERE code='%s' LIMIT 1";
				$sql = sprintf($sql, $car['CARID']);
				$this->db->query($sql);
				$id = $this->db->nextRecord();
				$i_id = $id['id'];
				$sql = "UPDATE ".$this->tb_item." SET mnf_id='%d', tm_id='%s', name='%s', price='%s', special='%d', mtime='%d' WHERE id='%d' LIMIT 1";
				$sql = sprintf($sql, $mnf_id, $tm_id, $car['MODEL'], $car['PRICERUB'], $car['SPECIALOFFER'], time(), $i_id);
				$this->db->query($sql);
				echo " (update)\n";
			}

			if (empty($i_id) or empty($car['BRAND']) or empty($car['MODEL'])) // resume if something wrong
			{
				echo "  ^^^ something wrong, skipping\n";
				continue;
			}

			// 2.2 new props
			foreach ($this->prop_bind as $p => $p_id)
			{
				//echo "  p $car[$p]\n";
				$pv_id = $this->_getPropValueIdByName($p_id, $car[$p]);
				//print "$p $car[$p] => $pv_id\n";
				$sql = "INSERT INTO ".$this->tb_p2i." (i_id, p_id, value, pv_id) VALUES ('%d', '%d', '%s', '%d')";
				$sql = sprintf($sql, $i_id, $p_id, $pv_id, $pv_id);
				$this->db->query($sql);
			}

			// 2.3 new attr
			foreach ($this->attr_bind as $a => $a_id)
			{
				$car[$a] = str_replace("'", '"', $car[$a]); // dirty magic with quotes
				//echo "  a $a_id\t$car[$a]\n";
				$sql = "INSERT INTO ".$this->tb_p2i." (i_id, p_id, value) VALUES ('%d', '%d', '%s')";
				$sql = sprintf($sql, $i_id, $a_id, $car[$a]);
				$this->db->query($sql);
			}

			// 2.4 add item to category
			$sql = "INSERT INTO ".$this->tb_i2c." (i_id, c_id) VALUES ('%d', '%d')";
			$sql = sprintf($sql, $i_id, '1');
			$this->db->query($sql);


			// 2.5 photos
			// 2.5.1 load from db
			$photo_db = array();
			if ($this->db->query("SELECT id, img FROM ".$this->tb_gal." WHERE i_id=$i_id"))
			{
				while ($m = $this->db->nextRecord())
				{
					$photo_db[$m['id']] = $m['img'];
				}
				//var_dump($photo_db);
			}
			
			// 2.5.2 insert new to db and generate tmbs
			$_image = new myElImage;
			$photo_xml = array();
			if (isset($car['PHOTOS']) and is_array($car['PHOTOS']))
			{
				foreach ($car['PHOTOS'] as $photo)
				{
					$p = $this->photo_path.'/'.$photo;
					if (is_file($p))
					{
						$path = substr($p, strpos($p, '/storage/'));
						//print "$p => $path\n";
						array_push($photo_xml, $path);
						if (in_array($path, $photo_db))
						{
							continue; // skip if already in db
						}
						
						// generate tmbs
						$imgName = baseName($p);
						$imgDir  = dirname($p).DIRECTORY_SEPARATOR;
						$tmbs = array(
							$imgDir.'tmbl-'.$imgName => 115,
							$imgDir.'tmbc-'.$imgName => 450,							
							//$imgDir.'tmbs-'.$imgName => 150,
						);
						$_i_info = $_image->imageInfo($p);
						foreach ($tmbs as $tmb => $size)
						{
							list($w, $h) = $_image->calcTmbSize($_i_info['width'], $_i_info['height'], $size);
							$_image->tmb($p, $tmb, $w, $h);
						}
						// insert into db
						$sql = "INSERT INTO ".$this->tb_gal." (i_id, img) VALUES (%d, '%s')";
						$sql = sprintf($sql, $i_id, $path);
						$this->db->query($sql);
						//print "$sql\n";
					}
				}
			}

			// 2.5.3 remove unused images from db
			foreach ($photo_db as $img_id => $photo)
			{
				if (!in_array($photo, $photo_xml))
				{
					print "Delete $photo\n";
					$sql = "DELETE FROM ".$this->tb_gal." WHERE id=%d LIMIT 1";
					$sql = sprintf($sql, $img_id);
					$this->db->query($sql);
				}
			}
			//var_dump($photo_xml);
			
		}
	}


	function _getTMByName($mnf_id = null, $name = null)
	{
		$sql = "SELECT id FROM ".$this->tb_tm." WHERE mnf_id=$mnf_id AND UPPER(name)=UPPER('".$name."') LIMIT 1";
		$this->db->query($sql);
		$tm = $this->db->nextRecord();
		return ($tm['id'] > 0 ? $tm['id'] : false);
	}

	function _getMnfByName($name = null)
	{
		$sql = "SELECT id FROM ".$this->tb_mnf." WHERE UPPER(name)=UPPER('".$name."') LIMIT 1";
		$this->db->query($sql);
		$mnf = $this->db->nextRecord();
		return ($mnf['id'] > 0 ? $mnf['id'] : false);
	}
	
	function _getPropValueIdByName($p_id, $v)
	{
		$sql = "SELECT id FROM ".$this->tb_prop_value." WHERE p_id='".$p_id."' AND value='".$v."' LIMIT 1";
		$this->db->query($sql);
		$id = $this->db->nextRecord();
		return $id['id'];
	}
}

require_once './core/lib/elImage.class.php';
class myelImage extends elImage
{
	function _error() {}
}


$import = new IShopImportLexus();
$import->photo_path = './storage/import/Photo';
$files = glob('./storage/import/*.xml');
foreach ($files as $f)
{
//	$import->parseXML($f);
	$import->getCars($f);
}
$import->getProps();
//print_r($import->cars);

$import->loadProps();
$import->loadMnf();
$import->loadTM();

$import->loadCars();
//print_r($import->props);
//print_r($import->cars);

//require_once('./index.php');


