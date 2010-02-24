<?php

class elCatalogCrossLinksManager
{
  /**
   * ID текущей стр
   *
   * @var int
   */
  var $pageID    = 0;
  /**
   * имя таблицы
   *
   * @var string
   */
  var $_tb       = 'el_catalogs_crosslink';
  /**
   * Объект elNavigator
   *
   * @var object
   */
  var $_nav      = null;
  /**
   * DB object
   *
   * @var object
   */
  var $_db       = null;
  /**
   * объект класса elForm
   *
   * @var object
   */
  var $_form     = null;
  /**
   * Массив алиасов имен групп связанных объектов
   * элемент ('crossLinksGroups') конфига текущего модуля
   *
   * @var array
   */
  var $_conf     = array();
  /**
   * Массив имен модулей-каталогов, с объектами которых можем связывать
   *
   * @var array
   */
  var $_typesMap = array(
    'DocsCatalog'    => array('elDCatalogItem',     'el_dcat_'),
    'IShop'          => array('elIShopItem',    'el_ishop_'),
    'FileArchive'    => array('elFAFile',       'el_fa_'),
    'GoodsCatalog'   => array('elGCatalogItem', 'el_gcat_'),
    'VacancyCatalog' => array('elVacancy',      'el_vaccat_'),
    'TechShop'       => array('elTSItem',       'el_techshop_'),
  );

  /**
   * Конструктор
   *
   * @return elCatalogCrossLinksManager
   */
  function elCatalogCrossLinksManager()
  {
    $this->_nav   = & elSingleton::getObj('elNavigator');
    $this->pageID = $this->_nav->getCurrentPageID();
    $this->_db    = &elSingleton::getObj('elDb');
    if ( !$this->_db->isTableExists('el_catalogs_crosslink') )
    {
      $sql = "CREATE TABLE IF NOT EXISTS `el_catalogs_crosslink` (
        id int(5) NOT NULL auto_increment,
        mpid int(3) NOT NULL default 0,
        mid int(5) NOT NULL default 0,
        spid int(3) NOT NULL default 0,
        scatid int(3) NOT NULL default 0,
        sid int(5)  NOT NULL default 0,
        PRIMARY KEY(id),
        KEY(mpid, mid)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
      $this->_db->query($sql);
    }
  }


  /**
   * Возвращает массив связанных объектов
   *
   * @param  int   $mid  ID айтема с которым связаны объекты
   * @return array
   */
  function getLinkedObjects($mid)
  {
	
    $tmp = $ret = array();
    // список страниц-каталогов в которых есть связанные объекты
    $sql = 'SELECT l.spid, l.sid, m.module, m.name '
      .'FROM '.$this->_tb.' AS l, el_menu AS m '
      .'WHERE mpid=\''.$this->pageID.'\' AND mid='.intval($mid).' AND m.id=l.spid ORDER BY m._left';
    $this->_db->query($sql);

    while ( $r = $this->_db->nextRecord() )
    {
      if ( !empty($tmp[$r['spid']]))
      {
        $tmp[$r['spid']][5][] = $r['sid'];
      }
      elseif ( false != ($nfo = $this->_getPageNfo($r['spid'], $r['module'])) )
      {
        $tmp[$r['spid']]    = $nfo;
        $tmp[$r['spid']][5] = array($r['sid']);
        $tmp[$r['spid']][6] = !empty($this->_conf[$r['spid']]) ? $this->_conf[$r['spid']] : $r['name'];
      }
    }

    // формируем массив имен и URL'ов объектов
	
    foreach ( $tmp as $pageID=>$v )
    { 
        $URL = $this->_nav->getPageURL($pageID);
        $sql = 'SELECT i2c.c_id, i.id, i.name '
              	.' FROM '.$v[2].' AS i, '.$v[4].' AS i2c, '.$v[3].' AS c '
              	.'WHERE i.id IN ('.implode(',', $v[5]).') AND i2c.i_id=i.id AND c.id=i2c.c_id '
				.'GROUP BY i.id '
              	.'ORDER BY c._left,  IF(i2c.sort_ndx>0, LPAD(i2c.sort_ndx, 4, "0"), "9999"), i.name';
        $this->_db->query($sql);
        while ($r = $this->_db->nextRecord())
        {
          if ( empty($ret[$pageID]) )
          {
            $ret[$pageID] = array('name'=>$v[6], 'items'=>array());
          }
          $ret[$pageID]['items'][] = array('url'=>$URL.'item/'.$r['c_id'].'/'.$r['id'].'/', 'name'=>$r['name']);
        }
    }
    return $ret;
  }

  /**
   * Сохраняет новые имена-алиасы для групп связанных объектов
   *
   * @return bool
   */
  function confCrossLinks()
  {
	$conf = & elSingleton::getObj('elXmlConf');
	$view = $conf->get('crossLinksView', $this->pageID);
    $this->_makeConfForm($view);
    if ($this->_form->isSubmitAndValid() )
    {
      $data = $this->_form->getValue(); 
      
      $conf->drop('crossLinksGroups', $this->pageID);
      $conf->set('crossLinksGroups', $data['alias'], $this->pageID);
		$conf->set('crossLinksView', (int)$data['crossLinksView'], $this->pageID);
      $conf->save();
      return true;
    }
    return false;
  }

  /**
   * Изменяет список связанных объектов для текущего айтема
   *
   * @param  object $masterItem
   * @return bool
   */
  function editCrossLinks(&$masterItem)
  {
	include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elCatalogItem.class.php';
    // извлекаем ID связаных объектов
    $vals = array();
    $sql = 'SELECT spid, scatid, sid FROM '.$this->_tb.' WHERE mpid=\''.$this->pageID.'\' AND mid=\''.$masterItem->ID.'\'';
    $this->_db->query($sql);
    while ($r = $this->_db->nextRecord())
    {
      $vals[$r['spid']][$r['scatid']][$r['sid']] = 1;
    }

    // массив деревьев категорий и айтемов из кaталогов для загрузки в форму
    $pages = $this->_nav->getPagesByModules( array_keys($this->_typesMap) ); 
    $tree  = array();
	$obj = & new elCatalogItem();

    foreach ($pages as $ID=>$page)
    {
      if ( false == (list($module, $objName, $tbi, $tbc, $tbi2c) = $this->_getPageNfo($ID, $page['module'])) )
      {
        continue;
      }
      $sql       = 'SELECT id, _left, _right, level, name FROM '.$tbc.' ORDER BY _left';
      $tree[$ID] = $this->_db->queryToArray($sql, 'id');
      $sql = 'SELECT c_id, '.implode(',', $obj->listAttrs())
          .' FROM '.$tbi.','.$tbi2c
          .' WHERE id=i_id '
          .' ORDER BY name';
      $this->_db->query( $sql );
      while ($r = $this->_db->nextRecord())
      {
        if (empty($tree[$ID][$r['c_id']]['items']))
        {
          $tree[$ID][$r['c_id']]['items'] = array();
          $tree[$ID][$r['c_id']]['vals'] = array();
        }
        $item = $obj->copy($r);
        $tree[$ID][$r['c_id']]['items'][$item->ID] = $item->getName();
        if (!empty($vals[$ID][$r['c_id']][$item->ID]))
        {
          $tree[$ID][$r['c_id']]['vals'][$item->ID] = $item->ID;
        }
      }
    }
    unset( $item);
    $this->_makeForm( $masterItem->getName(), $tree);
    if (!$this->_form->isSubmitAndValid())
    {
      return false;
    }
    $data = $this->_form->getValue(); 
    $sql = 'DELETE FROM '.$this->_tb.' WHERE mpid=\''.$this->pageID.'\' AND mid=\''.$masterItem->ID.'\'';
    $this->_db->query($sql);
    $this->_db->optimizeTable($this->_tb);
    $sql = 'INSERT INTO '.$this->_tb.' (mpid, mid, spid, scatid, sid) VALUES ';
    $sql = '';
    $tpl = '('.$this->pageID.', '.$masterItem->ID.', %d, %d, %d),';
    foreach ($tree as $ID=>$v)
    {
      if (!empty($data['items_'.$ID]))
      {
        foreach ($data['items_'.$ID] as $catID=>$items)
        {
          if (!empty($items))
          {
            foreach ($items as $iID)
            {
              $sql .= sprintf($tpl, $ID, $catID, $iID);
            }
          }
        }
      }
    }
    if (!empty($sql))
    {
      $sql = 'INSERT INTO '.$this->_tb.' (mpid, mid, spid, scatid, sid) VALUES '.substr($sql, 0, -1);
      $this->_db->query($sql);
    }
    return true;
  }

  /**
   * Возвращает ХТМЛ-код текущей формы
   *
   * @return string
   */
  function formToHtml()
  {
    return $this->_form->toHtml();
  }

  /******************************************************/
  //                  PRIVATE METHODS                   //
  /******************************************************/

  /**
   * Создает форму для настройки имен групп
   *
   */
  function _makeConfForm($view)
  {
	$this->_form = & elSingleton::getObj('elForm');
	$this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
	$this->_form->setLabel( m('Linked objects groups configuration') );
	$this->_form->add( new elCData('c1', m('You may set linked objects groups names')) );
	$pages = $this->_nav->getPagesByModules( array_keys($this->_typesMap) );
	foreach ( $pages as $ID=>$page )
	{
		$name = !empty($this->_conf[$ID]) ? $this->_conf[$ID] : $page['name'];
		$label = sprintf( m('Objects from page "%s"'), $page['name']);
		$this->_form->add( new elText('alias['.$ID.']', $label, $name) );
	}
	$views = array(
		m('All groups collapsed'),
		m('First group expanded'),
		m('All groups expanded')
		);
	$this->_form->add( new elSelect('crossLinksView', m('Linked objects groups view'), $view, $views));
  }

  /**
   * Создает форму для изменения списка свяханных объектов
   *
   * @param string $name  имя айтема с которым связываем
   * @param array  $tree  дерево каталогов
   */
  function _makeForm($name, $tree)
  {
    $this->_form = & elSingleton::getObj('elForm');
    $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $this->_form->setLabel( sprintf(m('Edit linked object for "%s"'), $name) );
    foreach ($tree as $ID=>$one)
    {
      $root = current($one);
      $c = & new elFormTreeContainer('catalog_'.$ID, $root['name'], $one, 'items_'.$ID);
      $this->_form->add( $c, array('nolabel'=>1) ); 
    }
  }

  /**
   * Возвращает имя модуля, имя объекта и имена таблиц для заданой страницы
   *
   * @param  int    $pageID
   * @param  string $module
   * @return array
   */
  function _getPageNfo( $pageID, $module)
  {
    if ( empty($this->_typesMap[$module]) )
    {
      return false;
    }
    $objName = $this->_typesMap[$module][0];
    $tbi = $this->_typesMap[$module][1].$pageID.'_item';
    $tbc = $this->_typesMap[$module][1].$pageID.'_cat';
    $tbi2c = $this->_typesMap[$module][1].$pageID.'_i2c';

    return array($module, $objName, $tbi, $tbc, $tbi2c);
  }

}

?>