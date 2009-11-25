<?php

class elModuleAdminNews extends elModuleNews
{
  var $_mMapAdmin = array(
        'edit' => array('m'=>'edit', 'ico'=>'icoNewsNew', 'l'=>'Create news', 'g'=>'Actions'),
        'rm'   => array('m'=>'rm')
         );

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function edit()
  {
    $news = & $this->_getNews();
    if ( !$news->fetch() )
    {
      $this->_pageNum = 1;;
    }
    if ( !$news->editAndSave() )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $news->formToHtml() );
    }
    else
    {
      elMsgBox::put( m('Data saved') );
      $fromItem = $this->_arg(2);
      $URL = $fromItem ? 'read/'.$this->_pageNum.'/'.$news->ID : $this->_pageNum;
      elActionLog($news, false, $URL, $news->title);
      elLocation( EL_URL.$URL );
    }
  }

  function rm()
  {
    $news = & $this->_getNews();
    if ( !$news->fetch() )
    {
      elThrow(E_USER_NOTICE, 'There are no one object "%s" with ID="%d"',
        array( $news->getObjName(), $news->ID), EL_URL );
    }
    $news->delete();
    elMsgBox::put( sprintf( m('Object "%s" "%s" was deleted'), $news->getObjName(), $news->title) );
	elActionLog($news, 'delete', false, $news->title);
    elLocation( EL_URL.$this->_pageNum );
  }

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function &_makeConfForm()
  {
    $cFormat  = array(EL_NEWS_COL_ONE=>m('One column'), EL_NEWS_COL_TWO=>m('Two columns'));
    $dFormats = array(0=>m('No'), EL_NEWS_DATE=>m('Date only'), EL_NEWS_DATETIME=>m('Date and time'));
    $nums     = range(5,100);

    $form = &parent::_makeConfForm();
    $form->add( new elSelect('displayFormat',m('News topics list format'), (int)$this->_conf('displayFormat'), $cFormat ) );
    $form->add( new elSelect('newsOnPage',   m('Numbers of news on page'), $this->_newsOnPage, $nums , false, false, false));
    $form->add( new elSelect('displayDate',  m('Display news dates'), $this->_conf('displayDate'), $dFormats) );
    $form->add( new elSelect('displayDetailLink', m('Display link to news content'), $this->_conf('displayDetailLink'), $GLOBALS['yn']) );
    $form->add( new elText('detailLinkText', m('Link to new content text'),    $this->_conf('detailLinkText')) );
    $form->add( new elText('topicLinkText',  m('Link to topics list text'), $this->_conf('topicLinkText')) );
    return $form;
  }
}

?>