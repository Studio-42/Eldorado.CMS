<?php

class elIGGallery extends elMemberAttribute 
{
	var $_objName = 'Image gallery';
	var $_tbImg   = '';
	var $_uniq    = 'g_id';
	//var $igDir    = '';
	var $ID       = 0;	
	var $name     = '';
	var $comment  = '';
	var $sortNdx  = 0;
	var $crTime   = 0;
	var $mTime    = 0;
	var $imgSort  = '';
	
	function setImgTb( $tb )
	{
		$this->_tbImg = $tb;
	}
	
	function countImages()
	{
		$db = & $this->_getDb();
		$db->query('SELECT COUNT(*) AS num FROM '.$this->_tbImg.' WHERE i_gal_id='.$this->ID);
		$r = $db->nextRecord();
		return $r['num'];
	}
	
	function getPreviewImages( $num, $offset=0 )
	{
		$img = & elSingleton::getObj('elIGImage');
		$img->setTb($this->_tbImg);
		$sort       = empty($this->imgSort) ? 'RAND()' : $this->imgSort;
		return $img->getCollection(null, $sort, $offset, $num, 'i_gal_id='.$this->ID);
	}
	
	function clean( $dir )
	{
		if ( !$this->ID || !$this->countImages() )
		{
			return;
		}
		$db  = & $this->_getDb();
		$files = $db->queryToArray('SELECT i_file FROM '.$this->_tbImg.' WHERE i_gal_id='.$this->ID, null, 'i_file');
		if ( $files )
		{
			$img = & elSingleton::getObj('elIGImage');
			$img->setTb($this->_tbImg);
			foreach ( $files as $file )
			{
				$img->rmFile($file);
			}
		}
		$db->query('DELETE FROM '.$this->_tbImg.' WHERE i_gal_id='.$this->ID);
		$db->optimizeTable($this->_tbImg);
	}
	
	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elText('g_name', m('Name'), $this->getAttr('g_name'), array('maxlength'=>150)) );
		$this->form->add( new elTextArea('g_comment', m('Comment'), $this->getAttr('g_comment'), array('rows'=>4, 'maxlength'=>255)) );
		$this->form->setRequired('g_name');
	}
	
	function _initMapping()
	{
		$map = array(
								'g_id'       => 'ID',
								'g_name'     => 'name',
								'g_comment'  => 'comment',
								'g_sort_ndx' => 'sortNdx',
								'g_crtime'   => 'crTime',
								'g_mtime'    => 'mTime'
								);
		$this->crTime = $this->mTime = time();
		return $map;									
	}
	
	function _attrsForSave()
	{
		$this->setAttr('g_mtime', time());
		if (!$this->crTime )
		{
			$this->setAttr('g_crtime', time());
		}
		return parent::_attrsForSave();
	}
}

?>