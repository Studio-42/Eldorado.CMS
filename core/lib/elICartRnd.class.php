<?php

class elICartRnd {
	var $_te = null;
	var $url = '';
	var $_steps = array(
		'icart'    => 'Products',
		'delivery' => 'Delivery/payment',
		'address'  => 'Address',
		'complete' => 'Confirmation'
		);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function elICartRnd() {
		$this->_te = & elSingleton::getObj('elTE');
		
		foreach ($this->_steps as $k=>$v) {
			$this->_steps[$k] = m($v);
		}
	}
	
	/**
	 * render shopping cart
	 *
	 * @return void
	 **/
	function rndICart($icart) {
		$this->_rndCommon('icart');
		$this->_te->setFile('ICART_CONTENT', 'services/ICart/icart.html');
		
		$this->_te->assignVars('currencySign', $icart->getCurrencySymbol());
		$this->_te->assignVars('amount', $icart->amountFormated);
		$items = $icart->getItems();
		

		foreach ($items as $item) {
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
				foreach ($item['props'] as $k=>$v) {
					$data['props'] .= "$k: $v<br/>";
				}
			}
			$this->_te->assignBlockVars('ICART_ITEM', $data);
		}
		
		$this->_te->parse('ICART_CONTENT');
	}
	
	/**
	 * render delivery/payment selection
	 *
	 * @return void
	 **/
	function rndDelivery($regions, $delivery, $payment, $val) {
		$this->_rndCommon('delivery');
		$this->_te->setFile('ICART_CONTENT', 'services/ICart/delivery.html');
		
		$this->_te->assignBlockFromArray('ICART_REGION',   $regions);
		$this->_te->assignBlockFromArray('ICART_DELIVERY', $delivery);
		$this->_te->assignBlockFromArray('ICART_PAYMENT',  $payment);
		$this->_te->assignVars(array(
			'delivery_fee'=>$val['fee'],
			'delivery_comment' => $val['comment']
			));
		// $this->_te->assignVars('delivery_fee', $val['fee']);
		
		
		$this->_te->parse('ICART_CONTENT');	
	}
	
	
	function renderComplite() { }
	
	/**
	 * render common part of order template
	 *
	 * @return void
	 **/
	function _rndCommon($step) {
		$this->_te->setFile('PAGE', 'services/ICart/default.html');
		$this->_te->assignVars('iCartStepTitle', $this->_steps[$step]);
		$this->_te->assignVars('iCartURL', $this->url.'__icart__/');
		$this->_te->assignVars('buttonText', $step == 'complete' ? m('Complete') : m('Continue').' &raquo;');
		$allow = true;
		foreach ($this->_steps as $k=>$v) {
			$link = $allow ? '<a href="'.$this->url.'__icart__/'.($k == 'icart' ? '' : $k.'/').'">'.$v.'</a>': $v;
			
			$this->_te->assignBlockVars('ICART_STEP', array('link' => $link));
			$this->_te->assignVars('stepID', $step);
			if ($allow && $k == $step) {
				$allow = false;
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
		'letter'   => 'letter.html'
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

