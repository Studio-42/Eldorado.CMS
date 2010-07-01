<?php

class elHookOrderComplete
{
	function run()
	{
		exec('php ./core/vendor/OchkarikOrderExport.class.php > /dev/null &');
		return true;
	}
}
