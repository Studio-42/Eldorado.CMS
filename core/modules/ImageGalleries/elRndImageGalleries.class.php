<?php

class elRndImageGalleries extends elModuleRenderer
{
	var $_tpls = array('gal' => 'gallery.html', 'image'=>'image.html');
	
	function rndList( $gList )
	{
		$this->_setFile();
		$cnt = 0;
		
		foreach ( $gList as $gal )
		{
			// elPrintR($gal);
			$images = $gal['preview'];
			unset($gal['preview']);
			$gal['cssRowClass'] = (++$cnt%2) ? 'strip-ev' : 'strip-odd';
			$this->_te->assignBlockVars('IG', $gal );
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('IG.ADMIN', array('g_id' => $gal['g_id']), 1);
			}
			if ($this->_conf('displayGalDate'))
			{
				$this->_te->assignBlockVars('IG.IG_DATE', array('date'=>$gal['date']), 1);
			}
			if ($this->_conf('displayGalImgNum'))
			{
				$this->_te->assignBlockVars('IG.IG_IMG_NUM', array('num'=>$gal['num']), 1);
			}
			if ($gal['g_comment'])
			{
				$this->_te->assignBlockVars('IG.IG_COMMENT', array('g_comment'=>$gal['g_comment']), 1);
			}

			foreach ($images as $img)
			{
				$this->_te->assignBlockVars('IG.IG_TMB', $img, 1);
			}
		}
	}
	
	function rndGallery($content, $gID)
	{
		$this->_setFile('gal');
		$gal = $content['gal'];
		$images = $content['images'];
		$this->_te->assignVars($gal);
		$this->_admin && $this->_te->assignBlockVars('GAL_ADMIN', array('g_id' => $gal['g_id']));
		$this->_conf('displayGalDate') && $this->_te->assignBlockVars('GAL_DATE');
		$this->_conf('displayGalImgNum') && $this->_te->assignBlockVars('GAL_IMG_NUM');
		$gal['g_comment'] && $this->_te->assignBlockVars('GAL_COMMENT');
		
		$dsplFileName = $this->_conf('displayFileName');
		$dsplFileSize = $this->_conf('displayFileSize');
		$dsplImgDate  = $this->_conf('displayImgDate');
		$dsplImgSize  = $this->_conf('displayImgSize');
		
		for ($i=0, $s = sizeof($images); $i<$s; $i++)
		{
			$this->_te->assignBlockVars('GAL_IMG', $images[$i]);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('GAL_IMG.ADMIN', array('i_gal_id' => $images[$i]['i_gal_id'], 'i_id' => $images[$i]['i_id']), 1);
			}
			$images[$i]['i_comment'] && $this->_te->assignBlockVars('GAL_IMG.COMMENT', array('i_comment' => $images[$i]['i_comment']), 1);
			$dsplFileName && $this->_te->assignBlockVars('GAL_IMG.FILE',  array('i_file'      => $images[$i]['i_file']), 1);
			$dsplFileSize && $this->_te->assignBlockVars('GAL_IMG.FSIZE', array('i_file_size' => $images[$i]['i_file_size']), 1);
			$dsplImgDate  && $this->_te->assignBlockVars('GAL_IMG.DATE',  array('date'        => $images[$i]['date']), 1);
			$dsplImgSize  && $this->_te->assignBlockVars('GAL_IMG.SIZE',  array('i_width_0'   => $images[$i]['i_width'], 'i_height_0' => $images[$i]['i_height']), 1);
		}
		if ( 1 < $content['pages'] )
		{
			$this->_rndPager($content['pages'], $content['pageNum'], $gID);
		}
	}

	function rndImage($img)
	{
		$this->_setFile('image');
		$data = array(
			'i_name'   => $img->getAttr('i_name'),
			'src'      => EL_IG_URL.$this->pageID.'/'.$this->_sizes[$this->_imgSizeNdx].'/'.$img->file,
			'i_width'  => $img->getAttr('i_width_'.$this->_imgSizeNdx),
			'i_height' => $img->getAttr('i_height_'.$this->_imgSizeNdx)
			);
		$this->_te->assignVars( $data );								
	}
	
	function _rndPager( $total, $current, $gID )
	{
		$this->_te->setFile('PAGER', 'common/pager.html');
		if ( $current > 1 )
		{
			$this->_te->assignBlockVars('PAGER.PREV', array('num'=>$current-1, 'url'=>EL_URL.$gID.'/'));
		}
		for ( $i=1; $i<=$total; $i++ )
		{
			$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num'=>$i, 'url'=>EL_URL.$gID.'/'));
		}
		if ( $current < $total )
		{
			$this->_te->assignBlockVars('PAGER.NEXT', array('num'=>$current+1, 'url'=>EL_URL.$gID.'/'));
		}
		$this->_te->parse('PAGER');
	}

	
}

?>