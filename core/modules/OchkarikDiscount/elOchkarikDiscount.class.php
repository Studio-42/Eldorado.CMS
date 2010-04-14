<?php

class elOchkarikDiscount extends elDataMapping
{
	var $_tb          = 'el_och_discount';
	var $_id          = 'card_num';

	var $card_num     = 0;
	var $discount     = 0;
	var $spent_amount = 0;
	var $update_time  = 0;
	var $query_ip     = '';
	var $query_time   = 0;
	var $query_count  = 0;

	function _initMapping()
	{
		return array(
			'card_num'     => 'card_num',
			'discount'     => 'discount',
			'spent_amount' => 'spent_amount',
			'update_time'  => 'update_time',
			'query_ip'     => 'query_ip',
			'query_time'   => 'query_time',
			'query_count'  => 'query_count'
		);
	}

	function updateCache($cn = null, $discount = null, $spent_amount = null)
	{
		if ($cn == null)
		{
			return false;
		}

		$new = false;
		$this->idAttr($cn);
		if (!$this->fetch()) // new
		{
			$new = true;
		}
		$cache = $this->attr();

		$cache['query_time']   = time();
		$cache['query_count'] += 1;
		$cache['query_ip']     = $_SERVER['REMOTE_ADDR'];

		if (($discount != null) && ($spent_amount != null))
		{
			$cache['discount']     = $discount;
			$cache['spent_amount'] = $spent_amount;
			$cache['update_time']  = time();
		}

		$this->attr($cache);

		if ($new == true)
		{
			$tmp_id = $this->_id;
			$this->_id = null;
			$this->attr($tmp_id, $cn);
			$this->save();
			$this->_id = $tmp_id;
		}
		else
		{
			$this->save();
		}

		return true;
	}
}

