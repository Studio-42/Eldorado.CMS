<?php

class elModuleAdminPoll extends elModulePoll
{
	var $_mMapAdmin = array(
		'edit'   => array('m' => 'editPoll', 'ico' => 'icoPollNew', 'l' => 'Create poll', 'g'=>'Actions'),
		'rm'     => array('m' => 'deletePoll'),
		'close'  => array('m' => 'closePoll')
	);
	
	/**
	 * Редактировать голосование
	 */
	function editPoll()
	{
		$poll = &$this->_factory->create((int)$this->_arg(0));
		if ( $poll->isComplete )
		{
			elThrow(E_USER_WARNING, 'Comleted vote could not be modified', null, EL_URL);
		}
		if ($poll->editAndSave())
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($poll->formToHtml());
	}

	/**
	 * Завершить голосование
	 */
	function closePoll()
	{
		$poll = &$this->_factory->create((int)$this->_arg());
		if ( !$poll->ID )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array(m('Poll'), $this->_arg()), EL_URL);
		}
		$poll->complete();
		elMsgBox::put(m('Data saved'));
		elLocation(EL_URL);
	}
	
	/**
	 * Удалить голосование
	 */
	function deletePoll()
	{
		$poll = &$this->_factory->create((int)$this->_arg());
		if ( !$poll->ID )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array(m('Poll'), $this->_arg()), EL_URL);
		}
		$poll->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $poll->getObjName(), $poll->name) );
		elLocation(EL_URL);
	}
	
	
	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();
		$form->add( new elSelect('maxPollVarians', m('Poll maximum number variants'), (int)$this->_conf('maxPollVarians'), range(2, 30), null, false, false) );
		$vars = array(EL_POLL_NO_DATE    => m('No'),
					  EL_POLL_BEGIN_DATE => m('Only begin date'),
					  EL_POLL_END_DATE   => m('Only stop date'),
					  EL_POLL_ALL_DATE   => m('Start date and stop date'));
		$form->add( new elSelect('displayDates', m('Display polls dates'), (int)$this->_conf('displayDates'), $vars) ); 
		return $form;
	}
}

?>