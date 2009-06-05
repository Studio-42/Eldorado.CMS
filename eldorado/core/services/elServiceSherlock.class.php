<?php

class elServiceSherlock extends elService
{
	var $nav        = null;
	var $_tplDir    = 'common/search/';
	var $_tplFile   = 'searchResult.html';
	var $_pageTitle = 'Search';

	function defaultMethod()
	{
		$form = &$this->makeForm();
		$this->_initRenderer();

		if ( !$form->isSubmitAndValid() )
		{
				$vars   = array( 'FORM' => $form->toHtml() );
				$this->_rnd->render($vars);
		}
		else
		{
			$sstr = strip_tags($form->getElementValue('sstr')); //echo $sstr;
			$result = $this->_search( $sstr );
			$block  = empty($result) ? 'SR_NORESULT' : 'SR_RESULT';
			$vars   = array(
							'FORM'      => $form->toHtml(),
							'searchStr' => $sstr
							);

			$this->_rnd->render($vars);
			$this->_rnd->render(array(), null, 'SR_TITLE');
			$this->_rnd->render($result, null, $block);

		}
	}

	function toXML()
	{
		$sstr = strip_tags( trim($_GET['sstr']) );
		$result = $this->_search( $sstr );// elPrintR($result);
		$tpl    = "<record><name>%s</name><url>%s</url></record>\n";
		$reply  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$reply .= "<response>\n";
		$reply .= "<method>doSearch</method>\n";
		$reply .= "<result>\n";
		$reply .= "<searchString>".htmlspecialchars($sstr)."</searchString>\n";
		foreach ($result as $one )
		{
			$reply .= sprintf($tpl, $one['name'], $one['url']);
		}
		$reply .= "</result>\n";
		$reply .= "</response>\n";
		return $reply;
	}

	function & makeForm()
	{
		$form = & elSingleton::getObj( 'elForm', 'search', m("Find on site") );
		$form->setAttr('method', 'GET');
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->add( new elText('sstr', m('Search string')) );
		$form->setElementRule('sstr', 'minlength', true, 2);
		$form->renderer->setButtonsNames(m('Find'), m('Drop'));
		return $form;
	}

	function _getSourceList()
	{
		$db    = & elSingleton::getObj('elDb');
		$sList = array();
		$sql   = 'SELECT id, name, el_menu.module FROM el_menu, el_module WHERE '
						.'el_menu.redirect_url=\'\' AND '
						.'el_module.module=el_menu.module AND el_module.search=\'1\' ORDER BY el_menu._left';;
		$db->query( $sql );
		while ($r = $db->nextRecord())
		{
			if ( !isset($sList[$r['module']]['obj']) )
			{
				if ( empty($sList[$r['module']]) )
				{
					$sList[$r['module']] = array();
				}
				$sList[$r['module']][] = $r['id'];
			}
		}
		return $sList;
	}

	function _search( $str )
	{
		$str = substr($str, 0, 100);
		$r   = '/\[|\]|\\\|\/|\'|\"|\*|\||\(|\)|(\s{2,)/';
		$str = preg_replace($r, '', $str);
		$r   = array('.',',','!','@','#','^','&','*',':', '%');
		$str = str_replace( $r, '', $str); 
		if ( empty($str) )
		{
			return array();
		}
		$this->nav  = & elSingleton::getObj('elNavigator');
		$sList      = $this->_getSourceList();
		$result     = array();
		$regex      = preg_replace( '/\s+/i', '|',	preg_quote($str));
		$regex      = 'UPPER("'.$regex.'")';
		//echo "$str $regex";
		foreach ( $sList as $moduleName => $pageIDs )
		{
			$class = 'elModule'.$moduleName.'Search';
			if ( !elSingleton::incLib('modules/'.$moduleName.'/'.$class.'.class.php') )
			{
				continue;
			}
			$searchObj = & elSingleton::getObj($class);
			$r = $searchObj->getResults($pageIDs, $regex);

			foreach ($r as $pageID=>$onePageRes)
			{
				$pageName = $this->nav->getPageName($pageID);
				$pageURL  = $this->nav->getPageURL($pageID);
				foreach ( $onePageRes as $one)
				{
					$name     = $pageName.($one['title'] ? ' :: '.$one['title'] : '');
					$url      = $pageURL.($one['path'] ? $one['path'] : '');
					$result[] = array('name'=>$name, 'url'=>$url);
				}
			}
		}
		return $result;
	}

}

?>