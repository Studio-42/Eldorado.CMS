<?php
set_time_limit(0);

class elModuleAdminImageGalleries extends elModuleImageGalleries
{
  var $_mMapAdmin  = array(
    'edit'   => array( 'm' => 'galleryEdit',       'g' => 'Actions', 'l' => 'New gallery', 'ico'=>'icoAlbumNew'),
    'rm'     => array( 'm' => 'galleryRm'),
    'clean'  => array( 'm' => 'galleryClean'),
    'i_edit' => array( 'm' => 'imageEdit',         'g' => 'Actions', 'l' => 'New image', 'ico'=>'icoImgNew'),
    'i_rm'   => array( 'm' => 'imageRm'),
    'sort'   => array( 'm' => 'galleriesSort',     'g' => 'Actions', 'l' => 'Forced sorting galleries', 'ico'=>'icoSortAlphabet' ),
    'i_sort' => array( 'm' => 'imagesSort',        'g' => 'Actions', 'l' => 'Forced sorting images in current gallery' , 'ico'=>'icoSort'),
    'import' => array( 'm' => 'importFromArchive', 'g' => 'Actions', 'l' => 'Import images from archive', 'ico'=>'icoImgImport' )
    );

  
  
  /**
   * Создает/редактирует объект "Галерея изображений"
   *
   */
	function galleryEdit()
	{
		$gallery = $this->_factory->gallery($this->_gID);
		if (!$gallery->editAndSave() )
		{
			$this->_initRenderer();
			return $this->_rnd->addToContent( $gallery->formToHtml() );
		}
		elMsgBox::put( m('Data saved'));
		elLocation(EL_URL);
	}

	/**
	* Удаляет пустую галерею
	*
	*/
	function galleryRm()
	{
		$g = $this->_factory->gallery($this->_gID);
		if (!$g->ID)
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',	array($g->getObjName(), $this->_arg(1)), EL_URL );
		}
		if ($g->countImages())
		{
			elThrow(E_USER_WARNING, 'Non empty object "%s" "%s" can not be deleted',  array($g->getObjName(), $g->name), EL_URL);
		}
		$g->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $g->getObjName(), $g->name) );
		ellocation(EL_URL);
	}

  /**
	 * Удаляет все изображения их галереи
	 *
	 */
	function galleryClean()
	{
		$g = $this->_factory->gallery((int)$this->_arg());
		if (!$g->ID)
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',	array($g->getObjName(), $this->_arg(1)), EL_URL );
		}
		$g->clean($this->_factory->image());
		elMsgBox::put( sprintf(m('All images were removed from gallery "%s"'), $g->name) );
		elLocation(EL_URL);
	}

  /**
	 * Create/edit image object
	 *
	 */
	function imageEdit()
	{
		$i = & elSingleton::getObj('elImage');
		if (!$i->allowResize())
		{
			elThrow(E_USER_WARNING, 'There are no one image manipulation libraries available! We need access to GD, Imagick or mogrify.', null, EL_URL);
		}
		$img = $this->_factory->image((int)$this->_arg(1)); 
		$params = array(
			'parents' => $this->_galleries,
			'gID'     => $this->_gID,
			'rename'  => $this->_conf('imgUniqNames'),
			'wm'      => $this->_conf('watermark'),
			'wmpos'   => $this->_conf('watermarkPos'),
			'tmbSize' => $this->_conf('tmbMaxSize'),
			'crop'    => $this->_conf('tmbCrop')
			 );
		if (!$img->editAndSave($params) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $img->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.$this->_gID);
		}
	}

  /**
	 * Удаляет одно изображение (файл с диска и запись из ДБ)
	 *
	 */
	function imageRm()
	{
		$img = $this->_factory->image((int)$this->_arg(1));
		if ( !$img->ID )
	    {
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',	array($img->getObjName(), $this->_arg(1)), EL_URL );
	    }
		$img->delete();
	    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $img->getObjName(), $img->name.' '.$img->file) );
	    ellocation(EL_URL.$this->_gID);
	}


  /**
	 * Задает порядок сортировки галерей
	 * Галереи с индексом >0 сортируются по индексу,
	 * остальные - по полю указаному в настройках модуля
	 *
	 * @return viod
	 */
	function galleriesSort()
	{
		$gallery = $this->_factory->gallery();
		$list = $this->_factory->galleriesSortList(); 
		$form = & elSingleton::getObj('elForm');
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
		$form->setLabel( m('Forced sorting galleries') );
		$form->add( new elCData('c', m('Set sorting indexes to place galleries in require order')));

		foreach ($list as $g)
		{
		  $form->add(new elText('g_sort_ndx['.$g['g_id'].']', $g['g_name'], $g['g_sort_ndx'], array('size'=>7)));
		}
		if (!$form->isSubmitAndValid())
		{
			$this->_initRenderer();
			return $this->_rnd->addToContent($form->toHtml());
		}
		$sort = $form->getValue();
		$sort = $sort['g_sort_ndx'];
		$db   = & $this->getDb();
		$sql  = 'UPDATE '.$this->_tbG.' SET g_sort_ndx=%d WHERE g_id=%d';
		foreach ( $sort as $id=>$ndx )
		{
			$db->query(sprintf($sql, $ndx, $id));
		}
		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL);
	}

  /**
	 * Задает порядок сортировки изображений в галерее
	 * Картинки с индексом >0 сортируются по индексу,
	 * остальные - по полю указаному в настройках модуля
	 *
	 * @return viod
	 */
	function imagesSort()
	{
		if (!$this->_gID)
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',	array('Image gallery', 0), EL_URL);
		}
		$content = $this->_factory->galleryContent($this->_gID); 
		if (empty($content['images']))
		{
			elThrow(E_USER_WARNING, 'There are no images in this gallery', null, EL_URL);
		}
		
		$form = & elSingleton::getObj('elForm');
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
		$form->setLabel(m('Forced sorting images in current gallery') );
		$form->add( new elCData('c', m('Set sorting indexes to place images in require order')) );
		$images = $content['images'];
		foreach ($images as $i)
		{
			$l = '<img src="'.EL_IG_URL.$this->pageID.'/tmb/'.$i['i_file'].'" width="'.$i['i_width_tmb'].'" height="'.$i['i_height_tmb'].'" />';
			$form->add( new elText('i_sort_ndx['.$i['i_id'].']', $l, $i['i_sort_ndx'], array('size'=>7)));
			
		}
		
		if ( !$form->isSubmitAndValid() )
		{
			$this->_initRenderer();
			return $this->_rnd->addToContent( $form->toHtml() );
		}
		$sort = $form->getValue();
		$sort = $sort['i_sort_ndx'];
		$db   = & $this->getDb();
		$sql  = 'UPDATE '.$this->_tbI.' SET i_sort_ndx=%d WHERE i_id=%d';
		foreach ($sort as $id=>$ndx)
		{
			$db->query(sprintf($sql, $ndx, $id));
		}

		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL.$this->_gID);
	}


  /**
   * Загружает изображения из архива в галерею
   *
   */
  function importfromArchive()
  {
    $ark   = & elSingleton::getObj('elArk');
    $mimes = $ark->getMimesExtract();
    $form  = & $this->_makeImportForm( $mimes );

    if ( !$form->isSubmitAndValid() )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $form->toHtml() );
    }
    else
    {
      $val     = $form->getElementValue('img_arc');
      $galID   = (int)$form->getElementValue('i_gal_id');
      $dir     = EL_IG_DIR.$this->pageID.DIRECTORY_SEPARATOR;
      $dirTmp  = EL_DIR_TMP.str_replace(' ', '_', microtime()).DIRECTORY_SEPARATOR;
      $arcFile = $val['name'];
      $arcPath = $dirTmp.$arcFile;
      $ok      = array();
      $failed  = array();

      
      // временная дир
      if ( !elFS::mkdir($dirTmp) )
      {
        elThrow(E_USER_WARNING, 'Could not create temporary dir for extracting files', null, EL_URL);
      }
      // копируем архив во временную папку
      $uploader = & $form->get('img_arc');
      if ( !$uploader->moveUploaded(null, $dirTmp) )
      {
        @elFS::rmdir($dirTmp);
        elThrow(E_USER_WARNING, 'Error uploading file "%s"! See more info in debug or error log.', $arcFile, EL_URL);
      }
      // распаковываем
      if (!$ark->extract($arcPath, $dirTmp))
      {
        @elFS::rmdir($dirTmp);
        elThrow(E_USER_WARNING, $ark->getError(), $arcFile, EL_URL);
      }
      // удаляем архив
      @unlink($arcPath);

      // копируем картинки из временной дир в дир галереи, масштабируем под все размеры и создаем запись в ДБ
		$imgs = elFS::ls($dirTmp, EL_FS_ONLY_FILES);
		// elPrintR($imgs); 
		if ( !$imgs )
		{
			@elFS::rmdir($dirTmp);
			elThrow(E_USER_WARNING, 'Empty archive! Import interrapted!', null, E_URL);
		}
		$image = $this->_factory->image();
		$image->galID = $galID;
		foreach ($imgs as $file)
		{
			if (false == ($s = getimagesize($dirTmp.$file)))
			{
				$failed[] = $file;
				continue;
			}
			if (!elFS::copy($dirTmp.$file, $dir.'original'))
			{
				$failed[] = $file;
				continue;
			}
			$filename = $file;
			if ($this->_conf('imgUniqNames'))
			{
				$p = strrpos($file, '.');
				$filename = md5(microtime()).'.'.substr($file, $p+1);
				if (!rename($dir.'original'.DIRECTORY_SEPARATOR.$file, $dir.'original'.DIRECTORY_SEPARATOR.$filename))
				{
					$filename = $file;
				}
			}
			
			if (!$image->setImgFile($dir.'original'.DIRECTORY_SEPARATOR.$filename, $this->_conf('watermark'), $this->_conf('watermarkPos'), $this->_conf('tmbMaxSize'), $this->_conf('tmbCrop')))
			{
				$failed[] = $file;
				continue;
			}
			$image->save();
			$image->idAttr(0);
			$ok[] = $file;
		}
		// remove tmp dir
	      @elFS::rmdir($dirTmp);

	      elMsgBox::put( m('Images import results'));
	      elMsgBox::put( sprintf(m('Extracted files from archive: %d'), sizeof($imgs)));
	      if ( $ok )
	      {
	        elMsgBox::put( sprintf(m('Imported images: %d (%s)'), sizeof($ok), implode(', ', $ok)) );
	      }
	      if ( $failed )
	      {
	        elMsgBox::put( sprintf(m('Failed imported images: %d (%s)'), sizeof($failed), implode(', ', $failed)) );
	      }
	      elLocation(EL_URL.$galID);
	}
  }


  

  //********************************************//
  //							PRIVATE METHODS								//
  //********************************************//

	function _onInit()
	{
		parent::_onInit();

		if ( empty($this->_galleries) )
		{
			unset($this->_mMap['sort']);
			unset($this->_mMap['i_sort']);
			unset($this->_mMap['import']);
		}
		elseif ( $this->_gID )
		{
			$this->_mMap['i_edit']['apUrl'] = $this->_gID;	
			$this->_mMap['i_sort']['apUrl'] = $this->_gID;
		}
		else
		{
			unset($this->_mMap['i_sort']);
		}

		if ( !empty($this->_mMap['import']) )
		{
			$ark   = & elSingleton::getObj('elArk');
			if (false == ($mimes = $ark->getMimesExtract()))
			{
				unset($this->_mMap['import']);
			}
		}
		$dir = EL_IG_DIR.$this->pageID.DIRECTORY_SEPARATOR;
		!is_dir($dir.'tmb') && elFS::mkdir($dir.'tmb');
		!is_dir($dir.'original') && elFS::mkdir($dir.'original');		

	}


  function &_makeImportForm($arcExt)
  {
    $form    = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $form->setLabel( m('Import images from archive') );
    $comment = sprintf(m('You can import images from following types of archives: %s'), implode(', ', $arcExt));
    $form->add( new elCData('c1', $comment) );
    $form->add( new elSelect('i_gal_id', m('Gallery'), null, $this->_galleries) );
    $file = & new elFileInput('img_arc', m('Images archive')); 
    // we love Mac :)
    $arcExt[] = 'tgz';
    $arcExt[] = 'tbz';
    $file->setFileExt( $arcExt );
    $form->add($file);
    $form->setRequired('img_arc');
    return $form;
  }


  function &_makeConfForm()
  {
    $form     = & parent::_makeConfForm();
    $sortOpts = array(
      EL_IG_SORT_NAME => m('By title'),
      EL_IG_SORT_TIME => m('By publishing time')
      );
    $tmbSizes = array(50=>'50x50', 75=>'75x75', 100=>'100x100', 125=>'125x125', 150=>'150x150', 175=>'175x175', 200=>'200x200', 225=>'225x225', 250=>'275x275', 300=>'300x300');
    // $dm = array(EL_IG_DISPL_POPUP    => m('Popup window'),  EL_IG_DISPL_LIGHTBOX => m('LightBox'));
    // $form->add( new elSelect('displayMethod', m('Display full-size image using'), $this->_conf('displayMethod'), $dm) );
    $form->add( new elSelect('tmbMaxSize',   m('Thumbnails max size (px)'),   $this->_conf('tmbMaxSize'), $tmbSizes) );
    $form->add( new elSelect('tmbCrop',      m('Thumbnails'),        (int)$this->_conf('tmbCrop'), array(m('Store proportions'), m('Crop square'))) );
    $form->add( new elSelect('imgUniqNames', m('Create unique names for images files'), $this->_conf('imgUniqNames'), $GLOBALS['yn']) );
    $form->add( new elSelect('gSort',        m('Galleries display order'),    $this->_conf('gSort'),      $sortOpts) );
    $form->add( new elSelect('iSort',        m('Images display order'),       $this->_conf('iSort'),      $sortOpts) );

    $form->add( new elCData('c1', m('Galleries list appearance')), array('cellAttrs'=>' class="form-tb-sub"'));
    $form->add( new elSelect('tmbNumInGalList', m('Thumbnails number in galleries list'),  $this->_conf('tmbNumInGalList'), range(1,15), null, false, false) );
    $form->add( new elSelect('displayGalDate', m('Display gallery publish date'),   $this->_conf('displayGalDate'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayGalImgNum', m('Display numbers of images in gallery'),  $this->_conf('displayGalImgNum'), $GLOBALS['yn']) );

    $form->add( new elCData('c2', m('Gallery appearance')), array('cellAttrs'=>' class="form-tb-sub"'));
    $form->add( new elSelect('tmbNumPerPage', m('Thumbnails number per page'),   $this->_conf('tmbNumPerPage'), range(10, 120), null, false, false) );
    $form->add( new elSelect('displayImgSize', m('Display image size'),    $this->_conf('displayImgSize'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayImgDate', m('Display image publishing date'),   $this->_conf('displayImgDate'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayFileName', m('Display image file name'),   $this->_conf('displayFileName'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayFileSize', m('Display image file size'),   $this->_conf('displayFileSize'), $GLOBALS['yn']) );
    
    $form->add( new elCData('c3', m('Watermark')), array('cellAttrs'=>' class="form-tb-sub"'));
    
    if ( !function_exists('imagejpeg') )
    {
      $form->add( new elCData('c4', m('Could not use watermark because of GD2 library does not installed!')) );
    }
    else
    {
      $wm  = $this->_conf('watermark');
      $src = is_file(EL_IG_DIR.$this->pageID.DIRECTORY_SEPARATOR.'wm'.DIRECTORY_SEPARATOR.$wm) 
		? EL_BASE_URL.'/storage/galleries/'.$this->pageID.'/wm/'.$wm 
		: '';
      $this->_im = & new elImageUploader('wm', m('Watermark image'), $src) ;
      $this->_im->fileExt = array('gif', 'png');
      $form->add( $this->_im );
      $form->add( new elSelect('watermarkPos', m('Watermark position'), $this->_conf('watermarkPos'), array_map('m', $this->_wmPos)) );
    }
    return $form;
  }

  function _validConfForm( &$form )
  {
    $values = $form->getValue();
    unset($values['wm']);
    $wm = & $form->get('wm'); //elPrintR($wm); return;
    $dir = EL_IG_DIR.$this->pageID.DIRECTORY_SEPARATOR.'wm';
    //elPrintR($values);
    if ( $this->_im->isUploaded() )
    {

      $ext = $this->_im->getExt();
      
      if ( !is_dir($dir) )
      {
        mkdir($dir);
      }
      if ( false  != ($wmFile = $this->_im->moveUploaded('wm.'.$ext, $dir.'/'))  )
      {
        $values['watermark'] = basename($wmFile);
      }
      else
      {
        elThrow( E_USER_WARNING, 'Can not upload file "%s"', 'watermark');
      }
    }
    elseif ( $this->_im->isMarkedToDelete() )
    {

      $wmFile = $dir.'/'.$this->_conf('watermark');
      
      if ( is_file($wmFile) && !unlink($wmFile) )
      {
        elThrow( E_USER_WARNING, 'Can not remove file "%s"', $wmFile );
      }
      else
      {
        $values['watermark'] = '';
      }
    }
    return $values;
  }
    
	function _updateConf( $newConf )
	{
		if ( $newConf['tmbMaxSize'] <= 0 )
		{
			$newConf['tmbMaxSize'] = 125;
		}
		if ($newConf['tmbMaxSize'] != $this->_conf('tmbMaxSize') || (int)$newConf['tmbCrop'] != (int)$this->_conf('tmbCrop'))
		{
			// echo $newConf['tmbCrop'].' '.$this->_conf('tmbCrop');
			$this->_updateTmbs((int)$newConf['tmbMaxSize'], $newConf['tmbCrop']);
		}
		parent::_updateConf($newConf);
	}

	function _updateTmbs($w)
	{
		$img = $this->_factory->image();
		$img->tmbMaxWidth = $w;
		$images = $img->collection(true);
		foreach ( $images as $i )
		{
			$i->updateTmb($w);
		}
	}


}

?>