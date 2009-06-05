<?php

class elModuleAdminGlossary extends elModuleGlossary
{
	var $_mMapAdmin = array(
		'edit' => array('m' => 'editEntry', 'ico' => 'icoDocNew', 'l' => 'Create glossary entry', 'g'=>'Actions'),
		'rm'   => array('m' => 'deleteEntry')
	);
  var $_mMapConf = array();

	function deleteEntry()
	{
		$gloss = $this->_getGlossary($this->_args[0]);
		if ( !$gloss->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
              array($gloss->getObjName(), $gloss->ID),
              EL_URL);
    }
		$gloss->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $gloss->getObjName(), $gloss->word) );
		elLocation(EL_URL);
	}

	function editEntry()
	{
		$gloss = $this->_getGlossary($this->_arg(0));
		if (!$gloss->editAndSave())
		{
			$this->_initRenderer();
			$this->_rnd->addToContent($gloss->formToHtml());
		}
		else
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
	}

}

?>