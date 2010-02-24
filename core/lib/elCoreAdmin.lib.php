<?php
// useful for fast creation of select fields
$GLOBALS['yn'] 			= array( m('No'), m('Yes'));
$GLOBALS['posLR']   = array(
														EL_POS_LEFT  => m('left'),
														EL_POS_RIGHT => m('right')
														);
$GLOBALS['posLRT']  = array(
														EL_POS_LEFT  => m('left'),
														EL_POS_RIGHT => m('right'),
														EL_POS_TOP   => m('top')
														);
$GLOBALS['posLRTB'] = array(
														EL_POS_LEFT   => m('left'),
														EL_POS_TOP    => m('top'),
														EL_POS_RIGHT  => m('right'),
														EL_POS_BOTTOM => m('bottom')
														);
$GLOBALS['posNLRTB'] = array(
														0             => m('No'),
														EL_POS_LEFT   => m('left'),
														EL_POS_TOP    => m('top'),
														EL_POS_RIGHT  => m('right'),
														EL_POS_BOTTOM => m('bottom')
														);

/**
 *
 **/
function elCheckUserUniqFields( $el, $errMsg, $UID )
{
  $field = $el->getName();
  if ( 'login' == $field )
    {
      $regexp = '/^[a-z0-9_\-\/]{3,25}$/i';
      $errMsg = m('"%s" must contain latin alfanum of underline from 3 till 25 chars');
      $errMsg2 = m('Login already exists');
    }
  else
    {
      $regexp = '/^[a-zA-Z0-9\._-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/';
      $errMsg = m('"%s" must contain valid email address');
      $errMsg2 = m('E-mail already exists');
    }

  $value = $el->getValue();
  if ( empty($value) || !preg_match($regexp, $value) )
    {
      return sprintf( $errMsg, $el->getLabel());
    }
  $ats = & elSingleton::getObj('elATS');
  $db = & $ats->getAuthDb();

  $sql = 'SELECT uid FROM el_user WHERE '.$field.'=\''.mysql_real_escape_string($value).'\' ';
  if ( $UID )
    {
      $sql .= 'AND uid!='.$UID;
    }
  $db->query($sql);
  if ( $db->numRows() )
    {
      return $errMsg2;
    }
}

function getPermName( $perm )
{
  $pNames = getPermNames();
  return isset($pNames[$perm]) ? $pNames[$perm] : m('Undefined');
}

function getPermNames()
{
  return array(EL_READ=>m('Read only'), EL_WRITE=>m('Read/write'), EL_FULL=>m('Full control'));
}

function elGetNavTree( $delim='', $rootName=null )
{
	$db   = & elSingleton::getObj('elDb');
	$name = null === $rootName ? 'name' : 'IF(id<>1, name, "'.mysql_real_escape_string($rootName).'") ';
	//$name = !$delim ? $name : 'CONCAT( REPEAT("'.$delim.'  ", level), '.$name.') AS name';
    $name = -1 != $rootName ? 'CONCAT( REPEAT("'.$delim.'  ", level), '.$name.') AS name' : 'CONCAT( REPEAT("'.$delim.'  ", level-1), '.$name.') AS name';
	$sql  = 'SELECT id, '.$name.'  FROM el_menu ORDER BY _left';
	return $db->queryToArray($sql, 'id', 'name');
}

/**
* recursively directory copy
*/
function elCopyTree( $src, $dst )
{
  $src = preg_replace('|/{1,}$|', '', $src);
  $dst = preg_replace('|/{1,}$|', '', $dst);
  if ( !is_dir($src) )
  {
    return elThrow(E_USER_ERROR, 'Directory %s does not exists', $src);
  }
  if ( !is_dir($dst) && !mkdir($dst) )
  {
    return elThrow(E_USER_ERROR, 'Could not create directory %s', $src);
  }

  $d = dir( $src );
  while ( $entr = $d->read() )
  {
    if ( '.' == $entr || '..' == $entr )
    {
      continue;
    }

    if ( is_dir($src.'/'.$entr) )
    {
      if ( !elCopyTree($src.'/'.$entr, $dst.'/'.$entr) )
      {
        return elThrow(E_USER_ERROR, 'Could not copy %s to %s!',  array($src.'/'.$entr, $dst.'/'.$entr) );
      }
    }
    else
    {
      if ( !copy($src.'/'.$entr, $dst.'/'.$entr) )
      {
        return elThrow(E_USER_ERROR, 'Could not copy %s to %s!', array($src.'/'.$entr, $dst.'/'.$entr));
      }
    }

  }
  $d->close();
  return true;
}
/**
* recursively directory delete
*/
function elRmdir( $dir, $excludeTop=false, $exclude=null, $force=false )
{
  if ( !is_dir($dir) )
  {
    return elThrow(E_USER_ERROR, 'Directory %s does not exists', $dir);
  }
  $d = dir( '/' ==substr($dir, -1, 1) ? substr($dir, 0, -1) : $dir );
  while ( $file = $d->read() )
  {
    $entr = $d->path.'/'.$file;
    if ( ('.' != $file && '..' != $file) && (empty($exclude) || !preg_match($exclude, $entr)) )
    {
      $m = is_dir($entr) ? 'elRmdir' : 'unlink';
      if ( !$m($entr) )
      {
        elThrow(E_USER_ERROR, 'Could not delete file "%s"', $entr);
        if ( !$force )
        {
          return;
        } 
      }
    }
  }
  $d->close();
  return $excludeTop ? true : rmdir($dir);
 }

?>