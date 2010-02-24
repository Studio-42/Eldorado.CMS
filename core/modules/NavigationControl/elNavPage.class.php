<?php

class elNavPage extends elMemberAttribute
{
	var $tb            = 'el_menu';
	var $tree          = null;
	var $ID            = 0;
	var $parentID      = 1;
	var $name          = '';
	var $descrip       = '';
	var $dir           = '';
	var $module        = 'Container';
	var $visible       = 2;
	var $visibleLimit  = 0;
	var $perm          = EL_READ;
	var $level         = 2;
	var $redirectURL   = '';
	var $icoMain       = 'default.png';
	var $icoAddMenuTop = 'default.png';
	var $icoAddMenuBot = 'default.png';
	var $altTpl        = '';

	var $_defVals = array(
		'ico_main'         => 'default.png',
		'ico_add_menu_top' => 'default.png',
		'ico_add_menu_bot' => 'default.png');

	var $meta          = array();
	var $metaNames     = array('DESCRIPTION', 'KEYWORDS');

	var $_defModule    = 'Container';
	var $_objName      = 'Site page';

	function elNavPage( $attrs=null, $tb=null, $uniq=null )
	{
		parent::elMemberAttribute( $attrs, $tb, $uniq ); 
		$this->tree = & elSingleton::getObj('elDbNestedSets', $this->tb);
	}
	
	function memberMapping()
	{
		$map = array(
		'id'               => 'ID',
		'name'             => 'name',
		'page_descrip'     => 'descrip',
		'dir'              => 'dir',
		'module'           => 'module',
		'visible'          => 'visible',
		'visible_limit'    => 'visibleLimit',
		'perm'             => 'perm',
		'level'            => 'level',
		'redirect_url'     => 'redirectURL',
		'ico_main'         => 'icoMain',
		'ico_add_menu_top' => 'icoAddMenuTop',
		'ico_add_menu_bot' => 'icoAddMenuBot',
		'alt_tpl'          => 'altTpl'
		);
		return $map;
	}


	function manageIcons($icoType='ico_main')
	{
		$this->makeIconsForm( $icoType );
		if ( $this->form->isSubmitAndValid() )
		{
			$data = $this->form->getValue();
			if ( 0 == $data['action'])
			{
				$icoName = $this->getAttr($icoType);
				if ( $icoName != $this->_defVals[$icoType] &&  !unlink(EL_DIR_STORAGE.'pageIcons/'.$icoName) )
				{
					elThrow(E_USER_WARNING, 'Could not delete file "%s"', $icoName );
				}
				$icoName = '';
			}
			elseif ( !empty($data['ico_file']) )
			{
				$icoName = $data['ico_file']['name'];
				$tmpName = $data['ico_file']['tmp_name'];
				if ( !move_uploaded_file($tmpName, EL_DIR_STORAGE.'pageIcons/'.$icoName) )
				{
					return elThrow(E_USER_WARNING, 'Error uploading file "%s"! See more info in debug or error log.', $icoName );
				}
				//chmod(EL_DIR_FILES.'icons/'.$icoName, 0666 );
			}
			$this->setAttr($icoType, $icoName);
			$this->save();
			elAddJs('window.opener.location.reload(); window.close();', EL_JS_SRC_ONLOAD);
		}
	}

	function makeIconsForm( $ico )
	{
		$this->_formRndClass = 'elTplGridFormRenderer';
		parent::makeForm();
		if ('ico_add_menu_top' == $ico )
		{
			$label = sprintf( m('Icon for page "%s" in top additional menu'), $this->name);
		}
		elseif ('ico_add_menu_bot' == $ico)
		{
			$label = sprintf( m('Icon for page "%s" in bottom additional menu'), $this->name);
		}
		else
		{
			$label = sprintf( m('Icon for page "%s" in main menu'), $this->name);
		}
		$this->form->setLabel( $label );

		$ico = '<img src="'.EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/'.$this->getAttr($ico).'" border="0" />';

		$this->form->add( new elCData('ico', $ico), array('rowspan'=>2)  );
		$js = "document.getElementById('ico_file').style.display = this.value==0 ? 'none' : '';";
		$this->form->add( new elSelect('action', null, 1,
		array( m('Set default icon'), m('Upload new icon')),
		array('onChange'=>$js)) );
		$fi = & new elFileInput('ico_file', null );
		$fi->fileExt = array('jpg', 'gif', 'png');
		$this->form->add( $fi );
		$this->form->renderer->addButton( new elSubmit('s', null, m('Submit'), array('class'=>'submit')) );
		$this->form->renderer->addButton( new elReset('r',  null, m('Drop'),   array('class'=>'submit')) );
		$this->form->setElementRule('ico_file', 'noempty', false);
	}

	function editAndSave()
	{
		if ( $this->ID )
		{
			$this->parentID = $this->tree->getParentID( $this->ID ); //echo $this->parentID;
		}
		$this->makeForm();

		if ( $this->form->isSubmitAndValid() )
		{
			$vals = $this->form->getValue(true); //print_r($vals);
			if ( !$this->tree->getNodeInfo($vals['parent_id']) )
			{
				return elThrow(E_USER_WARNING, 'Parent page ID="%d" does not exits', $vals['parent_id']);
			}
			if ( !$this->_isDirUniq($vals['dir'], $vals['parent_id'], $this->ID) )
			{
				return elThrow(E_USER_WARNING, 'Dir must be unique');
			}

			if ( !$this->ID )
			{
				$this->ID = $this->tree->insert($vals['parent_id']);
				if ( !$this->ID )
				{
					return elThrow(E_USER_WARNING, 'Can not write to DB', null, null,	__FILE__, __LINE__);
				}
			}
			else
			{
				if ( $this->parentID != $vals['parent_id'] )
				{
					if ( !$this->tree->move($this->ID, $vals['parent_id']) )
					{
						return false;
					}
				}
			}
			$this->setAttr('name', $vals['name']);
			$vals['module'] = $this->changeModule( $vals['module'] );
			$this->setAttrs($vals);
			return $this->save();
		}
	}

	function changeModule( $newModule )
	{
		if ( $this->module == $newModule )
		{
			return $this->module;
		}

		if ( $this->module && !$this->_unsetModule($this->module) )
		{
			elThrow(E_USER_WARNING, 'Failed to change module. (Cant uninstall old page module)');
			return $this->module;
		}
		if ( !$this->_setModule($newModule) )
		{
			elThrow(E_USER_WARNING, 'Failed to change module. (Cant install new page module)');
			return $this->_defModule;
		}
		return $newModule;
	}


	function delete()
	{
		$this->tree = & elSingleton::getObj('elDbNestedSets', $this->tb);
		list($left, $right, $level) = $this->tree->getNodeInfo($this->ID);
		if (0 ==$level)
		{
			elThrow(E_USER_WARNING, 'Root of the site menu can not be deleted', null, EL_URL);
		}
		if ( $right - $left > 1 )
		{
			elThrow(E_USER_WARNING, 'Not empty page can not be deleted', null, EL_URL);
		}
		if ( !$this->_unsetModule($this->module) )
		{
			elThrow(E_USER_WARNING, 'Can not uninstall module before delete page');
		}

		if ( !$this->tree->delete($this->ID) )
		{
			elThrow(E_USER_WARNING, 'Can not delete page with ID="%d"', $this->ID, EL_URL);
		}
		return true;
	}

	function _getAltTplsList()
	{
		$ret = array();
		$tmp = glob(EL_DIR.'style/alternative/*.html'); //elPrintR($tplList);
		foreach ($tmp as $file)
		{
			if ( !is_file($file) || !is_readable($file) )
			{
				continue;
			}
			$f = basename($file);
			$prew = '';
			if ( file_exists(EL_DIR.'style/alternative/preview/'.$f.'.png') )
			{
				$prew = EL_BASE_URL.'/style/alternative/preview/'.$f.'.png';
			}
			elseif ( file_exists(EL_DIR.'style/alternative/preview/'.$f.'.jpg') )
			{
				$prew = EL_BASE_URL.'/style/alternative/preview/'.$f.'.jpg';
			}
			$tpl = empty($prew) ? $f : '<img src="'.$prew.'" />'.$f;
			$ret[$f] = $tpl;
		}
		if (!empty($ret))
		{
			array_unshift($ret, m('Do not use'));
		}
		return $ret;
	}

	function makeForm()
	{
		parent::makeForm();
		
		$mods  = $this->getModulesList();
		$perms = array( 
			0 => m('No access'),
			1 => m('Read only')
			);

		$vis   = array( 
			0 => m('None'),
			1 => m('On site map only'),
			2 => m('Total')
			);

		$vsb   = array( 
			EL_PAGE_DISPL_ALL => m('Whole site'),
			EL_PAGE_DISPL_MAP => m('On site map only'),
			0                 => m('Invisible')
			);

		$vsbLimit = array( 
			0                      => m('None'),
			EL_PAGE_DISPL_LIMIT_NA => m('Only for non authed users'),
			EL_PAGE_DISPL_LIMIT_A  => m('Only for authed users') 
			);

		$this->form->add( new elText('name',            m('Name'),             $this->name) );
		$this->form->add( new elText('page_descrip',    m('Description'),      $this->descrip) );
		$this->form->add( new elText('dir',             m('Dir'),              $this->dir) );
		$this->form->add( new elSelect('parent_id',     m('Parent page'),      $this->parentID,     $this->tree->quickList()) );
		$this->form->add( new elSelect('module',        m('Module'),           $this->module,       $mods) );
		$this->form->add( new elSelect('visible',       m('Visible'),          $this->visible,      $vsb) );
		$this->form->add( new elSelect('visible_limit', m('Visible limit'),    $this->visibleLimit, $vsbLimit) );
		$this->form->add( new elSelect('perm',          m('Default access'),   $this->perm, $perms) );
		$this->form->add( new elText('redirect_url',    m('Redirect URL'),     $this->redirectURL)) ;
		$altTpls = $this->_getAltTplsList();
		if (!empty($altTpls))
		{
			$this->form->add( new elRadioButtons('alt_tpl', m('Alternative template'), $this->altTpl, $altTpls) );
		}
		
		$this->form->setRequired('name');
		$this->form->setElementRule('dir',       'el_vdir', true);
		$this->form->setElementRule('outer_url', 'url',     false);
	}

	function getModulesList()
	{
		$db = & $this->_getDb();
		$sql = 'SELECT el_module.module, IF(descrip !=\'\', descrip, el_module.module) AS name '
		.'FROM el_module LEFT JOIN el_menu ON el_menu.module=el_module.module '
		.'WHERE  (multi=\'1\' OR el_menu.module IS NULL) OR el_module.module=\''.$this->module.'\' '
		.'ORDER BY name';
		return $db->queryToArray( $sql, 'module', 'name' );
	}


	// ======================  PRIVATE METHODS ========================= //

	function _isParentExists($parentID)
	{
		return $this->tree->getNodeInfo($parentID);
	}

	function _isDirUniq($dir, $parentID, $ID)
	{
		$db = & $this->_getDb();
		$sql = 'SELECT ch.id FROM '.$this->tb.' AS ch,'.$this->tb.' AS p '
		.'WHERE p.id=\''.(int)$parentID.'\' AND '
		.'ch._left BETWEEN p._left AND p._right AND '
		.'ch.level=p.level+1 AND '
		.'ch.dir=\''.$dir.'\'';
		if ( $ID )
		{
			$sql .= ' AND ch.id!=\''.(int)$ID.'\'';
		}
		$db->query($sql);
		return !$db->numRows();
	}

	function _attrsForSave()
	{
		$attrs = $this->getAttrs();
		unset( $attrs['level'] );
		return $attrs;
	}

	function _setModule( $ID )
	{
		if ( !$this->_initModule($ID) )
		{
			return elThrow(E_USER_WARNING, 'Module "%s" does not exists', $ID);
		}
		if ( !$this->_processSqlFile('install.sql') )
		{
			return elThrow(E_USER_WARNING, 'Error while installing module "%s"', $this->module->name);
		}
		$this->module->onInstall();
		$this->module->_updateConf( $this->module->_conf );
		return true;

	}

	function _unsetModule( $ID )
	{
		if ( !$this->_initModule($ID) )
		{
			return elThrow(E_USER_WARNING, 'Module ID="%d" does not exists', $ID);
		}
		if ( $this->module->required )
		{
			elThrow(E_USER_WARNING, 'Page "%s" requires for normal site functionality. Delete denied',
			$this->name, EL_URL);
		}

		$this->_processSqlFile('uninstall.sql');
		$this->module->onUninstall();
		$conf = &elSingleton::getObj('elXmlConf');
		$conf->dropGroup( $this->ID );
		$conf->save();
		return true;
	}

	function _initModule( $mName )
	{
		$this->module     = null;
		$this->modulePath = '';
		// check if file and class in it exists
		$this->modulePath = elSingleton::incLib('modules/'.$mName.'/elModule'.$mName.'.class.php', true);
		if ( !$this->modulePath || !class_exists('elModule'.$mName) )
		{
			return false;
		}
		//create module object, but does not call his init() method!!!
		$class                         = 'elModule'.$mName;
		$this->module                  = & new $class;
		$this->module->pageID          = $this->ID;
		$this->module->_confID         = $this->ID;
		$this->module->_conf['module'] = $mName;
		$this->module->name            = $mName;
		return true;
	}

	function _processSqlFile($f)
	{
		$file = $this->modulePath.'/'.$f;
		if ( !is_readable($file) || false == ($sql = file_get_contents($file)) )
		{
			return true;
		}
		$sql = str_replace('{pageID}',   $this->ID,   trim($sql) );
		$sql = str_replace('{pageName}', mysql_real_escape_string($this->name), $sql );
		$sql = explode(';', $sql);
		$db = & $this->_getDb();

		foreach ( $sql as $q )
		{
			$q = trim($q);
			if ( !empty($q) )
			{
				if ( !$db->query($q) )
				{
					return false;
				}
			}
		}
		return true;
	}

}

?>