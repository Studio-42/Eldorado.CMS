<?php
/**
 *  Template engine.
 */
class elTE
{
	/**
   * array of templates dirs
   * @var array
   */
	var $dir        = 'style/';
	/**
   * array of template file names
   * @var array
   */
	var $files       = array();
	/**
   * array of blocks (objects)
   * @var array
   */
	var $blocks      = array();

	var $fileBlocks  = array();
	/**
   * array of templates content
   * @var array
   */
	var $tplData     = array();
	/**
   * array of assigned vars names
   * @var array
   */
	var $vars        = array('name'=>array(), 'value'=>array());

	var $replaceFrom = array(
														"/{m\('([^']+)'\)}/e",     //m() function
														'/{!([^!]*)!}/e',             //eval
													);
	var $replaceTo   = array(
													"m('\\1')",
													"\\1",
													);


	// ******************  PUBLIC METHODS  ************************* //

	/**
   * The class contructor.
   */
	function elTE()
	{
		// Special vars
		$this->assignVars('BASE_URL',    EL_BASE_URL.'/');
		$this->assignVars('URL',         EL_URL);
		$this->assignVars('WM_URL',      EL_WM_URL);
		$this->assignVars('SUBMOD_URL',  EL_URL);
		$this->assignVars('STYLE_URL',   EL_BASE_URL.'/style/');
		$this->assignVars('STORAGE_URL', EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/');


		$argsStr  = implode('/', $GLOBALS['core']->args);
		$argsStr  = strlen($argsStr) > 1 ? '/'.$argsStr.'/' : '/';
		$argsStr .= !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '';
		$this->assignVars('PRINT_URL', EL_URL.EL_URL_PRNT.$argsStr);
		$this->assignVars('POPUP_URL', EL_URL.EL_URL_POPUP.'/');
		$this->assignVars('LANG',      defined('EL_LANG') ? EL_LANG : 'en');

		$this->replaceFrom[] = '~(href|src)="(/[^\s":]*)"~ism';
		$this->replaceTo[]   = "\\1=\"".EL_BASE_URL."\\2\"";

	}


	function parseFile( $fh, $fileName, $target=null )
	{
		if ( $this->setFile($fh, $fileName) )
		{
			$this->parse($fh, $target);
		}
	}
	/**
   * Add template file.
   *
   * Can add one file or array of files.
   * @param mixed   $handle  template handler or array(handle=>file)
   * @param string  $file    filename
   */
	function setFile($handle, $file, $whiteSpace=false)
	{
		if ( empty($handle) || empty($file) )
		{
			return false;
		}

		if ( empty($this->files[$handle]) )
		{
			if ('/' != $file{0})
			{
				$file = realpath($this->dir.$file);
			}
			if ( !is_readable($file) )
			{
				return false;
			}

			$this->files[$handle]   = $file;
			$this->tplData[$handle] = trim( file_get_contents($file));
			if (!$whiteSpace)
			{
				$this->tplData[$handle] = str_replace(array("\n\n", "\t"),	array("\n", ''), $this->tplData[$handle] );
			}
			
			$this->_cutBlocks($handle, $whiteSpace);
		}
		return true;
	}


	function isTplLoaded($handle)
	{
		return isset($this->tplData[$handle]);
	}

	function isBlockExists($block)
	{
		if ( isset($this->blocks[$block]) )
		{
			return true;
		}
		foreach ( $this->blocks as $k=>$b )
		{
			if ( $b->isBlockExists($block) )
			{
				return true;
			}
		}
		return false;
	}

	function findBlock($block)
	{
		if ( isset($this->blocks[$block]) )
		{
			return $block;
		}
		foreach ( $this->blocks as $k=>$b )
		{
			if ( false != ($found = $b->findBlock($block)))
			{
				return $found;
			}
		}
		return false;
	}
	/**
   * Variable assignment.
   *
   * @param mixed   $var  variable name or array of variables
   * @param string  $val  variable value
   * @param bool    $append append new value to var value
   */
	function assignVars($var, $val='', $append=false, $as_tpl=false)
	{
		if (!is_array($var))
		{
			if ( $as_tpl )
			{
				$val = str_replace( $this->vars['name'], $this->vars['value'], $val);
			}
			$this->vars['name'][$var] = '{'.$var.'}';


			if ( !$append || !isset($this->vars['value'][$var]) )
			{
				$this->vars['value'][$var] = $val;
			}
			else
			{
				$this->vars['value'][$var] .= $val;
			}
		}
		else
		{
			foreach ($var as $name=>$val)
			{
				if ( $as_tpl )
				{
					$val = str_replace( $this->vars['name'], $this->vars['value'], $val);
				}
				$this->vars['name'][$name] = '{'.$name.'}';

				if ( !$append || !isset($this->vars['value'][$name]) )
				{
					$this->vars['value'][$name] = $val;
				}
				else
				{
					$this->vars['value'][$name] .= $val;
				}
			}
		}
	}

	function isBlockUsed($block)
	{
		return !empty($this->_blockUse[$block]);
	}

	/**
   * Assign variables for block.
   *
   * Param path contain path to nested block in format "PARENT.CHILD1.CHILD2" or top-level block name.
   * Level - level of nested blocks.
   * This param tells all blocks with level >= $level to create new iteration.
   * So you can set variables for one block iteration several times.
   * Top-level block has level = 1.
   *
   * @param string  $path path to block
   * @param array   $vars variables
   * @param int   $level  level
   */
	function assignBlockVars($path, $vars=null, $level=0)
	{
		$block = (($pos = strpos($path, '.')) !== false) ? substr($path, 0, $pos) : $path;

		if ( !isset($this->blocks[$block]) )
		{
			return elThrow(E_USER_WARNING, 'elTE::assignBlockVars: Block '.$block.' does not exists', null, null, false, __FILE__, __LINE__);
		}
		$this->_blockUse[$path] = 1;
		$this->blocks[$block]->assignVars($path, $vars, $level);
	}

	function assignBlockFromArray($blocks, $data, $level=0, $parentBlock=null)
	{
		if ( !is_array($blocks) )
		{
			while ($one = array_shift($data) )
			{
				$this->assignBlockVars($blocks, $one, $level);
			}
		}
		else
		{
			while ($one = array_shift($data) )
			{
				foreach ( $blocks as $block )
				{
					if ( $parentBlock )
					{
						$block = $parentBlock.'.'.$block;
					}
					$this->assignBlockVars($block, $one, $level);
				}
			}

		}
	}

	function assignBlockOnce($path, $vars=null, $level=0)
	{
		if (!$this->isBlockUsed($path) && $this->isBlockExists($path))
		{
			$this->assignBlockVars($path, $vars, $level);
		}
	}

	function assignBlockIfExists($block, $vars)
	{
		if (false != ($path = $this->findBlock($block)))
		{
			$level = substr_count($path, '.'); //echo $path.' '.$level;
			$this->assignBlockVars($path, $vars, $level);
		}
	}
	/**
   * Parse template into var.
   *
   * If target is not set, parse into variable with name of template handler.
   *
   * @param string  $handle template handler
   * @param string  $target variable name to parse in
   * @param bool    $append append parsed string to variable
   */
	function parseWithNested($handle, $target=null, $append = false, $glob=false)
	{
		if (!isset($this->tplData[$handle]))
		{
			return elThrow(E_USER_WARNING, 'elTE::parseWithNested: no template '.$handle.' to parse',
			null, null, false, __FILE__, __LINE__);
		}

		foreach ( $this->files as $h=>$file )
		{
			if ($h != $handle && strstr($this->tplData[$handle], '{'.$h.'}')
			&& !isset($this->vars['value'][$h]))
			{
				$this->parse($h, $h);
			}
		}
		$this->parse($handle, $target, $append, $glob);
	}



	function parse($handle, $target = null, $append=false, $glob=false, $clean=false)
	{
		if ( empty($this->tplData[$handle]) )
		{
			return elThrow(E_USER_WARNING, 'elTE::parse: no template '.$handle.' to parse',
			null, null, false, __FILE__, __LINE__);
		}

		if ( isset($this->fileBlocks[$handle]) )
		{
			foreach ($this->fileBlocks[$handle] as $block)
			{
				$this->tplData[$handle] = str_replace('{BLOCK.'.$block.'}', $this->blocks[$block]->parse(), $this->tplData[$handle]);
			}
		}
		$this->tplData[$handle] = str_replace($this->vars['name'], $this->vars['value'], $this->tplData[$handle]);
		if ( $glob && $this->replaceFrom )
		{
			$this->tplData[$handle] = $this->globReplace( $this->tplData[$handle] );
		}
		$this->assignVars($target ? $target : $handle, $this->tplData[$handle], $append);

		if ($clean)
		{
			if (isset($this->files[$handle]))
			{
				unset($this->files[$handle], $this->tplData[$handle]);
			}
			if (isset($this->fileBlocks[$handle]))
			{
				unset($this->fileBlocks[$handle]);
			}
		}
	}


	function globReplace( $str )
	{
		return preg_replace($this->replaceFrom, $this->replaceTo, $str);
	}

	function dropUndefined( $str )
	{
		return preg_replace( '/{([a-z0-9_\.]+)}/i', '', $str );
	}

	/**
   * print value of variable
   *
   * @param string  var name
   */
	function fprint($handle)
	{
		if ( !isset($this->vars['value'][$handle]) )
		{
			if ( isset($this->tplData[$handle]) )
			{
				$this->parseWithNested($handle, null, null, true);
			}
			else
			{
				return elThrow(E_USER_WARNING, 'elTE::fprint: template handle does not defined', null, null, false, __FILE__, __LINE__);
			}
		}

		echo $this->dropUndefined( $this->vars['value'][$handle] );
	}

	function getVar($handle, $glob=false)
	{
		$str = isset($this->vars['value'][$handle]) ? $this->vars['value'][$handle] : '';

		if ( $glob )
		{
			$this->vars['value'][$handle] = $this->globReplace($this->vars['value'][$handle]);
		}
		return $str;
		//return $str && $glob ? $this->globReplace($str) : $str;
	}



	function dropBlock($block)
	{
		if ( isset($this->blocks[$block]) )
		{
			unset($this->blocks[$block]);
			foreach ( $this->fileBlocks as $h=>$bList )
			{
				if ( $ndx = array_search($block, $bList) )
				{
					unset($this->fileBlocks[$h][$ndx]);
					return;
				}
			}
		}
	}


	// ====================  PRIVATE METHODS  ===================== //


	/**
   * Scan template content for dinamic blocks.
   *
   * Block defines as "<!-- BEGIN block_name --> here {block} content <!-- END block_name -->".
   * If any one found, for each top-level block create object Block and give him his content.
   * Block scan his content for nested blocks and create childs objects
   * for each his top-level block, and so on so on...
   * @param string  $handle template handler
   */
	function _cutBlocks($handle, $whiteSpace=false)
	{
		$reg = '/<!--\s+BEGIN\s+([a-z0-9_]+)\s+-->\s*(.*)\s*<!--\s+END\s+(\\1)\s+-->/smi';
		if (preg_match_all($reg, $this->tplData[$handle], $m))
		{
			for ($i=0, $s=sizeof($m[1]); $i < $s; $i++)
			{
				$this->fileBlocks[$handle][] = $m[1][$i]; // bind block name to file handler
				$this->blocks[$m[1][$i]]     = new elTEBlock($m[1][$i], !$whiteSpace ? trim($m[2][$i]) : $m[2][$i]  );
				$this->tplData[$handle]      = str_replace($m[0][$i], '{BLOCK.'.$m[1][$i].'}', $this->tplData[$handle]);
			}
		}
	}

}

//////////////////////////////////////////////////////////////////

/**
 * Auxiliary class for elTE for dinamic block manipulations.
 * Dont use it directly.
 * @access  privare
 */

class elTEBlock
{
	/**
   * Block name
   * @var string
   */
	var $name = '';
	/**
   * Block content.
   * @var string
   */
	var $content = '';
	/**
   * Array of nested top-levels blocks
   * @var array
   */
	var $childs = array();
	/**
   * Block variables array.
   * Var[key][inum] = array('vars'=>array(), 'vals'=>array())
   * key - key of parents iteration.
   * inum - number of iteration in current parent iteration
   * vars - array of variables names
   * vals - array of variable values
   * @var array
   */
	var $vars = array();

	/**
   * Class contructor.
   * Search content for nested blocks and create childs object for each top-level block.
   * Конструктор. Устанавливает имя блока и контент.
   *
   * @param string  $name block name
   * @param string  $cont block content
   */
	function elTEBlock($name, $cont)
	{
		$this->name    = $name;
		$this->content = $cont;

		$reg = '/<!--\s+BEGIN\s+([a-z0-9_]+)\s+-->\s*(.*)\s*<!--\s+END\s+(\\1)\s+-->/smi';
		if (preg_match_all($reg, $this->content, $m))
		{
			for ($i=0, $s=sizeof($m[1]); $i < $s; $i++)
			{
				$this->childs[$m[1][$i]] = new elTEBlock($m[1][$i], $m[2][$i]);
				$this->content = str_replace($m[0][$i], '{BLOCK.'.$m[1][$i].'}', $this->content);
			}
		}
	}

	function isBlockExists($block)
	{
		if ( isset($this->childs[$block]) )
		{
			return true;
		}
		foreach ($this->childs as $k=>$b)
		{
			if ( $b->isBlockExists($block) )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Find nested block by name and return full name (path)
	 * for example - findBlock('SOME_BLOCK') may return 'PARENT_BLOCK.SOME_BLOCK'
	 *
	 * @param  string $block  nested block name
	 * @return string         full nested block name
	 */
	function findBlock($block)
	{
		if ( !empty($this->childs[$block]) )
		{
			return $this->name.'.'.$block;
		}
		foreach ($this->childs as $k=>$b)
		{
			if ( false != ($found = $b->findBlock($block) ))
			{
				return $this->name.'.'.$found;
			}
		}
		return false;
	}

	/**
   * Set block variables.
   * If variables addressed to this block - set its.
   * Otherwise pass data to child block.
   * While passed data to child, decrease level by 1
   * and add to parent key number of his iteration.
   *
   * @param string  $name block name or path to block
   * @param array   $data block variables
   * @param int   $level  on wich level create new iteration
   * @param string  $key  parent iteration key
   */
	function assignVars($name, $data, $new_ilevel=0, $parent_key='0')
	{
		// Iteration number 'inside' parent iteration
		// level <= 0 means this block must create new iteration
		// level > 0 - data should be addded to previous iteration,
		// if there is no previous iteration - create it
		if (0 == $new_ilevel || !isset($this->vars[$parent_key]) )
		{
			$this->vars[$parent_key][] = array();
		}

		$inum = sizeof($this->vars[$parent_key])-1;

		if ($name == $this->name)
		{ // data addressed to this block
			if (is_array($data))
			{
				foreach ($data as $var=>$val)
				{
					$this->vars[$parent_key][$inum]['vars'][$var] = '{'.$var.'}';
					$this->vars[$parent_key][$inum]['vals'][$var] = $val;
				}
			}
			return;
		}
		// data addressed to child block
		$name = explode('.', $name);
		unset($name[0]); // delete this block name from path

		if (!$this->childs[$name[1]])
		{ // child block not exists - show warning
			return elThrow(E_USER_WARNING, 'elTEBlock['.$this->name.']::assignVars(): Child block '.$name[1].' was not found',
			null, null, false, __FILE__, __LINE__);
		}
		// pass data to child
		$this->childs[$name[1]]->assignVars(implode('.', $name), $data, $new_ilevel-1, $parent_key.'_'.$inum);
	}

    /**
   * Parse content and return it.
   *
   * Replace childs blocks declarations to childs parsed content
   *
   * @param string  $key parent iteration key
   * @return  string
   */
	function parse($key='0')
	{
		$res = '';
		if (isset($this->vars[$key]))
		{
			foreach ($this->vars[$key] as $i=>$vars)
			{
				$str = !empty($vars) ? str_replace($vars['vars'], $vars['vals'], $this->content) : $this->content;
				foreach ($this->childs as $name=>$child)
				{
					$str = str_replace('{BLOCK.'.$name.'}', $this->childs[$name]->parse($key .'_'.$i), $str);
				}
				$res .= $str;
			}
		}
		return $res;
	}
}
?>
