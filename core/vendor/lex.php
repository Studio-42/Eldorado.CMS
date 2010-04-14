<?php
/**
 * Import data from Lexus to IShop
 *
 * @author Troex Nevelin <troex@fury.scancode.ru>
 */

// TODO нах было ебаться сверять что есть в базе, а чего нет
//      проще всё убивать и начинать заново


chdir('../');

require_once './core/vendor/XMLParseIntoStruct.class.php';

class IShopImportLexus
{
	var $file      = null;
	var $struct    = array();
	var $cars      = array();
	var $props     = array();

	var $prop_bind = array(
		'AREA'             => '15',
		'MNFCDATE'         => '5',
		'BODYTYPE'         => '1',
		'ENGINETYPE'       => '6',
		'TRANSMISSIONTYPE' => '9',
		'CHASSISTYPE'      => '10'
	);
	var $attr_bind = array(
		'PRICEUSD'         => '16',
		'MILEAGEKM'        => '2',
		'ENGINEPOWER'      => '7',
		'ENGINECUBATURE'   => '8',
		'COLOR'            => '3',
		'INTERNALS'        => '12',
		'COMPLECTATION'    => '13',
		'DESCRIPTION'      => '14'
	);

	var $itype_id      = '3';

	var $m;

	var $my_host   = 'localhost';
	var $my_user   = 'root';
	var $my_pass   = '';
	var $my_db     = 'lexus';

	var $shop_id   = '29';
	var $tb_prop_value;
	var $tb_mnf;
	var $tb_item;
	var $tb_p2i;
	var $tb_i2c;

	function __construct()
	{
		$this->tb_prop_value = 'el_ishop_'.$this->shop_id.'_prop_value';
		$this->tb_mnf        = 'el_ishop_'.$this->shop_id.'_mnf';
		$this->tb_item       = 'el_ishop_'.$this->shop_id.'_item';
		$this->tb_p2i        = 'el_ishop_'.$this->shop_id.'_p2i';
		$this->tb_i2c        = 'el_ishop_'.$this->shop_id.'_i2c';
		
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
			if ($cars['name'] == 'CAR')
			{
				$car = array();
				//$car = new stdClass();
				foreach ($cars['child'] as $a)
				{
					$k = $a['name'];
					$v = $a['content'];

					if ($k == 'MNFCDATE')
						$v = date('Y', strtotime($v));
					elseif (in_array($k, array('PRICERUB', 'PRICEUSD', 'ENGINECUBATURE')))
					{
						$v = str_replace(',', '.', $v); // conver to PHP numbers
						$v = sprintf('%.2f', $v);
					}

					$car[$k] = trim($v);
				}
				array_push($this->cars, $car);
			}
	}

	function getProps()
	{
		$props = array();
		$valid_props = array_keys($this->prop_bind);
		foreach ($this->cars as $car)
			foreach ($car as $p => $v)
			{
				if (!(in_array($p, $valid_props)))
					continue;

				if (!(isset($props[$p])))
					$props[$p] = array();

				array_push($props[$p], $v);
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
		// 1. delete old
		$sql = "TRUNCATE TABLE ".$this->tb_item;
		mysql_query($sql, $this->m);
		$sql = "TRUNCATE TABLE ".$this->tb_p2i;
		mysql_query($sql, $this->m);
		$sql = "TRUNCATE TABLE ".$this->tb_i2c;
		mysql_query($sql, $this->m);

		// 2. new auto
		foreach ($this->cars as $car)
		{
			// 2.1 new item
			$mnf_id = $this->_getMnfByName($car['BRAND']);
			$sql = "INSERT INTO ".$this->tb_item." (type_id, mnf_id, code, name, price, crtime, mtime) VALUES (3, '%d', '%s', '%s', '%s', '%d', '%d')";
			$sql = sprintf($sql, $mnf_id, $car['CARID'], $car['MODEL'], $car['PRICERUB'], time(), time());
			mysql_query($sql, $this->m);
			$i_id = mysql_insert_id();

			// 2.2 new props
			foreach ($this->prop_bind as $p => $p_id)
			{
				$pv_id = $this->_getPropValueIdByName($p_id, $car[$p]);
				//print "$p $car[$p] => $pv_id\n";
				$sql = "INSERT INTO ".$this->tb_p2i." (i_id, p_id, value, pv_id) VALUES ('%d', '%d', '%s', '%d')";
				$sql = sprintf($sql, $i_id, $p_id, $pv_id, $pv_id);
				mysql_query($sql, $this->m);
			}

			// 2.3 new attr
			foreach ($this->attr_bind as $a => $p_id)
			{
				$sql = "INSERT INTO ".$this->tb_p2i." (i_id, p_id, value) VALUES ('%d', '%d', '%s')";
				$sql = sprintf($sql, $i_id, $p_id, $car[$a]);
				mysql_query($sql, $this->m);
			}

			// 2.4 add item to category
			$sql = "INSERT INTO ".$this->tb_i2c." (i_id, c_id) VALUES ('%d', '%d')";
			$sql = sprintf($sql, $i_id, '1');
			mysql_query($sql, $this->m);
		}
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

$file = '/home/troex/Downloads/TradeIn.xml';

$import = new IShopImportLexus();
$import->file = $file;
$import->parseXML();
$import->getCars();
$import->getProps();
$import->loadProps();
$import->loadMnf();
$import->loadCars();
//print_r($import->props);
//print_r($import->cars);

//require_once('./index.php');


