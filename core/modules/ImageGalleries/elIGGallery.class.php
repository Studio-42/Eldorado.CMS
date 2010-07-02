<?php

class elIGGallery extends elDataMapping
{
	var $_objName = 'Image gallery';
	var $_uniq    = 'g_id';
	var $_id      = 'g_id';
	var $tbi      = '';
	var $ID       = 0;	
	var $name     = '';
	var $comment  = '';
	var $sortNdx  = 0;
	var $crTime   = 0;
	var $mTime    = 0;
	var $imgSort  = '';
	
	function countImages()
	{
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT COUNT(i_id) AS num FROM '.$this->tbi.' WHERE i_gal_id='.$this->ID);
		$r = $db->nextRecord(); 
		return $r['num'];
	}
	
	function clean($image)
	{
		if ($this->ID && $this->countImages())
		{
			$images = $image->collection(true, true, 'i_gal_id="'.$this->ID.'"');
			foreach ($images as $i)
			{
				$i->rmFile();
			}
			$db = &elSingleton::getObj('elDb');
			$db->query('DELETE FROM '.$image->tb().' WHERE i_gal_id="'.$this->ID.'"');
			$db->optimizeTable($image->tb());
		}
	}
	
	function _makeForm()
	{
		parent::_makeForm();
		$this->_form->add( new elText('g_name', m('Name'), $this->name, array('maxlength'=>255)) );
		$this->_form->add( new elTextArea('g_comment', m('Comment'), $this->comment, array('rows'=>5)) );
		$this->_form->setRequired('g_name');
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
		$this->attr('g_mtime', time());
		if (!$this->crTime )
		{
			$this->attr('g_crtime', time());
		}
		return parent::_attrsForSave();
	}
}

?>
