<?php
/**
 * Каталог вакансий
 *
 */
include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

class elModuleVacancyCatalog extends elCatalogModule
{
  var $tbc          = 'el_vaccat_%d_cat';
  var $tbi          = 'el_vaccat_%d_item';
  var $tbi2c        = 'el_vaccat_%d_i2c';
  var $_itemClass   = 'elVacancy';
  var $_formElement = null;
  var $_fList       = array();
  var $_mMap        = array('item' => array('m'=>'viewItem'), 'send' => array('m' => 'sendVacancy') );
  var $_conf        = array(
    'deep'              => 0,
    'catsCols'          => 1,
    'itemsCols'         => 2,
    'itemsSortID'       => 1,
    'itemsPerPage'      => 10,
    'displayCatDescrip' => 1,
    'allowAttach'       => 1,
    'rcptIDs'           => '',
    'useDefaultForm'    => 1,
    'replyMsg'          => ''
    );

  /**
   * Рисует форму для резюме, проверяет ее и отсылает на email
   *
   * @return void
   */
  function sendVacancy()
  {
    elLoadMessages('ModuleMailer');
    $this->_item = $this->_getItem();
    $this->_initRenderer();
    $default = true;

    if ( !$this->_item->ID )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
        array($this->_item->getObjName(), $this->_arg(1)),
        EL_URL.$this->_cat->ID);
    }

    if (!$this->_conf('useDefaultForm') && $this->_loadFormElements())
    {
      $this->_makeNoDefaultForm($this->_item->getAttr('name'));
      $default = false;
    }
    else
    {
      $this->_makeDefaultForm($this->_item->getAttr('name'));
    }

    if (!$this->_form->isSubmitAndValid())
    {

      return $this->_rnd->addToContent( $this->_form->toHtml());
    }

    $subj                 = sprintf(m('Resume on vacancy "%s"'), $this->_item->getAttr('name'));
    list($msg, $reply)    = $this->_getMailMsgAndReply( $this->_form->getValue(), $default );
    list($sender, $rcpts) = $this->_getMailAddresses($this->_form->getValue());
    $res = false;
    foreach ( $rcpts as $rcpt )
    {
      if ( $this->_send($sender, $rcpt, $subj, $msg) )
      {
        $res = true;
      }
    }

    if ( !$res ) //!$this->_send($sender, $rcpts, $subj, $msg) )
    {
      elThrow(E_USER_WARNING, 'Could not send e-mail');
      return $this->_rnd->addToContent( $this->_form->toHtml() );
    }
    elMsgBox::put( $reply );
    elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID);
  }

  /*********************************************/
  //             PRIVATE                       //
  /*********************************************/

  /**
   * Отсылает email с данными формы для резюме
   *
   * @param string $sender email отправителя или дефолтный email сайта
   * @param array  $rcpts  список получателй
   * @param string $subj   тема
   * @param string $msg    резюме
   * @return bool
   */
  function _send($sender, $rcpts, $subj, $msg)
  {
    $postman = & elSingleton::getObj('elPostman');
    $postman->newMail($sender, $rcpts, $subj, $msg);
    if (!empty($this->_attach))
    {
      $postman->attach($this->_attach);
      unlink($this->_attach);
      $this->_attach = '';
    }
    return $postman->deliver();
  }

  /**
   * Создает форму для резюме по умолчанию
   *
   * @param string $vac Название вакансии
   */
  function _makeDefaultForm($vac)
  {
    $this->_form = & elSingleton::getObj('elForm');
    $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $this->_form->setLabel( sprintf(m('Send resume on vacancy "%s"'), $vac) );
    $this->_form->add( new elText('name',       m('Full name')) );
    $this->_form->add( new elText('email',      m('E-mail')) );
    $this->_form->add( new elText('phone',      m('Phone')) );
    $this->_form->add( new elTextArea('resume', m('Resume text')) );


    $this->_form->setRequired('name');
    $this->_form->setElementRule('email', 'email', false);
    $this->_form->setElementRule('phone', 'phone');

    if ($this->_conf('allowAttach'))
    {
      $this->_form->add( new elCData('c1',       m('Use this form bellow if You want to send resume as attached file:')) );
      $file = & new elFileInput('attach', m('Attach file'));
      $file->setMaxSize( 1 );
      $file->setFileExt( array('txt', 'doc', 'rtf', 'rtfd', 'pdf') );
      $this->_form->add( $file );
    }
    else
    {
      $this->_form->setRequired('resume');
    }
        $this->_form->add( new elCaptcha('capt_'.$this->pageID, m('Enter code from picture')) );

    if ($this->_form->isSubmitAndValid() && $this->_conf('allowAttach') )
    {
      if ( !$this->_form->getElementValue('resume') && !$file->isUploaded())
      {
        $this->_form->pushError('resume', m('You should fill "resume text" field or send resume as attached file'));
      }
      elseif ($file->isUploaded())
      {
        if ( false != ($up = $file->moveUploaded(null, EL_DIR_STORAGE)) )
        {
          $this->_attach = $up;
        }
        else
        {
          $this->_form->pushError('attach', m('Can not upload file'));
        }
      }
    }

  }

  /**
   * Создает форму для резюме из данных конструктора форм
   *
   * @param string $vac название вакансии
   */
  function _makeNoDefaultForm( $vac )
  {
    $this->_form = &elSingleton::getObj('elForm');
    $this->_form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
    $this->_form->setLabel( sprintf(m('Send resume on vacancy "%s"'), $vac) );
    foreach ($this->_fList as $el)
    {
      $attrs = 'subtitle' == $el->type ? array('cellAttrs'=>'class="formSubheader"') : null;
      $this->_form->add( $el->toFormElement(), $attrs );
      if ('comment' != $el->type && 'subtitle' != $el->type && 'none' != $el->valid)
      {
        $this->_form->setElementRule($el->ID, $el->valid, true, null, $el->errorMsg);
      }
    }
  }

  /**
   * Формирует  из данных формы резюме текст сообщения и текст подтверждения
   *
   * @param array $data    данные формы резюме
   * @param bool  $default обрабатываем форму по умолчанию?
   * @return array
   */
  function _getMailMsgAndReply($data, $default=true)
  {
    $msg   = "\n";
    $reply = $this->_conf('replyMsg');

    if ($default)
    {
      $msg .= m('Competitor').': '.$data['name']."\n";
      $msg .= m('E-mail').': '.(!empty($data['email']) ? $data['email'] : m('Not set'))."\n";
      $msg .= m('Phone').': '.$data['phone']."\n";
      $msg .= m('Resume text').': '.(!empty($data['resume']) ? $data['resume'] : m('In attached file'))."\n";
      if ( empty($reply) )
      {
        $reply = sprintf(m('Dear %s. Your message was delivered!'), $data['name']);
      }
    }
    else
    {
      foreach ($data as $k=>$v)
      {
        $msg .= $this->_fList[$k]->label.': '.$v."\n";
      }
      if ( empty($reply) )
      {
        $reply = sprintf(m('Dear %s. Your message was delivered!'), m('Visitor'));
      }
    }
    return array($msg, $reply);
  }

  /**
   * Возвращает адреса оправителя и получателей
   *
   * @param array $data данные формы резюме
   * @return array
   */
  function _getMailAddresses($data)
  {
    $ec = & elSingleton::getObj('elEmailsCollection');
    if (!empty($data['email']))
    {
      $sender = $data['email'];
    }
    elseif (!empty($data['Email']))
    {
      $sender = $data['Email'];
    }
    elseif (!empty($data['E-mail']))
    {
      $sender = $data['E-mail'];
    }
    else
    {
      $sender = $ec->getDefault();
    }

    $rcpts   = array();
    $rcptIDs = $this->_conf('rcptIDs');
    if (empty($rcptIDs))
    {
      $rcpts[] = $ec->getDefault();
    }
    else
    {
      foreach ($rcptIDs as $ID)
      {
        $rcpts[] = $ec->getEmailByID($ID);
      }
    }
    return array($sender, $rcpts);
  }

  /**
   * Создает одиночный элемент формы
   * Используется в конструкторе форм
   *
   * @return object
   */
  function &_getFormElement()
  {
    if (empty($this->_formElement))
    {
      include_once(EL_DIR_CORE.'modules/MailFormator/elMFElement.class.php');
      elLoadMessages('ModuleMailFormator');
      elAddJs('MailFormator.lib.js', EL_JS_CSS_FILE);
      $this->_formElement = & elSingleton::getObj('elMFElement');
      $this->_formElement->setTb('el_vaccat_'.$this->pageID.'_form');
      $this->_formElement->setUniqAttr( (int)$this->_arg(1) );
      if ( !$this->_formElement->fetch())
      {
        $this->_formElement->setUniqAttr(0);
      }
    }
    return $this->_formElement;
  }

  /**
   * Загружает объекты - элементы формы, созданой в конструкторе
   * Если объектов нет - возвращает false
   *
   * @return bool
   */
  function _loadFormElements()
  {
    $element      = & $this->_getFormElement();
    $this->_fList = $element->getCollection(null, 'fsort, fid');
    return !empty($this->_fList);
  }
}

?>