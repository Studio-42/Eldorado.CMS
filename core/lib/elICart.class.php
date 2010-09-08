<?php

class elICart
{
	var $_tb          = 'el_icart';
	// var $_tbo         = 'el_order';
	// var $_tboi        = 'el_order_item';
	// var $_tboc        = 'el_order_customer';
	var $_db          = null;
	var $_SID         = '';
	var $_UID         = 0;
	var $_items       = array();
	var $_itemsLoaded = false;
	var $qnt          = 0;
	var $amount       = 0;
	var $wishlist     = 0;
	var $_rnd         = null;
	var $conf         = array();
	var $_precision   = 0;
	

    function elICart()
    {
		$ats              = & elSingleton::getObj('elATS');
        $this->_UID       = $ats->getUserID();
        $this->_db        = & elSingleton::getObj('elDb');
        $this->_conf      = & elSingleton::getObj('elICartConf');
		$this->_currency  = & elSingleton::getObj('elCurrency');
		$this->_precision = $this->_conf->precision() > 0 ? 2 : 0;
        $this->_SID       = mysql_real_escape_string(session_id());
        
		$this->_load();
    }
    
	function getItems() {
		return $this->_items;
	}
    
	function getCurrencySymbol() {
		return $this->_currency->getSymbol();
	}

    function isEmpty()
    {
        return !($this->qnt);
    }
    
    function deleteItem($ID)
    {
		$this->_db->query(sprintf('DELETE FROM %s WHERE id=%d  AND sid="%s" LIMIT 1', $this->_tb, $ID, $this->_SID));
        $this->_db->optimizeTable( $this->_tb );
        $this->_reload();
    }
    
	function clean($wishlist = 0) {
		$this->_db->query(sprintf('DELETE FROM %s WHERE sid="%s" and wishlist="%d"', $this->_tb, $this->_SID, $wishlist));
        $this->_db->optimizeTable( $this->_tb );
        $this->_reload();
	}

    function update($iqnt)
    {
		foreach ($iqnt as $id=>$qnt) {
			$qnt = (int)$qnt;
			if ($qnt>0) {
				$sql = sprintf('UPDATE %s SET qnt=%d WHERE id=%d AND sid="%s" LIMIT 1', $this->_tb, $qnt, $id, $this->_SID);
			} else {
				$sql = sprintf('DELETE FROM %s WHERE id=%d AND sid="%s"', $this->_tb, $id, $this->_SID);
				
			}
			$this->_db->query($sql);
		}
		$this->_db->optimizeTable( $this->_tb );
        $this->_reload();
   }
    
	function add($item) {
		if (false != ($ID = $this->_find($item))) {
			$sql = 'UPDATE el_icart SET qnt=qnt+1, mtime=%d WHERE id=%d';
			$sql = sprintf($sql, time(), $ID);
		} else {
			$item['code']  = mysql_real_escape_string($item['code']);
			$item['name']  = mysql_real_escape_string($item['name']);
			$item['props'] = !empty($item['props']) ? mysql_real_escape_string(serialize($item['props'])) : '';
			$sql = 'INSERT INTO el_icart '
			. '(sid, uid, page_id, i_id, m_id, code, name, qnt, price, props, wishlist, crtime, mtime) '
	. "VALUES ('%s',  %d,      %d,   %d,   %d,  '%s','%s',   1,  '%s',  '%s',     '%d',     %d,    %d)";
			$sql = sprintf($sql, $this->_SID, $this->_UID, $item['page_id'], $item['i_id'], $item['m_id'], $item['code'], $item['name'], $item['price'], $item['props'], $item['wishlist'], time(), time() );
		}
		return $this->_db->query($sql);
	}

    /*******************************************************/
    /*                  PRIVATE                            */
    /*******************************************************/
    
	function _find($item) {
		$items = $this->getItems();
		foreach ($items as $i) {
			if (   $i['page_id'] == $item['page_id'] 
				&& $i['i_id']    == $item['i_id'] 
				&& $i['m_id']    == $item['m_id']
				&& $i['props']   == $item['props']
				&& $i['wishlist']== $item['wishlist']) 
			{
				return $i['id'];
			}
		}
	}

    function _load()
    {
		$opts = array('precision' => $this->_precision);
		$this->_db->query(sprintf('SELECT id, page_id, i_id, m_id, code, name, qnt, price, props, wishlist FROM %s WHERE sid="%s" ORDER BY  code, name', $this->_tb, $this->_SID));
		while($r = $this->_db->nextRecord())
		{
			$sum                             = $r['price']*$r['qnt'];
			$this->_items[$r['id']]          = $r;
			$this->_items[$r['id']]['props'] = !empty($r['props']) ? unserialize($r['props']) : array();
			$this->_items[$r['id']]['price'] = $r['price'];
			$this->_items[$r['id']]['priceFormated'] = $this->_currency->format($r['price'], $opts);
			$this->_items[$r['id']]['sum']   = $this->_currency->format($sum, $opts);
			if ($r['wishlist'] == 0)
			{
				$this->qnt                  += $r['qnt'];
	            $this->amount               += $sum;
			}
			else
			{
				$this->wishlist             += 1; // no matter witch qnt in wishlist
			}
		}
		$this->amountFormated = $this->_currency->format($this->amount, $opts);
    }
    
    function _reload()
    {
        $this->_total = $this->_amount = 0;
        $this->_items = array();
        $this->_load();
    }
    
    function _getItems()
    {
        if ( !$this->_itemsLoaded )
        {
            $this->_load();
        }
        return $this->_items;
    }
    
 
}
?>
