<?php
define('EL_IG_SORT_NAME',  0);
define('EL_IG_SORT_TIME',  1);

define('EL_IG_WMPOS_TL', 0);
define('EL_IG_WMPOS_TR', 1);
define('EL_IG_WMPOS_C',  2);
define('EL_IG_WMPOS_BL', 3);
define('EL_IG_WMPOS_BR', 4);

if ( !defined('EL_IG_DISPL_POPUP') )
{
	define('EL_IG_DISPL_POPUP', 0);
}
if (!defined('EL_IG_DISPL_LIGHTBOX') )
{
	define('EL_IG_DISPL_LIGHTBOX', 1);
}

$GLOBALS['igImgSizes'] = array('Original size', '640x480', '800x600', '1024x768', '1280x960');
$GLOBALS['igDir']      = EL_DIR_STORAGE.'galleries/';
$GLOBALS['igURL']      = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/galleries/';
// URL args -
// /gallery_id/page_number
// /view/gallery_id/image_id

class elModuleImageGalleries extends elModule
{
	var $_tbG        = '';
	var $_tbI        = '';
	var $_gallery    = null;
	var $_collection = array();
	var $_mMap       = array('img' => array( 'm'   => 'viewImg'));
	var $_sharedRndMembers = array('_imgSizeNdx');
	var $_galSorts   = array(
													EL_IG_SORT_NAME => 'IF(g_sort_ndx>0, LPAD(g_sort_ndx, 4, "0"), "9999"), g_name, g_crtime DESC',
													EL_IG_SORT_TIME => 'IF(g_sort_ndx>0, LPAD(g_sort_ndx, 4, "0"), "9999"), g_crtime DESC, g_name',
													);
	var $_imgSorts   = array(
													EL_IG_SORT_NAME => 'IF(i_sort_ndx>0, LPAD(i_sort_ndx, 4, "0"), "9999"), i_name, i_crtime DESC',
													EL_IG_SORT_TIME => 'IF(i_sort_ndx>0, LPAD(i_sort_ndx, 4, "0"), "9999"), i_crtime DESC, i_name',
													);
	var $_conf       = array(
													'gSort'            => EL_IG_SORT_TIME,
													'iSort'            => EL_IG_SORT_TIME,
													'displayMethod'    => EL_IG_DISPL_LIGHTBOX,
													'displayGalDate'   => 1,
													'displayGalImgNum' => 1,
													'displayImgDate'   => 1,
													'displayImgSize'   => 2,
													'displayFileSize'  => 1,
													'displayFileName'  => 1,
													'tmbNumInGalList'  => 5,
													'tmbNumPerPage'    => 20,
													'tmbNumInRow'      => 5,
													'tmbMaxSize'       => 150,
													'imgUniqNames'     => 0,
													'watermark'        => '',
													'watermarkPos'     => EL_IG_WMPOS_BR       
													);

	var $_imgSizeNdx = 3;
	
	var $_wmPos = array(
                  EL_IG_WMPOS_TL => 'Top left',
                  EL_IG_WMPOS_TR => 'Top right',
                  EL_IG_WMPOS_C  => 'Center',
                  EL_IG_WMPOS_BL => 'Bottom left',
                  EL_IG_WMPOS_BR => 'Bottom right',
                   );
	
/**
 * Display galleries list or
 * gallery content if there is only one gallery
 */

	function defaultMethod()
	{
		$this->_initRenderer();
		$mt = &elSingleton::getObj('elMetaTagsCollection'); 

		if ( empty($this->_collection))
		{
			return;
			$this->_rnd->addToContent( m('There are no one image galleries') );
		}
		elseif ( $this->_gID )
		{
			$this->_rndGallery(); 
			$mt->init($this->pageID, $this->_gID, 0);
		}
		elseif ( 1 == sizeof($this->_collection) && EL_READ == $this->_aMode )
		{
			$this->_gID = key($this->_collection);
			$this->_rndGallery();
		}
		else
		{
			$this->_rnd->rndList( $this->_collection );
		}

	}

/**
 * View full size image
 *
 */
	function viewImg()
	{
		$img = $this->_getImage();
		if ( !$img->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',
							array($img->getObjName(),	$img->ID) );

		}
		$this->_initRenderer();
		$this->_rnd->rndImage($img);
	}

	/**
	 * Called when new image galleries page creating
	 * Create directory for images inside EL_DIR_STORAGE.'galleries/'
	 *
	 * @return viod
	 */
	function onInstall()
	{
		$dirs = array( 'galleries', 'galleries/'.$this->pageID);
		foreach ($dirs as $dir )
		{
			$dir = EL_DIR_STORAGE.$dir;
			if ( !is_dir($dir) )
			{
				if (!mkdir($dir, 0755))
				{
					return elThrow(E_USER_ERROR, 'Could not create directory %s', $dir);
				}
			}
			if ( !is_writable($dir) )
			{
				return elThrow(E_USER_ERROR, 'Directory %s has not write permissions', $dir);
			}
		}
	}

	/**
	 * Called when image galleries page deleting
	 * Remove dir with all images
	 *
	 */
	function onUninstall()
	{

		$cmd = 'rm -rf '.escapeshellarg(realpath(EL_DIR_STORAGE.'galleries/'.$this->pageID));
		elDebug('Uninstall '.$cmd );
		exec($cmd);
	}


	//****************************************//
	//					PRIVATE METHODS
	//****************************************//
	/**
 	* Create and return new gallery object
 	*
 	* @return object
 	*/
	function _getGallery()
	{
		$gallery = elSingleton::getObj('elIGGallery');
		$gallery->setTb( $this->_tbG );
		$gallery->setImgTb( $this->_tbI );
		$gallery->setUniqAttr( (int)$this->_arg() );
		if ( !$gallery->fetch() )
		{
			$gallery->setUniqAttr(0);
		}
		return $gallery;
	}

	/**
	 * create and return new image object
	 *
	 * @return object
	 */
	function _getImage()
	{
		$img = elSingleton::getObj('elIGImage');
		$img->setTb( $this->_tbI );
		$img->setUniqAttr( (int)$this->_arg(1) );
		$img->tmbMaxSize = $this->_conf('tmbMaxSize');
		$wm = $this->_conf('watermark');
		if ($wm && is_file('./storage/galleries/'.$this->pageID.'/wm/'.$wm) )
		{
			$wmPos = $this->_conf('watermarkPos');
			if ( empty($this->_wmPos[$wmPos]) )
			{
				$wmPos = EL_IG_WMPOS_BR;
			}
			$img->wm    = './storage/galleries/'.$this->pageID.'/wm/'.$wm;
			$img->wmPos = $wmPos;
		}
		if ( !$img->fetch() )
		{
			$img->setUniqAttr(0);
		}
		return $img;
	}

	/**
	 * render gallery page
	 *
	 */
	function _rndGallery()
	{
		$gallery          = $this->_collection[$this->_gID];
		$gallery->imgSort = $this->_getISort();
		$pageNum          = (int)$this->_arg(1);
		$total            = $gallery->countImages();
		$imgsPerPage      = (int)$this->_conf('tmbNumPerPage');

		if (!$pageNum)
		{
			$pageNum = 1;
		}
		if ( 0 >= $imgsPerPage )
		{
			$imgsPerPage = 20;
		}
		$pagesTotal = ceil($total/$imgsPerPage);
		if ( $pagesTotal < $pageNum  )
		{
			$pageNum = 1;
		}
		$this->_rnd->_imgSizeNdx = $this->_imgSizeNdx;
		$this->_rnd->rndGallery($gallery, $pageNum, $pagesTotal, EL_IG_DISPL_LIGHTBOX == $this->_conf('displayMethod') );
	}

	function _onBeforeStop()
	{
		if (!empty($this->_collection[$this->_gID]))
		{
			$gallery = $this->_collection[$this->_gID];
			elAppendToPagePath( array('url'=>$gallery->ID, 'name'=>$gallery->getAttr('g_name')) );
		}
	}

	function _onInit()
	{
		$GLOBALS['igDir'] .= $this->pageID.'/';
		$GLOBALS['igURL'] .= $this->pageID.'/';
		$this->_tbG        = 'el_ig_'.$this->pageID.'_gallery';
		$this->_tbI        = 'el_ig_'.$this->pageID.'_image';

		$gallery           = $this->_getGallery();
		$this->_collection = $gallery->getCollection(null, $this->_getGSort());
		$this->_gID        = $gallery->ID;
		$ID                = $this->_arg();

		if ( $ID && $ID !== $this->_gID )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',
							array($gallery->getObjName(),	$ID), EL_URL );

		}
		$ats = & elSingleton::getObj('elATS');
		if (isset($_POST['ig_isize']))
		{
			$ats->user->setPref('ig_isize', (int)$_POST['ig_isize']);
		}

		$imgSizeNdx = $ats->user->getPref('ig_isize');
		if (null === $imgSizeNdx || empty($GLOBALS['igImgSizes'][$imgSizeNdx]) )
		{
			$this->_imgSizeNdx = 1;
			$ats->user->setPref('ig_isize', 1);
		}
		else
		{
			$this->_imgSizeNdx = (int)$imgSizeNdx;
		}

		if (EL_IG_DISPL_LIGHTBOX == $this->_conf('displayMethod'))
		{
			unset($this->_mMap['img']);
			
			elAddJs('jquery.js', EL_JS_CSS_FILE);
			elAddJs('jquery.pngFix.js', EL_JS_CSS_FILE);
			elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
			elAddJs('jquery.fancybox.js', EL_JS_CSS_FILE);
			
			elAddCss('fancybox.css');
		}
	}


	/**
	 * Return string for MySQl ORDER BY op - galleries sorting
	 *
	 * @return string
	 */
	function _getGSort()
	{
		$sort = $this->_conf('gSort');
		return !empty($this->_galSorts[$sort]) ? $this->_galSorts[$sort] : $this->_galSorts[EL_IG_SORT_TIME];
	}

	/**
	 * return string for MySQL ORDER BY op - image sorting
	 *
	 * @return unknown
	 */
	function _getISort()
	{
		$sort = $this->_conf('iSort');
		return !empty($this->_imgSorts[$sort]) ? $this->_imgSorts[$sort] : $this->_imgSorts[EL_IG_SORT_TIME];
	}


}
?>