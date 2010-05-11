<?php

class elICart
{
	var $_tb          = 'el_icart';
	var $_tbo         = 'el_order';
	var $_tboi        = 'el_order_item';
	var $_tboc        = 'el_order_customer';
	var $_db          = null;
	var $_SID         = '';
	var $_UID         = 0;
	var $_items       = array();
	var $_itemsLoaded = false;
	var $qnt       = 0;
	var $amount      = 0;
	var $_rnd         = null;
	var $_conf        = array();
	var $_precision   = 0;
	

    function elICart()
    {
        $this->_db       = & elSingleton::getObj('elDb');
        $this->_conf     = & elSingleton::getObj('elICartConf');
		$this->_currency = & elSingleton::getObj('elCurrency');
		$this->_precision = $this->_conf->precision() > 0 ? 2 : 0;
        $this->_SID      = mysql_real_escape_string(session_id());
        $ats             = & elSingleton::getObj('elATS');
        $this->_UID      = $ats->getUserID();
		$this->_load();
    }
    

    
	function getItems()
	{
		return $this->_items;
	}
    
    function isEmpty()
    {
        return !$this->_total;
    }
    
    function removeItem($ID)
    {
        $items = $this->_getItems();
        if ( empty($items[$ID]) )
        {
            return false;
        }
        if ( !$this->_db->query('DELETE FROM '.$this->_tb.' WHERE id='.$ID) )
        {
            return false;
        }
        $res = $this->_db->affectedRows();
        $this->_db->optimizeTable( $this->_tb );
        $this->_reload();
        return $res;
    }
    
    function updateQnt( $iqnt )
    {
        $items = $this->_getItems();
        if ( !is_array($iqnt) )
        {
            return;
        }
        $this->_db->query('DELETE FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\' AND id NOT IN ('.implode(',', array_keys($iqnt) ).') ');
        foreach ($iqnt as $ID=>$qnt)
        {
            if ( !empty($items[$ID]) )
            {
                $sql = ( 0>= $qnt )
                    ? 'DELETE FROM '.$this->_tb.' WHERE id='.intval($ID).' AND sid=\''.$this->_SID.'\''
                    : 'UPDATE '.$this->_tb.' SET qnt='.intval($qnt).' WHERE id='.intval($ID).' AND sid=\''.$this->_SID.'\'';
                $this->_db->query( $sql );
            }
        }
        $this->_db->optimizeTable( $this->_tb );
    }
    
	function add($item) {
		if (false != ($ID = $this->_find($item))) {
			$sql = 'UPDATE el_icart SET qnt=qnt+1, mtime=%d WHERE id=%d';
			$sql = sprintf($sql, time(), $ID);
		} else {
			$item['code']  = mysql_real_escape_string($item['code']);
			$item['name']  = mysql_real_escape_string($item['name']);
			$item['props'] = !empty($item['props']) ? serialize($item['props']) : '';
			
			$sql = 'INSERT INTO el_icart (sid, uid, page_id, i_id, m_id, code, name, qnt, price, props, crtime, mtime) '
                        .'VALUES         ("%s", %d, %d,      %d,   %d,   "%s", "%s", 1,   "%s",  "%s",  %d,     %d)';
			$sql = sprintf($sql, $this->_SID, $this->_UID, $item['page_id'], $item['i_id'], $item['m_id'], $item['code'], $item['name'], $item['price'], $item['props'], time(), time() );
		}
		return $this->_db->query($sql);
	}



    

	function compliteOrder($customerNfo, $deliveryPrice)
	{
		$this->_load();
		if (!$this->_items)
		{
			return false;
		}
		// TODO discount
		$this->_db->query(
			sprintf('INSERT INTO %s (uid, crtime, mtime, state, amount, delivery_price, total) 
					VALUES (%d, %d, %d, "%s", "%s", "%s", "%s")', 
					$this->_tbo, $this->_UID, time(), time(), "send", $this->_amount, $delivery_price, $this->_amount+$deliveryPrice)
			);
		if (false == ($orderID = $this->_db->insertID()))
		{
			echo 'HERE FUCKING ODER '.$orderID;
			return false;
		}
		
		$crtime = time();

		$this->_db->prepare( 'INSERT INTO '.$this->_tboi.' (order_id, uid, shop, i_id, m_id, code, name, qnt, price, props, crtime) VALUES ',
		 	'(%d, %d, "%s", %d, %d, "%s", "%s", %d, "%s", "%s", %d)');
		foreach ($this->_items as $i)
		{
			$i['props'] = serialize($i['props']);
			$this->_db->prepareData( array($orderID, $this->_UID, $i['shop'], $i['i_id'], $i['m_id'], $i['code'], $i['name'], $i['qnt'], $i['price'], $i['props'], time()) );
		}
		$this->_db->execute();
		
		$this->_db->prepare('INSERT INTO '.$this->_tboc.' (order_id, uid, label, value) VALUES ', '(%d, %d, "%s", "%s")');
		foreach ($customerNfo as $nfo)
		{
			$this->_db->prepareData( array($orderID, $this->_UID, $nfo['label'], $nfo['value']));
		}
		$this->_db->execute();
		
		$this->_db->query('DELETE FROM '.$this->_tb.' WHERE id IN ('.implode(',', array_keys($this->_items)).')');
		$this->_db->optimizeTable($this->_tb);
		return $orderID;
	}
    
    /*******************************************************/
    /*                  PRIVATE                            */
    /*******************************************************/
    
	function _find($item) {
		$items = $this->getItems();
		foreach ($items as $i) {
			if ($i['page_id'] == $item['page_id'] && $i['i_id'] == $item['i_id'] && $i['m_id'] == $item['m_id']) {
				return $i['id'];
			}
		}
	}


    
    function _load()
    {
		$opts = array('precision' => $this->_precision);
		$this->_db->query(sprintf('SELECT id, page_id, i_id, m_id, code, name, qnt, price, props FROM %s WHERE sid="%s" ORDER BY  code, name', $this->_tb, $this->_SID));
		while($r = $this->_db->nextRecord())
		{
			$this->_items[$r['id']] = $r;
			$this->_items[$r['id']]['props'] = !empty($r['props']) ? unserialize($r['props']) : array();
			$this->_items[$r['id']]['price'] = $this->_currency->format($r['price'], $opts);
			$this->_items[$r['id']]['sum'] = $this->_currency->format($r['price']*$r['qnt'], $opts);
			$this->qnt  += $r['qnt'];
            $this->amount += $this->_items[$r['id']]['sum'];
		}
    }
    
    function _reload()
    {
        $this->_itemsLoaded = false;
        $this->_total = $this->_amount = 0;
        $this->_items = array();
        $this->_loadSummary();
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
