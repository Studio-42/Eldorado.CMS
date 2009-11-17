<?php
/*
* @package eldoradoCore
* Site's meta tags management
*/
include_once EL_DIR_CORE.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';

class elSubModuleMetaTags extends elModule
{
  var $_mMapAdmin = array(
                          'robots' => array('m' => 'editRobotsTxt', 'l' => 'Edit robots.txt file', 'ico' => 'icoEdit', 'g' => 'Actions' )
                          );

  var $_mMapConf = array();
  
	var $_mMapAjax = array(
		'childs' => '_getChilds',
		'meta'   => '_getMeta',
		'update' => '_updateMeta'
		);

  var $modules = array('DocsCatalog'   => array('tbc'=>'el_dcat_%d_cat',     'tbi'=>'el_dcat_%d_item', 'tbi2c'=>'el_dcat_%d_i2c', 'sort'=>array('name', 'crtime DESC, name')),
                      'GoodsCatalog'   => array('tbc'=>'el_gcat_%d_cat',     'tbi'=>'el_gcat_%d_cat', 'tbi2c'=>'el_gcat_%d_i2c', 'sort'=>array('name', 'price, name', 'crtime DESC, name')),
                      'IShop'          => array('tbc'=>'el_ishop_%d_cat',    'tbi'=>'el_ishop_%d_item', 'tbi2c'=>'el_ishop_%d_i2c', 'sort'=>array(1  => 'name', 'code, name', 'price DESC, name', 'crtime DESC, name'  )),
                      'TechShop'       => array('tbc'=>'el_techshop_%d_cat', 'tbi'=>'el_techshop_%d_item', 'tbi2c'=>'el_techshop_%d_i2c', 'sort'=>array('name', 'crtime DESC, name')),
                      'VacancyCatalog' => array('tbc'=>'el_vaccat_%d_cat',   'tbi'=>'el_vaccat_%d_item', 'tbi2c'=>'el_vaccat_%d_i2c', 'sort'=>array('name', 'crtime DESC, name')),
                      'FileArchive'    => array('tbc'=>'el_fa_%d_cat' ),
                      'ImageGalleries' => array('tbc'=>'el_ig_%d_gallery', 'sort'=>array('g_name, g_crtime DESC',' g_crtime DESC, g_name',) ),
                      'News'           => array('tbi'=>'el_news_%d', 'iname'=>'title', 'sort'=>array('published DESC')),
                      'EventSchedule'  => array('tbi'=>'el_event_%d', 'sort'=>array('begin_ts'))
                      );
  
  /**
  * Display list of meta tags
  */
	function defaultMethod()
	{
		if (!empty($_POST['action']) && !empty($this->_mMapAjax[$_POST['action']]))
		{
			$m = $this->_mMapAjax[$_POST['action']];
			return $this->_aMode < EL_WRITE ? $this->_jsonError('Access denied!') : $this->$m();
		}

		if ($this->_aMode < EL_WRITE)
		{
			elThrow(E_USER_WARNING, 'Access denied to %s', '', EL_URL);
		}
		
		elLoadJQueryUI();
		elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
		elAddCss('eldialogform.css', EL_JS_CSS_FILE);
		elAddJs('eldialogform.min.js', EL_JS_CSS_FILE);
		
		$this->_initRenderer();
		$this->_rnd->rndMeta( $this->_getNodes(0) ); 
	}

	function editRobotsTxt()
  	{
  		$rFile = EL_DIR.'robots.txt';
  		if ( !file_exists($rFile) && !$this->_saveRobotsTXT('') )
	  	{
	  		elThrow(E_USER_WARNING, 'Could not not create file %s', 'robots.txt', EL_URL.$this->_smPath);
	  	}
	  	if ( !is_writable($rFile) )
	  	{
	  		elThrow(E_USER_WARNING, 'File %s is not writable', 'roots.txt', EL_URL.$this->_smPath);
	  	}
	  	$form = & elSingleton::getObj('elForm');
	  	$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
	  	$form->setLabel( m('Edit robots.txt file') );
	  	$form->add( new elTextArea('content', m('Content'), file_get_contents($rFile)) );
	  	if ( !$form->isSubmitAndValid() )
	  	{
	  		$this->_initRenderer();
	  		$this->_rnd->addToContent( $form->toHtml() );
	  	}
	  	else
	  	{
	  		if ( !$this->_saveRobotsTXT($form->getElementValue('content')) )
	  		{
	  			elThrow(E_USER_WARNING, 'Could write to file %s', 'robots.txt', EL_URL.$this->_smPath);
	  		}
	  		elMsgBox::put( m('Data saved') );
	  		elLocation(EL_URL.$this->_smPath);
	  	}
  	}
 	

	function _getChilds()
	{
		$ID = !empty($_POST['id']) ? trim($_POST['id']) : '';
		if (!$ID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		list($pID, $cID, $iID) = explode('_', $ID);
		$pID = (int)$pID;
		$cID = (int)$cID;
		$iID = (int)$iID;
		if (!$pID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		$nodes = $this->_getNodes($pID, $cID);
		$ret = array();
		exit(elJSON::encode($nodes));


		exit('get childs');
	}

	function _getMeta()
	{
		$ID = !empty($_POST['id']) ? trim($_POST['id']) : '';
		if (!$ID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		list($pID, $cID, $iID) = explode('_', $ID);
		$pID = (int)$pID;
		$cID = (int)$cID;
		$iID = (int)$iID;
		if (!$pID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		$db    = & elSingleton::getObj('elDb');
		$sql   = 'SELECT LOWER(name) AS name, content FROM el_metatag WHERE page_id='.$pID.' AND c_id='.$cID.' AND i_id='.$iID.' ORDER BY IF (LOWER(name)=\'title\', "0", "1"), name';
		$metas = $db->queryToArray($sql, 'name', 'content');
		exit(elJSON::encode($metas));
	}


	function _updateMeta()
	{
		$ID = !empty($_POST['id']) ? trim($_POST['id']) : '';
		if (!$ID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		list($pID, $cID, $iID) = explode('_', $ID);
		$pID = (int)$pID;
		$cID = (int)$cID;
		$iID = (int)$iID;
		if (!$pID)
		{
			return $this->_jsonError('Invalid arguments');
		}
		$db  = & elSingleton::getObj('elDb');
	    $sql = 'DELETE FROM el_metatag WHERE page_id='.$pID.' AND c_id='.intval($cID).' AND i_id='.intval($iID);
	    $db->query($sql);
	    $db->optimizeTable('el_metatag');
	
		if (!empty($_POST['meta']['name']) && is_array($_POST['meta']['name']))
		{
			for ($i=0, $s = sizeof($_POST['meta']['name']); $i < $s ; $i++) { 
				$n = !empty($_POST['meta']['name'][$i]) ? trim($_POST['meta']['name'][$i]) : '';
				$v = !empty($_POST['meta']['value'][$i]) ? trim($_POST['meta']['value'][$i]) : '';
				if ($n && $v)
				{
					$sql = 'REPLACE INTO el_metatag SET name=\''.mysql_real_escape_string($n).'\', content=\''.mysql_real_escape_string($v).'\', '
			      		.'page_id='.$pID.', c_id='.$cID.', i_id='.$iID;
					$db->query($sql);
				}
			}
		}
		exit(elJSON::encode(array('message' => m('Meta tags updated'))));
	}

	function _jsonError($err, $args=null)
	{
		$err = !empty($args) ? vsprintf(m($err), $args) : m($err);
		exit(elJSON::encode(array('error' => $err)));
	}

  


  function _getNodes($pID, $cID=0)
  {
    $nodes = array();
    $nav   = & elSingleton::getObj( 'elNavigator' );
    $page  = $nav->getPage($pID);
    $db    = & elSingleton::getObj('elDb');
    
    if ( 0 == $pID || $page['has_childs'] && !$cID )
    { // корень сайта или стр с вложенными стр
      $sql = 0 == $pID
        ? 'SELECT id, name, module, _right-_left-1 AS has_childs FROM el_menu WHERE level=1 ORDER BY _left'
        : 'SELECT ch.id, ch.name, ch.module, ch._right-ch._left-1 AS has_childs FROM el_menu AS ch, el_menu AS p '
          .'WHERE p.id=\''.intval($pID).'\' AND (ch._left BETWEEN p._left AND p._right) AND ch.level=p.level+1 ORDER BY ch._left';  
       
      $pages = $db->queryToArray($sql); 

      foreach ($pages as $p)
      {
        $nodes[] = array(
			// 'pid'        => $p['id'],
			// 'cid'        => 0,
			// 'iid'        => 0,
			'id'         => $p['id'].'_0_0',
			'name'       => $p['name'],
			'has_childs' => intval($p['has_childs'] || !empty($this->modules[$p['module']]) ),
			);
      }
    }
    
    if ( !empty($this->modules[$page['module']]) )
    {
      $tbc   = !empty($this->modules[$page['module']]['tbc']) ? sprintf($this->modules[$page['module']]['tbc'], $pID) : '';
      $tbi2c = !empty($this->modules[$page['module']]['tbi2c']) ? sprintf($this->modules[$page['module']]['tbi2c'], $pID) : '';
      $tbi   = !empty($this->modules[$page['module']]['tbi']) ? sprintf($this->modules[$page['module']]['tbi'], $pID) : '';
      $conf  = &elSingleton::getObj('elXmlConf'); 
      
      if ( $tbc )
      { //подкатегории
        $where = $cID ? 'p.id='.intval($cID) : 'p._left=1';
        if ('ImageGalleries'==$page['module'])
        {
          if ( $cID )
          { // в галерее нет подкатегорий
            return $nodes;
          }
          $sortID = (int)$conf->get('gSort', $pID);
          $sort   = 'IF(g_sort_ndx>0, LPAD(g_sort_ndx, 4, "0"), "9999"), '
	      .( ($sortID && $this->modules[$page['module']]['sort'][$sortID]) ? $this->modules[$page['module']]['sort'][$sortID] : 'g_crtime');
            
          $sql = 'SELECT g_id AS id, g_name AS name, 0 AS childs FROM '.$tbc.' ORDER BY '.$sort; 
        }
        elseif ( !$tbi2c )
        {
          
          $sql = 'SELECT c.id, c.name, IF(c._right-c._left>1, 1, 0) AS childs '
                .'FROM '.$tbc.' AS p, '.$tbc.' AS c '
                .'WHERE '.$where.' AND c.level=p.level+1 AND (c._left BETWEEN p._left AND p._right) '
                .'GROUP BY c.id  ORDER BY c._left';
        }
        else
        {
          $sql = 'SELECT c.id, c.name, IF(c._right-c._left>1 OR COUNT(i2c.i_id)>0, 1, 0) AS childs '
                .'FROM '.$tbc.' AS p, '.$tbc.' AS c LEFT JOIN '.$tbi2c.' AS i2c ON i2c.c_id=c.id '
                .'WHERE '.$where.' AND c.level=p.level+1 AND (c._left BETWEEN p._left AND p._right) '
                .'GROUP BY c.id  ORDER BY c._left';
        }
        $db->query($sql); 
        while ( $r = $db->nextRecord() )
        {
          $nodes[] = array(
				// 'pid'       => $pID,
				// 'cid'        => $r['id'],
				// 'iid'        => 0,
				'id'         => $pID.'_'.$r['id'].'_0',
				'name'       => $r['name'],
				'has_childs' => $r['childs']
				);
        }
      }
      
      if ( $tbi )
      { // items
        if ( !$tbi2c )
        {
          $iname = !empty($this->modules[$page['module']]['iname']) ? $this->modules[$page['module']]['iname'] : 'name';
          $sort  = !empty($this->modules[$page['module']]['sort'][0]) ? $this->modules[$page['module']]['sort'][0] :'id';
          $sql   = 'SELECT id, '.$iname.' AS name, 0 AS c_id FROM '.$tbi.' ORDER BY '.$sort;
        }
        else
        {
          $sortID = (int)$conf->get('itemsSortID', $pID);
          $sort = !empty($this->modules[$page['module']]['sort'][$sortID])
            ? $this->modules[$page['module']]['sort'][$sortID]
            : $this->modules[$page['module']]['sort'][0];
          $sort = 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), '.$sort;
          $sql  = 'SELECT i.id, i.name, i2c.c_id FROM '.$tbi.' AS i, '.$tbi2c.' AS i2c WHERE i2c.c_id='.(!$cID ? 1 : $cID).' AND i.id=i2c.i_id ORDER BY '.$sort;
        }
        
        $db->query($sql); 
        while ( $r = $db->nextRecord() )
        {
          $nodes[] = array(
				// 'pid'       => $pID,
				// 'cid'        => $r['c_id'],
				// 'iid'        => $r['id'],
				'id'         => $pID.'_'.$r['c_id'].'_'.$r['id'],
				'name'       => $r['name'],
				'has_childs' => 0
				);
        }
      }
      
    }
    return $nodes;
  }

  

  

  function _saveRobotsTXT( $content )
  {
  	if ( false == ($fp = fopen(EL_DIR.'robots.txt', 'w')))
  	{
  		return false;
  	}
  	fwrite($fp, $content);
  	fclose( $fp );
  	return true;
  }



}


?>