<?php

elAddCss('moduleUpdateClient.css');

class elModuleAdminUpdateServer extends elModuleUpdateServer
{
  var $_mMapAdmin = array(
    'prolong'  => array('m' => 'prolongLicense', 'l'=>'Prolong license'),
    'edit'     => array('m' => 'editLicense',     'l'=>'New license',     'g'=>'Actions'),
    'log'      => array('m' => 'viewLog',        'l'=>'View log',        'g'=>'Actions'),
    'clearlog' => array('m' => 'clearLog',       'l'=>'Clear log',        'g'=>'Actions'),
    'rm'       => array('m' => 'rmLicense'),
    );


  /**
   * Список лицензий
   *
   */
  function defaultMethod()
  {
    $this->_initRenderer();
    $this->_rnd->rndLicenseList( $this->_lc->getCollectionToArray() );
  }


  function viewLog()
  {
    $logRec = & elSingleton::getObj('elUpdServLogRecord');
    $this->_initRenderer();
    $this->_rnd->rndLog( $logRec->getCollection() );
  }

  function clearLog()
  {
    $db = &elSingleton::getObj('elDb');
    $db->query('TRUNCATE el_userv_log');
    elMsgBox::put( m('Log was cleared') );
    elLocation(EL_URL);
  }

  /**
   * Продлевает лицензию на 1 год
   *
   */
  function prolongLicense()
  {
    $this->_lc->cleanAttrs();
    $this->_lc->setUniqAttr( (int)$this->_arg() );
    if ( !$this->_lc->fetch() )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
        array($this->_lc->getObjName(), $this->_arg()),	EL_URL);
    }
    $this->_lc->prolong();
    elMsgBox::put(m('License for site "%s" was prolonged for 1 year'));
    elLocation(EL_URL);
  }


  /**
   * Создание/редактирование лицензии
   *
   */
  function editLicense()
  {
    $this->_lc->cleanAttrs();
    $this->_lc->setUniqAttr( (int)$this->_arg() );
    $this->_lc->fetch();
    if (!$this->_lc->editAndSave())
    {
      $this->_initRenderer();
      $this->_rnd->addToContent($this->_lc->formToHtml());
      return;
    }
    elMsgBox::put(m('Data saved'));
    elLocation(EL_URL);
  }

  /**
   * Удаление лицензии
   *
   */
  function rmLicense()
  {
    $this->_lc->cleanAttrs();
    $this->_lc->setUniqAttr( (int)$this->_arg() );
    if ( !$this->_lc->fetch() )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
        array($this->_lc->getObjName(), $this->_arg()),	EL_URL);
    }
    $this->_lc->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $this->_lc->getObjName(), $this->_lc->ID) );
    elLocation(EL_URL);
  }




  function &_makeConfForm()
  {
    $form = & parent::_makeConfForm();
    $form->add( new elText('sourceDir', m('Source directory'), $this->_conf('sourceDir')));
    $form->add( new elSelect('debug', m('Write debug into log'), $this->_conf('debug'), $GLOBALS['yn']) );
    return $form;
  }

}

?>