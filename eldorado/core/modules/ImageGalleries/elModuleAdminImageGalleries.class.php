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
    'import' => array( 'm' => 'importFromArchive', 'g' => 'Actions', 'l' => 'Import images from archive', 'ico'=>'icoImgImport' ),
    'check'  => array( 'm' => 'checkAndRepaire',   'g' => 'Actions', 'l' => 'Find and fix errors', 'ico'=>'icoMagic' )
    );

  
  
  /**
   * Создает/редактирует объект "Галерея изображений"
   *
   */
  function galleryEdit()
  {
    $gallery = $this->_getGallery();
    if (!$gallery->editAndSave() )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $gallery->formToHtml() );
    }
    else
    {
      elMsgBox::put( m('Data saved'));
      elLocation(EL_URL);
    }
  }

  /**
   * Удаляет пустую галерею
   *
   */
  function galleryRm()
  {
    $gallery = $this->_getGallery();
    if ( $gallery->countImages() )
    {
      elThrow(E_USER_WARNING, 'Non empty object "%s" "%s" can not be deleted',
      array($gallery->getObjName(), $gallery->getAttr('g_name')), EL_URL);
    }
    $gallery->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $gallery->getObjName(), $gallery->getAttr('g_name')) );
    ellocation(EL_URL);
  }

  /**
	 * Удаляет все изображения их галереи
	 *
	 */
  function galleryClean()
  {
    $gallery = $this->_getGallery();
    $gallery->clean();
    elMsgBox::put( sprintf(m('All images were removed from gallery "%s"'), $gallery->getAttr('g_name')) );
    elLocation(EL_URL);
  }

  /**
	 * Create/edit image object
	 *
	 */
  function imageEdit()
  {
    $img = $this->_getImage();
    $params = array(
                    'gList'  => $this->_getGalsList(),
                    'rename' => $this->_conf('imgUniqNames'),
                    'gID'    => $this->_gID );
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
    $img = $this->_getImage();
    if ( !$img->ID )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',
      array($img->getObjName(),	$this->_arg(1)), EL_URL );
    }
    $img->delete();
    $imgName = $img->getAttr('i_name').' '.$img->getAttr('i_file_size');
    $msg = sprintf(m('Object "%s" "%s" was deleted'), $img->getObjName(), $imgName);
    elMsgBox::put( $msg );
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
    $form = & $this->_makeGalleriesSortForm();
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
      $db->safeQuery($sql, $ndx, $id);
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
    $gallery = $this->_getGallery();
    if ( !$gallery->countimages() )
    {
      elThrow(E_USER_WARNING, 'There are no images in this gallery', null, EL_URL);
    }

    $form = & $this->_makeImagesSortForm();
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
      $db->safeQuery($sql, $ndx, $id);
    }

    elMsgBox::put( m('Data saved') );
    elLocation(EL_URL.$gallery->ID);
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
      $dir     = $GLOBALS['igDir'];
      $dirTmp  = $dir.str_replace(' ', '_', microtime());
      $arcFile = $val['name'];
      $arcPath = $dirTmp.'/'.$arcFile;
      $ok      = array();
      $failed  = array();

      // можем распаковать?
      if (!$ark->canExtract($val['type'], true))
      {
        elThrow( E_USER_WARNING, 'File %s is not an archive file, or this archive type does not supported!',  $val['name'], EL_URL);
      }
      // временная дир
      if ( !mkdir($dirTmp) )
      {
        elThrow(E_USER_WARNING, 'Could not create temporary dir for extracting files', null, EL_URL);
      }
      // копируем архив во временную папку
      $uploader = & $form->get('img_arc');
      if ( !$uploader->moveUploaded(null, $dirTmp) )
      {
        @rmdir($dirTmp);
        elThrow(E_USER_WARNING, 'Error uploading file "%s"! See more info in debug or error log.', $arcFile, EL_URL);
      }
      // распаковываем
      if (!$ark->extract($arcPath, $dirTmp))
      {
        @exec( 'rm -rf '.escapeshellarg($dirTmp) );
        elThrow(E_USER_WARNING, $ark->getError(), $arcFile, EL_URL);
      }
      // удаляем архив
      @unlink($arcPath);

      // копируем картинки из временной дир в дир галереи, масштабируем под все размеры и создаем запись в ДБ
      $imgs = glob($dirTmp.'/*'); //elPrintR($imgs);
      if ( !$imgs )
      {
        @rmdir($dirTmp);
        elThrow(E_USER_WARNING, 'Empty archive! Import interrapted!', null, E_URL);
      }

      $imgObj = $this->_getImage();
      $imgObj->initImager();
      $rename = $this->_conf('imgUniqNames');
      foreach ( $imgs as $path )
      {
        $origFileName = basename($path);
        // а ты картинко?!
        if ( false == ( list(,,$ext) = $imgObj->imager->getInfo($path)) )
        {
          $failed[] = $origFileName;
          @unlink($path);
          elThrow(E_USER_WARNING, $imgObj->imager->getError());
          continue;
        }
        // захади дарагой!
        $fileName = $rename ? md5(microtime()).'.'.$ext : $origFileName;
        if (!copy($path, $dir.$fileName))
        {
          $failed[] = $origFileName;
          @unlink($path);
          elThrow(E_USER_WARNING, 'Could not copy %s to %s!', array($imgPath, $dir.$imgFile));
          continue;
        }
        //@chmod($dir.$fileName, 0664);
        @unlink($path);

        // пишем в ДБ и масштабируем картинко
        $imgObj->cleanAttrs();
        $imgObj->setAttr('i_file', '');
        $imgObj->setAttr('i_gal_id', $galID);
        if ( $imgObj->setImgFile($fileName) && $imgObj->save() )
        {
          $ok[] = $origFileName;
        }
        else
        {
          $failed[] = $origFileName;
        }
      }
      // remove tmp dir
      @exec( 'rm -rf '.escapeshellarg($dirTmp) );

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

  /**
   * Йа починко!
   * Удаляет записи в ДБ для несуществующих файлов
   * Для файлов без записи в БД - создает новую запись в галерее Lost and Found
   *
   */
  function checkAndRepaire()
  {
    $cntAdd      = $cntRm = $cntFix = 0;
    $imgObj      = $this->_getImage();
    $imgObj->initImager();
    $imager      = & $imgObj->imager;

    $db          = & $this->getDb();
    $sql         = 'SELECT DISTINCT i_file, CONCAT(\''.$GLOBALS['igDir'].'\', i_file) AS f FROM '.$this->_tbI;
    $filesInDb   = $db->queryToArray($sql, 'i_file', 'f'); //elPrintR($filesInDb);
    $filesOnDisk = glob($GLOBALS['igDir'].'*.*'); //elPrintR($filesOnDisk);
    $filesToAdd  = array_diff($filesOnDisk, $filesInDb); //elPrintR($filesToAdd);
    $filesToRm   = array_diff($filesInDb, $filesOnDisk); //elPrintR($filesToRm);
    $imgSizes = $GLOBALS['igImgSizes'];
    $imgSizes[0] = 'tmb';

    // remove db records which has no files
    // remove preview and tmb if exists
    if ( !empty($filesToRm) )
    {
      foreach ($filesToRm as $f=>$path)
      {
        foreach ($imgSizes as $size )
        {
          $path = $GLOBALS['igDir'].$size.'/'.$f;
          if ( is_file($path) && !@unlink($path) )
          {
            elThrow(E_USER_WARNING, 'Could not delete file "%s"', $path);
          }
        }
      }
      $db->query('DELETE FROM '.$this->_tbI.' WHERE i_file IN (\''.implode("','", array_keys($filesToRm)).'\')');
      $cntRm = $db->affectedRows();
      $db->optimizeTable($this->_tbI);
    }

    if ( $filesOnDisk )
    {
      $tmbSize = $this->_conf('tmbMaxSize');
      foreach ($filesOnDisk as $path)
      {
        $f = basename($path);
        foreach ( $imgSizes as $size )
        {
          //echo "$f=>$path<br>"; continue;
          if ( !file_exists($GLOBALS['igDir'].$size.'/'.$f) )
          {
            if ( $imager->makeTmb($GLOBALS['igDir'].$f, $size, $tmbSize, $tmbSize) )
            {
              $cntFix++;
            }
            else
            {
              elThrow(E_USER_WARNING, $imager->getError());
            }
          }
        }
      }
    }

    // all new files add to new gallery named "Lost and found"
    if ( !empty($filesToAdd) )
    {
      $gallery = $this->_getGallery();
      $db->query('SELECT g_id FROM '.$this->_tbG.' WHERE g_name=\'Lost and found\'');
      if ( !$db->numRows() )
      {
        $gallery->setAttr('g_name', m('Lost and found'));
        $gallery->save();
      }
      else
      {
        $r = $db->nextRecord();
        $gallery->setUniqAttr($r['g_id']);
      }

      foreach ( $filesToAdd as $path )
      {
        $imgObj->cleanAttrs();
        $imgObj->setAttr('i_gal_id', $gallery->ID);
        if ( $imgObj->setImgFile(basename($path)) && $imgObj->save() )
        {
          $cntAdd++;
        }
      }
    }



    $msg = sprintf(m('Records in DB with unexisted files was found and deleted: %d'), $cntRm);
    elmsgBox::put( $msg );
    $msg = sprintf(m('New files was found and added to gallery named "%s": %d'), m('Lost and found'), $cntAdd);
    elmsgBox::put( $msg );
    $msg = sprintf(m('Errors was found and fixed: %d'), $cntFix);
    elmsgBox::put( $msg );
    elLocation(EL_URL);

  }


  

  //********************************************//
  //							PRIVATE METHODS								//
  //********************************************//

  function _onInit()
  {
    parent::_onInit();

    if ( empty($this->_collection) )
    {
      unset($this->_mMap['sort']);
      unset($this->_mMap['i_edit']);
      unset($this->_mMap['i_sort']);
      unset($this->_mMap['import']);
    }
    elseif ( !$this->_gID )
    {
      unset($this->_mMap['i_sort']);
    }

    if ( !empty($this->_mMap['i_edit']))
    {
      $this->_mMap['i_edit']['apUrl'] = $this->_gID;
    }

    if ( !empty($this->_mMap['i_sort']))
    {
      $this->_mMap['i_sort']['apUrl'] = $this->_gID;
    }

    if ( !empty($this->_mMap['import']) )
    {
      $ark   = & elSingleton::getObj('elArk');
      if (false == ($mimes = $ark->getMimesExtract()))
      {
        unset($this->_mMap['import']);
      }
    }
  }

  function &_makeGalleriesSortForm()
  {
    $gallery = $this->_getGallery();
    $gList   = $gallery->getCollectionToArray('g_id,g_name,g_sort_ndx', null, $this->_getGSort());
    $form 	 = & elSingleton::getObj('elForm');

    $form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $form->setLabel( m('Forced sorting galleries') );
    $form->add( new elCData('c', m('Set sorting indexes to place galleries in require order')));

    foreach ($gList as $g)
    {
      $form->add(new elText('g_sort_ndx['.$g['g_id'].']', $g['g_name'], $g['g_sort_ndx'], array('size'=>7)));
    }
    return $form;
  }

  function &_makeImagesSortForm()
  {
    $gallery = $this->_collection[$this->_gID];
    $imgObj  = $this->_getImage();
    $f       = 'i_name, i_file, i_width_tmb, i_height_tmb, i_sort_ndx';
    $imgs    = $imgObj->getCollectionToArray($f, 'i_gal_id=\''.$this->_gID.'\'', $this->_getISort());
    $URL     = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/galleries/'.$this->pageID.'/tmb/';
    $form    = & elSingleton::getObj('elForm');

    $form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $form->setLabel( m('Forced sorting images in current gallery') );
    $form->add( new elCData('c', m('Set sorting indexes to place images in require order')) );

    foreach ($imgs as $img)
    {
      $l = $img['i_name'].'<br /><img src="'.$URL.$img['i_file'].'" width="'
      .$img['i_width_tmb'].'" height="'.$img['i_height_tmb'].'" />';
      $form->add( new elText('i_sort_ndx['.$img['i_id'].']', $l, $img['i_sort_ndx'], array('size'=>7)));
    }
    return $form;
  }

  function &_makeImportForm($arcExt)
  {
    $form    = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $form->setLabel( m('Import images from archive') );
    $comment = sprintf(m('You can import images from following types of archives: %s'), implode(', ', $arcExt));
    $form->add( new elCData('c1', $comment) );
    $form->add( new elSelect('i_gal_id', m('Gallery'), null, $this->_getGalsList()) );
    $file    = & new elFileInput('img_arc', m('Images archive')); //elPrintR($arcExt);
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
      EL_IG_SORT_TIME => m('By publishing time'),
      //EL_IG_SORT_NDX  => m('By sort index')
      );
    $tmbSizes = array(50=>'50x50', 75=>'75x75', 100=>'100x100', 125=>'125x125', 150=>'150x150', 175=>'175x175', 200=>'200x200', 225=>'225x225', 250=>'250x250');
    $dm = array(EL_IG_DISPL_POPUP    => m('Popup window'),  EL_IG_DISPL_LIGHTBOX => m('LightBox'));
    $form->add( new elSelect('displayMethod', m('Display full-size image using'), $this->_conf('displayMethod'), $dm) );
    $form->add( new elSelect('tmbMaxSize', m('Thumbnails max size (px)'),   $this->_conf('tmbMaxSize'), $tmbSizes) );
    $form->add( new elSelect('imgUniqNames', m('Create unique names for images files'), $this->_conf('imgUniqNames'), $GLOBALS['yn']) );
    $form->add( new elSelect('gSort',      m('Galleries display order'),    $this->_conf('gSort'),      $sortOpts) );
    $form->add( new elSelect('iSort',      m('Images display order'),       $this->_conf('iSort'),      $sortOpts) );

    $form->add( new elCData('c1', m('Galleries list appearance')), array('cellAttrs'=>'style="font-weight:bold"'));
    $form->add( new elSelect('tmbNumInGalList', m('Thumbnails number in galleries list'),
    $this->_conf('tmbNumInGalList'), range(1,15), null, false, false) );
    $form->add( new elSelect('displayGalDate', m('Display gallery publish date'),
    $this->_conf('displayGalDate'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayGalImgNum', m('Display numbers of images in gallery'),
    $this->_conf('displayGalImgNum'), $GLOBALS['yn']) );

    $form->add( new elCData('c2', m('Gallery appearance')), array('cellAttrs'=>'style="font-weight:bold"'));
    $form->add( new elSelect('tmbNumPerPage', m('Thumbnails number per page'),
    $this->_conf('tmbNumPerPage'), range(10, 120), null, false, false) );
    $form->add( new elSelect('tmbNumInRow', m('Thumbnails number in row'),
    $this->_conf('tmbNumInRow'), range(3, 20), null, false, false) );
    $form->add( new elSelect('displayImgSize', m('Display image size'),
    $this->_conf('displayImgSize'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayImgDate', m('Display image publishing date'),
    $this->_conf('displayImgDate'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayFileName', m('Display image file name'),
    $this->_conf('displayFileName'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayFileSize', m('Display image file size'),
    $this->_conf('displayFileSize'), $GLOBALS['yn']) );
    
    $form->add( new elCData('c3', m('Watermark')), array('cellAttrs'=>'style="font-weight:bold"'));
    
    if ( !function_exists('imagejpeg') )
    {
      $form->add( new elCData('c4', m('Could not use watermark because of GD2 library does not installed!')) );
    }
    else
    {
      $wm  = $this->_conf('watermark');
      $src = is_file('./storage/galleries/'.$this->pageID.'/wm/'.$wm) ? EL_BASE_URL.'/storage/galleries/'.$this->pageID.'/wm/'.$wm : '';
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
    $dir = './storage/galleries/'.$this->pageID.'/wm';
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
    if ( 0 >= $newConf['tmbMaxSize'] )
    {
      $newConf['tmbMaxSize'] = 125;
    }
    if ($newConf['tmbMaxSize'] != $this->_conf('tmbMaxSize') )
    {
      $this->_updateTmbs($newConf['tmbMaxSize']);
    }
    parent::_updateConf($newConf);
  }

  function _updateTmbs($w)
  {
    $imgObj   = $this->_getImage();
    $db       = &$this->getDb();
    $sql      = 'SELECT '.implode(',', $imgObj->listAttrs()).' FROM '.$this->_tbI;
    $imgsData = $db->queryToArray($sql, 'i_id');

    foreach ( $imgsData as $data )
    {
      $imgObj->setAttrs( $data );
      $imgObj->updateTmb($w);
      $imgObj->save();
    }
  }


  /**
	 * Return galleries list
	 *
	 * @return array
	 */
  function _getGalsList()
  {
    $ret = array();
    foreach ( $this->_collection as $id=>$g )
    {
      $ret[$id] = $g->name;
    }
    return $ret;
  }


}

?>