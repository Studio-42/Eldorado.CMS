<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndTechShop extends elCatalogRenderer
{
	var $_tpls      = array(
		'item'     => 'item.html',
		'model'    => 'model.html',
		'mnfs'     => 'manufacts.html',
		'mnf'      => 'manufact.html',
		'cmp'      => 'compare.html'
		);
	var $_admTpls    = array(
	  'item'     => 'adminItem.html',
	  'ftGroups' => 'adminFtGroups.html',
	  'mnfs'     => 'adminManufacts.html',
	  );

	var $_currInfo  = array();
	var $_pricePrec = 0;
	var $_cartOn    = false;


	function setCurrencyInfo( $currencyInfo, $pricePrec )
	{
	  $this->_currInfo = $currencyInfo;
	  $this->_te->assignVars( 'currency',     $this->_currInfo['currency'] );
	  $this->_te->assignVars( 'currencySign', $this->_currInfo['currencySign'] );
	  $this->_te->assignVars( 'currencyName', $this->_currInfo['currencyName'] );
	  $this->_pricePrec = $pricePrec;
	}

	function switchCartOn()
	{
	  $this->_cartOn = true;
	}


	function renderItem( $item, $linkedObjs=null )
	{
	  	$this->_setFile('item');
	  	$this->_te->assignVars( $item->toArray() );
		if ($this->_conf('displayManufact') && !empty($item->mnfName) )
		{
			$this->_te->assignBlockVars('ITEM_MNF', 
				array('mnf_id'=>$item->mnfID, 'mnfName'=>$item->mnfName, 'mnfCountry'=>$item->mnfCountry));
		}
	  	$models   = $item->getModels(); //elPrintR($models);
	  	$models   = array_chunk($models, $this->_conf('modelsInRow')); //elPrintR($models);
	  	$ftGroups = $item->getFt();
		elAddJs('jquery-ui.js', EL_JS_CSS_FILE);
		elAddCss('ui-themes/Blitzer/ui.all.css', EL_JS_CSS_FILE);
	  
		if ( !empty($models[0]) )
	  	{
			$this->_te->assignBlockVars('TS_TABS.TS_TAB_MODELS', null, 1);
			$this->_te->assignBlockVars('TS_TABS_JS', null, 1);
		    for ( $i=0, $s = sizeof($models); $i<$s; $i++ )
		    { 
   				$useDes = $useImg = false;
				$this->_te->assignBlockVars('MODELS');
				$cellWidth = floor(100/(sizeof($models[$i])+1)); 
				foreach ($models[$i] as $one)
				{ 
				  	$vars = array('id' => $one->ID, 'iID'=>$one->iID, 'code' =>$one->code, 'name' => $one->name, 'cellWidth'=>$cellWidth);
				  	$this->_te->assignBlockVars('MODELS.M_NAME', $vars, 1);

					if (!$useDes && !empty($one->descrip))
				  	{
				    	$useDes = true;
				  	}
				  	if (!$useImg && !empty($one->img) )
				  	{
				    	$useImg = true;
				  	}
		      	}

	      		if ( !empty($this->_currInfo)  )
	      		{ 
	        		foreach ($models[$i] as $one)
	        		{
	          			$this->_te->assignBlockVars('MODELS.M_PRICE_ROW.M_PRICE', array('price'=>$this->_formatPrice($one->price)), 2);
			  			if ( $one->price > 0 )
			  			{
							if ( 2 == $this->_conf['eshopFunc'] )
							{
								$this->_te->assignBlockVars('MODELS.M_PRICE_ROW.M_PRICE.M_ORDER', array('ID'=>$one->ID, 'iID'=>$one->iID), 3 );
							}
			  			}
	        		}
	      		}

	      		// display models description if at least one has it
	      		if ($useDes)
	      		{
	        		foreach ($models[$i] as $one)
	        		{
	          			$vars = array('descrip' => $one->descrip);
			          	$this->_te->assignBlockVars('MODELS.M_DESCRIP_ROW.M_DESCRIP', $vars, 2);
	        		}
	      		}
	      		// display models image if at least one has it
	      		if ($useImg)
	      		{
	        		foreach ($models[$i] as $one)
	        		{
	          			$this->_te->assignBlockVars('MODELS.M_IMG_ROW.M_IMG_CELL', null, 2);
	          			if (!empty($one->img))
	          			{
	            			$vars = array('img' => basename($one->img), 'path'=>dirname($one->img), 'm_id'=>$one->ID);
	            			$this->_te->assignBlockVars('MODELS.M_IMG_ROW.M_IMG_CELL.M_IMG', $vars, 3);
	          			}
	        		}
	      		}

	      		if (empty($ftGroups))
	      		{
	        		continue;
	      		}
	      		//display models features
	      		$colspan = sizeof($models[$i])+1;
	      		$this->_te->assignBlockVars('MODELS.M_FTS', array('colspan'=>$colspan), 1);
	      		foreach ($ftGroups as $gr)
	      		{
	        		$vars = array('name'=>$gr->name, 'colspan'=>$colspan);
	        		$this->_te->assignBlockVars('MODELS.M_FTS.M_FTG', $vars, 2);
	        		$fts = $gr->getFeatures(); 
	        		foreach ($fts as $ft)
	        		{
	          			$this->_te->assignBlockVars('MODELS.M_FTS.M_FTG.M_FT_ROW', $ft->toArray(), 3);
			  
	          			if ($ft->isSplit)
	          			{
	            			$one = $models[$i][0]; 
	            			$value = $ft->getModelValue($one->ID);
	            			if ( empty($value) )
	            			{
	              				$modIDs = array_keys($item->getModels());
	              				foreach ($modIDs as $modID)
	              				{
	                				if ( false != ($value = $ft->getModelValue($modID)))
	                				{
	                  					break;
	                				}
	              				}
	            			}

	            			$vars = array('value' => str_replace('\n', '<br />', $value), 'attrs'=>' colspan="'.($colspan-1).'" align="center"');
	            			$this->_te->assignBlockVars('MODELS.M_FTS.M_FTG.M_FT_ROW.M_FT', $vars, 4);
	            			continue;
	          			}

	          			foreach ($models[$i] as $one)
	          			{
	            			$vars = array('value' => $ft->getModelValue($one->ID) );
	            			$this->_te->assignBlockVars('MODELS.M_FTS.M_FTG.M_FT_ROW.M_FT', $vars, 4);
	          			}
	        		}
	      		}
	    	}
	  	}
	  	else
	  	{
			
	    	if ( !empty($this->_currInfo) && $item->price > 0)
	    	{
				$this->_te->assignBlockVars('ITEM_PRICE', array('price'=>$this->_formatPrice($item->price)));
				$this->_te->assignBlockVars('IS_ITEM_ORDER', array('id'=>$item->ID));
	    	}
	    	if ( false != ($ftGroups = $item->getFt()) )
	    	{
				$this->_te->assignBlockVars('TS_TABS.TS_TAB_FT', null, 1);
				$this->_te->assignBlockVars('TS_TABS_JS', null, 1);
	      		$this->_te->assignBlockVars('ITEM_FTS');
	      		foreach ( $ftGroups as $group )
	      		{
	        		$this->_te->assignBlockVars('ITEM_FTS.IFTS_GROUP', $group->toArray(), 1);
	        		foreach ($group->features as $ft)
	        		{
	          			$val = $ft->toArray();
	          			$val['value'] = $ft->getItemValue($item->ID);
	          			$this->_te->assignBlockVars('ITEM_FTS.IFTS_GROUP.IFTS_FT', $val, 2);
	        		}
	      		}
	    	}
	  	}
		$this->_rndLinkedObjs($linkedObjs);
	}

	function rndModel($model)
	{
	  $this->_setFile('model');
	  $data = array('id' => $model->ID, 'code' => $model->code, 'name' => $model->name, 'img' => $model->img, 'descrip' => $model->descrip);
	  if ($model->img && file_exists('.'.$model->img) && false != ($s = getimagesize('.'.$model->img)) )
	  {
	    $data['w'] = $s[0];
	    $data['h'] = $s[1];
	    $resizeX = $s[0] + 50;
	    $resizeY = empty($model->descrip) ? $s[1] + 100 : $s[1] + 300;
	    elAddJs("window.resizeTo(".$resizeX.", ".$resizeY.");", EL_JS_SRC_ONLOAD);
	  }
	  else
	  {
	    $data['img'] = EL_BASE_URL.'/style/images/pixel.gif';
	    $data['w']   = $data['h'] = 200;
	  }
	  $this->_te->assignVars( $data );
	}

	function rndManufacturers($mnfsList)
	{
	  $this->_setFile('mnfs');
	  foreach ($mnfsList as $mnf)
	  {
	    $this->_te->assignBlockVars( 'MNF', $mnf->toArray() );
	  }
	}

	function rndManufacturer($mnf)
	{
	  $this->_setFile('mnf');
	  $this->_te->assignVars( $mnf->toArray() );
	}

	function rndManufacturerItems($mnf, $items)
	{
	  $this->_setFile();
	  $this->_te->assignVars($mnf->toArray());
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

	function rndCompare( $items, $ftgs )
	{
	  $this->_setFile('cmp');
	  $this->_te->assignVars('colspan', sizeof($items)+1);
	  foreach ($items as $one)
	  {
	    $imgVars = array();
	    $vars    = array('id' => $one['id'], 'code' =>$one['code'], 'name' => $one['name']);
	    if ($one['isItem'])
	    {
	      $vars['url']       = EL_URL.'item/'.$one['c_id'].'/'.$one['id'].'/';
	      $vars['inputName'] = 'i';
	    }
	    else
	    {
	      $vars['url']       = EL_URL.'item/'.$one['c_id'].'/'.$one['i_id'].'/';
	      $vars['inputName'] = 'm';
	      if ( $one['img'])
	      {
	        $imgVars = array('tmb'=>dirname($one['img']).'/mini_'.basename($one['img']), 'm_id'=>$one['id']);
	      }
	    }
	    $this->_te->assignBlockVars('CMP_OBJ', $vars);
	    if (!empty($imgVars))
	    {
	      $this->_te->assignBlockVars('CMP_OBJ.CMP_OBJ_IMG', $imgVars, 1);
	    }
	    $mnf = array('mnf'=>$one['mnf'], 'country'=>$one['country'], 'mnf_id'=>$one['mnf_id']);
	    $this->_te->assignBlockVars('CMP_OBJ_MNF', $mnf);
	    if ($this->_currInfo)
	    {
	      $this->_te->assignBlockVars('CMP_OBJ_PRICES_ROW.CMP_OBJ_PRICE', array('price'=>$this->_formatPrice($one['price'])), 1);
	    }
	  }

	  foreach ($ftgs as $ftg)
	  {
	    $this->_te->assignBlockVars('CMP_FTG', $ftg->toArray());
	    $fts = $ftg->getFeatures();
	    foreach ( $fts as $ft)
	    {
	      $this->_te->assignBlockVars('CMP_FTG.FT_ROW', $ft->toArray(), 1);
	      foreach ( $items as $one )
	      {
	        $v = $one['isItem'] ? $ft->getItemValue($one['id']) : $ft->getModelValue($one['id']);
	        $this->_te->assignBlockVars('CMP_FTG.FT_ROW.FT', array('value'=>$v), 2);
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
	  	$i = 0;
		foreach ($items as $item)
		{
			$vars = $item->toArray(); 
			$vars['cssRowClass'] = ($i++)%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM', $vars, 1);

			if ($this->_conf('displayManufact') && !empty($vars['mnfName']) )
			{
				$mnf = array('mnf_id'=>$vars['mnf_id'], 'mnfName'=>$vars['mnfName'], 'mnfCountry'=>$vars['mnfCountry']);
				$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_MNF', $mnf, 2);
			}

		  	if ( false != ($models = $item->getModels()) )
		  	{
		    	$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_MOD_ROW', array('i_id'=>$item->ID), 2);
		    	foreach ($models as $model)
		    	{
		      		$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_MOD_ROW.OI_MODEL', $model->toArray(), 3);
		      		if (!empty($this->_currInfo) && $model->price>0 )
		      		{
		        		$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_MOD_ROW.OI_MODEL.OI_MODEL_PRICE',
		        			array('price'=>$this->_formatPrice($model->price)), 4);
		      		}
		      		if ($model->img)
		      		{
		        		$vars = array( 'm_id'=>$model->ID, 'tmb'=>dirname($model->img).'/mini_'.basename($model->img) );
		        		$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_MOD_ROW.OI_MODEL.OI_MODEL_IMG', $vars, 4);
		      		}
		      		if (!empty($item->ft[$model->ID]))
		      		{
						$this->_te->assignBlockFromArray('ITEMS_ONECOL.O_ITEM.OI_MOD_ROW.OI_MODEL.OIM_ANN.OIM_ANN_FT', $item->ft[$model->ID], 5);
		      		}
		    	}
		  	}
		  	else
		  	{
		    	$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.O_ITEM_COMP', array('id'=>$item->ID), 2);
		    	if ( !empty($this->_currInfo) && $item->price>0 )
		    	{
		      		$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OI_PRICE', array('price'=>$this->_formatPrice($item->price)), 2);
		    	}
		    	// display item announced features if exists
		    	if (!empty($item->ft))
		    	{
					$this->_te->assignBlockFromArray('ITEMS_ONECOL.O_ITEM.OI_ANN.OI_ANN_FT', $item->ft, 3);
		    	}
		  	}
		}
		if (!empty($this->_currInfo))
		{
		  $this->_te->assignBlockVars('TS_PRICE_DOWNL');
		}
	}

	/**
	 * Рисует список документов в две колонки
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsTwoColumns($items)
	{
		$rowCnt = $i = 1;
		foreach ($items as $item)
		{
			$cssLastClass = 'col-last';
			if ( ($i++)%2  )
			{
				$cssLastClass = '';
				$cssRowClass  = $rowCnt++%2 ? 'strip-ev' : 'strip-odd';
				$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW', array('cssRowClass'=>$cssRowClass), 1);
			}
			$vars = $item->toArray();
			$vars['cssRowClass']  = $cssRowClass;
			$vars['cssLastClass'] = $cssLastClass;
			$this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM', $vars, 2 );
			if ($this->_conf('displayManufact') && !empty($vars['mnfName']) )
			{
				$mnf = array('mnf_id'=>$vars['mnf_id'], 'mnfName'=>$vars['mnfName'], 'mnfCountry'=>$vars['mnfCountry']);
				$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_MNF', $mnf, 3);
			}
			if ( false != ($models = $item->getModels()))
		    {
		      	$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_MOD_ROW', array('i_id'=>$item->ID), 3);
		      	foreach ($models as $model)
		      	{
		        	$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_MOD_ROW.TI_MODEL', $model->toArray(), 4);
		        	if (!empty($this->_currInfo) && $model->price>0)
		        	{
		          		$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_MOD_ROW.TI_MODEL.TI_MODEL_PRICE',
		          			array('price'=>$this->_formatPrice($model->price)), 5);
		        	}
		        	if ($model->img)
		        	{
		          		$vars = array( 'm_id'=>$model->ID, 'tmb'=>dirname($model->img).'/mini_'.basename($model->img) );
		          		$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_MOD_ROW.TI_MODEL.TI_MODEL_IMG', $vars, 5);
		        	}
		        	if (!empty($item->ft[$model->ID]))
		        	{
						$this->_te->assignBlockFromArray('ITEMS_TWOCOL.IROW.T_ITEM.TI_MOD_ROW.TI_MODEL.TIM_ANN.TIM_ANN_FT', $item->ft[$model->ID], 6);
		        	}
		      	}
		    }
			else
		    {
		      	$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.T_ITEM_COMP', array('id'=>$item->ID), 3);
		      	if (!empty($this->_currInfo) && $item->price>0)
		      	{
		        	$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TI_PRICE', array('price'=>$this->_formatPrice($item->price)), 3);
		      	}
		      	// display item announced features if exists
		      	if (!empty($item->ft))
		      	{
					$this->_te->assignBlockFromArray('ITEMS_TWOCOL.IROW.T_ITEM.TI_ANN.TI_ANN_FT', $item->ft, 4);
		      	}
		    }
		}
		if (!empty($this->_currInfo))
	  	{
	    	$this->_te->assignBlockVars('TS_PRICE_DOWNL');
	  	}
		
	}

	function _formatPrice( $pr )
	{
	  return 0 < $pr
	  ? number_format(round($pr, $this->_pricePrec), $this->_pricePrec, $this->_currInfo['decPoint'], $this->_currInfo['thousandsSep'])
	  : '';
	}

}

?>