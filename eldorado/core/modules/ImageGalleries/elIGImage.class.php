<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elImage.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFileInfo.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFS.class.php';

class elIGImage extends elDataMapping
{
	var $_id     = 'i_id';
	var $_objName  = 'Image';
	var $_error    = '';
	var $pageID    = 0;	
	var $dir       = '';
	var $sizes     = array();
	var $tmbMaxWidth = 150;
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
	

	function editAndSave( $params=null )
	{
		$this->_makeForm( $params );
		if ( !$this->_form->isSubmitAndValid() )
		{
			return false;
		}

		$uploader = & $this->_form->get('upl_file');

		if ( $uploader->isUploaded() )
		{
			$filename = $params['rename'] ? md5(microtime()).'.'.$uploader->getExt() : $uploader->getFileName();
			$file     = $this->dir.'original'.DIRECTORY_SEPARATOR.$filename;
			if ( !$uploader->moveUploaded($filename, $this->dir.'original'.DIRECTORY_SEPARATOR) )
			{
				elLoadMessages('Errors');
				return $this->_form->pushError('upl_file', sprintf(m('Can not upload file "%s"'), $uploader->getFileName()) );
			}
			
			// if ( $this->wm )
			// {
			// 	if (!$this->_setWatermark($GLOBALS['igDir'].$file))
			// 	{
			// 		elThrow(E_USER_WARNING, 'Could not add watermark to image');
			// 	}
			// }

			if ( !$this->setImgFile($file, $params['wm'], $params['wmpos']) )
			{
//				@unlink($file);
				return $this->_form->pushError('upl_file', $this->_error);
			}
			
		}
		
		$this->attr($this->_form->getValue());
		
		return $this->save();
	}

	function _makeForm( $params )
	{
		parent::_makeForm();
		$gID = $this->ID ? $this->galID : $params['gID'];
		$this->_form->add( new elSelect('i_gal_id', m('Gallery'), $gID, $params['parents']) );

		$uploader = & new elImageUploader('upl_file', m('Image'), $this->file ? EL_IG_URL.$this->pageID.'/tmb/'.rawurlencode($this->file) : '' );
		$uploader->setReplaceMode(true);
		$this->_form->add( $uploader );
		if (!$this->file)
		{
			$this->_form->setRequired('upl_file');
		}
		$this->_form->add( new elText('i_name', m('Name'), $this->name) );
		$this->_form->add( new elTextArea('i_comment', m('Comment'), $this->comment, array('rows'=>4, 'maxlength'=>256)) );
	}


	function setImgFile($file, $wm, $wmpos)
	{
		if (false == ($s = elFileInfo::isWebImage($file)))
		{
			elLoadMessages('Errors');
			$this->_error = sprintf(m('File "%s" is not an image or has unsupported type'), basename($file));
			return false;
		}

		$this->attr( array('i_width_0' => $s[0], 'i_height_0' => $s[1]));

		if (!elFS::mkdir($this->dir.'tmb'))
		{
			elLoadMessages('Errors');
			$this->_error = sprintf(m('Could not create directory %s'), $dir.'tmb');
			return false;
		}

		$_image = elSingleton::getObj('elImage');
		$h = ceil($this->tmbMaxWidth/(4/3));
		
		if (!$_image->tmb($file, $this->dir.'tmb', $this->tmbMaxWidth, $h, true))
		{
			$this->_error = $_image->error;
			return false;
		}
		$this->attr(array('i_width_tmb' => $this->tmbMaxWidth, 'i_height_tmb' => $h));

		if ($wm)
		{
			$_image->watermark($file, $this->dir.'wm'.DIRECTORY_SEPARATOR.$wm, $wmpos);
		}

		for ($i=1, $sz=sizeof($this->sizes); $i<$sz; $i++)
		{
			$_dir = $this->dir.$this->sizes[$i].DIRECTORY_SEPARATOR; 
			if (!elFS::mkdir($_dir))
			{
				elLoadMessages('Errors');
				$this->_error = sprintf(m('Could not create directory %s'), $_dir);
				return false;
			}
			list($_w, $_h) = explode('x', $this->sizes[$i]);
			list($w, $h)   = $_image->calcTmbSize($s[0], $s[1], $_w, $_h);

			if (!$_image->tmb($file, $_dir, $w, $h))
			{
				$this->_error = $_image->error;
				return false;
			}
			$this->attr(array('i_width_'.$i => $w, 'i_height_'.$i => $h));
		}
		$this->attr(array('i_file' => basename($file), 'i_file_size' => filesize($file)/1024));
		$this->ID && $this->file && $this->file != basename($file) && $this->rmFile();
		return true;
	}

	function updateTmb()
	{
		$_image = elSingleton::getObj('elImage');
		$h = ceil($this->tmbMaxWidth/(4/3));
		
		if (!$_image->tmb($this->dir.'original'.DIRECTORY_SEPARATOR.$this->file, $this->dir.'tmb', $this->tmbMaxWidth, $h, true))
		{
			$this->_error = $_image->error;
			return false;
		}
		$this->attr(array('i_width_tmb' => $this->tmbMaxWidth, 'i_height_tmb' => $h));
		return $this->save();
	}

	function rmFile()
	{
		file_exists($this->dir.'original'.DIRECTORY_SEPARATOR.$this->file) && @unlink($this->dir.'original'.DIRECTORY_SEPARATOR.$this->file);
		file_exists($this->dir.'tmb'.DIRECTORY_SEPARATOR.$this->file) && @unlink($this->dir.'tmb'.DIRECTORY_SEPARATOR.$this->file);		
		foreach ($this->sizes as $s)
		{
			file_exists($this->dir.$s.DIRECTORY_SEPARATOR.$this->file) && @unlink($this->dir.$s.DIRECTORY_SEPARATOR.$this->file);		
		}
	}

	function delete()
	{
		if ($this->ID)
		{
			$this->rmFile();
			parent::delete();
		}
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
			$this->attr('i_crtime', time());
		}
		$this->attr('i_mtime', time());
		return parent::_attrsForSave();
	}


	
}
?>