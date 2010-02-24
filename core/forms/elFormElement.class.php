<?php

/**
 * Базовый класс для библиотеки elForms 
 * Based on composition pattern
 */

class elFormElement
{
  /**
   * @param	array	массив аттрибутов эл-та
   */
  var $attrs = array();
  /**
   * @param	string	строка-метка для эл-та
   */
  var $label = '';
  /**
   * @param	string	уникальный id эл-та (?????)
   */
  var $_id = '';

  var $events = array();

  function __construct($name=null, $label=null, $attrs=null)
  {
  
    $this->setName($name);
    $this->setLabel($label);
    $this->_generateID();
    if (is_array($attrs) )
    {
      $this->setAttrs($attrs);
    }

  }

  function elFormElement($name=null, $label=null, $attrs=null)
  {
  
    $this->__construct($name, $label, $attrs); 
  }

  // ----- get methods ----- //

  /**
   * возвращает ID эл-та
   */
  function getID()
  {
    return $this->_id;
  }

  function event( $event, &$obj )
  {
    if ( isset( $this->events[$event]) && method_exists($this, $this->events[$event] )  )
    {
      $args = func_num_args() > 2 ? array_slice(func_get_args(), 2) : array();
      return $this->{$this->events[$event]}($obj, $args);
    }
  }

  /**
   * возвращает аттрибут по его имени (или null)
   */
  function getAttr($attr)
  {
    return isset($this->attrs[$attr]) ? $this->attrs[$attr] : null;
  }

  /**
   * возвращает имя эл-та
   */
  function getName()
  {
    return $this->getAttr('name');
  }

  /**
   * возвращает label эл-та
   */
  function getLabel()
  {
    return $this->label;
  }

  /**
   * возвращает значение эл-та (default действие - для эл-тов input)
   */
  function getValue() {}

  /**
   * возвращает аттрибуты эл-та в виде строки
   */
  function attrsToString()
  {
    $str = ''; 
    foreach ($this->attrs as $k=>$v)
    {
      $str .= ' '.$k .'="'.htmlspecialchars($v).'"';
    }
    return $str;
  }

  /**
   * возвращает html представление эл-та
   */
  function toHtml()
  {
    return '';
  }

  // ----- set methods ----- //

  /**
   * устанавливает имя эл-та
   */
  function setName($name)
  {
    $this->setAttr('name', $name);
  }

  /**
   * устанавливает label эл-та
   */
  function setLabel($label)
  {
    $this->label = $label;
  }

  /**
   * устанавливает значение эл-та
   */
  function setValue($value) { }

  /**
   * устанавливает значение аттрибута
   */
  function setAttr($name, $val)
  {
    $this->attrs[$name] = $val;
    if ( 'name' == $name )
    {
      $this->setAttr('ID', $val);
    }
  }

  /**
   * удаляет аттрибут
   */
  function dropAttr($attr)
  {
    if ( isset($this->attrs[$attr]) )
    {
      unset($this->attrs[$attr]);
    }
  }

  /**
   * устанавливает значения аттрибутов из массива
   */
  function setAttrs($attrs)
  {
    foreach ( $attrs as $n=>$v)
    {
      $this->setAttr($n, $v);
    }
  }

  //------- private -------------//
  function _generateID()
  {
    $this->_id = md5($this->getAttr('name'));
  }

}

?>