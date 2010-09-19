<?php

class elModuleMailer extends elModule
{
  var $emailsCollection = null;
  var $form             = null;
  var $_defMethodNoArgs = true;
  var $_prnt = false;
  var $_cDef = array();
  var $_conf = array(
                     'formLabel'    => 'Send message',
                     'subjLabel'    => 'Subject',
                     'msgLabel'     => 'Message text',
                     'subjReq'      => 1,
                     'subjDefault'  => 'Message from site',
                     'commentsTop'  => '',
                     'commentsBot'  => '',
                     'selectRcpt'   => 1,
                     'confirm'      => 'You message was recieved.',
                     'spamProtect'  => 1
                     );

  // ************************************************************ //
  //  ****************  PUBLIC METHODS  ***********************   //
  // ************************************************************ //

  function defaultMethod()
    {
      $this->_makeForm();
      $this->_initRenderer();
      if ( $this->form->isSubmitAndValid() )
        {
          if ( $this->_send( $this->form->getValue() ) )
            {
              $reply = sprintf( m('Dear %s.'), $this->form->getElementValue('name')).' '.m($this->_conf('confirm'));
              elMsgBox::put( $reply );
              elLocation( EL_URL );
            }
          else
            {
              elThrow(E_USER_WARNING, m('Can not send email.') );
            }
        }
      $this->_rnd->addToContent( $this->form->toHtml() );
    }

	function ifModifiedSince()
	{
		// this page becomes old in 7 days
		return array(true, (time() - (86400 * 7)));
	}

  // ============================================================ //
  //  ================  PRIVATE METHODS  ======================   //
  // ============================================================ //


  function _send( $vals )
    {
      if ( !$this->_conf('selectRcpt') || empty($vals['rcpt']) || 
           false == ($rcpt = $this->emailsCollection->getEmailByID((int)$vals['rcpt'])) )
        {
          $rcpt = $this->emailsCollection->getDefault();
        }

      $from = $this->emailsCollection->formatEmail(addslashes($vals['name']), $vals['addr']);
      $subj = !empty($vals['subj']) ? $vals['subj'] : $this->_conf('subjDefault');

      $postman = & elSingleton::getObj('elPostman');
      $postman->newMail( $from, $rcpt,  $subj, $vals['msg'] );
      if (!$postman->deliver())
      {
      	elDebug($postman->error);
      	return false;
      }
      return true;
      return $postman->deliver();
    }

  function _makeForm()
    {
      $this->form = & elSingleton::getObj('elForm');
      $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
      $this->form->setLabel( $this->_conf('formLabel') );

      if ( false != ($comments = $this->_conf('commentsTop')) )
      {
        $this->form->add( new elCdata( 'c1', $comments ) );
      }
      
      if ( $this->_conf('selectRcpt') )
      {
        $this->form->add( new elSelect('rcpt', m('Recipient'), null, $this->emailsCollection->getLabels()) );
        $this->form->setRequired('subj');
      }

      $this->form->add( new elText('name', m('Your name') ) );
      $this->form->add( new elText('addr', m('Your email') ) );
      if ( $this->_conf('subjReq') ) 
      {
        $this->form->add( new elText('subj', $this->_conf('subjLabel'), $this->_conf('subjDefault' ) ) );
      }
      $this->form->add( new elTextArea('msg', $this->_conf('msgLabel') ) );

      if ( false != ($comments = $this->_conf('commentsBot')) )
      {
        $this->form->add( new elCdata( 'c2', $comments ) );
      }

      if ( $this->_conf('spamProtect') )
      {
        $this->form->add( new elCaptcha('capt_'.$this->pageID, m('Enter code from picture') ) );  
      }
      

      $this->form->setRequired('name');
      $this->form->setRequired('msg');
      $this->form->setElementRule('addr', 'email', true);

    }

  function _loadConf()
    {
      $this->_cDef = $this->_conf; 
      parent::_loadConf();
    }

  function _onInit()
    {
      $this->emailsCollection = & elSingleton::getObj('elEmailsCollection'); 
      foreach ( $this->_cDef as $k=>$v )
        {
          if ( !is_numeric($v) && ( empty($this->_conf[$k]) || $v == $this->_conf[$k] ) )
            { 
              $this->_conf[$k] = m($v);
            }
        }
    }

}
?>