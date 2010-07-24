<?php
/**
 * IShop product
 *
 * @package IShop
 **/
class elIShopItem extends elDataMapping {
	var $tbmnf      = '';
	var $tbp2i      = '';
	var $tbi2c      = '';
	var $tbgal      = '';
	var $ID         = 0;
	var $typeID     = 0;
	var $mnfID      = 0;
	var $tmID       = 0;
	var $code       = '';
	var $name       = '';
	var $announce   = '';
	var $content    = '';
	var $price      = 0;
	var $special    = 0;
	var $ym         = 1;
	var $img        = '';
	var $gallery;
	var $crtime     = 0;
	var $mtime      = 0;
	var $propVals   = array();
	var $_type      = null;
	var $_factory   = null;
	var $_objName = 'Product';
  
	/**
	 * get item by id
	 *
	 * @return bool
	 **/
	function fetch() {
		if (parent::fetch()) {
			$vals = $this->fetchPropsValues(array($this->ID));
			foreach ($vals as $v) {
				if (!isset($this->propVals[$v['p_id']])) {
					$this->propVals[$v['p_id']] = array();
				}
				$this->propVals[$v['p_id']][] = $v['value'];
			}
			return true;
		}
		return false;
	}

	/**
	 * delete item and all refrenced data
	 *
	 * @return void
	 **/
	function delete() {
		parent::delete(array($this->tbp2i=>'i_id', $this->tbi2c=>'i_id', $this->tbgal => 'i_id'));
	}
	
	/**
	 * return properties values by items ids
	 *
	 * @param  array  $ids
	 * @return array
	 **/
	function fetchPropsValues($ids) {
		$db = $this->_db();
		return $db->queryToArray(sprintf('SELECT i_id, p_id, value FROM %s WHERE i_id IN (%s)', $this->tbp2i, implode(',', $ids)));
	}

	/**
	 * return item type
	 *
	 * @return elIShopItemType
	 **/
	function getType() {
		if (!isset($this->_type)) {
			$this->_type = $this->_factory->getFromRegistry(EL_IS_ITYPE, $this->typeID);
		}
		return $this->_type;
	}

	/**
	 * return categories in which item is found
	 *
	 * @return array
	 **/
	function getCats() {
		$a = array();
		$db = $this->_db();
		if ($db->query(sprintf('SELECT c_id FROM %s WHERE i_id=%d', $this->tbi2c, $this->ID))) {
			while ($r = $db->nextRecord()) {
				array_push($a, $r['c_id']);
			}
		}
		return $a;
	}

	/**
	 * return item manufacturer
	 *
	 * @return elIShopManufacturer
	 **/
	function getMnf() {
		return $this->_factory->getFromRegistry(EL_IS_MNF, $this->mnfID);
	}

	/**
	 * return item trademark
	 *
	 * @return elIShopTm
	 **/
	function getTm() {
		return $this->_factory->getFromRegistry(EL_IS_TM, $this->tmID);
	}
	
	/**
	 * return properties marked for annouce in items list
	 *
	 * @return array
	 **/
	function getAnnouncedProperties() {
		$ret   = array();
		$type  = $this->getType();
		foreach ($type->getAnnouncedProperties() as $p) {
			$ret[] = array(
				'name'  => $p->name,
				'value' => $p->valuesToString(isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : array())
				);
		}
		return $ret;
	}

	/**
	 * return properties grouped by position
	 *
	 * @return array
	 **/
	function getProperties() {
		$ret   = array('top'=>array(), 'table'=>array(), 'bottom'=>array(), 'order'=>array());
		$type  = $this->getType();
		$props = $type->getProperties();

		foreach ($props as $p) {
			
			$ml = $p->isMultiList();
			if ($ml) {
				$ret['order'][] = array(
					'name'  => $p->name, 
					'value' => $p->valuesToString($value)
					);
			}
			if (!($ml || $p->isHidden)) {
				$value = isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : array();
				$value = $p->valuesToString($value);
				if ($value) {
					$ret[$p->displayPos][] = array(
						'name'  => $p->name, 
						'value' => $value
						);
				}
			}
		}
		return $ret;
	}

	/**
	 * return images list - gallery
	 *
	 * @return array
	 **/
	function getGallery() {
		if ($this->ID && !isset($this->gallery)) {
			$db = $this->_db();
			$this->gallery = $db->queryToArray(sprintf('SELECT id, img FROM %s WHERE i_id=%d ORDER BY id', $this->tbgal, $this->ID), 'id', 'img');
		}
		return $this->gallery;
	}

	/**
	 * return first image thumbnail url 
	 *
	 * @return string
	 **/
	function getDefaultTmb($type = 'l') {
		$gallery = $this->getGallery();
		if ($gallery) {
			return $this->getTmbURL(key($gallery), $type);
		}
	}
	
	/**
	 * return thumbnail url by image id and tmb type
	 *
	 * @param  int    $id    image id
	 * @param  string $type  tmb type/size
	 * @return string
	 **/
	function getTmbURL($id, $type = 'l') {
		$gallery = $this->getGallery();
		if (isset($gallery[$id])) {
			list($tmbl, $tmbc) = $this->_getTmbNames($gallery[$id]);
			return EL_BASE_URL.('l' == $type ? $tmbl : $tmbc);
		}
	}
	
	/**
	 * return thumbnail path by image id and tmb type
	 *
	 * @param  int    $id    image id
	 * @param  string $type  tmb type/size
	 * @return string
	 **/
	function getTmbPath($id, $type = 'l') {
		return '.'.str_replace(EL_BASE_URL, '', $this->getTmbURL($id, $type));
	}
	
	/**
	 * delete image from item gallery
	 *
	 * @return bool
	 **/
	function rmImage($img_id = false) {
		if ((!$this->ID) || (!$img_id) || (($img = $this->getImg($img_id)) === false)) {
			return false;
		}
		$db  = $this->_db();
		$db->query(sprintf('DELETE FROM %s WHERE id=%d AND i_id=%d', $this->tbgal, $img_id, $this->ID));
		list($tmbl, $tmbc) = $this->_getTmbNames($img);
		@unlink('.'.$tmbl);
		@unlink('.'.$tmbc);
		return true;
	}

	/**
	 * change image in item gallery
	 *
	 * @return bool
	 **/
	function changeImage($img_id, $lSize, $cSize) {
		if (empty($_POST['imgURL'])) {
			return false;
		}

		$imgPath = urldecode(str_replace(EL_BASE_URL, '', $_POST['imgURL']));
		if (in_array($imgPath, $this->getGallery())) {
			return elThrow(E_USER_WARNING, 'This image is already in the gallery');
		}

		$_image = & elSingleton::getObj('elImage');
		list($tmbl, $tmbc) = $this->_getTmbNames($imgPath);

		// list image
		$lSize = $lSize < 30 ? 120 : $lSize;
		$info = $_image->imageInfo('.'.$imgPath);
		list($w, $h) = $_image->calcTmbSize($info['width'], $info['height'], $lSize);
		if (!$_image->tmb('.'.$imgPath, '.'.$tmbl, $w, $h)) {
			return elThrow(E_USER_WARNING, $image->error);
		}

		// item card image
		$cSize = $cSize < 30 ? 120 : $cSize;
		$info = $_image->imageInfo('.'.$imgPath);
		list($w, $h) = $_image->calcTmbSize($info['width'], $info['height'], $cSize);
		if (!$_image->tmb('.'.$imgPath, '.'.$tmbc, $w, $h)) {
			return elThrow(E_USER_WARNING, $image->error);
		}

		if ($img_id > 0) {
			if (($img = $this->getImg($img_id)) !== false) {
				if ($img != $imgPath) // if set to the same image as before do not delete just generated thumbs 
				{
					list($tmbl, $tmbc) = $this->_getTmbNames($this->img);
					@unlink('.'.$tmbl);
					@unlink('.'.$tmbc);
				}
			}
			$sql = sprintf('UPDATE %s SET img="%s" WHERE id=%d AND i_id=%d LIMIT 1', $this->tbgal, $imgPath, $img_id, $this->ID);			
		} else {
			$sql = sprintf('INSERT INTO %s (i_id, img) VALUES (%d, "%s")', $this->tbgal, $this->ID, $imgPath);
		}
		$db = $this->_db();
		$db->query($sql);
		return true;
	}

	/*********************************************************/
	/***                     PRIVATE                       ***/
	/*********************************************************/	

	/**
	 * Create form for edit/create object 
	 *
	 * @param  array  $params 
	 * @return void
	 **/
	function _makeForm($params=null) {
		parent::_makeForm();
		if ($this->ID) {
			$cats = $this->getCats();
		} else {
			$this->typeID = $params['typeID'];
			$this->mnfID  = $params['mnfID'];
			$cats         = array($params['catID']);
		}
		$type = $this->_factory->getFromRegistry(EL_IS_ITYPE, $this->typeID);
		$this->_form->setLabel(sprintf($this->ID ? m('Edit object "%s"') : m('Create object "%s"'), $type->name));
		
		$this->_form->add(new elText('code',  m('Code/Articul'), $this->code) );
		$this->_form->add(new elText('name',  m('Name'),         $this->name,  array('style' => 'width:100%')));
		$this->_form->add(new elText('price', m('Price'),        $this->price) );
		
		$cat = $this->_factory->create(EL_IS_CAT);
		$this->_form->add(new elMultiSelectList('cat_id', m('Parent category'), $cats, $cat->getTreeToArray(0, true)));
		
		$mnfs  = array();
		$_mnfs = $this->_factory->getAllFromRegistry(EL_IS_MNF);
		foreach ($_mnfs as $m) {
			$mnfs[$m->ID] = $m->name;
		}
		if ($mnfs) {
			$this->_form->add(new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $mnfs));
			$tms  = array();
			$_tms = $this->_factory->getAllFromRegistry(EL_IS_TM);
			foreach ($_tms as $id=>$tm) {
				$tms[$id] = $tm->name;
			}
			if ($tms) {
				$tms = array(m('Undefined')) + $tms;
				$this->_form->add(new elSelect('tm_id', m('Trade mark/model'), $this->tmID, $tms));
			}
		}
		
		$this->_form->add(new elEditor('announce', m('Announce'), $this->announce, array('height' => 250)));
	    $this->_form->add(new elEditor('content',  m('Content'),  $this->content));
		// elPrintR($this->propVals);
		foreach ($type->getProperties() as $p) {
			$this->_form->add($p->toFormElement(isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : null, true));
		}
	
		$this->_form->add(new elSelect('ym', m('Upload into Yandex market'), $this->ym, $GLOBALS['yn']));
	
		$this->_form->setRequired('cat_id[]');
	    $this->_form->setRequired('code');
	    $this->_form->setRequired('name');
	}
	
	/**
	 * check for unique item articul/code
	 *
	 * @return bool
	 **/
	function _validForm() {
		$data = $this->_form->getValue();
		$code = mysql_real_escape_string($data['code']);
		$db   = $this->_db();
		$test = $db->queryToArray(sprintf('SELECT id FROM %s WHERE code="%s" AND id<>%d', $this->_tb, mysql_real_escape_string($data['code']), $this->ID));
		return count($test) ? $this->_form->pushError('code', m('Item code must be unique')) : true;
	}

	/**
	 * save categories and properties values for item
	 *
	 * @param  bool  $isNew  flag - is this item created right now?
	 * @param  array $params form params
	 * @return bool
	 **/
	function _postSave($isNew, $params=null) {
		$db = $this->_db();
		
		// set categories
		$catIDs = $this->_form->getElementValue('cat_id[]');
		$rm = array();
		$add = array();
		if ($isNew) {
			$add = $catIDs;
		} else {
			$old = $this->getCats();
			$add = array_diff($catIDs, $old);
			$rm  = array_diff($old, $catIDs);
		}
		
		if ($rm) {
			$db->query(sprintf('DELETE FROM %s WHERE i_id=%d AND c_id IN (%s)', $this->tbi2c, $this->ID, implode(',', $rm)));
			$db->optimizeTable($this->tbi2c);
		}
		if ($add) {
			$db->prepare('INSERT INTO '.$this->tbi2c.' (c_id, i_id) VALUES ', '(%d, %d)');
			foreach ($add as $catID) {
				$db->prepareData(array($catID, $this->ID));
			}
			$db->execute();
		}
		
		// save properties values
		$db->query(sprintf('DELETE FROM %s WHERE i_id=%d', $this->tbp2i, $this->ID));
		$db->optimizeTable($this->tbp2i);
		$data  = $this->_form->getValue();
		$type  = $this->getType();
		$props = $type->getProperties();
		foreach ($data as $k=>$v) {
			if (preg_match('/^prop_\d+/i', $k)) {
				$k = (int)str_replace('prop_', '', $k);
				if (isset($props[$k])) {
					if (is_array($v)) {
						foreach ($v as $_v) {
							$db->query(sprintf('INSERT INTO %s (i_id, p_id, value) VALUES (%d, %d, "%s")', $this->tbp2i, $this->ID, $k, mysql_real_escape_string($_v)));
						}
					} else {
						$db->query(sprintf('INSERT INTO %s (i_id, p_id, value) VALUES (%d, %d, "%s")', $this->tbp2i, $this->ID, $k, mysql_real_escape_string($v)));
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * update timestamps before save
	 *
	 * @return array
	 **/
	function _attrsForSave() {
		$attrs = parent::_attrsForSave();
		$attrs['mtime'] = time();
		if (!$this->ID) {
			$attrs['crtime'] = time();
		}
		return $attrs;
	}

	/**
	 * return small and middle tmb names
	 *
	 * @param  string  $imgPath  image path
	 * @return array
	 **/
	function _getTmbNames($imgPath) {
		$imgName = baseName($imgPath);
		$imgDir  = dirname($imgPath).DIRECTORY_SEPARATOR;
		return array($imgDir.'tmbl-'.$imgName, $imgDir.'tmbc-'.$imgName);
	}

	/**
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		$map = array(
			'id'       => 'ID',
			'type_id'  => 'typeID',
			'mnf_id'   => 'mnfID',
			'tm_id'    => 'tmID',
			'code'     => 'code',
			'name'     => 'name',
			'announce' => 'announce',
			'content'  => 'content',
			'price'    => 'price',
			'special'  => 'special',
			'ym'       => 'ym',
			'crtime'   => 'crtime',
			'mtime'    => 'mtime'
			);
		return $map;
	}

} // END class 

?>
