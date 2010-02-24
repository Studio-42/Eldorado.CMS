<?php

class elRndFAQ extends elModuleRenderer
{

	function render($collection, $form)
	{
		$this->_setFile();
		$this->_te->assignVars('questForm', $form);
		$cNum = $qNum = 1;

		foreach ($collection as $cat)
		{
			$data         = $cat->toArray();
			$data['cNum'] = $cNum;
			$qNum         = 1;

			$this->_te->assignBlockVars('FAQ_CAT', $data);
			$this->_te->assignBlockVars('FAQ_CAT.CAT_ADMIN', array('cid' => $data['cid']), 1);
			$cssDisplay = EL_WM == EL_WM_PRNT ? 'block' : 'none';
			foreach ( $cat->quests as $q )
			{
				$data               = $q->toArray(); //elPrintR($data);
				$data['cNum']       = $cNum;
				$data['qNum']       = $qNum++;
				$data['question']   = nl2br($data['question']);
				$data['answer']     = nl2br($data['answer']);
				$data['cssDisplay'] = $cssDisplay;

				$this->_te->assignBlockVars('FAQ_CAT.FAQ_CAT_QUEST_LIST.FAQ_QUESTION', $data, 2);
				if ( $this->_admin )
				{
					$this->_te->assignBlockVars('FAQ_CAT.FAQ_CAT_QUEST_LIST.FAQ_QUESTION.ADMIN', array('id' => $data['id']), 3);
					if ( !$data['status'] )
					{
						$this->_te->assignBlockVars('FAQ_CAT.FAQ_CAT_QUEST_LIST.FAQ_QUESTION.FQ_NOTPUBL', null, 3);
					}
					if ( empty($data['answer']))
					{
						$this->_te->assignBlockVars('FAQ_CAT.FAQ_CAT_QUEST_LIST.FAQ_QUESTION.FQ_NOANSW', null, 3);
					}
				}
			}
			$cNum++;
		}
	}

}

?>