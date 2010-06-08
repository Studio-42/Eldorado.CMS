<?php

/**
 * Clean icart table
 **/
class elCronCleanICart
{
	var $age = 15; // in days
	function run()
	{
		$db = & elSingleton::getObj('elDb');
		$tb = 'el_icart';
		if ($db->isTableExists($tb))
		{
			$sql = 'DELETE FROM '.$tb.' WHERE mtime < (unix_timestamp() - (86400 * '.(int)$age.'))';
			$db->query($sql);
		}
	}

} // END class elCronCleanICart
