<?php

class elRndBulletinBoard extends elModuleRenderer
{
	var $_admTpls   = array('new' => 'newPosts.html');
	
	function rndDefault($catID, $cats, $items, $pageNum, $pagesNum, $allowPost, $authedUser)
	{
		$this->_setFile();
		$this->_te->assignVars('cat_id', $catID);
		if ($this->newMsgsNum)
		{
			$this->_te->assignBlockVars('BB_PANEL.BB_NOT_PUBLISHED', array('num'=>$this->newMsgsNum), 1);
		}
		if ($allowPost)
		{
			$this->_te->assignBlockVars('BB_PANEL.BB_POST_BUTTON', array('cat_id'=>$catID), 1);
		}
		if (!$authedUser)
		{
			$this->_te->assignBlockVars('BB_PANEL.BB_AUTH_FORM', null, 1);
			$ats = & elSingleton::getObj('elATS');
			if ($ats->isRegistrationAllowed())
			{
				$this->_te->assignBlockVars('BB_PANEL.BB_AUTH_FORM.BB_REG', null, 2);
			}
		}
		foreach( $cats as $cat )
		{
			if (!isset($cat['items_num']))
			{
				$cat['items_num'] = 0;
			}
			$this->_te->assignBlockVars('BB_CAT', $cat);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('BB_CAT.BB_CAT_ADMIN', array('id'=>$cat['id']), 1);
			}
		}
//
		if ($items)
		{
			foreach ($items as $item)
			{
				$item['crtime'] = date(EL_DATE_FORMAT, $item['crtime']);
				$item['content'] = nl2br($item['content']);
				$item['cssClass'] = $item['published'] ? '' : 'not-published';
				$this->_te->assignBlockVars('BB_ITEM', $item);
				if ($this->_admin)
				{
					$this->_te->assignBlockVars('BB_ITEM.BB_ITEM_ADMIN', array('id'=>$item['id'], 'cat_id'=>$item['cat_id']), 1);
					if (!$item['published'])
					{
						$this->_te->assignBlockVars('BB_ITEM.BB_ITEM_ADMIN.BBI_PUBLISH', array('id'=>$item['id']), 2);
					}
				}
			}
			
			if ($pagesNum>1)
			{
				$this->_rndPager($catID, $pagesNum, $pageNum);
			}
		}

		

	}
	
	function rndNew($cats, $items)
	{
		$this->_setFile('new');
		$catID = -1;
		foreach ($items as $item)
		{
			if ($catID!=$item['cat_id'])
			{
				$data = !empty($cats[$item['cat_id']]) ? $cats[$item['cat_id']] : null;
				$this->_te->assignBlockVars('BB_CAT', $data);
				$catID = $item['cat_id'];
			}
			$item['crtime'] = date(EL_DATE_FORMAT, $item['crtime']);
			$item['content'] = nl2br($item['content']);
			$this->_te->assignBlockVars('BB_CAT.BB_ITEM', $item, 1);
		}
	}
	
	
	function _rndPager( $catID, $total, $current )
	{
		$this->_te->setFile('PAGER', 'common/pager.html');
		$url = EL_URL.$catID.'/';
		if ( $current > 1 )
		{
			$this->_te->assignBlockVars('PAGER.PREV', array('url' => $url, 'num'=>$current-1 ));
		}
		for ( $i=1; $i<=$total; $i++ )
		{
			$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num'=>$i, 'url'=>$url));
		}
		if ( $current < $total )
		{
			$this->_te->assignBlockVars('PAGER.NEXT', array('url'=>$url, 'num'=>$current+1 ));
		}
		$this->_te->parse('PAGER');
	}
}

?>