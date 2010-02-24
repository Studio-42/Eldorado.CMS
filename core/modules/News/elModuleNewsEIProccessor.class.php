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
		$coll = $newsObj->collection(false, true, $this->_paramToSQL($param), 'published');
		
		$ret  = "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>\n";
		$ret .= "<exportData>\n";
		$ret .= "<baseURL><![CDATA[".EL_BASE_URL."]]></baseURL>\n";
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
		return $ret;
	}
	
	
	function import(  )
	{
		$URL  = $this->_getImportURL(); 
		$vals = $index = array();
		if (!$this->_parseIntoStruct($URL, $vals, $index))
		{
			return false;
		}
		$newsObj = & $this->_module->_getNews();
		$newsObj->deleteAll();
		
		for ($i=0, $s=sizeof($index['ID']); $i<$s; $i++)
		{
			$newsObj->idAttr(0);
			$newsObj->attr('title', $vals[$index['TITLE'][$i]]['value']);
			$newsObj->attr('announce', $vals[$index['ANNOUNCE'][$i]]['value']);
			$newsObj->attr('content', $vals[$index['CONTENT'][$i]]['value']);
			$newsObj->attr('published', $vals[$index['PUBLISHED'][$i]]['value']);
			$newsObj->attr('export_param', $vals[$index['EXPORT_PARAM'][$i]]['value']);
			$newsObj->save();
			$this->_searchForFiles($vals[$index['ANNOUNCE'][$i]]['value']);
			$this->_searchForFiles($vals[$index['CONTENT'][$i]]['value']);
		}
		return true;
	}
	
}

?>