<?php

class elRndImageGalleries extends elModuleRenderer
{
	var $_tpls       = array('gal' => 'gallery.html', 'image'=>'image.html');
	var $_admTpls    = array('gal' => 'adminGallery.html');
	var $_imgSizeNdx = 2;
	
	
	function rndList( $gList )
	{
		$this->_setFile();
		$cnt = 0;
		foreach ( $gList as $gal )
		{
			$val = array('g_id'        => $gal->ID, 
						 'g_name'      => $gal->getAttr('g_name'), 
						 'cssRowClass' => (++$cnt%2) ? 'strip-ev' : 'strip-odd');
			$this->_te->assignBlockVars('IG', $val );
			if ( $this->_conf('displayGalDate') )
			{
				$this->_te->assignBlockVars('IG.IG_DATE', array('date'=>date(EL_DATE_FORMAT, $gal->getAttr('g_crtime'))), 1 );
			}
			if ( $this->_conf('displayGalImgNum') )
			{
				$this->_te->assignBlockVars('IG.IG_IMG_NUM', array('num'=>$gal->countImages()), 1);
			}
			if ( $gal->comment )
			{
				$this->_te->assignBlockVars('IG.IG_COMMENT', array('g_comment'=>$gal->comment), 1);
			}

			if ( $this->_conf('tmbNumInGalList') )
			{
				$prevImgs = $gal->getPreviewImages( $this->_conf('tmbNumInGalList') ); //elPrintR($prevImgs);
				if ( $prevImgs )
				{
					$this->_te->assignBlockVars('IG.IG_PREV', array('g_id'=>$gal->ID), 1);
					foreach ( $prevImgs as $img )
					{
						$vars = array(
									'g_id' => $gal->ID,
									'src'          => $img->getSrc('tmb'),
									'i_width_tmb'  => $img->getAttr('i_width_tmb'), 
									'i_height_tmb' => $img->getAttr('i_height_tmb')
									);
						$this->_te->assignBlockVars('IG.IG_PREV.IG_TMB', $vars, 2);
					}
				}
			}
		}
	}
	
	function rndGallery($gal, $pageNum, $pagesTotal, $useLightbox=false)
	{
		$this->_setFile('gal');
		$this->_te->assignVars('g_name', $gal->getAttr('g_name'));
		foreach ($GLOBALS['igImgSizes'] as $i=>$s)
		{
			//echo $i.' '.$this->_imgSizeNdx.'<br>';
			$sel = $i == $this->_imgSizeNdx ? ' selected="on"' : '';
			$this->_te->assignBlockVars('ISIZE', array('ndx'=>$i, 'size'=>m($s), 'sel'=>$sel));
		}
		if ( '' != ($gComment = $gal->getAttr('g_comment') ) )
		{
			$this->_te->assignBlockVars('GAL_COMMENT', array('g_comment'=>$gComment));
		}
		$numInRow    = (int)$this->_conf('tmbNumInRow');
		$cellWidth   = floor(100/$numInRow); 
		$imgsPerPage = (int)$this->_conf('tmbNumPerPage');
		$imgs        = $gal->getPreviewImages($imgsPerPage, ($pageNum-1)*$imgsPerPage); 
		$cnt         = -1;
		$cssCnt      = 0; 
		$dsplFileName = $this->_conf('displayFileName');
		$dsplFileSize = $this->_conf('displayFileSize');
		$dsplImgDate  = $this->_conf('displayImgDate');
		$dsplImgSize  = $this->_conf('displayImgSize');
		foreach ( $imgs as $img )
		{
			if ( 0 == (++$cnt)%$numInRow )
			{
				$cssCnt++;
				$css = $cssCnt%2 ? 'strip-ev' : 'strip-odd';
				$this->_te->assignBlockVars('GAL_ROW', array('cssRowClass'=>$css)); 
			}
			//$iName = nl2br(htmlspecialchars( wordwrap($img->getAttr('i_name'), $wordWrap, "\n", 1)));
			$iName = htmlspecialchars($img->getAttr('i_name'));
			$iID   = $img->getAttr('i_id');

			$data = array(
							'i_id'      => $iID,
							'i_gal_id'  => $gal->ID,
							'i_name'    => $iName,
							'cellWidth' => $cellWidth,
							'i_width_tmb'  => $img->getAttr('i_width_tmb')
							);	
			$this->_te->assignBlockVars('GAL_ROW.TMB', $data, 1);	

			$data['src']          = $img->getSrc('tmb');										
			//$data['i_width_tmb']  = $img->getAttr('i_width_tmb');
			$data['i_height_tmb'] = $img->getAttr('i_height_tmb');
			if ( $useLightbox )
			{
				$block          = 'GAL_ROW.TMB.TMB_LB';
				$data['target'] = $img->getSrc($this->_imgSizeNdx);
				$data['i_name'] = $iName;
			}
			else
			{
				$block              = 'GAL_ROW.TMB.TMB_POPUP';
				$data['win_width']  = $img->getAttr('i_width_'.$this->_imgSizeNdx);
				$data['win_height'] = $img->getAttr('i_height_'.$this->_imgSizeNdx);
			}
			$this->_te->assignBlockVars($block, $data, 2);
			
			if ( $dsplFileName )
			{
				$this->_te->assignBlockVars('GAL_ROW.TMB.IMG_INFO.FILE_NAME', array('i_file'=>$img->getAttr('i_file')), 3);
			}
			if ( $dsplFileSize )
			{
				$this->_te->assignBlockVars('GAL_ROW.TMB.IMG_INFO.FILE_SIZE', array('i_file_size'=>$img->getAttr('i_file_size')), 3);
			}
			if ( $dsplImgDate )
			{
				$date = date(EL_DATE_FORMAT , $img->getAttr('i_mtime'));
				$this->_te->assignBlockVars('GAL_ROW.TMB.IMG_INFO.IMG_DATE', array('date'=>$date), 3);
			}
			if ( $dsplImgSize )
			{
				$val = array( 'i_width_0'  => $img->getAttr('i_width_0'), 'i_height_0' => $img->getAttr('i_height_0'));
				$this->_te->assignBlockVars('GAL_ROW.TMB.IMG_INFO.IMG_SIZE', $val, 3);
			}

			if ( false != ($cmnt = $img->getAttr('i_comment')))
			{
				//$cmnt = wordwrap($cmnt, $wordWrap, '<br />', 1);  
				$this->_te->assignBlockVars('GAL_ROW.TMB.TMB_COMMENT', array('i_comment'=>$cmnt), 2);
			}
		}
		if ( 1 < $pagesTotal )
		{
			$this->_te->assignVars('catID', $gal->ID);
			$this->_rndPager($pagesTotal, $pageNum);
		}
		if ( $useLightbox && $this->_te->isBlockExists('GAL_SCRIPT'))
		{
			$this->_te->assignBlockVars('GAL_SCRIPT');
		}
	}

	function rndImage($img)
	{
		$this->_setFile('image');
		$data = array(
									'i_name'   => $img->getAttr('i_name'),
									'src'      => $img->getSrc($this->_imgSizeNdx),
									'i_width'  => $img->getAttr('i_width_'.$this->_imgSizeNdx),
									'i_height' => $img->getAttr('i_height_'.$this->_imgSizeNdx)
									);
		$this->_te->assignVars( $data );								
	}
	
	function _rndPager( $total, $current )
	{
		$this->_te->setFile('PAGER', 'common/pager.html');
		$this->_te->assignVars('total', $total);

		if ( $current > 1 )
		{
			$this->_te->assignBlockVars('PAGER.PREV', array('num'=>$current-1));
		}
		for ( $i=1; $i<=$total; $i++ )
		{
			$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num'=>$i));
		}
		if ( $current < $total )
		{
			$this->_te->assignBlockVars('PAGER.NEXT', array('num'=>$current+1));
		}
		$this->_te->parse('PAGER');
	}

	
}

?>