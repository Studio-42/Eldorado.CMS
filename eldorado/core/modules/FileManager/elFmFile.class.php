<?php

class elFmFile
{
  var $hash   = '';
  var $path   = '';
  var $type   = '';
  var $childs = array();
  var $perms  = 0;
  var $stat   = array();
  var $_load  = 0;

  function elFmFile( $path )
  {
    $this->path = realpath($path);
    $this->hash = md5($path);

    if ( is_dir($this->path) )
    {
      $this->type = 'd';
      if ( is_readable($this->path) && is_executable($this->path) )
      {
        $this->perms = is_writable($path) ? 2 : 1;
      }
    }
    elseif ( is_file($path) )
    {
      $this->type = 'f';
      if ( is_readable($this->path) )
      {
        $this->perms = is_writable($path) ? 2 : 1;
      }
    }
  }

  /**
   * read dir content // 1- only dirs, 2 -all
   */
  function open( $l=1 )
  {
    if ( $this->_load >= $l || !$this->perms || 'f' == $this->type )
    {
      return;
    }

    $this->_load = $l;

    if ( false == ($d = dir( $this->path ) ) )
    {
      return $this->perms = 0;
    }

    while ( false != ($entr = $d->read()) )
    {
      if ( '.' != substr($entr, 0, 1) )
      {
        $path = $this->path.'/'.$entr;
        if ( (1 == $l && !is_dir($path)) || !empty($this->childs[md5($path)]) )
        {
          continue;
        }
        $child = & new elFmFile($this->path.'/'.$entr);
        if ( $child->type && $child->perms )
        {
          $this->childs[$child->hash] =  $child;
        }
      }
    }
    $d->close();
  }

  function isFile()
    {
      return 'f' == $this->type;
    }

  function isDir()
    {
      return 'd' == $this->type;
    }

  function &find( $hash, $type=null )
  {
    if ( $hash == $this->hash )
    {
      return $this;
    }
    if ( 'f' == $this->type )
    {
      return null;
    }

    $this->open(2);
    $retNull = null;
    if ( isset($this->childs[$hash]) )
    {
      return !$type || $type == $this->childs[$hash]->type ? $this->childs[$hash] : $retNull;
    }

    foreach ($this->childs as $h=>$c )
    {
      if ( 'd'== $c->type &&  null != ($child = &$this->childs[$h]->find($hash) ) )
	    {
	      return !$type || $type ==$c->type ? $child : $retNull;
	    }
    }
    return $retNull;
  }

  function &findByName($name, $type=null)
  {
    return $this->find( md5(realpath($name)), $type );
  }

  function getTree()
  {
    if ('f' == $this->type )
    {
      return null;
    }
    $this->open(1);
    $tree = array( $this->hash=>$this->path);

    foreach ($this->childs as $h=>$c )
    {
      if ('d' == $c->type )
      {
        $tree += $this->childs[$h]->getTree();
      }
    }
    natsort($tree);
    return $tree;
  }

  function getChilds()
  {
    $this->open(2);
    return $this->childs;
  }


  function getStat()
  {
    if ( $this->stat )
    {
      return $this->stat;
    }

    $this->stat = array(
                        'name' => basename($this->path),
                        'type' => $this->type,
                        'hash' => $this->hash,
                        'path' => $this->path,
                        );
    if ( 'd' == $this->type )
    {
      $this->stat['mime'] = 'dir';
      return $this->stat;
    }

    $this->stat['filesize'] = round( filesize($this->path)/1024, 2 );

    //if ( !function_exists('mime_content_type') || false == ( $this->stat['mime'] = mime_content_type($this->path)) )
    //{
      //$this->stat['mime'] = exec( 'file -b '.escapeshellarg($this->path) );
    $this->stat['mime'] = elMimeContentType($this->path); 
    //}

    if ( false !== ($p = strpos($this->stat['mime'], ';')) )
    {
      $this->stat['mime'] = substr($this->stat['mime'], 0, $p);
    }

    if (strstr($this->stat['mime'], 'image') && false != ($s = getimagesize($this->path)))
    {
      $this->stat['imgW'] = $s[0];
      $this->stat['imgH'] = $s[1];
    }
    return $this->stat;
  }

  /**
   * Create new subdirectory
   */
  function mkDir( $dir )
  {
    if ( !$this->_checkName($dir) )
    {
      elThrow(E_USER_WARNING,
              'Name "%s" contains illegal symbols. Only latin alfanum, underscore, dot and dash symbols are accepted',
              $dir );
      return false;
    }
    $path = 'd' == $this->type ? $this->path.'/' : dirname($this->path).'/';
    if ( file_exists($path.$dir) )
    {
      return elThrow(E_USER_WARNING, 'Could not create "%s"! Target already exists.', $dir);
    }
    if ( !mkdir($path.$dir, 0777))
    {
      return elThrow(E_USER_WARNING, m('Could not create directory %s'), $dir );
    }
    return true;
  }

  /**
   * Copy file
   * TO DO - add recursive directory copy
   */
  function copy( $target )
  {
    if ( !$this->isFile() )
    {
      elThrow(E_USER_WARNING, 'Could not copy %s to %s! Recursive directory copy does not supported yet!.',
              array(basename($this->path), $target) );
      return false;
    }
    $target = realpath($target).'/'.(basename($this->path));
    if ( file_exists($target) )
    {
      elThrow(E_USER_WARNING, 'Could not copy %s to %s! Target already exists.',
              array(basename($this->path), $target) );
      return false;
    }
    if ( !copy($this->path, $target) )
    {
      elThrow(E_USER_WARNING, 'Could not copy %s to %s!',
        array(basename($this->path), $target) );
      return false;
    }
    return true;
  }

  /**
   * Delete
   */
  function rm()
  {
    if ( 'd' == $this->type )
    {
      $this->open(2);
      if ( $this->childs )
      {
        return elThrow(E_USER_WARNING, m('Could not delete not empty directory %s'), basename($this->path) );
      }
      if ( 1 >= $this->perms )
      {
        return elThrow(E_USER_WARNING, m('Could not delete read-only directory %s'), basename($this->path) );
      }
      if ( !rmdir($this->path) )
      {
        return elThrow(E_USER_WARNING, m('Could not delete directory %s'), basename($this->path) );
      }
    }
    else
    {
      if ( 1 >= $this->perms )
      {
        return elThrow(E_USER_WARNING, m('Could not delete read-only file %s'), basename($this->path) );
      }
      if ( !unlink($this->path) )
      {
        return elThrow(E_USER_WARNING, m('Could not delete file %s'), basename($this->path) );
      }
    }
    return true;
  }

  function mv( $target )
  {
    return $this->copy($target) ? $this->rm() : false;
  }

  function renameFile( $newName )
  {
    $name = basename($this->path);
    if ( !$this->_checkName($newName) )
    {
      elThrow(E_USER_WARNING,
              m('Name "%s" contains illegal symbols. Only latin alfanum, underscore, dot and dash symbols are accepted'),
              $newName );
      return false;
    }
    if ( file_exists(dirname($this->path).'/'.$newName) )
    {
      return elThrow(E_USER_WARNING, m('Could not rename %s to %s! Target already exists.'), array($name, $newName) );
    }
    if ( $name == $newName )
    {
      return elThrow(E_USER_WARNING, m('Could not rename %s to %s!'), array($name, $newName) );
    }
    if ( !rename($this->path, dirname($this->path).'/'.$newName) )
    {
      return elThrow(E_USER_WARNING, m('Could not rename %s to %s!'), array($name, $newName) );
    }
    return true;
  }

  function _checkName( $name )
  {
    return preg_match('/^[a-z0-9_\-\.]{1,}$/i', $name);
  }

}


?>