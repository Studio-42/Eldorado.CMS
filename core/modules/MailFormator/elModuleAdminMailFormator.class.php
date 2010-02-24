<?php
class elModuleAdminMailFormator extends elModuleMailFormator
{
   var $_mMapAdmin = array(
      'edit' => array('m' => 'editElement', 'ico' => 'icoEdit', 'l' => 'Create new element', 'g'=>'Actions'),
      'rm'   => array('m' => 'rmElement')
      );

	/**
	 * Create/edit form element
	 *
	 * @return void
	 */
   function editElement()
   {
      $element = & $this->_getElement();

      if ( !$element->editAndSave() )
      {
	 $this->_initRenderer();
	 return $this->_rnd->addToContent( $element->formToHtml() );
      }
      elMsgBox::put( m('Data saved') );
      elLocation(EL_URL);
   }

   /**
    * remove element if exists
    *
    */
   function rmElement()
   {
      $el = & $this->_getElement();
      if (!$el->getUniqAttr())
      {
	 elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array($el->getObjName(), $this->_arg()), EL_URL);
      }
      $el->delete();
      elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $el->getObjName(), $el->getAttr('flabel')));
      elLocation(EL_URL);
   }

	/**
	 * Render form with edit/delete elemnts ability
	 * overload parent method
	 *
	 */
	function _makeForm()
	{
		$this->_form = &elSingleton::getObj('elForm');
		$rnd = &elSingleton::getObj('elTplGridFormRenderer', 3);
		$this->_form->setRenderer($rnd);
		if ( false == ($label = $this->_conf('formLabel')))
		{
			$label = m('Send message');
		}
		$this->_form->setLabel( $label );
		if ( $this->_conf('selectRcpt') )
		{
			$this->_form->add( new elCData('rcpt_l', m('Select recipient')) );
			$this->_form->add( new elSelect('rcpt',  m('Select recipient'), null, $this->_rcptList));
			$this->_form->add( new elCData('dummy', '') );
		}
		$tpl = "<ul class=\"adm-icons\">"
			   ."<li><a href=\"".EL_URL."edit/%d/\" class=\"icons edit\" title=\"".m('Edit')."\"></a></li>"
			   ."<li><a href=\"".EL_URL."rm/%d/\" class=\"icons delete\"  title=\"".m('Delete')."\" onClick=\"return confirm('".m('Do You really want to delete ')."?');\"></a></li>"
			   ."</ul>";

		$tpl = '<table cellpadding="0" cellspacing="0"><tr>
			<td style="padding:0 1px"><a href="'.EL_URL.'edit/%d/" class="icons edit"  title="'.m('Edit').'"></a></td>
			<td style="padding:0 1px"><a href="'.EL_URL.'rm/%d/"   class="icons delete" title="'.m('Delete').'" onClick="return confirm(\''.m('Do You really want to delete ').'?\');"></a></td>
		</table>';

		foreach ($this->_fList as $el)
		{ 
			$edit = sprintf($tpl, $el->ID, $el->ID);
			if ( 'comment' == $el->type || 'subtitle' == $el->type )
			{
				$obj = $el->toFormElement();
				$cssClass = 'comment' == $el->type ? 'form-tb-cdata' : 'form-tb-sub';
				$this->_form->add( new elCData($el->ID, $obj->value ), array('colspan'=>2,  'class'=>$cssClass) );
				$this->_form->add( new elCData($el->ID.'_a', $edit),   array('width'=>'50', 'class'=>$cssClass,	'style'=>'white-space:nowrap') );
			}
			else
			{
				$this->_form->add( new elCData($el->ID.'_l', $el->label) );
				$this->_form->add( $el->toFormElement() );
				$this->_form->add( new elCData($el->ID.'_a', $edit), array('width'=>'50', 'style'=>'white-space:nowrap') );
			}
			if ( 'none' != $el->valid )
			{
				$this->_form->setElementRule($el->ID, $el->valid, true, null, $el->errorMsg);
			}
		}
		$rnd->addButton(new elSubmit('submit', null, m('Submit')) );
		$rnd->addButton(new elReset('reset', null, m('Drop') ) );
	}

	function &_makeConfForm()
	{
		$form = & parent::_makeConfForm();
		$form->add( new elText('formLabel',         m('Title text'),               $this->_conf('formLabel')) );
		$form->add( new elText('replyMsg',          m('Confirmation text'),        $this->_conf('replyMsg')) );
		$form->add( new elText('subject',           m('Subject'),                  $this->_conf('subject')) );
		$form->add( new elSelect('selectRcpt',      m('Allow select recipient'),   $this->_conf('selectRcpt'), $GLOBALS['yn']));
		$form->add( new elCheckBoxesGroup('rcptIDs',m('Recipients'),               array_keys($this->_rcptList), 																$this->_ec->getLabels()) );
		$form->setRequired('rcptIDs[]');
		return $form;
	}

	function _validConfForm(&$form)
	{
		$data = $form->getValue();
		if (empty($data['rcptIDs']))
		{
			return $form->pushError('rcptIDs[]', sprintf( m('"%s" can not be empty'), m('Recipients')));
		}
		$newConf = $data;
		$newConf['rcptIDs'] = array();
		foreach ($data['rcptIDs'] as $v)
		{
			$newConf['rcptIDs'][$v] = $v;
		}
		return $newConf;
	}

}

?>