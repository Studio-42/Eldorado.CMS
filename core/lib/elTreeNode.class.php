<?php

class elTreeNode
{
   var $id      = 0;
   var $name    = '';
   var $left    = 0;
   var $right   = 0;
   var $level   = 0;
   var $_childs = array();
   var $_items = array();
   var $_tplNode = '<div style="padding:3 3 3 10">%s<div>%s</div><div>%s</div></div>';
   var $_tplItem = '<input type="checkbox" name="i_%s" value="1" />%s<br />';

   function elTreeNode($id, $l, $r, $level, $name='', $items=null)
   {
      $this->id    = $id;
      $this->left  = $l;
      $this->right = $r;
      $this->level = $level;
      $this->name  = $name;
      if (is_array($items))
      {
        $this->_items = $items;
      }
   }

   function addChild(&$node)
   {
      if ($this->left < $node->left && $node->right < $this->right && $node->level == $this->level+1)
      {
         $this->_childs[] = &$node;
         return true;
      }
      foreach ($this->_childs as $i=>$n)
      {
         if ( $this->_childs[$i]->addChild($node))
         {
            return true;
         }
      }
      return false;
   }


   function fixIndexes($left=0)
   {
      //echo $this->id.'-->';
      $this->left = $left+1;
      $right      = $this->left;
      foreach ($this->_childs as $i=>$n)
      {
         $right = $this->_childs[$i]->fixIndexes($right);
      }
      $this->right = $right+1;
      return $this->right;
   }

   function getIndexes(  )
   {
      //$data[$this->id] = array($this->left, $this->right);
      $ret = array( array($this->id, $this->left, $this->right));
      foreach($this->_childs as $i=>$n)
      {
         //$this->_childs[$i]->getIndexes($data);
         $ret = array_merge_recursive($ret, $this->_childs[$i]->getIndexes());
      }
      return $ret;
   }

   function toHtml()
   {
      $html = '';
      foreach ($this->_childs as $ch)
      {
        $childs .= $ch->toHtml();
      }
      foreach ($this->_items as $iID=>$i)
      {
        $items .= sprintf($this->_tplItem, $iID, $i);
      }
      $html = sprintf($this->_tplNode, $this->name, $childs, $items);
      return $html;
   }

}

?>