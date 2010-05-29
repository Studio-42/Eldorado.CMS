<?php

class elMsgBox
{
  var $queues = array( EL_MSGQ=>array(), EL_WARNQ=>array(), EL_DEBUGQ=>array() );
  var $_prefix = 'el_msgq';

  function elMsgBox( )
    {
      $labels = array_keys($this->queues);
      foreach ($labels as $label)
	{
	  $this->createQueue($label);
	}
    }

  function put($msg, $label=EL_MSGQ)
    {
      $mb = & elSingleton::getObj('elMsgBox');
      $mb->putMsg($msg, $label);
    }

  function createQueue( $label )
    {
      $k = $this->_prefix.$label; 
      $this->queues[$label] = isset($_SESSION[$k]) && is_array($_SESSION[$k])
	? $_SESSION[$k] : array();
    }

  function dropQueue( $label )
    {
      if ( $label && !isset($this->queues[$label]) )
	{
	  unset($this->queues[$label]);
	}
    }

  function listQueues()
    {
      return array_keys($this->queues);
    }
  
  function putMsg($msg, $label=EL_MSGQ)
    {
      if ( isset($this->queues[$label]) )
	{
		$key = crc32($msg);
		if (sizeof($this->queues[$label]) > 150)
		{
			$this->queues[$label] = array_slice($this->queues[$label], 0, 150);
		}
	  $this->queues[$label][$key] = $msg;
	}

    }

  function fetchMsg($label)
    {
      return isset($this->queues[$label]) && $this->queues[$label] 
	? array_shift($this->queues[$label]) : null;
    }

  function fetchToString($label)
    {
      $str = '';
      if ( isset($this->queues[$label]) && $this->queues[$label] )
	{
	  $str = implode("\n", $this->queues[$label]) . "\n"; 
	  $this->queues[$label] = array();
	}
      return $str;
    }

  function save()
    {
      if ( isset($_SESSION) )
	{
	  foreach ($this->queues as $label=>$queue)
	    {
	      $_SESSION[$this->_prefix.$label] = $queue;
	    }
	}
    }

}

?>