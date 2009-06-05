<?php

class elRndFileManager extends elModuleRenderer
{
  var $_tpls      = array(
  												'viewImg' => 'image.html',
  												'popup'   => 'popup.html'
  												);

  var $_mimeIcons = array(
			 'dir'                 => 'icoDir',
			 'unknown'             => 'icoMimeUnknown',
			 'text/plain'          => 'icoMimeText',
			 'text/html'           => 'icoMimeHtml',
			 'application/pdf'     => 'icoMimePDF',
			 'application/x-rar'   => 'icoMimeArchive',
			 'application/x-zip'   => 'icoMimeArchive',
			 'application/x-bzip2' => 'icoMimeArchive',
			 'application/x-gzip'  => 'icoMimeArchive',
			 'application/msword'  => 'icoMimeMsWord',
			 'audio/X-HX-AAC-ADTS' => 'icoMimeVideo',
			 'text/rtf'            => 'icoMimeRtf',
			 'image/jpeg'          => 'icoMimeImage',
			 'image/gif'           => 'icoMimeImage',
			 'image/png'           => 'icoMimeImage'
			 );

  var $baseFmURL = EL_WM_URL;


  function render( $cwd, $baseDir, $arkMimes, $num, $numPopup, $tmbW, $tmbH )
  {
  	$imager = &elSingleton::getObj('elImager');
    if ( EL_WM != EL_WM_POPUP )
    {
      $this->_setFile();
    }
    else
    {
      $this->_setFile('popup');
      $num = $numPopup;
    }
    $this->_te->assignVars('baseFmURL', $this->baseFmURL);
    $cnt = 0;

    $files = $cwd->getChilds();
    uasort($files, array($this, '_sort'));
    $w          = floor(100/$num);
    $baseTmbDir = realpath(EL_DIR_STORAGE).'/.thumbnails/';
    $baseTmbURL = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/.thumbnails/';
    foreach ( $files as $f )
    {
	    if ( 0 == $cnt )
	    {
	    	$this->_te->assignBlockVars('FILES', null);
	    }
	    if ( $cnt++ >= $num-1 )
	    {
	      $cnt = 0;
	    }

      $stat            = $f->getStat();
      $stat['fileURL'] = EL_BASE_URL.'/'.( str_replace($baseDir, EL_DIR_STORAGE_NAME.'/', $stat['path']) );
      $stat['ico']     = $this->_mimeIco($stat['mime']);
      $ctl             = array('name'=>$stat['name'], 'hash'=>$stat['hash']);
      //$stat['name'] = wordwrap($stat['name'], ceil($tmbW/7), '<br />', 1);
      $this->_te->assignBlockVars('FILES.CELL', array('cell_width'=>$w), 1);
      if ( $f->isDir() )
	    {

	      $this->_te->assignBlockVars('FILES.CELL.DIR', $stat, 2);
	      if ( $f->perms > 1 )
        {
          $this->_te->assignBlockVars('FILES.CELL.DIR.DIR_CTL', $ctl, 3);
        }
	    }
      elseif ( !empty($stat['imgW']) )
	    {
	      $dirHash = md5(dirname($stat['path']));
	      $tmbDir  = $baseTmbDir.$dirHash;
	      $tmbURL  = $baseTmbURL.$dirHash.'/'.$stat['name'];
	      if ( !file_exists($tmbDir.$stat['name']) && !$imager->makeTmb($stat['path'], $tmbDir, $tmbW, $tmbH) )
        {
          $stat['tmb'] = '{'.$stat['ico'].'}';
        }
	      else
        {
					$stat['tmb'] = $baseTmbURL.$dirHash.'/'.$stat['name'];
        }
	      $this->_te->assignBlockVars('FILES.CELL.IMG', $stat, 2);
	      if ( $f->perms > 1 )
        {
          $this->_te->assignBlockVars('FILES.CELL.IMG.IMG_CTL', $ctl, 3);
        }
	    }
      else
	    {

	      $this->_te->assignBlockVars('FILES.CELL.FILE', $stat, 2);
	      if ( $f->perms > 1 )
        {
          $this->_te->assignBlockVars('FILES.CELL.FILE.FILE_CTL', $ctl, 3);
          if ( !empty($arkMimes[$stat['mime']]) )
          {
            $this->_te->assignBlockVars('FILES.CELL.FILE.FILE_CTL.ARK', $ctl, 4);
          }
        }
	    }
    }
  }

  function _mimeIco( $mime )
  {
    return !empty($this->_mimeIcons[$mime]) ? $this->_mimeIcons[$mime] : $this->_mimeIcons['unknown'];
  }

  function _sort($el1, $el2)
  {
    if ( $el1->type == $el2->type )
    {
      return ($el1->path < $el2->path) ? -1 : 1;
    }
    else
    {
      return ('f' == $el1->type) ? 1 : -1;
    }
  }

}

?>