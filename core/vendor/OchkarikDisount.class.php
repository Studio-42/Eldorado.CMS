<?php

/**
 * Ochkarik discount poller, gets discount and spent amount for card holder
 *
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

class OchkarikDiscount
{
	var $error        = null;

	var $card_number  = null;
	var $discount     = 0;
	var $spent_amount = 0;

	var $db_serv      = '';
	var $db_user      = '';
	var $db_pass      = '';

	function __construct()
	{
		$this->db_serv = '82.204.249.186';
		$this->db_user = 'webuser';
		$this->db_pass = 'ph7T4uChA6';
	}

	function check($card_number = null)
	{
		if (!(function_exists('sybase_connect')))
		{
			$this->error = 'This class requires SyBase module installed';
			return false;
		}

		if (strlen($card_number) != 8)
		{
			$this->error = 'Required length 8 symbols';
			return false;
		}

		if (!(preg_match('/^\d+$/', $card_number)))
		{
			$this->error = 'Card number must contain only numbers';
			return false;
		}
		
		$this->card_number = $card_number;
		if ($this->queryDatabase())
		{
			return true;
		}
		else
		{
			return false;
		}
		return false;
	}

	function queryDatabase()
	{
		// TODO reuse database link
		$sql = 'EXEC sp_ClubCardPerCent @ClubCardNum="'.$this->card_number.'"';
		if ($link = sybase_connect($this->db_serv, $this->db_user, $this->db_pass))
		{
			// ok
		}
		else
		{
			$this->error = 'Cannot connect to server';
			return false;
		}

		if ($q = sybase_query($sql))
		{
			$r = sybase_fetch_assoc($q);
			if (empty($r))
			{
				$this->error = 'No information found about card';
				return false;
			}
			$this->discount = $r['PrSk'];
			$this->spent_amount = $r['sSumma'];
			return true;
		}
		else
		{
			$this->error = 'Query failed';
			$this->error .= ' ('.sybase_get_last_message().')';
			return false;
		}
		return false;
	}
}

