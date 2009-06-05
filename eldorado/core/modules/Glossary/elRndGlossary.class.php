<?php

class elRndGlossary extends elModuleRenderer
{
	function rndGloss($entries, $ABCs, $curr=null)
	{
		$this->_setFile();
		$this->_te->assignBlockVars('GLOSS', array());
		if (!$curr)
		{
		  $a = current($ABCs);
		  $curr = $a[0]['letter'];
		}
		foreach ($ABCs as $ABC)
		{
			$this->_te->assignBlockVars('GLOSS.GLOSS_ABC', array());
			foreach ($ABC as $letter)
			{
				$block = $letter['letter'] == $curr
				  ? 'GLOSS.GLOSS_ABC.GLOSS_LETTER.GLOSS_LETTER_CURR'
				  : 'GLOSS.GLOSS_ABC.GLOSS_LETTER.GLOSS_LETTER_NORM';
				$this->_te->assignBlockVars($block, $letter, 2);
			}
		}
		foreach ($entries as $entry)
		{
			$entry = $entry->toArray();
			$this->_te->assignBlockVars('GLOSS.GLOSS_ENTRY', $entry);
			$block = $entry['url'] ? 'GLOSS.GLOSS_ENTRY.GLOSS_WORD_URL' : 'GLOSS.GLOSS_ENTRY.GLOSS_WORD';
			$this->_te->assignBlockVars($block, $entry, 2);
		}
	}
}
?>