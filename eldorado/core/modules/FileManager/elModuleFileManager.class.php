<?php

elLoadMessages('FileManager');

class elModuleFileManager extends elModule
{
  var $root       = null;
  var $cwd        = null;
  var $_acl       = array();
  var $_ark       = null;
  var $_popupMode = false;
  var $_confID    = 'FileManager';
  var $_baseURL   = EL_URL;
  var $_icoPopup  = true;
  var $_panePopup = true;
  var $_prnt      = false;
  var $_sharedRndMembers = array('_icoPopup', '_panePopup');
  var $_mMap = array(
    'cd'      => array( 'm' => 'cd',        'l'=>'Go top', 'ico'=>'icoHome',  'g'=>'Actions'),
    'mkdir'   => array( 'm' => 'mkDir',     'l'=>'Create directory', 'ico'=>'icoCatNew', 'onClick'=>'return mkdirForm();', 'g'=>'Actions'),
    'upl'     => array( 'm' => 'upload',    'l'=>'Upload files', 'ico'=>'icoFileUpload', 'g' => 'Actions'),
    'archive' => array( 'm' => 'arkCreate', 'l'=>'Create archive', 'ico'=>'icoArc', 'g' => 'Actions'),
    'move'    => array( 'm' => 'move',      'l'=>'Copy/move/delete group of files', 'ico'=>'icoFileActions', 'g' => 'Actions'),
    'rm'      => array( 'm' => 'rm'),
    'rename'  => array( 'm' => 'renameFile'),
    'extract' => array( 'm' => 'arkExtract'),
    'clean'   => array( 'm' => 'cleanTmbs')
    );
  var $_conf = array(
    'numInRow'       => 5,
    'numInRowPopup'  => 4,
    'uploadFilesNum' => 5,
    'tmbW'           => 100,
    'tmbH'           => 100
    );

  //**************************************************************************************//
  // *******************************  PUBLIC METHODS  *********************************** //
  //**************************************************************************************//

  /**
   * Render current working dir
   */
  function defaultMethod()
  {
    $this->_initRenderer();
    $this->_rnd->render(
      $this->cwd,
      $this->root->path.'/',
      $this->_ark->getMimesExtract(),
      $this->_conf('numInRow'),
      $this->_conf('numInRowPopup'),
      $this->_conf('tmbW'),
      $this->_conf('tmbH')
      );
  }

  /**
   * Change directory if exists and save position in user prefs
   */
  function cd()
  {
    $this->_cd( $this->_arg() ? $this->_arg() : $this->root->hash );
    elLocation( $this->_baseURL );
  }


  // ------------------  DIRECTORY MANIPULATION  ------------------------------ //

  /**
   * Create new directory
   */
  function mkDir()
  {
    if ( !empty($_POST['newName']) && $this->cwd->mkDir(trim($_POST['newName'])) )
    {
      elMsgBox::put( sprintf(m('Directory "%s" was created'), $_POST['newName']) );
    }
    elLocation( $this->_baseURL );
  }

  /**
   * Delete directory
   */
  function rm()
  {
    if ( null == ($file = & $this->cwd->find($this->_arg()))  )
    {
      elThrow(E_USER_WARNING, m('File does not exists'), null, $this->_baseURL );
    }
    if ( $file->rm() )
    {
      elMsgBox::put( sprintf( m('"%s" was deleted'), basename($file->path)) );
    }
    $this->_cleanTmbs();
    elLocation( $this->_baseURL.'clean/' );
  }

  /**
   * Rename file or dir
   */
  function renameFile()
  {
    if ( !empty($_POST['newName']) )
    {
      if ( null == ($file = &$this->cwd->find($this->_arg()))  )
      {
        elThrow(E_USER_WARNING, m('File does not exists'), null, $this->_baseURL );
      }
      $newName = trim($_POST['newName']);
      $oldName = basename($file->path);
      if ('.' == $newName{0})
      {
        elThrow(E_USER_WARNING, m('File name does not allowed'), null, $this->_baseURL );
      }
      if ( $file->renameFile( $newName ) )
      {
        elMsgBox::put( sprintf(m('File "%s" was renamed to "%s"'), $oldName, $newName) );
      }
    }
    elLocation( $this->_baseURL );
  }



  /**
   * Move files
   */
  function move()
  {
    $noClose = (int) EL_WM == EL_WM_POPUP;
    $this->_initRenderer();

    $form = & $this->_makeFilesForm();

    if ( $form->isSubmitAndValid() )
    {
      $data = $form->getValue();
      if ( empty($data['fmFiles']) )
      {
        elThrow(E_USER_WARNING, 'There are no files was selected', null, $this->_baseURL.'move/');
      }

      // check for target dir for copy or move
      if ( 3 > $data['fmAction'] )
      {
        if ( empty($data['fmTarget']) || false == ($target = &$this->root->find($data['fmTarget'], 'd')) )
        {
          elThrow(E_USER_WARNING, 'There is no target directory for copy or move files', null, $this->_baseURL.'move/');
        }
        elseif ( $this->cwd->hash == $target->hash )
        {
          elThrow(E_USER_WARNING, 'Could not copy or move files to himself', null, $this->_baseURL.'move/');
        }
      }

      $report = array();
      if ( 1 == $data['fmAction'] )
      {
        $method = 'copy';
        $msg    = 'Following files were copied: %s';
      }
      elseif ( 2 == $data['fmAction'] )
      {
        $method = 'mv';
        $msg    = 'Following files were moved: %s';
      }
      else
      {
        $method = 'rm';
        $msg    = 'Following files were deleted %s';
      }

      foreach ( $data['fmFiles'] as $hash )
      {
        if ( false != ($file = & $this->cwd->find($hash, 'f')) )
        {
          if ( !$file->$method( $target->path ) )
          {
            elLocation($this->_baseURL);
          }
          $report[] = basename($file->path);
        }
      }
      elMsgBox::put( sprintf( m($msg), '<br /> - '.implode('<br /> - ', $report)) );
      $this->_cleanTmbs();
      elLocation($this->_baseURL);
    }
    $this->_rnd->addToContent( $form->toHtml() );
  }

  /**
   * Upload  files
   */
  function upload()
  {
    $this->_initRenderer();
    if ( 1 >= $this->cwd->perms )
    {
      elThrow(E_USER_WARNING, 'Could not upload or copy files into this read-only directory', null, $this->_baseURL);
    }

    $num = 0 < $this->_conf('uploadFilesNum') ?  (int)$this->_conf('uploadFilesNum') : 2;
    $form = & $this->_makeUploadForm( $num );

    if ( $form->isSubmitAndValid() )
    {
      $files = $report = array();

      for ($i=1; $i<=$num; $i++)
      {
        if ( false != ( $f =$form->getElementValue('f'.$i)) )
        {
          $files[] = $f;
        }
      }
      if ( empty($files) )
      {
        $form->pushError('f1', 'At least one file must be uploaded.');
        return $this->_rnd->addToContent( $form->toHtml() );
      }
      foreach ( $files as $f )
      {
        if ( empty($f['name']) || empty($f['size']) )
        {
          elThrow(E_USER_WARNING, m('Can not upload file, probably, you forget send it!') );
          continue;
        }
        if ( '.' == $f['name']{0} )
        {
          elThrow(E_USER_WARNING, m('Error uploading file "%s"! File name does not allowed.'), $f['name'] );
          continue;
        }
        if ( empty($f['tmp_name']) || $f['error'] || !move_uploaded_file($f['tmp_name'], $this->cwd->path.'/'.$f['name']))
        {
          elThrow(E_USER_WARNING, m('Error uploading file "%s"! See more info in debug or error log.'), $f['name']);
          continue;
        }
        if ( !preg_match('/^[a-z0-9_\-\.]+$/i', $f['name']) )
        {
          $msg = sprintf( m('File name "%s" contains unwelcome symbols. There is may be some problem to view this file in browser or download it. Renamed is recommended'), $f['name']);
          elMsgBox::put( $msg, EL_WARNQ);
        }
        $report[] = $f['name'];
      }
      elMsgBox::put( sprintf( m('Following files was uploaded: %s'), '<br /> - '.implode('<br /> - ', $report)) );
      $this->_cleanTmbs();
      elLocation($this->_baseURL);
    }
    $this->_rnd->addToContent( $form->toHtml() );
  }

  /**
   * Extract files from archive into current directory
   */
  function arkExtract()
  {
    if ( null == ($file = &$this->cwd->find($this->_arg(), 'f')) )
    {
      elThrow(E_USER_WARNING, 'File does not exists', null, $this->_baseURL );
    }
    $stat = $file->getStat();
    if ( !$this->_ark->canExtract($stat['mime'], true) )
    {
      elThrow( E_USER_WARNING, 'File %s is not an archive file, or this archive type does not supported!',  basename($file->path), $this->_baseURL);
    }
    if ( !$this->_ark->extract($file->path, dirname($file->path)) )
    {
      elThrow(E_USER_WARNING, $this->_ark->getError(), basename($file->path), $this->_baseURL);
    }
    elMsgBox::put( sprintf(m('Files from archive %s was extracted'), basename($file->path)) );
    elLocation( $this->_baseURL );
  }


  /**
   * Add files to new archive
   */
  function arkCreate()
  {
    $form = & $this->_makeArkForm();
    $this->_initRenderer();

    if ( $form->isSubmitAndValid() )
    {
      $data = $form->getValue();
      if ( empty($data['arkFiles']) )
      {
        $form->pushError('arkFiles', m('There are no files was selected') );
        return $this->_rnd->addToContent( $form->toHtml() );
      }

      $files = array();
      foreach ( $data['arkFiles'] as $hash )
      {
        if ( null != ($file = & $this->cwd->find($hash, 'f') )
        ||   null != ($file = & $this->cwd->find($hash, 'd') ) )
        {
          $files[] = './'.basename($file->path);
        }
      }
      $arcName = $this->cwd->path.'/'.$data['arkName'];
      if (!$this->_ark->createArchive($arcName, $data['arkType'], $files, $this->cwd->path) )
      {
        elThrow(E_USER_WARNING, m('Error creating archive. Here is info that may be usefull: "%s"'),
          $this->_ark->getError(), $this->_baseURL );
      }
      elMsgBox::put( sprintf( m('Archive %s was created'), $data['arkName']));
      elLocation( $this->_baseURL );
    }
    $this->_rnd->addToContent( $form->toHtml() );
  }

  function cleanTmbs()
  {
    $this->_cleanTmbs();
    elLocation($this->_baseURL);
  }
  //**************************************************************************************//
  // =============================== PRIVATE METHODS ==================================== //
  //**************************************************************************************//



  function _initNormal()
  {
    parent::_initNormal();

    if ( EL_WM == EL_WM_POPUP )
    {
      $this->_popupMode = 1;
      $this->_baseURL = EL_WM_URL.intval($this->pageID).'/';
    }

    $this->_root();
    $this->_cd();

    if ( 1 >= $this->cwd->perms ) //read only
    {
      $save = $this->_mMap['cd'];
      unset($this->_mMap);
      $this->_mMap = array('cd'=>$save);
    }

    $this->_ark = &elSingleton::getObj('elArk');
  }

  function _initRenderer()
  {
    parent::_initRenderer();
    $this->_rnd->baseFmURL = $this->_baseURL;
    if ( $this->_popupMode )
    {
      $this->_rnd->_path = '__fm__/'.$this->pageID.'/';
      $this->_smPath = EL_URL_POPUP.'/__fm__/'.$this->pageID.'/';
    }
  }

  function _onBeforeStop()
  {
    //elPrintR($this->_mMap);
    $nav = '<form method="POST" action="'.$this->_baseURL.'">';
    $nav .= m('Go to').':&nbsp;&nbsp;';
    $nav .= '<select name="nav" onChange="this.form.action=\''.$this->_baseURL.'cd/\'+this.value;this.form.submit();">';
    $tree = $this->root->getTree();
    $pathLen = strlen($this->root->path);
    foreach ( $tree as $hash=>$dir )
    {
      $dir = $dir == $this->root->path ? '/' : substr($dir, $pathLen);
      $nav .= '<option value="'.$hash.'"'.($this->cwd->hash == $hash ? 'selected="on"' : '').'>'.$dir.'</option>';
    }
    $nav .= '</select>';
    $nav .= '</form>'; //echo $nav;
    $this->_rnd->addOnPane( $nav );
  }


  /**
   * create root node
   */
  function _root()
  {
    if ( !is_dir(EL_DIR_STORAGE) )
    {
      @mkdir(EL_DIR_STORAGE);
    }
    if ( !is_dir(EL_DIR_STORAGE.'.thumbnails') )
    {
      @mkdir(EL_DIR_STORAGE.'.thumbnails');
    }
    $this->root = & elSingleton::getObj('elFmFile', EL_DIR_STORAGE);

    if ( !$this->root->type )
    {
      return elThrow(E_USER_ERROR, 'Top dir for uploading files does not exists', null, EL_BASE_URL);
    }
    elseif ( !$this->root->perms )
    {
      return elThrow(E_USER_WARNING, 'Access denied to %s', EL_DIR_STORAGE);
    }
  }

  /**
   * set current working dir
   */
  function _cd( $hash=null )
  {
    $ats  = & elSingleton::getObj('elATS');
    $user = & $ats->getUser();

    if ( $hash )
    {
      $cd = $hash;
    }
    else
    {
      $cd = null != ($saved = $user->getPref('fmCwdHash'))
      ? $saved
      : $this->root->hash;
    }

    if (  null != (@$cwd = & $this->root->find($cd, 'd' ) ) )
    {
      $this->cwd = & $cwd;
    }
    else
    {
      $this->cwd = & $this->root;
    }

    if ( $hash && $hash <> $this->cwd->hash )
    {
      elThrow(E_USER_WARNING, 'Directory does not exists');
    }

    //save or erase?
    if ( $this->cwd->hash <> $this->root->hash )
    {
      $user->setPref('fmCwdHash', $this->cwd->hash);
    }
    else
    {
      $user->dropPref('fmCwdHash');
    }
  }


  function &_makeFilesForm()
  {
    $form = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $form->renderer->errInline = false;
    $form->setLabel( m('Copy/Move/Delete files') );
    $form->add( new elSelect('fmAction', m('Action'), 1, array(1=>m('Copy'), m('Move'), m('Delete') )) );

    $dirs = $file = array();

    $tree = $this->root->getTree();
    $cutLen = strlen($this->root->path);
    if ( 2 == $this->root->perms )
    {
      $dirs[$this->root->hash] = '/';
    }
    foreach ( $tree as $hash=>$path )
    {
      if ( is_writable($path) )
      {
        $dirs[$hash] = $path != $this->root->path ? substr($path, $cutLen) : '/';
      }
    }
    $form->add( new elSelect('fmTarget', m('Target dir'), null, $dirs ) );

    $childs = $this->cwd->getChilds();
    foreach ( $childs as $el )
    {
      if ( 'f' == $el->type )
      {
        $name = basename($el->path) . (2 == $el->perms ? '' : ' ('.m('read-only').')') ;
        $files[$el->hash] = $name;
      }
    }
    $form->add( new elCheckBoxesGroup('fmFiles', m('Files'), null, $files ) );
    $form->setRequired('fmAction');
    $form->setRequired('fmFiles[]');
    return $form;
  }

  function &_makeUploadForm( $num )
  {
    $form = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $form->renderer->errInline = false;
    $form->setLabel( m('Upload files') );
    $form->add( new elCData('', sprintf( m('Files size must be not greater then %d Mb'), ini_get('upload_max_filesize') ) ) );
    for ( $i=1; $i<=$num; $i++ )
    {
      $form->add( new elFileInput('f'.$i, '') );
    }
    return $form;
  }

  function &_makeArkForm()
  {
    $form = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $form->renderer->errInline = false;
    $form->setLabel( m('Create archive') );

    $form->add( new elCData('t1', m('New archive file name')), array('cellAttrs'=>'class="form_header2"') );

    $c = &new elFormContainer('c1');
    $c->setTpl('element', '<div style="display:none">%s</div> %s');
    $c->add( new elText('arkName', m('New archive name'), 'new_archive', array('size'=>30)), array('nolabel'=>1) );
    $c->add( new elSelect('arkType', '', '', $this->_ark->getTypesCreate() ));
    $form->add($c);
    $form->setRequired('arkName');

    $form->add( new elCData('t2', m('Files to add to archive')), array('cellAttrs'=>'class="form_header2"') );

    $files  = array();
    $childs = $this->cwd->getChilds();
    foreach ( $childs as $el )
    {
     // if ( 'f' == $el->type )
      //{
        $files[$el->hash] = basename($el->path);
      //}
    }
    $form->add( new elCheckBoxesGroup('arkFiles', 'Add files to archive', null, $files ), array('nolabel'=>1) );
    return $form;
  }

  function &_makeConfForm()
  {
    $form = &parent::_makeConfForm();

    $form->add( new elSelect('uploadFilesNum', m('Number of files uploading at once'),     $this->_conf('uploadFilesNum'),
    range(1, 10), null, false, false ) );
    $form->add( new elSelect('numInRow',       m('Number of icons in row'),                $this->_conf('numInRow'),
    range(2, 20), null, false, false ) );
    $form->add( new elSelect('numInRowPopup',  m('Number of icons in row (popup window)'), $this->_conf('numInRowPopup'),
    range(2, 20),  null, false, false ) );
    $sizes = array( 25  => '25x25',
    50  => '50x50',
    75  => '75x75',
    100 => '100x100',
    125 => '125x125',
    150 => '150x150',
    175 => '175x175',
    200 => '200x200',
    225 => '225x225',
    250 => '250x250');
    $form->add( new elSelect('tmbW',        m('Maximum Image thumbnail size (px)'), $this->_conf('tmbW'),
    $sizes));//, null, false, false ) );
    return $form;
  }

  function _validConfForm( &$form)
  {
    $conf = $form->getValue();
    $conf['tmbH'] = $conf['tmbW'];
    if ( $this->_conf('tmbW') != $conf['tmbW'] )
    {
      $this->_cleanTmbs();
    }
    //elPrintR($conf);
    return $conf;
  }

  function _cleanTmbs()
  {
    if ( false == ($d = dir($this->root->path.'/.thumbnails')) )
    {
      return;
    }
    $tree = $this->root->getTree();
    while ( $entr = $d->read() )
    {
      if ( '.' == $entr || '..' == $entr )
      {
        continue;
      }
      if ( is_file($d->path.'/'.$entr) )
      {
        @unlink($d->path.'/'.$entr);
      }
      elseif ( is_dir($d->path.'/'.$entr) && false != ($sd = dir($d->path.'/'.$entr) ) )
      {
        while ( $e = $sd->read() )
        {
          if ( '.' != $e && '..' != $e && is_file($sd->path.'/'.$e) )
          {
            @unlink($sd->path.'/'.$e);
          }
        }
        $sd->close();
        if ( empty($tree[$entr]) )
        {
          rmdir($d->path.'/'.$entr);
        }
      }
    }
    $d->close();
  }

}


?>