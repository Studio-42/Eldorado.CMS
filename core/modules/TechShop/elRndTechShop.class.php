<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndTechShop extends elCatalogRenderer
{
	var $_tpls      = array(
		'item'     => 'item.html',
		'model'    => 'model.html',
		'mnfs'     => 'manufacts.html',
		'mnf'      => 'manufact.html',
		'cmp'      => 'compare.html',
		'ftGroups' => 'ft-groups.html',
		);
	var $_currInfo  = array();
	var $_pricePrec = 0;
	var $_cartOn    = false;


	function init( $moduleName, $conf, $prnt=false, $admin=false, $tabs=null, $curTab=null ) {
		parent::init( $moduleName, $conf, $prnt, $admin, $tabs, $curTab);
		if ( $this->_conf('ishop') > EL_TS_ISHOP_DISABLED )
		{
			$this->_currInfo = elGetCurrencyInfo();
			  $this->_te->assignVars( 'currency',     $this->_currInfo['currency'] );
			  $this->_te->assignVars( 'currencySign', $this->_currInfo['currencySign'] );
			  $this->_te->assignVars( 'currencyName', $this->_currInfo['currencyName'] );
			  $this->_pricePrec = (int)$this->_conf('pricePrec');
		}
	}
	/**
	 * Render item card
	 *
	 * @param  object  $item
	 * @param  array   $linkedObjs  linked objects array
	 * @return void
	 **/
	function renderItem( $item, $linkedObjs=null )
	{
		elLoadJQueryUI();
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');
		if ($this->_admin) {
			elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
		}

	  	$this->_setFile('item');
	  	$this->_te->assignVars( $item->toArray() );
		if ($this->_conf('displayManufact') && !empty($item->mnfName) )
		{
			$this->_te->assignBlockVars('ITEM_MNF', array('mnf_id'=>$item->mnfID, 'mnfName'=>$item->mnfName, 'mnfCountry'=>$item->mnfCountry));
		}
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id' => $item->ID));
			if ($this->_conf('fakePrice')) {
				$this->_te->assignBlockVars('ITEM_ADMIN.FAKE_PRICE', array('id'=>$item->ID), 1);
			}
		}
	  	
		// pricelist or item price
		if ($this->_conf('ishop')) {
			// 
			$price = $item->getPriceList();
			$order = $this->_conf('ishop') == EL_TS_ISHOP_ENABLED;
			if ($price) {
				// elPrintR($price);
				$this->_te->assignBlockVars('TS_TABS.PRICELIST', null, 1);
				foreach ($price as $one) {
					$one['price'] = $this->_formatPrice($one['price']);
					
					$data = array(
						'price' => $this->_formatPrice($one['price']),
						'name'  => $one['name'],
						);
					$this->_te->assignBlockVars('TS_PRICELIST.ROW', $data, 1);
					if ($order) {
						$data = array('url' => EL_URL.'order/'.$this->_catID.'/'.$item->ID.'/'.($item->hasFakePrice ? 'f' : 'm').'/'.$one['modelID'].'/');
						$this->_te->assignBlockVars('TS_PRICELIST.ROW.MODEL_ORDER', $data, 2);
					}
				}
			} elseif ($item->price) {
				$this->_te->assignBlockVars('ITEM_PRICE', array('price' => $this->_formatPrice($item->price)));
				if ($order) {
					$this->_te->assignBlockVars('ITEM_ORDER', array('url' => EL_URL.'order/'.$this->_catID.'/'.$item->ID.'/'));
				}
			}
		}
	
		if ($item->models) {
			// models features
			$colspan = sizeof($item->models)+1;
			$this->_te->assignBlockVars('TS_TABS.FEATURES', array('label' => m('Models/Features')), 1);
			$descrip = $img = $price = false;
			foreach ($item->models as $id=>$model) {
				$this->_te->assignBlockVars('TS_FEATURES.HEAD.MODEL', array('code' => $model->code, 'name' => $model->name), 2);
				if ($this->_admin) {
					$this->_te->assignBlockVars('TS_FEATURES.HEAD.MODEL.ADMIN', array('i_id'=>$item->ID, 'id' => $model->ID), 3);
				}
				
				if ($model->descrip) {
					$descrip = true;
				}
				if ($model->img) {
					$img = true;
				}
			}
			if ($descrip || $img || $price) {
				foreach ($item->models as $id=>$model) {
					if ($descrip) {
						$this->_te->assignBlockVars('TS_FEATURES.HEAD.DESCRIPTIONS.DESCRIP', array('descrip' => $model->descrip), 3);
					}
					if ($img) {
						if ($model->img) {
							$this->_te->assignBlockVars('TS_FEATURES.HEAD.IMAGES.IMG_PLACE.IMG', $this->_modelImgData($model), 3);
						} else {
							$this->_te->assignBlockVars('TS_FEATURES.HEAD.IMAGES.IMG_PLACE', null, 3);
						}
						
					}
				}
			}
			
			foreach ($item->features as $group) {
				$this->_te->assignBlockVars('TS_FEATURES.GROUP', array('name'=>$group['name'], 'colspan'=>$colspan), 1);
				foreach ($group['features'] as $fid=>$f) {
					$this->_te->assignBlockVars('TS_FEATURES.GROUP.FEATURE_ROW', $f, 2);
					if ($f['is_split']) {
						// find data for shared value
						$data = array('colspan'=>' colspan="'.($colspan-1).'"', 'value' => '');
						foreach ($item->models as $model) {
							if (!empty($model->features[$fid])) {
								$data['value'] = str_replace('\n', '<br />', $model->features[$fid]);
								break;
							}
						}
						$this->_te->assignBlockVars('TS_FEATURES.GROUP.FEATURE_ROW.FEATURE', $data, 3);
					} else {
						foreach ($item->models as $model) {
							$this->_te->assignBlockVars('TS_FEATURES.GROUP.FEATURE_ROW.FEATURE', array('value' => !empty($model->features[$fid]) ? str_replace('\n', '<br/>', $model->features[$fid]) : ''), 3);
						}
						
					}
				}
			}
			
		} elseif ($item->features) {
			// item own features
			$this->_te->assignBlockVars('TS_TABS.FEATURES', array('label' => m('Features')), 1);
			foreach ($item->features as $group) {
				$this->_te->assignBlockVars('TS_FEATURES.GROUP', array('name'=>$group['name'], 'colspan'=>2), 1);
				foreach ($group['features'] as $f) {
					$f['value'] = str_replace('\n', '<br/>', $f['value']);
					$this->_te->assignBlockVars('TS_FEATURES.GROUP.FEATURE_ROW', $f, 2);
					$this->_te->assignBlockVars('TS_FEATURES.GROUP.FEATURE_ROW.FEATURE', $f, 3);
				}
			}
		}
		
		if ($linkedObjs) {
			if ($this->_conf('linkedObjPos') == EL_TS_LOBJ_POS_TAB) {
				foreach ($linkedObjs as $id=>$o) {
					$this->_te->assignBlockVars('TS_TABS.L_OBJECTS', array('id'=>$id, 'name'=>$o['name']), 1);
					$this->_te->assignBlockVars('TS_L_OBJECT', array('id'=>$id));
					foreach ($o['items'] as $i) {
						$this->_te->assignBlockVars('TS_L_OBJECT.LINK', $i, 1);
					}
				}
			} else {
				$this->_rndLinkedObjs($linkedObjs);
			}
		}

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function renderFeaturesGroups($groups)
	{
		$this->_setFile('ftGroups');
		foreach ($groups as $group) {
			$this->_te->assignBlockVars('TS_FT_GROUP', $group->toArray());
			foreach ($group->features as $f) {
				$this->_te->assignBlockVars('TS_FT_GROUP.FT', $f->toArray(), 1);
			}
		}
	}


	function rndManufacturers($mnfsList)
	{
		$this->_setFile('mnfs');
		foreach ($mnfsList as $mnf)
		{
			$this->_te->assignBlockVars('MNF', $mnf );
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('MNF.ADMIN', array('id' => $mnf['id']), 1);
			}
		}
	}

	function rndManufacturer($mnf)
	{
		$this->_setFile('mnf');
		$data = $mnf->toArray();
		if (!$data['descrip']) {
			$data['descrip'] = $data['announce'];
		}
		$this->_te->assignVars($data);
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('MNF_ADMIN', array('id' => $mnf->ID), 1);
		}
	}

	function rndManufacturerItems($items)
	{
		$this->_setFile();
		if(!empty($items))
		{
			if (2 == $this->_conf('itemsCols'))
			{
				$this->_rndItemsTwoColumns($items);
			}
			else
			{
				$this->_rndItemsOneColumn($items);
			}
		}
	}

	function rndFtList($ftGroups)
	{
	  $this->_setFile('ftGroups');
	  foreach ($ftGroups as $ftg)
	  {
	    $this->_te->assignBlockVars('FT_GROUP', $ftg->toArray());
	    if ( false != ($fts = $ftg->getFeatures()) )
	    {
	      $this->_te->assignBlockVars('FT_GROUP.FTS', array('gid'=>$ftg->ID), 1);
	      foreach ($fts as $ft)
	      {
	        $this->_te->assignBlockVars('FT_GROUP.FTS.FT', $ft->toArray(), 2);
	      }
	    }

	  }
	}

	function rndCompareTable($tb) {
		$items    = $tb['items'];
		$features = $tb['features'];
		$this->_setFile('cmp');
		$this->_te->assignVars('colspan', sizeof($items)+1);
		
		foreach ($items as $item) {
			$this->_te->assignBlockVars('TS_ITEM', array('name' => $item['name'], 'id'=>$item['i_id']));
		}
		
		foreach ($features as $group) {
			$this->_te->assignBlockVars('TS_FT_GROUP', array('name' => $group['name']));
			foreach ($group['features'] as $fid => $name) {
				$this->_te->assignBlockVars('TS_FT_GROUP.FEATURE', array('name' => $name), 1);
				foreach ($items as $item) {
					$this->_te->assignBlockVars('TS_FT_GROUP.FEATURE.VALUE', array('value' => isset($item['features'][$fid]) ? $item['features'][$fid] : '' ), 2);
				}
			}
		}
		
	}

	//********************************************//
	//                  PRIVATE                   //
	//********************************************//
	/**
	 * Рисует список документов в одну колонк
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsOneColumn($items)
	{
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');
		$features = $this->_conf('featuresInItemsList') >= EL_TSHOP_FEATURES_IN_LIST_ENABLE;
		$compare  = $this->_conf('featuresInItemsList') == EL_TSHOP_FEATURES_IN_LIST_COMPARE;
		$price    = $this->_conf('ishop') > EL_TS_ISHOP_DISABLED;
		$fakePrice = $this->_conf('faksePrice');
		$i = 0;
		foreach ($items as $item) {
			$data = $item->toArray();
			$data['cssRowClass'] = $i++%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ($this->_admin) {
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
			}
			if ($this->_conf('displayManufact') && !empty($data['mnfName']) ) {
				$mnf = array('mnf_id'=>$data['mnf_id'], 'mnfName'=>$data['mnfName'], 'mnfCountry'=>$data['mnfCountry']);
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF', $mnf, 3);
			}
			
			if ($features) {
				if ($item->models) {
					$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS', array('i_id'=>$item->ID), 3);
					foreach ($item->models as $model) {
						$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS.MODEL', $model->toArray(), 3);
						if ($this->_admin) {
							$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS.MODEL.MODEL_ADMIN', array('i_id'=>$model->iID, 'id'=>$model->ID), 4);
						}
						if ($compare) {
							$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS.MODEL.MODEL_COMPARE', array('id'=>$model->ID), 4);
						}
						if ($price && $model->price>0 && !$item->hasFakePrice) {
							$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS.MODEL.MODEL_PRICE', array('price'=>$this->_formatPrice($model->price)), 4);
						}
						if ($model->img) {
			        		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MODELS.MODEL.MODEL_IMG', $this->_modelImgData($model), 4);
			      		}
						if ($model->features)
			      		{
							$this->_te->assignBlockFromArray('ITEMS_ONECOL.ITEM.MODELS.MODEL.MODEL_ANN.MODEL_ANN_FT', $model->features, 5);
			      		}
					}
				}
				
			}
			
		}
		
		return;
		$i = 0;
		foreach ($items as $item)
		{
			$data = $item->toArray();
			$data['cssRowClass'] = $i++%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
			}
			if ($this->_conf('displayManufact') && !empty($data['mnfName']) )
			{
				$mnf = array('mnf_id'=>$data['mnf_id'], 'mnfName'=>$data['mnfName'], 'mnfCountry'=>$data['mnfCountry']);
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF', $mnf, 3);
			}
			
			if ( false != ($models = $item->getModels()) )
			{
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MOD_ROW', array('i_id'=>$item->ID), 3);
				foreach ($models as $model)
		    	{
		      		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MOD_ROW.MODEL', $model->toArray(), 3);
		      		if (!empty($this->_currInfo) && $model->price>0 )
		      		{
		        		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MOD_ROW.MODEL.MODEL_PRICE',
		        			array('price'=>$this->_formatPrice($model->price)), 4);
		      		}
					if ($this->_admin)
					{
						$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MOD_ROW.MODEL.MODEL_ADMIN', array('i_id'=>$model->iID, 'id'=>$model->ID), 4);
					}
		      		if ($model->img)
		      		{
						$vars = array( 
								'm_id'   => $model->ID, 
								'tmb'    => EL_BASE_URL.dirname($model->img).'/mini_'.basename($model->img), 
								'target' => EL_BASE_URL.$model->img
								);
		        		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MOD_ROW.MODEL.MODEL_IMG', $vars, 4);
		      		}
		      		if (!empty($item->ft[$model->ID]))
		      		{
						$this->_te->assignBlockFromArray('ITEMS_ONECOL.ITEM.MOD_ROW.MODEL.MODEL_ANN.MODEL_ANN_FT', $item->ft[$model->ID], 5);
		      		}
		    	}
			}
			else
			{
		    	if ( !empty($this->_currInfo) && $item->price>0 )
		    	{
		      		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.PRICE', array('price'=>$this->_formatPrice($item->price)), 2);
		    	}
		    	// display item announced features if exists
		    	if (!empty($item->ft))
		    	{
					$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.COMPARE', array('id'=>$item->ID), 2);
					$this->_te->assignBlockFromArray('ITEMS_ONECOL.ITEM.ANN.ANN_FT', $item->ft, 3);
		    	}
			}
		}
		if (!empty($this->_currInfo))
		{
		  $this->_te->assignBlockVars('TS_PRICE_DOWNL');
		}
		$this->_te->assignBlockVars('TS_COMPARE');
	}

	/**
	 * Рисует список документов в две колонки
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsTwoColumns($items)
	{
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');

		$features = $this->_conf('featuresInItemsList') >= EL_TSHOP_FEATURES_IN_LIST_ENABLE;
		$compare  = $this->_conf('featuresInItemsList') == EL_TSHOP_FEATURES_IN_LIST_COMPARE;
		$price    = $this->_conf('ishop') > EL_TS_ISHOP_DISABLED;
		$fakePrice = $this->_conf('faksePrice');
		$rowCnt   = $i = 0; 
		$s        = sizeof($items);
		
		if ($compare) {
			elAddJs('jquery.cluetip.js', EL_JS_CSS_FILE);
			elAddCss('jquery.cluetip.css');
		}
		
		foreach ($items as $item)
		{
			$data = $item->toArray();
			$data['cssLastClass'] = 'col-last';
			if (!($i++%2))
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM', $data, 1 );
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
				
			}
			if ($this->_conf('displayManufact') && !empty($data['mnfName']) )
			{
				$mnf = array('mnf_id'=>$data['mnf_id'], 'mnfName'=>$data['mnfName'], 'mnfCountry'=>$data['mnfCountry']);
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MNF', $mnf, 2);
			}
			
			if ($features) {
				if ($item->models) {
					// display item models and models features
					$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS', array('i_id'=>$item->ID), 3);
					foreach ($item->models as $model) {
						$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS.MODEL', $model->toArray(), 3);
						if ($this->_admin) {
							$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS.MODEL.MODEL_ADMIN', array('i_id'=>$model->iID, 'id'=>$model->ID), 4);
						}
						if ($price && $model->price>0 && !$item->hasFakePrice) {
			          		$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS.MODEL.MODEL_PRICE', array('price'=>$this->_formatPrice($model->price)), 4);
			        	}
						if ($model->img) {
			          		$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS.MODEL.MODEL_IMG', $this->_modelImgData($model), 4);
			        	}
						if (!empty($model->features)) {
							$this->_te->assignBlockFromArray('ITEMS_TWOCOL.ITEM.MODELS.MODEL.MODEL_ANN.MODEL_ANN_FT', $model->features, 5);
			        	}
						if ($compare) {
							$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MODELS.MODEL.MODEL_COMPARE', array('id' => $model->ID), 4);
						} 
					}
				} else {
			      	// display item announced features if exists
			      	if (!empty($item->features)) {
						$this->_te->assignBlockFromArray('ITEMS_TWOCOL.ITEM.ANN.ANN_FT', $item->features, 3);
			      	}
					// switch on compare if items has features (may be not announced)
					if ($compare && $item->hasFeatures) {
						$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.COMPARE', array('id'=>$item->ID), 3);
					}
				}
			} 
			// display item price
			if ($price && !$item->models && $item->price>0 && !$item->hasFakePrice) {
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.PRICE', array('price'=>$this->_formatPrice($item->price)), 3);
			}
		}
		
		if (!empty($this->_conf['priceDownl']) && $this->_conf('ishop'))
	  	{
	    	$this->_te->assignBlockVars('TS_PRICE_DOWNL');
	  	}

	}

	/**
	 * Return model's image data 
	 *
	 * @param  object  $model
	 * @return array
	 **/
	function _modelImgData($model) {
		if ($model->img) {
			return array( 
					'm_id'   => $model->ID, 
					'tmb'    => EL_BASE_URL.dirname($model->img).'/mini_'.basename($model->img), 
					'target' => EL_BASE_URL.$model->img
					);
		}
		return null;
	}
	
	/**
	 * Return formatted price
	 *
	 * @param  float    $pr    price
	 * @param  boolean  $sign  append currency sign?
	 * @return string
	 **/
	function _formatPrice( $pr, $sign=false )
	{
		return 0 < $pr
			? number_format(round($pr, $this->_pricePrec), $this->_pricePrec, $this->_currInfo['decPoint'], $this->_currInfo['thousandsSep']).' '.($sign?$this->_currInfo['currencySign']:'')
			: '';
	}

}

?>