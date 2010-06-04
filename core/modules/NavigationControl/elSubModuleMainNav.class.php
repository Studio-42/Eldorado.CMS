<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elNavPage.class.php';

class elSubModuleMainNav extends elModule
{
  var $page  = null;

  var $_prnt = false;

  var $_mMapAdmin = array(
         'edit'       => array('m'=>'editPage', 'ico'=>'icoPageNew', 'l'=>'New page', 'g'=>'Actions'),
         'del'        => array('m'=>'rmPage'),
         'icons'      => array('m'=>'managePageIcons'),
         'up'         => array('m'=>'moveUp'),
         'down'       => array('m'=>'moveDown'),
         'page_layout'=> array('m'=>'pageLayout', 'l'=>'How to display navigation paths on page')
         );

  var $_navTypes = array( EL_NAV_TYPE_MAIN => 'One main navigation menu',
                          EL_NAV_TYPE_COMBI => '"Combi" top-level menu + submenu for current page (default)',
                          EL_NAV_TYPE_JS    => 'One main navigation menu with javascript (Expanding menu)'
      );

  var $_pathsOpts = array('navPathInSTitle'  => 'Path to current page in window title',
                          'pagePathInSTitle' => 'Path inside current page in window title',
                          'navPathInPTitle'  => 'Path to current page in page title',
                          'pagePathInPTitle' => 'Path inside current page in page title');

  var $_pathsVars = array(-1                     => 'Use common site options',
                          EL_NAV_PATH_FULL       => 'Full path',
                          EL_NAV_PATH_FIRST_PAGE => 'Only first element',
                          EL_NAV_PATH_LAST_PAGE  => 'Only last element',
                          EL_NAV_PATH_NO         => 'Do not display' );

  var $_conf = array(
                      'navType'          => EL_NAV_TYPE_COMBI,
                      'mainMenuPos'      => EL_POS_TOP,
                      'subMenuPos'       => EL_POS_LEFT,
                      'mainMenuUseIcons' => 0,
                      'subMenuUseIcons'  => 0,
                      'subMenuDisplParent' => 0,
                      'navPathInSTitle'  => EL_NAV_PATH_LAST_PAGE,
                      'pagePathInSTitle' => EL_NAV_PATH_LAST_PAGE,
                      'navPathInPTitle'  => EL_NAV_PATH_LAST_PAGE,
                      'pagePathInPTitle' => EL_NAV_PATH_LAST_PAGE
                    );
  var $_confID = 'layout';

  var $_reqModules = array('SiteControl', 'NavigationControl');

  function defaultMethod()
  {
    $page = & new elNavPage();
    $menu = $page->getCollection(null, '_left'); 
    array_shift($menu);
    $conf = &elSingleton::getObj('elXmlConf');
    $this->_initRenderer();
	$db = &elSingleton::getObj('elDb');
	$sql = 'SELECT ch.id AS chID, p.id AS pID FROM el_menu AS ch, el_menu AS p '
			.'WHERE ch._left BETWEEN p._left AND p._right AND ch.level=p.level+1 ORDER BY ch._left';
	$parentsID = $db->queryToArray($sql, 'chID', 'pID'); 
    $this->_rnd->render( $menu, $this->_modulesList(), $parentsID );
  }

  /**
  * Move page up
  */
  function moveUp()
  {
    $page = & elSingleton::getObj('elNavPage');
    $ID = (int)$this->_arg(0);
    $page->setUniqAttr( $ID );
    if ( !$page->fetch() )
    {
      elThrow(E_USER_NOTICE, 'Object "%s" ID="%d" does not exists',
      array(m('Page'), $ID), EL_URL);
    }
    $tree = & elSingleton::getObj('elDbNestedSets', 'el_menu');
    if ( !($nID = $tree->getNeighbourID( $ID )) )
    {
      elThrow(E_USER_NOTICE, 'Can not move object "%s" "%s" up', array(m('Page'), $page->name), EL_URL );
    }
    $tree->exchange($ID, $nID);
    elMsgBox::put( m('Data saved') );
    elLocation(EL_URL);
  }

  /**
  * Move page down
  */
  function moveDown()
  {
    $page = & elSingleton::getObj('elNavPage');
    $ID = (int)$this->_arg(0);
    $page->setUniqAttr( $ID );
    if ( !$page->fetch() )
    {
      elThrow(E_USER_NOTICE, 'Object "%s" ID="%d" does not exists', array(m('Page'), $ID), EL_URL);
    }
    $tree = & elSingleton::getObj('elDbNestedSets', 'el_menu');
    if ( !($nID = $tree->getNeighbourID( $ID, false )) )
    {
      elThrow(E_USER_NOTICE, 'Can not move object "%s" "%s" down', array(m('Page'), $page->name), EL_URL );
    }
    $tree->exchange($ID, $nID);
    elMsgBox::put( m('Data saved') );
    elLocation(EL_URL);
  }

  /**
  * Create new page or edit existed page
  */
  function editPage()
  {
    $page = & elSingleton::getObj('elNavPage');
    $page->setUniqAttr((int)$this->_arg(0));
    $page->fetch();

    if ( !$page->editAndSave() )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent($page->formToHtml());
    }
    else
    {
    	$ats =  & elSingleton::getObj('elATS');
    	$ats->onPageChange($page->ID);
      elMsgBox::put(m('Data saved'));
      elLocation(EL_URL);
    }
  }


  /**
  * remove existed page
  */
  function rmPage()
  {
    $page = & elSingleton::getObj('elNavPage');
    $page->setUniqAttr((int)$this->_arg(0));
    if ( !$page->fetch() )
    {
      elThrow(E_USER_WARNING, 'Page with ID="%d" does not exists', (int)$this->_arg(0), EL_URL);
    }

    if ( in_array($page->module, $this->_reqModules) )
    {
      elThrow(E_USER_WARNING, 'Page "%s" requires for normal site functionality. Delete denied', $page->name, EL_URL);
    }

    if ( $page->delete() )
    {
    	$ats =  & elSingleton::getObj('elATS');
    	$ats->onPageDelete($page->ID);
      elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $page->getObjName(), $page->name) );
      elLocation(EL_URL);
    }
  }

  function pageLayout()
  {
    $page = & elSingleton::getObj('elNavPage');
    $page->setUniqAttr((int)$this->_arg(0));
    if ( !$page->fetch() )
    {
      elThrow(E_USER_WARNING, 'Page with ID="%d" does not exists', (int)$this->_arg(0), EL_URL);
    }
    $conf = &elSingleton::getObj('elXmlConf');
    $form = &elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $form->setLabel( sprintf(m('How to display navigation paths on page "%s"'), $page->name) );
    $form->add( new elCData('c_1', m('This options override common site options for this page only.')),  array('cellAttrs'=>'class="form_comments"') );

    $options = array_map('m', $this->_pathsOpts);
    $vars    = array_map('m', $this->_pathsVars);

    foreach ( $options as $opt=>$l )
    {
      $val = null !== $conf->get($opt, $page->ID) ? (int)$conf->get($opt, $page->ID) : -1;
      $form->add( new elSelect($opt, $l, $val,  $vars ) );
    }

    if ( $form->isSubmitAndValid() )
    {
      $data = $form->getValue();
      unset( $vars[-1] );
      foreach ( $options as $opt=>$l )
        {
          if ( !isset($data[$opt]) || !isset($vars[$data[$opt]]) )
          {
            $conf->drop($opt, $page->ID);
          }
          else
          {
            $conf->set($opt, (int)$data[$opt], $page->ID);
          }
        }
      $conf->save();
      elMsgBox::put( m('Data saved') );
      elLocation(EL_URL);
    }
    $this->_initRenderer();
    $this->_rnd->addToContent( $form->toHtml() );
  }

  /**
  * set icon for page
  */
  function managePageIcons()
  {
    $page = & elSingleton::getObj('elNavPage');
    $ID = (int)$this->_arg(0);
    $page->setUniqAttr( $ID );
    if ( !$page->fetch() )
    {
      elThrow(E_USER_NOTICE, 'Object "%s" ID="%d" does not exists',
      array(m('Page'), $ID), EL_URL);
    }

    $page->manageIcons();
    $this->_initRenderer();
    $this->_rnd->addToContent( $page->formToHtml());
  }

  function &_makeConfForm()
  {
    $form = parent::_makeConfForm();
    $form->setLabel( m('Site navigation layout') );

    unset($this->_pathsVars[-1]);
    $this->_navTypes  = array_map('m', $this->_navTypes);
    $this->_pathsOpts = array_map('m', $this->_pathsOpts);
    $this->_pathsVars = array_map('m', $this->_pathsVars);

    $headAttrs = array('cellAttrs'=>' class="form-tb-sub"') ;
    $form->add( new elCData('h_1', m('Site navigation type')), $headAttrs );
    $type = (int)$this->_conf('navType');

	$js = "var r = $('#subMenuPos').parents('tr').eq(0); if (this.value == 1) { r.hide().next('tr').hide().next('tr').hide(); }	else if (this.value == 2) { r.show().next('tr').show().next('tr').show(); }	else { r.show().next('tr').hide().next('tr').hide(); }";
	$form->add(new elSelect('navType', m('Site navigation type'), $type, $this->_navTypes, array('onchange'=>$js)));

    $form->add( new elSelect('mainMenuPos', m('Main menu position on page'), $this->_conf('mainMenuPos'),  $GLOBALS['posLRT']) );

    $form->add( new elSelect('mainMenuUseIcons', m('Display pages icons in main navigation menu'), (int)$this->_conf('mainMenuUseIcons'),  $GLOBALS['yn']) );

    $form->add( new elSelect('subMenuPos', m('Submenu position on page'), $this->_conf('subMenuPos'),  $GLOBALS['posLRT'], array('onChange'=>'checkSubMenuParam(2, this.value)')) );
    $form->add( new elSelect('subMenuUseIcons', m('Display pages icons in submenu navigation menu'),  (int)$this->_conf('subMenuUseIcons'),  $GLOBALS['yn']) );
    $form->add( new elSelect('subMenuDisplParent', m('Display parent page name before submenu navigation menu'),  (int)$this->_conf('subMenuDisplParent'),  $GLOBALS['yn']) );

    $form->add( new elCData('h_2', m('How to display navigation paths')), $headAttrs );
    $form->add( new elCData('c_1', m('There are common options for whole site. And any of them can be overriden by curent page option.')) );
    foreach ( $this->_pathsOpts as $opt=>$l )
    {
      $form->add( new elSelect($opt, $l, (int)$this->_conf($opt),  $this->_pathsVars ) );
    }

    elAddJs("$('#navType').trigger('change');", EL_JS_SRC_ONREADY);
    return  $form;
  }


  function _modulesList()
  {
    $db = & elSingleton::getObj('elDb');
    return $db->queryToArray('SELECT module, IF(""<>descrip, descrip, module) AS name FROM el_module ORDER BY name', 'module', 'name');
  }


}

?>