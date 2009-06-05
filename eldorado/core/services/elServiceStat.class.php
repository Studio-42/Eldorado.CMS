<?php

class elServiceStat extends elService
{
	
	function defaultMethod()
	{
		$dir = getcwd(); echo $dir;
		elPrintR($this->_args);
		
		if ('admin' == $this->_args[0])
		{
			chdir('./stat/admin');
			include_once './index.php';
			exit();
		}
		else
		{
		chdir('./stat');
		include_once './index.php';
		}
	}
	
}

?>