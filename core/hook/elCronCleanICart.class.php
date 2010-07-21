<?php

/**
 * Clean icart table
 **/
class elCronCleanICart
{
	var $anonymous_ttl = 30;  // in days
	var $user_ttl      = 150; // in days

	function run()
	{
		$db = & elSingleton::getObj('elDb');
		$tb = 'el_icart';
		if ($db->isTableExists($tb))
		{
			// Clean anonymous users' icart
			$sql = 'DELETE FROM '.$tb.' WHERE uid=0 AND mtime < (unix_timestamp() - (86400 * '.(int)$anonymous_ttl.'))';
			$db->query($sql);

			// Clean registered users' icart
			$sql = 'DELETE FROM '.$tb.' WHERE uid>0 AND mtime < (unix_timestamp() - (86400 * '.(int)$user_ttl.'))';
			$db->query($sql);
		}
	}

} // END class elCronCleanICart
