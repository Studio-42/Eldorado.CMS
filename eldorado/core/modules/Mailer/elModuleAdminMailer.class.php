<?php

class elModuleAdminMailer extends elModuleMailer
{

  // ============================================================ //
  //  ================  PRIVATE METHODS  ======================   //
  // ============================================================ //


  function _makeConfForm()
    {
      $form = & parent::_makeConfForm();
      $form->add( new elCdata( null, m('Leave input fields empty to use default values') ) );

      $form->add( new elText('formLabel',       m('Title text'),             $this->_get('formLabel')) );
      $form->add( new elSelect('selectRcpt',    m('Allow select recipient'), $this->_conf('selectRcpt'), $GLOBALS['yn'] ) );
      $form->add( new elSelect('spamProtect',   m('Spam protection'),        $this->_conf('spamProtect'), $GLOBALS['yn'] ) );
      $form->add( new elSelect('subjReq',       m('Require subject'),        $this->_conf('subjReq'),    $GLOBALS['yn'] ) );
      $form->add( new elText('subjLabel',       m('Subject field label'),    $this->_get('subjLabel') ) );
      $form->add( new elText('subjDefault',     m('Default subject'),        $this->_get('subjDefault')) );
      $form->add( new elText('msgLabel',        m('Message field label'),    $this->_get('msgLabel')) );
      $form->add( new elTextArea('commentsTop', m('Top comments'),           $this->_get('commentsTop'), array('rows'=>4)) );
      $form->add( new elTextArea('commentsBot', m('Bottom comments'),        $this->_get('commentsBot'), array('rows'=>4)) );
      $form->add( new elTextArea('confirm',     m('Confirmation text'),      $this->_get('confirm'),     array('rows'=>4)) );

      return $form;
    }

  function _get( $k )
    {
      return $this->_conf($k) == m($this->_cDef[$k]) ? '' : $this->_conf($k);
    }

} 

?>