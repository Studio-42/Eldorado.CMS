<?php

class elFAQQuestion extends elMemberAttribute 
{
	var $ID       = 0;
	var $catID    = 0;
	var $question = '';
	var $answer   = '';
	var $status   = 0;
	var $email    = '';
	var $notified = 0;
	var $qsort    = 0;
	var $atime    = 0;
	var $_objName = 'Question';
	
	function makeForm( $params )
	{
		parent::makeForm();

		if ( !$params['admin'] )
		{
			$this->form->setLabel( m('Ask a question') );
			$this->form->add( new elText('email', m('E-mail for answer')) );
			$this->form->setElementRule('email', 'email', false);
		}
		else
		{
			$status = $GLOBALS['yn'];
			if ( !$this->notified && $this->email )
			{
				$status[2] = sprintf(m('Publish and send notify on email (%s)'), $this->email);
			}
			$this->form->add( new elSelect('status', m('Published'), $this->getAttr('status'), $status) );
		}
		
		$this->form->add( new elSelect('cat_id', m('Category'), $this->getAttr('cat_id'), $params['cList']) );
		$this->form->add( new elTextArea('question', m('Question'), $this->getAttr('question')) );
		$this->form->setRequired('question');
		
		if ($params['admin'])
		{
			$this->form->add( new elTextArea('answer', m('Answer'), $this->getAttr('answer')) );
			$this->form->setRequired('answer');
		}
		
	}
	
	function _attrsForSave()
	{
		$attrs = $this->getAttrs();
		$attrs['cat_id'] = (int) $attrs['cat_id'];
		if ( !$this->getUniqAttr() )
		{
			$attrs['atime'] = time();		
		}
		if (2 == $attrs['status'] && !$this->notified && $this->email)
		{
			if ($this->_sendNotify())
			{
				$attrs['notified'] = 1;
				elMsgBox::put(m('Notify about answer was successfully sent'));
			}
			else
			{
				$attrs['notified'] = 1;
				elThrow(E_USER_WARNING, m('Could not send notify'));
			}
			$attrs['status'] = 12;
		}
		return $attrs;
	}
	
	function _sendNotify()
	{
		$postman = & elSingleton::getObj('elPostman');
		$ec = & elSingleton::getObj('elEmailsCollection');
		$conf = & elSingleton::getObj('elXmlConf');
		$siteMane = $conf->get('SiteName', 'common');
		$subj = sprintf(m('Notify from site %s'), EL_BASE_URL);
		$msg = sprintf( m('Administrator on site %s (%s) aswered on question You asked. To read answer go to %s'), 
										$siteName, EL_BASE_URL, EL_URL);
		$postman->newMail($ec->getDefault(), $this->email, $subj, $msg);
		//return $postman->deliver();
		return true;
	}
	
	function _initMapping()
	{
		$map = array( 'id'       => 'ID', 
									'cat_id'   => 'catID',
									'question' => 'question', 
									'answer'   => 'answer', 
									'status'   => 'status',
									'email'    => 'email', 
									'atime'    => 'atime',
									'qsort'    => 'qsort',
									'notified' => 'notified'
									);
		return $map;
	}
}

?>