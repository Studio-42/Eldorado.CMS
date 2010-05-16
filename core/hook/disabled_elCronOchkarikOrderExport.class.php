<?php

class elCronOchkarikOrderExport {

	function run()
	{
		include_once './core/vendor/OchkarikOrderExport.class.php';
		
		$ooe = new OchkarikOrderExport;
		$ooe->run();
	}

}
