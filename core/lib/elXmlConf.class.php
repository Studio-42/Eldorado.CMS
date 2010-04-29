<?php

if (!defined('EL_DIR_CONF') )
{
	define ('EL_DIR_CONF', './conf/');
}

class elXmlConf
{
	var $dir       = EL_DIR_CONF;
	var $file      = 'main.conf.xml';
	var $groups    = array();
	var $curGroup  = '';
	var $_defGroup = 'common';
	var $_parser   = null;
	var $_gr       = '';
	var $_ct       = '';
	var $_el       = '';
	var $_tags     = array('GROUP'=>'_gr', 'CONTAINER'=>'_ct', 'NODE'=>'_el');
	var $_trans    = '';

	function elXmlConf( $file = 'main.conf.xml', $dir=EL_DIR_CONF, $haltOnLoadError=true )
	{
		$this->dir  = $dir;
		$this->file = $this->dir.$file;
		$this->resetCurrent();
		$this->_load($haltOnLoadError);
	}

	function setCurrent( $group )
	{
		$this->curGroup = isset($this->groups[$group]) ? $group : $this->_defGroup;
	}

	function resetCurrent()
	{
		$this->curGroup = $this->_defGroup;
	}

	function set( $var, $val, $group=null )
	{
		$this->groups[($group ? $group : $this->curGroup)][$var] = $val;
	}

	function makeGroup( $group, $vals=null )
	{
		if ( !isset($this->groups[$group]) )
		{
			$this->groups[$group] = array();
		}
		if ( !empty($vals) && is_array($vals) )
		{
			$this->groups[$group] = $vals;
		}
	}

	function get( $var, $group=null )
	{
		if (!$group)
		{
			$group = $this->curGroup;
		}
		return isset($this->groups[$group][$var]) ? $this->groups[$group][$var] : null;
	}

	function getGroup( $group )
	{
		return isset($this->groups[$group]) ? $this->groups[$group] : null;
	}

	function isGroupExists( $group )
	{
		return isset($this->groups[$group]);
	}

	function findGroup( $var, $val, $all=false )
	{
		$ret = $all ? array() : null;

		foreach ( $this->groups as $n=>$gr )
		{
			if ( isset($gr[$var]) && $gr[$var] == $val )
			{
				if ( $all )
				{
					$ret[] = $n;
				}
				else
				{
					return $n;
				}
			}
		}
		return $ret;
	}


	function drop($var, $group=null)
	{
		if ( !$group )
		{
			$group = $this->curGroup;
		}
		if ( isset($this->groups[$group][$var]) )
		{
			unset($this->groups[$group][$var]);
		}
	}

	function cleanGroup( $group )
	{
		if ( isset($this->groups[$group]) )
		{
			$this->groups[$group] = array();
		}
	}

	function dropGroup( $group )
	{
		if ( isset($this->groups[$group]) )
		{
			unset($this->groups[$group]);
		}
	}

	function save()
	{
		if ( !is_writable($this->file) || ($fp = fopen($this->file, 'w')) == false )
		{
			return elThrow(E_USER_WARNING, 'Can not write to file "%s"', $this->file);
		}
		$str = '';
		foreach ( $this->groups as $n=>$gr )
		{
			$str .= '<GROUP name="'.htmlspecialchars($n). "\">\n";

			foreach ( $gr as $k=>$v )
			{
				if ( !is_array($v) )
				{
					$str .= '<NODE name="'. htmlspecialchars($k).'">'.htmlspecialchars(trim($v))."</NODE>\n";;
				}
				else
				{
					$str .= '<CONTAINER name="'.htmlspecialchars($k)."\">\n";
					foreach ( $v as $k1=>$v1 )
					{
						if ( !is_array($v1) )
						{
							$str .= '<NODE name="'. htmlspecialchars($k1).'">'.htmlspecialchars(trim($v1))."</NODE>\n";
						}
						else
						{
							$str .= '<NODE name="'. htmlspecialchars($k1).'" isArray="yes">'.implode(',', $v1)."</NODE>\n";
						}
					}
					$str .= "</CONTAINER>\n";
				}
			}
			$str .= "</GROUP>\n";
		}
		$str = "<?xml version=\"1.0\"?>\n<ROOT>\n" . $str . "</ROOT>\n";

		@flock($fp, LOCK_EX);
		fwrite($fp, $str);
		@flock($fp, LOCK_UN);
		fclose($fp);
		return true;
	}

	//*****************    PRIVATE METHODS  **************************//

	function _load($haltOnLoadError)
	{
		if ( false == ($fp = fopen($this->file, 'r')) )
		{
			elThrow(E_USER_ERROR, 'Could not read "%s"', $this->file, null, (int)$haltOnLoadError, __FILE__, __LINE__);
		}
		$this->_trans = array_flip(get_html_translation_table(HTML_SPECIALCHARS));
		$this->_parser = xml_parser_create();

		xml_set_object($this->_parser, $this);
		xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 1);
		xml_set_element_handler($this->_parser, "_tagOpen", "_tagClose");
		xml_set_character_data_handler($this->_parser, "_cdata");

		while ( !feof($fp) )
		{
			$line = fgets($fp);
			if ( !xml_parse($this->_parser, $line) )
			{
				$err = xml_get_error_code($this->_parser) . ' '
				. xml_error_string( xml_get_error_code($this->_parser) )
				. ' in line: '.xml_get_current_line_number($this->_parser);
				xml_parser_free($this->_parser);
				elThrow(E_USER_ERROR, 'Can not read "%s"', $this->file, $err, 1, __FILE__, __LINE__);
			}
		}
		xml_parser_free($this->_parser);
		unset($this->_parser);
	}

	function _tagOpen($parser, $tag, $attrs)
	{
		if ( isset($this->_tags[$tag]) && isset($attrs['NAME']) && $attrs['NAME']!== '' )
		{
			$this->{$this->_tags[$tag]} = $attrs['NAME']; 
			$this->_ar = isset($attrs['ISARRAY']);
		}
	}

	function _cdata($parser, $cdata)
	{
		if ( $this->_gr && $this->_el!='' )
		{
			if ( $this->_ct)
			{
				if ( !isset($this->groups[$this->_gr][$this->_ct][$this->_el]) )
				{
					$this->groups[$this->_gr][$this->_ct][$this->_el] = '';
				}
				if ( $this->_ar)
				{
					$this->groups[$this->_gr][$this->_ct][$this->_el] = explode(',', $cdata); //elPrintR($cdata);
				}
				else
				{
					$this->groups[$this->_gr][$this->_ct][$this->_el] .= strtr($cdata, $this->_trans);
				}
			}
			else
			{
				if ( !isset($this->groups[$this->_gr][$this->_el]) )
				{
					$this->groups[$this->_gr][$this->_el] = '';
				}
				$this->groups[$this->_gr][$this->_el] .= strtr($cdata, $this->_trans);
			}
		}
	}

	function _tagClose($parser, $tag)
	{
		if ( isset($this->_tags[$tag]) )
		{
			$this->{$this->_tags[$tag]} = '';
		}
	}

}

?>
