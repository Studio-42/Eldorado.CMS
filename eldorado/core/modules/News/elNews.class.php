<?php

class elNews extends elMemberAttribute
{
  var $ID        = 0;
  var $title     = '';
  var $content   = '';
  var $publishTs = 0;
  var $announce  = '';
  var $expParam  = '';
  var $_objName  = 'News';

  //**************************************************************************************//
  // *******************************  PUBLIC METHODS  *********************************** //
  //**************************************************************************************//
  /**
   * Проверяет пустой или нет контент
   *
   * @return unknown
   */
  function hasContent()
  {
    return (bool)$this->content;
  }

  /**
   * Возвращает контент новости, если контент пустой  возвращает аннонс
   *
   * @return string
   */
  function getContent()
  {
    return !empty($this->content) ? $this->content : $this->announce;
  }

  function makeForm()
  {
    parent::makeForm();
    $this->form->add( new elDateSelector('published', m('Date'), $this->publishTs, null, 1, 0, true) );
    $this->form->add( new elText(  'title',        m('Title'),    $this->title,    array('style'=>'width:100%')) );
    $this->form->add( new elEditor('announce',     m('Announce'), $this->announce, array('rows'=>22), false, array('tbl'=>0)) );
    $this->form->add( new elEditor('content',      m('Content'),  $this->content) );
    $this->form->add( new elText(  'export_param', m('Export parameter'), $this->expParam, array('style'=>'width:100%')) );
    $this->form->setRequired('announce');
  }

  //**************************************************************************************//
  // =============================== PRIVATE METHODS ==================================== //
  //**************************************************************************************//

  function _initMapping()
  {
    $map = array(
      'id'           => 'ID',
      'announce'     => 'announce',
      'title'        => 'title',
      'content'      => 'content',
      'published'    => 'publishTs',
      'export_param' => 'expParam');
    return $map;
  }

}
?>