<?php

/*
* @package eldoradoCore
* Additional menus management
*/

class elSubModuleAddNav extends elModule 
{
  var $_mMap  = array(
                      'sel'  => array('m' => 'selectPages'),
                      'ico'  => array('m' => 'manageIcon'),
                      'edit' => array('m' => 'editAMenu', 'l'=>'New additional side menu', 'g'=>'Actions', 'ico'=>'icoMenu'),
                      'rm'   => array('m' => 'rmAMenu') );                              
                      
  var $_prnt  = false;
  
  var $_conf  = array( 'addMenuTop'=>EL_ADD_MENU_NO, 'addMenuBottom'=>EL_ADD_MENU_NO );
  
  var $_confID = 'layout';
  
  var $_aMenus = array();
                        
  var $_stats = array( //EL_ADD_MENU_NO   => 'Not use',
                       EL_ADD_MENU_TEXT => 'Text only',
                       EL_ADD_MENU_ICO  => 'Icons only',
                       EL_ADD_MENU_TI   => 'Text and icons'
                     );
            
  function defaultMethod()
  {
    $this->_initRenderer();
    $this->_rnd->rndMenusAdd( $this->_aMenus, 
                              $this->_stats[$this->_conf('addMenuTop')], 
                              $this->_stats[$this->_conf('addMenuBottom')] );    
  }   
 
  function editAMenu()
  {
    $aSideMenu = & elSingleton::getObj('elASideMenu');
    $aSideMenu->ID = (int)$this->_arg();
    $aSideMenu->fetch();
    
    if (!$aSideMenu->editAndSave())
    {
      $this->_initRenderer();
      return $this->_rnd->addToContent( $aSideMenu->formToHtml()); 
    }
    
    elMsgBox::put( m('Data was saved') );
    elLocation( EL_URL.$this->_smPath );
    
  }
  
  function rmAMenu()
  {
    $aSideMenu = & elSingleton::getObj('elASideMenu');
    $aSideMenu->ID = (int)$this->_arg();
    if ( !$aSideMenu->fetch() )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array(m('Additional side menu'), $aSideMenu->ID), EL_URL.$this->_smPath );
    }
    $aSideMenu->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), m('Additional side menu'), $aSideMenu->name) );
    elLocation( EL_URL.$this->_smPath );
  }
  
  function selectPages()
  {
	
    $mID = (int)$this->_arg();
    if ( EL_ADD_MENU_TOP == $mID )
    {
      $label = m('Top additional menu');
      $fld = 'in_add_menu_top';
      $menu = $this->_aMenus[EL_ADD_MENU_TOP];
    }
    else
    {
      $label = m('Bottom additional menu');
      $fld = 'in_add_menu_bot';
      $menu = $this->_aMenus[EL_ADD_MENU_BOT];
    }
	// elPrintR($menu);
	$pageIDs = array();
	for ($i=0; $i < sizeof($menu); $i++) { 
		$pageIDs[] = $menu[$i]['id'];
	}
    // $form = & $this->_makeSelectPagesForm( $label, array_keys($menu) );
	$form = & $this->_makeSelectPagesForm( $label, $pageIDs );
    if ( $form->isSubmitAndValid() )
    {
      $all = $form->getValue();
      $pagesList = $all['menu'];
      $db = & elSingleton::getObj('elDb');
      $db->query('UPDATE el_menu SET '.$fld.'=\'0\' ');
      if ( !empty($pagesList) )
      {
        $sql = 'UPDATE el_menu SET '.$fld.'=\'1\' WHERE id IN ('.implode(',', $pagesList).')'; 
        $db->query( $sql );
      }
      elMsgBox::put( m('Data was saved') );
      elLocation( EL_URL.$this->_smPath );
    }
    $this->_initRenderer();
    $this->_rnd->addToContent( $form->toHtml() );
  }
  
  function manageIcon()
  {
    $mID = (int)$this->_arg(0);
    $pID = (int)$this->_arg(1);
    $page = & elSingleton::getObj('elNavPage');
    $page->setUniqAttr( $pID ); 
    if ( !$page->fetch() )
    {
      elThrow(E_USER_NOTICE, 'Object "%s" ID="%d" does not exists', array(m('Page'), $pID), EL_URL.$this->_path);
    }
    
    $page->manageIcons(EL_ADD_MENU_TOP == $mID ? 'ico_add_menu_top' : 'ico_add_menu_bot');
    $this->_initRenderer();
    $this->_rnd->addToContent( $page->formToHtml());
  }        
  
  function &_makeSelectPagesForm( $label, $pageIDs )
  {
    $p = &elSingleton::getObj('elNavPage');    
    $pagesList = $p->tree->quickList(); 
    unset($pagesList[1]);
       // elPrintR($pageIDs);
    $form = & elSingleton::getObj('elForm');
    $form->setLabel( $label );
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') ); 
    $form->add( new elCheckBoxesGroup('menu', '', $pageIDs, $pagesList) );
    return $form;
  }
    
  function _onInit()
  {
    $nav   = & elSingleton::getObj('elNavigator');
    $this->_aMenus = $nav->getAdditionalMenus();
    $this->_aMenus[EL_ADD_MENU_SIDE] = array();
    $this->_stats = array_map('m', $this->_stats);
    $aSideMenu = & elSingleton::getObj('elASideMenu');
    $this->_aMenus[EL_ADD_MENU_SIDE] = $aSideMenu->getAll(); 
  }
  
  function &_makeConfForm()
  {
    $form = & parent::_makeConfForm();
    $form->setLabel( m('Configure additional navigation menu') );

    $form->add( new elSelect('addMenuTop',    m('Top additional menu'),    $this->_conf('addMenuTop'),     $this->_stats ) );
    $form->add( new elSelect('addMenuBottom', m('Bottom additional menu'), $this->_conf('addMenuBottom'),  $this->_stats ) );
    
    return $form;
  }
  
  
}

?>