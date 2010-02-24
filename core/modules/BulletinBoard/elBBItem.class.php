<?php

class elBBItem extends elMemberAttribute
{
	var $ID         = 0;
	var $catID      = 0;
	var $UID        = 0;
	var $title      = '';
	var $content    = '';
	var $phone      = '';
	var $email      = '';
	var $crtime     = 0;
	var $author     = '';
	var $published  = 0;
	var $_objName   = 'Message';
	
	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elText('title', m('Title'), $this->title));
		$this->form->add( new elTextArea('content', m('Content'), $this->content));
		$this->form->add( new elText('phone', m('Your phone number'), $this->phone));
		$this->form->add( new elText('email', m('Your E-mail'), $this->email));
		$this->form->setRequired('title');
		$this->form->setRequired('content');
		$this->form->setElementRule('phone', 'phone', false);
		$this->form->setElementRule('email', 'email', false);
	}
	
	function deleteFromCategory()
	{
		if ($this->catID)
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('DELETE FROM '.$this->tb.' WHERE cat_id='.$this->catID);
			$db->optimizeTable($this->tb);
		}
	}
	
	function publish()
	{
		if ($this->ID)
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('UPDATE '.$this->tb.' SET published=1 WHERE id="'.$this->ID.'" LIMIT 1');
		}
	}
	
	function _validForm()
	{
		$data = $this->form->getValue();
		return !$data['phone'] && !$data['email']
			? $this->form->pushError('phone', m('Please, enter Your phone number or E-mail'))
			: true;
	}
	
	function _attrsForSave()
	{
		$attrs = $this->getAttrs();
		$attrs['title']   = mysql_real_escape_string(strip_tags($attrs['title']));
		$attrs['content'] = mysql_real_escape_string(strip_tags($attrs['content']));
		$attrs['email']   = mysql_real_escape_string(strip_tags($attrs['email']));
		$attrs['phone']   = mysql_real_escape_string(strip_tags($attrs['phone']));
		if (!$this->ID)
		{
			$attrs['crtime'] = time();
		}
		return $attrs;
	}
	
	function _initMapping()
	{
		return array(
			'id'        => 'ID',
			'cat_id'    => 'catID',
			'uid'       => 'UID',
			'title'     => 'title',
			'content'   => 'content',
			'phone'     => 'phone', 
			'email'     => 'email',
			'author'    => 'author',
			'crtime'    => 'crtime',
			'published' => 'published'
			);
	}
	
}

?>