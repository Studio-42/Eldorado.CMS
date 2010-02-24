<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIGGallery.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIGImage.class.php';

class elIGFactory
{
	var $tbg    = 'el_ig_%d_gallery';
	var $tbi    = 'el_ig_%d_image';
	var $_db = null;
	var $pageID = 0;
	var $_dir = '';
	var $sortg  = '';
	var $sorti  = '';
	var $_gSorts   = array(
		EL_IG_SORT_NAME => 'IF(g_sort_ndx>0, LPAD(g_sort_ndx, 4, "0"), "9999"), g_name, g_crtime DESC',
		EL_IG_SORT_TIME => 'IF(g_sort_ndx>0, LPAD(g_sort_ndx, 4, "0"), "9999"), g_crtime DESC, g_name',
		);
	var $_iSorts   = array(
		EL_IG_SORT_NAME => 'IF(i_sort_ndx>0, LPAD(i_sort_ndx, 4, "0"), "9999"), i_name, i_crtime DESC',
		EL_IG_SORT_TIME => 'IF(i_sort_ndx>0, LPAD(i_sort_ndx, 4, "0"), "9999"), i_crtime DESC, i_name',
		);
	
	var $_conf = array();
	var $_sizes = array();
	var $_sizeNdx = 2;
	
	function __construct($pageID, $conf, $sizeNdx, $sizes)
	{
		$this->pageID   = $pageID;
		$this->_dir     = EL_IG_DIR.$pageID.DIRECTORY_SEPARATOR;
		$this->tbg      = sprintf($this->tbg, $pageID);
		$this->tbi      = sprintf($this->tbi, $pageID);
		$this->_conf    = $conf;
		$this->_sizeNdx = $sizeNdx; 
		$this->_sizes   = $sizes;
		$this->sortg    = !empty($conf['gSort']) && !empty($this->_gSorts[$conf['gSort']]) ? $this->_gSorts[$conf['gSort']] : $this->_gSorts[EL_IG_SORT_TIME];
		$this->sorti    = !empty($conf['iSort']) && !empty($this->_iSorts[$conf['iSort']]) ? $this->_iSorts[$conf['iSort']] : $this->_iSorts[EL_IG_SORT_TIME];
		$this->_conf['tmbNumPerPage']   = $this->_conf['tmbNumPerPage'] > 0 ? $this->_conf['tmbNumPerPage'] : 20;
		$this->_conf['tmbNumInGalList'] = $this->_conf['tmbNumInGalList'] > 0 ? $this->_conf['tmbNumInGalList'] : 5;
		$this->_conf['tmbMaxSize']      = $this->_conf['tmbMaxSize'] > 0 ? $this->_conf['tmbMaxSize'] : 20;
		$this->_db      = & elSingleton::getObj('elDb');
	}
	
	function elIGFactory($pageID, $conf, $sizeNdx, $sizes)
	{
		$this->__construct($pageID, $conf, $sizeNdx, $sizes);
	}
	
	function gallery($id=0)
	{
		$g = & new elIGGallery( array('g_id' => $id), $this->tbg);
		$g->tbi = $this->tbi;
		!$g->fetch() && $g->idAttr(0);
		return $g;
	}
	
	function image($id=0)
	{
		$img = & new elIGImage(array('i_id' => $id), $this->tbi);
		$img->pageID = $this->pageID;
		$img->dir = $this->_dir;
		$img->sizes = $this->_sizes;
		$img->tmbMaxWidth = $this->_conf['tmbMaxSize'];
		!$img->fetch() && $img->idAttr(0);
		return $img;
	}
	
	function galleriesList()
	{
		return $this->_db->queryToArray('SELECT g_id, g_name FROM '.$this->tbg, 'g_id', 'g_name');
	}
	
	function galleriesSortList()
	{
		return $this->_db->queryToArray('SELECT g_id, g_name, g_sort_ndx FROM '.$this->tbg.' ORDER BY '.$this->sortg, 'g_id');
	}
	
	function galleries()
	{
		$ret  = array();
		$g    = $this->gallery();
		$ret  = $g->collection(false, true, null, $this->sortg);
		$nums = $this->_db->queryToArray('SELECT i_gal_id, COUNT(i_id) AS num FROM '.$this->tbi.' GROUP BY i_gal_id', 'i_gal_id', 'num');
		foreach ($ret as $id=>$g)
		{
			$ret[$id]['date']      = date(EL_DATE_FORMAT, $ret[$id]['g_crtime']);
			$ret[$id]['g_comment'] = nl2br($ret[$id]['g_comment']);
			$ret[$id]['num']       = 0;
			$ret[$id]['preview']   = array();
			if (isset($nums[$id]))
			{
				$ret[$id]['num'] = $nums[$id];
				$sql = 'SELECT i_gal_id AS g_id, CONCAT("'.EL_IG_URL.$this->pageID.'/tmb/", i_file) AS src, i_width_tmb, i_height_tmb FROM '.$this->tbi
						.' WHERE i_gal_id="'.$id.'" ORDER BY RAND() LIMIT 0, '.$this->_conf['tmbNumInGalList'];
				$ret[$id]['preview'] = $this->_db->queryToArray($sql);
			}
		}
		return $ret;
	}
	
	
	function galleryContent($gID, $pageNum)
	{
		if (false == ($g = $this->gallery($gID)))
		{
			return false;
		}
		
		$gal              = $g->toArray();
		$gal['date']      = date(EL_DATE_FORMAT, $gal['g_crtime']);
		$gal['g_comment'] = nl2br($gal['g_comment']);
		$gal['num']       = $g->countImages();
		$images           = array();
		$pageNum          = $pageNum>0 ? $pageNum : 1;
		$pages            = ceil($gal['num']/$this->_conf['tmbNumPerPage']);
		$offset           = ($pageNum-1)*$this->_conf['tmbNumPerPage'];

		$size = $this->_sizeNdx ? $this->_sizes[$this->_sizeNdx] : 'original';
		$sql = 'SELECT i_id, i_gal_id, CONCAT("'.EL_IG_URL.$this->pageID.'/tmb/", i_file) AS src, i_file, '
				.'CONCAT("'.EL_IG_URL.$this->pageID.'/'.$size.'/", i_file) AS target, '
				.'i_width_'.$this->_sizeNdx.'+20 AS win_width, i_height_'.$this->_sizeNdx.'+50 AS win_height, '
				.'i_width_tmb, i_height_tmb, i_width_0, i_height_0, i_file_size, i_name, i_comment, '
				.'DATE_FORMAT(FROM_UNIXTIME(i_crtime), "'.EL_MYSQL_DATE_FORMAT.'")  AS date, i_sort_ndx '
				.'FROM '.$this->tbi.' WHERE i_gal_id="'.$g->ID.'" ORDER BY '.$this->sorti.' LIMIT '.$offset.', '.$this->_conf['tmbNumPerPage'];
				
		$images = $this->_db->queryToArray($sql);
		return array('gal' => $gal, 'images'=>$images, 'pageNum'=>$pageNum, 'pages'=>$pages);
	}
	
}

?>