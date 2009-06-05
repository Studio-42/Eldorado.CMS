<?php
class elIGImage extends elMemberAttribute
{
	var $_uniq     = 'i_id';
	var $_objName  = 'Image';
	var $tmbMaxSize = 125;
	var $ID        = 0;
	var $galID     = 0;
	var $file      = '';
	var $fileSize  = 0;
	var $name      = '';
	var $comment   = '';
	var $width     = 0;
	var $height    = 0;
	var $width1    = 0;
	var $height1   = 0;
	var $width2    = 0;
	var $height2   = 0;
	var $width3    = 0;
	var $height3   = 0;
	var $width4    = 0;
	var $height4   = 0;
	var $width5    = 0;
	var $height5   = 0;

	var $widthTmb  = 0;
	var $heightTmb = 0;
	var $sortNdx   = 0;
	var $crTime    = 0;
	var $mTime     = 0;

	var $wm        = '';
	var $wmPos     = EL_IG_WMPOS_BR;
	var $imager    = null;
	
	function getSrc($sizeNdx)
	{
		if ('tmb' === $sizeNdx )
		{
			return $GLOBALS['igURL'].'tmb/'.$this->file;
		}
		if ( $sizeNdx > 0 && !empty($GLOBALS['igImgSizes'][$sizeNdx]) )
		{
			return $GLOBALS['igURL'].$GLOBALS['igImgSizes'][$sizeNdx].'/'.$this->file;
		}
		return $GLOBALS['igURL'].$this->file;
	}
	
	function editAndSave( $params=null )
	{
		$this->makeForm( $params );
		if ( !$this->form->isSubmitAndValid() )
		{
			return false;
		}

		$uploader = & $this->form->get('upl_file');

		if ( $uploader->isUploaded() )
		{
			$file = $params['rename'] ? md5(microtime()).'.'.$uploader->getExt() : $uploader->getFileName();
			if ( !$uploader->moveUploaded($file, $GLOBALS['igDir']) )
			{
				
				return $this->form->pushError('upl_file', sprintf(m('Can not upload file "%s"'), $uploader->getFileName()) );
			}
			if ( $this->wm )
			{
				if (!$this->_setWatermark($GLOBALS['igDir'].$file))
				{
					elThrow(E_USER_WARNING, 'Could not add watermark to image');
				}
			}
			if ( !$this->setImgFile($file) )
			{
				@unlink($GLOBALS['igDir'].$file);
				return $this->form->pushError('upl_file', sprintf( m('File "%s" is not an image or has unsupported type'), $uploader->getFileName()));
			}
			
		}
		
		$this->setAttrs( $this->form->getValue() );
		
		return $this->save();
	}

	function makeForm( $params )
	{
		parent::makeForm();
		$gID = $this->getUniqAttr() ? $this->getAttr('i_gal_id') : $params['gID'];
		$this->form->add( new elSelect('i_gal_id', m('Gallery'), $gID, $params['gList']) );
		$this->form->add( new elText(  'i_name',   m('Name'),   $this->name) );

		$uploader = & new elImageUploader('upl_file', m('Image'), $this->file ? $this->getSrc(0) : '' );
		$uploader->setReplaceMode(true);
		$this->form->add( $uploader );
		if (!$this->getAttr('i_file'))
		{
			$this->form->setRequired('upl_file');
		}
		$this->form->add( new elTextArea( 'i_comment', m('Comment'), $this->getAttr('i_comment'), 
																			array('rows'=>4, 'maxlength'=>255)) );
	}

	function setImgFile($file)
	{
		//$_imager = & elSingleton::getObj('el_imager');
		$this->initImager();
		$path   = realpath($GLOBALS['igDir'].$file);
		
		if (false == (list($origW, $origH) = $this->imager->getInfo($path)) )
		{
			return elThrow(E_USER_WARNING, $this->__imager->getError());
		}
		// remove old file
		if ( $this->file )
		{
			$this->rmFile($this->file);
		}
		$this->setAttr('i_file',      $file);
		$this->setAttr('i_file_size', ceil(filesize($path)/1024) );
		$this->setAttr('i_width_0',   $origW);
		$this->setAttr('i_height_0',  $origH);
		
		// make thumbnail
		if ( false == (list($tmbW, $tmbH) = $this->imager->makeTmb($path, 'tmb', $this->tmbMaxSize, $this->tmbMaxSize)) )
		{
			elThrow(E_USER_WARNING, $this->__imager->getError());
			list($tmbW, $tmbH) = $this->imager->calcTmbSize($origW, $origH, $this->tmbMaxSize, $this->tmbMaxSize);
		}
		$this->setAttr('i_width_tmb',  $tmbW);
		$this->setAttr('i_height_tmb', $tmbH);
		
		// make scaled copies
		for ( $i=1, $s=sizeof($GLOBALS['igImgSizes']); $i<$s; $i++ )
		{
			list($tmbW, $tmbH) = explode('x', $GLOBALS['igImgSizes'][$i]);
			if ( false == (list($prevW, $prevH) = $this->imager->makeTmb($path, $GLOBALS['igImgSizes'][$i], $tmbW, $tmbH)) )
			{
				elThrow(E_USER_WARNING, $this->imager->getError());
				list($prevW, $prevH) = $this->imager->calcTmbSize($origW, $origH, $tmbW, $tmbH);
			}	
			$this->setAttr('i_width_'.$i,  $prevW);
			$this->setAttr('i_height_'.$i, $prevH);
		}
		return true;
	}

	function updateTmb( $tmbSize )
	{
		if ($tmbSize > 0 && ($tmbSize<>$this->getAttr('i_width_tmb') || $tmbSize<>$this->getAttr('i_height_tmb')) )
		{
			$path   = realpath($GLOBALS['igDir'].$this->getAttr('i_file'));
			$this->initImager();
			
			if ( false == (list($tmbW, $tmbH) = $this->imager->makeTmb($path, 'tmb', $tmbSize, $tmbSize)) )
			{
				elThrow(E_USER_WARNING, $this->imager->getError());
				list($tmbW, $tmbH) = $this->imager->calcTmbSize($w, $h, $tmbSize, $tmbSize);
			}
			$this->setAttr('i_width_tmb',  $tmbW);
			$this->setAttr('i_height_tmb', $tmbH);
			return true;
		}
		return false;
	}
	
	function delete()
	{
		if (!$this->ID)
		{
			return;
		}
		$this->rmFile( $this->getAttr('i_file') );
		$db = & $this->_getDb();
		$db->query('DELETE FROM '.$this->tb.' WHERE i_id=\''.$this->ID.'\'');
		$db->optimizeTable($this->tb);
	}

	function _initMapping()
	{
		$map = array(
		'i_id'         => 'ID',
		'i_gal_id'     => 'galID',
		'i_file'       => 'file',
		'i_file_size'  => 'fileSize',
		'i_name'       => 'name',
		'i_comment'    => 'comment',
		'i_width_0'    => 'width',
		'i_height_0'   => 'height',
		'i_width_1'    => 'width1',
		'i_height_1'   => 'height1',
		'i_width_2'    => 'width2',
		'i_height_2'   => 'height2',
		'i_width_3'    => 'width3',
		'i_height_3'   => 'height3',
		'i_width_4'    => 'width4',
		'i_height_4'   => 'height4',
		'i_width_5'    => 'width5',
		'i_height_5'   => 'height5',
		'i_width_tmb'  => 'widthTmb',
		'i_height_tmb' => 'heightTmb',
		'i_sort_ndx'   => 'sortNdx',
		'i_crtime'     => 'crTime',
		'i_mtime'      => 'mTime'
		);
		return $map;
	}

	function _attrsForSave()
	{
		if ( !$this->ID )
		{
			$this->setAttr('i_crtime', time());
		}
		$this->setAttr('i_mtime', time());
		return parent::_attrsForSave();
	}

	function initImager()
	{
		if (!$this->imager)
		{
			$this->imager = & elSingleton::getObj('elImager');
		}
	}
	
	function rmFile($file)
	{
		// remove original
		$path = realpath($GLOBALS['igDir'].$file); 
		if ( is_file($path) && !@unlink($path) )
		{
			elThrow(E_USER_WARNING, 'Could not delete file "%s"', $path);
		}
		// remove thumbnail
		$path = realpath($GLOBALS['igDir'].'tmb/'.$file); 
		if ( is_file($path) && !@unlink($path) )
		{
			elThrow(E_USER_WARNING, 'Could not delete file "%s"', $path);
		}
		// remove all scaled copies
		for ( $i=1, $s=sizeof($GLOBALS['igImgSizes']); $i<$s; $i++ )
		{
			$path = realpath($GLOBALS['igDir'].$GLOBALS['igImgSizes'][$i].'/'.$file);
			if ( is_file($path) && !@unlink($path) )
			{	
				elThrow(E_USER_WARNING, 'Could not delete file "%s"', $path);
			}
		}
	}
	
	function _setWatermark($file)
	{
		if ( !function_exists('imagecreatefromjpeg') )
		{
			return false;
		}
		$fc = array(
					1 => 'imagecreatefromgif',
					2 => 'imagecreatefromjpeg',
					3 => 'imagecreatefrompng'
					);
		$fs = array(
					1 => 'imagegif',
					2 => 'imagejpeg',
					3 => 'imagepng'
					);
		
		$s = getimagesize($file);
		if ( empty($fc[$s[2]]) )
		{
			return false;
		}
		$f     = $fc[$s[2]];
		$fSave = $fs[$s[2]];
		$orig  = $f($file);
		
		$s = getimagesize($this->wm);
		if ( empty($fc[$s[2]]) )
		{
			return false;
		}
		$f  = $fc[$s[2]];
		$wm = $f($this->wm);
		
		$wOrig = imagesx( $orig );
		$hOrig = imagesy( $orig );
		$wWm   = imagesx( $wm );
		$hWm   = imagesy( $wm );
		
		switch ($this->wmPos)
		{
			case EL_IG_WMPOS_TL:
				$x = $y = 0;
				break;
			case EL_IG_WMPOS_TR:
				$x = $wOrig - $wWm;
				$y = 0;
				break;
			case EL_IG_WMPOS_C:
				$x = ($wOrig - $wWm)/2;
				$y = ($hOrig - $hWm)/2;
				break;
			case EL_IG_WMPOS_BL:
				$x = 0;
				$y = $hOrig - $hWm;
				break;
			default:
				$x = $wOrig - $wWm;
				$y = $hOrig - $hWm;
				
		}
		
		
		$out = imagecreatetruecolor($wOrig, $hOrig);
		imagealphablending($out, TRUE);
		
		imagecopy($out, $orig, 0, 0, 0, 0, $wOrig, $hOrig);
		imagecopy($out, $wm, $x, $y, 0, 0, $wWm, $hWm);
		$fSave($out, $file, 100);
		imagedestroy($out);
		imagedestroy($wm);
		imagedestroy($orig);
		return true;
	}
	
}
?>