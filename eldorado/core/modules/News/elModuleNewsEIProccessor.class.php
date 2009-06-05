<?php
include_once 'elModuleEIProccessor.class.php';

class elModuleNewsEIProccessor extends elModuleEIProccessor
{
	/**
	 * List of required fields in imported XML
	 *
	 * @var array
	 */
	var $_reqFields = array('TITLE', 'ANNOUNCE', 'CONTENT', 'PUBLISHED');
	
	function export($param)
	{
		$newsObj = & $this->_module->_getNews();
		$coll = $newsObj->getCollectionToArray(null, $this->_paramToSQL($param), 'published');
		
		$ret  = "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>\n";
		$ret .= "<exportData>\n";
		
		foreach ($coll as $one)
		{
			$ret .= "<news>\n";
			$ret .="<id>".$one['id']."</id>\n";
			$ret .="<title><![CDATA[ ".$one['title']." ]]></title>\n";
			$ret .="<announce><![CDATA[ ".$one['announce']." ]]></announce>\n";
			$ret .="<content><![CDATA[ ".$one['content']." ]]></content>\n";
			$ret .="<published>".$one['published']."</published>\n";
			$ret .="<export_param><![CDATA[ ".$one['export_param']." ]]></export_param>\n";
			$ret .= "</news>\n";
		}
		$ret .= "</exportData>\n";
		//echo nl2br(htmlspecialchars($ret));
		return $ret;
	}
	
	
	function import(  )
	{
		$URL  = $this->_getImportURL(); //echo $URL;
		$vals = $index = array();
		if (!$this->_parseIntoStruct($URL, $vals, $index))
		{
			return false;
		}
		$newsObj = & $this->_module->_getNews();
		$newsObj->deleteAll();
		
//		echo 'VALS<br>'; elPrintR($vals);
//		echo 'INDEX<br>'; elPrintR($index);
		
		for ($i=0, $s=sizeof($index['ID']); $i<$s; $i++)
		{
			//$id =   $vals[$index['ID'][$i]]['value']; echo $id;
			$newsObj->setUniqAttr(0);
			$newsObj->setAttr('title', $vals[$index['TITLE'][$i]]['value']);
			$newsObj->setAttr('announce', $vals[$index['ANNOUNCE'][$i]]['value']);
			$newsObj->setAttr('content', $vals[$index['CONTENT'][$i]]['value']);
			$newsObj->setAttr('published', $vals[$index['PUBLISHED'][$i]]['value']);
			$newsObj->setAttr('export_param', $vals[$index['EXPORT_PARAM'][$i]]['value']);
			$newsObj->save();
		}
		return true;
	}
	
}

?>