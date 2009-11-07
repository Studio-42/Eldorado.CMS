<?php

class elModuleMailFormator extends elModule
{
	var $_fList    = array(); //form fields list
	var $_rcptList = array(); //recipients list
	var $_form     = null;
	var $_conf     = array(
			'formLabel'  => '',
			'selectRcpt' => 0,
			'rcptIDs'    => array(),
			'subject'    => '',
			'replyMsg'   => ''
			);
	var $_tpl = "%s: %s\n";
	var $_tplSt = "--- %s ---\n";
	var $_ec = null;  //emails collection object

	/**
	 * render form. If form is submitted - send data to email
	 *
	 * @return unknown
	 */
	function defaultMethod()
	{
		if (empty($this->_fList) )
		{
			return;
		}
		if ( empty($this->_rcptList) )
		{
			if ( EL_PERM_READ < $this->_aMode )
			{
				elThrow(E_USER_WARNING, 'There are no one recipients were defined');
			}
			return;
		}
		$this->_makeForm();
		$this->_initRenderer();
		if ( !$this->_form->isSubmitAndValid() )
		{
			return $this->_rnd->addToContent( $this->_form->toHtml() );
		}

		$data  = $this->_form->getValue(); 
		$msg   = '';
		$dir   = $this->_getTmpDir();
		$files = array();
		foreach ($this->_fList as $el)
		{
			if ( 'subtitle' == $el->type )
			{
				$msg .= sprintf($this->_tplSt, $el->value);
			}
			elseif ('date' == $el->type)
			{
				$msg .= sprintf($this->_tpl, $el->label, date(EL_DATE_FORMAT, $data[$el->ID]));
			}
			elseif ('file' == $el->type)
			{
				if ($data[$el->ID]['size'] > 0 && !$data[$el->ID]['error'] && move_uploaded_file($data[$el->ID]['tmp_name'], $dir.$data[$el->ID]['name']))
				{
					$files[] = array('name'=>$dir.$data[$el->ID]['name'], 'type'=>$data[$el->ID]['type']);	
				}
			}
			elseif (is_array($data[$el->ID]))
			{
				$msg .= sprintf($this->_tpl, $el->label, implode(', ', $data[$el->ID]));
			}
			elseif ('captcha' != $el->type)
			{
				$msg .= sprintf($this->_tpl, $el->label, $data[$el->ID]);
			}
		}
		$rcptList = $this->_conf('selectRcpt') ? array($data['rcpt']) : $this->_rcptList;
		
		if ( false == ($subj = $this->_conf('subject')))
		{
			$subj = m('Message from site');
		}
		if ( !$this->_send($rcptList, $subj, $msg, $files) )
		{
			return elThrow(E_USER_WARNING, 'Can not send email.');
		}
		if ( false == ($reply = $this->_conf('replyMsg')))
		{
			$reply = m('You message was recieved.');
		}
		elMsgBox::put($reply);
		elLocation(EL_URL);
	}

	function _getTmpDir()
	{
		if ( !is_dir('./tmp') )
		{
			return mkdir('./tmp') ? './tmp/' : './log/';
		}
		return is_writable('./tmp') ? './tmp/' : './log/';
	}

	function _send( $rcptList, $subj, $msg, $files )
	{
		$postman = & elSingleton::getObj('elPostman');
		$to = array();
		foreach ( $rcptList as $rcptID ) {
			$addr = $this->_ec->getEmailByID($rcptID);
			if ($addr) {
				$to[] = $addr;
			}
		}
		$postman->newMail($this->_ec->getDefault(), $to ? $to : $this->_ec->getDefault(), $subj, $msg);
		if (!empty($files))
		{
			foreach($files as $f)
			{
				$postman->attach($f['name'], $f['type']);
				@unlink($f['name']);
			}
		}
		return $postman->deliver();
	}

	/**
	 * Create form from elements from $this->_fList array
	 *
	 */
	function _makeForm()
	{
		$this->_form = &elSingleton::getObj('elForm');
		$this->_form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
		if ( false == ($label = $this->_conf('formLabel')))
		{
			$label = m('Send message');
		}
		$this->_form->setLabel( $label );

		if ( $this->_conf('selectRcpt') )
		{
			$this->_form->add( new elSelect('rcpt', m('Select recipient'), null, $this->_rcptList));
		}
		foreach ($this->_fList as $el)
		{
			$attrs = 'subtitle' == $el->type ? array('cellAttrs'=>'class="form-tb-sub"') : null;
			$this->_form->add( $el->toFormElement(), $attrs );
			if ('comment' != $el->type && 'subtitle' != $el->type && 'none' != $el->valid)
			{
				$this->_form->setElementRule($el->ID, $el->valid, true, null, $el->errorMsg);
			}
		}
	}

	/**
	 * Set form recipients from conf. If conf is empty, set all site's emails as recipients
	 *
	 */
	function _onInit()
	{
		$this->_ec = & elSingleton::getObj('elEmailsCollection');

		if ( !empty($this->_conf['rcptIDs']) && is_array($this->_conf['rcptIDs']) )
		{
			foreach ($this->_conf['rcptIDs'] as $ID)
			{
				if ( $this->_ec->isEmailExists($ID) )
				{
					$this->_rcptList[$ID] = $this->_ec->getLabel($ID);
				}
			}
		}
		
		if ( empty($this->_rcptList) )
		{
			$this->_rcptList = $this->_ec->getLabels();
		}
		$element      = & $this->_getElement();
		$this->_fList = $element->getCollection(null, 'fsort, fid');
		elLoadMessages('ModuleMailer');
	}

	function &_getElement()
	{
		$el = & elSingleton::getObj('elMFElement');
		$el->setTb('el_mail_form_'.$this->pageID);
		$el->setUniqAttr( (int)$this->_arg() );
		if (!$el->fetch())
		{
			$el->setUniqAttr(0);
		}
		return $el;
	}




}

?>