<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIGFactory.class.php';

define('EL_IG_SORT_NAME',  0);
define('EL_IG_SORT_TIME',  1);

define('EL_IG_WMPOS_TL', 'tl');
define('EL_IG_WMPOS_TR', 'tr');
define('EL_IG_WMPOS_C',  'c');
define('EL_IG_WMPOS_BL', 'bl');
define('EL_IG_WMPOS_BR', 'br');


define('EL_IG_DIR', EL_DIR_STORAGE.'galleries'.DIRECTORY_SEPARATOR);
define('EL_IG_URL', EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/galleries/');

// URL args -
// /gallery_id/page_number
// /view/gallery_id/image_id

class elModuleImageGalleries extends elModule
{
	var $_tbG        = '';
	var $_tbI        = '';
	var $_factory    = null;

	var $_sharedRndMembers = array('pageID', '_sizeNdx', '_sizes');
	var $_conf       = array(
		'gSort'            => EL_IG_SORT_TIME,
		'iSort'            => EL_IG_SORT_TIME,
		// 'displayMethod'    => EL_IG_DISPL_LIGHTBOX,
		'displayGalDate'   => 1,
		'displayGalImgNum' => 1,
		'displayImgDate'   => 1,
		'displayImgSize'   => 2,
		'displayFileSize'  => 1,
		'displayFileName'  => 1,
		'tmbNumInGalList'  => 5,
		'tmbNumPerPage'    => 20,
		'tmbMaxSize'       => 150,
		'tmbCrop'          => true,
		'imgUniqNames'     => 0,
		'watermark'        => '',
		'watermarkPos'     => EL_IG_WMPOS_BR,
		// 'imgSizeNdx'       => 2
		);

	// var $_sizeNdx = 2;
	
	var $_wmPos = array(
                  EL_IG_WMPOS_TL => 'Top left',
                  EL_IG_WMPOS_TR => 'Top right',
                  EL_IG_WMPOS_C  => 'Center',
                  EL_IG_WMPOS_BL => 'Bottom left',
                  EL_IG_WMPOS_BR => 'Bottom right',
                   );
	var $_gID = '';
	var $_galleries = array();
/**
 * Display galleries list or
 * gallery content if there is only one gallery
 */

	function defaultMethod()
	{
		$this->_initRenderer();
		if (!$this->_gID && 1 == sizeof($this->_galleries) && EL_READ == $this->_aMode)
		{
			$this->_gID = key($this->_galleries);
		}
		if ($this->_gID)
		{

			elAppendToPagePath( array('url'=>$this->_gID, 'name'=>$this->_galleries[$this->_gID]) );
			$this->_rnd->rndGallery($this->_factory->galleryContent($this->_gID, (int)$this->_arg(1)), $this->_gID);
		}
		else
		{
			$this->_rnd->rndList($this->_factory->galleries());
		}
	}

/**
 * View full size image
 *
 */
	function viewImg()
	{
		$img = $this->_factory->image((int)$this->_arg(1));
		if ( !$img->ID)
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists',	array($img->getObjName(),	$img->ID) );
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
		$dir = EL_IG_DIR.$this->pageID.DIRECTORY_SEPARATOR;
		if (!is_dir($dir) && !elFS::mkdir($dir))
		{
			return elThrow(E_USER_ERROR, 'Could not create directory %s', $dir);
		}
		if (!is_dir($dir.'tmb') && !elFS::mkdir($dir.'tmb'))
		{
			return elThrow(E_USER_ERROR, 'Could not create directory %s', $dir.'tmb');
		}
		if (!is_dir($dir.'tmb') && !elFS::mkdir($dir.'original'))
		{
			return elThrow(E_USER_ERROR, 'Could not create directory %s', $dir.'original');
		}
		for ($i=1, $s= sizeof($this->_sizes); $i<$s; $i++)
		{
			if (!is_dir($dir.$this->_sizes[$i]) && !elFS::mkdir($dir.$this->_sizes[$i]))
			{
				return elThrow(E_USER_ERROR, 'Could not create directory %s', $dir.$this->_sizes[$i]);
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
		elFS::rmdir(EL_IG_DIR.$this->pageID);
	}


	//****************************************//
	//					PRIVATE METHODS
	//****************************************//

	function _onInit()
	{
		$this->_tbG        = 'el_ig_'.$this->pageID.'_gallery';
		$this->_tbI        = 'el_ig_'.$this->pageID.'_image';

		$ats = & elSingleton::getObj('elATS');
		if (isset($_POST['ig_isize']) && !empty($this->_sizes[$_POST['ig_isize']]))
		{
			$ats->user->setPref('ig_isize', (int)$_POST['ig_isize']);
		}



		$this->_factory = & new elIGFactory($this->pageID, $this->_conf);

		$this->_galleries  = $this->_factory->galleriesList(); 

		$gID = $this->_arg();
		if (!empty($gID))
		{
			if (isset($this->_galleries[$gID]))
			{
				$this->_gID = $gID;
			}
			else
			{
				header('HTTP/1.x 404 Not Found'); 
				elThrow(E_USER_ERROR, 'Error 404: Page %s not found.', $_SERVER['REQUEST_URI']);
			}
		}
			
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');

	}


}
?>