<?php
/**
 *@pakage eldorado
 * @subpakage modules
 * @ver 3.4.0
 * Class for email's used on site control
 * TODO - add options for sending mail method (PHP or SMTP) - here and in elPostman
 */

class elSubModuleEmailsControl extends elModule
{

  var $_mMapAdmin = array('edit' => array('m'=>'editEmail', 'ico'=>'icoMailNew', 'l'=>'Create email', 'g' => 'Actions'),
                          'del'  => array('m'=>'rmEmail') );
  var $_prnt      = false;
  var $_confID    = 'mail';
  var $_conf      = array('transport' => 'PHP',
                          'smtpAuth'  => '',
                          'smtpHost'  => '',
                          'smtpPort'  => '25',
                          'smtpUser'  => '',
                          'smtpPass'  => '',
                          'logSend'   => 0,
                          'logFailed' => 0
                          );
// *********************  PUBLIC METHODS  **************************** //

  /**
   * Display emails list
   */
  function defaultMethod()
  {
    $ec = & elSingleton::getObj('elEmailsCollection');
    $eList = array();
    foreach ( $ec->collection as $ID=>$e )
    {
      $eList[$ID] = array('id'=>$ID, 'label'=>$e['label'], 'val'=>$e['email']);
      if ( $ID == $ec->defaultID )
      {
        $eList[$ID]['label'] .= ' ['.m('default').']';
      }
    }
    $this->_initRenderer();
    $this->_rnd->render( $eList, EL_READ < $this->_aMode ? 'emails' : null, 'ROW' );
  }

  /**
   * Create or edit email address
   */
  function editEmail()
  {
    $email = &elSingleton::getObj('elEmailAddress');
    $email->setUniqAttr((int)$this->_arg());
    $email->fetch();
    if ( !$email->editAndSave() )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $email->formToHtml());
    }
    else
    {
      elMsgBox::put( m('Data saved') );
      elLocation( EL_URL.$this->_smPath);
    }
  }

  /**
   * Delete address if exists
   */
  function rmEmail()
  {
    $email = &elSingleton::getObj('elEmailAddress');
    $email->setUniqAttr((int)$this->_arg());
    if ( !$email->fetch() )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
        array(m($email->getObjName(), $email->ID)), EL_URL.$this->_smPath);
    }
    $email->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $email->getObjName(), $email->email) );
    elLocation( EL_URL.$this->_smPath);;
  }

  function &_makeConfForm()
  {
    $form = parent::_makeConfForm();
    $form->addJsSrc( 'checkMailForm();', EL_JS_SRC_ONLOAD );

    $form->add( new elSelect('logSend',   m('Write sended emails to log file'),        $this->_conf('logSend'), $GLOBALS['yn'] ) );
    $form->add( new elSelect('logFailed', m('Write failed sended emails to log file'), $this->_conf('logFailed'), $GLOBALS['yn'] ) );

    $tr = array('PHP'=>m('Using PHP buildin mail function'), 'SMTP'=>m('Directly via SMTP server'));
    $attr = array('onChange'=>'checkMailForm();');
    $form->add( new elSelect('transport', m('Send mail from site'), $this->_conf('transport'),  $tr, $attr) );

    $form->add( new elText('smtpHost', m('SMTP server'), $this->_conf('smtpHost') ) );
    $form->add( new elText('smtpPort', m('SMTP port'),   $this->_conf('smtpPort') ) );
    $form->add( new elSelect('smtpAuth', m('SMTP require authentication'), $this->_conf('smtpAuth'), $GLOBALS['yn'], $attr ));
    $form->add( new elText('smtpUser', m('SMTP user login'),    $this->_conf('smtpUser') ) );
    $form->add( new elText('smtpPass', m('SMTP user password'), $this->_conf('smtpPass') ) );
    return $form;
  }

}

?>