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
	var $_total       = 0;
	var $_amount      = 0;
	var $_rnd         = null;
	var $_conf        = null;
	

    function elICart()
    {
        $this->_db       = & elSingleton::getObj('elDb');
        $this->_conf     = & elSingleton::getObj('elXmlConf');
        $this->_SID      = mysql_real_escape_string(session_id());
        $ats             = & elSingleton::getObj('elATS');
        $this->_UID      = $ats->getUserID();
        if ( $this->_UID )
        {
            $sql = 'UPDATE '.$this->_tb.' SET sid=\''.$this->_SID.'\' WHERE uid=\''.$this->_UID.'\'';
            $this->_db->query($sql);
        }
        $this->_loadSummary();
    }
    
    function getTotalQnt()
    {
        return $this->_total;
    }
    
    function getAmount()
    {
        return $this->_amount;
    }
    
    function getItems()
    {
		return $this->_getItems();
 
    }
    
    function getItemsRaw()
    {
        return $this->_getItems();
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
			$sql = 'INSERT INTO el_icart (sid, uid, page_id, i_id, m_id, code, display_code, name, qnt, price, props, crtime, mtime) '
                        .'VALUES         ("%s", %d, %d,      %d,   %d,   "%s", 1,            "%s", 1,   "%s",  "%s",  %d,     %d)';
			$sql = sprintf($sql, $this->_SID, $this->_UID, $item['page_id'], $item['i_id'], $item['m_id'], 
				mysql_real_escape_string($item['code']), mysql_real_escape_string($item['name']), $item['price'], 
				!empty($item['props']) ? serialize($item['props']) : '', time(), time() );
			
		}
		return $this->_db->query($sql);
	}

    /**
     * Добавляет в корзину товар из модуля IShop
     *
     */
    function addIShopItem($pageID, $iID, $props=null)
    {
        $iID        = intval($iID); 
        $modConfig  = $this->_conf->getGroup($pageID);
        $itemProps  = array();
        elSingleton::incLib('modules/IShop/elIShopFactory.class.php', true);
        $factory    = & elSingleton::getObj('elIShopFactory');
        $factory->init($pageID, $modConfig);
        $item       = $factory->getItem($iID);
        $dispayCode = (int)!empty($modConfig['displayCode']);
		
		if (empty($item->name))
		{
			return '';
		}
        if (is_array($props))
        {
            foreach ($props as $pID => $v)
                $itemProps[] = array($item->getPropName($pID), $v);
            $pSerial = mysql_real_escape_string(serialize($itemProps));
        }
        else
            $pSerial = $props;
        $sqlIns = 'INSERT INTO %s (sid, uid, shop, i_id, code, display_code, name, qnt, price, props, crtime, mtime) '
            .'VALUES (\'%s\', \'%d\', \'IShop\', \'%d\', \'%s\',     %d,     \'%s\', \'1\', \'%s\', \'%s\', \'%d\', \'%d\')';
        $sqlUpd = 'UPDATE %s SET sid=\'%s\', uid=\'%d\', i_id=\'%d\', code=\'%s\', display_code=\'%s\', '
            .'name=\'%s\', qnt=qnt+1, price=\'%s\', props=\'%s\', mtime=\'%d\' WHERE id=\'%d\'';         
       
        $sql = 'SELECT id FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\' AND shop=\'IShop\' AND i_id=\''.$iID.'\' AND props=\''.$pSerial.'\'';
        $this->_db->query( $sql );
       
        if ( !$this->_db->numRows() )
        { 
            $sql = sprintf($sqlIns, $this->_tb, $this->_SID, $this->_UID, $iID,
                           $item->code, $displayCode, $item->name, $item->price, $pSerial, time(), time()); 
        }
        else
        {
            $r = $this->_db->nextRecord();
            $sql = sprintf($sqlUpd, $this->_tb, $this->_SID, $this->_UID, $iID,
                           $item->code, $displayCode, $item->name, $item->price, $pSerial, time(), (int)$r['id']);
        }
        return $this->_db->query($sql) ? $item->name : ''; 
    }
    
    function addTechShopItem($pageID, $iID, $mID = NULL)
    {
      	$iID       = intval($iID);
        $mID       = intval($mID); //echo $iID.' '.$mID; exit;
        elSingleton::incLib('modules/TechShop/elTSFactory.class.php', true);
        $factory   = & elSingleton::getObj('elTSFactory');
        $factory->init($pageID);
        $modConfig = $this->_conf->getGroup($pageID);
        
        $item      = $factory->create(EL_TS_ITEM, $iID);
        
        if ( !$item->ID )
        {
            return false;
        }
        
        if ( !$mID )
        {
            $code = !empty($modConfig['dislayCode']) ? $item->code : '';
            $sql  = 'SELECT id FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\' AND shop=\'TechShop\' AND i_id=\''.$iID.'\'';
            $this->_db->query($sql);
            if ( !$this->_db->numRows() )
            {
                $sqlIns = 'INSERT INTO %s (sid, uid, shop, i_id, code, name, qnt, price, props, crtime, mtime) '
                            .'VALUES (\'%s\', \'%d\', \'TechShop\', \'%d\', \'%s\', \'%s\', \'1\', \'%s\', \'%s\', \'%d\', \'%d\')';
                $sql = sprintf($sqlIns, $this->_tb, $this->_SID, $this->_UID, $item->ID,
                           $code, $item->name, $item->price, '', time(), time());
            }
            else
            {
                $r = $this->_db->nextRecord();
                $sqlUpd = 'UPDATE %s SET sid=\'%s\', uid=\'%d\', i_id=\'%d\', code=\'%s\', '
                            .'name=\'%s\', qnt=qnt+1, price=\'%s\', props=\'%s\', mtime=\'%d\' WHERE id=\'%d\'';
                $sql = sprintf($sqlUpd, $this->_tb, $this->_SID, $this->_UID, $item->ID,
                           $code, $item->name, $item->price, '', time(), (int)$r['id']);
            }
            return $this->_db->query($sql) ? $item->name : ''; 
        }
        else
        {
            $model = $factory->create(EL_TS_MODEL,$mID);
            if (empty($model->ID))
            {
                return false;
            }
            
            $mCode = !empty($modConfig['dislayCode']) ? $model->code : '';
            $name = $model->name;
            $sql  = 'SELECT id FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\' AND shop=\'TechShop\' AND i_id=\''.$iID.'\' AND m_id=\''.$mID.'\'';
            $this->_db->query($sql);
            if ( !$this->_db->numRows() )
            {
                $sqlIns = 'INSERT INTO %s (sid, uid, shop, i_id, m_id, code, name, qnt, price, props, crtime, mtime) '
                            .'VALUES (\'%s\', \'%d\', \'TechShop\', \'%d\', \'%d\', \'%s\', \'%s\', \'1\', \'%s\', \'%s\', \'%d\', \'%d\')';
                $sql = sprintf($sqlIns, $this->_tb, $this->_SID, $this->_UID, $item->ID, $mID,
                           $code, $name, $model->price, '', time(), time());
            }
            else
            {
                $r = $this->_db->nextRecord();
                $sqlUpd = 'UPDATE %s SET sid=\'%s\', uid=\'%d\', i_id=\'%d\', m_id=\'%d\', code=\'%s\', '
                            .'name=\'%s\', qnt=qnt+1, price=\'%s\', props=\'%s\', mtime=\'%d\' WHERE id=\'%d\'';
                $sql = sprintf($sqlUpd, $this->_tb, $this->_SID, $this->_UID, $item->ID, $mID,
                           $code, $name, $model->price, '', time(), (int)$r['id']);
            }
            return $this->_db->query($sql) ? $name : ''; 
        }
        

	
	
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

    function _loadSummary()
    {
        $this->_db->query('SELECT SUM(qnt) AS total, SUM(qnt*price) AS amount FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\'');
        if ( $this->_db->numRows() )
        {
            $r = $this->_db->nextRecord();
            $this->_total  = $r['total'];
            $this->_amount = $r['amount'];
        }
    }
    
    function _load()
    {
        if ( !$this->_itemsLoaded && !$this->isEmpty() )
        {
            $sql = 'SELECT id, shop, page_id, i_id, m_id, code, display_code, name, qnt, price, props, url FROM '.$this->_tb.' WHERE sid=\''.$this->_SID.'\' ORDER BY shop, code, name';
            //$this->_items = $this->_db->queryToArray( $sql, 'id'); 
			$this->_db->query($sql);
			while($r = $this->_db->nextRecord())
			{
				$this->_items[$r['id']] = $r;
				$this->_items[$r['id']]['props'] = !empty($r['props']) ? unserialize($r['props']) : array();
				$this->_items[$r['id']]['sum'] = $r['price']*$r['qnt'];
			}
        }
        $this->_itemsLoaded = true;
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
