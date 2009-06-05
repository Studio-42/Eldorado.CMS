<?php

class elCache
{
	var $dir = EL_DIR_CACHE;
	var $groupDirs = array();
	var $_enable = true;
	var $_ttl = 86400;
	var $_gcProbability = 3;

	function elCache($dir=EL_DIR_CACHE, $enable=true, $ttl=120)
	{
		$this->dir = $dir;
		$this->_enable = $enable;
		if ( $ttl )
		{
			$this->ttl = $ttl;
		}
		$this->_gc();
	}

	function get($id, $group='default')
	{
		if ( $this->_enable )
		{
			$file = $this->_fileName($id, $group);
			return is_readable($file) && !$this->_isExpired($file)
			? $this->_fetch($file) : null;
		}
		return null;
	}

	function _isExpired($file)
	{
		return filemtime($file) < time() - $this->_ttl;
	}

	function _fetch($file)
	{
		$data = null;
		if ( $fp = fopen($file, 'r') )
		{
			flock($fp, LOCK_SH);
			//first line is data type flag
			//0 - data is raw text;
			//1 - serialized data
			$type = trim( fgets($fp) );
			$data = (1 == $type{0})
			? unserialize( fread($fp, filesize($file)) )
			: fread($fp, filesize($file));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		return $data;
	}

	function save($id, $data, $group='default')
	{
		if ( $this->_enable )
		{
			$file = $this->_fileName($id, $group);
			if ( $fp = fopen($file, 'w') )
			{
				flock($fp, LOCK_EX);
				$data = !is_string($data)
				? "1\n".serialize($data)
				: "0\n".$data;
				fwrite($fp, $data);
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
	}

	function _fileName($id, $group)
	{
		if ( !isset($this->groupDirs[$group]) )
		{
			$dir = $this->dir . $group;
			if ( !is_dir($dir) )
			{
				mkdir($dir, 0755);
			}
			$this->groupDirs[$group] = $dir;
		}
		return $this->groupDirs[$group] . '/' . $id;
	}

	function _gc()
	{
		if ( !$this->_enable )
		{
			return;
		}
		srand((double) microtime() * 1000000);
		if ( rand(1, 100) < $this->_gcProbability &&  false != ($d = dir($this->dir)))
		{
			while ( $entr = $d->read() )
			{
				if ('.'!=$entr && '..'!=$entr && is_dir($d->path.'/'.$entr) )
				{
					$this->_doGc($d->path.'/'.$entr);
				}
			}
			$d->close();
		}
	}

	function _doGc($dir)
	{
		if (false !=($d = dir($dir)))
		{
			while ( $entr = $d->read() )
			{
				if ( '.'!=$entr && '..'!=$entr)
				{
					if (is_dir($d->path.'/'.$entr))
					{
						$this->_doGc($d->path.'/'.$entr);
					}
					elseif ( $this->_isExpired($d->path.'/'.$entr) )
					{
						@unlink($d->path.'/'.$entr);
					}
				}
			}
			$d->close();
		}
	}
}

?>