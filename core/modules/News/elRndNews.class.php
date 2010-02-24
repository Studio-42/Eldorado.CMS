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
		    'title'   => $news->attr('title'),
		    'content'=> $news->getContent(),
		    'topicLinkText' => !empty($this->_conf['topicLinkText']) ? $this->_conf['topicLinkText'] : m('Go back to news topics')
		  );
		$this->_te->assignVars( $data );
		if (false != ($format = $this->_getDateFormat()))
		{
		  $this->_te->assignBlockVars('NEWS_DATE', array('date' => date($format, $news->attr('published'))));
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
			    'title'   => $one->attr('title'),
			    'announce'=> $one->attr('announce'),
				);
			$this->_te->assignBlockVars( 'NEWS_OC.NEWS', $data, 1 );
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('NEWS_OC.NEWS.NEWS_ADMIN', array('id' => $one->ID), 2);
			}
			if ( $dFormat )
			{
			  $this->_te->assignBlockVars('NEWS_OC.NEWS.DATE', array('date'=>date($dFormat, $one->attr('published'))), 2 );
			}
			if ( ($one->hasContent()) && $linkText )
			{
			  $data = array('id'=>$one->ID, 'detailLinkText'=>$linkText);
				$this->_te->assignBlockVars('NEWS_OC.NEWS.DETAIL', $data, 2 );
			}
		}
	}

	function _rndTwoColumns($newsList, $dFormat, $linkText)
	{
		$i =0;
		$rowCnt = 1;
		foreach ( $newsList as $one )
		{
			$data = array(
			    'id'      => $one->ID,
			    'title'   => $one->attr('title'),
			    'announce'=> $one->attr('announce'),
				'cssLastClass' => 'col-last',
				
				  );
			if (!($i++%2)) 
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == sizeof($newsList) ? 'invisible' : '');
				$this->_te->assignBlockVars('NEWS_TC', $var);
				$data['cssLastClass'] = '';
			}
				
			$this->_te->assignBlockVars('NEWS_TC.NEWS', $data, 1);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('NEWS_TC.NEWS.NEWS_ADMIN', array('id' => $one->ID), 2);
			}
			if ( $dFormat )
			{
			  $this->_te->assignBlockVars('NEWS_TC.NEWS.DATE', array('date'=>date($dFormat, $one->attr('published'))), 3 );
			}
			if ( ($one->hasContent()) && $linkText )
			{
			  	$data = array('id'=>$one->ID, 'detailLinkText'=>$linkText);
				$this->_te->assignBlockVars('NEWS_TC.NEWS.DETAIL', $data, 3 );
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

	function _setFile($h='', $t='', $whiteSpace=false)
  	{
		$tpl = isset($this->_tpls[$h]) ? $this->_tpls[$h] : $this->_defTpl;
	    $this->_te->setFile($t ? $t : 'PAGE', $this->_dir.$tpl, $whiteSpace );
	}

}
?>