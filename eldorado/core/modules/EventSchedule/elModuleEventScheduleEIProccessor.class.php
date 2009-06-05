<?php
require_once 'elModuleEIProccessor.class.php';

class elModuleEventScheduleEIProccessor extends elModuleEIProccessor
{
	var $_reqFields      = array('name', 'announce', 'content', 'place', 'begin_ts', 'end_ts', 'export_param' );
	var $_fieldsSearchIn = array('announce', 'content');
	
	function export($param)
	{
		$eventObj = & $this->_module->_getEvent(); 
		
		$coll = $eventObj->getCollectionToArray(null, $this->_paramToSQL($param), 'id');
	
		$ret  = "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>\n";
		$ret .= "<exportData>\n";
		$ret .= "<baseURL><![CDATA[".EL_BASE_URL."]]></baseURL>\n";
		foreach ($coll as $one)
		{
			$ret .= "<event>\n";
			$ret .= "<id>".$one['id']."</id>\n";
			$ret .= "<name><![CDATA[".$one['name']."]]></name>\n";
			$ret .= "<announce><![CDATA[".$one['announce']."]]></announce>\n";
			$ret .= "<content><![CDATA[".$one['content']."]]></content>\n";
			$ret .= "<place><![CDATA[".$one['place']."]]></place>\n";
			$ret .= "<begin_ts>".$one['begin_ts']."</begin_ts>\n";
			$ret .= "<end_ts>".$one['end_ts']."</end_ts>\n";
			$ret .= "<export_param>".$one['export_param']."</export_param>\n";
			$ret .= "</event>\n";
		}
		$ret .= "</exportData>\n";
		//echo nl2br(htmlspecialchars($ret));
		return $ret;
	}
	
	function import()
	{
		$URL  = $this->_getImportURL(); 
		$vals = $index = array();
		if (!$this->_parseIntoStruct($URL, $vals, $index))
		{
			return false; 
		}
		$eventObj = & $this->_module->_getEvent();
		$eventObj->deleteAll();
		//echo $this->_sourceBaseURL;
		//elPrintR($vals); 
		//elPrintR($index);  exit;
		
		//elPrintR($this->_reqFields); exit;
		for ($i=0, $s=sizeof($index['ID']); $i<$s; $i++)
		{
			//$id =   $vals[$index['ID'][$i]]['value']; echo $id;
			$eventObj->setUniqAttr(0);
			foreach ($this->_reqFields as $f)
			{
			   if ( isset($vals[$index[strtoupper($f)][$i]]['value']))
			   {
			      $val = $vals[$index[strtoupper($f)][$i]]['value'];
			      if ( in_array($f, $this->_fieldsSearchIn))
			      {
			         $this->_searchForFiles($val);
			      }
				  $eventObj->setAttr($f, $val);
			   }
			}
			$eventObj->save();
		}
		return true;
	}
}

?>