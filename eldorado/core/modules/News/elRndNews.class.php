<?php
class elRndNews extends elModuleRenderer
{
	var $_tpls       = array('one'=>'news.html');
	var $displayDate = false;
	var $pageNum     = 1;

	function setPageNum( $num )
	{
		$this->pageNum = $num;
		$this->_te->assignVars('curPageNum', $num);
	}

	function render( $newsList, $total )
	{
		$this->_setFile();

    $m = EL_NEWS_COL_TWO == $this->_conf['displayFormat'] ? '_rndTwoColumns' : '_rndOneColumn';
    $this->$m( $newsList, $this->_getDateFormat(), $this->_getDetailLinkText() );

		if ( $total > 1 )
		{
			$this->renderPager($total);
		}
	}

	function renderNewsDetails( $news )
	{
		if ( $news->altername )
		{
		  elSetAlterTitle($news->altername);
		}
		$this->_setFile('one');
		$data = array(
		    'id'      => $news->ID,
		    'title'   => $news->getAttr('title'),
		    'content'=> $news->getContent(),
		    'topicLinkText' => !empty($this->_conf['topicLinkText']) ? $this->_conf['topicLinkText'] : m('Go back to news topics')
		  );
		$this->_te->assignVars( $data );
		if (false != ($format = $this->_getDateFormat()))
		{
		  $this->_te->assignBlockVars('NEWS_DATE', array('date' => date($format, $news->getAttr('published'))));
		}
		if ( $this->_admin )
		{
		  $this->_te->assignBlockVars('NEWS_ADMIN', array('id' => $news->ID));
		}
	}

	function renderPager( $total )
	{
		$this->_te->setFile('PAGER', 'common/pager.html');
		$this->_te->assignVars('total', $total);
		$data = array('url'=>EL_URL, 'num'=>'');
		if ( $this->pageNum > 1 )
		{
			$data['num'] = $this->pageNum-1;
			$this->_te->assignBlockVars('PAGER.PREV', $data);
		}
		for ( $i=1; $i<=$total; $i++ )
		{
			$data['num'] = $i;
			$this->_te->assignBlockVars($i != $this->pageNum ? 'PAGER.PAGE' : 'PAGER.CURRENT', $data);
		}
		if ( $this->pageNum < $total )
		{
			$data['num'] = $this->pageNum+1;
			$this->_te->assignBlockVars('PAGER.NEXT', $data);
		}
		$this->_te->parse('PAGER');
	}

  /*********************************************************/
  //               PRIVATE
  /*********************************************************/

  function _rndOneColumn($newsList, $dFormat, $linkText)
	{
    foreach ( $newsList as $one )
		{
		  $data = array(
		    'id'      => $one->ID,
		    'title'   => $one->getAttr('title'),
		    'announce'=> $one->getAttr('announce'),
		  );
			$this->_te->assignBlockVars( 'NEWS_OC.NEWS', $data, 1 );
			if ( $dFormat )
			{
			  $this->_te->assignBlockVars('NEWS_OC.NEWS.NDATE', array('date'=>date($dFormat, $one->getAttr('published'))), 2 );
			}
			if ( ($one->hasContent()) && $linkText )
			{
			  $data = array('id'=>$one->ID, 'detailLinkText'=>$linkText);
				$this->_te->assignBlockVars('NEWS_OC.NEWS.NDETAIL', $data, 2 );
			}
		}
	}

	function _rndTwoColumns($newsList, $dFormat, $linkText)
	{
    $c = $r = 0;
    foreach ( $newsList as $one )
    {
      $data = array(
		    'id'      => $one->ID,
		    'title'   => $one->getAttr('title'),
		    'announce'=> $one->getAttr('announce'),
		  );
		  if ($c++%2)
		  {
		    $block = 'NEWS_TC.NROW.TNEWS_RIGHT';
		    $dateBlock = $block.'.R_NDATE';
		    $linkBlock = $block.'.R_NDETAIL';
		    $level = 2;
		  }
		  else
		  {
		    $block = 'NEWS_TC.NROW.TNEWS_LEFT';
		    $dateBlock = $block.'.L_NDATE';
		    $linkBlock = $block.'.L_NDETAIL';
		    $level = 1;
		    $r++;

		  }
		  $data['oddRow'] = $r%2 ? 1 : 0;
      $this->_te->assignBlockVars($block, $data, $level);
      if ( $dFormat )
			{
			  $this->_te->assignBlockVars($dateBlock, array('date'=>date($dFormat, $one->getAttr('published'))), 3 );
			}
			if ( ($one->hasContent()) && $linkText )
			{
			  $data = array('id'=>$one->ID, 'detailLinkText'=>$linkText);
				$this->_te->assignBlockVars($linkBlock, $data, 3 );
			}
    }
	}


	/**
	 * Возвращает формат даты или пустую строку, если показ даты выключен
	 *
	 * @return string
	 */
	function _getDateFormat()
	{
	  $format = !empty($this->_conf['displayDate'])
      ? (EL_NEWS_DATE == $this->_conf['displayDate'] ? EL_DATE_FORMAT : EL_DATETIME_FORMAT )
      : '';
    return $format;
	}

	/**
	 * Возращает текст ссылки на подробное содержание новости
	 * или пустую строку, если показ ссылки выключен
	 *
	 * @return string
	 */
	function _getDetailLinkText()
	{
	  if ( !empty($this->_conf['displayDetailLink']) )
	  {
	    if (!empty($this->_conf['detailLinkText']) )
	    {
	      return $this->_conf['detailLinkText'];
	    }
	    return m('Read more');
	  }
    return '';
	}



}
?>