<?php

class elEmailsCollection
{
  var $collection = array();
  var $defaultID  = 0;

  function elEmailsCollection()
  {
    $db = & elSingleton::getObj('elDb');
    $this->collection = $db->queryToArray('SELECT id, label, email FROM el_email ORDER BY id', 'id');

    if ( $this->collection )
    {
      $db->query('SELECT id FROM el_email WHERE is_default=\'1\' LIMIT 0,1');
      if ( $db->numRows() )
      {
        $row = $db->nextRecord();
        $this->defaultID = $row['id'];
      }
      else
      {
        $ids = array_keys($this->collection);
        $this->defaultID = $ids[0];
      }
    }
  }

  function getEmailByID( $id, $format=true )
  {
    return isset($this->collection[$id]) 
      ? $format 
        ? $this->formatEmail($this->collection[$id]['label'], $this->collection[$id]['email']) 
        : $this->collection[$id]['email']
      : $this->getDefault($format);
  }

  function getDefault($format=true)
  {
    if ( !isset($this->collection[$this->defaultID]) )
    {
      return 'undefined';
    }
    $addr = $this->collection[$this->defaultID];
    return $format ? $this->formatEmail($addr['label'], $addr['email']) : $addr['email'];
  }

  function formatEmail( $label, $email )
  {
    $addr = $label ? sprintf('"'.$label.'"<'.$email.'>') : $email;
    return $addr;
  }

  	function getLabels()
  	{
    	$ret = array();
	    foreach ( $this->collection as $id=>$addr )
	    {
	      $ret[$id] = htmlspecialchars($this->formatEmail($addr['label'], $addr['email']));
	    }
	    return $ret;
  	}

	function getByIDs($ids, $defaultOnFailed=true) {
		$ret = array();
		if (!is_array($ids)) {
			$ids = array();
		}
		foreach ($ids as $id) {
			if (isset($this->collection[$id])) {
				$ret[$id] = $this->formatEmail($this->collection[$id]['label'], $this->collection[$id]['email']);
			}
		}
		if (empty($ret) && $defaultOnFailed) {
			$ret[$this->defaultID] = $this->formatEmail($this->collection[$this->defaultID]['label'], $this->collection[$this->defaultID]['email']);
		}
		return  $ret;
	}

  function size()
  {
    return sizeof($this->collection);
  }
  
  function getLabel($ID)
  {
  	return isset($this->collection[$ID]) ? $this->collection[$ID]['label'] : '';
  }
  
  function isEmailExists( $ID )
  {
  	return isset($this->collection[$ID]);
  }

}

?>