<?php

define('EL_NEWS_DATE',     1);
define('EL_NEWS_DATETIME', 2);
define('EL_NEWS_COL_ONE',  0);
define('EL_NEWS_COL_TWO',  1);


class elModuleNews extends elModule
{
  var $_mMap = array('read'=>array('m'=>'newsDetail') );

  var $_conf = array(
    'newsOnPage'        => 10,
    'displayDate'       => 1,
    'displayDetailLink' => 1,
    'detailLinkText'    => '',
    'topicLinkText'     => '',
    'displayFormat'     => EL_NEWS_COL_ONE,
    );
  var $_tb         = '';
  var $_newsOnPage = 10;
  var $_pageNum    = 1;
  var $_total      = 0;


 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function defaultMethod()
  {
    $offset     = ($this->_pageNum-1)*$this->_newsOnPage;
    $totalPages = ceil($this->_total/$this->_newsOnPage);

    $news = & $this->_getNews();
    $coll = $news->collection(true, false, null, 'published DESC', $offset, $this->_newsOnPage);

    $this->_initRenderer();
    $this->_rnd->render( $coll, $totalPages );
  }

  function newsDetail()
  {
    $this->_curNews = & $this->_getNews();

    if ( !$this->_curNews->fetch() )
    {
      elThrow(E_USER_NOTICE, 'There are no one object "%s" with ID="%d"',
        array($this->_curNews->getObjName(), $this->_curNews->ID), EL_URL );
    }

    $this->_initRenderer();
    $this->_rnd->renderNewsDetails( $this->_curNews );
  }


 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _onInit()
  {
    $this->_tb = 'el_news_'.$this->pageID;
    $this->db = & elSingleton::getObj('elDb');
    $this->db->query('SELECT COUNT(*) AS total FROM '.$this->_tb );
    if ( $this->db->numRows() )
    {
      $r = $this->db->nextRecord();
      $this->_total = $r['total'];
    }
    if ( 0 < ($n = (int)$this->_conf('newsOnPage')) )
    {
      $this->_newsOnPage = $n;
    }
    $this->_curPageNum();
  }

  function _curPageNum()
  {
    $n = $this->_arg(0);
    if ( is_numeric($n) && $n > 0 && ($n-1)*$this->_newsOnPage <= $this->_total )
    {
      $this->_pageNum = (int)$n;
    }
  }

  function _initRenderer()
  {
    parent::_initRenderer();
    $this->_rnd->displayDate = $this->_conf('displayDate');
    $this->_rnd->setPageNum( $this->_pageNum );
  }

  function &_getNews()
  {
    $news = & elSingleton::getObj('elNews');
    $news->tb($this->_tb);
    $news->idAttr( (int)$this->_arg(1) );
    return $news;
  }

  function _onBeforeStop()
  {
    if ( !empty($this->_curNews) )
    {
      elAppendToPagePath( array('url'  => 'read/'.$this->_pageNum.'/'.$this->_curNews->ID,
                                'name' => $this->_curNews->title) );
      $mt = &elSingleton::getObj('elMetaTagsCollection'); 
      $mt->init($this->pageID, 0, $this->_curNews->ID);
    }
  }
}

?>
