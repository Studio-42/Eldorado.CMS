<?php
/**
 * Объект - лицензия на обновление
 *
 */
class elUpdServLicense extends elMemberAttribute
{
	var $ID       = 0;
	var $key      = '';
	var $URL      = '';
	var $expire   = 0;
	var $_objName = 'License';
	var $tb       = 'el_license';

	/**
	 * Извлекает из БД лицензию по ее ключу
	 *
	 * @param  string
	 * @return bool
	 */
	function fetchByKey( $key )
	{
	  $db  = & elSingleton::getObj('elDb');
	  $sql = 'SELECT '.implode(',', $this->listAttrs())
  	  .' FROM '.$this->tb.' WHERE '
	    .' lkey=\''.mysql_escape_string($key).'\' AND expire>UNIX_TIMESTAMP(NOW())';
	  $db->query( $sql );
	  if ( 1 <> $db->numRows() )
	  {
	    return false;
	  }
	  $this->setAttrs( $db->nextRecord() );
	  return true;
	}

	/**
	 * Продлевает лицензию на 1 год
	 *
	 */
	function prolong()
	{
	  $db  = & elSingleton::getObj('elDb');
	  $sql = 'UPDATE '.$this->tb.' SET expire = unix_timestamp(from_unixtime(expire) + INTERVAL 1 YEAR) '
	   .'WHERE id = "'.$this->ID.'"';
	  $db->query($sql);
	}

	/**
	 * Создает форму для редактирования аттрибутов объекта
	 *
	 */
	function makeForm()
	{
		parent::makeForm();
		$this->form->add(new elText('lkey', m('License key'), $this->key));
		$this->form->add(new elText('url',  m('Site URL'),    $this->URL));
		$expire = $this->ID ? $this->expire : time()+60*60*24*365;
		$this->form->add(new elDateSelector('expire', m('Expire date'), $expire));
		$this->form->setRequired('lkey');
		$this->form->setElementRule('url', 'http_url');
	}


	/***************************************************************/
	/*                     PRIVATE METHODS                         */
	/***************************************************************/

	/**
	 * Проверяет форму - ключ лицензии и URL должны быть уникальными
	 *
	 * @return bool
	 */
	function _validForm()
	{
	  $data = $this->form->getValue();
	  $db   = & elSingleton::getObj('elDb');
	  $sql  = 'SELECT id FROM '.$this->tb.' WHERE lkey=\''.mysql_escape_string($data['lkey']).'\' AND id<>'.intval($this->ID);
	  $db->query($sql);
	  if ( $db->numRows() )
	  {
	    return $this->form->pushError( 'lkey', m('Record with the same key already exists'));
	  }

	  if ('/' == substr($data['url'], -1, 1))
    {
      $data['url'] = substr($data['url'], 0, -1);
    }
    $sql = 'SELECT id FROM '.$this->tb.' WHERE url=\''.mysql_escape_string($data['url']).'\' AND id<>'.intval($this->ID);
	  $db->query($sql);
	  if ( $db->numRows() )
	  {
	    return $this->form->pushError( 'url', m('Record with the same URL already exists'));
	  }
	  return true;
	}

	/**
	 * Возвращает массив атрибутов для сохранения в БД
	 * Удаляет последний слэш из URL
	 * обрабатывает аттрибуты mysql_real_escape_string()
	 *
	 * @return array
	 */
	function _attrsForSave()
	{
	  $attrs = $this->getAttrs();
	  if ('/' == substr($attrs['url'], -1, 1))
    {
      $attrs['url'] = substr($attrs['url'], 0, -1);
    }
	  return array_map('mysql_real_escape_string',  $attrs );
	}

	function _initMapping()
 	{
    	return array(
			'id'     => 'ID',
			'lkey'   => 'key',
			'url'    => 'URL',
			'expire' => 'expire'
		);
	}
}

?>