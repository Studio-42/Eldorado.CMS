<?php

class elMFElement extends elMemberAttribute
{
  var $ID       = 0;
  var $type     = 'comment';
  var $label    = '';
  var $value    = '';
  var $opts     = '';
  var $valid    = 'none';
  var $size     = 1;
  var $errorMsg = '';
  var $sort     = 1;
  var $_uniq    = 'fid';
  var $_objName = 'Элемент формы';

  function toFormElement()
  { 
    switch ( $this->type )
    {
      case 'text':
        return new elText($this->ID, $this->label, $this->value);
        break;

      case 'textarea':
        return new elTextArea($this->ID, $this->label, $this->value, array('rows' => 5));
        break;

      case 'select':
        $value = strstr($this->value, "\n") ? $this->_strToArray($this->value) : $this->value;
        return new elSelect($this->ID, $this->label, $value, $this->_strToArray($this->opts), null, null, false);
        break;

      case 'checkbox':
        return new elCheckBoxesGroup($this->ID, $this->label, $this->_strToArray($this->value),
        $this->_strToArray($this->opts), null, null, false);
        break;

      case 'radio':
        return new elRadioButtons($this->ID, $this->label, $this->value, $this->_strToArray($this->opts), null, null, false);
        break;

      case "date":
        if ( !empty($this->value) && preg_match('/[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}/', $this->value))
        {
          $d     = explode('.', $this->value);
          $value = mktime(0, 0, 0, $d[1],$d[0], $d[2]);
        }
        else
        {
          $value = null;
        }
        if ($this->opts )
        {
          $opts       = $this->_strToArray($this->opts);
          $offsetLeft  = isset($opts[0]) ? $opts[0] : 1;
          $offsetRight = isset($opts[1]) ? $opts[1] : 2;
          return new elDateSelector($this->ID, $this->label, $value, null, $offsetLeft, $offsetRight);
        }
        return new elDateSelector($this->ID, $this->label, $value);
        break;

      case 'file':
        $f = & new elFileInput($this->ID, $this->label);
        $f->setMaxSize($this->size);
        return $f;
        break;

      case 'captcha':
        return new elCaptcha($this->ID, m('Enter code from picture'));
        break;

      default:
        $value = 'comment' == $this->type
        ? $this->value
        : '<div style="font-weight:bold;text-align:center">'.$this->value.'</div>';
        return new elCData($this->ID, $value);
    }
  }

  function editAndSave( $params=null )
  {
    $this->makeForm( $params );
    if ( !$this->form->isSubmitAndValid() )
    {
      return false;
    }

    $data          = $this->form->getValue(); 
    $data['fsort'] = (int)$data['fsort'];
    $this->setAttrs( $data );

    $isComment = in_array( $this->type, array('comment', 'subtitle') );
    $isSelect  = in_array( $this->type, array('select', 'checkbox', 'radio') );
    if ( $isComment )
    {
      if ( empty($this->value) )
      {
        return $this->form->pushError('fvalue', sprintf(m('"%s" can not be empty'), m('Default value')));
      }
    }
    if ( $isSelect )
    {
      if ( empty($this->opts) )
      {
        return $this->form->pushError('fopts', sprintf(m('"%s" can not be empty'), m('Value variants')));
      }
      $this->setAttr('fvalid', $data['fvalid_sel']);
    }
    else
    {
      $this->setAttr('fvalid', $data['fvalid_all']);
    }
    if ('captcha' == $this->type)
    {
      $this->setAttr('flabel', m('Enter code from picture'));
    }
    return $this->save();
  }

  function makeForm()
  {
    parent::makeForm();
    $maxSortNdx = $this->countAll();

    if ($this->ID)
    {
      $this->form->setLabel( m('Modify element') );
    }
    else
    {
      $this->form->setLabel( m('Create new element') );
      $this->setAttr('fsort', ++$maxSortNdx);
    }
    $types = array(
                  'comment'  => m('Comment'),
                  'subtitle' => m('Sub-title'),
                  'text'     => m('Text field'),
                  'textarea' => m('Text area'),
                  'select'   => m('Drop-down menu'),
                  'checkbox' => m('Check-boxes group'),
                  'radio'    => m('Switch buttons group'),
                  'date'     => m('Date selector'),
                  'file'     => m('File upload field'),
                  'captcha'  => m('Spam protection').'. '.m('Captcha: image with code and input field')
                  );
    $valid = array(
                  'none'     => m('Any. May be empty.'),
                  'noempty'  => m('Any. Could not be empty.'),
                  'email'    => m('E-mail'),
                  'url'      => m('URL'),
                  'phone'    => m('Phone number'),
                  'numbers'  => m('Only numbers'),
                  'letters_or_space'  => m('Only letters')
                  );
    $validSel = array(
                      'none'   => m('Any. May be empty.'),
                      'noempty'=> m('Any. Could not be empty.'),
                    );
    $fileMaxSize = range(0, 10);

    unset($fileMaxSize[0]);
    $fileMaxSize[15] = 15;
    $fileMaxSize[20] = 20;
    $fileMaxSize[25] = 25;
    $fileMaxSize[30] = 30;
    $fileMaxSize[50] = 50;
    $fileMaxSize[75] = 75;
    $fileMaxSize[100] = 100;
    $sort     = range( 1, $maxSortNdx );
    $comValue = m('For drop-down menu, checkboxes and radio switch enter one value per line. For date selector enter date in format dd.mm.yyyy');
    $comOpts  = m('For drop-down menu, checkboxes and radio switch enter one value per line');
    $this->form->add( new elSelect('fsort',     m('Element position'),(int)$this->getAttr('fsort'), $sort, null, null, false) );
    $this->form->add( new elSelect('ftype',     m('Element type'),    $this->getAttr('ftype'), $types));
    $this->form->add( new elText('flabel',      m('Element name'),    $this->getAttr('flabel')));
    $this->form->add( new elCData('c_value',    $comValue));
    $this->form->add( new elTextArea('fvalue',  m('Default value'),   $this->getAttr('fvalue'), array('rows'=>7)) );
    $this->form->add( new elCData('c_opts',     $comOpts));
    $this->form->add( new elTextArea('fopts',   m('Value variants'),  $this->getAttr('fopts'), array('rows'=>7)));
    $this->form->add( new elSelect('fvalid_all',m('Value type'),      $this->getAttr('fvalid'), $valid) );
    $this->form->add( new elSelect('fvalid_sel',m('Value type'),      $this->getAttr('fvalid'), $validSel) );
    $this->form->add( new elText('ferror',      m('Error message'),   $this->getAttr('ferror')));
    $this->form->add( new elSelect('fsize',     m('Max file size (Mb)'),   $this->getAttr('fsize'), $fileMaxSize) );


	$f = '
		$("#ftype").change(function() {
			var r = $(this).parents("tr").eq(0);
			r.next("tr").show().nextAll("tr").hide();
			switch(this.value) {
				case "text":
					$("#row_c_value, #row_fvalue, #row_fvalid_all, #row_ferror").show();
					break;
				case "textarea":
					$("#row_c_value, #row_fvalue, #row_fvalid_sel, #row_ferror").show();
					break;
				case "file":
					$("#row_fvalid_sel, #row_ferror, #row_fsize").show();
					break;
				case "select":
				case "checkbox":
				case "radio":
				case "date":
					$("#row_c_value, #row_fvalue, #row_c_opts, #row_fopts, #row_fvalid_sel, #row_ferror").show()
					break;
				default:
					r.next("tr").show().nextAll("tr").hide();
			}
		}).trigger("change");
	
	';
    elAddJs($f, EL_JS_SRC_ONREADY);
  }


  function save()
  {
    $db       = & $this->_getDb();
    $sortNdxs = $db->queryToArray('SELECT fid, fsort FROM '.$this->tb.' ORDER BY fsort', 'fid', 'fsort');
    if ( !parent::save() )
    {
      return false;
    }
    $sortNdxs[$this->ID] = $this->sort;
    $i = 1;
    foreach ( $sortNdxs as $ID=>$ndx )
    {
      if ( $i == $this->sort )
      {
        $i++;
      }
      if ( $ID != $this->ID )
      {
        $db->query('UPDATE '.$this->tb.' SET fsort='.($i++).' WHERE fid=\''.intval($ID).'\'');
      }
    }
    return true;
  }

  //************************************************//
  //							PRIVATE METHODS										//
  //************************************************//

  function _initMapping()
  {
    $map = array( 'fid'    => 'ID',
    'ftype'  => 'type',
    'flabel' => 'label',
    'fvalue' => 'value',
    'fopts'  => 'opts',
    'fvalid' => 'valid',
    'fsize'  => 'size',
    'ferror' => 'errorMsg',
    'fsort'  => 'sort');
    return $map;
  }

  function _strToArray( $str )
  {
    $ret = explode("\n", str_replace("\r", '', $str));
    return $ret;
  }

}

?>