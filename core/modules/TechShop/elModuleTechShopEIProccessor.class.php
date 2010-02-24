<?php

require_once 'elModuleEIProccessor.class.php';
require_once(EL_DIR_CORE.'lib/elTreeNode.class.php');

class elModuleTechShopEIProccessor extends elModuleEIProccessor
{
  var $_db             = null;
  var $_factory        = null;
  var $_fieldsSearchIn = array('announce', 'descrip');
  var $_parser         = null;
  var $_tag            = '';
  var $_tb             = '';
  var $tbAi            = '';
  var $tbAiType        = '';
  var $_tmp            = null;

  function init(&$module)
  {
    $this->_module  = &$module;
    $this->_factory = &elSingleton::getObj('elTSFactory');
    $this->_db      = & elSingleton::getObj('elDb');
    $this->_db->supressDebug = 1;
  }


  function export($param)
  {
    $ret  = "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>\n";
    $ret .= "<exportData>\n";
    $ret .= "<baseURL><![CDATA[".EL_BASE_URL."]]></baseURL>\n";

    $param = trim($param);
    if (!empty($param))
    {
      $mnfIDs = explode(',', $param); 
      for ($i=0, $s=sizeof($mnfIDs); $i<$s; $i++ )
      {
        if (0>= $mnfIDs[$i])
        {
          unset($mnfIDs[$i]);
        }
      }
      if (empty($mnfIDs))
      {
        return $ret."<ERROR>Invalid export parameter</ERROR>\n</exportData>\n";
      }
      $sql = 'SELECT id FROM '.$this->_factory->tb('tbmnf').' WHERE id IN ('.implode(',', $mnfIDs).')';
      $mnfIDs = $this->_db->queryToArray($sql, 'id', 'id');
      if (empty($mnfIDs))
      {
        return $ret."<ERROR>Empty result, check export parameter</ERROR>\n</exportData>\n";
      }

      // manufacts
      $sql = 'SELECT id, name, country, logo_img, logo_img_mini, announce, descrip, sort_ndx, url FROM '
      .$this->_factory->tb('tbmnf').' WHERE id IN ('.implode(',', $mnfIDs).') ORDER BY id';
      $ret .= $this->_tbToXml('tbmnf', $sql, 'id', 'int(3)');

      // items
      $sql = 'SELECT id, mnf_id, code, name, announce, descrip, crtime FROM '.$this->_factory->tb('tbi')
      .' WHERE mnf_id IN ('.implode(',', $mnfIDs).') ORDER BY id';
      $ret .= $this->_tbToXml('tbi', $sql, 'id', 'int(3)');

      // items 2 cats
      $sql = 'SELECT i_id, c_id, sort_ndx FROM '.$this->_factory->tb('tbi2c').', '.$this->_factory->tb('tbi')
      .' WHERE mnf_id IN ('.implode(',', $mnfIDs).') AND i_id=id';
      $ret .= $this->_tbToXml('tbi2c', $sql);

      // categories
      $sql = 'SELECT DISTINCT p.id, p._left, p._right, p.level, p.name, p.descrip FROM '
      .$this->_factory->tb('tbc').'   AS p, '
      .$this->_factory->tb('tbc').'   AS ch, '
      .$this->_factory->tb('tbi2c').' AS rel, '
      .$this->_factory->tb('tbi').'   AS i '
      .'WHERE i.mnf_id IN ('.implode(',', $mnfIDs).') AND rel.i_id=i.id AND ch.id=rel.c_id AND '
      .'(ch._left BETWEEN p._left AND p._right)  ORDER BY p._left';
      $ret .= $this->_tbToXml('tbc', $sql, 'id', 'int(3)');

      // models
      $sql = 'SELECT m.id, m.i_id, m.code, m.name, m.descrip, m.img FROM '
      .$this->_factory->tb('tbm').' AS m, '
      .$this->_factory->tb('tbi').' AS i '
      .'WHERE i.mnf_id IN ('.implode(',', $mnfIDs).') AND m.i_id=i.id ORDER BY m.id';
      $ret .= $this->_tbToXml('tbm', $sql, 'id', 'int(4)');

      // features to items
      $sql = 'SELECT ft_id FROM '.$this->_factory->tb('tbft2i').', '.$this->_factory->tb('tbi')
      .' WHERE mnf_id IN ('.implode(',', $mnfIDs).') AND i_id=id';
      $ftiIDs = $this->_db->queryToArray($sql, '', 'ft_id');
      $sql = 'SELECT DISTINCT i_id, ft_id, value, is_announced FROM '.$this->_factory->tb('tbft2i').', '.$this->_factory->tb('tbi')
      .' WHERE mnf_id IN ('.implode(',', $mnfIDs).') AND i_id=id';
      $ret .= $this->_tbToXml('tbft2i', $sql);

      // features to models
      $sql = 'SELECT DISTINCT f.ft_id FROM '
      .$this->_factory->tb('tbft2m').' AS f, '
      .$this->_factory->tb('tbm').'    AS m, '
      .$this->_factory->tb('tbi').'    AS i '
      .'WHERE i.mnf_id IN ('.implode(',', $mnfIDs).') AND m.i_id=i.id AND f.m_id=m.id';
      $ftmIDs = $this->_db->queryToArray($sql, '', 'ft_id');
      $sql = 'SELECT f.m_id, f.ft_id, f.value, f.is_announced FROM '
      .$this->_factory->tb('tbft2m').' AS f, '
      .$this->_factory->tb('tbm').'    AS m, '
      .$this->_factory->tb('tbi').'    AS i '
      .'WHERE i.mnf_id IN ('.implode(',', $mnfIDs).') AND m.i_id=i.id AND f.m_id=m.id';
      $ret .= $this->_tbToXml('tbft2m', $sql);

      // features
      $ftIDs = array_merge($ftiIDs, $ftmIDs);
      unset($ftiIDs, $ftmIDs);
      if ( empty($ftIDs) )
      {
        $ftIDs = array(0);
      }

      $sql = 'SELECT id, gid, name, unit, sort_ndx FROM '.$this->_factory->tb('tbft')
      .' WHERE id IN ('.implode(',', $ftIDs).') ORDER BY id';
      $ret .= $this->_tbToXml('tbft', $sql, 'id', 'int(3)');

      // features groups
      $sql = 'SELECT DISTINCT g.id, g.name, g.sort_ndx FROM '
      .$this->_factory->tb('tbftg').' AS g, '
      .$this->_factory->tb('tbft').' AS f '
      .'WHERE f.id IN ('.implode(',', $ftIDs).') AND g.id=f.gid '
      .'ORDER BY g.id';
      $ret .= $this->_tbToXml('tbftg', $sql, 'id', 'int(3)');

      unset($ftIDs);
    }
    else
    {
      // categories
      $sql = 'SELECT id, _left, _right, level, name, descrip FROM '.$this->_factory->tb('tbc').' ORDER BY id';
      $ret .= $this->_tbToXml('tbc', $sql, 'id', 'int(3)');

      // manufacts
      $sql = 'SELECT id, name, country, logo_img, logo_img_mini, announce, descrip, sort_ndx, url FROM '
      .$this->_factory->tb('tbmnf').' ORDER BY id';
      $ret .= $this->_tbToXml('tbmnf', $sql, 'id', 'int(3)');

      // items
      $sql = 'SELECT id, mnf_id, code, name, announce, descrip, crtime FROM '.$this->_factory->tb('tbi').' ORDER BY id';
      $ret .= $this->_tbToXml('tbi', $sql, 'id', 'int(3)');

      // items 2 cats
      $sql = 'SELECT i_id, c_id, sort_ndx FROM '.$this->_factory->tb('tbi2c');
      $ret .= $this->_tbToXml('tbi2c', $sql);

      // models
      $sql = 'SELECT id, i_id, code, name, descrip, img FROM '.$this->_factory->tb('tbm').' ORDER BY id';
      $ret .= $this->_tbToXml('tbm', $sql, 'id', 'int(4)');

      // features groups
      $sql = 'SELECT id, name, sort_ndx FROM '.$this->_factory->tb('tbftg').' ORDER BY id';
      $ret .= $this->_tbToXml('tbftg', $sql, 'id', 'int(3)');

      // features to items
      $sql = 'SELECT i_id, ft_id, value, is_announced FROM '.$this->_factory->tb('tbft2i');
      $ret .= $this->_tbToXml('tbft2i', $sql);

      // features
      $sql = 'SELECT id, gid, name, unit, sort_ndx FROM '.$this->_factory->tb('tbft').' ORDER BY id';
      $ret .= $this->_tbToXml('tbft', $sql, 'id', 'int(3)');

      // features to models
      $sql = 'SELECT m_id, ft_id, value, is_announced FROM '.$this->_factory->tb('tbft2m');
      $ret .= $this->_tbToXml('tbft2m', $sql);
      //fwrite($fp, $this->_tbToXml('tbft2m', $sql));
    }
    $ret .= "</exportData>\n";

    return $ret;
  }


  function import()
  {
    $fp = $this->_getRemoteFile($this->_getImportURL(), true);

    $this->_parser = xml_parser_create();
    xml_set_object($this->_parser, $this);
    xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 1);
    xml_set_element_handler($this->_parser, "_tagOpen", "_tagClose");
    xml_set_character_data_handler($this->_parser, "_cdata");

    while (!feof($fp))
    {
      if ( !xml_parse($this->_parser, fgets($fp)) )
      {
        $err = xml_get_error_code($this->_parser).' '.xml_error_string( xml_get_error_code($this->_parser) )
              .' in line: '.xml_get_current_line_number($this->_parser);
        return elThrow(E_USER_ERROR, 'Import error! Could not get data from %s. Error: %s', array($this->_getImportURL(), $err));
      }
    }
    fclose($fp);
    xml_parser_free($this->_parser);
    unset($this->_parser);
    $this->_fixTbCatIndexes();
    return true;
  }


  /***************************************************/
  //             PRIVATE METHODS                     //
  /***************************************************/

  function _tbToXml($tbName, $sql, $ai=null, $aiType=null)
  {
    if (!$this->_db->query($sql))
    {
      die(mysql_error());
    }

    if ($this->_db->numRows())
    {
      $attrs = '';
      if ($ai)
      {
        $attrs = 'ai="'.$ai.'"'.(!empty($aiType) ? ' ai_type="'.$aiType.'"' : '');
      }
      $ret .= "<table name='".$tbName."' ".$attrs.">\n";
      while ($r = $this->_db->nextRecord())
      {
        $ret .= "<record>\n";
        foreach ($r as $k=>$v)
        {
          $ret .= '<'.$k.'><![CDATA['.$v." ]]></".$k.">\n";
        }
        $ret .= "</record>\n";
      }
      $ret .= "</table>\n";
    }
    return $ret;
  }

  function _tagOpen($parser, $name, $attrs)
  {
    if ('BASEURL' == $name)
    {
      $this->_tag = $name;
    }
    elseif ('TABLE' == $name)
    {
      $this->_tb = $this->_factory->tb($attrs['NAME']);// echo $attrs['NAME'].' = '.$this->_tb.'<br>';

      if (!empty($this->_tb))
      {
        $this->_tbAi     = !empty($attrs['AI']) ? $attrs['AI'] : '';
        $this->_tbAiType = !empty($attrs['AI_TYPE']) ? $attrs['AI_TYPE'] : '';

        $this->_db->query('TRUNCATE TABLE '.$this->_tb);

        if (!empty($this->_tbAi) && !empty($this->_tbAiType))
        {
          //echo $this->_tb. " - <br>";
          $this->_db->query('ALTER TABLE '.$this->_tb.' CHANGE '.$this->_tbAi.' '.$this->_tbAi.' '.$this->_tbAiType.' NOT NULL');
        }
      }
    }
    elseif ('RECORD' == $name)
    {
      $this->_tmp = array();
    }
    elseif ('EXPORTDATA' != $name)
    {
      $this->_tag = $name;
    }
  }

  function _tagClose($parser, $name)
  {
    $this->_tag = '';
    if ('RECORD' == $name && null<>$this->_tb )
    {
      $sql = 'INSERT INTO '.$this->_tb.' ('.implode(',', array_keys($this->_tmp)).') VALUES (';
      foreach ($this->_tmp as $k=>$v)
      {
        $v = trim($v);
        if (in_array($k, $this->_fieldsSearchIn))
        {
          $this->_searchForFiles($v);
        }
        elseif ('img' == $k && !empty($v))
        {
          $this->_copyFile($v);
          $this->_copyFile( dirname($v).'/mini_'.basename($v) );
        }
        $sql .= '"'.mysql_real_escape_string($v).'",';
      }
      $this->_tmp = array();
      $this->_db->query(substr($sql, 0, -1).')');

    }
    elseif ('TABLE' == $name)
    {
      if (!empty($this->_tb) && !empty($this->_tbAi) && !empty($this->_tbAiType))
      {
        $this->_db->query('ALTER TABLE '.$this->_tb.' CHANGE '.$this->_tbAi.' '.$this->_tbAi.' '.$this->_tbAiType.' NOT NULL auto_increment');
      }
    }
  }

  function _cdata($parser, $data)
  {
    if ('BASEURL' == $this->_tag)
    {
      $this->_sourceBaseURL = $data;
    }
    elseif ( !empty($this->_tag) )
    {
      $k = strtolower($this->_tag);
      if (!isset($this->_tmp[$k]))
      {
        $this->_tmp[$k] = '';
      }
      $this->_tmp[$k] .= $data;
    }
  }


  function _fixTbCatIndexes()
  {
    $this->_db->query('SELECT id, _left, _right, level FROM '.$this->_factory->tb('tbc').' ORDER BY _left');

    while ($r = $this->_db->nextRecord())
    {
      if (!isset($rootNode))
      {
        $rootNode = & new elTreeNode($r['id'], $r['_left'], $r['_right'], $r['level']);
      }
      else
      {
        if ( !$rootNode->addChild(new elTreeNode($r['id'], $r['_left'], $r['_right'], $r['level'])) )
        {
          elThrow(E_USER_ERROR, 'Could not fix tree indexes in category table. Data is wrong!', null, EL_URL);
        }
      }
    }

    $rootNode->fixIndexes();
    $indexes = $rootNode->getIndexes();
    foreach ($indexes as $ndx)
    {
      $sql = 'UPDATE '.$this->_factory->tb('tbc').' SET _left='.$ndx[1].', _right='.$ndx[2].' WHERE id='.$ndx[0];
      $this->_db->query($sql);
    }
  }

}



?>