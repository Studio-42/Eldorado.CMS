<?php

class elHookOrderComplete
{
	function run()
	{
		exec('php ./core/vendor/OchkarikOrderExport2.class.php > /dev/null &');
		return true;
	}
}
