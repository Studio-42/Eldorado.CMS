<?php
ignore_user_abort(true);
set_time_limit(0);
/**
 * Parent class for modules EI proccessor classes
 *
 */
class elModuleEIProccessor
{
   /**
	 * Reference to module object
	 *
	 * @var object
	 */
   var $_module = null;
   /**
	 * List of required fields in imported XML
	 *
	 * @var array
	 */
   var $_reqFields = array();

   /**
	 * List of fields to search files names in it to copy files from remote server
	 *
	 * @var array
	 */
   var $_fieldsSearchIn = array();

   /**
    * Remote site base URL - need for coping files 
    *
    * @var unknown_type
    */
   var $_sourceBaseURL = '';

   /**
	 * Set refrence to module object
	 *
	 * @param object $module
	 */
   function init(&$module)
   {
      $this->_module = &$module;
   }

   /**
	 * Create and return XML
	 *
	 * @param string $param
	 */
   function export($param=null)
   {
   }

   /**
	 * Read responce from URL, parse as XML and put into DB
	 *
	 * @return bool
	 */
   function import()
   {
      return false;
   }

   /**
	 * Check is export allowed (in conf file). If not - put message in $err
	 *
	 * @param string $err
	 * @return bool
	 */
   function isExportAllowed(&$err)
   {
      if ( !$this->_module->_conf('export') )
      {
         $err = m('Export does not allowed');
         return false;
      }
      if ( false != ($KEY = $this->_module->_conf('exportKEY')) && (empty($_GET['key']) || $_GET['key'] != $KEY ) )
      {
         $err = m('Invalid import key');
         return false;
      }
      return true;
   }

   /**
	 * parse $param (export param) and return "where" part of sql query
	 *
	 * @param string $param
	 * @return string
	 */
   function _paramToSQL($param, $fieldName='export_param')
   {
      if ( empty($param) )
      {
         return '';
      }
      $param = explode(',', $param);
      $sql   = '';
      foreach ( $param as $p )
      {
         $sql .= $fieldName.' RLIKE "(\,)?('.$p.')(\,)?" OR ';
      }
      return substr($sql, 0, -3);
   }

   /**
	 * Parse config params and return URL to get XML data
	 *
	 * @return string
	 */
   function _getImportURL()
   {
      $URL   = $this->_module->_conf('importURL');
      $param = $this->_module->_conf('importParam');
      $KEY   = $this->_module->_conf('importKEY');
      if ('/' != substr($URL, -1) )
      {
         $URL .= '/';
      }
      $URL .= EL_URL_XML.'/';
      if ($param)
      {
         $URL .= $param.'/';
      }
      if ($KEY)
      {
         $URL .= '?key='.$KEY;
      }
      return $URL;
   }

   /**
    * Read file from remote server and return it's content
    * Return false on failed or if file is empty
    *
    * @param  string $URL
    * @return string
    */
   function _getRemoteFile($URL, $fpRet=false)
   {
      $fp   = tmpfile();
      
      if (!$this->_module->_conf('useCurl') || !function_exists('curl_init'))
      {
         if (false == ($sfp = fopen($URL, 'r')) )
         {
            return elThrow(E_USER_ERROR, 'Import error! Could not get data from %s. Error: %s', array($URL, ''));   
         }
         while (!feof($sfp))
         {
           fwrite($fp, fgets($sfp));
         }
//         $data = file_get_contents( $URL);
//         if ( empty($data) )
//         {
//            return elThrow(E_USER_ERROR, 'Import error! Could not get data from %s', $URL);
//         }
      }
      else
      {
         //$fp   = tmpfile();
         $data = '';
         $c    = curl_init();
         curl_setopt($c, CURLOPT_URL,    $URL);
         curl_setopt($c, CURLOPT_HEADER, 0);
         curl_setopt($c, CURLOPT_FILE,   $fp);
         curl_exec($c);
         if ( false != ($cErr = curl_errno($c) ))
         {
            return elThrow(E_USER_ERROR, 'Import error! Could not get data from %s. Error: %s', array($URL, curl_error($c)));
         }
         curl_close($c);
      }
      fseek($fp, 0);
      if ($fpRet)
      {
         return $fp;
      }
      while (!feof($fp))
      {
         $data .= fgets($fp);
      }
      fclose($fp);
      return $data;
   }

   /**
	 * Fetch XML from URL, valid fetched data and parse into array or produce error
	 *
	 * @param  string $URL
	 * @param  array $vals
	 * @param  array $index
	 * @return bool
	 */
   function _parseIntoStruct($URL, &$vals, &$index)
   {
      if ( false == ($data = $this->_getRemoteFile($URL)) )
      {
         return false;
      }
      //echo nl2br(htmlspecialchars($data)); exit;
      
      $p = xml_parser_create();
      xml_parse_into_struct($p, $data, $vals, $index);
      xml_parser_free($p);
      if ( empty($index) )
      {
         return elThrow(E_USER_ERROR, 'Import error! Could not get data from %s', $URL);
      }
      if (!empty($index['ERROR']) )
      {
         return elThrow(E_USER_ERROR, 'Import error! Error message is: %s', $vals[$index['ERROR'][0]]['value']);
      }
      if (!empty($this->_reqFields))
      {
         foreach ($this->_reqFields as $f)
         {
            $f = strtoupper($f);
            if ( !isset($index[$f]))
            {
               return elThrow(E_USER_ERROR, 'Import error! Recieved data has invalid structure!');
            }
         }
      }
      if (!empty($index['BASEURL'][0]))
      {
         $this->_sourceBaseURL = $vals[$index['BASEURL'][0]]['value'];
      }
      return true;
   }

   /**
    * Search file names in string and copy files from remote server
    *
    * @param string $str
    */
   function _searchForFiles($str)
   {
      if (preg_match_all('|(/'.EL_DIR_STORAGE_NAME.'/[^"]+)|ism', $str, $m))
      {
         foreach ( $m[0] as $f)
         {
            $this->_copyFile($f);
         }
      }
   }

   /**
    * Save remote file in EL_DIR_STORAGE subdirectory
    *
    * @param  string $file
    * @return bool
    */
   function _copyFile($file)
   {
      $path = dirname($file); 
      if (!$this->_checkPath($path))
      {
         return false;
      }
      if ( file_exists('.'.$file) )
      {
         return true;
      }
      if (false == ($raw = $this->_getRemoteFile($this->_sourceBaseURL.$file)) )
      {
         return false;
      }
      if (false == ($fp  = fopen('.'.$file, 'w')))
      {
         return elThrow(E_USER_ERROR, 'Could write to file %s', $file);
      }
      fwrite($fp, $raw);
      fclose($fp);
      return true;
   }

   /**
    * Check is directories tree exists in EL_DIR_STORAGE and create it if it is not exists
    *
    * @param  string $path
    * @return bool
    */
   function _checkPath($path)
   {
      $dirs = !strstr($path, '/') ? array($path) : explode('/', $path);
      $path = '.';
      foreach ($dirs as $dir)
      {
         if ('.'!=$dir && !empty($dir))
         {
            $path .= '/'.$dir;
            if (!is_dir($path) && !mkdir($path))
            {
               return elThrow(E_USER_ERROR, 'Could not create %s', $path);
            }
         }
      }
      return true;
   }
}
?>