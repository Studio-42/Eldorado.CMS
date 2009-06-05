<?php

class elFormContainer extends elFormElement
{
  var $parent = null;
  var $childs = array();
  var $events = array('addToForm' => 'onAddToForm');
  var $tpl    = array(
                    'header'  => '<div%s>',
                    'label'   => '%s',
                    'element' => '%s%s',
                    'footer'  => '</div>'
                  );

  function add( &$el )
  {
    $this->childs[] = &$el;
    if ( $this->parent )
    {
      $parent = &$this->parent;
      do
      {
        if ('elform' == get_class($parent) )
        {
          $el->event('addToForm', $parent);
          return;
        }
      }
      while ( NULL != ($parent = &$parent->getParent()) );
    }
    return $el->getID();
  }

  function onAddToForm( &$form )
  {
    $this->parent = & $form;
    foreach ( $this->childs as $id=>$child )
    {
      $this->childs[$id]->event( 'addToForm', $form );
    }
  }

  function &getParent()
  {
    return $this->parent;
  }

  function setTpl($tpl, $str)
  {
    if ( isset($this->tpl[$tpl]) )
    {
      $this->tpl[$tpl] = $str;
    }
  }

  function toHtml()
  {
    $html = sprintf($this->tpl['header'], $this->attrsToString());
    if ( $this->label )
    {
      $html .= sprintf($this->tpl['label'], $this->label);
    }
    foreach ( $this->childs as $child )
    {
      $html .= sprintf($this->tpl['element'], $child->getLabel(), $child->toHtml() );
    }
    $html .= $this->tpl['footer'];
    return $html;
  }

}




class elFormTreeContainer extends elFormContainer
{
  var $_nodeID = 0;
  var $_left  = 0;
  var $_right = 0;
  var $_level = 0;
  var $_itemsName = '';
  var $_displayChilds = true;
  var $_item  = null;
  var $tpl =
    '<div style="clear:both;cursor:default;padding:1px 0 0 0;">
    <div onClick="elFormTreeContainer(\'%s\')">
    <div id="%s_ctr" style="float:left;width:11px;border:1px solid grey;background:#eaeaea;text-align:center;padding:0 2px;font-size:12px;font-weight:bold">%s</div>
    <div style="padding:4px;padding-left:20px">%s</div>
    </div>
    <div id="%s_childs" style="display:%s;border-left:1px solid grey;padding-left:15px">%s</div>
    </div>';




  function elFormTreeContainer($name, $label, $tree, $itemsName=null, $attrs=null, $displayChilds=true)
  {
    $this->setName($name);
    $this->setLabel($label);
    $this->_itemsName = !empty($itemsName) ? $itemsName : $name;
    $this->_displayChilds = $displayChilds;
    $this->_generateID();
    if (is_array($attrs) )
    {
      $this->setAttrs($attrs);
    }

    if (!empty($tree) && is_array($tree))
    {
      $this->setTree($tree);
    }
  }


  function setTree( $tree )
  {
    //echo 'set tree';
    if ( empty($this->_nodeID) )
    {
      $first = array_shift($tree);
      $this->_nodeID = $first['id'];
      $this->_left = $first['_left'];
      $this->_right = $first['_right'];
      $this->_level = $first['level'];
      if (!empty($first['items']))
      {
        //echo $this->getAttr('name').'<br>'; elPrintR($first['items']);
        $this->_item = & new elCheckBoxesGroup($this->_itemsName.'['.$this->_nodeID.']', '', $first['vals'], $first['items']);
       }
    }
    while ( false != ($one=array_shift($tree)))
    {
      //echo $one['name'].' size='.sizeof($tree).'<br>';

      $node =  &new elFormTreeContainer($this->getAttr('name').'_'.$one['id'], $one['name'], array($one), $this->_itemsName, null, false);
      //echo $node->label.'<br>';
      $this->add( $node );
    }
    //`elPrintR($this);
  }



  function add( &$node )
  {
    if ($this->_left < $node->_left && $node->_right < $this->_right && $node->_level == $this->_level+1)
    {
      $this->childs[] =  &$node;
      return true;
    }
    foreach ( $this->childs as $ID=>$one )
    {
      if ($this->childs[$ID]->add( $node))
      {
        return true;
      }
    }
    return false;
  }

  function onAddToForm( &$form )
  {
    $this->parent = & $form;
    if (! empty($this->_item) )
    {
      $this->_item->event( 'addToForm', $form );
    }
    foreach ( $this->childs as $id=>$child )
    {
      $this->childs[$id]->event( 'addToForm', $form );
    }
    $js = 'function elFormTreeContainer(ID)
      {
        d = document.getElementById(ID+"_childs");
        ctr = document.getElementById(ID+"_ctr");
        if ( d.style.display == "" )
        {
          d.style.display = "none";
          ctr.removeChild(ctr.firstChild);
          ctr.appendChild(document.createTextNode("+"));

        }
        else
        {
          d.style.display = "";
          ctr.removeChild(ctr.firstChild);
          ctr.appendChild(document.createTextNode("-"));
        }

        }';
    $form->addJsSrc($js);
  }

  function toHtml()
  {
    $html = '';
    foreach ($this->childs as $node)
    {
      $html .= $node->toHtml();
    }
    if (!empty($this->_item))
    {
      $html .= $this->_item->toHtml();
    }
    $id = $this->getAttr('name');
    if ($this->_displayChilds)
    {
      $d = 'block';
      $s = '-';
    }
    else
    {
      $d = 'none';
      $s = '+';
    }
    $html = sprintf($this->tpl, $id, $id, $s, $this->label, $id, $d, $html);
    return $html;
  }

}


?>