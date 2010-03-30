<?php

class elServiceSherlock extends elService
{
	var $nav        = null;
	var $_tplDir    = 'common/search/';
	var $_tplFile   = 'result.html';
	var $_pageTitle = 'Search';
	var $_mMap      = array('html' => array('m' => 'html'));

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
			$sstr   =  $this->_searchString($_GET['sstr']);
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

	function html()
	{
		$sstr   =  $this->_searchString($_GET['sstr']);
		$result = $this->_search( $sstr );
		$html   = '<div><h4>'.m('Search results for').': "'.$sstr.'"</h4>';
		if (!$result)
		{
			$html .= '<p>'.m('There is nothing was found').'</p>';
		}
		else
		{
			$html .= '<ul>';
			foreach ($result as $one)
			{
				$html .= '<li><a href="'.$one['url'].'" class="link forward2">'.$one['name'].'</a></li>';
			}
			$html .= '</ul>';
		}

		exit($html.'</div>');
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

	function _searchString($str)
	{
		$str = strip_tags(substr(trim($str), 0, 100));
		$r   = '/\[|\]|\\\|\/|\'|\"|\*|\||\(|\)|(\s{2,)/';
		$str = preg_replace($r, '', $str);
		$r   = array('.',',','!','@','#','^','&','*',':', '%');
		$str = str_replace( $r, '', $str);
		return $str;
	}

	function _search( $str )
	{
		if ( empty($str) )
		{
			return array();
		}
		$this->nav  = & elSingleton::getObj('elNavigator');
		$sList      = $this->_getSourceList();
		$result     = array();
		$regex1     = preg_replace( '/\s+/i', ' ', preg_quote($str));
		$regex1     = 'UPPER("'.$regex1.'")';
		$regex2     = preg_replace( '/\s+/i', '|', preg_quote($str));
		$regex2     = 'UPPER("'.$regex2.'")';
		$tmp        = array();
		foreach ( $sList as $moduleName => $pageIDs )
		{
			$class = 'elModule'.$moduleName.'Search';
			if ( !elSingleton::incLib('modules/'.$moduleName.'/'.$class.'.class.php') )
			{
				continue;
			}
			$searchObj = & elSingleton::getObj($class);
			$r = $searchObj->getResults($pageIDs, $regex1, $regex2);

			$tmp += $r;
		}
		
		if (!empty($tmp))
		{
			uasort($tmp, '_cmp');
			foreach ($tmp as $url=>$v)
			{
				$result[] = array('url'=>$url, 'name' => $v['title']);
			}
		}
		return $result;
	}
}

function _cmp($a, $b)  
{
	if ($a['weight'] == $b['weight'])
	{
		return strcasecmp($a['title'], $b['title']);
	}
	return $a['weight'] > $b['weight'] ? -1 : 1;
}

?>