<?php
//TODO - add display page icons

class elModuleContainer extends elModule
{
  var $_conf = array(
	'goFirstChild'   => 0, 
	'displayDescrip' => 1, 
	'deep'           => 0, 
	'showIcons'      => '',
	'cols'           => 1
	);

  function defaultMethod()
  {
    $nav    = &elSingleton::getObj('elNavigator');
    $childs = $nav->getPages($this->pageID, (int)$this->_conf('deep'), false, false, -1 ); 
	if ( empty( $childs) )
	{
		return;
	}
    elseif ( $this->_conf('goFirstChild') && $this->_aMode < EL_WRITE )
    { //  перенаправление на первую вложеную стр
        elLocation($childs[0]['url']);
    }

    $pages   = array();
    $descrip = $this->_conf('displayDescrip');
    foreach ($childs as $one )
    {
      $pages[] = array( 
		'id'      => $one['id'],
		'name'    => $one['name'],
		'descrip' => ($descrip ? $one['page_descrip'] : ''),
        'url'     => $one['url'],
        'level'   => $one['level'],
        'ico'     => $one['ico_main']
		);
    }
    $this->_initRenderer();
    $this->_rnd->render( $pages );
  }

  function &_makeConfForm()
  {
    $form = & parent::_makeConfForm();
	
    $opts = array( m('Display nested pages list'), m('Redirect to first nested page') );
    $js   = "$(this).parents('tr').siblings('tr:has(td > select)').toggleClass('hide');"; 
	$form->add( new elSelect('goFirstChild', m('Container behaviour'), $this->_conf('goFirstChild'), $opts, array('onChange'=>$js)) );

	$attrs = $this->_conf('goFirstChild') ? 'class="hide"' : null;

	$views = array( 1=>m('One column'), 2=>m('Two columns') );
	$form->add( new elSelect('cols', m('Container view'), $this->_conf('cols'), $views ), array('rowAttrs'=>$attrs) );
    
	$opts = array(1 => m('Only one level'),
                  2 => m('Two levels'),
                  0 => m('All levels'));
    $form->add( new elSelect('deep', m('How many levels of nested pages display'), $this->_conf('deep'), $opts ), array('rowAttrs'=>$attrs) );
	$form->add( new elSelect('showIcons', m('Show pages icons'), $this->_conf('showIcons'), $GLOBALS['yn']), array('rowAttrs'=>$attrs) );
	$form->add( new elSelect('displayDescrip', m('Display pages descriptions'), $this->_conf('displayDescrip'), $GLOBALS['yn'] ), array('rowAttrs'=>$attrs) );
    
	elAddJs('jquery.js', EL_JS_CSS_FILE); 
    return $form;
  }
}

?>