<?php

class elICartRnd {
	var $_te = null;
	var $url = '';

	var $steps = array();
	var $precision = 0;
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function elICartRnd() {
		$this->_te = & elSingleton::getObj('elTE');

	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function addToContent($str) {
		$this->_te->assignVars('PAGE', $str, true);
	}
	
	/**
	 * render shopping cart
	 *
	 * @return void
	 **/
	function rndICart($icart) {
		$this->_rndCommon('cart');
		$this->_te->setFile('ICART_CONTENT', 'services/ICart/icart.html');
		
		$this->_te->assignVars('currencySign', $icart->getCurrencySymbol());
		$this->_te->assignVars('amount', $icart->amountFormated);
		$items = $icart->getItems();

		$wishlist = array();
		foreach ($items as $item) {
			if ($item['wishlist'] == 1)
			{
				array_push($wishlist, $item);
				continue;
			}
			$data = array(
				'id'    => $item['id'],
				'code'  => $item['code'],
				'name'  => $item['name'],
				'qnt'   => $item['qnt'],
				'price' => $item['priceFormated'],
				'sum'   => $item['sum'],
				'props' => ''
				);
			if (!empty($item['props'])) {
				foreach ($item['props'] as $p) {
					$data['props'] .= "$p[0]: $p[1]<br/>";
				}
			}
			$this->_te->assignBlockVars('ICART_ITEM', $data);
		}
		$this->_te->parse('ICART_CONTENT');

		if (!empty($wishlist))
		{
			$this->_te->setFile('ICART_WISHLIST', 'services/ICart/wishlist.html');
			//echo 'wow';
			$nav = & elSingleton::getObj('elNavigator');
			foreach ($wishlist as $d)
			{
				$data = array(
					'link'  => $nav->getPageURL($d['page_id']).'find/'.$d['i_id'],
					'id'    => $d['id'],
					'code'  => $d['code'],
					'name'  => $d['name'],
					'price' => $d['priceFormated'],
					'props' => ''
				);
				if (!empty($d['props'])) {
					foreach ($d['props'] as $p) {
						$data['props'] .= "$p[0]: $p[1]<br/>";
					}
				}
				$this->_te->assignBlockVars('WISHLIST_ITEM', $data);
			}
			// and so on
			$this->_te->parse('ICART_WISHLIST');
		}
	}
	
	/**
	 * render delivery/payment selection
	 *
	 * @return void
	 **/
	function rndDelivery($regions, $delivery, $payment, $val) {
		
		$this->_rndCommon('delivery');
		$this->_te->setFile('ICART_CONTENT', 'services/ICart/delivery.html');
		
		foreach ($regions as $one) {
			$one['selected'] = $one['id'] == $val['region_id'] ? ' selected="on"' : '';
			$this->_te->assignBlockVars('ICART_REGION', $one);
		}
		foreach ($delivery as $one) {
			$one['selected'] = $one['id'] == $val['delivery_id'] ? ' selected="on"' : '';
			$this->_te->assignBlockVars('ICART_DELIVERY', $one);
		}
		foreach ($payment as $one) {
			$one['selected'] = $one['id'] == $val['payment_id'] ? ' selected="on"' : '';
			$this->_te->assignBlockVars('ICART_PAYMENT', $one);
		}

		$this->_te->assignVars(array(
			'delivery_price'   => $val['price'],
			'delivery_comment' => $val['comment']
			));
		
		$this->_te->parse('ICART_CONTENT');	
	}
	
	
	/**
	 * render address form
	 *
	 * @return void
	 **/
	function rndAddress($form) {
		$this->_rndCommon('address');
		$this->_te->assignVars('ICART_CONTENT', $form);
	}
	
	
	function rndConfirm($icart, $delivery, $address, $total) { 
		$this->_rndCommon('confirm');
		$this->_te->setFile('ICART_CONTENT', 'services/ICart/summary.html');

		$this->_rndSummary($icart, $delivery, $address, $total);

		$this->_te->parse('ICART_CONTENT');
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function rndMailContent($icart, $delivery, $address, $total, $orderID) {
		$this->_te->setFile('ICART_MAIL', 'services/ICart/mail.html');
		$this->_te->assignVars(array(
			'orderID' => $orderID,
			'date' => date(EL_DATETIME_FORMAT)
			));
		$this->_rndSummary($icart, $delivery, $address, $total);
		$this->_te->parse('ICART_MAIL', 'ICART_MAIL', false, true, true);
		return $this->_te->getVar('ICART_MAIL');
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _rndSummary($icart, $delivery, $address, $total) {
		$this->_te->assignVars('currencySign', $icart->getCurrencySymbol());
		$this->_te->assignVars('amount', $icart->amountFormated);
		
		$items = $icart->getItems();

		foreach ($items as $item) {
			if ($item['wishlist'] == 1)
			{
				continue;
			}
			$data = array(
				'id'    => $item['id'],
				'code'  => $item['code'],
				'name'  => $item['name'],
				'qnt'   => $item['qnt'],
				'price' => $item['priceFormated'],
				'sum'   => $item['sum'],
				'props' => ''
				);
			if (!empty($item['props'])) {
				foreach ($item['props'] as $p) {
					$data['props'] .= "$p[0]: $p[1]<br/>";
				}
			}
			$this->_te->assignBlockVars('ICART_ITEM', $data);
		}
		
		if ($this->steps['delivery']['enable']) {
			//if ($delivery['fee']) {
				$this->_te->assignBlockVars('ICART_DELIVERY_PRICE', array('price' => $delivery['price'], 'total' => $total));
			//}
			if ($delivery['region_id']) {
				$this->_te->assignBlockVars('ICART_DELIVERY', $delivery);
				if ($delivery['comment']) {
					$this->_te->assignBlockVars('ICART_DELIVERY.COMMENT', $delivery, 1);
				}
			}
		}
		
		$this->_te->assignBlockfromArray('ICART_ADDR', $address);
	}
	
	/**
	 * need for compatibility
	 *
	 * @return void
	 **/
	function renderComplite() { }
	
	/**
	 * render common part of order template
	 *
	 * @return void
	 **/
	function _rndCommon($step) {
		
		$this->_te->setFile('PAGE', 'services/ICart/default.html');
		$this->_te->assignVars('iCartStepTitle', m($this->steps[$step]['label']));
		$this->_te->assignVars('iCartURL', $this->url.'__icart__/');
		$this->_te->assignVars('buttonText', m('Continue').' &raquo;');
		$this->_te->assignVars('stepID', $step);

		foreach ($this->steps as $k=>$v) {
			if ($v['enable']) {
				$block = $v['allow'] ? 'ICART_STEP_AVAIL' : 'ICART_STEP_DISABLE';
				$data = array(
					'url'      => $this->url.'__icart__/'.($k == 'cart' ? '' : $k.'/'),
					'name'     => m($v['label']),
					'cssClass' => $k == $step ? 'icart-nav-active' : ''
					);
				$this->_te->assignBlockVars('ICART_STEP.'.$block, $data);
			}
		}
	}
	

	
}


class _elICartRnd extends elModuleRenderer
{
    var $_te        = null;
    var $_conf      = array();
    var $_dir       = '';
    var $_tpls      = array(
		'icart'    => 'icart.html',
        'delivery' => 'delivery.html',
        'summary'  => 'summary.html',
		'letter'   => 'letter.html',
		'wishlist' => 'wishlist.html'
	);

    /**
     * получает мсасив-конфигурацию корзины
     *
     **/
    function setConf( $conf )
    {
        $this->_conf = $conf;
    }
    /**
     * Устанавливает массив-кол-во шагов в корзине,
     * текущий шаг и макимально допустимый для клиента шаг
     **/
    function setSteps($steps, $curStepID, $maxStepID)
    {
        $this->_steps     = $steps;
        $this->_curStepID = $curStepID;
        $this->_maxStepID = $maxStepID; 
    }
    
  
    
    /**
     * Рисует товары в корзине
     **/
    function rndICart( $items )
    {
		$currency = & elSingleton::getObj('elCurrency');
        $this->_rndCommon();
        $this->_setFile('icart', 'ICART_CONTENT');
        $amount = 0;
        foreach ( $items as $i )
        {

            $amount += $i['sum'];
            $i['price'] = $currency->format($i['price'], array('precision' => $this->_conf['precision']));
            $i['sum']   = $currency->format($i['sum'],   array('precision' => $this->_conf['precision']));
            $props = $i['props'];
            unset($i['props']);
            $this->_te->assignBlockVars('ICART_ITEM', $i);
            if ( !empty($props) )
            {
                foreach ( $props as $p )
                {
                    $data = array('name'=>$p[0], 'value'=>$p[1]);
                    $this->_te->assignBlockVars('ICART_ITEM.IC_IPROPS.IC_IPROP', $data, 2);
                }
            }
        }
        $this->_te->assignVars('amount', $currency->format($amount, array('precision' => $this->_conf['precision'])) );
		$this->_te->assignVars('currencySign', $currency->current['symbol']);
    }
    
    /**
     * Рисует форму доставки-оплаты
     **/
    function rndDelivery()
    {
        $this->_rndCommon();
        $this->_setFile('delivery', 'ICART_CONTENT');
    }
    
    /**
     * Рисует форму - инфа о покупателе
     **/
    function rndAddressForm( $formHtml )
    {
        $this->_rndCommon();
        $this->_te->assignVars( 'ICART_CONTENT', $formHtml );
    }
    
    /**
     *  Рисует сводную таблицу заказа
     **/
    function rndSummary($items, $delivery, $amount, $addr)
    {
        $this->_rndCommon();
        $this->_te->assignVars('ICART_CONTENT', $this->getSummaryRnd($items, $delivery, $amount, $addr) );
    }
    
    /**
     * Возвращает содержимое сводной таблицы заказа
     * для отрисовки или отправке по e-mail
     **/
    function getSummaryRnd($items, $delivery, $amount, $addr, $rnd=true, $orderID=0)
    {
		$currency = &elSingleton::getObj('elCurrency');
		if ($rnd)
		{
			$this->_setFile('summary', 'SUMMARY');
			$this->_te->assignBlockVars('ICART_BUTTON');
		}
		else
		{
			$this->_setFile('letter', 'SUMMARY', true);
			$this->_te->assignVars('date', date(EL_DATETIME_FORMAT));
			$this->_te->assignVars('order_id', $orderID);
		}

        $this->_te->assignVars('currencySign', $currency->current['symbol']);
        foreach ( $items as $i )
        {
			if ($rnd && !$i['display_code'])
			{
				$i['code'] = '';
			}
            $i['price'] = $currency->format($i['price'], array('precision' => $this->_conf['precision']));
            $i['sum']   = $currency->format($i['sum'],   array('precision' => $this->_conf['precision']));
            $this->_te->assignBlockVars('ICART_ITEM', $i);
            if ( !empty($i['props']) )
            {
                foreach ( $i['props'] as $p )
                {
                    $data = array('name'=>$p[0], 'value'=>$p[1]);
                    $this->_te->assignBlockVars('ICART_ITEM.IC_IPROPS.IC_IPROP', array('name'=>$p[0], 'value'=>$p[1]), 2);
                }
            }
        }
        $this->_te->assignVars('amount', $currency->format($amount, array('precision' => $this->_conf['precision']))); 
        
        if ( !empty($delivery) )
        {
            $this->_te->assignBlockVars('ICART_DELIVERY');
        }
        
        $this->_te->assignBlockFromArray('ICART_ADDR', $addr);
        $this->_te->parse('SUMMARY', null, false, true);
        return $this->_te->getVar('SUMMARY');
    }
    
    /*********************************************************/
    /**                      PRIVATE                        **/
    /*********************************************************/    
    /**
     * Загружает общий шаблон для корзины
     * Отрисовывает меню (шаги) корзины 
     **/
    function _rndCommon()
    {
        $this->_setFile();
        foreach ( $this->_steps as $ID=>$step )
        {
            if ( $ID<=$this->_maxStepID )
            {
                $link     = '<a href="'.EL_URL.'__icart__/'.$ID.'/">'.$step.'</a>';
                if ($ID == $this->_curStepID)
                {
                    $cssClass = 'iCartCurStep';
                    $this->_te->assignVars('iCartStepTitle', $step);
                }
                else
                {
                    $cssClass ='iCartStep';    
                }
                
            }
            else
            {
                $link     = $step;
                $cssClass = 'iCartStepDisable';
            }
            $this->_te->assignVars('stepID', $this->_curStepID);
            $this->_te->assignBlockVars('ICART_STEP', array('stepID'=>$ID, 'link'=>$link, 'cssClass'=>$cssClass) );
        }
    }

 
}

