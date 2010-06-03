<?php
/**
 * Import data from Lexus to IShop
 *
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */

// TODO tm from model

//chdir('../');

require_once './core/vendor/XMLParseIntoStruct.class.php';

class IShopImportLexus
{
	var $file      = null;
	var $struct    = array();
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
		'DESCRIPTION'      => '12'
	);

	var $itype_id  = '1';

	var $m;

	var $my_host   = 'localhost';
	var $my_user   = 'root';
	var $my_pass   = '';
	var $my_db     = 'velican';

	var $shop_id   = '2';
	var $tb_prop_value;
	var $tb_mnf;
	var $tb_item;
	var $tb_p2i;
	var $tb_i2c;
	var $tb_tm;

	function __construct()
	{
		$this->tb_prop_value = 'el_ishop_'.$this->shop_id.'_prop_value';
		$this->tb_mnf        = 'el_ishop_'.$this->shop_id.'_mnf';
		$this->tb_item       = 'el_ishop_'.$this->shop_id.'_item';
		$this->tb_p2i        = 'el_ishop_'.$this->shop_id.'_p2i';
		$this->tb_i2c        = 'el_ishop_'.$this->shop_id.'_i2c';
		$this->tb_tm         = 'el_ishop_'.$this->shop_id.'_tm';

		$this->m = mysql_connect($this->my_host, $this->my_user, $this->my_pass);
		mysql_query("SET NAMES 'utf8'", $this->m);
		mysql_select_db($this->my_db, $this->m);
	}

	function parseXML()
	{
		$parser = new XMLParseIntoStruct;

		$xml = file_get_contents($this->file);
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
		$this->struct = $struct[0]['child'];
		return $struct[0]['child'];
	}

	function getCars()
	{
		foreach ($this->struct as $cars)
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
					$v = trim($v);
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
				array_push($this->cars, $car);
			}
		}
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
		
		$r = mysql_query("SELECT p_id, value FROM ".$this->tb_prop_value
			." WHERE p_id IN (".implode(', ', array_values($this->prop_bind)).")", $this->m);

		$props_flip = array_flip($this->prop_bind);
		$props_db = array();
		while ($p = mysql_fetch_assoc($r))
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
				mysql_query($sql, $this->m);
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
				mysql_query($sql, $this->m);
			}
		}
	}

	function loadMnf()
	{
		// load from db
		$r = mysql_query("SELECT name FROM ".$this->tb_mnf, $this->m);
		$mnf_db = array();
		while ($m = mysql_fetch_assoc($r))
		{
			array_push($mnf_db, $m['name']);
		}
		// load from xml
		$mnf_xml = array();
		foreach ($this->cars as $car)
		{
			$m = $car['BRAND'];
			if (!(in_array($m, $mnf_xml)))
			{
				array_push($mnf_xml, $m);
			}
		}
		// get difference
		$mnf_del = array_diff($mnf_db, $mnf_xml);
		$mnf_add = array_diff($mnf_xml, $mnf_db);
		// delete not used
		foreach ($mnf_del as $m)
		{
			$sql = "DELETE FROM ".$this->tb_mnf." WHERE name='".$m."' LIMIT 1";
			mysql_query($sql, $this->m);
		}
		// add new
		foreach ($mnf_add as $m)
		{
			$sql = "INSERT INTO ".$this->tb_mnf." (name) VALUES ('".$m."')";
			mysql_query($sql, $this->m);
		}
	}

	/**
	 * Load TradeMarks to DB from XML
	 *
	 * @return void
	 **/
	function loadTM()
	{
		$r = mysql_query("SELECT mnf.name AS mnf, tm.name AS tm FROM ".$this->tb_tm." AS tm, ".$this->tb_mnf." AS mnf WHERE tm.mnf_id=mnf.id", $this->m);
		$tm_db = array();
		while ($m = mysql_fetch_assoc($r))
		{
			$tm_db[$m['mnf']][$m['tm']] = 1;
		}
		//var_dump($tm_db);

		$tm_xml = array();
		foreach ($this->cars as $car)
		{
			$tm_xml[$car['BRAND']][$car['MODEL']] = 1;
		}
		//var_dump($tm_xml);

		foreach ($tm_xml as $mnf => $model)
		{
			foreach ($model as $m => $value)
			{
				echo "$mnf => $m\n";
				if (isset($tm_db[$mnf][$m]) and $tm_db[$mnf][$m] == 1)
				{
					continue;
				}
				$mnf_id = $this->_getMnfByName($mnf);
				$sql = "INSERT INTO ".$this->tb_tm." (mnf_id, name) VALUES ($mnf_id, '$m')";
				mysql_query($sql);
			}
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
		//mysql_query($sql, $this->m);
		$sql = "TRUNCATE TABLE ".$this->tb_p2i;
		mysql_query($sql, $this->m);
		$sql = "TRUNCATE TABLE ".$this->tb_i2c;
		mysql_query($sql, $this->m);

		// 1.1. find car diffs (old and new)
		$d_cars = array();
		$sql = "SELECT code FROM ".$this->tb_item;
		$r = mysql_query($sql, $this->m);
		while ($m = mysql_fetch_assoc($r))
		{
			array_push($d_cars, $m['code']);
		}
		$i_cars = array();
		foreach ($this->cars as $car)
		{
			array_push($i_cars, $car['CARID']);
		}
		$old_cars = array_diff($d_cars, $i_cars);
		$new_cars = array_diff($i_cars, $d_cars);
		// 1.2. delete old cars from db
		if (!empty($old_cars))
		{
			$sql = "DELETE FROM ".$this->tb_item." WHERE code IN ('".implode("', '", $old_cars)."')";
			mysql_query($sql);
		}

		// 2. new or update car
		foreach ($this->cars as $car)
		{
			$i_id = '';
			echo "\n* $car[MODEL]";
			// 2.1 new item
			$mnf_id = $this->_getMnfByName($car['BRAND']);
			if (in_array($car['CARID'], $new_cars))
			{
				$sql = "INSERT INTO ".$this->tb_item." (type_id, mnf_id, tm_id, code, name, price, crtime, mtime) VALUES (3, '%d', '%s', '%s', '%s', '%d', '%d')";
				$sql = sprintf($sql, $mnf_id, $car['CARID'], $this->_getTMByName($mnf_id, $car['MODEL']), $car['MODEL'], $car['PRICERUB'], time(), time());
				mysql_query($sql, $this->m);
				$i_id = mysql_insert_id();
				echo " (insert)\n";
			}
			else
			{
				$sql = "SELECT id FROM ".$this->tb_item." WHERE code='%s' LIMIT 1";
				$sql = sprintf($sql, $car['CARID']);
				$r = mysql_query($sql, $this->m);
				$id = mysql_fetch_assoc($r);
				$i_id = $id['id'];
				$sql = "UPDATE ".$this->tb_item." SET mnf_id='%d', tm_id='%s', name='%s', price='%s', mtime='%d' WHERE id='%d' LIMIT 1";
				$sql = sprintf($sql, $mnf_id, $this->_getTMByName($mnf_id, $car['MODEL']), $car['MODEL'], $car['PRICERUB'], time(), $i_id);
				mysql_query($sql, $this->m);
				echo " (update)\n";
			}

			if (empty($i_id)) // resume if something wrong
			{
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
				mysql_query($sql, $this->m);
			}

			// 2.3 new attr
			foreach ($this->attr_bind as $a => $a_id)
			{
				$car[$a] = str_replace("'", '"', $car[$a]); // dirty magic with quotes
				//echo "  a $a_id\t$car[$a]\n";
				$sql = "INSERT INTO ".$this->tb_p2i." (i_id, p_id, value) VALUES ('%d', '%d', '%s')";
				$sql = sprintf($sql, $i_id, $a_id, $car[$a]);
				mysql_query($sql, $this->m);
			}

			// 2.4 add item to category
			$sql = "INSERT INTO ".$this->tb_i2c." (i_id, c_id) VALUES ('%d', '%d')";
			$sql = sprintf($sql, $i_id, '1');
			mysql_query($sql, $this->m);
		}
	}

	function _getTMByName($mnf_id = null, $name = null)
	{
		$sql = "SELECT id FROM ".$this->tb_tm." WHERE mnf_id=$mnf_id AND name='".$name."' LIMIT 1";
		$r = mysql_query($sql, $this->m);
		$tm = mysql_fetch_assoc($r);
		return $tm['id'];
	}

	function _getMnfByName($name = null)
	{
		$sql = "SELECT id FROM ".$this->tb_mnf." WHERE name='".$name."' LIMIT 1";
		$r = mysql_query($sql, $this->m);
		$mnf = mysql_fetch_assoc($r);
		return $mnf['id'];
	}
	
	function _getPropValueIdByName($p_id, $v)
	{
		$sql = "SELECT id FROM ".$this->tb_prop_value." WHERE p_id='".$p_id."' AND value='".$v."' LIMIT 1";
		$r = mysql_query($sql, $this->m);
		$id = mysql_fetch_assoc($r);
		return $id['id'];
	}
}

$file = './TradeIn.xml';

$import = new IShopImportLexus();
$import->file = $file;
$import->parseXML();
$import->getCars();
$import->getProps();

$import->loadProps();
$import->loadMnf();
$import->loadTM();

$import->loadCars();
//print_r($import->props);
//print_r($import->cars);

//require_once('./index.php');


