<?php

class elDbDump {
	
	var $_db = null;
	
	function elDbDump($db)
	{
		if ($db)
		{
			$this->_db = $db;
		}
		else
		{
			$this->_db = & elSingleton::getObj('elDb');
		}
	}
	
	/**
	 * Возвращает дамп таблицы
	 *
	 * @param  string  $tb          имя таблицы
	 * @param  string  $addDrop     добавить drop table if exists
	 * @param  int     $insertSize  кол-во записей в одной конструкции insert
	 * @return string
	 **/
	function dumpTable($tb, $addDrop=true, $insertSize=30)
	{
		if ( !$this->_db->query('SHOW CREATE TABLE '.$tb) )
		{
			elThrow(E_USER_WARNING, 'Table %s does not exists', $tb, EL_URL);
		}
		
		$dump  = $addDrop ? 'DROP TABLE IF EXISTS `'.$tb."`;\n--\n" : '';
		$row = $this->_db->nextRecord();
		$dump .= $row['Create Table'].";\n--\nLOCK TABLES `".$tb."` WRITE;\n--\n";

		$fields = $primary = $tpls = $isStrings = array();
		if ( !$this->_db->query('SHOW FIELDS FROM '.$tb) )
		{
			elThrow(E_USER_WARNING, 'Could not get fields info in table %s', $tb, EL_URL);
		}
		while ($row = $this->_db->nextRecord())
		{
			$fields[] = $row['Field'];
			if ('PRI' == $row['Key'])
			{
				$primary[] = $row['Field'];
			}
			
			$tpls[$row['Field']] = strstr($row['Type'], 'int') ? '%d' : '"%s"';
			if ( strstr($row['Type'], 'char') || strstr($row['Type'], 'text') || strstr($row['Type'], 'blob') )
			{
				$isStrings[] = $row['Field'];
			}
			
		}

		$fields = implode(', ', $fields);
		$head   = sprintf('INSERT INTO %s (%s) VALUES ', $tb, $fields);
		$body   = '('.implode(', ', $tpls).')'; 
		
		$sql = sprintf('SELECT %s FROM %s ORDER BY %s', $fields, $tb, implode(', ', $primary));
		if ( !$this->_db->query($sql) )
		{
			elThrow(E_USER_WARNING, 'Could not fetch records from table %s', $tb, EL_URL);
		}
		$s        = sizeof($isStrings);
		$cnt      = 1;
		$dataDump = '';
		$locale = setlocale(LC_ALL, 0);
		setlocale(LC_ALL, 'en_US');
		while ($row = $this->_db->nextRecord())
		{
			for ($i=0; $i < $s; $i++) 
			{ 
				$row[$isStrings[$i]] = mysql_real_escape_string($row[$isStrings[$i]]);
			}
			$dataDump .= vsprintf($body, $row).", \n";
			if ( $cnt++ >= $insertSize)
			{
				$cnt      = 1;
				$dump    .= $head.substr($dataDump, 0, -3).";\n--\n";
				$dataDump = '';
			}
		}
		
		if ( !empty($dataDump) )
		{ 
			$dump .= $head.substr($dataDump, 0, -3).";\n--\n";
		}
		$dump .= "\nUNLOCK TABLES;\n--\n";
		setlocale(LC_ALL, $locale);
		return $dump;
		
	}
	
	
	/**
	 * возвращает дапм базы в виде строки
	 *
	 * @param  string  $prefix  префикс таблиц включаемых в дамп
	 * @return string
	 **/
	function dump($prefix='')
	{
		$dump = '';
		if( false != ($tables = $this->_db->tablesList()) )
		{
			for ($i=0, $s=sizeof($tables); $i < $s; $i++) 
			{ 
				if (!$prefix || 0 === strpos($tables[$i], $prefix))
				{
					if ( false == ($tbDump = $this->dumpTable($tables[$i])) )
					{
						return false;
					}
					$dump .= $tbDump."\n\n";
				}
			}
		}
		return $dump;
	}
	
	/**
	 * Записывает дамп базы в файл
	 *
	 * @param  string  $file    имя файла для сохранения
	 * @param  string  $prefix  префикс таблиц включаемых в дамп
	 * @return bool
	 **/
	function writeDump($file, $prefix='')
	{
		if (false == ($fp = fopen($file, 'w')))
		{
			elThrow(E_USER_WARNING, 'Could not write to file %s', $file, EL_URL);
		}
		if( false != ($tables = $this->_db->tablesList()) )
		{
			for ($i=0, $s=sizeof($tables); $i < $s; $i++) 
			{ 
				if (!$prefix || 0 === strpos($tables[$i], $prefix))
				{
					if ( false == ($tbDump = $this->dumpTable($tables[$i])) )
					{
						return false;
					}
					fwrite($fp, $tbDump."\n\n");
				}
			}
		}
		@fclose($fp);
		return true;
	}
	
	/**
	 * Загружает дамп из файла в базу ( дамп должен быть сделан elMySQLDump::dump())
	 *
	 * @param  string  $file    имя файла дампа
	 * @return bool
	 **/
	function restore($file)
	{
		if ( !file_exists($file) || !is_readable($file) )
		{
			elThrow(E_USER_WARNING, 'File %s does not exists or not readable', $file, EL_URL);
		}
		if ( false == ($raw = file_get_contents($file)) )
		{
			elThrow(E_USER_WARNING, 'File %s is empty', $file, EL_URL);
		}
		$queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $raw);
		foreach ($queries as $sql)
		{
			if (strlen(trim($sql)) > 0)
			{
				if ( !$this->_db->query($sql) )
				{
					elThrow(E_USER_WARNING, 'MySQL query failed: %s', mysql_error(), EL_URL);
					return false;
				}
			}
		}
		return true;
	}

}

?>
